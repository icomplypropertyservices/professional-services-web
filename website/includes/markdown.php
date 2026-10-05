<?php
declare(strict_types=1);

function ps_parse_front_matter(string $raw): array
{
    $meta = [];
    if (!preg_match('/\A---\R(.*?)\R---\R/s', $raw, $m)) {
        return $meta;
    }
    foreach (preg_split('/\R/', $m[1]) as $line) {
        if (!str_contains($line, ':')) {
            continue;
        }
        [$k, $v] = explode(':', $line, 2);
        $meta[trim($k)] = trim($v, " \t\"'");
    }
    return $meta;
}

function ps_strip_front_matter(string $raw): string
{
    return preg_replace('/\A---\R.*?\R---\R/s', '', $raw, 1) ?? $raw;
}

function ps_extract_meta_table(string $markdown): array
{
    $fields = [];
    if (!preg_match_all('/^\|([^|\n]+)\|(.*)\|\s*$/m', $markdown, $rows, PREG_SET_ORDER)) {
        return $fields;
    }
    foreach ($rows as $row) {
        $key = strtolower(trim($row[1]));
        $value = trim($row[2]);
        if ($key === 'field' || $key === '---' || str_contains($key, '---') || $value === '---') {
            continue;
        }
        $fields[$key] = $value;
    }
    return $fields;
}

function ps_extract_jsonld(string $markdown): ?array
{
    if (!preg_match('/```json\R(.*?)\R```/s', $markdown, $m)) {
        return null;
    }
    $decoded = json_decode($m[1], true);
    return is_array($decoded) ? $decoded : null;
}

function ps_body_markdown(string $raw): string
{
    $body = ps_strip_front_matter($raw);
    $body = preg_replace('/## Meta block \(required\).*?```[ \t]*\R/s', '', $body, 1) ?? $body;
    $body = preg_replace('/^# .+\R/', '', $body, 1) ?? $body;
    return trim($body);
}

function ps_inline_format(string $text): string
{
    $text = ps_h($text);
    $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
    $text = preg_replace('/`([^`]+)`/', '<code>$1</code>', $text) ?? $text;
    return $text;
}

function ps_inline(string $text, int &$imageCounter): string
{
    $tokens = [];
    $text = preg_replace_callback('/!\[([^\]]*)\]\(([^)]+)\)/', function (array $m) use (&$tokens, &$imageCounter): string {
        $alt = trim($m[1]);
        $src = ps_placeholder_src($imageCounter);
        $imageCounter++;
        $tokens[] = '<img src="' . ps_h($src) . '" alt="' . ps_h($alt) . '" width="1200" height="675">';
        return "\x00TOK" . (count($tokens) - 1) . "\x00";
    }, $text) ?? $text;
    $text = preg_replace_callback('/\[([^\]]+)\]\(([^)]+)\)/', function (array $m) use (&$tokens): string {
        $href = ps_rewrite_href($m[2]);
        $tokens[] = '<a href="' . ps_h($href) . '">' . ps_inline_format($m[1]) . '</a>';
        return "\x00TOK" . (count($tokens) - 1) . "\x00";
    }, $text) ?? $text;
    $html = ps_inline_format($text);
    return preg_replace_callback('/\x00TOK(\d+)\x00/', function (array $m) use ($tokens): string {
        return $tokens[(int) $m[1]] ?? '';
    }, $html) ?? $html;
}

function ps_markdown_to_html(string $markdown): string
{
    $markdown = str_replace(["\r\n", "\r"], "\n", $markdown);
    $lines = explode("\n", $markdown);
    $html = [];
    $para = [];
    $list = null;
    $table = [];
    $inCode = false;
    $code = [];
    $imageCounter = 0;

    $flushPara = function () use (&$para, &$html, &$imageCounter): void {
        if ($para === []) {
            return;
        }
        $html[] = '<p>' . ps_inline(implode(' ', $para), $imageCounter) . '</p>';
        $para = [];
    };
    $flushList = function () use (&$list, &$html): void {
        if ($list === null) {
            return;
        }
        $tag = $list['type'];
        $html[] = '<' . $tag . '>' . implode('', $list['items']) . '</' . $tag . '>';
        $list = null;
    };
    $flushTable = function () use (&$table, &$html, &$imageCounter): void {
        if ($table === []) {
            return;
        }
        $rows = [];
        foreach ($table as $i => $cells) {
            $tag = $i === 0 ? 'th' : 'td';
            if ($i === 1 && ps_is_table_sep($cells)) {
                continue;
            }
            $row = '';
            foreach ($cells as $cell) {
                $row .= '<' . $tag . '>' . ps_inline(trim($cell), $imageCounter) . '</' . $tag . '>';
            }
            $rows[] = '<tr>' . $row . '</tr>';
        }
        $html[] = '<table>' . implode('', $rows) . '</table>';
        $table = [];
    };

    foreach ($lines as $line) {
        if (str_starts_with($line, '```')) {
            if ($inCode) {
                $html[] = '<pre><code>' . ps_h(implode("\n", $code)) . '</code></pre>';
                $code = [];
                $inCode = false;
            } else {
                $flushPara();
                $flushList();
                $flushTable();
                $inCode = true;
            }
            continue;
        }
        if ($inCode) {
            $code[] = $line;
            continue;
        }
        if (preg_match('/^\s*\|.*\|\s*$/', $line)) {
            $flushPara();
            $flushList();
            $inner = trim($line, " \t|");
            $table[] = explode('|', $inner);
            continue;
        }
        if ($table !== []) {
            $flushTable();
        }
        if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $hm)) {
            $flushPara();
            $flushList();
            $level = strlen($hm[1]);
            $id = '';
            if ($level === 2 && preg_match('/faq/i', $hm[2])) {
                $id = ' id="faqs"';
            }
            $html[] = '<h' . $level . $id . '>' . ps_inline($hm[2], $imageCounter) . '</h' . $level . '>';
            continue;
        }
        if (preg_match('/^[-*]\s+(.+)$/', $line, $lm)) {
            $flushPara();
            if ($list === null || $list['type'] !== 'ul') {
                $flushList();
                $list = ['type' => 'ul', 'items' => []];
            }
            $list['items'][] = '<li>' . ps_inline($lm[1], $imageCounter) . '</li>';
            continue;
        }
        if (preg_match('/^\d+\.\s+(.+)$/', $line, $lm)) {
            $flushPara();
            if ($list === null || $list['type'] !== 'ol') {
                $flushList();
                $list = ['type' => 'ol', 'items' => []];
            }
            $list['items'][] = '<li>' . ps_inline($lm[1], $imageCounter) . '</li>';
            continue;
        }
        if (trim($line) === '') {
            $flushPara();
            $flushList();
            continue;
        }
        $flushList();
        $para[] = trim($line);
    }
    $flushPara();
    $flushList();
    $flushTable();
    if ($inCode && $code !== []) {
        $html[] = '<pre><code>' . ps_h(implode("\n", $code)) . '</code></pre>';
    }
    return implode("\n", $html);
}

function ps_is_table_sep(array $cells): bool
{
    foreach ($cells as $cell) {
        if (!preg_match('/^:?-{3,}:?$/', trim($cell))) {
            return false;
        }
    }
    return $cells !== [];
}

function ps_fix_jsonld_urls(array $node, string $canonical): array
{
    $walk = function ($value) use (&$walk, $canonical) {
        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = $walk($v);
            }
            return $out;
        }
        if (!is_string($value)) {
            return $value;
        }
        $value = str_replace('`', '', $value);
        if (str_contains($value, '/assets/images/')) {
            return ps_site_url() . '/assets/images/placeholders/ps-1.svg';
        }
        $value = str_replace(
            ps_site_url() . '/pages/keywords/',
            ps_site_url() . '/keywords/',
            $value
        );
        $value = str_replace(ps_site_url() . '/pages/contact', ps_site_url() . '/contact/', $value);
        $value = preg_replace(
            '#' . preg_quote(ps_site_url(), '#') . '/pages/([a-z0-9-]+)/?#',
            ps_site_url() . '/hubs/$1/',
            $value
        ) ?? $value;
        if (($value === '' || str_contains($value, '/pages/')) && str_contains((string) $canonical, 'icomplyprofessionalservices.co.uk')) {
            return $canonical;
        }
        return $value;
    };
    $fixed = $walk($node);
    return is_array($fixed) ? $fixed : $node;
}

/**
 * @return array{title:string,description:string,canonical:string,jsonld:array,body_html:string,h2:array,front:array}
 */
function ps_document_from_markdown(string $raw, string $publicPath): array
{
    $front = ps_parse_front_matter($raw);
    $fields = ps_extract_meta_table($raw);
    $jsonld = ps_extract_jsonld($raw);
    $canonical = ps_canonical_for($publicPath);
    $title = $fields['title'] ?? '';
    $description = $fields['description'] ?? '';
    if ($jsonld !== null) {
        $jsonld = ps_fix_jsonld_urls($jsonld, $canonical);
    }
    $body = ps_markdown_to_html(ps_body_markdown($raw));
    preg_match_all('/<h2[^>]*>(.*?)<\/h2>/s', $body, $h2m);
    $h2 = array_map(static fn (string $h): string => trim(strip_tags($h)), $h2m[1]);
    return [
        'title' => $title,
        'description' => $description,
        'canonical' => $canonical,
        'jsonld' => $jsonld ?? [],
        'body_html' => $body,
        'h2' => $h2,
        'front' => $front,
    ];
}

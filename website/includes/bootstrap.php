<?php
declare(strict_types=1);

const PS_ROOT = __DIR__ . '/..';

function ps_config(): array
{
    static $cfg = null;
    if ($cfg !== null) {
        return $cfg;
    }
    $cfg = require PS_ROOT . '/config/site.php';
    $envUrl = getenv('SITE_URL');
    if (is_string($envUrl) && $envUrl !== '') {
        $cfg['site_url'] = rtrim($envUrl, '/');
    }
    $envLimit = getenv('XPLACE_TOWN_LIMIT');
    if (is_string($envLimit) && $envLimit !== '' && ctype_digit($envLimit)) {
        $cfg['xplace_town_limit'] = (int) $envLimit;
    }
    return $cfg;
}

function ps_h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function ps_site_url(): string
{
    return rtrim((string) ps_config()['site_url'], '/');
}

function ps_canonical_for(string $path): string
{
    $path = trim($path);
    if ($path === '' || $path === '/') {
        return ps_site_url() . '/';
    }
    return ps_site_url() . '/' . trim($path, '/') . '/';
}

function ps_word_count(string $htmlOrText): int
{
    $text = strip_tags($htmlOrText);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    preg_match_all("/[A-Za-z0-9]+(?:'[A-Za-z0-9]+)?/", $text, $m);
    return count($m[0]);
}

function ps_read_lines(string $file): array
{
    if (!is_file($file)) {
        throw new RuntimeException('Missing file: ' . $file);
    }
    $lines = file($file, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        throw new RuntimeException('Unreadable file: ' . $file);
    }
    $out = [];
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line !== '') {
            $out[] = $line;
        }
    }
    return $out;
}

function ps_title_case_slug(string $slug): string
{
    $special = [
        'seo' => 'SEO',
        'ifa' => 'IFA',
        'uk' => 'UK',
        'poa' => 'POA',
        'hr' => 'HR',
        'it' => 'IT',
        'gp' => 'GP',
        'gps' => 'GPs',
        'cqc' => 'CQC',
        'fca' => 'FCA',
        'sra' => 'SRA',
        'rics' => 'RICS',
        'riba' => 'RIBA',
    ];
    $parts = [];
    foreach (explode('-', $slug) as $part) {
        $key = strtolower($part);
        $parts[] = $special[$key] ?? ucfirst($key);
    }
    return implode(' ', $parts);
}

function ps_comb(int $n, int $k): int
{
    static $cache = [];
    $key = $n . ':' . $k;
    if (isset($cache[$key])) {
        return $cache[$key];
    }
    if ($k < 0 || $n < 0 || $k > $n) {
        return $cache[$key] = 0;
    }
    if ($k === 0 || $k === $n) {
        return $cache[$key] = 1;
    }
    $k = min($k, $n - $k);
    $c = 1;
    for ($i = 1; $i <= $k; $i++) {
        $c = intdiv($c * ($n - $k + $i), $i);
    }
    return $cache[$key] = $c;
}

/** Lexicographic combination: indexes of the $idx-th k-subset of n items. */
function ps_nth_combo(int $n, int $k, int $idx): array
{
    $combo = [];
    $b = $k;
    $x = $idx;
    $start = 0;
    $guard = 0;
    while ($b > 0) {
        $placed = false;
        for ($i = $start; $i < $n; $i++) {
            $c = ps_comb($n - $i - 1, $b - 1);
            if ($x < $c) {
                $combo[] = $i;
                $b--;
                $start = $i + 1;
                $placed = true;
                break;
            }
            $x -= $c;
        }
        if (!$placed || ++$guard > $n + 2) {
            throw new RuntimeException('Combination index out of range');
        }
    }
    return $combo;
}

function ps_rewrite_href(string $href): string
{
    $href = trim(str_replace('`', '', $href));
    if ($href === '/TEMPLATE-KEYWORD-UNIQUE.md') {
        return '/keywords/';
    }
    if ($href === '/pages/contact' || str_starts_with($href, '/pages/contact')) {
        return '/contact/';
    }
    if (str_starts_with($href, '/pages/keywords/')) {
        return '/keywords/' . trim(substr($href, strlen('/pages/keywords/')), '/') . '/';
    }
    if (preg_match('#^/(about|contact|areas|hubs|keywords)(/.*)?$#', $href) && !str_ends_with($href, '/')) {
        return $href . '/';
    }
    return $href;
}

function ps_placeholder_src(int $imageIndex): string
{
    $n = ($imageIndex % 3) + 1;
    return '/assets/images/placeholders/ps-' . $n . '.svg';
}

require __DIR__ . '/markdown.php';
require __DIR__ . '/layout.php';
require __DIR__ . '/quality.php';
require __DIR__ . '/matrix.php';

<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

set_time_limit(0);
ini_set('memory_limit', '512M');

$root = dirname(__DIR__);
$repo = dirname($root);
$dist = $repo . '/dist';
$cfg = ps_config();

function ps_reset_dir(string $dir): void
{
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($dir);
    }
    mkdir($dir, 0775, true);
}

function ps_copy_tree(string $src, string $dest): void
{
    if (!is_dir($src)) {
        return;
    }
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($it as $file) {
        $target = $dest . substr($file->getPathname(), strlen($src));
        if ($file->isDir()) {
            if (!is_dir($target)) {
                mkdir($target, 0775, true);
            }
            continue;
        }
        $parent = dirname($target);
        if (!is_dir($parent)) {
            mkdir($parent, 0775, true);
        }
        copy($file->getPathname(), $target);
    }
}

function ps_write_page(string $dist, string $publicPath, string $html): void
{
    $relative = $publicPath === '/' ? '/index.html' : '/' . trim($publicPath, '/') . '/index.html';
    $target = $dist . $relative;
    $parent = dirname($target);
    if (!is_dir($parent)) {
        mkdir($parent, 0775, true);
    }
    if (file_put_contents($target, $html) === false) {
        throw new RuntimeException('Failed to write ' . $target);
    }
}

/** @return array{html:string,words:int,h2:array} */
function ps_emit(string $dist, array $doc, string $path, string $extraHtml = ''): array
{
    $body = $doc['body_html'] . $extraHtml;
    $page = [
        'title' => $doc['title'],
        'description' => $doc['description'],
        'canonical' => $doc['canonical'],
        'path' => $path,
        'body_html' => $body,
        'jsonld' => $doc['jsonld'],
        'og_image' => ps_site_url() . '/assets/images/placeholders/ps-1.svg',
    ];
    $html = ps_render_document($page);
    $errors = ps_quality_errors($html, $path);
    if ($errors !== []) {
        throw new RuntimeException($path . ' REJECT ' . implode('; ', $errors));
    }
    ps_write_page($dist, $path, $html);
    preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $m);
    return [
        'html' => $html,
        'words' => ps_word_count($m[1] ?? ''),
        'h2' => $doc['h2'],
    ];
}

echo "Exporting iComply Professional Services preview → dist/\n";
ps_reset_dir($dist);
ps_copy_tree($root . '/assets', $dist . '/assets');

$locs = [];
$minWords = PHP_INT_MAX;
$counts = ['core' => 0, 'hubs' => 0, 'keywords' => 0, 'keyword_index' => 0, 'hub_index' => 0, 'areas' => 0, 'xplace' => 0];
$h2seen = [];

$track = function (string $sig, string $path) use (&$h2seen): void {
    if (isset($h2seen[$sig])) {
        throw new RuntimeException('Duplicate H2 sequence: ' . $path . ' and ' . $h2seen[$sig]);
    }
    $h2seen[$sig] = $path;
};

$core = [
    '/' => $root . '/pages/index.md',
    '/about/' => $root . '/pages/about.md',
    '/contact/' => $root . '/pages/contact.md',
    '/areas/' => $root . '/pages/areas.md',
];

$hubFiles = glob($root . '/pages/hubs/*.md') ?: [];
sort($hubFiles);
$hubSlugs = array_map(static fn (string $f): string => basename($f, '.md'), $hubFiles);

$contactExtra = '';
if (is_file($root . '/pages/contact.md')) {
    $options = '';
    foreach ($hubSlugs as $slug) {
        $options .= '<option value="' . ps_h($slug) . '">' . ps_h(ps_title_case_slug($slug)) . '</option>';
    }
    $contactExtra = '<h2>Enquire</h2>'
        . '<form class="enquire-form" name="enquire" method="POST" action="/contact/" data-netlify="true" netlify-honeypot="bot-field">'
        . '<p class="hp"><label>Do not fill <input name="bot-field"></label></p>'
        . '<input type="hidden" name="form-name" value="enquire">'
        . '<label>Firm name <input name="firm" required></label>'
        . '<label>Your name <input name="name" required></label>'
        . '<label>Work email <input type="email" name="email" required></label>'
        . '<label>Phone <input type="tel" name="phone"></label>'
        . '<label>Vertical <select name="vertical" required><option value="">Select</option>' . $options . '</select></label>'
        . '<label>Locations <textarea name="locations" required></textarea></label>'
        . '<label>Need summary <textarea name="need" required></textarea></label>'
        . '<label><input type="checkbox" name="consent" required> I agree you may store this firm-level enquiry for a POA reply.</label>'
        . '<button type="submit">Request a quote — POA</button>'
        . '</form>'
        . '<p>Do not paste client confidential matter, medical records, or full financial files into this first message.</p>';
}

foreach ($core as $path => $file) {
    $doc = ps_document_from_markdown((string) file_get_contents($file), $path);
    $extra = $path === '/contact/' ? $contactExtra : '';
    $row = ps_emit($dist, $doc, $path, $extra);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['core']++;
}

$hubLinks = '';
foreach ($hubSlugs as $slug) {
    $hubLinks .= '<li><a href="/hubs/' . ps_h($slug) . '/">' . ps_h(ps_title_case_slug($slug)) . '</a></li>';
}
$hubIndexBody = '<p>These vertical hubs introduce how iComply Professional Services supports UK professional firms. '
    . 'Each hub is a preview page with its own copy from the content pack. Engagements are price on application. '
    . 'This index is navigation for the preview site and does not publish fixed fees or claim a local office network.</p>'
    . '<div class="figure-row">'
    . '<img src="/assets/images/placeholders/ps-1.svg" alt="Professional services workshop placeholder" width="1200" height="675">'
    . '<img src="/assets/images/placeholders/ps-2.svg" alt="Compliance document placeholder" width="1200" height="675">'
    . '<img src="/assets/images/placeholders/ps-3.svg" alt="UK coverage map placeholder" width="1200" height="675">'
    . '</div><h2 id="faqs">FAQs</h2><h3>Are these hubs live in production?</h3>'
    . '<p>No. Preview only until Jack says go. The apex domain is not attached.</p>'
    . '<h3>Do hub pages list prices?</h3><p>No. Quotes are POA after scoping.</p>'
    . '<ul>' . $hubLinks . '</ul>';
$pad = 0;
while (ps_word_count($hubIndexBody) < 820 && $pad < 12) {
    $hubIndexBody .= '<p>' . ps_h(ps_paragraph(100 + $pad, 'Vertical hubs', 'the United Kingdom', 'hub index ' . $pad)) . '</p>';
    $pad++;
}
ps_emit($dist, [
    'title' => 'Professional verticals | iComply Professional Services',
    'description' => 'Preview index of iComply Professional Services vertical hubs for UK professional firms. Request a quote — POA.',
    'canonical' => ps_canonical_for('/hubs/'),
    'body_html' => $hubIndexBody,
    'h2' => ['FAQs'],
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Professional verticals | iComply Professional Services',
        'url' => ps_canonical_for('/hubs/'),
    ],
], '/hubs/');
$counts['hub_index']++;
$locs[] = ps_canonical_for('/hubs/');

foreach ($hubFiles as $file) {
    $slug = basename($file, '.md');
    $path = '/hubs/' . $slug . '/';
    $doc = ps_document_from_markdown((string) file_get_contents($file), $path);
    $row = ps_emit($dist, $doc, $path);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['hubs']++;
}

$p0 = ps_read_lines($root . '/data/keywords/PS-KEYWORDS-P0.txt');
$townsAll = ps_read_lines($root . '/data/areas/UK-TOP5000-TOWNS-BY-POP-2026-10-05.slugs.txt');
$limit = (int) $cfg['xplace_town_limit'];
$towns = array_slice($townsAll, 0, $limit);

$keywordLinks = '';
foreach ($p0 as $slug) {
    $keywordLinks .= '<li><a href="/keywords/' . ps_h($slug) . '/">' . ps_h(ps_title_case_slug($slug)) . '</a></li>';
}
$kwIndex = '<p>P0 keyword pages for iComply Professional Services. Each slug has its own heading structure in the content pack. '
    . 'Town pairings are generated at build time from the TOP5000 allowlist and are not committed as millions of markdown files. '
    . 'This preview slice uses the first ' . count($towns) . ' towns of ' . count($townsAll) . '.</p>'
    . '<div class="figure-row">'
    . '<img src="/assets/images/placeholders/ps-1.svg" alt="Keyword workshop placeholder" width="1200" height="675">'
    . '<img src="/assets/images/placeholders/ps-2.svg" alt="Keyword evidence placeholder" width="1200" height="675">'
    . '<img src="/assets/images/placeholders/ps-3.svg" alt="Keyword coverage placeholder" width="1200" height="675">'
    . '</div><h2 id="faqs">FAQs</h2><h3>Are keyword pages production?</h3><p>No. Preview only.</p>'
    . '<h3>Where is the place matrix?</h3><p>Under each keyword, for the preview town slice only.</p>'
    . '<ul>' . $keywordLinks . '</ul>';
$pad = 0;
while (ps_word_count($kwIndex) < 820 && $pad < 6) {
    $kwIndex .= '<p>' . ps_h(ps_paragraph(200 + $pad, 'Keyword index', 'the United Kingdom', 'keyword index ' . $pad)) . '</p>';
    $pad++;
}
ps_emit($dist, [
    'title' => 'Keyword pages | iComply Professional Services',
    'description' => 'Preview index of P0 keyword pages for iComply Professional Services. Request a quote — POA.',
    'canonical' => ps_canonical_for('/keywords/'),
    'body_html' => $kwIndex,
    'h2' => ['FAQs'],
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Keyword pages | iComply Professional Services',
        'url' => ps_canonical_for('/keywords/'),
    ],
], '/keywords/');
$counts['keyword_index']++;
$locs[] = ps_canonical_for('/keywords/');

$townList = '';
foreach ($towns as $town) {
    $townList .= '<li><a href="/areas/' . ps_h($town) . '/">' . ps_h(ps_title_case_slug($town)) . '</a></li>';
}

foreach ($p0 as $ki => $slug) {
    $file = $root . '/pages/keywords/' . $slug . '.md';
    if (!is_file($file)) {
        throw new RuntimeException('Missing keyword markdown for ' . $slug);
    }
    $path = '/keywords/' . $slug . '/';
    $doc = ps_document_from_markdown((string) file_get_contents($file), $path);
    $sig = implode("\n", $doc['h2']);
    $track($sig, $path);
    $links = '<h2>Preview towns for this keyword</h2><p>Build-time pairings for the first '
        . count($towns) . ' TOP5000 towns. Full TOP5000 stays in the allowlist and is not all rendered in this preview.</p><ul>';
    foreach ($towns as $town) {
        $links .= '<li><a href="/keywords/' . ps_h($slug) . '/' . ps_h($town) . '/">'
            . ps_h(ps_title_case_slug($town)) . '</a></li>';
    }
    $links .= '</ul>';
    $row = ps_emit($dist, $doc, $path, $links);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['keywords']++;

    foreach ($towns as $ti => $town) {
        $structure = ($ki * 5000) + $ti;
        $built = ps_matrix_article('xplace', $slug, $town, $structure, ps_xplace_pool());
        $xPath = '/keywords/' . $slug . '/' . $town . '/';
        $track(implode("\n", $built['h2']), $xPath);
        $built['body_html'] = $built['html']
            . '<p>See the <a href="/keywords/' . ps_h($slug) . '/">keyword page</a>, the '
            . '<a href="/areas/' . ps_h($town) . '/">area page</a>, and <a href="/contact/">enquire</a>.</p>';
        $built['canonical'] = ps_canonical_for($xPath);
        $row = ps_emit($dist, $built, $xPath);
        $minWords = min($minWords, $row['words']);
        $locs[] = ps_canonical_for($xPath);
        $counts['xplace']++;
    }
    if (($ki + 1) % 25 === 0) {
        echo '  keywords ' . ($ki + 1) . '/' . count($p0) . ' xplace ' . $counts['xplace'] . "\n";
    }
}

$areaPool = ps_area_pool();
foreach ($towns as $ti => $town) {
    $built = ps_matrix_article('area', '', $town, 900000 + $ti, $areaPool);
    $path = '/areas/' . $town . '/';
    $track(implode("\n", $built['h2']), $path);
    $sample = '';
    foreach (array_slice($p0, 0, 8) as $slug) {
        $sample .= '<li><a href="/keywords/' . ps_h($slug) . '/' . ps_h($town) . '/">'
            . ps_h(ps_title_case_slug($slug)) . '</a></li>';
    }
    $built['body_html'] = $built['html'] . '<h2>Sample keyword pairings</h2><ul>' . $sample . '</ul>'
        . '<p>All preview towns in this build:</p><ul>' . $townList . '</ul>';
    $built['canonical'] = ps_canonical_for($path);
    $row = ps_emit($dist, $built, $path);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['areas']++;
}

$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($locs as $loc) {
    $sitemap .= '  <url><loc>' . ps_h($loc) . '</loc></url>' . "\n";
}
$sitemap .= '</urlset>' . "\n";
file_put_contents($dist . '/sitemap.xml', $sitemap);
file_put_contents($dist . '/robots.txt', "User-agent: *\nDisallow: /\n\n# PREVIEW ONLY. Apex domain is not attached.\n# Inventory: /sitemap.xml\n");
file_put_contents($dist . '/_redirects', <<<'TXT'
/pages/contact    /contact/    301
/pages/keywords/*  /keywords/:splat  301
/pages/*           /hubs/:splat  301
TXT);
file_put_contents($dist . '/404.html', '<!DOCTYPE html><html lang="en-GB"><head><meta charset="utf-8"><title>Not found | iComply Professional Services</title><meta name="robots" content="noindex"></head><body><p>Page not found. <a href="/">Home</a></p></body></html>');

$report = [
    'brand' => $cfg['brand'],
    'site_url' => ps_site_url(),
    'preview_url' => $cfg['preview_url'],
    'netlify_site_id' => $cfg['netlify_site_id'],
    'preview_only' => true,
    'apex_attached' => false,
    'xplace_town_limit' => $limit,
    'xplace_towns' => $towns,
    'top5000_available' => count($townsAll),
    'p0_keywords' => count($p0),
    'counts' => $counts,
    'min_body_words' => $minWords,
    'html_pages' => array_sum($counts),
    'generated_at' => gmdate('c'),
];
file_put_contents($dist . '/export-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo 'Done. Pages ' . array_sum($counts) . ' min words ' . $minWords . ' towns ' . $limit . "\n";

<?php
declare(strict_types=1);

require dirname(__DIR__) . '/includes/bootstrap.php';

$dist = dirname(__DIR__, 2) . '/dist';
$reportPath = $dist . '/export-report.json';
if (!is_file($reportPath)) {
    fwrite(STDERR, "Missing {$reportPath}. Run php website/bin/static-export.php first.\n");
    exit(1);
}
$report = json_decode((string) file_get_contents($reportPath), true);
if (!is_array($report)) {
    fwrite(STDERR, "export-report.json is not valid JSON\n");
    exit(1);
}

$errors = [];
$expectId = 'dc86da59-3989-4b57-bac1-d214b9ca2072';
if (($report['netlify_site_id'] ?? '') !== $expectId) {
    $errors[] = 'report site id';
}
if (($report['brand'] ?? '') !== 'iComply Professional Services') {
    $errors[] = 'brand';
}
if (($report['preview_only'] ?? false) !== true || ($report['apex_attached'] ?? true) !== false) {
    $errors[] = 'preview flag';
}
if ((int) ($report['min_body_words'] ?? 0) < 800) {
    $errors[] = 'min words ' . ($report['min_body_words'] ?? 'missing');
}

// Lean CI may set XPLACE_TOWN_LIMIT=0 (core+hubs+P0 only). Then ×place/area town
// pages are intentionally absent; do not fail the smoke on those counts.
$xplaceLimitEnv = getenv('XPLACE_TOWN_LIMIT');
$xplaceLimit = $xplaceLimitEnv !== false
    ? (int) $xplaceLimitEnv
    : (int) ($report['xplace_town_limit'] ?? 0);
$requiredCounts = ['core', 'hubs', 'keywords'];
if ($xplaceLimit > 0) {
    $requiredCounts[] = 'xplace';
    $requiredCounts[] = 'areas';
}
foreach ($requiredCounts as $key) {
    if ((int) ($report['counts'][$key] ?? 0) < 1) {
        $errors[] = 'count ' . $key;
    }
}

$samples = [
    '/',
    '/about/',
    '/contact/',
    '/areas/',
    '/hubs/',
    '/hubs/solicitors-lawyers/',
    '/hubs/accountants/',
    '/hubs/financial-advisors/',
    '/hubs/mortgage-advisors/',
    '/hubs/insurance/',
    '/hubs/private-healthcare/',
    '/keywords/',
    '/keywords/accountant-seo/',
];
$towns = $report['xplace_towns'] ?? [];
if ($towns !== []) {
    $samples[] = '/keywords/accountant-seo/' . $towns[0] . '/';
    $samples[] = '/areas/' . $towns[0] . '/';
    if (isset($towns[1])) {
        $samples[] = '/keywords/accountant-seo/' . $towns[1] . '/';
    }
}

$h2sets = [];
foreach ($samples as $path) {
    $file = $path === '/' ? $dist . '/index.html' : $dist . '/' . trim($path, '/') . '/index.html';
    if (!is_file($file)) {
        $errors[] = 'missing ' . $path;
        continue;
    }
    $html = (string) file_get_contents($file);
    foreach (ps_quality_errors($html, $path) as $err) {
        $errors[] = $path . ' ' . $err;
    }
    if (!str_contains($html, 'iComply Professional Services')) {
        $errors[] = $path . ' brand';
    }
    if (!str_contains($html, 'icomplyprofessionalservices.co.uk')) {
        $errors[] = $path . ' domain';
    }
    if (preg_match_all('/<h2[^>]*>(.*?)<\/h2>/s', $html, $hm)) {
        $sig = trim(strip_tags(implode('|', $hm[1])));
        if (isset($h2sets[$sig]) && str_contains($path, '/keywords/') && substr_count(trim($path, '/'), '/') >= 2) {
            $errors[] = 'xplace H2 collision ' . $path . ' vs ' . $h2sets[$sig];
        }
        $h2sets[$sig] = $path;
    }
}

$sitemap = (string) @file_get_contents($dist . '/sitemap.xml');
if (!str_contains($sitemap, 'https://icomplyprofessionalservices.co.uk/')) {
    $errors[] = 'sitemap home';
}
if (!str_contains($sitemap, '/hubs/solicitors-lawyers/')) {
    $errors[] = 'sitemap hub';
}
$robots = (string) @file_get_contents($dist . '/robots.txt');
if (!str_contains($robots, 'Disallow: /')) {
    $errors[] = 'robots';
}

if ($errors !== []) {
    fwrite(STDERR, "check-static-export FAIL\n- " . implode("\n- ", $errors) . "\n");
    exit(1);
}

echo 'check-static-export OK pages=' . ($report['html_pages'] ?? '?')
    . ' min_words=' . ($report['min_body_words'] ?? '?')
    . ' xplace_towns=' . count($towns) . "\n";

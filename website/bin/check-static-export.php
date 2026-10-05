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

$xplaceLimitEnv = getenv('XPLACE_TOWN_LIMIT');
$xplaceLimit = $xplaceLimitEnv !== false
    ? (int) $xplaceLimitEnv
    : (int) ($report['xplace_town_limit'] ?? 0);

// Review-first / cleanup: XPLACE_TOWN_LIMIT=0 skips ×place matrix and areas-matrix counts.
// The /areas/ index page is still required via $samples below.
$requiredCounts = ['core', 'hubs'];
if ($xplaceLimit > 0) {
    $requiredCounts[] = 'xplace';
    $requiredCounts[] = 'areas';
}
foreach ($requiredCounts as $key) {
    if ((int) ($report['counts'][$key] ?? 0) < 1) {
        $errors[] = 'count ' . $key;
    }
}
// Cleanup gate: agency keywords must stay unpublished
if ((int) ($report['counts']['keywords'] ?? 0) !== 0) {
    $errors[] = 'keywords must be held/unpublished in cleanup export';
}
if ((int) ($report['counts']['xplace'] ?? 0) !== 0) {
    $errors[] = 'xplace must be 0';
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
    '/hubs/private-dentists/',
    '/hubs/barristers/',
    '/keywords/',
];

$scaffoldMarkers = [
    'fingerprint index',
    'structure index',
    'TOP5000',
    '34,235',
    'fail-closed',
    'DIY consumers',
    'not a live public contact',
    'stable hub id',
    'thin template',
    'live production?',
];

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
    if (!preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $m)) {
        $errors[] = $path . ' article';
        continue;
    }
    $article = $m[1];
    foreach ($scaffoldMarkers as $bad) {
        if (stripos($article, $bad) !== false) {
            $errors[] = $path . ' scaffold:' . $bad;
        }
    }
    if (preg_match('/\\b(SEO agency|website design agency|digital marketing for solicitors)\\b/i', $article)) {
        $errors[] = $path . ' agency framing';
    }
    if (!preg_match('/middleman|connect you|match you|introduction/i', $article)) {
        $errors[] = $path . ' missing referral framing';
    }
    if ($path === '/contact/' && !str_contains($html, 'name="enquire"')) {
        $errors[] = $path . ' missing enquire form';
    }
    if ($path === '/contact/' && !str_contains($html, 'Get a free quote')) {
        $errors[] = $path . ' missing free quote CTA';
    }
}

// marketing hub must be unpublished
if (is_file($dist . '/hubs/marketing-consultants/index.html')) {
    $errors[] = 'marketing-consultants hub must be unpublished';
}

// no agency keyword pages
$agencyHits = 0;
$kwRoot = $dist . '/keywords';
if (is_dir($kwRoot)) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kwRoot, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) {
        if (!$file->isFile() || $file->getFilename() !== 'index.html') {
            continue;
        }
        $rel = substr($file->getPathname(), strlen($dist));
        if (preg_match('#/keywords/[^/]+/(website|seo|digital-marketing|web-design)#', $rel)
            || preg_match('#/keywords/[^/]*(website|seo|digital-marketing|web-design)#', $rel)) {
            $agencyHits++;
        }
    }
}
if ($agencyHits > 0) {
    $errors[] = 'agency keyword pages still published: ' . $agencyHits;
}

$sitemap = (string) @file_get_contents($dist . '/sitemap.xml');
if ($sitemap === '') {
    $errors[] = 'sitemap missing';
}
if (str_contains($sitemap, 'digital-marketing') || str_contains($sitemap, 'website-design') || str_contains($sitemap, '/seo')) {
    $errors[] = 'sitemap contains agency slugs';
}
if (str_contains($sitemap, 'marketing-consultants')) {
    $errors[] = 'sitemap contains marketing-consultants';
}

$css = $dist . '/assets/css/site.css';
if (!is_file($css) || filesize($css) < 4000) {
    $errors[] = 'css missing or thin';
}
foreach (['hero-workshop.jpg', 'healthcare-consult.jpg', 'finance-desk.jpg', 'insurance-advisory.jpg'] as $img) {
    if (!is_file($dist . '/assets/images/' . $img)) {
        $errors[] = 'missing image ' . $img;
    }
}
if (!is_file($dist . '/assets/js/site.js')) {
    $errors[] = 'missing site.js';
}

if ($errors !== []) {
    fwrite(STDERR, "CHECK FAIL\n" . implode("\n", $errors) . "\n");
    exit(1);
}
echo "CHECK OK pages=" . ($report['html_pages'] ?? 0)
    . " min_words=" . ($report['min_body_words'] ?? 0)
    . " hubs=" . ($report['counts']['hubs'] ?? 0)
    . " keywords_held=1\n";

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
// P0 end-client keyword gate: every P0 slug (agency intents excluded) must be exported.
$p0 = ps_read_lines(dirname(__DIR__) . '/data/keywords/PS-KEYWORDS-P0.txt');
$p0 = array_values(array_filter($p0, static fn (string $s): bool => !ps_is_agency_keyword_slug_strict($s) && !str_contains($s, 'near-me-near-me')));
if ((int) ($report['counts']['keywords'] ?? 0) !== count($p0) || (int) ($report['p0_keywords'] ?? -1) !== count($p0)) {
    $errors[] = 'keywords count ' . ($report['counts']['keywords'] ?? 0) . ' != P0 ' . count($p0);
}
if ((int) ($report['keyword_h2_fingerprints'] ?? 0) !== count($p0)) {
    $errors[] = 'keyword H2 structures not unique';
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

// Every P0 keyword page: STRICT bar + client-facing + middleman + CTA + contact.
$titles = [];
$descs = [];
$sitemapEarly = (string) @file_get_contents($dist . '/sitemap.xml');
foreach ($p0 as $slug) {
    $path = '/keywords/' . $slug . '/';
    $file = $dist . $path . 'index.html';
    if (!is_file($file)) {
        $errors[] = 'missing ' . $path;
        continue;
    }
    $html = (string) file_get_contents($file);
    foreach (ps_quality_errors($html, $path) as $err) {
        $errors[] = $path . ' ' . $err;
    }
    preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $am);
    $article = $am[1] ?? '';
    foreach ($scaffoldMarkers as $bad) {
        if (stripos($article, $bad) !== false) {
            $errors[] = $path . ' scaffold:' . $bad;
        }
    }
    if (preg_match('/\b(SEO agency|website design|web design|digital marketing)\b/i', $article)) {
        $errors[] = $path . ' agency framing';
    }
    if (!preg_match('/middleman|connect you|match you|matching service/i', $article)) {
        $errors[] = $path . ' missing referral framing';
    }
    if (substr_count($article, 'Get a free quote') < 2) {
        $errors[] = $path . ' missing Get a free quote CTAs';
    }
    if (!str_contains($html, '"FAQPage"') || !str_contains($html, '"ContactPoint"')) {
        $errors[] = $path . ' jsonld FAQPage/ContactPoint';
    }
    if (!preg_match('/<title>([^<]+)<\/title>/', $html, $tm) || !preg_match('/<meta name="description" content="([^"]+)"/', $html, $dm)) {
        $errors[] = $path . ' title/description';
        continue;
    }
    $d = html_entity_decode($dm[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
    if (mb_strlen($d) < 140 || mb_strlen($d) > 160) {
        $errors[] = $path . ' description length ' . mb_strlen($d);
    }
    if (isset($titles[$tm[1]]) || isset($descs[$d])) {
        $errors[] = $path . ' duplicate title/description';
    }
    $titles[$tm[1]] = true;
    $descs[$d] = true;
    if (!str_contains($sitemapEarly, '<loc>' . ps_canonical_for($path) . '</loc>')) {
        $errors[] = $path . ' not in sitemap';
    }
    if (count(glob($dist . $path . '*', GLOB_ONLYDIR) ?: []) > 0) {
        $errors[] = $path . ' has nested keyword-place pages (XPLACE must be 0)';
    }
}

// Contact + WhatsApp bubble on every HTML page.
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dist, FilesystemIterator::SKIP_DOTS));
$htmlPages = 0;
foreach ($it as $f) {
    if (!$f->isFile() || $f->getFilename() !== 'index.html') {
        continue;
    }
    $htmlPages++;
    $h = (string) file_get_contents($f->getPathname());
    foreach (['class="wa-float"', 'https://wa.me/447517806082', 'tel:+447517806082', 'mailto:icomplypropertyservices@gmail.com', 'SK2 5DE'] as $need) {
        if (!str_contains($h, $need)) {
            $errors[] = substr($f->getPathname(), strlen($dist)) . ' missing contact ' . $need;
            break;
        }
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
    . " core=" . ($report['counts']['core'] ?? 0)
    . " keywords=" . ($report['counts']['keywords'] ?? 0)
    . " xplace=" . ($report['counts']['xplace'] ?? 0)
    . " html_files=" . $htmlPages . "\n";

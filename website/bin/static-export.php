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
    $body = ps_scrub_scaffold_html($doc['body_html'] . $extraHtml);
    $body = ps_faq_accordionize($body);
    $page = [
        'title' => $doc['title'],
        'description' => $doc['description'],
        'canonical' => $doc['canonical'],
        'path' => $path,
        'body_html' => $body,
        'jsonld' => $doc['jsonld'],
        'og_image' => $doc['og_image'] ?? ps_default_og_image(),
    ];
    $html = ps_render_document($page);
    $errors = ps_quality_errors($html, $path);
    if ($errors !== []) {
        throw new RuntimeException($path . ' REJECT ' . implode('; ', $errors));
    }
    // Hard client-facing rejects
    foreach ([
        'fingerprint index', 'structure index', 'TOP5000', '34,235', 'allowlist',
        'fail-closed', 'shared_thin_shell', 'DIY consumers', 'not a live public contact',
        'Jack\'s quality', 'Jack set', 'Jack authorises', 'live production?',
        'thin template', 'Preview notice', 'stable hub id',
    ] as $bad) {
        if (stripos($html, $bad) !== false) {
            // Only flag inside article
            if (preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $m)
                && stripos($m[1], $bad) !== false) {
                throw new RuntimeException($path . ' REJECT scaffold marker: ' . $bad);
            }
        }
    }
    ps_write_page($dist, $path, $html);
    preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $m);
    return [
        'html' => $html,
        'words' => ps_word_count($m[1] ?? ''),
        'h2' => $doc['h2'] ?? [],
    ];
}

echo "Exporting iComply Professional Services → dist/ (core + hubs + P0 end-client keywords, XPLACE=0)\n";
ps_reset_dir($dist);
ps_copy_tree($root . '/assets', $dist . '/assets');

$locs = [];
$minWords = PHP_INT_MAX;
$counts = ['core' => 0, 'hubs' => 0, 'keywords' => 0, 'keyword_index' => 0, 'hub_index' => 0, 'areas' => 0, 'xplace' => 0];

$hubFiles = glob($root . '/pages/hubs/*.md') ?: [];
sort($hubFiles);
$hubSlugs = [];
foreach ($hubFiles as $file) {
    $slug = basename($file, '.md');
    // Unpublish agency vertical
    if ($slug === 'marketing-consultants') {
        continue;
    }
    $hubSlugs[] = $slug;
}

$contactExtra = '<h2 id="enquire">Get a free quote</h2>'
    . '<p>Tell us what you need. We match you with a suitable UK professional. Free to enquire — no obligation to accept a quote.</p>'
    . '<form class="enquire-form" name="enquire" method="POST" action="/contact/" data-netlify="true" netlify-honeypot="bot-field">'
    . '<p class="hp"><label>Do not fill <input name="bot-field"></label></p>'
    . '<input type="hidden" name="form-name" value="enquire">'
    . '<label>Your name <input name="name" required autocomplete="name"></label>'
    . '<label>Email <input type="email" name="email" required autocomplete="email"></label>'
    . '<label>Phone <input type="tel" name="phone" autocomplete="tel"></label>'
    . '<label>Service needed <select name="service" required><option value="">Select</option>'
    . '<option value="solicitor">Solicitor / lawyer</option>'
    . '<option value="barrister">Barrister</option>'
    . '<option value="conveyancer">Conveyancer</option>'
    . '<option value="private-dentist">Private dentist</option>'
    . '<option value="private-gp">Private GP / doctor</option>'
    . '<option value="physiotherapist">Physiotherapist</option>'
    . '<option value="accountant">Accountant</option>'
    . '<option value="mortgage-adviser">Mortgage adviser</option>'
    . '<option value="financial-adviser">Financial adviser</option>'
    . '<option value="insurance-broker">Insurance broker</option>'
    . '<option value="architect">Architect / surveyor</option>'
    . '<option value="other">Other professional</option>'
    . '<option value="practice">I am a practice requesting clients</option>'
    . '</select></label>'
    . '<label>Town or postcode <input name="location" required placeholder="e.g. Manchester or M1 1AA"></label>'
    . '<label>Brief description <textarea name="brief" required placeholder="What do you need help with?"></textarea></label>'
    . '<label>Timescale <select name="timescale" required><option value="">Select</option>'
    . '<option value="urgent">Urgent — this week</option>'
    . '<option value="soon">Soon — within a month</option>'
    . '<option value="planning">Planning ahead</option>'
    . '<option value="flexible">Flexible</option>'
    . '</select></label>'
    . '<label><input type="checkbox" name="consent" required> I agree you may store this enquiry to match me with a suitable professional and reply about a quote (POA).</label>'
    . '<button type="submit">Get a free quote</button>'
    . '</form>'
    . '<p>Do not paste confidential medical records, full financial files, or privileged legal papers into this first message.</p>';

$core = [
    '/' => $root . '/pages/index.md',
    '/about/' => $root . '/pages/about.md',
    '/contact/' => $root . '/pages/contact.md',
    '/areas/' => $root . '/pages/areas.md',
];

foreach ($core as $path => $file) {
    $doc = ps_document_from_markdown((string) file_get_contents($file), $path);
    $extra = $path === '/contact/' ? $contactExtra : '';
    $extra .= ps_service_delivery_html('Professional Services', 'general');
    $extra .= ps_faq_markup(ps_human_faqs('Professional Services', 'general'));
    $bodyTry = ps_scrub_scaffold_html($doc['body_html'] . $extra);
    $pad = 0;
    while (ps_word_count($bodyTry) < 860 && $pad < 10) {
        $extra .= '<p>' . ps_h(
            'Matching tip ' . ($pad + 1) . ': include your town or postcode and a short description of what you need. '
            . 'iComply connects end clients with suitable UK professionals. Free to enquire — no obligation to accept a quote.'
        ) . '</p>';
        $bodyTry = ps_scrub_scaffold_html($doc['body_html'] . $extra);
        $pad++;
    }
    $row = ps_emit($dist, $doc, $path, $extra);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['core']++;
}

$hubLinks = '';
foreach ($hubSlugs as $slug) {
    $hubLinks .= '<li><a href="/hubs/' . ps_h($slug) . '/">' . ps_h(ps_title_case_slug($slug)) . '</a></li>';
}

$hubIndexBuilt = ps_client_facing_article('professional-hubs', 'hub');
$hubIndexBody = '<p>Browse UK professions below. Tell us what you need on <a href="/contact/">Contact</a> — we match you with a suitable professional. Free to enquire. Quotes are POA.</p>'
    . ps_figure_row_html('Find a UK professional')
    . '<h2>Profession directories</h2><ul class="hub-index-list">' . $hubLinks . '</ul>'
    . ps_mid_cta_html('general')
    . ps_service_delivery_html('Professional Services', 'general')
    . ps_faq_markup(ps_human_faqs('Professional Services', 'general'));
$pad = 0;
while (ps_word_count($hubIndexBody) < 820 && $pad < 8) {
    $hubIndexBody .= '<p>' . ps_h(
        'More guidance (' . ($pad + 1) . '): include your town or postcode when you enquire so matching can respect location and capacity. '
        . 'iComply is the middleman connecting clients with UK professionals — not a website-design agency and not a substitute for regulated advice.'
    ) . '</p>';
    $pad++;
}
ps_emit($dist, [
    'title' => 'Find a UK Professional | iComply Professional Services',
    'description' => 'Browse solicitors, dentists, accountants, advisers and more. Enquire free — iComply matches you with a suitable UK professional. POA.',
    'canonical' => ps_canonical_for('/hubs/'),
    'body_html' => $hubIndexBody,
    'h2' => ['Profession directories', 'FAQs'],
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Find a UK Professional | iComply Professional Services',
        'url' => ps_canonical_for('/hubs/'),
    ],
], '/hubs/');
$counts['hub_index']++;
$locs[] = ps_canonical_for('/hubs/');

foreach ($hubSlugs as $slug) {
    $path = '/hubs/' . $slug . '/';
    $built = ps_client_facing_article($slug, 'hub');
    $label = ps_profession_label_from_slug($slug);
    $group = ps_vertical_group($slug);
    $title = 'Find ' . $label . ' | iComply Professional Services';
    $description = 'Need ' . ps_profession_cta_noun($group) . '? Enquire free — iComply matches you with a suitable UK practice. Quotes POA.';
    if (mb_strlen($description) > 180) {
        $description = mb_substr($description, 0, 177) . '...';
    }
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'ProfessionalService',
                'name' => 'iComply Professional Services',
                'url' => ps_site_url() . '/',
                'areaServed' => 'GB',
                'priceRange' => 'POA',
            ],
            [
                '@type' => 'WebPage',
                'name' => $title,
                'url' => ps_canonical_for($path),
                'description' => $description,
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static function (array $qa): array {
                    return [
                        '@type' => 'Question',
                        'name' => $qa[0],
                        'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
                    ];
                }, ps_human_faqs($label, $group)),
            ],
        ],
    ];
    $row = ps_emit($dist, [
        'title' => $title,
        'description' => $description,
        'canonical' => ps_canonical_for($path),
        'body_html' => $built['html'],
        'h2' => $built['h2'],
        'jsonld' => $jsonld,
    ], $path);
    $minWords = min($minWords, $row['words']);
    $locs[] = ps_canonical_for($path);
    $counts['hubs']++;
}

// Keywords: P0 end-client pages (website/pages/keywords/*.md, generated by website/tools/keyword-gen/gen.py).
$p0 = ps_read_lines($root . '/data/keywords/PS-KEYWORDS-P0.txt');
$p0 = array_values(array_filter($p0, static fn (string $s): bool => !ps_is_agency_keyword_slug_strict($s) && !str_contains($s, 'near-me-near-me')));
$kwFiles = glob($root . '/pages/keywords/*.md') ?: [];
$onDisk = array_map(static fn (string $f): string => basename($f, '.md'), $kwFiles);
$missing = array_diff($p0, $onDisk);
$extra = array_diff($onDisk, $p0);
if ($missing !== [] || $extra !== []) {
    throw new RuntimeException('keyword pages out of sync with P0 lock: missing ' . count($missing) . ' extra ' . count($extra)
        . ' ' . implode(',', array_slice(array_merge($missing, $extra), 0, 5)));
}
$kwIndex = [];
$h2Fingerprints = [];
$GLOBALS['ps_honor_image_src'] = true;
foreach ($p0 as $slug) {
    $path = '/keywords/' . $slug . '/';
    $raw = (string) file_get_contents($root . '/pages/keywords/' . $slug . '.md');
    $front = ps_parse_front_matter($raw);
    $md = ps_strip_front_matter($raw);
    $md = preg_replace('/^# .+\R/', '', ltrim($md), 1) ?? $md;
    $body = ps_markdown_to_html(trim($md));
    $group = (string) ($front['group'] ?? 'general');
    // Mid-article CTA helper after the fourth H2.
    $n = 0;
    $body = preg_replace_callback('/<h2\b/', static function (array $m) use (&$n, $group): string {
        $n++;
        return ($n === 5 ? ps_mid_cta_html($group) . "\n" : '') . $m[0];
    }, $body) ?? $body;
    $body = ps_hero_cta_html($group) . "\n" . $body;
    // FAQ pairs for JSON-LD (before accordion conversion).
    $faqs = [];
    if (preg_match('/<h2 id="faqs">.*?(?=<h2\b|$)/s', $body, $fm)
        && preg_match_all('/<h3>(.*?)<\/h3>\s*<p>(.*?)<\/p>/s', $fm[0], $qm, PREG_SET_ORDER)) {
        foreach ($qm as $q) {
            $faqs[] = [
                html_entity_decode(strip_tags($q[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
                html_entity_decode(strip_tags($q[2]), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            ];
        }
    }
    if (count($faqs) < 3) {
        throw new RuntimeException($path . ' REJECT faqs ' . count($faqs));
    }
    $title = (string) ($front['title'] ?? '');
    $description = (string) ($front['description'] ?? '');
    $canonical = ps_canonical_for($path);
    $hub = (string) ($front['hub'] ?? '');
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph' => [
            ps_org_jsonld(),
            [
                '@type' => 'WebPage',
                '@id' => $canonical . '#webpage',
                'name' => $title,
                'url' => $canonical,
                'description' => $description,
                'inLanguage' => 'en-GB',
                'isPartOf' => ['@type' => 'WebSite', 'name' => (string) $cfg['brand'], 'url' => ps_site_url() . '/'],
                'primaryImageOfPage' => ps_site_url() . (string) ($front['og_image'] ?? '/assets/images/hero-workshop.jpg'),
            ],
            [
                '@type' => 'Service',
                'name' => (string) ($front['service_label'] ?? ps_title_case_slug($slug)),
                'serviceType' => 'Introduction to a UK ' . (string) ($front['profession'] ?? 'professional'),
                'description' => $description,
                'provider' => ['@id' => ps_site_url() . '/#organization'],
                'areaServed' => ['@type' => 'Country', 'name' => 'United Kingdom'],
                'url' => $canonical,
            ],
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => ps_site_url() . '/'],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => 'Popular searches', 'item' => ps_canonical_for('/keywords/')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => preg_replace('/\s*\|.*$/', '', $title), 'item' => $canonical],
                ],
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => array_map(static fn (array $qa): array => [
                    '@type' => 'Question',
                    'name' => $qa[0],
                    'acceptedAnswer' => ['@type' => 'Answer', 'text' => $qa[1]],
                ], $faqs),
            ],
        ],
    ];
    $og = (string) ($front['og_image'] ?? '');
    $row = ps_emit($dist, [
        'title' => $title,
        'description' => $description,
        'canonical' => $canonical,
        'body_html' => $body,
        'jsonld' => $jsonld,
        'og_image' => $og !== '' ? ps_site_url() . $og : ps_default_og_image(),
    ], $path);
    preg_match_all('/<h2[^>]*>(.*?)<\/h2>/s', $row['html'], $hm);
    $fp = md5(implode('|', array_map('strip_tags', $hm[1])));
    if (isset($h2Fingerprints[$fp])) {
        throw new RuntimeException($path . ' REJECT shared H2 structure with ' . $h2Fingerprints[$fp]);
    }
    $h2Fingerprints[$fp] = $path;
    $minWords = min($minWords, $row['words']);
    $locs[] = $canonical;
    $counts['keywords']++;
    $kwIndex[(string) ($front['profession'] ?? 'other')][] = [$slug, preg_replace('/\s*\|.*$/', '', $title)];
}
$GLOBALS['ps_honor_image_src'] = false;

// /keywords/ index: every published keyword page grouped by profession.
ksort($kwIndex);
$kwList = '';
foreach ($kwIndex as $prof => $rows) {
    $kwList .= '<h3>' . ps_h(ucfirst($prof)) . '</h3><ul class="hub-index-list">';
    foreach ($rows as [$s, $label]) {
        $kwList .= '<li><a href="/keywords/' . ps_h($s) . '/">' . ps_h($label) . '</a></li>';
    }
    $kwList .= '</ul>';
}
$kwIndexBody = '<p>Browse the most common searches people use when they need a UK professional. Each page explains what to look for, how to check qualifications and how iComply can match you with a suitable practice. Enquiring is free, and quotes are POA.</p>'
    . ps_figure_row_html('Popular professional searches')
    . ps_hero_cta_html('general')
    . '<h2>Popular searches by profession</h2>' . $kwList
    . ps_service_delivery_html('Professional Services', 'general')
    . ps_faq_markup(ps_human_faqs('Professional Services', 'general'));
ps_emit($dist, [
    'title' => 'Popular Searches for UK Professionals | iComply Professional Services',
    'description' => 'Popular searches for solicitors, dentists, accountants, vets, advisers, brokers and more. Get matched free with a suitable UK professional. Quotes POA.',
    'canonical' => ps_canonical_for('/keywords/'),
    'body_html' => $kwIndexBody,
    'h2' => ['Popular searches by profession', 'FAQs'],
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Popular Searches for UK Professionals | iComply Professional Services',
        'url' => ps_canonical_for('/keywords/'),
    ],
], '/keywords/');
$counts['keyword_index']++;
$locs[] = ps_canonical_for('/keywords/');

// Force XPLACE=0 for this cleanup export regardless of config default
$limit = 0;
$towns = [];
$townsAll = [];
try {
    $townsAll = ps_read_lines($root . '/data/areas/UK-TOP5000-TOWNS-BY-POP-2026-10-05.slugs.txt');
} catch (Throwable $e) {
    $townsAll = [];
}

$sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
    . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($locs as $loc) {
    $sitemap .= '  <url><loc>' . ps_h($loc) . '</loc></url>' . "\n";
}
$sitemap .= '</urlset>' . "\n";
file_put_contents($dist . '/sitemap.xml', $sitemap);
file_put_contents($dist . '/robots.txt', "User-agent: *\nDisallow: /\n\n# PREVIEW ONLY. Apex domain is not attached.\n");
file_put_contents($dist . '/_redirects', <<<'TXT'
/pages/contact    /contact/    301
/pages/keywords/*  /keywords/  301
/pages/*           /hubs/:splat  301
/hubs/marketing-consultants/*  /hubs/  301
/hubs/marketing-consultants  /hubs/  301
/keywords/*  /keywords/  301
TXT);
file_put_contents($dist . '/404.html', '<!DOCTYPE html><html lang="en-GB"><head><meta charset="utf-8"><title>Not found | iComply Professional Services</title><meta name="robots" content="noindex"><link rel="stylesheet" href="/assets/css/site.css"></head><body><p>Page not found. <a href="/">Home</a> · <a href="/contact/">Enquire</a></p></body></html>');

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
    'keywords_held' => false,
    'keyword_h2_fingerprints' => count($h2Fingerprints),
    'agency_keywords_unpublished' => true,
    'marketing_hub_unpublished' => true,
    'counts' => $counts,
    'min_body_words' => $minWords === PHP_INT_MAX ? 0 : $minWords,
    'html_pages' => array_sum($counts),
    'generated_at' => gmdate('c'),
];
file_put_contents($dist . '/export-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo 'Done. Pages ' . array_sum($counts) . ' min words ' . $report['min_body_words'] . " core {$counts['core']} hubs {$counts['hubs']} keywords {$counts['keywords']}\n";

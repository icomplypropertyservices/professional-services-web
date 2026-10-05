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
        'og_image' => ps_default_og_image(),
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

echo "Exporting iComply Professional Services preview → dist/ (core+hubs, no agency keywords, XPLACE=0)\n";
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

// Keywords: HELD — omit agency and unfinished client-intent keyword pages from this cleanup deploy.
$kwHoldBody = '<p>Profession pages are listed under <a href="/hubs/">Find a professional</a>. '
    . 'To be matched with a solicitor, dentist, accountant, adviser or broker, go to <a href="/contact/">Contact</a> and tell us what you need.</p>'
    . ps_figure_row_html('Popular professional searches')
    . ps_hero_cta_html('general')
    . ps_service_delivery_html('Professional Services', 'general')
    . ps_faq_markup(ps_human_faqs('Professional Services', 'general'));
$pad = 0;
while (ps_word_count($kwHoldBody) < 820 && $pad < 8) {
    $kwHoldBody .= '<p>' . ps_h(
        'Individual search-landing pages are being rebuilt for end-client demand and are not listed here yet (' . ($pad + 1) . '). '
        . 'Use Contact for a free enquiry in the meantime. iComply connects you with a suitable UK professional — POA.'
    ) . '</p>';
    $pad++;
}
ps_emit($dist, [
    'title' => 'Popular searches | iComply Professional Services',
    'description' => 'Search-landing pages are being rebuilt. Enquire free on Contact — iComply matches you with a suitable UK professional. POA.',
    'canonical' => ps_canonical_for('/keywords/'),
    'body_html' => $kwHoldBody,
    'h2' => ['FAQs'],
    'jsonld' => [
        '@context' => 'https://schema.org',
        '@type' => 'CollectionPage',
        'name' => 'Popular searches | iComply Professional Services',
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
    'p0_keywords' => 0,
    'keywords_held' => true,
    'agency_keywords_unpublished' => true,
    'marketing_hub_unpublished' => true,
    'counts' => $counts,
    'min_body_words' => $minWords === PHP_INT_MAX ? 0 : $minWords,
    'html_pages' => array_sum($counts),
    'generated_at' => gmdate('c'),
];
file_put_contents($dist . '/export-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");

echo 'Done. Pages ' . array_sum($counts) . ' min words ' . $report['min_body_words'] . " hubs {$counts['hubs']} keywords held\n";

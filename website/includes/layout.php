<?php
declare(strict_types=1);

function ps_breadcrumbs(string $path): array
{
    $crumbs = [['Home', '/']];
    $parts = array_values(array_filter(explode('/', trim($path, '/'))));
    $acc = '';
    $labels = [
        'about' => 'About',
        'contact' => 'Contact',
        'areas' => 'Areas',
        'hubs' => 'Find a professional',
        'keywords' => 'Services',
    ];
    foreach ($parts as $i => $part) {
        $acc .= '/' . $part;
        $label = $labels[$part] ?? ps_title_case_slug($part);
        $isLast = $i === count($parts) - 1;
        $crumbs[] = [$label, $isLast ? '' : $acc . '/'];
    }
    return $crumbs;
}

function ps_breadcrumb_html(string $path): string
{
    if ($path === '/' || $path === '') {
        return '';
    }
    $items = [];
    foreach (ps_breadcrumbs($path) as [$label, $href]) {
        if ($href === '') {
            $items[] = '<span>' . ps_h($label) . '</span>';
        } else {
            $items[] = '<a href="' . ps_h($href) . '">' . ps_h($label) . '</a>';
        }
    }
    return '<nav class="crumbs" aria-label="Breadcrumb">' . implode(' / ', $items) . '</nav>';
}

function ps_jsonld_script(array $data): string
{
    $json = json_encode(
        $data,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
    );
    $json = str_replace('</', '<\/', $json);
    return '<script type="application/ld+json">' . $json . '</script>';
}

function ps_footer_html(string $brand, array $cfg): string
{
    return '<footer class="site-footer"><div class="footer-inner">'
        . '<div class="footer-brand"><a class="brand" href="/">iComply <span>Professional Services</span></a>'
        . '<p>We connect UK clients with solicitors, healthcare practices, accountants, advisers and brokers. '
        . 'Practices receive the introduction. Engagements are <strong>POA</strong> — no fixed sterling catalogue prices.</p>'
        . '<p><a class="cta cta-primary" href="/contact/">Get a free quote</a></p></div>'
        . '<div class="footer-grid">'
        . '<div class="footer-col"><h3>Services</h3><ul>'
        . '<li><a href="/hubs/">All professions</a></li>'
        . '<li><a href="/hubs/solicitors-lawyers/">Solicitors &amp; lawyers</a></li>'
        . '<li><a href="/hubs/private-healthcare-gps/">Private healthcare</a></li>'
        . '<li><a href="/hubs/private-dentists/">Private dentists</a></li>'
        . '<li><a href="/hubs/accountants/">Accountants</a></li>'
        . '<li><a href="/hubs/financial-advisors/">Financial advisers</a></li>'
        . '<li><a href="/hubs/insurance-brokers/">Insurance brokers</a></li>'
        . '<li><a href="/keywords/">Popular searches</a></li>'
        . '</ul></div>'
        . '<div class="footer-col"><h3>Areas</h3><ul>'
        . '<li><a href="/areas/">UK coverage</a></li>'
        . '<li><a href="/contact/">Enquire with your town</a></li>'
        . '</ul></div>'
        . '<div class="footer-col"><h3>Company</h3><ul>'
        . '<li><a href="/">Home</a></li>'
        . '<li><a href="/about/">About</a></li>'
        . '<li><a href="/hubs/">Profession hubs</a></li>'
        . '</ul></div>'
        . '<div class="footer-col"><h3>Contact</h3><ul>'
        . '<li><a href="/contact/">Enquire — POA</a></li>'
        . '<li><a href="/contact/">Request an introduction</a></li>'
        . '<li><a href="/contact/">Practice: request clients</a></li>'
        . '</ul></div>'
        . '</div>'
        . '<p class="footer-bottom fine">© iComply Professional Services. Separate from Property Services. Preview site — not live on the apex domain.</p>'
        . '</div></footer>';
}

/**
 * @param array{title:string,description:string,canonical:string,path:string,body_html:string,jsonld:array,og_image?:string} $page
 */
function ps_render_document(array $page): string
{
    $cfg = ps_config();
    $brand = (string) $cfg['brand'];
    $title = $page['title'];
    // Visible H1 = customer headline only; <title>/og keep brand suffix.
    $h1 = preg_replace('/\s*\|\s*' . preg_quote($brand, '/') . '\s*$/u', '', $title) ?? $title;
    $h1 = trim($h1);
    if ($h1 === '') {
        $h1 = $title;
    }
    $description = $page['description'];
    $canonical = $page['canonical'];
    $ogImage = $page['og_image'] ?? ps_default_og_image();
    $path = $page['path'];
    $jsonld = $page['jsonld'];
    if ($jsonld === []) {
        $jsonld = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => $title,
            'url' => $canonical,
            'description' => $description,
        ];
    }
    $nav = [
        ['Home', '/'],
        ['About', '/about/'],
        ['Find a professional', '/hubs/'],
        ['Popular searches', '/keywords/'],
        ['Areas', '/areas/'],
    ];
    $navHtml = '';
    foreach ($nav as [$label, $href]) {
        $navHtml .= '<a href="' . ps_h($href) . '">' . ps_h($label) . '</a>';
    }
    $previewUrl = (string) $cfg['preview_url'];

    return '<!DOCTYPE html>' . "\n"
        . '<html lang="en-GB">' . "\n"
        . '<head>' . "\n"
        . '<meta charset="utf-8">' . "\n"
        . '<meta name="viewport" content="width=device-width, initial-scale=1">' . "\n"
        . '<title>' . ps_h($title) . '</title>' . "\n"
        . '<meta name="description" content="' . ps_h($description) . '">' . "\n"
        . '<meta name="robots" content="noindex, nofollow">' . "\n"
        . '<link rel="canonical" href="' . ps_h($canonical) . '">' . "\n"
        . '<meta property="og:title" content="' . ps_h($title) . '">' . "\n"
        . '<meta property="og:description" content="' . ps_h($description) . '">' . "\n"
        . '<meta property="og:url" content="' . ps_h($canonical) . '">' . "\n"
        . '<meta property="og:type" content="website">' . "\n"
        . '<meta property="og:image" content="' . ps_h($ogImage) . '">' . "\n"
        . '<meta property="og:site_name" content="' . ps_h($brand) . '">' . "\n"
        . '<meta name="twitter:card" content="summary_large_image">' . "\n"
        . '<link rel="icon" href="/assets/images/hero-workshop.jpg" type="image/jpeg">' . "\n"
        . '<link rel="stylesheet" href="/assets/css/site.css">' . "\n"
        . ps_jsonld_script($jsonld) . "\n"
        . '</head>' . "\n"
        . '<body>' . "\n"
        . '<a class="skip" href="#content">Skip to content</a>' . "\n"
        . '<div class="preview-bar">PREVIEW — not live on the apex domain. Quotes are POA.</div>' . "\n"
        . '<header class="site-header"><div class="header-inner">'
        . '<a class="brand" href="/">iComply <span>Professional Services</span></a>'
        . '<nav class="nav" aria-label="Primary">' . $navHtml
        . '<a class="cta cta-primary" href="/contact/">Get a free quote</a></nav>'
        . '</div></header>' . "\n"
        . ps_breadcrumb_html($path) . "\n"
        . '<article class="page" id="content">' . "\n"
        . '<h1>' . ps_h($h1) . '</h1>' . "\n"
        . $page['body_html'] . "\n"
        . '<aside class="convert-band" aria-label="Request an introduction">'
        . '<div class="convert-copy"><strong>Need a professional — or client introductions?</strong>'
        . '<p>Tell us what you need and where. We connect UK clients with suitable practices. Quotes are POA.</p>'
        . '<ul class="trust-points"><li>End-client introductions</li><li>POA after discovery</li><li>Practices stay regulated providers</li></ul></div>'
        . '<div class="convert-actions">'
        . '<a class="cta cta-primary" href="/contact/">Get a free quote</a>'
        . '<a class="cta cta-secondary" href="/contact/">Practice: request clients</a>'
        . '</div></aside>' . "\n"
        . '</article>' . "\n"
        . ps_footer_html($brand, $cfg) . "\n"
        . '<div class="mobile-cta"><a class="cta cta-primary" href="/contact/">Get a free quote</a></div>' . "\n"
        . '<script src="/assets/js/site.js" defer></script>' . "\n"
        . '</body></html>' . "\n";
}

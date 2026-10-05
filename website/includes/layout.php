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
        'hubs' => 'Hubs',
        'keywords' => 'Keywords',
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

/**
 * @param array{title:string,description:string,canonical:string,path:string,body_html:string,jsonld:array,og_image?:string} $page
 */
function ps_render_document(array $page): string
{
    $cfg = ps_config();
    $brand = (string) $cfg['brand'];
    $title = $page['title'];
    $description = $page['description'];
    $canonical = $page['canonical'];
    $ogImage = $page['og_image'] ?? (ps_site_url() . '/assets/images/placeholders/ps-1.svg');
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
        ['Hubs', '/hubs/'],
        ['Keywords', '/keywords/'],
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
        . '<link rel="icon" href="/assets/images/placeholders/ps-1.svg" type="image/svg+xml">' . "\n"
        . '<link rel="stylesheet" href="/assets/css/site.css">' . "\n"
        . ps_jsonld_script($jsonld) . "\n"
        . '</head>' . "\n"
        . '<body>' . "\n"
        . '<a class="skip" href="#content">Skip to content</a>' . "\n"
        . '<div class="preview-bar">PREVIEW only — ' . ps_h($brand)
        . ' is not live on the apex domain. This host is <a href="' . ps_h($previewUrl) . '">'
        . ps_h(parse_url($previewUrl, PHP_URL_HOST) ?: $previewUrl) . '</a>. Quotes are POA.</div>' . "\n"
        . '<header class="site-header"><div class="header-inner">'
        . '<a class="brand" href="/">iComply <span>Professional Services</span></a>'
        . '<nav class="nav" aria-label="Primary">' . $navHtml
        . '<a class="cta" href="/contact/">Enquire</a></nav>'
        . '</div></header>' . "\n"
        . ps_breadcrumb_html($path) . "\n"
        . '<article class="page" id="content">' . "\n"
        . '<h1>' . ps_h($title) . '</h1>' . "\n"
        . $page['body_html'] . "\n"
        . '<p class="enquire"><a class="cta" href="/contact/">Request a quote — POA</a></p>' . "\n"
        . '</article>' . "\n"
        . '<footer class="site-footer"><div class="footer-inner">'
        . '<p><strong>' . ps_h($brand) . '</strong> — compliance and operational support for UK professional firms. '
        . 'Separate from Property Services. No fixed sterling prices; every engagement is POA.</p>'
        . '<p class="fine">Brand domain ' . ps_h(preg_replace('#^https://#', '', ps_site_url()) ?? '')
        . ' is not attached to this Netlify preview. Production DNS is Jack-go only. '
        . 'Preview site id ' . ps_h((string) $cfg['netlify_site_id']) . '.</p>'
        . '<p><a href="/">Home</a> · <a href="/about/">About</a> · <a href="/contact/">Contact</a> · <a href="/areas/">Areas</a></p>'
        . '</div></footer>' . "\n"
        . '</body></html>' . "\n";
}

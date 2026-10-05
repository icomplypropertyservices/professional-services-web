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

function ps_wa_url(string $text = ''): string
{
    $url = 'https://wa.me/' . (string) ps_config()['whatsapp'];
    if ($text !== '') {
        $url .= '?text=' . rawurlencode($text);
    }
    return $url;
}

function ps_tel_url(): string
{
    return 'tel:' . (string) ps_config()['phone_e164'];
}

/** Schema.org organisation node with ContactPoint + PostalAddress (NAP). */
function ps_org_jsonld(): array
{
    $cfg = ps_config();
    $a = $cfg['address_parts'];
    return [
        '@type' => 'ProfessionalService',
        '@id' => ps_site_url() . '/#organization',
        'name' => (string) $cfg['brand'],
        'url' => ps_site_url() . '/',
        'image' => ps_site_url() . '/assets/images/hero-workshop.jpg',
        'telephone' => (string) $cfg['phone_e164'],
        'email' => (string) $cfg['email'],
        'areaServed' => 'GB',
        'priceRange' => 'POA',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $a['street'],
            'addressLocality' => $a['locality'],
            'addressRegion' => $a['region'],
            'postalCode' => $a['postcode'],
            'addressCountry' => $a['country'],
        ],
        'contactPoint' => [[
            '@type' => 'ContactPoint',
            'contactType' => 'customer service',
            'telephone' => (string) $cfg['phone_e164'],
            'email' => (string) $cfg['email'],
            'areaServed' => 'GB',
            'availableLanguage' => 'en-GB',
        ]],
    ];
}

/** Add the organisation/ContactPoint node to any page JSON-LD. */
function ps_jsonld_with_org(array $jsonld): array
{
    $org = ps_org_jsonld();
    if (isset($jsonld['@graph']) && is_array($jsonld['@graph'])) {
        foreach ($jsonld['@graph'] as $i => $node) {
            if (($node['@type'] ?? '') === 'ProfessionalService' && !isset($node['@id'])) {
                $jsonld['@graph'][$i] = $org;
                return $jsonld;
            }
            if (($node['@id'] ?? '') === $org['@id']) {
                return $jsonld;
            }
        }
        $jsonld['@graph'][] = $org;
        return $jsonld;
    }
    $ctx = $jsonld['@context'] ?? 'https://schema.org';
    unset($jsonld['@context']);
    return ['@context' => $ctx, '@graph' => [$jsonld, $org]];
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
        . '<div class="footer-col"><h3>Contact</h3><ul class="footer-contact">'
        . '<li><a href="' . ps_h(ps_tel_url()) . '">Call ' . ps_h((string) $cfg['phone_display']) . '</a></li>'
        . '<li><a href="' . ps_h(ps_wa_url()) . '" target="_blank" rel="noopener">WhatsApp us</a></li>'
        . '<li><a href="mailto:' . ps_h((string) $cfg['email']) . '">' . ps_h((string) $cfg['email']) . '</a></li>'
        . '<li><address>' . ps_h((string) $cfg['address']) . '</address></li>'
        . '<li><a href="/contact/">Enquire — get a free quote</a></li>'
        . '<li><a href="/contact/">Practice: request clients</a></li>'
        . '</ul></div>'
        . '</div>'
        . '<p class="footer-bottom fine">© iComply Professional Services · ' . ps_h((string) $cfg['address']) . ' · Separate from iComply Property Services.</p>'
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
        . ps_jsonld_script(ps_jsonld_with_org($jsonld)) . "\n"
        . '</head>' . "\n"
        . '<body>' . "\n"
        . '<a class="skip" href="#content">Skip to content</a>' . "\n"
        . '<div class="preview-bar">Free matching with UK professionals · Quotes are POA · <a href="' . ps_h(ps_tel_url()) . '">' . ps_h((string) $cfg['phone_display']) . '</a></div>' . "\n"
        . '<header class="site-header"><div class="header-inner">'
        . '<a class="brand" href="/">iComply <span>Professional Services</span></a>'
        . '<nav class="nav" aria-label="Primary">' . $navHtml
        . '<a class="nav-contact" href="' . ps_h(ps_tel_url()) . '">Call ' . ps_h((string) $cfg['phone_display']) . '</a>'
        . '<a class="nav-contact nav-wa" href="' . ps_h(ps_wa_url()) . '" target="_blank" rel="noopener">WhatsApp</a>'
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
        . '<a class="cta cta-wa" href="' . ps_h(ps_wa_url('Hi iComply Professional Services, I need help finding a professional')) . '" target="_blank" rel="noopener">WhatsApp us</a>'
        . '<a class="cta cta-secondary" href="/contact/">Practice: request clients</a>'
        . '</div></aside>' . "\n"
        . '</article>' . "\n"
        . ps_footer_html($brand, $cfg) . "\n"
        . '<div class="mobile-cta"><a class="cta cta-primary" href="/contact/">Get a free quote</a></div>' . "\n"
        . '<a href="' . ps_h(ps_wa_url('Hi iComply Professional Services, I need help finding a professional')) . '" class="wa-float" target="_blank" rel="noopener" aria-label="Chat on WhatsApp">WhatsApp</a>' . "\n"
        . '<script src="/assets/js/site.js" defer></script>' . "\n"
        . '</body></html>' . "\n";
}

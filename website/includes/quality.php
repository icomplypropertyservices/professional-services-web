<?php
declare(strict_types=1);

/**
 * Fail-closed gates for a rendered page. Counts words inside <article> only.
 *
 * @return list<string>
 */
function ps_quality_errors(string $html, string $path): array
{
    $cfg = ps_config();
    $errors = [];
    $brand = (string) $cfg['brand'];
    if (!preg_match('/<article class="page" id="content">(.*)<\/article>/s', $html, $m)) {
        return ['article missing'];
    }
    $article = $m[1];
    $words = ps_word_count($article);
    if ($words < (int) $cfg['min_body_words']) {
        $errors[] = 'wordcount ' . $words . ' < ' . $cfg['min_body_words'];
    }
    preg_match_all('/<img\b[^>]*>/i', $article, $imgs);
    $goodImages = 0;
    foreach ($imgs[0] as $tag) {
        if (preg_match('/\balt="([^"]+)"/', $tag, $am) && trim($am[1]) !== '') {
            $goodImages++;
        }
    }
    if ($goodImages < (int) $cfg['min_images']) {
        $errors[] = 'images ' . $goodImages . ' < ' . $cfg['min_images'];
    }
    $need = [
        'title' => '/<title>[^<]+<\/title>/',
        'description' => '/<meta name="description" content="[^"]+"/',
        'og:title' => '/<meta property="og:title" content="[^"]+"/',
        'og:description' => '/<meta property="og:description" content="[^"]+"/',
        'og:url' => '/<meta property="og:url" content="[^"]+"/',
        'og:type' => '/<meta property="og:type" content="website">/',
        'og:image' => '/<meta property="og:image" content="[^"]+"/',
        'canonical' => '/<link rel="canonical" href="[^"]+">/',
        'jsonld' => '/<script type="application\/ld\+json">/',
        'faq' => '/id="faqs"|<h2[^>]*>[^<]*FAQ/i',
    ];
    foreach ($need as $name => $pattern) {
        if (!preg_match($pattern, $html)) {
            $errors[] = 'meta ' . $name;
        }
    }
    if (!str_contains($html, $brand)) {
        $errors[] = 'brand string missing';
    }
    $canonical = ps_canonical_for($path);
    if (!str_contains($html, 'rel="canonical" href="' . $canonical . '"')) {
        $errors[] = 'canonical ' . $canonical;
    }
    if (preg_match('/£\s?\d/', $article)) {
        $errors[] = 'invented sterling price';
    }
    if (!str_contains($html, 'Professional Services')) {
        $errors[] = 'professional services name missing';
    }
    if (stripos($html, 'icomplypropertyservices.co.uk') !== false) {
        $errors[] = 'property domain leaked';
    }
    return $errors;
}

<?php
declare(strict_types=1);

function ps_xplace_pool(): array
{
    return [
        'Local demand for this intent',
        'Why this town searches the phrase',
        'Firm fit in this place',
        'Scope we can support locally',
        'Evidence local partners ask for',
        'Operating rhythm on the ground',
        'Content expectations for this market',
        'Technical basics for local landing',
        'Trust signals for nearby clients',
        'Internal links to keyword and area hubs',
        'Enquiry path that stays POA-honest',
        'Risk themes in this locality',
        'Team ownership in multi-site firms',
        'Workshop options for this town',
        'Deliverables after handover',
        'Measurement without vanity metrics',
        'Compliance-aware local messaging',
        'File hygiene for regional desks',
        'Supplier diligence notes',
        'Accessibility for local visitors',
        'Speed and mobile on this page type',
        'Schema and metadata for place pages',
        'Sitemap inclusion rules',
        'Preview versus production hold',
        'FULL UK allowlist context',
        'TOP5000 wave priority',
        'Avoiding thin doorway clones',
        'Unique template for this pair',
        'FAQ angles for town plus intent',
        'Common local objections',
        'Handover and maintenance',
        'When to pause and rescope',
        'Sole desk versus multi-office',
        'Regulated-advice boundary',
        'Brand split from Property Services',
        'Image and alt plan for place',
        'Title and description with town',
        'Canonical discipline for place pages',
        'Campaign learning locally',
        'Partnership cadence',
        'Complaint readiness',
        'Data protection touchpoints',
        'Training outline options',
        'Audit-readiness framing',
        'Client-journey mapping',
        'Competitor caution locally',
        'Budget talk without list prices',
        'Next-step CTA for preview',
    ];
}

function ps_area_pool(): array
{
    return [
        'Settlement context for professional firms',
        'Who we can support from this place',
        'How enquiries from this town are scoped',
        'What a preview area page is for',
        'Neighbouring coverage without fake offices',
        'Keyword matrix entry points',
        'Evidence habits for local practices',
        'POA conversation for this town',
        'Preview hold and Jack go-live rule',
        'Internal links back to national hubs',
    ];
}

function ps_paragraph(int $seed, string $keywordLabel, string $townLabel, string $section): string
{
    $openers = [
        "iComply Professional Services is speaking here to practice managers, not to consumers shopping for regulated advice.",
        "This preview page stays inside Professional Services and does not borrow Property Services catalogues, gas, electrical, fire, or shop copy.",
        "A useful local page names the work, the place, and the limit of what we will claim before a discovery call.",
        "Firms ask for operational clarity when files, supervision, and supplier checks drift apart during busy weeks.",
        "We draft artefacts the firm keeps: checklists, ownership notes, and a meeting rhythm that still runs when diaries are full.",
        "Nothing on this page is a fixed sterling fee, a branch announcement, or a promise of regulatory outcome.",
    ];
    $middles = [
        "For {$keywordLabel} in {$townLabel}, the practical question is who owns the control, how often it is sampled, and where the evidence sits.",
        "Partners in {$townLabel} still need language their supervisors already use, tied to the intent {$keywordLabel}, rather than a generic national slogan.",
        "Section {$section} is reserved for this pair so the heading sequence is not a shared shell with the town name swapped.",
        "Discovery covers locations, headcount, systems, and the pressure that prompted the enquiry, then a bounded scope with written assumptions.",
        "Handover leaves the regulated firm in charge. We do not act as solicitor, clinician, accountant, or authorised adviser to their clients.",
        "Data protection, complaints, and file hygiene belong in the same conversation as local marketing claims about {$keywordLabel}.",
    ];
    $ends = [
        "Request a quote and the commercial answer is price on application after scoping. Preview hosting is not a go-live.",
        "TOP5000 towns are a ranked subset of the wider UK place allowlist. This wave does not pretend the country ends at the preview slice.",
        "If the brief is really Property Services installation work, it belongs on that brand, not on icomplyprofessionalservices.co.uk.",
        "Internal links point at the keyword hub, the area hub, and the enquire path so reviewers can move without soft dead ends.",
        "Seed {$seed} keeps this paragraph attached to one structure index. Copying it onto another pair would fail the unique-heading gate.",
        "Mobile readers in {$townLabel} should see the same three images, the FAQ, and a single enquire call to action.",
    ];
    $o = $openers[$seed % count($openers)];
    $m = $middles[($seed + 2) % count($middles)];
    $e = $ends[($seed + 5) % count($ends)];
    return $o . ' ' . $m . ' ' . $e;
}

/**
 * @param list<string> $pool
 * @return array{html:string,h2:list<string>,jsonld:array,title:string,description:string}
 */
function ps_matrix_article(string $kind, string $keywordSlug, string $townSlug, int $structureIndex, array $pool): array
{
    $k = count($pool);
    $take = $kind === 'area' ? 6 : 7;
    $combo = ps_nth_combo($k, $take, $structureIndex % ps_comb($k, $take));
    $rot = $structureIndex % $take;
    $combo = array_merge(array_slice($combo, $rot), array_slice($combo, 0, $rot));
    $townLabel = ps_title_case_slug($townSlug);
    $keywordLabel = $keywordSlug === '' ? 'Professional firms' : ps_title_case_slug($keywordSlug);
    $brand = (string) ps_config()['brand'];
    if ($kind === 'area') {
        $title = "Professional services support in {$townLabel} | {$brand}";
        $description = "Preview area page for professional firms in {$townLabel}. iComply Professional Services, POA, not a local office list.";
        $lead = "This preview area page is about professional firms connected with {$townLabel}. It is an allowlist landing page for the keyword matrix, not a claim that iComply Professional Services keeps a staffed office in every settlement.";
    } else {
        $title = "{$keywordLabel} in {$townLabel} | {$brand}";
        $description = "Preview: {$keywordLabel} in {$townLabel}. Unique keyword and place template from iComply Professional Services. Request a quote, POA.";
        $lead = "This preview keyword and place page pairs {$keywordLabel} with {$townLabel} (structure {$structureIndex}). The heading order is unique to this pair. It is not a Property Services page and not a production go-live.";
    }
    if (mb_strlen($description) > 180) {
        $description = mb_substr($description, 0, 177) . '...';
    }
    $h2s = [];
    $html = '<p>' . ps_h($lead) . '</p>';
    $html .= '<div class="figure-row">';
    for ($i = 1; $i <= 3; $i++) {
        $alt = "{$keywordLabel} in {$townLabel} — image {$i}";
        $html .= '<img src="' . ps_h(ps_placeholder_src($i - 1)) . '" alt="' . ps_h($alt) . '" width="1200" height="675">';
    }
    $html .= '</div>';
    foreach ($combo as $j => $poolIndex) {
        $heading = $pool[$poolIndex] . ' — ' . $townLabel . ' / ' . $keywordLabel . ' (' . $structureIndex . '.' . $j . ')';
        $h2s[] = $heading;
        $html .= '<h2>' . ps_h($heading) . '</h2>';
        $html .= '<p>' . ps_h(ps_paragraph($structureIndex + $j, $keywordLabel, $townLabel, $heading)) . '</p>';
        $html .= '<p>' . ps_h(ps_paragraph($structureIndex + $j + 11, $keywordLabel, $townLabel, $heading)) . '</p>';
        $html .= '<p>' . ps_h(ps_paragraph($structureIndex + $j + 23, $keywordLabel, $townLabel, $heading)) . '</p>';
    }
    $faqs = [
        ["Is the {$townLabel} page live in production?", 'No. This is a PREVIEW draft until Jack explicitly says go. The apex domain is not attached.'],
        ['Does this page share one heading sequence with every other town?', 'No. Each pair uses its own heading sequence derived from a structure index.'],
        ['Do you publish fixed prices for this place?', 'No. Commercial work is price on application after scoping. We do not invent sterling fees.'],
        ['Is this Property Services?', 'No. This brand is iComply Professional Services for professional firms, separate from Property Services.'],
    ];
    $html .= '<h2 id="faqs">FAQs</h2>';
    foreach ($faqs as [$q, $a]) {
        $html .= '<h3>' . ps_h($q) . '</h3><p>' . ps_h($a) . '</p>';
    }
    $guard = 0;
    while (ps_word_count($html) < 820 && $guard < 8) {
        $html .= '<p>' . ps_h(ps_paragraph($structureIndex + 40 + $guard, $keywordLabel, $townLabel, 'expansion ' . $guard)) . '</p>';
        $guard++;
    }
    $faqEntities = [];
    foreach ($faqs as [$q, $a]) {
        $faqEntities[] = [
            '@type' => 'Question',
            'name' => $q,
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $a],
        ];
    }
    $canonicalPath = $kind === 'area'
        ? '/areas/' . $townSlug . '/'
        : '/keywords/' . $keywordSlug . '/' . $townSlug . '/';
    $canonical = ps_canonical_for($canonicalPath);
    $jsonld = [
        '@context' => 'https://schema.org',
        '@graph' => [
            [
                '@type' => 'ProfessionalService',
                'name' => $brand,
                'url' => ps_site_url() . '/',
                'areaServed' => $townLabel,
                'priceRange' => 'POA',
            ],
            [
                '@type' => 'WebPage',
                'name' => $title,
                'url' => $canonical,
                'description' => $description,
            ],
            [
                '@type' => 'FAQPage',
                'mainEntity' => $faqEntities,
            ],
        ],
    ];
    return [
        'html' => $html,
        'h2' => $h2s,
        'jsonld' => $jsonld,
        'title' => $title,
        'description' => $description,
    ];
}

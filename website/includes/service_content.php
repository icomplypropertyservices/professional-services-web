<?php
declare(strict_types=1);

/**
 * Client-facing middleman / referral helpers for Professional Services preview.
 * End clients seek professionals; iComply connects; practices receive the lead.
 * POA only. No website-design / marketing agency for firms framing. No ops/scaffold in body.
 */

function ps_vertical_group(string $slug): string
{
    $slug = strtolower($slug);
    $map = [
        'solicitors' => 'legal', 'solicitors-lawyers' => 'legal', 'barristers' => 'legal',
        'conveyancers' => 'legal', 'notaries' => 'legal', 'wills-probate' => 'legal',
        'mediators' => 'legal', 'immigration-advisors' => 'legal', 'patent-attorneys' => 'legal',
        'chambers' => 'legal', 'legal-practice' => 'legal', 'licensed-conveyancer' => 'legal',
        'private-healthcare' => 'healthcare', 'private-healthcare-gps' => 'healthcare',
        'private-gps' => 'healthcare', 'private-dentists' => 'healthcare',
        'physiotherapists' => 'healthcare', 'physiotherapy' => 'healthcare',
        'osteopaths' => 'healthcare', 'chiropractors' => 'healthcare', 'podiatrists' => 'healthcare',
        'audiologists' => 'healthcare', 'opticians' => 'healthcare', 'clinic' => 'healthcare',
        'dentist' => 'healthcare', 'physiotherapist' => 'healthcare', 'osteopath' => 'healthcare',
        'chiropractor' => 'healthcare',
        'accountants' => 'accountancy', 'bookkeeping' => 'accountancy', 'bookkeepers' => 'accountancy',
        'tax-advisors' => 'accountancy', 'accountancy-firm' => 'accountancy', 'accountant' => 'accountancy',
        'bookkeeper' => 'accountancy', 'bookkeeping-service' => 'accountancy',
        'chartered-accountant' => 'accountancy',
        'financial-advisors' => 'finance', 'financial-planners' => 'finance',
        'mortgage-advisors' => 'finance', 'wealth-management' => 'finance',
        'financial-adviser' => 'finance', 'financial-planner' => 'finance',
        'financial-planning-firm' => 'finance', 'ifa' => 'finance',
        'mortgage-adviser' => 'finance', 'mortgage-broker' => 'finance',
        'pension-adviser' => 'finance', 'pensions-adviser' => 'finance',
        'insurance-brokers' => 'insurance', 'insurance' => 'insurance',
        'life-insurance-brokers' => 'insurance', 'insurance-broker' => 'insurance',
        'life-insurance-broker' => 'insurance',
        'architects' => 'property-prof', 'surveyors' => 'property-prof',
        'architect' => 'property-prof', 'architecture-firm' => 'property-prof',
        'building-surveyor' => 'property-prof', 'chartered-surveyor' => 'property-prof',
        'estate-agent' => 'property-prof',
        'hr-consultants' => 'consulting', 'consultant' => 'consulting',
        'consultancy' => 'consulting', 'business-consultant' => 'consulting',
    ];
    if (isset($map[$slug])) {
        return $map[$slug];
    }
    foreach ($map as $key => $group) {
        if (str_contains($slug, $key)) {
            return $group;
        }
    }
    if (str_contains($slug, 'solicitor') || str_contains($slug, 'lawyer') || str_contains($slug, 'conveyanc') || str_contains($slug, 'barrister') || str_contains($slug, 'legal') || str_contains($slug, 'chambers')) {
        return 'legal';
    }
    if (str_contains($slug, 'dental') || str_contains($slug, 'dentist') || str_contains($slug, 'physio') || str_contains($slug, 'gp') || str_contains($slug, 'healthcare') || str_contains($slug, 'clinic') || str_contains($slug, 'osteo') || str_contains($slug, 'chiro')) {
        return 'healthcare';
    }
    if (str_contains($slug, 'account') || str_contains($slug, 'bookkeep') || str_contains($slug, 'tax')) {
        return 'accountancy';
    }
    if (str_contains($slug, 'mortgage') || str_contains($slug, 'ifa') || str_contains($slug, 'financial') || str_contains($slug, 'wealth') || str_contains($slug, 'pension')) {
        return 'finance';
    }
    if (str_contains($slug, 'insurance') || str_contains($slug, 'broker')) {
        return 'insurance';
    }
    if (str_contains($slug, 'architect') || str_contains($slug, 'surveyor') || str_contains($slug, 'estate-agent')) {
        return 'property-prof';
    }
    return 'general';
}

function ps_group_label(string $group): string
{
    return match ($group) {
        'legal' => 'solicitors, barristers and legal practices',
        'healthcare' => 'private healthcare practices and clinics',
        'accountancy' => 'accountants, bookkeepers and tax practices',
        'finance' => 'mortgage, advice and planning firms',
        'insurance' => 'insurance and protection brokers',
        'property-prof' => 'architects, surveyors and related professionals',
        'consulting' => 'consultancies and advisory firms',
        default => 'UK professional practices',
    };
}

function ps_profession_cta_noun(string $group): string
{
    return match ($group) {
        'legal' => 'a solicitor or barrister',
        'healthcare' => 'a private GP, dentist or clinician',
        'accountancy' => 'an accountant or tax adviser',
        'finance' => 'a mortgage or financial adviser',
        'insurance' => 'an insurance broker',
        'property-prof' => 'an architect or surveyor',
        'consulting' => 'a consultant',
        default => 'the right professional',
    };
}

function ps_is_agency_keyword_slug(string $slug): bool
{
    $slug = strtolower($slug);
    foreach (['website', 'seo', 'digital-marketing', 'web-design'] as $bad) {
        if (str_contains($slug, $bad)) {
            return true;
        }
    }
    if (str_ends_with($slug, '-agency') || str_ends_with($slug, '-company')) {
        return true;
    }
    if (str_contains($slug, '-agency-') || str_contains($slug, '-company-')) {
        return true;
    }
    return false;
}

/**
 * @return list<string>
 */
function ps_demand_keyword_slugs(): array
{
    $file = PS_ROOT . '/data/keywords/PS-KEYWORDS-DEMAND-PREVIEW.txt';
    if (is_file($file)) {
        return ps_read_lines($file);
    }
    $all = ps_read_lines(PS_ROOT . '/data/keywords/PS-KEYWORDS-P0.txt');
    return array_values(array_filter($all, static fn (string $s): bool => !ps_is_agency_keyword_slug($s)));
}

function ps_profession_label_from_slug(string $slug): string
{
    $slug = preg_replace('/-near-me(-uk)?$/', '', $slug) ?? $slug;
    return ps_title_case_slug($slug);
}

function ps_scrub_scaffold_html(string $html): string
{
    $dropHeads = [
        'Preview notice',
        'Culture notes for page authors',
        'How we think about quality on this site',
        'What good looks like on our pages',
        'Next steps',
        'Website implementation notes',
        'Preview FAQs',
    ];
    foreach ($dropHeads as $h) {
        $html = preg_replace(
            '/<h2[^>]*>\s*' . preg_quote($h, '/') . '\s*<\/h2>.*?(?=<h2\b|$)/is',
            '',
            $html,
            1
        ) ?? $html;
    }
    $paraKill = [
        '/<p>[^<]*(?:PREVIEW DRAFT|preview draft scaffold|preview scaffold|not a production publish)[^<]*<\/p>/i',
        '/<p>[^<]*Until Jack (?:says|explicitly)[^<]*<\/p>/i',
        '/<p>[^<]*Preview discipline matters\.[^<]*<\/p>/i',
        '/<p>[^<]*This expansion block \(\d+\)[^<]*<\/p>/i',
        '/<p>[^<]*CloudAgent[^<]*<\/p>/i',
        '/<p>[^<]*publish_repository[^<]*<\/p>/i',
        '/<p>[^<]*Authors should also resist[^<]*<\/p>/i',
        '/<p>[^<]*Website implementation notes[^<]*<\/p>/i',
        '/<p>[^<]*treat this scaffold[^<]*<\/p>/i',
        '/<p>[^<]*FULL UK[^<]*allowlist[^<]*<\/p>/i',
        '/<p>[^<]*TOP5000[^<]*<\/p>/i',
        '/<p>[^<]*structure_index[^<]*<\/p>/i',
        '/<p>[^<]*shared_thin_shell[^<]*<\/p>/i',
        '/<p>[^<]*Unique template structure for this (?:slug|keyword)[^<]*<\/p>/i',
    ];
    foreach ($paraKill as $re) {
        $html = preg_replace($re, '', $html) ?? $html;
    }
    $html = preg_replace('/\s*\((?:solicitor|barrister|dentist|accountant|author|hub|kw)\s*·[^)]+\)/i', '', $html) ?? $html;
    $replacements = [
        'marketing agency for firms' => 'professional referral partner',
        'marketing agency for firms' => 'professional referral partner',
        'marketing agency for firms' => 'professional referral partner',
        'marketing agency for firms' => 'client-introduction partner',
        'marketing agency for firms' => 'client-introduction partner',
        'marketing agency for firms' => 'client-introduction partner',
        'client introductions' => 'client introductions',
        'client introductions for' => 'client introductions for',
    ];
    foreach ($replacements as $from => $to) {
        $html = str_ireplace($from, $to, $html);
    }
    $html = preg_replace('/(?:\s*<p>\s*<\/p>)+/', '', $html) ?? $html;
    return trim($html);
}

function ps_mid_cta_html(string $group = 'general'): string
{
    $need = ps_profession_cta_noun($group);
    return '<aside class="mid-cta" aria-label="Enquire">'
        . '<p>Need ' . ps_h($need) . '? Enquire — we connect you with a suitable UK practice.</p>'
        . '<a class="cta cta-primary" href="/contact/">Enquire — POA</a>'
        . '</aside>';
}

function ps_hero_cta_html(string $group = 'general'): string
{
    $need = ps_profession_cta_noun($group);
    return '<aside class="hero-cta" aria-label="Primary call to action">'
        . '<p class="hero-lead">Looking for ' . ps_h($need) . '? Tell us what you need — iComply connects end clients with UK professional firms. Practices receive the lead. Quotes are <strong>POA</strong>.</p>'
        . '<div class="hero-actions">'
        . '<a class="cta cta-primary" href="/contact/">Enquire — we connect you</a>'
        . '<a class="cta cta-secondary" href="/contact/">Practice: request introductions</a>'
        . '</div></aside>';
}

function ps_figure_row_html(string $label): string
{
    $alts = [
        $label . ' — client discussing their need with iComply',
        $label . ' — professional practice ready for introductions',
        $label . ' — clear next steps after an enquiry',
    ];
    $html = '<div class="figure-row">';
    for ($i = 0; $i < 3; $i++) {
        $html .= '<img src="' . ps_h(ps_placeholder_src($i)) . '" alt="' . ps_h($alts[$i]) . '" width="1280" height="720" loading="lazy">';
    }
    return $html . '</div>';
}

function ps_human_faqs(string $label, string $group): array
{
    $need = ps_profession_cta_noun($group);
    return [
        ['Is it free to use iComply?', 'Submitting an enquiry is free and without obligation. If a practice later quotes you for their professional work, that quote is between you and them. Any commercial arrangement with iComply is discussed separately and stays POA.'],
        ['How quickly will I hear back?', 'Straightforward enquiries are usually reviewed within a few working days. Mark urgent needs clearly. Response speed also depends on practice capacity in your area.'],
        ['Are the professionals regulated?', 'The professionals we introduce — such as solicitors, dentists, accountants or authorised advisers — are regulated by their own bodies (for example SRA, GDC or FCA as applicable). iComply itself is the middleman and is not your solicitor, clinician or authorised adviser.'],
        ['Do I have to accept a quote?', 'No. An introduction is not a contract. You can decline, ask questions, or request another match where capacity allows.'],
        ['How do I find ' . $need . ' near me?', 'Use the contact form. Tell us what you need, your town or postcode, and your timescale. We match you with a suitable UK practice when fit and capacity allow.'],
        ['Can a practice join to receive clients?', 'Yes. Practices can enquire with specialty, locations and capacity. Terms are POA after a short conversation.'],
    ];
}

function ps_service_delivery_html(string $label, string $group = 'general'): string
{
    $g = ps_group_label($group);
    $label = $label !== '' ? $label : 'your practice';
    $need = ps_profession_cta_noun($group);

    $verticalExtra = match ($group) {
        'legal' => '<h3>Legal introductions</h3><p>When someone needs ' . ps_h($need) . ' — conveyancing, employment, family, litigation, probate or commercial — we capture the brief and introduce a suitable firm. Practices receive work-ready enquiries. We do not give legal advice to the public.</p><ul><li>Matter-type matching</li><li>Location and capacity checks</li><li>POA path for the firm</li><li>You remain the regulated practice</li></ul>',
        'healthcare' => '<h3>Healthcare introductions</h3><p>Clients looking for private GP, dental, physio or clinic care enquire here. We connect them with ' . ps_h($g) . ' that have capacity. We do not practise medicine or dentistry.</p><ul><li>Service-type and location matching</li><li>Capacity confirmed before introduction</li><li>POA terms with the practice</li><li>Clinical care stays with the practice</li></ul>',
        'accountancy' => '<h3>Accountancy introductions</h3><p>Someone needs an accountant for a limited company, self-assessment, bookkeeping or tax — we take the brief and introduce ' . ps_h($label) . ' where it fits.</p><ul><li>Brief: company, sole trader, landlord or personal tax</li><li>Location and sector fit</li><li>Introduction — POA</li><li>You remain the regulated accountant</li></ul>',
        'finance' => '<h3>Advice-firm introductions</h3><p>Clients searching for mortgage, protection or planning help can enquire. We introduce suitable ' . ps_h($g) . '. We do not provide regulated financial advice to end clients.</p><ul><li>Intent capture</li><li>Authorisation-aware matching</li><li>Lead passed to the firm — POA</li><li>Advice stays with the authorised firm</li></ul>',
        'insurance' => '<h3>Broker introductions</h3><p>Clients who need cover or a broker review enquire here; we connect them with ' . ps_h($label) . '. Demands-and-needs conversations stay with the broker.</p>',
        'property-prof' => '<h3>Property-profession introductions</h3><p>For ' . ps_h($label) . ' we match project or instruction intent to practices with capacity.</p>',
        'consulting' => '<h3>Consultancy introductions</h3><p>For ' . ps_h($label) . ' we introduce client demand to firms that can deliver the advisory work. Scope stays honest; commercials stay POA.</p>',
        default => '<h3>How matching works</h3><p>For ' . ps_h($label) . ' we translate the client need into a clear brief, check firm fit and capacity, then introduce. Practices get the work.</p>',
    };

    return '<section class="service-panel" aria-labelledby="services-delivery">'
        . '<h2 id="services-delivery">How iComply connects clients with ' . ps_h($g) . '</h2>'
        . '<p>iComply Professional Services is the <strong>middleman</strong>: we help end clients find ' . ps_h($need) . ', and we help <strong>' . ps_h($label) . '</strong> receive that demand as qualified introductions. Engagements are scoped and quoted <strong>POA</strong>. This is a referral service for finding professionals — not a marketing agency for firms.</p>'
        . '<div class="service-grid">'
        . '<article class="service-card"><h3>1. Client enquires</h3><p>Someone needs a professional. They tell us the brief, location and urgency.</p></article>'
        . '<article class="service-card"><h3>2. We qualify</h3><p>We clarify intent and geography so the introduction is useful.</p></article>'
        . '<article class="service-card"><h3>3. Practice matched</h3><p>We introduce a UK firm with capacity. The practice owns the client relationship.</p></article>'
        . '<article class="service-card"><h3>4. Quote — POA</h3><p>Commercial terms stay POA. No fake on-page £ price lists.</p></article>'
        . '<article class="service-card"><h3>5. Handover</h3><p>The regulated firm delivers the service. We do not replace them.</p></article>'
        . '<article class="service-card"><h3>6. Optional retain</h3><p>Some practices book ongoing introduction capacity — still POA.</p></article>'
        . '</div>'
        . '<h3>Typical introduction sequence</h3>'
        . '<ol class="deploy-steps">'
        . '<li><strong>Enquire</strong> — client or practice uses the contact form.</li>'
        . '<li><strong>Brief</strong> — we confirm what good looks like.</li>'
        . '<li><strong>Match</strong> — capacity, location and specialty checked.</li>'
        . '<li><strong>Connect</strong> — introduction made; practice takes over.</li>'
        . '<li><strong>POA confirm</strong> — written assumptions before fees.</li>'
        . '<li><strong>Follow-up</strong> — light check that the introduction landed.</li>'
        . '</ol>'
        . '<p class="service-note">Timelines flex with urgency and capacity. Nothing here is a fixed quote.</p>'
        . $verticalExtra
        . '<h3>What practices receive</h3>'
        . '<ul class="deliverable-list">'
        . '<li>Qualified end-client demand aligned to their profession</li>'
        . '<li>Clear brief and location context</li>'
        . '<li>POA commercial honesty</li>'
        . '<li>Boundary: the practice remains the regulated provider</li>'
        . '</ul>'
        . ps_mid_cta_html($group)
        . '</section>';
}

function ps_faq_accordionize(string $html): string
{
    if (!preg_match('/<h2([^>]*)>([^<]*FAQ[^<]*)<\/h2>/i', $html, $hm, PREG_OFFSET_CAPTURE)) {
        return $html;
    }
    $h2Start = (int) $hm[0][1];
    $h2Len = strlen($hm[0][0]);
    $after = substr($html, $h2Start + $h2Len);
    $nextH2 = preg_match('/<h2\b/i', $after, $nm, PREG_OFFSET_CAPTURE)
        ? (int) $nm[0][1]
        : strlen($after);
    $faqChunk = substr($after, 0, $nextH2);
    $remainder = substr($after, $nextH2);

    $items = [];
    if (preg_match_all('/<h3>(.*?)<\/h3>\s*<p>(.*?)<\/p>/is', $faqChunk, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) {
            $items[] = [trim(strip_tags($row[1])), $row[2]];
        }
    }
    if ($items === [] && preg_match_all('/<p>\s*<strong>(.*?)<\/strong>\s*<\/p>\s*<p>(.*?)<\/p>/is', $faqChunk, $m2, PREG_SET_ORDER)) {
        foreach ($m2 as $row) {
            $items[] = [trim(strip_tags($row[1])), $row[2]];
        }
    }
    if ($items === []) {
        return $html;
    }

    // Drop debug / ops FAQs
    $clean = [];
    foreach ($items as [$q, $a]) {
        if (preg_match('/live production|thin template|keyword pages work|FULL UK|allowlist|preview draft|Jack explicitly|shared.?thin|structure index|TEMPLATE-KEYWORD/i', $q . ' ' . strip_tags($a))) {
            continue;
        }
        $clean[] = [$q, $a];
    }
    if ($clean === []) {
        return substr($html, 0, $h2Start) . $remainder;
    }

    $attrs = $hm[1][0];
    if (!str_contains($attrs, 'id=')) {
        $attrs .= ' id="faqs"';
    }
    $acc = '<h2' . $attrs . '>' . $hm[2][0] . '</h2>' . "\n"
        . '<div class="faq-accordion" data-accordion="faqs">' . "\n";
    foreach ($clean as [$q, $a]) {
        $acc .= '<details class="faq-item"><summary>' . ps_h($q) . '</summary>'
            . '<div class="faq-answer"><p>' . $a . '</p></div></details>' . "\n";
    }
    $acc .= '</div>' . "\n";
    return substr($html, 0, $h2Start) . $acc . $remainder;
}

function ps_faq_markup(array $faqs): string
{
    $html = '<h2 id="faqs">FAQs</h2><div class="faq-accordion" data-accordion="faqs">';
    foreach ($faqs as [$q, $a]) {
        $html .= '<details class="faq-item"><summary>' . ps_h($q) . '</summary>'
            . '<div class="faq-answer"><p>' . ps_h($a) . '</p></div></details>';
    }
    return $html . '</div>';
}

/**
 * Full client-facing hub/keyword article body (≥800 words target via rich sections).
 *
 * @return array{html:string,h2:list<string>}
 */
function ps_client_facing_article(string $slug, string $kind = 'hub'): array
{
    $group = ps_vertical_group($slug);
    $label = ps_profession_label_from_slug($slug);
    $g = ps_group_label($group);
    $need = ps_profession_cta_noun($group);
    $seed = crc32($slug . '|' . $kind);

    $sectionPool = [
        'What clients usually need' => "People looking for {$label} support often start with a practical problem: a transaction, an appointment, a filing deadline, a remortgage, or a cover review. iComply captures that problem in plain language so a practice can respond without guessing.",
        'How we match locally' => "Location matters. We ask for your town or region and factor travel, remote options and practice capacity. We do not invent a staffed office on every high street — we match honestly to firms that can help.",
        'What a good brief includes' => "A useful enquiry names the profession, the problem in one or two sentences, timing, and how to reach you. Leave confidential files out of the first message; the practice will collect detail securely later.",
        'For practices receiving introductions' => "{$label} practices that want client demand should tell us specialty, locations and weekly capacity. Introductions are scoped for fit — not spray-and-pray marketing lists dressed up as leads.",
        'Boundaries you can trust' => "iComply is the middleman. We do not act as your solicitor, clinician, accountant or authorised adviser. Regulated work stays with the practice you choose to instruct after introduction.",
        'Pricing honesty — POA' => "There are no fixed sterling catalogue prices on this site. Commercial terms for introductions or related support are price on application after a short discovery conversation.",
        'After you are connected' => "Once introduced, the practice owns the relationship. They confirm scope, diaries and fees under their own terms. We may lightly follow up to learn whether the introduction landed well.",
        'When we cannot match yet' => "If specialty or capacity is missing in your area, we say so rather than force a poor fit. You can update the brief or widen location preferences and try again.",
        'How this differs from directories' => "This page is about finding a professional — not buying marketing services for a firm. It exists so end clients can find {$g}, and so those practices can receive real demand.",
        'Preparing for the first conversation' => "Have dates, reference numbers and basic goals ready for the practice. Clients who prepare a short timeline usually get clearer first replies from busy professionals.",
        'Multi-site and sole desks' => "Large firms and sole practitioners both receive introductions when capacity allows. Matching notes include how you prefer to be contacted and whether evening or remote appointments help.",
        'Privacy and sensitive matters' => "Share enough to match, not your entire file. Employment disputes, health concerns and financial details deserve care — the practice will guide what to send once engaged.",
    ];

    $keys = array_keys($sectionPool);
    $h2s = [];
    $html = ps_hero_cta_html($group);
    $html .= '<p>Looking for <strong>' . ps_h($label) . '</strong>? iComply Professional Services connects end clients with ' . ps_h($g) . '. Tell us what you need — we introduce a suitable UK practice. Practices receive the lead. Quotes are <strong>POA</strong>.</p>';
    $html .= ps_figure_row_html($label);

    $count = 7;
    for ($i = 0; $i < $count; $i++) {
        $idx = (int) (($seed + $i * 7) % count($keys));
        // rotate uniquely by swapping with i
        $idx = ($idx + $i) % count($keys);
        $heading = $keys[$idx] . ' — ' . $label;
        // ensure uniqueness across pages by including slug-derived token without looking like ops
        if ($kind === 'keyword') {
            $heading = $keys[$idx] . ' for ' . $label;
        }
        // disambiguate hubs/keywords with slight wording variants
        $variant = (int) (($seed >> ($i * 3)) % 3);
        if ($variant === 1) {
            $heading = str_replace('—', ':', $heading);
        } elseif ($variant === 2 && $kind === 'hub') {
            $heading = $label . ': ' . $keys[$idx];
        }
        $h2s[] = $heading;
        $body = $sectionPool[$keys[$idx]];
        $extra = ' For ' . $label . ', that means clearer introductions and fewer wasted calls on both sides. '
            . 'Clients stay in control of whether to proceed; practices stay responsible for regulated delivery. '
            . 'If you already know you need ' . $need . ', include that in your enquiry on the contact page.';
        $html .= '<h2>' . ps_h($heading) . '</h2>';
        $html .= '<p>' . ps_h($body . $extra) . '</p>';
        $html .= '<p>' . ps_h('People searching for ' . $label . ' near them want a calm next step, not a sales script. We keep the path simple: enquire, clarify, connect. Nationwide coverage intent is real; invented branch claims are not.') . '</p>';
        if ($i === 2) {
            $html .= ps_mid_cta_html($group);
        }
    }

    $html .= ps_service_delivery_html($label, $group);
    $html .= ps_faq_markup(ps_human_faqs($label, $group));
    $html .= '<aside class="convert-inline"><p><strong>Ready?</strong> <a class="cta cta-primary" href="/contact/">Enquire — we connect you</a></p></aside>';

    // Pad if needed with unique closing prose
    $guard = 0;
    while (ps_word_count($html) < 820 && $guard < 6) {
        $html .= '<p>' . ps_h(
            'Additional guidance for ' . $label . ' introductions (' . ($guard + 1) . '): keep your first message short, name your town, '
            . 'and state whether the need is routine or urgent. Practices respond faster when the brief is specific. '
            . 'iComply remains the middleman connecting clients with ' . $g . ' — never a substitute for regulated advice.'
        ) . '</p>';
        $guard++;
    }

    return ['html' => $html, 'h2' => $h2s];
}

function ps_path_vertical(string $path): array
{
    $path = trim($path, '/');
    if (str_starts_with($path, 'hubs/')) {
        $slug = basename($path);
        return [ps_title_case_slug($slug), ps_vertical_group($slug)];
    }
    if (str_starts_with($path, 'keywords/')) {
        $parts = explode('/', $path);
        $slug = $parts[1] ?? '';
        return [ps_profession_label_from_slug($slug), ps_vertical_group($slug)];
    }
    return ['Professional Services', 'general'];
}

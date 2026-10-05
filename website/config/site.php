<?php
declare(strict_types=1);

/**
 * Non-secret site configuration.
 * NETLIFY_AUTH_TOKEN is never stored here — GitHub Actions reads it from repo secrets.
 * Ops locks on the agent box stay authoritative; this repo is the site build, not the lock store.
 */
return [
    'brand' => 'iComply Professional Services',
    'site_url' => 'https://icomplyprofessionalservices.co.uk',
    'preview_url' => 'https://icomply-professional-services.netlify.app',
    'preview_site_name' => 'icomply-professional-services',
    'netlify_site_id' => 'dc86da59-3989-4b57-bac1-d214b9ca2072',
    'navy' => '#0B1F3A',
    'orange' => '#FF6B00',
    'xplace_town_limit' => 50,
    'preview_only' => true,
    'min_body_words' => 800,
    'min_images' => 3,
];

<?php
declare(strict_types=1);

// HTML shell lives in website/includes/layout.php (ps_render_document).
// This file marks the templates path protected by netlify.toml.
require dirname(__DIR__) . '/includes/layout.php';

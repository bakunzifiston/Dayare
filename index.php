<?php

/**
 * Fallback when domain document root points to project root instead of public/.
 * Forwards the request to Laravel's public/index.php.
 * Safe to leave in place; remove if you set document root to /public.
 */

require __DIR__.'/public/index.php';

<?php
/**
 * Front assets (Phase 0 stub).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        // Phase 2+: scoped CSS/JS for booking forms.
    }
}

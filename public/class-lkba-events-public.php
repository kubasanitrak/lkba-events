<?php
/**
 * Front assets.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Public {

    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
    }

    public function enqueue_assets() {
        wp_register_style(
            'lkba-events',
            LKBA_EVENTS_PLUGIN_URL . 'public/css/lkba-events.css',
            array(),
            LKBA_EVENTS_VERSION
        );
        wp_enqueue_style('lkba-events');
    }
}

<?php
/**
 * Plugin loader.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Loader {

    public function __construct() {
        $this->load_dependencies();
    }

    private function load_dependencies() {
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-activator.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-db.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-settings.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-post-types.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-event.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-bookings.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-session.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-emails.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-payments.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-checkout.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-migrate.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-shortcodes.php';

        if (is_admin()) {
            require_once LKBA_EVENTS_PLUGIN_DIR . 'admin/class-lkba-events-admin.php';
        }

        require_once LKBA_EVENTS_PLUGIN_DIR . 'public/class-lkba-events-public.php';
    }

    public function run() {
        LKBA_Events_Settings::ensure_defaults();
        LKBA_Events_DB::maybe_upgrade();
        LKBA_Events_Bookings::expire_stale();

        new LKBA_Events_Post_Types();
        new LKBA_Events_Settings();
        LKBA_Events_Payments::boot();
        new LKBA_Events_Checkout();
        new LKBA_Events_Shortcodes();
        new LKBA_Events_Public();

        if (is_admin()) {
            new LKBA_Events_Admin();
        }
    }
}

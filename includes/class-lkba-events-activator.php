<?php
/**
 * Plugin activation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Activator {

    public static function activate() {
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-settings.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-post-types.php';
        require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-db.php';

        $post_types = new LKBA_Events_Post_Types();
        $post_types->register_post_types();
        $post_types->register_taxonomies();

        LKBA_Events_Settings::ensure_defaults();
        LKBA_Events_DB::create_tables();

        flush_rewrite_rules();

        update_option('lkba_events_db_version', LKBA_EVENTS_VERSION);
        update_option('lkba_events_plugin_activated', true);
    }
}

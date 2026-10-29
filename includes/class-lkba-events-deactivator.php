<?php
/**
 * Plugin deactivation.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Deactivator {

    public static function deactivate() {
        flush_rewrite_rules();
    }
}

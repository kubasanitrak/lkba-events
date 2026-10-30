<?php
/**
 * Plugin Name: LKBA Events
 * Plugin URI: https://github.com/kubasanitrak/lkba-events
 * Description: Teacher trainings and event bookings for Lenka Krejčová Barre Academy. Requires LKBA Payments.
 * Version: 0.3.5
 * Author: kubasanitrak
 * Author URI: https://github.com/kubasanitrak
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: lkba-events
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Requires Plugins: lkba-payments
 */

if (!defined('ABSPATH')) {
    exit;
}

define('LKBA_EVENTS_VERSION', '0.3.5');
define('LKBA_EVENTS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('LKBA_EVENTS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('LKBA_EVENTS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/** Global default: close bookings this many hours before start (decision §13). */
if (!defined('LKBA_EVENTS_BOOKING_CLOSE_HOURS')) {
    define('LKBA_EVENTS_BOOKING_CLOSE_HOURS', 12);
}

$lkba_events_autoload = LKBA_EVENTS_PLUGIN_DIR . 'vendor/autoload.php';
if (is_readable($lkba_events_autoload)) {
    require_once $lkba_events_autoload;
}

require_once LKBA_EVENTS_PLUGIN_DIR . 'lib/plugin-update-checker/plugin-update-checker.php';

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$lkba_events_update_checker = PucFactory::buildUpdateChecker(
    'https://github.com/kubasanitrak/lkba-events/',
    __FILE__,
    'lkba-events'
);
$lkba_events_update_checker->getVcsApi()->enableReleaseAssets('/lkba-events\.zip($|[?&#])/i');

function lkba_events_activate() {
    require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-dependency.php';
    if (!LKBA_Events_Dependency::payments_available()) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        deactivate_plugins(plugin_basename(__FILE__), true);
        wp_die(
            esc_html__('LKBA Events requires the LKBA Payments plugin to be installed and active.', 'lkba-events'),
            esc_html__('Plugin dependency missing', 'lkba-events'),
            array('back_link' => true)
        );
    }

    require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-activator.php';
    LKBA_Events_Activator::activate();
}
register_activation_hook(__FILE__, 'lkba_events_activate');

function lkba_events_deactivate() {
    require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-deactivator.php';
    LKBA_Events_Deactivator::deactivate();
}
register_deactivation_hook(__FILE__, 'lkba_events_deactivate');

require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-dependency.php';
require_once LKBA_EVENTS_PLUGIN_DIR . 'includes/class-lkba-events-loader.php';

function lkba_events_init() {
    if (!LKBA_Events_Dependency::payments_available()) {
        add_action('admin_notices', array('LKBA_Events_Dependency', 'admin_notice'));
        return;
    }

    $loader = new LKBA_Events_Loader();
    $loader->run();
}
add_action('plugins_loaded', 'lkba_events_init', 20);

function lkba_events_load_textdomain() {
    load_plugin_textdomain(
        'lkba-events',
        false,
        dirname(LKBA_EVENTS_PLUGIN_BASENAME) . '/languages'
    );
}
add_action('init', 'lkba_events_load_textdomain');

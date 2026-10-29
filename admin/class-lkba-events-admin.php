<?php
/**
 * Admin menu and settings stub.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Admin {

    const MENU_SLUG = 'lkba-events';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 5);
        add_filter('register_post_type_args', array($this, 'attach_cpt_to_menu'), 10, 2);
        add_action('admin_init', array($this, 'handle_save'));
    }

    public function attach_cpt_to_menu($args, $post_type) {
        if ($post_type === LKBA_Events_Post_Types::POST_TYPE_EVENT) {
            $args['show_in_menu'] = self::MENU_SLUG;
        }
        return $args;
    }

    public function register_menu() {
        add_menu_page(
            __('LKBA Události', 'lkba-events'),
            __('LKBA Události', 'lkba-events'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard'),
            'dashicons-calendar-alt',
            26
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Přehled', 'lkba-events'),
            __('Přehled', 'lkba-events'),
            'edit_posts',
            self::MENU_SLUG,
            array($this, 'render_dashboard')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Události', 'lkba-events'),
            __('Události', 'lkba-events'),
            'edit_posts',
            'edit.php?post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Kategorie', 'lkba-events'),
            __('Kategorie', 'lkba-events'),
            'manage_categories',
            'edit-tags.php?taxonomy=' . LKBA_Events_Post_Types::TAX_CATEGORY . '&post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Místa', 'lkba-events'),
            __('Místa', 'lkba-events'),
            'manage_categories',
            'edit-tags.php?taxonomy=' . LKBA_Events_Post_Types::TAX_PLACE . '&post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Rezervace', 'lkba-events'),
            __('Rezervace', 'lkba-events'),
            'edit_posts',
            'lkba-events-bookings',
            array($this, 'render_bookings_stub')
        );

        add_submenu_page(
            self::MENU_SLUG,
            __('Nastavení', 'lkba-events'),
            __('Nastavení', 'lkba-events'),
            'manage_options',
            'lkba-events-settings',
            array($this, 'render_settings')
        );
    }

    public function render_dashboard() {
        echo '<div class="wrap"><h1>' . esc_html__('LKBA Události', 'lkba-events') . '</h1>';
        echo '<p>' . esc_html__('Phase 0 scaffold — stats and booking tools arrive in later phases.', 'lkba-events') . '</p></div>';
    }

    public function render_bookings_stub() {
        echo '<div class="wrap"><h1>' . esc_html__('Rezervace', 'lkba-events') . '</h1>';
        echo '<p>' . esc_html__('Bookings list stub. Table prefix: lkba_bookings.', 'lkba-events') . '</p></div>';
    }

    public function handle_save() {
        if (!isset($_POST['lkba_events_settings_nonce'])) {
            return;
        }
        if (!current_user_can('manage_options')) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_events_settings_nonce'])), 'lkba_events_save_settings')) {
            return;
        }

        $hours = isset($_POST['lkba_booking_close_hours']) ? (int) $_POST['lkba_booking_close_hours'] : LKBA_EVENTS_BOOKING_CLOSE_HOURS;
        if ($hours < 1) {
            $hours = (int) LKBA_EVENTS_BOOKING_CLOSE_HOURS;
        }

        update_option(LKBA_Events_Settings::OPTION_BOOKING_CLOSE_HOURS, $hours);
        update_option(
            LKBA_Events_Settings::OPTION_SUPPORT_EMAIL,
            isset($_POST['lkba_support_email']) ? sanitize_email(wp_unslash($_POST['lkba_support_email'])) : ''
        );
        update_option(
            LKBA_Events_Settings::OPTION_SENDER_NAME,
            isset($_POST['lkba_sender_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_sender_name'])) : ''
        );
        update_option(
            LKBA_Events_Settings::OPTION_SENDER_EMAIL,
            isset($_POST['lkba_sender_email']) ? sanitize_email(wp_unslash($_POST['lkba_sender_email'])) : ''
        );
        update_option(
            LKBA_Events_Settings::OPTION_TERMS_PAGE,
            isset($_POST['lkba_terms_page']) ? absint($_POST['lkba_terms_page']) : 0
        );
        update_option(
            LKBA_Events_Settings::OPTION_PRIVACY_PAGE,
            isset($_POST['lkba_privacy_page']) ? absint($_POST['lkba_privacy_page']) : 0
        );
        update_option(
            LKBA_Events_Settings::OPTION_STORNO_PAGE,
            isset($_POST['lkba_storno_page']) ? absint($_POST['lkba_storno_page']) : 0
        );

        add_settings_error('lkba_events', 'saved', __('Nastavení uloženo.', 'lkba-events'), 'updated');
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        include LKBA_EVENTS_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
}

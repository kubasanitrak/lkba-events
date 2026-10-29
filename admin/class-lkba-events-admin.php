<?php
/**
 * Admin menu, event meta boxes, bookings list, settings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Admin {

    const MENU_SLUG = 'lkba-events';

    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 5);
        add_filter('register_post_type_args', array($this, 'attach_cpt_to_menu'), 10, 2);
        add_action('admin_init', array($this, 'handle_save_settings'));
        add_action('admin_init', array($this, 'handle_attendee_email'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post_' . LKBA_Events_Post_Types::POST_TYPE_EVENT, array($this, 'save_event_meta'), 10, 2);
        add_action(LKBA_Events_Post_Types::TAX_CATEGORY . '_add_form_fields', array($this, 'category_add_field'));
        add_action(LKBA_Events_Post_Types::TAX_CATEGORY . '_edit_form_fields', array($this, 'category_edit_field'));
        add_action('created_' . LKBA_Events_Post_Types::TAX_CATEGORY, array($this, 'save_category_meta'));
        add_action('edited_' . LKBA_Events_Post_Types::TAX_CATEGORY, array($this, 'save_category_meta'));
        add_action('admin_init', array($this, 'handle_booking_actions'));
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

        add_submenu_page(self::MENU_SLUG, __('Přehled', 'lkba-events'), __('Přehled', 'lkba-events'), 'edit_posts', self::MENU_SLUG, array($this, 'render_dashboard'));
        add_submenu_page(self::MENU_SLUG, __('Události', 'lkba-events'), __('Události', 'lkba-events'), 'edit_posts', 'edit.php?post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT);
        add_submenu_page(self::MENU_SLUG, __('Kategorie', 'lkba-events'), __('Kategorie', 'lkba-events'), 'manage_categories', 'edit-tags.php?taxonomy=' . LKBA_Events_Post_Types::TAX_CATEGORY . '&post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT);
        add_submenu_page(self::MENU_SLUG, __('Místa', 'lkba-events'), __('Místa', 'lkba-events'), 'manage_categories', 'edit-tags.php?taxonomy=' . LKBA_Events_Post_Types::TAX_PLACE . '&post_type=' . LKBA_Events_Post_Types::POST_TYPE_EVENT);
        add_submenu_page(self::MENU_SLUG, __('Rezervace', 'lkba-events'), __('Rezervace', 'lkba-events'), 'edit_posts', 'lkba-events-bookings', array($this, 'render_bookings'));
        add_submenu_page(self::MENU_SLUG, __('Nastavení', 'lkba-events'), __('Nastavení', 'lkba-events'), 'manage_options', 'lkba-events-settings', array($this, 'render_settings'));
    }

    public function render_dashboard() {
        $open = 0;
        foreach (LKBA_Events_Event::get_open_and_past()['open'] as $p) {
            if (LKBA_Events_Event::get_booking_status($p->ID) === 'open') {
                $open++;
            }
        }
        $bookings = LKBA_Events_Bookings::query(array('limit' => 5));
        echo '<div class="wrap"><h1>' . esc_html__('LKBA Události', 'lkba-events') . '</h1>';
        echo '<p>' . esc_html(sprintf(__('Otevřené termíny: %d', 'lkba-events'), $open)) . '</p>';
        echo '<p><a class="button button-primary" href="' . esc_url(admin_url('admin.php?page=lkba-events-bookings')) . '">' . esc_html__('Rezervace', 'lkba-events') . '</a></p>';
        if ($bookings) {
            echo '<h2>' . esc_html__('Poslední rezervace', 'lkba-events') . '</h2><ul>';
            foreach ($bookings as $b) {
                echo '<li>' . esc_html($b->booking_number . ' — ' . $b->status . '/' . $b->payment_status) . '</li>';
            }
            echo '</ul>';
        }
        echo '</div>';
    }

    public function add_meta_boxes() {
        add_meta_box(
            'lkba_event_details',
            __('Detaily události', 'lkba-events'),
            array($this, 'render_event_metabox'),
            LKBA_Events_Post_Types::POST_TYPE_EVENT,
            'normal',
            'high'
        );
    }

    public function render_event_metabox($post) {
        wp_nonce_field('lkba_event_meta', 'lkba_event_meta_nonce');
        $meta = LKBA_Events_Event::get_meta($post->ID);
        $companies = class_exists('LKBA_Payments_Companies') ? LKBA_Payments_Companies::get_choices() : array();
        $status = LKBA_Events_Event::get_booking_status($post->ID);
        include LKBA_EVENTS_PLUGIN_DIR . 'admin/partials/event-metabox.php';
    }

    public function save_event_meta($post_id, $post) {
        if (!isset($_POST['lkba_event_meta_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_event_meta_nonce'])), 'lkba_event_meta')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $start = isset($_POST['lkba_datetime_start']) ? sanitize_text_field(wp_unslash($_POST['lkba_datetime_start'])) : '';
        $end   = isset($_POST['lkba_datetime_end']) ? sanitize_text_field(wp_unslash($_POST['lkba_datetime_end'])) : '';
        // Convert datetime-local to MySQL-ish.
        $start = $start !== '' ? str_replace('T', ' ', $start) . (strlen($start) === 16 ? ':00' : '') : '';
        $end   = $end !== '' ? str_replace('T', ' ', $end) . (strlen($end) === 16 ? ':00' : '') : '';

        LKBA_Events_Event::save_meta($post_id, array(
            'datetime_start'  => $start,
            'datetime_end'    => $end,
            'place_name'      => isset($_POST['lkba_place_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_place_name'])) : '',
            'place_address'   => isset($_POST['lkba_place_address']) ? sanitize_text_field(wp_unslash($_POST['lkba_place_address'])) : '',
            'instructor'      => isset($_POST['lkba_instructor']) ? sanitize_text_field(wp_unslash($_POST['lkba_instructor'])) : '',
            'capacity'        => isset($_POST['lkba_capacity']) ? absint($_POST['lkba_capacity']) : 0,
            'price'           => isset($_POST['lkba_price']) ? wp_unslash($_POST['lkba_price']) : '',
            'company_id'      => isset($_POST['lkba_company_id']) ? sanitize_text_field(wp_unslash($_POST['lkba_company_id'])) : 'company-1',
            'payment_methods' => isset($_POST['lkba_payment_methods']) ? sanitize_key(wp_unslash($_POST['lkba_payment_methods'])) : 'bank_transfer',
            'booking_open'    => !empty($_POST['lkba_booking_open']) ? 1 : 0,
        ));
    }

    public function category_add_field() {
        echo '<div class="form-field"><label for="lkba_default_price">' . esc_html__('Výchozí cena (Kč, vč. DPH)', 'lkba-events') . '</label>';
        echo '<input type="number" step="0.01" name="lkba_default_price" id="lkba_default_price" value=""></div>';
    }

    public function category_edit_field($term) {
        $price = get_term_meta($term->term_id, LKBA_Events_Event::TERM_META_PRICE, true);
        echo '<tr class="form-field"><th><label for="lkba_default_price">' . esc_html__('Výchozí cena (Kč)', 'lkba-events') . '</label></th>';
        echo '<td><input type="number" step="0.01" name="lkba_default_price" id="lkba_default_price" value="' . esc_attr((string) $price) . '"></td></tr>';
    }

    public function save_category_meta($term_id) {
        if (isset($_POST['lkba_default_price'])) {
            $price = wp_unslash($_POST['lkba_default_price']);
            update_term_meta($term_id, LKBA_Events_Event::TERM_META_PRICE, $price === '' ? '' : (string) (float) $price);
        }
    }

    public function handle_booking_actions() {
        if (!isset($_GET['lkba_booking_action'], $_GET['booking_id'], $_GET['_wpnonce'])) {
            return;
        }
        if (!current_user_can('edit_posts')) {
            return;
        }
        $id = absint($_GET['booking_id']);
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['_wpnonce'])), 'lkba_booking_' . $id)) {
            return;
        }
        $action = sanitize_key(wp_unslash($_GET['lkba_booking_action']));
        if ($action === 'confirm') {
            LKBA_Events_Bookings::update($id, array(
                'status'         => 'confirmed',
                'payment_status' => 'paid',
                'paid_at'        => current_time('mysql'),
            ));
            $b = LKBA_Events_Bookings::get($id);
            if ($b) {
                LKBA_Events_Emails::send_paid($b);
            }
        } elseif ($action === 'cancel') {
            LKBA_Events_Bookings::update($id, array(
                'status'         => 'cancelled',
                'payment_status' => 'failed',
            ));
        }
        wp_safe_redirect(admin_url('admin.php?page=lkba-events-bookings'));
        exit;
    }

    public function render_bookings() {
        if (!current_user_can('edit_posts')) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended — read-only filters
        $status            = isset($_GET['status']) ? sanitize_key(wp_unslash($_GET['status'])) : '';
        $filter_event      = isset($_GET['event_id']) ? absint($_GET['event_id']) : 0;
        $filter_instructor = isset($_GET['instructor']) ? sanitize_text_field(wp_unslash($_GET['instructor'])) : '';
        $filter_place      = isset($_GET['place']) ? sanitize_text_field(wp_unslash($_GET['place'])) : '';

        $args = array('limit' => 500);
        if ($status !== '') {
            $args['status'] = $status;
        }
        if ($filter_event > 0) {
            $args['event_id'] = $filter_event;
        }
        $bookings = LKBA_Events_Bookings::query($args);
        $choices  = LKBA_Events_Bookings::meta_filter_choices();
        $stats    = LKBA_Events_Bookings::stats(array(
            'instructor' => $filter_instructor,
            'place'      => $filter_place,
            'status'     => $status,
        ));
        $email_result = get_transient('lkba_events_attendee_email_result');
        if ($email_result) {
            delete_transient('lkba_events_attendee_email_result');
        }
        include LKBA_EVENTS_PLUGIN_DIR . 'admin/partials/bookings-page.php';
    }

    public function handle_attendee_email() {
        if (!isset($_POST['lkba_attendee_email_nonce'])) {
            return;
        }
        if (!current_user_can('edit_posts')) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_attendee_email_nonce'])), 'lkba_events_attendee_email')) {
            return;
        }

        $event_id = isset($_POST['lkba_email_event_id']) ? absint($_POST['lkba_email_event_id']) : 0;
        $subject  = isset($_POST['lkba_email_subject']) ? sanitize_text_field(wp_unslash($_POST['lkba_email_subject'])) : '';
        $body     = isset($_POST['lkba_email_body']) ? wp_kses_post(wp_unslash($_POST['lkba_email_body'])) : '';
        $status_f = isset($_POST['lkba_email_status']) ? sanitize_key(wp_unslash($_POST['lkba_email_status'])) : '';

        if (!$event_id || $subject === '' || $body === '') {
            set_transient('lkba_events_attendee_email_result', array('sent' => 0, 'skipped' => 0, 'errors' => 1), 60);
            wp_safe_redirect(admin_url('admin.php?page=lkba-events-bookings'));
            exit;
        }

        $result = LKBA_Events_Emails::send_attendees($event_id, $subject, $body, $status_f);
        set_transient('lkba_events_attendee_email_result', $result, 60);
        wp_safe_redirect(admin_url('admin.php?page=lkba-events-bookings&event_id=' . $event_id));
        exit;
    }

    public function handle_save_settings() {
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
            isset($_POST['lkba_support_email']) ? sanitize_email(wp_unslash($_POST['lkba_support_email'])) : LKBA_Events_Settings::DEFAULT_SUPPORT_EMAIL
        );
        update_option(
            LKBA_Events_Settings::OPTION_SENDER_NAME,
            isset($_POST['lkba_sender_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_sender_name'])) : ''
        );
        update_option(
            LKBA_Events_Settings::OPTION_SENDER_EMAIL,
            isset($_POST['lkba_sender_email']) ? sanitize_email(wp_unslash($_POST['lkba_sender_email'])) : LKBA_Events_Settings::DEFAULT_SUPPORT_EMAIL
        );
        update_option(LKBA_Events_Settings::OPTION_TERMS_PAGE, isset($_POST['lkba_terms_page']) ? absint($_POST['lkba_terms_page']) : 0);
        update_option(LKBA_Events_Settings::OPTION_PRIVACY_PAGE, isset($_POST['lkba_privacy_page']) ? absint($_POST['lkba_privacy_page']) : 0);
        update_option(LKBA_Events_Settings::OPTION_STORNO_PAGE, isset($_POST['lkba_storno_page']) ? absint($_POST['lkba_storno_page']) : 0);
        update_option(LKBA_Events_Settings::OPTION_SUCCESS_PAGE, isset($_POST['lkba_success_page']) ? absint($_POST['lkba_success_page']) : 0);
        update_option(LKBA_Events_Settings::OPTION_FAILED_PAGE, isset($_POST['lkba_failed_page']) ? absint($_POST['lkba_failed_page']) : 0);

        add_settings_error('lkba_events', 'saved', __('Nastavení uloženo.', 'lkba-events'), 'updated');
    }

    public function render_settings() {
        if (!current_user_can('manage_options')) {
            return;
        }
        include LKBA_EVENTS_PLUGIN_DIR . 'admin/partials/settings-page.php';
    }
}

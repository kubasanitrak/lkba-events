<?php
/**
 * Front shortcodes — list, book, payment return, my bookings.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Shortcodes {

    public function __construct() {
        add_shortcode('lkba_event_categories', array($this, 'render_categories'));
        add_shortcode('lkba_events_list', array($this, 'render_list'));
        add_shortcode('lkba_event_book', array($this, 'render_book'));
        add_shortcode('lkba_my_bookings', array($this, 'render_my_bookings'));
        add_shortcode('lkba_payment_success', array($this, 'render_payment_success'));
        add_shortcode('lkba_payment_failed', array($this, 'render_payment_failed'));
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_categories($atts = array()) {
        $terms = get_terms(array(
            'taxonomy'   => LKBA_Events_Post_Types::TAX_CATEGORY,
            'hide_empty' => false,
        ));
        if (is_wp_error($terms) || !$terms) {
            return '<div class="lkba-events"><p>' . esc_html__('Zatím žádné kategorie.', 'lkba-events') . '</p></div>';
        }

        ob_start();
        echo '<div class="lkba-events lkba-event-categories">';
        echo '<ul class="lkba-event-categories__list">';
        foreach ($terms as $term) {
            $url = get_term_link($term);
            echo '<li><a href="' . esc_url(is_wp_error($url) ? '#' : $url) . '">' . esc_html($term->name) . '</a></li>';
        }
        echo '</ul></div>';
        return (string) ob_get_clean();
    }

    /**
     * Resolve book_page shortcode attr for subdirectory installs.
     *
     * Accepts: page ID, slug, path relative to home (leading slash OK), or full URL.
     * Leading "/" is NOT treated as domain-root — it is relative to home_url().
     *
     * @param string $raw
     * @return string Absolute URL or empty.
     */
    public static function resolve_book_page_url($raw) {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }

        if (ctype_digit($raw)) {
            $link = get_permalink((int) $raw);
            return $link ? esc_url($link) : '';
        }

        if (preg_match('#^https?://#i', $raw) || strpos($raw, '//') === 0) {
            return esc_url($raw);
        }

        $path = trim($raw);
        // Strip scheme-less host leftovers; keep path only.
        $path = preg_replace('#^https?:#i', '', $path);
        $path = ltrim($path, '/');
        if ($path === '') {
            return esc_url(home_url('/'));
        }

        $page = get_page_by_path($path);
        if ($page instanceof WP_Post) {
            $link = get_permalink($page);
            return $link ? esc_url($link) : '';
        }

        return esc_url(home_url('/' . $path));
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_list($atts = array()) {
        $atts = shortcode_atts(array(
            'book_page' => '',
            'category'  => '',
            'cat'       => '',
        ), $atts, 'lkba_events_list');

        $query_args = array();
        $cat_slug   = $atts['category'] !== '' ? $atts['category'] : $atts['cat'];
        if ($cat_slug !== '') {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => LKBA_Events_Post_Types::TAX_CATEGORY,
                    'field'    => 'slug',
                    'terms'    => sanitize_title($cat_slug),
                ),
            );
        }

        $split = LKBA_Events_Event::get_open_and_past($query_args);
        $book_page = self::resolve_book_page_url($atts['book_page']);

        $privacy = (int) get_option(LKBA_Events_Settings::OPTION_PRIVACY_PAGE, 0);
        $terms   = (int) get_option(LKBA_Events_Settings::OPTION_TERMS_PAGE, 0);
        $storno  = (int) get_option(LKBA_Events_Settings::OPTION_STORNO_PAGE, 0);

        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/events-list.php';
        return (string) ob_get_clean();
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_book($atts = array()) {
        $atts = shortcode_atts(array(
            'id'       => 0,
            'category' => '',
            'cat'      => '',
        ), $atts, 'lkba_event_book');

        $event_id = absint($atts['id']);
        if (!$event_id && isset($_GET['event_id'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $event_id = absint($_GET['event_id']);
        }

        $flash = isset($_GET['lkba_book']) ? sanitize_key(wp_unslash($_GET['lkba_book'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $recap_token = isset($_GET['recap']) ? sanitize_text_field(wp_unslash($_GET['recap'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        if ($flash === 'ok' && $recap_token !== '') {
            $recap = get_transient('lkba_book_recap_' . $recap_token);
            if (is_array($recap) && !empty($recap['booking_id'])) {
                $booking = LKBA_Events_Bookings::get((int) $recap['booking_id']);
                ob_start();
                include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/booking-recap.php';
                return (string) ob_get_clean();
            }
        }

        $draft = LKBA_Events_Session::get_draft();
        if (!$event_id && !empty($draft['event_id'])) {
            $event_id = (int) $draft['event_id'];
        }

        $error_msg = '';
        if ($flash === 'error') {
            $code = isset($_GET['msg']) ? sanitize_key(wp_unslash($_GET['msg'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $error_msg = self::error_message($code);
        }

        // Optional category filter when no concrete event is selected (shortcode or ?category= / ?cat=).
        $cat_slug = $atts['category'] !== '' ? $atts['category'] : $atts['cat'];
        if ($cat_slug === '') {
            if (isset($_GET['category'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $cat_slug = sanitize_title(wp_unslash($_GET['category'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            } elseif (isset($_GET['cat'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $cat_slug = sanitize_title(wp_unslash($_GET['cat'])); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            }
        } else {
            $cat_slug = sanitize_title($cat_slug);
        }

        $query_args = array();
        if ($cat_slug !== '' && !$event_id) {
            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => LKBA_Events_Post_Types::TAX_CATEGORY,
                    'field'    => 'slug',
                    'terms'    => $cat_slug,
                ),
            );
        }

        $events_open = array();
        foreach (LKBA_Events_Event::get_open_and_past($query_args)['open'] as $post) {
            if (LKBA_Events_Event::get_booking_status($post->ID) === 'open') {
                $events_open[] = $post;
            }
        }

        $support = LKBA_Events_Settings::get_support_email();
        $privacy = (int) get_option(LKBA_Events_Settings::OPTION_PRIVACY_PAGE, 0);
        $terms_p = (int) get_option(LKBA_Events_Settings::OPTION_TERMS_PAGE, 0);
        $storno  = (int) get_option(LKBA_Events_Settings::OPTION_STORNO_PAGE, 0);

        $payment_methods = $event_id ? LKBA_Events_Event::get_allowed_payment_methods($event_id) : array('bank_transfer');
        $draft_method    = isset($draft['payment_method']) ? sanitize_key($draft['payment_method']) : '';

        // Methods map for JS when switching event in the select.
        $methods_by_event = array();
        foreach ($events_open as $post) {
            $methods_by_event[(string) $post->ID] = LKBA_Events_Event::get_allowed_payment_methods($post->ID);
        }

        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/booking-form.php';
        return (string) ob_get_clean();
    }

    /**
     * Comgate success / pending return page.
     *
     * @param array|string $atts
     * @return string
     */
    public function render_payment_success($atts = array()) {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($ref === '' && isset($_GET['refId'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $ref = sanitize_text_field(wp_unslash($_GET['refId']));
        }

        $booking = $ref !== '' ? LKBA_Events_Bookings::get_by_number($ref) : null;
        $verified = false;

        if ($booking && $booking->payment_status !== 'paid' && class_exists('LKBA_Payments_Comgate')) {
            $context = LKBA_Events_Bookings::to_payment_context($booking);
            $verified = LKBA_Payments_Comgate::for_company((string) $booking->company_id)
                ->complete_from_return($context, (string) $booking->transaction_id);
            if ($verified) {
                $booking = LKBA_Events_Bookings::get((int) $booking->id);
            }
        }

        $support = LKBA_Events_Settings::get_support_email();
        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/payment-success.php';
        return (string) ob_get_clean();
    }

    /**
     * Comgate cancel / fail return page.
     *
     * @param array|string $atts
     * @return string
     */
    public function render_payment_failed($atts = array()) {
        $ref = isset($_GET['ref']) ? sanitize_text_field(wp_unslash($_GET['ref'])) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ($ref === '' && isset($_GET['refId'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $ref = sanitize_text_field(wp_unslash($_GET['refId']));
        }

        $booking = $ref !== '' ? LKBA_Events_Bookings::get_by_number($ref) : null;
        if ($booking && $booking->payment_status === 'awaiting' && class_exists('LKBA_Payments_Comgate')) {
            $context = LKBA_Events_Bookings::to_payment_context($booking);
            LKBA_Payments_Comgate::for_company((string) $booking->company_id)
                ->complete_from_return($context, (string) $booking->transaction_id);
            $booking = LKBA_Events_Bookings::get((int) $booking->id);
        }

        $support = LKBA_Events_Settings::get_support_email();
        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/payment-failed.php';
        return (string) ob_get_clean();
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_my_bookings($atts = array()) {
        $support = LKBA_Events_Settings::get_support_email();

        if (!is_user_logged_in()) {
            return '<div class="lkba-events lkba-my-bookings"><p>'
                . esc_html__('Pro přehled rezervací se přihlaste. Účet vznikne po zaplacení rezervace.', 'lkba-events')
                . '</p><p><a href="' . esc_url(wp_login_url(get_permalink())) . '">'
                . esc_html__('Přihlásit se', 'lkba-events') . '</a></p>'
                . '<p>' . esc_html__('Podpora:', 'lkba-events')
                . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a></p></div>';
        }

        $mine = LKBA_Events_Bookings::query(array(
            'user_id' => get_current_user_id(),
            'limit'   => 100,
        ));

        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/my-bookings.php';
        return (string) ob_get_clean();
    }

    /**
     * @param string $code
     * @return string
     */
    private static function error_message($code) {
        $map = array(
            'not_bookable'    => __('Událost není otevřená pro rezervaci (plná nebo uzavřená).', 'lkba-events'),
            'invalid_contact' => __('Vyplňte jméno a platný e-mail.', 'lkba-events'),
            'legal'           => __('Musíte souhlasit s podmínkami.', 'lkba-events'),
            'payments'        => __('Platební modul není dostupný.', 'lkba-events'),
            'full'            => __('Událost je plná.', 'lkba-events'),
            'fakturoid'       => __('Rezervace vznikla, ale proforma se nepodařila vytvořit. Napište na podporu.', 'lkba-events'),
            'comgate'         => __('Rezervace vznikla, ale přesměrování na platební bránu selhalo. Napište na podporu nebo zkuste bankovní převod.', 'lkba-events'),
        );
        return $map[$code] ?? __('Rezervaci se nepodařilo odeslat.', 'lkba-events');
    }
}

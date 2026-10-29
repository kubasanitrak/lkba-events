<?php
/**
 * Front shortcodes — list, book, my bookings stub.
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
     * @param array|string $atts
     * @return string
     */
    public function render_list($atts = array()) {
        $atts = shortcode_atts(array(
            'book_page' => '',
        ), $atts, 'lkba_events_list');

        $split = LKBA_Events_Event::get_open_and_past();
        $book_page = $atts['book_page'] !== '' ? esc_url($atts['book_page']) : '';

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
            'id' => 0,
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

        $events_open = array();
        foreach (LKBA_Events_Event::get_open_and_past()['open'] as $post) {
            if (LKBA_Events_Event::get_booking_status($post->ID) === 'open') {
                $events_open[] = $post;
            }
        }

        $support = LKBA_Events_Settings::get_support_email();
        $privacy = (int) get_option(LKBA_Events_Settings::OPTION_PRIVACY_PAGE, 0);
        $terms_p = (int) get_option(LKBA_Events_Settings::OPTION_TERMS_PAGE, 0);
        $storno  = (int) get_option(LKBA_Events_Settings::OPTION_STORNO_PAGE, 0);

        ob_start();
        include LKBA_EVENTS_PLUGIN_DIR . 'public/partials/booking-form.php';
        return (string) ob_get_clean();
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_my_bookings($atts = array()) {
        $support = LKBA_Events_Settings::get_support_email();

        if (!is_user_logged_in()) {
            return '<div class="lkba-events"><p>' . esc_html__('Pro přehled rezervací se přihlaste. Účet vznikne po zaplacení rezervace.', 'lkba-events') . '</p>'
                . '<p><a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a></p></div>';
        }

        $rows = LKBA_Events_Bookings::query(array(
            'limit' => 50,
        ));
        $uid = get_current_user_id();
        $mine = array_filter($rows, function ($b) use ($uid) {
            return (int) $b->user_id === $uid;
        });

        ob_start();
        echo '<div class="lkba-events lkba-my-bookings">';
        echo '<h2>' . esc_html__('Moje rezervace', 'lkba-events') . '</h2>';
        if (!$mine) {
            echo '<p>' . esc_html__('Zatím nemáte žádné rezervace.', 'lkba-events') . '</p>';
        } else {
            echo '<ul>';
            foreach ($mine as $b) {
                $event = get_post((int) $b->event_id);
                $title = $event ? $event->post_title : $b->booking_number;
                echo '<li><strong>' . esc_html($b->booking_number) . '</strong> — ' . esc_html($title)
                    . ' (' . esc_html($b->status) . ' / ' . esc_html($b->payment_status) . ')</li>';
            }
            echo '</ul>';
        }
        echo '<p>' . esc_html__('Zrušení nebo změna termínu:', 'lkba-events')
            . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a></p>';
        echo '</div>';
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
        );
        return $map[$code] ?? __('Rezervaci se nepodařilo odeslat.', 'lkba-events');
    }
}

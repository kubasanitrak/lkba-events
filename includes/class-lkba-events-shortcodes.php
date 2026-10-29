<?php
/**
 * Front shortcode stubs.
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
        return $this->stub_notice(
            __('Event categories shortcode — Phase 0 stub.', 'lkba-events')
        );
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_list($atts = array()) {
        return $this->stub_notice(
            __('Events list shortcode — Phase 0 stub.', 'lkba-events')
        );
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_book($atts = array()) {
        return $this->stub_notice(
            __('Event booking shortcode — Phase 0 stub (guest checkout later).', 'lkba-events')
        );
    }

    /**
     * @param array|string $atts
     * @return string
     */
    public function render_my_bookings($atts = array()) {
        return $this->stub_notice(
            __('My bookings shortcode — Phase 0 stub (account after paid).', 'lkba-events')
        );
    }

    /**
     * @param string $message
     * @return string
     */
    private function stub_notice($message) {
        if (!current_user_can('edit_posts')) {
            return '';
        }
        return '<div class="lkba-events-stub"><p>' . esc_html($message) . '</p></div>';
    }
}

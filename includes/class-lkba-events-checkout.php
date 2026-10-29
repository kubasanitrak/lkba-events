<?php
/**
 * Front booking submit → bank transfer (Phase 2).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Checkout {

    public function __construct() {
        add_action('init', array($this, 'maybe_handle_submit'), 20);
    }

    public function maybe_handle_submit() {
        if (empty($_POST['lkba_events_book_nonce'])) {
            return;
        }
        if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['lkba_events_book_nonce'])), 'lkba_events_book')) {
            return;
        }

        $event_id = isset($_POST['lkba_event_id']) ? absint($_POST['lkba_event_id']) : 0;
        $name     = isset($_POST['lkba_name']) ? sanitize_text_field(wp_unslash($_POST['lkba_name'])) : '';
        $email    = isset($_POST['lkba_email']) ? sanitize_email(wp_unslash($_POST['lkba_email'])) : '';
        $phone    = isset($_POST['lkba_phone']) ? sanitize_text_field(wp_unslash($_POST['lkba_phone'])) : '';
        $notes    = isset($_POST['lkba_notes']) ? sanitize_textarea_field(wp_unslash($_POST['lkba_notes'])) : '';
        $legal    = !empty($_POST['lkba_legal']);

        $invoice = array(
            'company_name' => isset($_POST['lkba_inv_company']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_company'])) : '',
            'street'       => isset($_POST['lkba_inv_street']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_street'])) : '',
            'city'         => isset($_POST['lkba_inv_city']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_city'])) : '',
            'zip'          => isset($_POST['lkba_inv_zip']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_zip'])) : '',
            'ic'           => isset($_POST['lkba_inv_ic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_ic'])) : '',
            'dic'          => isset($_POST['lkba_inv_dic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_dic'])) : '',
        );

        LKBA_Events_Session::save_draft(array(
            'event_id' => $event_id,
            'name'     => $name,
            'email'    => $email,
            'phone'    => $phone,
            'notes'    => $notes,
            'invoice'  => $invoice,
        ));

        $redirect_base = wp_get_referer() ? wp_get_referer() : home_url('/');

        if (!$event_id || !LKBA_Events_Event::is_bookable($event_id)) {
            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'not_bookable'));
        }
        if ($name === '' || $email === '' || !is_email($email)) {
            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'invalid_contact'));
        }
        if (!$legal) {
            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'legal'));
        }
        if (!class_exists('LKBA_Payments_Gateway')) {
            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'payments'));
        }

        $meta    = LKBA_Events_Event::get_meta($event_id);
        $company = $meta['company_id'] !== '' ? $meta['company_id'] : 'company-1';

        // Phase 2: bank-first only (ignore card even if event allows both).
        $booking_id = LKBA_Events_Bookings::create(array(
            'event_id'      => $event_id,
            'company_id'    => $company,
            'total'         => LKBA_Events_Event::get_price($event_id),
            'user_id'       => is_user_logged_in() ? get_current_user_id() : null,
            'customer_data' => array(
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
            ),
            'invoice_data'  => array_filter($invoice),
        ));

        if (is_wp_error($booking_id)) {
            $this->redirect_with($redirect_base, array(
                'lkba_book' => 'error',
                'msg'       => $booking_id->get_error_code(),
            ));
        }

        $booking = LKBA_Events_Bookings::get($booking_id);
        $context = LKBA_Events_Bookings::to_payment_context($booking);

        $bank = LKBA_Payments_Gateway::start_bank_transfer($context);
        if (is_wp_error($bank)) {
            // Keep booking pending; show error with booking number for support.
            $this->redirect_with($redirect_base, array(
                'lkba_book' => 'error',
                'msg'       => 'fakturoid',
                'ref'       => $booking->booking_number,
            ));
        }

        // Proforma meta also stored via action; ensure fields present.
        if (!empty($bank['invoice']) && is_array($bank['invoice'])) {
            LKBA_Events_Bookings::update($booking_id, array(
                'fakturoid_invoice_id'      => (string) ($bank['invoice']['invoice_id'] ?? ''),
                'fakturoid_invoice_number'  => (string) ($bank['invoice']['number'] ?? ''),
                'fakturoid_variable_symbol' => (string) ($bank['invoice']['variable_symbol'] ?? ''),
                'fakturoid_pdf'             => (string) ($bank['invoice']['pdf'] ?? ''),
                'payment_method'            => 'bank_transfer',
            ));
            $booking = LKBA_Events_Bookings::get($booking_id);
        }

        $instructions = isset($bank['instructions']) ? (string) $bank['instructions'] : '';
        LKBA_Events_Emails::send_reservation($booking, $instructions);
        LKBA_Events_Session::clear_draft();

        // Store recap in transient for thank-you view.
        $token = wp_generate_password(16, false, false);
        set_transient('lkba_book_recap_' . $token, array(
            'booking_id'   => $booking_id,
            'instructions' => $instructions,
            'qr_url'       => $bank['qr_url'] ?? '',
        ), HOUR_IN_SECONDS);

        $this->redirect_with($redirect_base, array(
            'lkba_book' => 'ok',
            'recap'     => $token,
        ));
    }

    /**
     * @param string $url
     * @param array  $args
     */
    private function redirect_with($url, array $args) {
        wp_safe_redirect(add_query_arg($args, $url));
        exit;
    }
}

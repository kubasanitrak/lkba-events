<?php
/**
 * Front booking submit — bank transfer or Comgate card (Phase 3).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Checkout {

    public function __construct() {
        add_action('init', array($this, 'maybe_handle_submit'), 20);
        add_action('init', array($this, 'maybe_download_invoice'), 5);
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
        $pay_raw  = isset($_POST['lkba_payment_method']) ? sanitize_key(wp_unslash($_POST['lkba_payment_method'])) : 'bank_transfer';

        $invoice = array(
            'company_name' => isset($_POST['lkba_inv_company']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_company'])) : '',
            'street'       => isset($_POST['lkba_inv_street']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_street'])) : '',
            'city'         => isset($_POST['lkba_inv_city']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_city'])) : '',
            'zip'          => isset($_POST['lkba_inv_zip']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_zip'])) : '',
            'ic'           => isset($_POST['lkba_inv_ic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_ic'])) : '',
            'dic'          => isset($_POST['lkba_inv_dic']) ? sanitize_text_field(wp_unslash($_POST['lkba_inv_dic'])) : '',
        );

        LKBA_Events_Session::save_draft(array(
            'event_id'        => $event_id,
            'name'            => $name,
            'email'           => $email,
            'phone'           => $phone,
            'notes'           => $notes,
            'invoice'         => $invoice,
            'payment_method'  => $pay_raw,
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

        $allowed = LKBA_Events_Event::get_allowed_payment_methods($event_id);
        if (!$allowed) {
            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'payments'));
        }

        $method = in_array($pay_raw, array('comgate', 'bank_transfer'), true) ? $pay_raw : 'bank_transfer';
        if (!in_array($method, $allowed, true)) {
            $method = $allowed[0];
        }

        $meta    = LKBA_Events_Event::get_meta($event_id);
        $company = $meta['company_id'] !== '' ? $meta['company_id'] : 'company-1';

        $booking_id = LKBA_Events_Bookings::create(array(
            'event_id'        => $event_id,
            'company_id'      => $company,
            'total'           => LKBA_Events_Event::get_price($event_id),
            'payment_method'  => $method,
            'user_id'         => is_user_logged_in() ? get_current_user_id() : null,
            'customer_data'   => array(
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
            ),
            'invoice_data'    => array_filter($invoice),
        ));

        if (is_wp_error($booking_id)) {
            $this->redirect_with($redirect_base, array(
                'lkba_book' => 'error',
                'msg'       => $booking_id->get_error_code(),
            ));
        }

        $booking = LKBA_Events_Bookings::get($booking_id);
        $context = LKBA_Events_Bookings::to_payment_context($booking);
        $context['payment_method'] = $method;
        $context['return_url_paid'] = LKBA_Events_Settings::get_payment_success_url(array(
            'ref' => $booking->booking_number,
        ));
        $context['return_url_cancelled'] = LKBA_Events_Settings::get_payment_failed_url(array(
            'ref' => $booking->booking_number,
        ));

        if ($method === 'comgate') {
            $card = LKBA_Payments_Gateway::start_comgate($context);
            if (!empty($card['error'])) {
                $this->redirect_with($redirect_base, array(
                    'lkba_book' => 'error',
                    'msg'       => 'comgate',
                    'ref'       => $booking->booking_number,
                ));
            }

            if (!empty($card['trans_id'])) {
                LKBA_Events_Bookings::update($booking_id, array(
                    'transaction_id' => (string) $card['trans_id'],
                    'payment_method' => 'comgate',
                ));
            }

            // Reservation e-mail without bank block (card in progress).
            $booking = LKBA_Events_Bookings::get($booking_id);
            LKBA_Events_Emails::send_reservation($booking, '');
            LKBA_Events_Session::clear_draft();

            if (!empty($card['redirect'])) {
                wp_redirect($card['redirect']); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect — Comgate gateway
                exit;
            }

            $this->redirect_with($redirect_base, array('lkba_book' => 'error', 'msg' => 'comgate'));
        }

        // Bank transfer.
        $bank = LKBA_Payments_Gateway::start_bank_transfer($context);
        if (is_wp_error($bank)) {
            $this->redirect_with($redirect_base, array(
                'lkba_book' => 'error',
                'msg'       => 'fakturoid',
                'ref'       => $booking->booking_number,
            ));
        }

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
     * Secure invoice PDF download for booking owner / admins.
     */
    public function maybe_download_invoice() {
        if (empty($_GET['lkba_invoice'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return;
        }

        $booking_id = absint($_GET['lkba_invoice']);
        $nonce      = isset($_GET['_wpnonce']) ? sanitize_text_field(wp_unslash($_GET['_wpnonce'])) : '';
        if (!$booking_id || !wp_verify_nonce($nonce, 'lkba_invoice_' . $booking_id)) {
            wp_die(esc_html__('Neplatný odkaz na fakturu.', 'lkba-events'), 403);
        }

        $booking = LKBA_Events_Bookings::get($booking_id);
        if (!$booking || empty($booking->fakturoid_pdf)) {
            wp_die(esc_html__('Faktura není k dispozici.', 'lkba-events'), 404);
        }

        $uid = get_current_user_id();
        $ok  = current_user_can('edit_posts')
            || ($uid && (int) $booking->user_id === $uid);
        if (!$ok) {
            // Allow tokenized guest access via email match + logged-out refuse.
            wp_die(esc_html__('Nemáte oprávnění stáhnout tuto fakturu. Přihlaste se.', 'lkba-events'), 403);
        }

        if (!class_exists('LKBA_Payments_Fakturoid')) {
            wp_die(esc_html__('Platební modul není dostupný.', 'lkba-events'), 500);
        }

        $path = LKBA_Payments_Fakturoid::absolute_path($booking->fakturoid_pdf);
        if (!is_readable($path)) {
            wp_die(esc_html__('Soubor faktury chybí.', 'lkba-events'), 404);
        }

        nocache_headers();
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . sanitize_file_name($booking->booking_number . '-invoice.pdf') . '"');
        header('Content-Length: ' . (string) filesize($path));
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile
        readfile($path);
        exit;
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

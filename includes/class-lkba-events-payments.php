<?php
/**
 * Wire E- refs into lkba-payments registry + Fakturoid resolve filter.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Payments {

    public static function boot() {
        add_action('lkba_payments_register', array(__CLASS__, 'register'));
        // If payments already fired the action before we loaded, register now.
        if (did_action('lkba_payments_register')) {
            self::register();
        }
        add_filter('lkba_payments_resolve_fakturoid', array(__CLASS__, 'resolve_fakturoid'), 10, 3);
        add_action('lkba_payments_fakturoid_proforma_created', array(__CLASS__, 'on_proforma_created'), 10, 2);
        add_action('lkba_payments_fakturoid_document_synced', array(__CLASS__, 'on_document_synced'), 10, 2);
        add_action('lkba_payments_fakturoid_invoice_after_card', array(__CLASS__, 'on_invoice_after_card'), 10, 2);
    }

    public static function register() {
        if (!class_exists('LKBA_Payments_Registry')) {
            return;
        }

        LKBA_Payments_Registry::register_resolver('E', array(__CLASS__, 'resolve_ref'));
        LKBA_Payments_Registry::register_paid_handler('E', array(__CLASS__, 'handle_paid'));
        LKBA_Payments_Registry::register_failed_handler('E', array(__CLASS__, 'handle_failed'));
    }

    /**
     * @param string $ref_id
     * @return array|null
     */
    public static function resolve_ref($ref_id) {
        $booking = LKBA_Events_Bookings::get_by_number($ref_id);
        if (!$booking) {
            return null;
        }
        return LKBA_Events_Bookings::to_payment_context($booking);
    }

    /**
     * @param array|null $context
     * @param array      $invoice
     * @param array      $payment
     * @return array|null
     */
    public static function resolve_fakturoid($context, $invoice, $payment) {
        if (is_array($context)) {
            return $context;
        }

        if (!empty($invoice['id'])) {
            $booking = LKBA_Events_Bookings::get_by_fakturoid_invoice_id((string) $invoice['id']);
            if ($booking) {
                return LKBA_Events_Bookings::to_payment_context($booking);
            }
        }

        if (!empty($invoice['custom_id'])) {
            $booking = LKBA_Events_Bookings::get_by_number((string) $invoice['custom_id']);
            if ($booking) {
                return LKBA_Events_Bookings::to_payment_context($booking);
            }
        }

        $vs = '';
        if (!empty($payment['variable_symbol'])) {
            $vs = (string) $payment['variable_symbol'];
        } elseif (!empty($invoice['variable_symbol'])) {
            $vs = (string) $invoice['variable_symbol'];
        }
        if ($vs !== '') {
            $booking = LKBA_Events_Bookings::get_by_variable_symbol($vs);
            if ($booking) {
                return LKBA_Events_Bookings::to_payment_context($booking);
            }
        }

        return null;
    }

    /**
     * @param array  $context
     * @param string $transaction_id
     * @param string $source
     * @param array  $extra
     * @return bool
     */
    public static function handle_paid($context, $transaction_id, $source, $extra = array()) {
        $ref = $context['ref_id'] ?? '';
        $booking = LKBA_Events_Bookings::get_by_number($ref);
        if (!$booking) {
            return false;
        }

        if ($booking->payment_status === 'paid' && $booking->status === 'confirmed') {
            return true;
        }

        $user_id = self::ensure_user_for_booking($booking);

        $fields = array(
            'status'         => 'confirmed',
            'payment_status' => 'paid',
            'paid_at'        => current_time('mysql'),
            'transaction_id' => $transaction_id,
        );
        if ($user_id) {
            $fields['user_id'] = $user_id;
        }

        if (!empty($extra['invoice']['id'])) {
            $fields['fakturoid_invoice_id'] = (string) $extra['invoice']['id'];
        }
        if (!empty($extra['invoice']['number'])) {
            $fields['fakturoid_invoice_number'] = (string) $extra['invoice']['number'];
        }
        if (!empty($extra['invoice']['variable_symbol'])) {
            $fields['fakturoid_variable_symbol'] = preg_replace('/\D/', '', (string) $extra['invoice']['variable_symbol']);
        }

        LKBA_Events_Bookings::update((int) $booking->id, $fields);
        $booking = LKBA_Events_Bookings::get((int) $booking->id);

        LKBA_Events_Emails::send_paid($booking);
        return true;
    }

    /**
     * @param array  $context
     * @param string $reason
     * @param string $source
     * @return bool
     */
    public static function handle_failed($context, $reason, $source) {
        $booking = LKBA_Events_Bookings::get_by_number($context['ref_id'] ?? '');
        if (!$booking) {
            return false;
        }
        LKBA_Events_Bookings::update((int) $booking->id, array(
            'payment_status' => 'failed',
            'status'         => 'cancelled',
        ));
        return true;
    }

    /**
     * Persist Fakturoid meta after proforma create.
     *
     * @param array $context
     * @param array $result
     */
    public static function on_proforma_created($context, $result) {
        $booking = LKBA_Events_Bookings::get_by_number($context['ref_id'] ?? '');
        if (!$booking || !is_array($result)) {
            return;
        }
        LKBA_Events_Bookings::update((int) $booking->id, array(
            'fakturoid_invoice_id'      => (string) ($result['invoice_id'] ?? ''),
            'fakturoid_invoice_number'  => (string) ($result['number'] ?? ''),
            'fakturoid_variable_symbol' => (string) ($result['variable_symbol'] ?? ''),
            'fakturoid_pdf'             => (string) ($result['pdf'] ?? ''),
        ));
    }

    /**
     * @param array $context
     * @param array $synced
     */
    public static function on_document_synced($context, $synced) {
        $booking = LKBA_Events_Bookings::get_by_number($context['ref_id'] ?? '');
        if (!$booking || !is_array($synced)) {
            return;
        }
        $fields = array();
        if (!empty($synced['invoice_id'])) {
            $fields['fakturoid_invoice_id'] = (string) $synced['invoice_id'];
        }
        if (!empty($synced['number'])) {
            $fields['fakturoid_invoice_number'] = (string) $synced['number'];
        }
        if (!empty($synced['variable_symbol'])) {
            $fields['fakturoid_variable_symbol'] = (string) $synced['variable_symbol'];
        }
        if (!empty($synced['pdf'])) {
            $fields['fakturoid_pdf'] = (string) $synced['pdf'];
        }
        if ($fields) {
            LKBA_Events_Bookings::update((int) $booking->id, $fields);
        }
    }

    /**
     * Card path: Fakturoid invoice created only after Comgate paid (decision §4).
     *
     * @param array $context
     * @param array $invoice
     */
    public static function on_invoice_after_card($context, $invoice) {
        $booking = LKBA_Events_Bookings::get_by_number($context['ref_id'] ?? '');
        if (!$booking || !is_array($invoice)) {
            return;
        }
        $fields = array(
            'fakturoid_invoice_id'      => (string) ($invoice['invoice_id'] ?? $invoice['id'] ?? ''),
            'fakturoid_invoice_number'  => (string) ($invoice['number'] ?? ''),
            'fakturoid_variable_symbol' => preg_replace('/\D/', '', (string) ($invoice['variable_symbol'] ?? '')),
            'fakturoid_pdf'             => (string) ($invoice['pdf'] ?? ''),
        );
        LKBA_Events_Bookings::update((int) $booking->id, array_filter($fields));
        // Paid confirmation already sent in handle_paid; PDF is available via my bookings download.
    }

    /**
     * Guest checkout: create or link WP user on payment confirmation.
     *
     * @param object $booking
     * @return int|null
     */
    public static function ensure_user_for_booking($booking) {
        if (!empty($booking->user_id)) {
            return (int) $booking->user_id;
        }

        $email = $booking->customer_data['email'] ?? '';
        $name  = $booking->customer_data['name'] ?? '';
        if ($email === '' || !is_email($email)) {
            return null;
        }

        $existing = get_user_by('email', $email);
        if ($existing) {
            return (int) $existing->ID;
        }

        $login = sanitize_user(current(explode('@', $email)), true);
        if ($login === '' || username_exists($login)) {
            $login = 'lkba_' . wp_generate_password(8, false, false);
        }

        $user_id = wp_insert_user(array(
            'user_login'   => $login,
            'user_email'   => $email,
            'user_pass'    => wp_generate_password(24),
            'display_name' => $name !== '' ? $name : $login,
            'role'         => 'subscriber',
        ));

        if (is_wp_error($user_id)) {
            return null;
        }

        return (int) $user_id;
    }
}

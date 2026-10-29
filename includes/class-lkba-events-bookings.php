<?php
/**
 * Bookings CRUD + capacity.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Bookings {

    /**
     * @return string
     */
    public static function table() {
        global $wpdb;
        return $wpdb->prefix . 'lkba_bookings';
    }

    /**
     * Active holds: pending/confirmed seats counting toward capacity.
     *
     * @param int $event_id
     * @return int
     */
    public static function count_active_for_event($event_id) {
        global $wpdb;

        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return 0;
        }

        $table = self::table();
        $now   = current_time('mysql');

        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table
             WHERE event_id = %d
             AND status IN ('pending', 'confirmed', 'rescheduled')
             AND payment_status NOT IN ('failed', 'expired')
             AND (
                status = 'confirmed'
                OR expires_at IS NULL
                OR expires_at > %s
             )",
            $event_id,
            $now
        ));
    }

    /**
     * @param array $data
     * @return int|WP_Error booking id
     */
    public static function create(array $data) {
        global $wpdb;

        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return new WP_Error('no_table', __('Bookings table missing.', 'lkba-events'));
        }

        $event_id = (int) ($data['event_id'] ?? 0);
        if (!$event_id || get_post_type($event_id) !== LKBA_Events_Post_Types::POST_TYPE_EVENT) {
            return new WP_Error('invalid_event', __('Invalid event.', 'lkba-events'));
        }

        if (!LKBA_Events_Event::is_bookable($event_id)) {
            return new WP_Error('not_bookable', __('This event is not open for booking.', 'lkba-events'));
        }

        $capacity = (int) LKBA_Events_Event::get_meta($event_id)['capacity'];
        if ($capacity > 0 && self::count_active_for_event($event_id) >= $capacity) {
            return new WP_Error('full', __('This event is full.', 'lkba-events'));
        }

        $customer = isset($data['customer_data']) && is_array($data['customer_data']) ? $data['customer_data'] : array();
        $invoice  = isset($data['invoice_data']) && is_array($data['invoice_data']) ? $data['invoice_data'] : array();
        $total    = isset($data['total']) ? (float) $data['total'] : LKBA_Events_Event::get_price($event_id);
        $company  = isset($data['company_id']) ? (string) $data['company_id'] : (string) LKBA_Events_Event::get_meta($event_id)['company_id'];

        $expires = self::default_expires_at($event_id);

        $row = array(
            'booking_number' => 'E-temp',
            'event_id'       => $event_id,
            'status'         => 'pending',
            'payment_status' => 'awaiting',
            'payment_method' => 'bank_transfer',
            'total'          => $total,
            'currency'       => 'CZK',
            'company_id'     => $company !== '' ? $company : 'company-1',
            'customer_data'  => wp_json_encode($customer),
            'invoice_data'   => wp_json_encode($invoice),
            'expires_at'     => $expires,
            'created_at'     => current_time('mysql'),
            'updated_at'     => current_time('mysql'),
        );
        $formats = array('%s', '%d', '%s', '%s', '%s', '%f', '%s', '%s', '%s', '%s', '%s', '%s', '%s');

        if (!empty($data['user_id'])) {
            $row['user_id'] = (int) $data['user_id'];
            $formats[]      = '%d';
        }

        $inserted = $wpdb->insert(self::table(), $row, $formats);

        if (!$inserted) {
            return new WP_Error('insert_failed', __('Could not create booking.', 'lkba-events'));
        }

        $id = (int) $wpdb->insert_id;
        $number = 'E-' . $id;
        $wpdb->update(
            self::table(),
            array('booking_number' => $number),
            array('id' => $id),
            array('%s'),
            array('%d')
        );

        return $id;
    }

    /**
     * @param int $event_id
     * @return string|null
     */
    public static function default_expires_at($event_id) {
        $meta  = LKBA_Events_Event::get_meta($event_id);
        $close = null;
        if ($meta['datetime_start'] !== '') {
            $start_ts = strtotime($meta['datetime_start']);
            if ($start_ts) {
                $close_h = LKBA_Events_Settings::get_booking_close_hours();
                $close   = $start_ts - ($close_h * HOUR_IN_SECONDS);
            }
        }
        $week = time() + (7 * DAY_IN_SECONDS);
        $ts   = $close ? min($close, $week) : $week;
        if ($ts <= time()) {
            $ts = time() + HOUR_IN_SECONDS;
        }
        return wp_date('Y-m-d H:i:s', $ts);
    }

    /**
     * @param int $id
     * @return object|null
     */
    public static function get($id) {
        global $wpdb;
        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table() . ' WHERE id = %d', $id));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $booking_number
     * @return object|null
     */
    public static function get_by_number($booking_number) {
        global $wpdb;
        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE booking_number = %s LIMIT 1',
            $booking_number
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $invoice_id
     * @return object|null
     */
    public static function get_by_fakturoid_invoice_id($invoice_id) {
        global $wpdb;
        if (!LKBA_Events_DB::table_exists('lkba_bookings') || $invoice_id === '') {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE fakturoid_invoice_id = %s LIMIT 1',
            (string) $invoice_id
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param string $vs
     * @return object|null
     */
    public static function get_by_variable_symbol($vs) {
        global $wpdb;
        $vs = preg_replace('/\D/', '', (string) $vs);
        if ($vs === '' || !LKBA_Events_DB::table_exists('lkba_bookings')) {
            return null;
        }
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . self::table() . ' WHERE fakturoid_variable_symbol = %s LIMIT 1',
            $vs
        ));
        return $row ? self::hydrate($row) : null;
    }

    /**
     * @param object $row
     * @return object
     */
    public static function hydrate($row) {
        $row->customer_data = json_decode((string) $row->customer_data, true);
        if (!is_array($row->customer_data)) {
            $row->customer_data = array();
        }
        $row->invoice_data = json_decode((string) $row->invoice_data, true);
        if (!is_array($row->invoice_data)) {
            $row->invoice_data = array();
        }
        return $row;
    }

    /**
     * @param int                  $id
     * @param array<string, mixed> $fields
     * @return bool
     */
    public static function update($id, array $fields) {
        global $wpdb;
        if (!$fields) {
            return false;
        }
        if (isset($fields['customer_data']) && is_array($fields['customer_data'])) {
            $fields['customer_data'] = wp_json_encode($fields['customer_data']);
        }
        if (isset($fields['invoice_data']) && is_array($fields['invoice_data'])) {
            $fields['invoice_data'] = wp_json_encode($fields['invoice_data']);
        }
        $fields['updated_at'] = current_time('mysql');
        $formats = array_fill(0, count($fields), '%s');
        return false !== $wpdb->update(self::table(), $fields, array('id' => (int) $id), $formats, array('%d'));
    }

    /**
     * @param array $args
     * @return object[]
     */
    public static function query(array $args = array()) {
        global $wpdb;

        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return array();
        }

        $table = self::table();
        $where = array('1=1');
        $params = array();

        if (!empty($args['event_id'])) {
            $where[]  = 'event_id = %d';
            $params[] = (int) $args['event_id'];
        }
        if (!empty($args['status'])) {
            $where[]  = 'status = %s';
            $params[] = $args['status'];
        }
        if (!empty($args['payment_status'])) {
            $where[]  = 'payment_status = %s';
            $params[] = $args['payment_status'];
        }
        if (!empty($args['search'])) {
            $where[]  = '(booking_number LIKE %s OR customer_data LIKE %s)';
            $like     = '%' . $wpdb->esc_like($args['search']) . '%';
            $params[] = $like;
            $params[] = $like;
        }

        $limit = isset($args['limit']) ? max(1, (int) $args['limit']) : 100;
        $sql   = "SELECT * FROM $table WHERE " . implode(' AND ', $where) . ' ORDER BY id DESC LIMIT %d';
        $params[] = $limit;

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $rows = $wpdb->get_results($wpdb->prepare($sql, $params));
        if (!is_array($rows)) {
            return array();
        }
        return array_map(array(__CLASS__, 'hydrate'), $rows);
    }

    /**
     * Build payments context for a booking.
     *
     * @param object $booking
     * @return array
     */
    public static function to_payment_context($booking) {
        $event   = get_post((int) $booking->event_id);
        $label   = $event ? $event->post_title : $booking->booking_number;
        $email   = $booking->customer_data['email'] ?? '';
        $name    = $booking->customer_data['name'] ?? '';

        return array(
            'ref_id'                    => $booking->booking_number,
            'company_id'                => (string) $booking->company_id,
            'amount'                    => (float) $booking->total,
            'currency'                  => $booking->currency ?: 'CZK',
            'customer_email'            => $email,
            'customer_name'             => $name,
            'label'                     => $label,
            'line_items'                => array(
                array(
                    'name'       => $label,
                    'quantity'   => 1,
                    'unit_price' => (float) $booking->total,
                ),
            ),
            'invoice_data'              => $booking->invoice_data,
            'fakturoid_invoice_id'      => (string) $booking->fakturoid_invoice_id,
            'fakturoid_invoice_number'  => (string) $booking->fakturoid_invoice_number,
            'fakturoid_variable_symbol' => (string) $booking->fakturoid_variable_symbol,
            'fakturoid_pdf'             => (string) $booking->fakturoid_pdf,
            'payment_method'            => (string) $booking->payment_method,
            'transaction_id'            => (string) $booking->transaction_id,
            'meta'                      => array(
                'booking_id' => (int) $booking->id,
                'event_id'   => (int) $booking->event_id,
            ),
        );
    }

    /**
     * Mark expired pending bookings (capacity release).
     */
    public static function expire_stale() {
        global $wpdb;
        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return;
        }
        $table = self::table();
        $now   = current_time('mysql');
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $wpdb->query($wpdb->prepare(
            "UPDATE $table SET payment_status = 'expired', status = 'cancelled', updated_at = %s
             WHERE status = 'pending' AND payment_status = 'awaiting'
             AND expires_at IS NOT NULL AND expires_at < %s",
            $now,
            $now
        ));
    }
}

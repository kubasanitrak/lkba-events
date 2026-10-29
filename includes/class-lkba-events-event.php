<?php
/**
 * Event occurrence helpers (meta + computed booking status).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Event {

    const META_START           = '_lkba_datetime_start';
    const META_END             = '_lkba_datetime_end';
    const META_PLACE_NAME      = '_lkba_place_name';
    const META_PLACE_ADDRESS   = '_lkba_place_address';
    const META_INSTRUCTOR      = '_lkba_instructor';
    const META_CAPACITY        = '_lkba_capacity';
    const META_PRICE           = '_lkba_price';
    const META_COMPANY_ID      = '_lkba_company_id';
    const META_PAYMENT_METHODS = '_lkba_payment_methods';
    const META_BOOKING_OPEN    = '_lkba_booking_open';

    const TERM_META_PRICE = 'lkba_default_price';

    /**
     * @param int $event_id
     * @return array<string, mixed>
     */
    public static function get_meta($event_id) {
        $event_id = (int) $event_id;
        return array(
            'datetime_start'  => (string) get_post_meta($event_id, self::META_START, true),
            'datetime_end'    => (string) get_post_meta($event_id, self::META_END, true),
            'place_name'      => (string) get_post_meta($event_id, self::META_PLACE_NAME, true),
            'place_address'   => (string) get_post_meta($event_id, self::META_PLACE_ADDRESS, true),
            'instructor'      => (string) get_post_meta($event_id, self::META_INSTRUCTOR, true),
            'capacity'        => (int) get_post_meta($event_id, self::META_CAPACITY, true),
            'price'           => get_post_meta($event_id, self::META_PRICE, true),
            'company_id'      => (string) get_post_meta($event_id, self::META_COMPANY_ID, true),
            'payment_methods' => (string) get_post_meta($event_id, self::META_PAYMENT_METHODS, true) ?: 'bank_transfer',
            'booking_open'    => get_post_meta($event_id, self::META_BOOKING_OPEN, true) === ''
                ? 1
                : (int) get_post_meta($event_id, self::META_BOOKING_OPEN, true),
        );
    }

    /**
     * @param int                  $event_id
     * @param array<string, mixed> $data
     */
    public static function save_meta($event_id, array $data) {
        $event_id = (int) $event_id;
        $map = array(
            'datetime_start'  => self::META_START,
            'datetime_end'    => self::META_END,
            'place_name'      => self::META_PLACE_NAME,
            'place_address'   => self::META_PLACE_ADDRESS,
            'instructor'      => self::META_INSTRUCTOR,
            'capacity'        => self::META_CAPACITY,
            'price'           => self::META_PRICE,
            'company_id'      => self::META_COMPANY_ID,
            'payment_methods' => self::META_PAYMENT_METHODS,
            'booking_open'    => self::META_BOOKING_OPEN,
        );

        foreach ($map as $key => $meta_key) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
            $value = $data[$key];
            if ($key === 'capacity' || $key === 'booking_open') {
                $value = (int) $value;
            } elseif ($key === 'price') {
                $value = ($value === '' || $value === null) ? '' : (string) (float) $value;
            } elseif ($key === 'payment_methods') {
                $value = in_array($value, array('both', 'card', 'bank_transfer'), true) ? $value : 'bank_transfer';
            } else {
                $value = sanitize_text_field((string) $value);
            }
            update_post_meta($event_id, $meta_key, $value);
        }
    }

    /**
     * Effective price: event override, else category default, else 0.
     *
     * @param int $event_id
     * @return float
     */
    public static function get_price($event_id) {
        $raw = get_post_meta($event_id, self::META_PRICE, true);
        if ($raw !== '' && $raw !== null) {
            return (float) $raw;
        }

        $terms = get_the_terms($event_id, LKBA_Events_Post_Types::TAX_CATEGORY);
        if (is_array($terms)) {
            foreach ($terms as $term) {
                $cat_price = get_term_meta($term->term_id, self::TERM_META_PRICE, true);
                if ($cat_price !== '' && $cat_price !== null) {
                    return (float) $cat_price;
                }
            }
        }

        return 0.0;
    }

    /**
     * Computed front status: open | full | closed.
     *
     * @param int $event_id
     * @return string
     */
    public static function get_booking_status($event_id) {
        $meta = self::get_meta($event_id);

        if (empty($meta['booking_open'])) {
            return 'closed';
        }

        $start = $meta['datetime_start'];
        if ($start !== '') {
            $start_ts = strtotime($start);
            $close_h  = LKBA_Events_Settings::get_booking_close_hours();
            if ($start_ts && (time() >= ($start_ts - ($close_h * HOUR_IN_SECONDS)))) {
                return 'closed';
            }
            if ($start_ts && $start_ts < time()) {
                return 'closed';
            }
        }

        $capacity = (int) $meta['capacity'];
        if ($capacity > 0) {
            $used = LKBA_Events_Bookings::count_active_for_event($event_id);
            if ($used >= $capacity) {
                return 'full';
            }
        }

        return 'open';
    }

    /**
     * @param int $event_id
     * @return bool
     */
    public static function is_bookable($event_id) {
        return self::get_booking_status($event_id) === 'open';
    }

    /**
     * @param array $args WP_Query args merge.
     * @return WP_Post[]
     */
    public static function query_events(array $args = array()) {
        $defaults = array(
            'post_type'      => LKBA_Events_Post_Types::POST_TYPE_EVENT,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'meta_value',
            'meta_key'       => self::META_START,
            'order'          => 'ASC',
        );
        $q = new WP_Query(array_merge($defaults, $args));
        return $q->posts;
    }

    /**
     * Split into open/upcoming bookable vs past/closed for listing.
     *
     * @return array{open:WP_Post[],past:WP_Post[]}
     */
    public static function get_open_and_past() {
        $all  = self::query_events(array('posts_per_page' => 100));
        $open = array();
        $past = array();
        $now  = time();

        foreach ($all as $post) {
            $meta  = self::get_meta($post->ID);
            $start = $meta['datetime_start'] !== '' ? strtotime($meta['datetime_start']) : false;
            $status = self::get_booking_status($post->ID);

            if ($start && $start < $now) {
                $past[] = $post;
            } elseif ($status === 'open' || $status === 'full') {
                $open[] = $post;
            } else {
                // Admin-closed future events still show in open section as closed/full.
                if ($start && $start >= $now) {
                    $open[] = $post;
                } else {
                    $past[] = $post;
                }
            }
        }

        return array('open' => $open, 'past' => $past);
    }

    /**
     * Human-readable datetime.
     *
     * @param int $event_id
     * @return string
     */
    public static function format_when($event_id) {
        $meta = self::get_meta($event_id);
        if ($meta['datetime_start'] === '') {
            return '';
        }
        $start = date_i18n('j. n. Y H:i', strtotime($meta['datetime_start']));
        if ($meta['datetime_end'] !== '') {
            $end = date_i18n('H:i', strtotime($meta['datetime_end']));
            return $start . ' – ' . $end;
        }
        return $start;
    }
}

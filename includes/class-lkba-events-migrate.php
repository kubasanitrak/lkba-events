<?php
/**
 * Import legacy Teacher Training / workshop data into events + bookings.
 *
 * Sources (barreacademy.cz standalone apps):
 * - includes/config.php → $workshops catalog (occurrences)
 * - lkba_goods → registrations (type = workshop name; vouchers share this table)
 * - lk_barre → “Jak si otevřít Pilates Studio” rows
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Migrate {

    const META_LEGACY_WORKSHOP_ID = '_lkba_legacy_workshop_id';
    const META_LEGACY_HOLDING     = '_lkba_legacy_holding';

    /**
     * Types in lkba_goods that belong to vouchers plugin — skip for events.
     *
     * @return string[]
     */
    public static function voucher_type_names() {
        return array('Dárkový poukaz', 'Voucher', 'gift voucher');
    }

    /**
     * cat code → category term name + default price (Kč, VAT-incl.).
     *
     * @return array<string, array{name:string,price:int,slug:string}>
     */
    public static function category_map() {
        return array(
            'tt'              => array('name' => 'Teacher Training', 'price' => 8000, 'slug' => 'teacher-training'),
            'tt2'             => array('name' => 'Teacher Training Level 2', 'price' => 16000, 'slug' => 'teacher-training-level-2'),
            'bst'             => array('name' => 'Barre & Strength Training', 'price' => 4500, 'slug' => 'barre-strength-training'),
            'choreoinsp'      => array('name' => 'Choreo Inspiration', 'price' => 5000, 'slug' => 'choreo-inspiration'),
            'choreoinspnaty'  => array('name' => 'Choreo Inspiration', 'price' => 5000, 'slug' => 'choreo-inspiration'),
            'bpstudio'        => array('name' => 'Jak si otevřít Pilates Studio', 'price' => 50000, 'slug' => 'jak-si-otevrit-pilates-studio'),
        );
    }

    /**
     * Map goods.type string → category key.
     *
     * @return array<string, string> normalized type → cat key
     */
    public static function type_to_cat() {
        return array(
            'teacher training'                           => 'tt',
            'teacher training level 2'                   => 'tt2',
            'barre & strength training workshop'         => 'bst',
            'barre & strength&nbsp;training workshop'    => 'bst',
            'choreo inspiration'                         => 'choreoinsp',
            'jak si otevřít pilates studio'              => 'bpstudio',
        );
    }

    /**
     * @return bool
     */
    public static function goods_table_exists() {
        return (bool) self::resolve_table('lkba_goods');
    }

    /**
     * @return bool
     */
    public static function barre_table_exists() {
        return (bool) self::resolve_table('lk_barre');
    }

    /**
     * @param string $name
     * @return string|null
     */
    public static function resolve_table($name) {
        global $wpdb;
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $name)) === $name) {
            return $name;
        }
        $prefixed = $wpdb->prefix . $name;
        if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $prefixed)) === $prefixed) {
            return $prefixed;
        }
        return null;
    }

    /**
     * Load $workshops from site config without executing it (DB/mail side effects).
     *
     * @return array<int, array>
     */
    public static function load_workshops_catalog() {
        $path = trailingslashit(ABSPATH) . 'includes/config.php';
        if (!is_readable($path)) {
            return array();
        }
        $src = file_get_contents($path);
        if ($src === false || $src === '') {
            return array();
        }

        // Extract `$workshops = [ ... ];` via bracket depth (no include / eval of full config).
        if (!preg_match('/\$workshops\s*=\s*\[/m', $src, $m, PREG_OFFSET_CAPTURE)) {
            return array();
        }
        $start = $m[0][1] + strlen($m[0][0]) - 1; // at '['
        $depth = 0;
        $len   = strlen($src);
        $end   = null;
        for ($i = $start; $i < $len; $i++) {
            $ch = $src[$i];
            if ($ch === '[') {
                $depth++;
            } elseif ($ch === ']') {
                $depth--;
                if ($depth === 0) {
                    $end = $i;
                    break;
                }
            }
        }
        if ($end === null) {
            return array();
        }

        $body = substr($src, $start, $end - $start + 1);
        // Normalize to PHP 7.4 short-array eval in isolated scope.
        $php = 'return ' . $body . ';';
        try {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged — parses catalog array only, not full config.
            $workshops = eval($php);
        } catch (Throwable $e) {
            return array();
        }
        return is_array($workshops) ? $workshops : array();
    }

    /**
     * Probe summary for admin UI.
     *
     * @return array<string, mixed>
     */
    public static function probe() {
        global $wpdb;
        $out = array(
            'config'           => is_readable(trailingslashit(ABSPATH) . 'includes/config.php'),
            'workshops_count'  => count(self::load_workshops_catalog()),
            'goods_table'      => self::resolve_table('lkba_goods'),
            'barre_table'      => self::resolve_table('lk_barre'),
            'goods_types'      => array(),
            'goods_event_rows' => 0,
            'barre_rows'       => 0,
        );

        if ($out['goods_table']) {
            $t = $out['goods_table'];
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $types = $wpdb->get_results("SELECT type, COUNT(*) AS c FROM `$t` GROUP BY type ORDER BY c DESC");
            $voucher = self::voucher_type_names();
            foreach ((array) $types as $row) {
                $is_voucher = in_array((string) $row->type, $voucher, true);
                $out['goods_types'][] = array(
                    'type'    => (string) $row->type,
                    'count'   => (int) $row->c,
                    'voucher' => $is_voucher,
                );
                if (!$is_voucher) {
                    $out['goods_event_rows'] += (int) $row->c;
                }
            }
        }
        if ($out['barre_table']) {
            $t = $out['barre_table'];
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $out['barre_rows'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$t`");
        }

        return $out;
    }

    /**
     * Ensure category terms exist (from map + existing WP terms the user already created).
     *
     * @return array{created:int,existing:int}
     */
    public static function ensure_categories() {
        $created = $existing = 0;
        $seen    = array();
        foreach (self::category_map() as $meta) {
            if (isset($seen[$meta['slug']])) {
                continue;
            }
            $seen[$meta['slug']] = true;
            $term = term_exists($meta['slug'], LKBA_Events_Post_Types::TAX_CATEGORY);
            if (!$term) {
                $term = term_exists($meta['name'], LKBA_Events_Post_Types::TAX_CATEGORY);
            }
            if ($term) {
                $term_id = (int) (is_array($term) ? $term['term_id'] : $term);
                $existing++;
            } else {
                $ins = wp_insert_term($meta['name'], LKBA_Events_Post_Types::TAX_CATEGORY, array('slug' => $meta['slug']));
                if (is_wp_error($ins)) {
                    continue;
                }
                $term_id = (int) $ins['term_id'];
                $created++;
            }
            update_term_meta($term_id, LKBA_Events_Event::TERM_META_PRICE, (string) $meta['price']);
        }
        return compact('created', 'existing');
    }

    /**
     * Import workshop occurrences as lkba_event posts.
     *
     * @return array{imported:int,skipped:int,errors:int}
     */
    public static function import_workshops() {
        $catalog  = self::load_workshops_catalog();
        $imported = $skipped = $errors = 0;
        self::ensure_categories();

        foreach ($catalog as $w) {
            if (!is_array($w) || empty($w['id'])) {
                $errors++;
                continue;
            }
            $legacy_id = (string) $w['id'];
            $existing  = self::find_event_by_legacy_id($legacy_id);
            if ($existing) {
                $skipped++;
                continue;
            }

            $cat_key = (string) ($w['cat'] ?? 'tt');
            $map     = self::category_map();
            $cat     = $map[$cat_key] ?? array('name' => (string) ($w['name'] ?? 'Workshop'), 'price' => 0, 'slug' => sanitize_title($w['name'] ?? 'workshop'));

            $title = wp_strip_all_tags(html_entity_decode((string) ($w['name'] ?? 'Workshop'), ENT_QUOTES, 'UTF-8'));
            $date  = wp_strip_all_tags(html_entity_decode((string) ($w['date'] ?? ''), ENT_QUOTES, 'UTF-8'));
            $lecture = wp_strip_all_tags(html_entity_decode((string) ($w['lecture'] ?? ''), ENT_QUOTES, 'UTF-8'));
            if ($lecture !== '') {
                $title .= ' ' . $lecture;
            }
            if ($date !== '') {
                $title .= ' — ' . $date;
            }

            $start = self::parse_workshop_datetime($w, 'start');
            $end   = self::parse_workshop_datetime($w, 'end');

            $post_id = wp_insert_post(array(
                'post_type'   => LKBA_Events_Post_Types::POST_TYPE_EVENT,
                'post_status' => !empty($w['enable']) ? 'publish' : 'draft',
                'post_title'  => $title,
                'post_content'=> '',
            ), true);

            if (is_wp_error($post_id) || !$post_id) {
                $errors++;
                continue;
            }

            $term = get_term_by('slug', $cat['slug'], LKBA_Events_Post_Types::TAX_CATEGORY);
            if ($term && !is_wp_error($term)) {
                wp_set_object_terms($post_id, (int) $term->term_id, LKBA_Events_Post_Types::TAX_CATEGORY);
            }

            $place = wp_strip_all_tags(html_entity_decode((string) ($w['place'] ?? ''), ENT_QUOTES, 'UTF-8'));
            if ($place !== '') {
                wp_set_object_terms($post_id, $place, LKBA_Events_Post_Types::TAX_PLACE);
            }

            LKBA_Events_Event::save_meta($post_id, array(
                'datetime_start'  => $start,
                'datetime_end'    => $end,
                'place_name'      => $place,
                'place_address'   => wp_strip_all_tags(html_entity_decode((string) ($w['address'] ?? ''), ENT_QUOTES, 'UTF-8')),
                'instructor'      => $lecture,
                'capacity'        => 0,
                'price'           => (string) $cat['price'],
                'company_id'      => 'company-1',
                'payment_methods' => 'both',
                'booking_open'    => !empty($w['enable']) && empty($w['full-capacity']) ? 1 : 0,
            ));
            update_post_meta($post_id, self::META_LEGACY_WORKSHOP_ID, $legacy_id);
            $imported++;
        }

        return compact('imported', 'skipped', 'errors');
    }

    /**
     * Import non-voucher rows from lkba_goods (+ lk_barre) as bookings.
     * Rows lack workshop occurrence id — attach to a per-category holding event.
     *
     * @param int $limit
     * @return array{imported:int,skipped:int,errors:int,message:string}
     */
    public static function import_registrations($limit = 1000) {
        global $wpdb;

        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            LKBA_Events_DB::create_tables();
        }

        self::ensure_categories();
        $imported = $skipped = $errors = 0;
        $rows     = array();

        $goods = self::resolve_table('lkba_goods');
        if ($goods) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $part = $wpdb->get_results($wpdb->prepare("SELECT *, 'lkba_goods' AS _src FROM `$goods` ORDER BY id ASC LIMIT %d", max(1, (int) $limit)));
            if (is_array($part)) {
                $rows = array_merge($rows, $part);
            }
        }
        $barre = self::resolve_table('lk_barre');
        if ($barre) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $part = $wpdb->get_results($wpdb->prepare("SELECT *, 'lk_barre' AS _src FROM `$barre` ORDER BY id ASC LIMIT %d", max(1, (int) $limit)));
            if (is_array($part)) {
                $rows = array_merge($rows, $part);
            }
        }

        if (!$rows) {
            return array(
                'imported' => 0,
                'skipped'  => 0,
                'errors'   => 0,
                'message'  => __('No legacy registration tables/rows found.', 'lkba-events'),
            );
        }

        $voucher_types = self::voucher_type_names();

        foreach ($rows as $row) {
            $type = (string) ($row->type ?? '');
            if (in_array($type, $voucher_types, true)) {
                $skipped++;
                continue;
            }

            $invoice = (string) ($row->invoice_number ?? '');
            $legacy_key = ($row->_src ?? 'lkba_goods') . ':' . (int) $row->id;
            if ($invoice !== '' && self::booking_exists_by_invoice($invoice)) {
                $skipped++;
                continue;
            }
            if (self::booking_exists_by_legacy_key($legacy_key)) {
                $skipped++;
                continue;
            }

            $cat_key = self::resolve_cat_for_type($type);
            $event_id = self::ensure_holding_event($cat_key, $type);
            if (!$event_id) {
                $errors++;
                continue;
            }

            $state = strtolower((string) ($row->state ?? 'pending'));
            $status_map = array(
                'pending'   => array('pending', 'awaiting'),
                'paid'      => array('confirmed', 'paid'),
                'used'      => array('confirmed', 'paid'),
                'canceled'  => array('cancelled', 'failed'),
                'cancelled' => array('cancelled', 'failed'),
            );
            $pair = $status_map[$state] ?? array('pending', 'awaiting');

            $total = isset($row->sum_total) && $row->sum_total !== null && $row->sum_total !== ''
                ? (float) $row->sum_total
                : (float) (self::category_map()[$cat_key]['price'] ?? 0);

            // Direct insert (bypass capacity / bookable checks for historical rows).
            $customer = array(
                'name'             => (string) ($row->name ?? ''),
                'email'            => (string) ($row->email ?? ''),
                'phone'            => (string) ($row->phone ?? ''),
                'legacy_goods_id'  => $legacy_key,
                'legacy_type'      => $type,
            );
            $invoice_data = array_filter(array(
                'company_name' => (string) ($row->invoice_name ?? ''),
                'street'       => (string) ($row->invoice_address ?? ''),
                'ic'           => (string) ($row->invoice_ico ?? ''),
                'dic'          => (string) ($row->invoice_dic ?? ''),
            ));

            $table = LKBA_Events_Bookings::table();
            $row_data = array(
                'booking_number'            => 'E-temp',
                'event_id'                  => $event_id,
                'status'                    => $pair[0],
                'payment_status'            => $pair[1],
                'payment_method'            => 'bank_transfer',
                'total'                     => $total,
                'currency'                  => 'CZK',
                'company_id'                => 'company-1',
                'customer_data'             => wp_json_encode($customer),
                'invoice_data'              => wp_json_encode($invoice_data),
                'fakturoid_invoice_number'  => $invoice,
                'fakturoid_variable_symbol' => preg_replace('/\D/', '', $invoice),
                'created_at'                => !empty($row->date_ordered) ? $row->date_ordered . (strlen((string) $row->date_ordered) === 10 ? ' 00:00:00' : '') : current_time('mysql'),
                'updated_at'                => current_time('mysql'),
            );
            if ($pair[1] === 'paid') {
                $row_data['paid_at'] = current_time('mysql');
            }
            $formats = array_fill(0, count($row_data), '%s');
            $formats[1] = '%d'; // event_id
            $formats[5] = '%f'; // total
            $ins = $wpdb->insert($table, $row_data, $formats);

            if (!$ins) {
                $errors++;
                continue;
            }
            $id = (int) $wpdb->insert_id;
            $wpdb->update($table, array('booking_number' => 'E-' . $id), array('id' => $id), array('%s'), array('%d'));
            $imported++;
        }

        return array(
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
            'message'  => sprintf(
                /* translators: 1: imported 2: skipped 3: errors */
                __('Registrace: %1$d nových, %2$d přeskočeno, %3$d chyb.', 'lkba-events'),
                $imported,
                $skipped,
                $errors
            ),
        );
    }

    /**
     * Full migrate: categories + workshops + registrations.
     *
     * @return array<string, mixed>
     */
    public static function run_all() {
        $cats = self::ensure_categories();
        $ws   = self::import_workshops();
        $reg  = self::import_registrations(2000);
        return array(
            'categories'    => $cats,
            'workshops'     => $ws,
            'registrations' => $reg,
            'message'       => sprintf(
                __('Kategorie +%1$d; workshopy +%2$d (skip %3$d); %4$s', 'lkba-events'),
                (int) $cats['created'],
                (int) $ws['imported'],
                (int) $ws['skipped'],
                (string) $reg['message']
            ),
        );
    }

    /**
     * @param string $type
     * @return string cat key
     */
    private static function resolve_cat_for_type($type) {
        $norm = strtolower(wp_strip_all_tags(html_entity_decode($type, ENT_QUOTES, 'UTF-8')));
        $norm = preg_replace('/\s+/', ' ', trim($norm));
        $map  = self::type_to_cat();
        if (isset($map[$norm])) {
            return $map[$norm];
        }
        // Fuzzy.
        if (strpos($norm, 'level 2') !== false) {
            return 'tt2';
        }
        if (strpos($norm, 'teacher training') !== false) {
            return 'tt';
        }
        if (strpos($norm, 'strength') !== false) {
            return 'bst';
        }
        if (strpos($norm, 'choreo') !== false) {
            return 'choreoinsp';
        }
        if (strpos($norm, 'pilates') !== false || strpos($norm, 'studio') !== false) {
            return 'bpstudio';
        }
        return 'tt';
    }

    /**
     * Holding event for registrations that cannot be tied to a workshop id.
     *
     * @param string $cat_key
     * @param string $type_label
     * @return int
     */
    private static function ensure_holding_event($cat_key, $type_label) {
        $map  = self::category_map();
        $cat  = $map[$cat_key] ?? array('name' => $type_label, 'price' => 0, 'slug' => 'legacy');
        $slug = 'legacy-holding-' . $cat['slug'];

        $q = new WP_Query(array(
            'post_type'      => LKBA_Events_Post_Types::POST_TYPE_EVENT,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => self::META_LEGACY_HOLDING,
            'meta_value'     => $cat['slug'],
            'fields'         => 'ids',
        ));
        if ($q->posts) {
            return (int) $q->posts[0];
        }

        $title = sprintf(
            /* translators: %s: category name */
            __('Legacy registrace — %s', 'lkba-events'),
            $cat['name']
        );
        $post_id = wp_insert_post(array(
            'post_type'   => LKBA_Events_Post_Types::POST_TYPE_EVENT,
            'post_status' => 'draft',
            'post_title'  => $title,
            'post_name'   => $slug,
            'post_content'=> __('Holding event for legacy form registrations without workshop occurrence id. Reassign bookings to real terms as needed.', 'lkba-events'),
        ), true);
        if (is_wp_error($post_id) || !$post_id) {
            return 0;
        }

        $term = get_term_by('slug', $cat['slug'], LKBA_Events_Post_Types::TAX_CATEGORY);
        if ($term && !is_wp_error($term)) {
            wp_set_object_terms($post_id, (int) $term->term_id, LKBA_Events_Post_Types::TAX_CATEGORY);
        }

        LKBA_Events_Event::save_meta($post_id, array(
            'datetime_start'  => '',
            'datetime_end'    => '',
            'place_name'      => '',
            'place_address'   => '',
            'instructor'      => '',
            'capacity'        => 0,
            'price'           => (string) $cat['price'],
            'company_id'      => 'company-1',
            'payment_methods' => 'bank_transfer',
            'booking_open'    => 0,
        ));
        update_post_meta($post_id, self::META_LEGACY_HOLDING, $cat['slug']);
        return (int) $post_id;
    }

    /**
     * @param string $legacy_id
     * @return int 0 if none
     */
    private static function find_event_by_legacy_id($legacy_id) {
        $q = new WP_Query(array(
            'post_type'      => LKBA_Events_Post_Types::POST_TYPE_EVENT,
            'post_status'    => 'any',
            'posts_per_page' => 1,
            'meta_key'       => self::META_LEGACY_WORKSHOP_ID,
            'meta_value'     => $legacy_id,
            'fields'         => 'ids',
        ));
        return $q->posts ? (int) $q->posts[0] : 0;
    }

    /**
     * @param string $invoice
     * @return bool
     */
    private static function booking_exists_by_invoice($invoice) {
        global $wpdb;
        if ($invoice === '' || !LKBA_Events_DB::table_exists('lkba_bookings')) {
            return false;
        }
        $table = LKBA_Events_Bookings::table();
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE fakturoid_invoice_number = %s LIMIT 1",
            $invoice
        )) > 0;
    }

    /**
     * @param string $legacy_key
     * @return bool
     */
    private static function booking_exists_by_legacy_key($legacy_key) {
        global $wpdb;
        if (!LKBA_Events_DB::table_exists('lkba_bookings')) {
            return false;
        }
        $table = LKBA_Events_Bookings::table();
        $like  = '%' . $wpdb->esc_like($legacy_key) . '%';
        return (int) $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE customer_data LIKE %s LIMIT 1",
            $like
        )) > 0;
    }

    /**
     * @param array  $w
     * @param string $which start|end
     * @return string Y-m-d H:i:s or ''
     */
    private static function parse_workshop_datetime(array $w, $which) {
        $date_raw = wp_strip_all_tags(html_entity_decode((string) ($w['date'] ?? ''), ENT_QUOTES, 'UTF-8'));
        $time     = $which === 'end'
            ? (string) ($w['time_end'] ?? '17:00')
            : (string) ($w['time_start'] ?? '10:00');

        // Prefer first day of ranges like "12. – 13. 5. 2026" or "23. - 24. 6. 2026".
        if (!preg_match('/(\d{1,2})\.\s*(?:[–\-—]\s*\d{1,2}\.\s*)?(\d{1,2})\.\s*(\d{4})/u', $date_raw, $m)) {
            // Single "21. 1. 2026"
            if (!preg_match('/(\d{1,2})\.\s*(\d{1,2})\.\s*(\d{4})/u', $date_raw, $m)) {
                return '';
            }
            $day = (int) $m[1];
            $mon = (int) $m[2];
            $yr  = (int) $m[3];
        } else {
            $day = (int) $m[1];
            $mon = (int) $m[2];
            $yr  = (int) $m[3];
        }

        if (!preg_match('/^(\d{1,2}):(\d{2})$/', $time, $tm)) {
            $tm = array(1 => 10, 2 => 0);
        }

        return sprintf('%04d-%02d-%02d %02d:%02d:00', $yr, $mon, $day, (int) $tm[1], (int) $tm[2]);
    }
}

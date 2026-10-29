<?php
/**
 * Booking transactional emails (bank-first MVP).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Emails {

    /**
     * Reservation created + payment instructions.
     *
     * @param object $booking
     * @param string $instructions_html From payments gateway.
     * @return bool
     */
    public static function send_reservation($booking, $instructions_html = '') {
        $email = $booking->customer_data['email'] ?? '';
        if ($email === '' || !is_email($email)) {
            return false;
        }

        $event = get_post((int) $booking->event_id);
        $title = $event ? $event->post_title : $booking->booking_number;
        $when  = LKBA_Events_Event::format_when((int) $booking->event_id);
        $meta  = LKBA_Events_Event::get_meta((int) $booking->event_id);
        $support = LKBA_Events_Settings::get_support_email();

        $inner  = '<p>' . esc_html(sprintf(
            /* translators: %s: customer name */
            __('Dobrý den %s,', 'lkba-events'),
            $booking->customer_data['name'] ?? ''
        )) . '</p>';
        $inner .= '<p>' . esc_html__('děkujeme za rezervaci. Níže najdete shrnutí a platební údaje.', 'lkba-events') . '</p>';
        $inner .= '<ul>';
        $inner .= '<li><strong>' . esc_html__('Událost:', 'lkba-events') . '</strong> ' . esc_html($title) . '</li>';
        if ($when !== '') {
            $inner .= '<li><strong>' . esc_html__('Termín:', 'lkba-events') . '</strong> ' . esc_html($when) . '</li>';
        }
        if ($meta['place_name'] !== '') {
            $inner .= '<li><strong>' . esc_html__('Místo:', 'lkba-events') . '</strong> ' . esc_html($meta['place_name']) . '</li>';
        }
        $inner .= '<li><strong>' . esc_html__('Rezervace:', 'lkba-events') . '</strong> ' . esc_html($booking->booking_number) . '</li>';
        $inner .= '</ul>';

        if ($instructions_html !== '') {
            $inner .= $instructions_html;
        } elseif (($booking->payment_method ?? '') === 'comgate') {
            $inner .= '<p>' . esc_html__('Platbu kartou dokončete v platební bráně Comgate. Po úspěšné platbě přijde potvrzení e-mailem.', 'lkba-events') . '</p>';
        }

        $inner .= '<p>' . esc_html__('Pro zrušení nebo změnu termínu napište na', 'lkba-events')
            . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a>.</p>';

        $subject = sprintf(
            /* translators: 1: site/brand, 2: booking number */
            __('[%1$s] Rezervace %2$s — platební údaje', 'lkba-events'),
            'LKBA',
            $booking->booking_number
        );

        return self::send($email, $subject, __('Potvrzení rezervace', 'lkba-events'), $inner);
    }

    /**
     * Admin blast to attendees of one event.
     *
     * @param int    $event_id
     * @param string $subject
     * @param string $body_html
     * @param string $status_filter pending|confirmed|'' (active seats)
     * @return array{sent:int,skipped:int,errors:int}
     */
    public static function send_attendees($event_id, $subject, $body_html, $status_filter = '') {
        $event_id = (int) $event_id;
        $rows     = LKBA_Events_Bookings::query(array(
            'event_id' => $event_id,
            'limit'    => 500,
        ));
        $sent = $skipped = $errors = 0;
        foreach ($rows as $b) {
            if ($status_filter !== '' && $b->status !== $status_filter) {
                $skipped++;
                continue;
            }
            if (!in_array($b->status, array('pending', 'confirmed', 'rescheduled'), true)) {
                $skipped++;
                continue;
            }
            if (in_array($b->payment_status, array('failed', 'expired'), true)) {
                $skipped++;
                continue;
            }
            $to = $b->customer_data['email'] ?? '';
            if ($to === '' || !is_email($to)) {
                $skipped++;
                continue;
            }
            $ok = self::send($to, $subject, $subject, wpautop(wp_kses_post($body_html)));
            if ($ok) {
                $sent++;
            } else {
                $errors++;
            }
        }
        return compact('sent', 'skipped', 'errors');
    }

    /**
     * Paid / confirmed + optional invoice PDF.
     *
     * @param object $booking
     * @return bool
     */
    public static function send_paid($booking) {
        $email = $booking->customer_data['email'] ?? '';
        if ($email === '' || !is_email($email)) {
            return false;
        }

        $event = get_post((int) $booking->event_id);
        $title = $event ? $event->post_title : $booking->booking_number;
        $when  = LKBA_Events_Event::format_when((int) $booking->event_id);
        $support = LKBA_Events_Settings::get_support_email();

        $inner  = '<p>' . esc_html(sprintf(__('Dobrý den %s,', 'lkba-events'), $booking->customer_data['name'] ?? '')) . '</p>';
        $inner .= '<p>' . esc_html__('platba byla přijata. Vaše rezervace je potvrzená.', 'lkba-events') . '</p>';
        $inner .= '<ul>';
        $inner .= '<li><strong>' . esc_html__('Událost:', 'lkba-events') . '</strong> ' . esc_html($title) . '</li>';
        if ($when !== '') {
            $inner .= '<li><strong>' . esc_html__('Termín:', 'lkba-events') . '</strong> ' . esc_html($when) . '</li>';
        }
        $inner .= '<li><strong>' . esc_html__('Rezervace:', 'lkba-events') . '</strong> ' . esc_html($booking->booking_number) . '</li>';
        $inner .= '</ul>';
        $inner .= '<p>' . esc_html__('Změny / storno:', 'lkba-events')
            . ' <a href="mailto:' . esc_attr($support) . '">' . esc_html($support) . '</a>.</p>';

        $attachments = array();
        if (!empty($booking->fakturoid_pdf) && class_exists('LKBA_Payments_Fakturoid')) {
            $path = LKBA_Payments_Fakturoid::absolute_path($booking->fakturoid_pdf);
            if (is_readable($path)) {
                $attachments[] = $path;
            }
        }

        $subject = sprintf(
            __('[%1$s] Rezervace %2$s — potvrzení platby', 'lkba-events'),
            'LKBA',
            $booking->booking_number
        );

        return self::send($email, $subject, __('Platba potvrzena', 'lkba-events'), $inner, $attachments);
    }

    /**
     * @param string $to
     * @param string $subject
     * @param string $title
     * @param string $inner_html
     * @param array  $attachments
     * @return bool
     */
    private static function send($to, $subject, $title, $inner_html, array $attachments = array()) {
        if (class_exists('LKBA_Payments_Emails')) {
            return LKBA_Payments_Emails::send($to, $subject, $title, $inner_html, $attachments);
        }

        $from_name  = get_option(LKBA_Events_Settings::OPTION_SENDER_NAME, 'Lenka Krejčová Barre Academy');
        $from_email = LKBA_Events_Settings::get_sender_email();
        $headers    = array('Content-Type: text/html; charset=UTF-8');
        if ($from_email) {
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }
        return wp_mail($to, $subject, $inner_html, $headers, $attachments);
    }
}

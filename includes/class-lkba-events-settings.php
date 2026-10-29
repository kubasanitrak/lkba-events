<?php
/**
 * Events options (legal pages, support, booking close).
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Settings {

    const DEFAULT_SUPPORT_EMAIL = 'rezervace@barreacademy.cz';

    const OPTION_BOOKING_CLOSE_HOURS = 'lkba_events_booking_close_hours';
    const OPTION_SUPPORT_EMAIL       = 'lkba_events_support_email';
    const OPTION_TERMS_PAGE          = 'lkba_events_terms_page';
    const OPTION_PRIVACY_PAGE        = 'lkba_events_privacy_page';
    const OPTION_STORNO_PAGE         = 'lkba_events_storno_page';
    const OPTION_SENDER_NAME         = 'lkba_events_email_sender_name';
    const OPTION_SENDER_EMAIL        = 'lkba_events_email_sender_email';

    public function __construct() {
        // Admin UI registers fields.
    }

    public static function ensure_defaults() {
        $defaults = array(
            self::OPTION_BOOKING_CLOSE_HOURS => (int) LKBA_EVENTS_BOOKING_CLOSE_HOURS,
            self::OPTION_SUPPORT_EMAIL       => self::DEFAULT_SUPPORT_EMAIL,
            self::OPTION_TERMS_PAGE          => 0,
            self::OPTION_PRIVACY_PAGE        => 0,
            self::OPTION_STORNO_PAGE         => 0,
            self::OPTION_SENDER_NAME         => 'Lenka Krejčová Barre Academy',
            self::OPTION_SENDER_EMAIL        => self::DEFAULT_SUPPORT_EMAIL,
        );

        foreach ($defaults as $key => $value) {
            if (false === get_option($key, false)) {
                add_option($key, $value);
            }
        }

        // Migrate empty support/sender to locked address.
        if ((string) get_option(self::OPTION_SUPPORT_EMAIL, '') === '') {
            update_option(self::OPTION_SUPPORT_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        }
        if ((string) get_option(self::OPTION_SENDER_EMAIL, '') === '') {
            update_option(self::OPTION_SENDER_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        }
    }

    /**
     * @return int
     */
    public static function get_booking_close_hours() {
        $hours = (int) get_option(self::OPTION_BOOKING_CLOSE_HOURS, LKBA_EVENTS_BOOKING_CLOSE_HOURS);
        return $hours > 0 ? $hours : (int) LKBA_EVENTS_BOOKING_CLOSE_HOURS;
    }

    /**
     * @return string
     */
    public static function get_support_email() {
        $email = (string) get_option(self::OPTION_SUPPORT_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        return $email !== '' ? $email : self::DEFAULT_SUPPORT_EMAIL;
    }

    /**
     * @return string
     */
    public static function get_sender_email() {
        $email = (string) get_option(self::OPTION_SENDER_EMAIL, self::DEFAULT_SUPPORT_EMAIL);
        return $email !== '' ? $email : self::DEFAULT_SUPPORT_EMAIL;
    }
}

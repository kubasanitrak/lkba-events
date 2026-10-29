<?php
/**
 * Session draft for unfinished booking forms.
 */

if (!defined('ABSPATH')) {
    exit;
}

class LKBA_Events_Session {

    const COOKIE = 'lkba_event_draft';
    const TTL    = DAY_IN_SECONDS;

    /**
     * @return string Cookie / transient key suffix.
     */
    private static function key() {
        if (!empty($_COOKIE[self::COOKIE])) {
            return sanitize_key(wp_unslash($_COOKIE[self::COOKIE]));
        }
        $token = wp_generate_password(20, false, false);
        if (!headers_sent()) {
            setcookie(self::COOKIE, $token, time() + self::TTL, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        }
        $_COOKIE[self::COOKIE] = $token;
        return $token;
    }

    /**
     * @param array $data
     */
    public static function save_draft(array $data) {
        set_transient('lkba_event_draft_' . self::key(), $data, self::TTL);
    }

    /**
     * @return array
     */
    public static function get_draft() {
        if (empty($_COOKIE[self::COOKIE])) {
            return array();
        }
        $data = get_transient('lkba_event_draft_' . sanitize_key(wp_unslash($_COOKIE[self::COOKIE])));
        return is_array($data) ? $data : array();
    }

    public static function clear_draft() {
        if (!empty($_COOKIE[self::COOKIE])) {
            delete_transient('lkba_event_draft_' . sanitize_key(wp_unslash($_COOKIE[self::COOKIE])));
        }
        if (!headers_sent()) {
            setcookie(self::COOKIE, '', time() - YEAR_IN_SECONDS, COOKIEPATH ? COOKIEPATH : '/', COOKIE_DOMAIN, is_ssl(), true);
        }
        unset($_COOKIE[self::COOKIE]);
    }
}

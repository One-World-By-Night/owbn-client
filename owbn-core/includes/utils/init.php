<?php
/**
 * OWBN Core — Shared Utilities
 *
 * Consolidated helper functions used across all OWBN plugins.
 *
 * @package OWBNCore
 */

defined( 'ABSPATH' ) || exit;

/**
 * Format a unix timestamp or date string using WordPress site settings.
 *
 * When no format is supplied the site's date + time format is used.
 * Accepts a unix timestamp (numeric) or a parseable date string.
 *
 * @param mixed  $timestamp Unix timestamp or date string.
 * @param string $format    Optional PHP date format. Empty = site default.
 * @return string Formatted date, or empty string on empty input.
 */
function owc_format_date( $timestamp, $format = '' ) {
    if ( empty( $timestamp ) ) {
        return '';
    }

    if ( $format === '' ) {
        $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
    }

    if ( is_numeric( $timestamp ) ) {
        return date_i18n( $format, (int) $timestamp );
    }

    // Attempt to parse a date string into a timestamp.
    $parsed = strtotime( $timestamp );
    if ( false === $parsed ) {
        return (string) $timestamp;
    }

    return date_i18n( $format, $parsed );
}

/**
 * Safely JSON-decode a value, always returning an array.
 *
 * Handles already-decoded arrays, empty strings, and malformed JSON gracefully.
 *
 * @param mixed $value JSON string, array, or empty value.
 * @return array Decoded data or empty array on failure.
 */
function owc_safe_json_decode( $value ) {
    if ( is_array( $value ) ) {
        return $value;
    }

    if ( empty( $value ) || ! is_string( $value ) ) {
        return array();
    }

    $decoded = json_decode( $value, true );

    return is_array( $decoded ) ? $decoded : array();
}

/**
 * Safely retrieve a value from an array by key.
 *
 * @param array  $array   Source array.
 * @param string $key     Key to look up.
 * @param mixed  $default Fallback value when key is missing.
 * @return mixed
 */
function owc_array_get( $array, $key, $default = '' ) {
    if ( ! is_array( $array ) ) {
        return $default;
    }

    return array_key_exists( $key, $array ) ? $array[ $key ] : $default;
}

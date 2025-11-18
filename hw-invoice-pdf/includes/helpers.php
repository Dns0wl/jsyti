<?php
/**
 * Helper functions for HW Invoice PDF.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'hwip_get_default_settings' ) ) {
    /**
     * Get default plugin settings.
     *
     * @return array
     */
    function hwip_get_default_settings() {
        return array(
            'store_logo_id'        => 0,
            'store_name'           => get_bloginfo( 'name', 'display' ),
            'store_address'        => '',
            'store_phone'          => '+62 813-8370-8797',
            'store_email'          => 'business@hayuwidyas.com',
            'store_website'        => home_url(),
            'footer_text'          => __( 'Thank you for appreciating and collecting quality handmade products from Indonesia by Hayu Widyas Handmade.', 'hw-invoice-pdf' ),
            'background_color'     => '#ffffff',
            'background_image_id'  => 0,
            'accent_color'         => '#222222',
            'font_family'          => 'sans-serif',
            'light_table_borders'  => 0,
        );
    }
}

if ( ! function_exists( 'hwip_parse_settings' ) ) {
    /**
     * Merge settings with defaults.
     *
     * @param array $settings Settings.
     *
     * @return array
     */
    function hwip_parse_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            $settings = array();
        }

        return wp_parse_args( $settings, hwip_get_default_settings() );
    }
}

if ( ! function_exists( 'hwip_bool_to_int' ) ) {
    /**
     * Convert boolean-like values to 0/1.
     *
     * @param mixed $value Value.
     *
     * @return int
     */
    function hwip_bool_to_int( $value ) {
        return (int) (bool) $value;
    }
}

if ( ! function_exists( 'hwip_get_initial_letter' ) ) {
    /**
     * Retrieve the first character of a string, compatible with servers lacking mbstring.
     *
     * @param string $value String input.
     *
     * @return string
     */
    function hwip_get_initial_letter( $value ) {
        if ( empty( $value ) || ! is_string( $value ) ) {
            return '';
        }

        $substring = function_exists( 'mb_substr' ) ? mb_substr( $value, 0, 1 ) : substr( $value, 0, 1 );

        return strtoupper( $substring );
    }
}

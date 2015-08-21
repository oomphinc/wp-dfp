<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/**
 * Helper functions
 *
 * @package WordPress
 * @subpackage WP_DFP
 */

if ( !function_exists( 'wp_dfp_ad_slot' ) ) {

	/**
	 * Gets an instance of the WP_DFP_Ad_Slot class
	 *
	 * @since 1.0
	 *
	 * @param string|WP_Post $slot The name of the ad slot or a WP_Post object.
	 *
	 * @return WP_DFP_Ad_Slot
	 */
	function wp_dfp_ad_slot( $slot ) {
		if ( !class_exists( 'WP_DFP_Ad_Slot' ) ) {
			WP_DFP::inc( 'class-wp-dfp-ad-slot.php' );
		}

		try {
			return new WP_DFP_Ad_Slot( $slot );
		} catch ( InvalidArgumentException $e ) {
			return new WP_Error();
		}
	}

}

if ( !function_exists( 'wp_parse_html_atts' ) ) {

	/**
	 * Gets an array of HTML attributes as a string
	 *
	 * @since 1.0
	 *
	 * @param array $atts An array of HTML attributes where the key is the attribute name and value is the attribute value.
	 *
	 * @return string
	 */
	function wp_parse_html_atts( array $atts ) {
		foreach ( $atts as $name => &$value ) {
			if ( is_array( $value ) ) {
				$value = join( ' ', $value );
			}

			$value = $name . '="' . esc_attr( $value ) . '"';
		}
		
		return join( ' ', $atts );
	}

}

if ( !function_exists( 'wp_dfp_settings_url' ) ) {

	/**
	 * Gets the URL to the WP_DFP settings page
	 *
	 * @since 1.0
	 *
	 * @return string      The URL to the WP_DFP settings page.
	 */
	function wp_dfp_settings_url() {
		return admin_url( 'edit.php?post_type=' . WP_DFP::POST_TYPE . '&amp;page=wp_dfp_settings' );
	}

}

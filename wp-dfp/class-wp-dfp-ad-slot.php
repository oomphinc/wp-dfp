<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/**
 * Handles functionality related to a single DFP ad slot
 *
 * @package WordPress
 * @subpackage WP_DFP
 * @since 1.0
 */

class WP_DFP_Ad_Slot {

	/**
	 * The underlying WP_Post object for the ad slot
	 *
	 * @since 1.0
	 * @access protected
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * Constructor method
	 *
	 * @since 1.0
	 *
	 * @param string|WP_Post $slot The name of the ad slot or a WP_Post object.
	 */
	public function __construct( $slot ) {
		$valid = true;

		if ( is_string( $slot ) ) {
			$post = get_page_by_title( $slot, 'OBJECT', WP_DFP::POST_TYPE );

			if ( is_null( $post )) {
				$valid = false;
			}

			$this->post = $post;
		}
		elseif ( $slot instanceof WP_Post ) {
			$this->post = $slot;
		}
		
		if ( !$valid ) {
			throw new InvalidArgumentException( 'Invalid argument passed to WP_DFP_Ad_Slot::__construct().' );
		}
	}

	/**
	 * Gets a meta value for the ad slot
	 *
	 * @since 1.0
	 *
	 * @param string $meta_key The meta key to get a value for.
	 * @param mixed  $default  The default value to return if a value does not exist for the given $meta_key..
	 * @return                 The meta value or $default if a value does not exist for the given $meta_key.
	 */
	public function meta( $meta_key, $default = false ) {
		$value = get_post_meta( $this->post->ID, $meta_key, true );
		return $value == '' ? $default : $value;
	}

	/**
	 * Gets the ad slot's sizes
	 *
	 * @since 1.0
	 *
	 * @return string|array
	 */
	public function sizes() {
		return $this->meta( WP_DFP::META_OOP ) ? 'oop' : $this->meta( WP_DFP::META_SIZING_RULES, array() );
	}

	/**
	 * Gets the full slot name
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function slot() {
		WP_DFP::inc( 'wp-dfp-settings.php' );
		return WP_DFP_Settings::get( 'slot_prefix', '' ) . $this->post->post_title;
	}

	/**
	 * Gets the display markup for the ad slot
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function markup() {
		/**
		 * Filter the ad container HTML attributes array
		 *
		 * @since 1.0
		 *
		 * @param array  $classes An array of HTML attributes.
		 * @param string $slot    The ad slot name.
		 */
		$container_atts = apply_filters( 'wp_dfp_ad_slot/container_atts', array(
			'class' => 'wp-dfp-ad-slot',
			'id'    => 'wp-dfp-ad-slot-' . $this->slot()
		), $this->slot );

		/**
		 * Filter the ad unit HTML attributes array
		 *
		 * @since 1.0
		 *
		 * @param array  $attributes An array of HTML attributes.
		 * @param string $slot       The ad slot name.
		 */
		$ad_atts = apply_filters( 'wp_dfp_ad_slot/ad_atts', array(
			'class' => 'wp-dfp-ad-unit',
			'id'    => 'wp-dfp-ad-unit-' . $this->slot(),
		), $this->slot );

		// Set the size mapping for this ad unit
		$ad_atts['data-size-mapping'] = $this->slot();

		// Set the name of this adunit
		$ad_atts['data-adunit'] = $this->slot();

		if ( $this->meta( WP_DFP::META_OOP ) ) {
			$ad_atts['data-outofpage'] = 'true';
		}
		else {
			$ad_atts['style'] = 'display: none';
		}

		$markup = '
			<div ' . wp_parse_html_atts( $container_atts ) . '>
				<div ' . wp_parse_html_atts( $ad_atts ) . '></div>
			</div>';

		/**
		 * Filter the full ad unit HTML markup
		 *
		 * @since 1.0
		 *
		 * @param string $markup The full HTML markup.
		 * @param string $slot   The ad slot name.
		 */
		return apply_filters( 'wp_dfp_ad_slot/markup', $markup, $this->slot() );
	}

}

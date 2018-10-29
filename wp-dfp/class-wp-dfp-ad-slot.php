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
	 * Data related to the ad units that are currently being displayed
	 *
	 * Used to ensure unique HTML id across the page
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $incrementor = array();

	/**
	 * Constructor method
	 *
	 * @since 1.0
	 *
	 * @param string|WP_Post $slot The name of the ad slot or a WP_Post object.
	 */
	public function __construct( $slot ) {
		if ( is_string( $slot ) ) {
			$this->post = get_page_by_title( $slot, 'OBJECT', WP_DFP::POST_TYPE );
		}
		elseif ( $slot instanceof WP_Post ) {
			$this->post = $slot;
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
		if ( !$this->post instanceof WP_Post ) {
			return $default;
		}

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
		if ( !$this->post instanceof WP_Post ) {
			return '';
		}

		WP_DFP::inc( 'wp-dfp-settings.php' );
		return WP_DFP_Settings::get( 'slot_prefix', '' ) . $this->post->post_title;
	}

	/**
	 * Gets the full path to the ad slot
	 *
	 * @since 1.0.1
	 *
	 * @return string
	 */
	public function path() {
		if ( !$this->post instanceof WP_Post ) {
			return '';
		}

		WP_DFP::inc( 'wp-dfp-settings.php' );
		return ltrim( WP_DFP_Settings::get( 'network_code', '' ) . '/' . $this->slot(), '/' );
	}

	/**
	 * Gets a unique HTML id attribute for the value passed
	 *
	 * @since 1.1.1
	 *
	 * @param  string $id An HTML id attribute
	 * @return string     A unique HTML id attribute.
	 */
	protected function get_id( $id ) {
		if ( !array_key_exists( $id, self::$incrementor ) ) {
			self::$incrementor[ $id ] = '';
		}
		else {
			self::$incrementor[ $id ] += 1;
		}

		return $id . ( empty( self::$incrementor[ $id ] ) ? '' : '-' . self::$incrementor[ $id ] );
	}

	/**
	 * Gets the display markup for the ad slot
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function markup() {
		if ( !$this->post instanceof WP_Post ) {
			return '';
		}

		/**
		 * Filter the ad container HTML attributes array
		 *
		 * @since 1.0
		 *
		 * @param array  $attributes An array of HTML attributes.
		 * @param string $slot       The ad slot name.
		 */
		$container_atts = apply_filters( 'wp_dfp_ad_slot/container_atts', array(
			'class' => 'wp-dfp-ad-slot',
			'id'    => $this->get_id( 'wp-dfp-ad-slot-' . $this->slot() ),
		), $this->slot() );

		$classes = array( 'wp-dfp-ad-unit', 'wp-dfp-ad-unit-' . $this->post->post_title );
		if ( $this->meta( WP_DFP::META_OOP ) ) {
			$classes[] = 'wp-dfp-ad-unit-oop';
		}

		/**
		 * Filter the ad unit HTML attributes array
		 *
		 * @since 1.0
		 *
		 * @param array  $attributes An array of HTML attributes.
		 * @param string $slot       The ad slot name.
		 */
		$ad_atts = apply_filters( 'wp_dfp_ad_slot/ad_atts', array(
			'class' => $classes,
			'id'    => $this->get_id( 'wp-dfp-ad-unit-' . $this->post->post_title ),
		), $this->slot(), $this->sizes() );

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

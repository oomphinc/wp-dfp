<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/**
 * Functionality that only happens during a non-admin request.
 *
 * @since 1.0
 *
 * @package WordPress
 * @subpackage WP_DFP
 */

class WP_DFP_Frontend {

	/**
	 * The targeting for the current content being displayed
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $targeting;

	/**
	 * Initialization functionality
	 *
	 * @since 1.0
	 */
	public static function init() {
		$c = get_called_class();

		add_action( 'wp_enqueue_scripts', array( $c, 'register_scripts' ) );
		add_action( 'wp_dfp_render_ad', array( $c, 'render_ad' ) );
		add_shortcode( 'wp_dfp_ad', array( $c, 'ad_shortcode' ) );
  }

	/**
	 * Registers public javascript files that can be enqueued later on.
	 *
	 * @since 1.0
	 * @action wp_enqueue_scripts
	 */
	public static function register_scripts() {
		wp_register_script( 'jquery-dfp', WP_DFP::url( 'js/jquery.dfp.js' ), array( 'jquery' ), '2.1.0', TRUE );
		wp_register_script( 'wp-dfp', WP_DFP::url( 'js/wp-dfp.js' ), array( 'jquery-dfp' ), WP_DFP::VERSION, TRUE );
	}

	/**
	 * Renders markup for the specified ad slot
	 *
	 * @since 1.0
	 * @action wp_dfp_render_ad
	 *
	 * @param string $slot   The name of the slot to render.
	 * @param string $action Optional. What to do with the rendered markup. Either "display" or "get".
	 */
	public static function render_ad( $slot, $action = 'display' ) {
		self::get_targeting();

		$markup = wp_dfp_ad_slot( $slot )->markup();

		if ( $action == 'display' ) {
			echo $markup;
		}
		else {
			return $markup;
		}
	}

	/**
	 * Gets markup for the [wp_dfp_ad] shortcode
	 *
	 * @since 1.0
	 *
	 * @param  array $atts An array of shortcode attributes.
	 *
	 * @return string      The generated shortcode markup.
	 */
	public static function ad_shortcode( $atts ) {
		$atts = shortcode_atts( array( 'slot' => null ), $atts );
		$markup = '';

		extract( $atts );

		if ( $slot ) {
			$markup = self::render_ad( $slot, 'get' );
		}

		return $markup;
	}

	/**
	 * Generates the targeting for the current content being displayed
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_targeting() {
		if ( is_array( self::$targeting ) ) {
			return;
		}

		// Init targeting array
		self::$targeting = array();

		// Include WP_DFP_Settings class
		WP_DFP::inc( 'wp-dfp-settings.php' );

		// Helper function for converting all array values to strings
		$to_string = create_function( '$v', 'return is_array( $v ) ? $v : (string) $v;' );

		// Define targeting rules for home/front page
		if ( is_front_page() || is_home() ) {
			self::$targeting['post_name']  = 'home';
			self::$targeting['post_title'] = 'Home';
			self::$targeting['post_id']    = 0;
			self::$targeting['post_type']  = '';
		}
		// Define targeting rules for single posts, pages, etc
		elseif ( is_singular() ) {
			global $post;

			$tag_terms = get_the_terms( $post->ID, 'post_tag' );
			if ( is_array( $tag_terms ) ) {
				self::$targeting['tag'] = wp_list_pluck( $tag_terms, 'slug' );
			}

			$category_terms = get_the_terms( $post->ID, 'category' );
			if ( is_array( $category_terms ) ) {
				self::$targeting['category'] = array_map( $to_string , wp_list_pluck( $category_terms, 'slug' ) );
			}

			self::$targeting['post_name']  = $post->post_name;
			self::$targeting['post_title'] = $post->post_title;
			self::$targeting['post_id']    = $post->ID;
			self::$targeting['post_type']  = $post->post_type;
		}
		// Define targeting rules for category archives
		elseif ( is_category() ) {
			self::$targeting['taxonomy'] = 'category';
			self::$targeting['term'] = get_queried_object()->slug;
		}
		// Define targeting rules for tag archives
		elseif ( is_tag() ) {
			self::$targeting['taxonomy'] = 'tag';
			self::$targeting['term'] = get_queried_object()->slug;
		}
		// Define targeting rules for custom taxonomy archives
		elseif ( is_tax() ) {
			$taxonomy = get_queried_object()->slug;
			self::$targeting['taxonomy'] = $taxonomy;
			self::$targeting['term'] = get_query_var( $taxonomy );
		}
		// Define targeting rules for search results
		elseif ( is_search() ) {
			self::$targeting['search_term'] = get_query_var( 's' );
		}

		/**
		 * Filter the targeting arguments for the current content being displayed
		 *
		 * @since 1.0
		 *
		 * @param array $targeting An array of targeting arguments.
		 */
		self::$targeting = apply_filters( 'wp_dfp_targeting', self::$targeting );

		// Define slots
		$all_slots = WP_DFP::get_slots();
		$slots = array();
		foreach ( $all_slots as $slot ) {
			$slot = wp_dfp_ad_slot( $slot );
			$slots[ $slot->slot() ] = $slot->sizes();
			unset( $slot ); // prevent possible memory spike
		}

		// Get network code
		$network_code = WP_DFP_Settings::get( 'network_code' );

		// Set message defaults
		$messages = array( 'noNetworkCode' => null );

		/* If current user can manage options and a network code is not defined
		then display an message in each in-page ad slot */
		if ( empty( $network_code ) && current_user_can( 'manage_options' ) ) {
			$messages['noNetworkCode'] = '<div class="wp-dfp-ad-unit-error">' . sprintf( __( 'WP_DFP: You must supply your DFP network code in the <a target="_blank" href="%s">WP DFP settings</a> screen.', 'wp-dfp' ), wp_dfp_settings_url() ) . '</div>';
		}

		wp_enqueue_script( 'wp-dfp' );
		wp_localize_script( 'wp-dfp', 'wpdfp', array(
			'network'   => $network_code,
			'targeting' => array_map( $to_string, self::$targeting ),
			'slots'     => $slots,
			'messages'  => $messages,
		) );
  }

}

WP_DFP_Frontend::init();

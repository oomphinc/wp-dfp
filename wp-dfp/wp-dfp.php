<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/*
Plugin Name: WP DFP
Plugin URI:  https://github.com/oomphinc/wp-dfp
Description: A simple & intuitive interface for displaying Google ads on your WP site
Version:     1.1.7
Author:      Oomph, Inc.
Author URI:  http://www.oomphinc.com
Domain Path: /languages
Text Domain: wp-dfp
License:     MIT

Copyright 2016 Oomph, Inc. <http://www.oomphinc.com>

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

class WP_DFP {

	/**
	 * The version of the plugin
	 *
	 * @since 1.0
	 * @var string $VERSION
	 */
	const VERSION = '1.1.7';

	/**
	 * The ad slot custom post type
	 *
	 * @since 1.0
	 * @var string $POST_TYPE
	 */
	const POST_TYPE = 'wp_dfp_ad_slot';

	/**
	 * The meta key that refers to a slot's sizing rules
	 *
	 * @since 1.0
	 * @var string $META_SIZING_RULES
	 */
	const META_SIZING_RULES = '_wp_dfp_sizing_rules';

	/**
	 * The meta key that refers to whether a slot is out-of-page
	 *
	 * @since 1.0
	 * @var string $META_SIZING_RULES
	 */
	const META_OOP = '_wp_dfp_oop';

	/**
	 * The available slots
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $slots;

	/**
	 * Initializes the plugin.
	 *
	 * @since 1.0
	 */
	public static function init() {
		$c = get_called_class();

		load_plugin_textdomain( 'wp-dfp', false, basename( dirname( __FILE__ ) ) );

		self::inc( 'functions.php' );
		self::inc( 'wp-dfp-settings.php' );

		if ( is_admin() ) {
			self::inc( 'wp-dfp-admin.php' );
		}
		else {
			self::inc( 'wp-dfp-frontend.php' );
		}

		add_action( 'init', array( $c, 'register_types' ) );
	}

	/**
	 * Registers custom post types and taxonomies
	 *
	 * @since 1.0
	 * @action init
	 */
	public static function register_types() {
		$singular = __( 'Ad Slot', 'wp-dfp' );
		$plural = __( 'Ad Slots', 'wp-dfp' );

		register_post_type( 'wp_dfp_ad_slot', array(
			'labels' => array(
				'name'               => $plural,
				'singular_name'      => $singular,
				'add_new_item'       => sprintf( __( 'Add New %s', 'wp-dfp' ), $singular ),
				'edit_item'          => sprintf( __( 'Edit %s', 'wp-dfp' ), $singular ),
				'new_item'           => sprintf( __( 'New %s', 'wp-dfp' ), $singular ),
				'view_item'          => sprintf( __( 'View %s', 'wp-dfp' ), $singular ),
				'search_items'       => sprintf( __( 'Search %s', 'wp-dfp' ), $plural ),
				'not_found'          => sprintf( __( 'No %s found', 'wp-dfp' ), $plural ),
				'not_found_in_trash' => sprintf( __( 'No %s found trash', 'wp-dfp' ), $plural )
			),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'supports'           => false,
			'menu_icon'          => 'dashicons-feedback',
			'register_meta_box_cb' => array( 'WP_DFP_Admin', 'meta_boxes' ),
		) );
	}

	/**
	 * Gets an absolute URL relative to the root URL of the plugin.
	 *
	 * @since 1.0
	 *
	 * @param string $path Optional. The path to append to the root URL.
	 * @return string      The requested absolute URL.
	 */
	public static function url( $path = '' ) {
		return plugins_url( ltrim( $path ), __FILE__ );
	}

	/**
	 * Includes a file that is relative to the plugin's root directory.
	 *
	 * @since 1.0
	 *
	 * @param string $path Optional. The path to append to the root directory.
	 * @return bool        Whether the file was successfully included or not.
	 */
  public static function inc( $path = '' ) {
		$abspath = plugin_dir_path( __FILE__ ) . ltrim( $path );

		if ( file_exists( $abspath ) ) {
			require_once $abspath;
			return true;
		}

		return false;
	}

	/**
	 * Gets all of the available slots
	 *
	 * @since 1.0
	 *
	 * @return array
	 */
	public static function get_slots() {
		if ( is_array( self::$slots ) ) {
			return self::$slots;
		}

		self::$slots = get_posts( array(
			'post_type' => self::POST_TYPE,
			'posts_per_page' => -1,
			'post_status' => 'publish',
		) );

		return self::$slots;
	}

}

WP_DFP::init();

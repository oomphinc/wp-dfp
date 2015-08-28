<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/**
 * Functionality that only happens during an admin request.
 *
 * @since 1.0
 *
 * @package WordPress
 * @subpackage WP_DFP
 */

class WP_DFP_Admin {

	/**
	 * The action name used to clone ad slots
	 *
	 * @since 1.1
	 * @var string $CLONE_ACTION
	 */
	const CLONE_ACTION = 'wp_dfp_clone_slot';

	/**
	 * Is the cloning task running?
	 *
	 * @since 1.1
	 * @var bool
	 */
	protected static $is_cloning = false;

	/**
	 * Initialization functionality
	 *
	 * @since 1.0
	 */
	public static function init() {
		$c = get_called_class();

		WP_DFP::inc( 'externals/wp-forms-api/wp-forms-api.php' );

		add_filter( 'enter_title_here', array( $c, 'change_enter_title_here_text' ) );
		add_filter( 'wp_form_base_url', array( $c, 'wp_form_base_url' ) );
		add_action( 'save_post_' . WP_DFP::POST_TYPE, array( $c, 'save_post' ) );
		add_filter( 'wp_insert_post_data', array( $c, 'insert_post_data' ), 10, 2 );
		add_filter( 'manage_' . WP_DFP::POST_TYPE . '_posts_columns', array( $c, 'manage_admin_columns' ) );
		add_action( 'manage_' . WP_DFP::POST_TYPE . '_posts_custom_column', array( $c, 'admin_column_values' ), 10, 2 );
		add_action( 'admin_notices', array( $c, 'display_admin_notices' ) );
		add_action( 'admin_action_' . self::CLONE_ACTION, array( $c, 'clone_slot' ) );
		add_filter( 'post_row_actions', array( $c, 'row_actions' ), 10, 2 );
	}

	/**
	 * Gets the URL that will clone the given ad slot
	 *
	 * @since 1.1
	 *
	 * @param  WP_Post|int A post object or post ID.
	 * @return string      HTML markup for a link that will clone an ad slot.
	 */
	public static function clone_url( $post ) {
		$post = get_post( $post );

		if ( $post instanceof WP_Post && $post->post_type == WP_DFP::POST_TYPE ) {
			return wp_nonce_url( admin_url( 'admin.php?action=' . self::CLONE_ACTION . '&amp;wp_dfp_slot=' . $post->ID ), self::CLONE_ACTION );
		}

		return '';
	}

	/**
	 * Filter the row actions
	 *
	 * @since 1.1
	 * @filter post_row_actions
	 *
	 * @param array   $actions An array of actions.
	 * @param WP_POST $post    A post object.
	 *
	 * @return array
	 */
	public static function row_actions( $actions, $post ) {
		if ( $post->post_type == WP_DFP::POST_TYPE && current_user_can( 'edit_posts' ) ) {
			$actions[ self::CLONE_ACTION ] = '<a href="' . self::clone_url( $post ) . '">' . __( 'Clone', 'wp-dfp' ) . '</a>';
		}

		return $actions;
	}

	/**
	 * Return the meta box forms
	 *
	 * @return array
	 */
	public static function meta_box_forms() {
		return array(
			// DFP slot configuration
			'wp-dfp-slot-config' => array(
				'wp_dfp_slot_name' => array(
					'#label'       => __( 'Slot Name', 'wp-dfp' ),
					'#type'        => 'text',
					'#description' => __( 'Enter the name of the slot as found in DFP. Do not include your network ID!', 'wp-dfp' ),
				),
				WP_DFP::META_OOP => array(
					'#label'       => __( 'This is an out-of-page slot.', 'wp-dfp' ),
					'#type'        => 'checkbox',
					'#checked'     => 1,
					'#conditional' => array( 'element' => '#wp-dfp-sizing-rules', 'action' => 'hide', 'value' => 1 ),
				),
			),

			'wp-dfp-sizing-rules' => array(
				'sizing_rules' => array(
					'#label' => '',
					'#description' => __( 'Click "Add Rule" below to specify a new sizing rule.', 'wp-dfp' ),
					WP_DFP::META_SIZING_RULES => array(
						'#type' => 'multiple',
						'#add_link' => __( 'Add Rule', 'wp-dfp' ),
						'#multiple' => array(
							'container_width' => array(
								'#type' => 'text',
								'#label' => __( 'Container Width', 'wp-dfp' ),
								'#description' => __( 'If the calculated ad container width is >= than this value and is less than the next largest specified ad container width, then the ad sizes defined below will be used for this ad slot.', 'wp-dfp' ),
							),
							'sizes' => array(
								'#type' => 'textarea',
								'#label' => __( 'Ad Sizes', 'wp-dfp' ),
								'#attrs' => array( 'rows' => 5 ),
								'#description' => __( 'One ad size per line. Example: 950x100 where 950 is the width of the ad and 100 is the height of the ad.', 'wp-dfp' )
							),
						),
					),
				)
			)
		);
	}

	/**
	 * Creates a clone of an ad slot
	 *
	 * @since 1.1
	 * @uses $wpdb
	 * @action admin_action_{$action}
	 */
	public static function clone_slot() {
		global $wpdb;

		$is_correct_action = isset( $_REQUEST['action'] ) && $_REQUEST['action'] == self::CLONE_ACTION;
		$is_valid_nonce = isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], self::CLONE_ACTION );

		if ( isset( $_REQUEST['wp_dfp_slot'] ) && $is_correct_action && $is_valid_nonce ) {
			$slot_id = $_REQUEST['wp_dfp_slot'];
			$slot = get_post( $slot_id );

			if ( $slot instanceof WP_Post ) {
				$data = $slot->to_array();
				$data['post_name'] .= '-clone';
				$data['post_title'] .= '-clone';
				$data['post_author'] = get_current_user_id();
				unset( $data['ID'], $data['post_date'], $data['post_modified'], $data['guid'] );

				// Insert post
				self::$is_cloning = true;
				$new_slot_id = wp_insert_post( $data );
				self::$is_cloning = false;

				// Meta
				$meta = get_post_custom( $slot_id );
				$sql = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) VALUES\n";
				$values = array();
				$did_one = false;

				foreach ( $meta as $meta_key => $meta_value ) {
					$sql .= $did_one ? ",\n" : '';
					$sql .= '(%d, %s, %s)';
					array_push( $values, $new_slot_id, $meta_key, $meta_value[0] );
					$did_one = true;
				}

				$wpdb->query( $wpdb->prepare( $sql, $values ) );

				// Redirect to post-edit screen
				wp_redirect( admin_url( "post.php?action=edit&post={$new_slot_id}" ) );
				exit;
			}

			wp_die();
		}
	}

	/**
	 * Displays any pertinent admin notices
	 *
	 * @since 1.0
	 * @action admin_notices
	 */
	public static function display_admin_notices() {
		$network_code = WP_DFP_Settings::get( 'network_code' );
		if ( !$network_code ) {
			if ( current_user_can( 'manage_options' ) ) {
				$notice = sprintf( __( 'WP_DFP: You have not specified your DFP network code. You can set your network code by going to the <a href="%s">WP DFP settings page</a>', 'wp-dfp' ), wp_dfp_settings_url() );
			}
			else {
				$notice = __( 'WP_DFP: A DFP network code is required for WP DFP to function correctly. Please have an admin set a network code ASAP.', 'wp-dfp' );
			}

			echo '<div class="error"><p>' . $notice . '</p></div>';
		}
	}

	/**
	 * Renders the admin column values
	 *
	 * @since 1.0
	 * @action manage_{$post_type}_custom_column
	 * @uses $post
	 *
	 * @param string $column_name The name of the column to display.
	 * @param int    $post_id     The ID of the current post. Can also be taken from the global $post->ID.
	 */
	public static function admin_column_values( $column_name, $post_id ) {
		global $post;

		switch ( $column_name ) {
			case 'wp_dfp_slot_path' :
				$ad_slot = wp_dfp_ad_slot( $post );
				if ( !is_wp_error( $ad_slot ) ) {
					echo $ad_slot->path();
				}
			break;

			case 'wp_dfp_shortcode' :
				echo '<code>[wp_dfp_ad slot="' . $post->post_title . '"]</code>';
			break;

			case 'wp_dfp_php_code' :
				echo '<code>do_action(\'wp_dfp_render_ad\', \'' . $post->post_title . '\');</code>';
			break;
		}
	}

	/**
	 * Modifies the admin columns
	 *
	 * @since 1.0
	 * @filter manage_{$post_type}_posts_columns
	 *
	 * @param array $columns An array of column name => label. The name is passed to functions to identify the column. The label is shown as the column header.
	 *
	 * @return array
	 */
	public static function manage_admin_columns( $columns ) {
		unset( $columns['date'] );

		$columns['title'] = __( 'Slot Name', 'wp-dfp' );
		$columns['wp_dfp_slot_path'] = __( 'Slot Path', 'wp-dfp' );
		$columns['wp_dfp_shortcode'] = __( 'Shortcode', 'wp-dfp' );
		$columns['wp_dfp_php_code'] = __( 'PHP Code', 'wp-dfp' );

		return $columns;
	}

	/**
	 * Set the wp_forms_api base URL
	 *
	 * @since 1.0
	 * @filter wp_form_base_url
	 */
	public static function wp_form_base_url( $url ) {
		return WP_DFP::url( 'externals/wp-forms-api' );
	}

	/**
	 * Adds meta boxes to the post edit screen
	 *
	 * @since 1.0
	 * @see WP_DFP::register_types()
	 */
	public static function meta_boxes( $post ) {
		$c = get_called_class();

		// Remove the default publishing options
		remove_meta_box( 'submitdiv', WP_DFP::POST_TYPE, 'side' );
		// Slot name
		add_meta_box( 'wp-dfp-slot-config', __( 'Slot Config', 'wp-dfp' ), array( $c, 'render_meta_box' ), WP_DFP::POST_TYPE, 'normal', 'default' );
		// Sizing rules
		add_meta_box( 'wp-dfp-sizing-rules', __( 'Sizing Rules', 'wp-dfp' ), array( $c, 'render_meta_box' ), WP_DFP::POST_TYPE, 'normal', 'default' );
		// Simplified publishing options
		add_meta_box( 'submitdiv', __( 'Actions', 'wp-dfp' ), array( $c, 'render_meta_box' ), WP_DFP::POST_TYPE, 'side', 'default' );
	}

	/**
	 * Renders a meta box
	 *
	 * @since 1.0
	 * @uses $wpdb
	 *
	 * @param WP_Post $post   The current post object.
	 * @param array   $args   Any additional arguments as passed by add_meta_box()
	 */
	public static function render_meta_box( $post, $args = array() ) {
		global $wpdb;

		$meta_box = array_shift( $args );
		$forms = self::meta_box_forms();
		$values = array();
		$form = isset( $forms[ $meta_box ] ) ? $forms[ $meta_box ] : false;

		switch ( $meta_box ) {
			case 'wp-dfp-slot-config' :
				$ad_slot = wp_dfp_ad_slot( $post );
				$values['wp_dfp_slot_name'] = $post->post_name;
				$values[ WP_DFP::META_OOP ] = (int) $ad_slot->meta( WP_DFP::META_OOP );
			break;

			case 'wp-dfp-sizing-rules' :
				$ad_slot = wp_dfp_ad_slot( $post );
				$rules = $ad_slot->meta( WP_DFP::META_SIZING_RULES, array() );
				$index = 0;
				foreach ( $rules as $container_width => $sizes ) {
					$values[ WP_DFP::META_SIZING_RULES ][ $index ]['container_width'] = $container_width;

					$size_parts = array();
					foreach ( $sizes as $size ) {
						$size_parts[] = join( 'x', $size );
					}

					$values[ WP_DFP::META_SIZING_RULES ][ $index ]['sizes'] = join( "\n", $size_parts );
					$index ++;
				}

			break;

			case 'submitdiv' :
				$delete_markup = '';
				if ( current_user_can( "delete_post", $post->ID ) ) {
					$delete_text = !EMPTY_TRASH_DAYS ? __( 'Delete Permanently' ) :  __( 'Move to Trash' );
					$delete_markup = '<div id="delete-action"><a class="submitdelete deletion" href="' . get_delete_post_link( $post->ID ) . '">' . $delete_text . '</a></div>';
				}

				$markup = '
					<div id="minor-publishing">
						<div id="misc-publishing-actions">
							<div class="misc-pub-section">
								<span class="dashicons dashicons-welcome-add-page"></span><a href="' . self::clone_url( $post ) . '">' . __( 'Clone this slot', 'wp-dfp' ) . '</a>
							</div>
						</div>
					</div>
					<div id="major-publishing-actions">' .
						$delete_markup . '
						<div id="publishing-action">
							<span class="spinner"></span>' .
							get_submit_button( __( 'Save Ad Slot', 'wp-dfp' ), 'primary button-large', 'publish', false ) . '
						</div>
						<div class="clear"></div>
					</div>';

				echo $markup;
			break;
		}

		if( $form ) {
			echo WP_Forms_API::render_form( $form, $values );
		}
	}

	/**
	 * Before an ad slot is saved to the db make some changes
	 *
	 * @since 1.0
	 * @filter wp_insert_post_data
	 *
	 * @param array $data    An array of slashed post data.
	 * @param array $postarr An array of sanitized, but otherwise unmodified post data.
	 *
	 * @return Modified $data.
	 */
	public static function insert_post_data( $data, $postarr ) {
		if ( $postarr['post_type'] == WP_DFP::POST_TYPE && isset( $postarr['wp_dfp_slot_name'] ) ) {
			$data['post_name'] = sanitize_title_with_dashes( $postarr['wp_dfp_slot_name'], '', 'save' );
			$data['post_title'] = $postarr['wp_dfp_slot_name'];
		}

		return $data;
	}

	/**
	 * When an ad slot is saved, performs some extra processing
	 *
	 * @since 1.0
	 * @action save_post_{$post_type}
	 *
	 * @param int $post_id The ID of the post that was created/updated.
	 */
	public static function save_post( $post_id ) {
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		WP_Forms_API::process_form( self::meta_box_forms(), $values );

		if ( is_array( $values[ WP_DFP::META_SIZING_RULES ] ) ) {
			$rules = array();

			foreach ( $values[ WP_DFP::META_SIZING_RULES ] as $rule ) {
				$key = intval( $rule['container_width'] );

				if ( $key <= 0 ) {
					continue;
				}

				$rules[ $key ] = array();
				$sizes = explode( "\n", $rule['sizes'] );

				foreach ( $sizes as $size ) {
					// split size string into an array
					$size = explode( 'x', strtolower( $size ) );
					// remove any extra whitespace
					$size = array_map( 'trim', $size );
					// make sure sizes are integers
					$size = array_map( 'intval', $size );

					$rules[ $key ][] = $size;
				}
			}

			// Sort rules by container width highest to lowest
			krsort( $rules );
		}

		// Add sizing rules as postmeta
		update_post_meta( $post_id, WP_DFP::META_SIZING_RULES, $rules );

		// Out-Of-Page?
		update_post_meta( $post_id, WP_DFP::META_OOP, isset( $_POST[ WP_DFP::META_OOP ] ) ? $_POST[ WP_DFP::META_OOP ] : 0 );
	}

	/**
	 * Changes the "Enter title here" text on the post edit screen
	 *
	 * @since 1.0
	 * @filter enter_title_here
	 *
	 * @return string
	 */
	public static function change_enter_title_here_text( $text ) {
		if ( get_current_screen()->post_type == WP_DFP::POST_TYPE ) {
			$text = __( 'Enter Ad slot name here', 'wp-dfp' );
		}

		return $text;
	}

}

WP_DFP_Admin::init();

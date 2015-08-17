<?php defined( 'ABSPATH' ) or die( 'No direct access please!' );

/**
 * Functionality related to the plugin's settings
 *
 * @package WordPress
 * @subpackage WP_DFP
 */

class WP_DFP_Settings {

	/**
	 * Option name used to store settings values
	 *
	 * @since 1.0
	 * @var string $OPTION_NAME
	 */
	const OPTION_NAME = 'wp_dfp_settings';

	/**
	 * Input name of the settings screen nonce field
	 *
	 * @since 1.0
	 * @var string $NONCE_NAME
	 */
	const NONCE_NAME = '_wp_dfp_settings_nonce';

	/**
	 * Expected value for the settings screen nonce field
	 *
	 * @since 1.0
	 * @var string $NONCE_ACTION
	 */
	const NONCE_ACTION = 'wp_dfp_settings_save';

	/**
	 * Query string argument name for settings saved
	 *
	 * @since 1.0
	 * @var string $SETTINGS_SAVED_ARG
	 */
	const SETTINGS_SAVED_ARG = 'wp_dfp_settings_saved';

	/**
	 * Form markup for the settings screen
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $form;

	/**
	 * Plugin settings values
	 *
	 * @since 1.0
	 * @access protected
	 * @var array
	 */
	protected static $values;

	/**
	 * Initialization method
	 *
	 * @since 1.0
	 */
	public static function init() {
		$c = get_called_class();

		if ( is_admin() ) {
			add_action( 'admin_menu', array( $c, 'admin_menu_items' ) );
			add_action( 'admin_init', array( $c, 'save_settings' ) );
			add_action( 'admin_notices', array( $c, 'display_form_notices' ) );
		}
	}

	/**
	 * Displays any notices related to the settings form
	 *
	 * @since 1.0
	 * @action admin_notices
	 */
	public static function display_form_notices() {
		if ( isset( $_GET[ self::SETTINGS_SAVED_ARG ] ) ) {
			echo '<div class="updated"><p>' . __( 'Settings saved successfully!', 'wp-dfp' ) . '</p></div>';
		}
	}

	/**
	 * Add items to the admin menu
	 *
	 * @since 1.0
	 * @action admin_menu
	 */
	public static function admin_menu_items() {
		$c = get_called_class();
		add_options_page( __( 'DFP Ad Settings', 'wp-dfp' ), __( 'DFP Ad Settings' ), 'manage_options', 'wp_dfp_settings', array( $c, 'settings_page' ) );
	}

	/**
	 * Saves the plugin settings
	 *
	 * @since 1.0
	 * @action admin_init
	 */
	public static function save_settings() {
		if ( !isset( $_POST[ self::NONCE_NAME ] ) ) {
			return;
		}

		check_admin_referer( self::NONCE_ACTION, self::NONCE_NAME );
		update_option( self::OPTION_NAME, array_map( 'trim', $_POST[ self::OPTION_NAME ] ) );

		// Redirect per best practices
		$url = add_query_arg( self::SETTINGS_SAVED_ARG, 1, wp_dfp_settings_url( 'settings_saved' ) );
		wp_redirect( $url );
		exit;
	}

	/**
	 * Renders the settings page
	 *
	 * @since 1.0
	 * @see self::admin_menu_items()
	 */
	public static function settings_page() {
		?>
		<div class="wrap">
			<h2><?php _e( 'DFP Ad Settings', 'wp-dfp' ); ?></h2>
			<?php self::render_form(); ?>
		</div>
		<?php
	}

	/**
	 * Gets a setting value
	 *
	 * @since 1.0
	 *
	 * @param  string $setting  Optional. The setting to get. Defaults to an array of ALL settings.
	 * @param  mixed  $default  The value to be returned in the event that $setting doesn't exist.
	 * @return mixed            The value of the requested setting. If the setting could not be found, $default will be returned.
	 */
	public static function get( $setting = null, $default = false ) {
		self::$values = get_option( self::OPTION_NAME, array() );

		if ( is_null( $setting ) ) {
			return self::$values;
		}

		return isset( self::$values[ $setting ] ) ? self::$values[ $setting ] : $default;
  }

	/**
	 * Renders the settings form
	 *
	 * @since 1.0
	 */
	public static function render_form() {
		self::form();
		self::get();
		echo '<form method="post">' . WP_Forms_API::render_form( self::$form, self::$values ) . '</form>';
	}

	/**
	 * Generates the settings form markup
	 *
	 * @since 1.0
	 * @access protected
	 */
	protected static function form() {
		self::$form = array(
			'wp_dfp_nonce' => array(
				'#type'   => 'markup',
				'#markup' => wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME, true, false ),
			),
			'network_code' => array(
				'#type'        => 'text',
				'#name'        => self::input_name( 'network_code' ),
				'#label'       => __( 'Network Code', 'wp-dfp' ),
				'#description' => __( 'Your network code as found in your DFP account.', 'wp-dfp' ),
			),
			'slot_prefix' => array(
				'#type'        => 'text',
				'#name'        => self::input_name( 'slot_prefix' ),
				'#label'       => __( 'Slot Prefix', 'wp-dfp' ),
				'#description' => __( 'If you would like, you can specifiy a prefix that will be added to every slot size', 'wp-dfp' ),
			),
			'submit' => array(
				'#type' => 'markup',
				'#markup' => get_submit_button(),
			),
		);
  	}

  	/**
  	 * Gets a prefixed input name
  	 *
  	 * @since 1.0
  	 * @access protected
  	 *
  	 * @param  string $name An un-prefixed input name.
  	 * @return string The prefixed input name.
  	 */
  	protected static function input_name( $name ) {
  		return self::OPTION_NAME . '[' . $name . ']';
  	}

}

WP_DFP_Settings::init();

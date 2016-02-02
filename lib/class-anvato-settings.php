<?php

/**
 * Anvato Settings
 */

if ( ! class_exists( 'Anvato_Settings' ) ) :

class Anvato_Settings {

	public $options_capability = 'manage_options';
	public $options = array();

	const SLUG = 'anvato';

	protected static $instance;

	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new Anvato_Settings;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	protected function __construct() {
		/** Don't do anything **/
	}

	protected function setup_actions() {
		add_action( 'admin_init', array( self::$instance, 'action_admin_init' ) );
		add_action( 'admin_menu', array( self::$instance, 'action_admin_menu' ) );
	}

	public function get_options() {
		if ( empty( $this->options ) ) {
			$this->options = get_option( self::SLUG );
		}
		return $this->options;
	}

	public function get_option( $key ) {
		$this->get_options();
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : null;
	}

	public function action_admin_init() {
		register_setting( self::SLUG, self::SLUG, array( self::$instance, 'sanitize_options' ) );
		add_settings_section( 'general', false, '__return_false', self::SLUG );
		add_settings_field( 'mcp_url', __( 'MCP URL:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'mcp_url' ) );
		add_settings_field( 'mcp_id', __( 'MCP ID:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'mcp_id' ) );
		add_settings_field( 'profile', __( 'Profile:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'profile' ) );
		add_settings_field( 'station_id', __( 'Station ID:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'station_id' ) );
		add_settings_field( 'player_url', __( 'Player URL:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'player_url' ) );
		add_settings_field( 'tracker_id', __( 'Analytics Tracker ID:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'tracker_id' ) );
		add_settings_field( 'adtag', __( 'Default Adtag:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'adtag' ) );
		add_settings_field( 'adobe_profile', __( 'Adobe Analytics Profile:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_profile' ) );
		add_settings_field( 'adobe_account', __( 'Adobe Analytics Account:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_account' ) );
		add_settings_field( 'adobe_trackingserver', __( 'Adobe Analytics Tracking Server:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_trackingserver' ) );
		add_settings_field( 'width', __( 'Default Width:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'width' ) );
		add_settings_field( 'height', __( 'Default Height:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'height' ) );
		add_settings_field( 'html5', __( 'Use HTML5 Player:', 'anvato' ), array( self::$instance, 'default_true_checkbox' ), self::SLUG, 'general', array( 'field' => 'html5' ) );
		add_settings_field( 'public_key', __( 'Public Key:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'public_key' ) );
		add_settings_field( 'private_key', __( 'Private Key:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'private_key', 'type' => 'password' ) );
	}

	public function action_admin_menu() {
		add_options_page( __( 'Anvato', 'anvato' ), __( 'Anvato', 'anvato' ), $this->options_capability, self::SLUG, array( self::$instance, 'view_settings_page' ) );
	}

	public function field( $args ) {
		$args = wp_parse_args( $args, array(
			'type' => 'text',
		) );

		if ( empty( $args['field'] ) ) {
			return;
		}

		printf( '<input type="%s" name="%s[%s]" value="%s" size="50" />', esc_attr( $args['type'] ), esc_attr( self::SLUG ), esc_attr( $args['field'] ), esc_attr( $this->get_option( $args['field'] ) ) );
	}

	public function default_true_checkbox( $args ) {
		if ( empty( $args['field'] ) ) {
			return;
		}

		$current = $this->get_option( $args['field'] );

		if ( null === $current ) {
			$current = true;
		}

		printf( '<input type="checkbox" name="%s[%s]" value="1" %s />', esc_attr( self::SLUG ), esc_attr( $args['field'] ), esc_attr( checked( $current, true, false ) ) );
	}

	public function sanitize_options( $in ) {
		/**
		 * Anvato Sanitized Options
		 *
		 * Modify the options that are saved by Anvato. The default options
		 * cannot be overwritten.
		 *
		 * @var $options array Options to be sanitized and saved
		 * @return array
		 */
		$out = (array) apply_filters( 'anvato_settings_sanitize_options', array(), $in );

		$out['mcp_url']              = sanitize_text_field( $in['mcp_url'] );
		$out['mcp_id']               = sanitize_text_field( $in['mcp_id'] );
		$out['profile']              = sanitize_text_field( $in['profile'] );
		$out['station_id']           = sanitize_text_field( $in['station_id'] );
		$out['player_url']           = esc_url_raw( $in['player_url'] );
		$out['tracker_id']           = sanitize_text_field( $in['tracker_id'] );
		$out['adtag']                = sanitize_text_field( $in['adtag'] );
		$out['adobe_profile']        = sanitize_text_field( $in['adobe_profile'] );
		$out['adobe_account']        = sanitize_text_field( $in['adobe_account'] );
		// Not stored as a full URL.
		$out['adobe_trackingserver'] = sanitize_text_field( $in['adobe_trackingserver'] );
		$out['width']                = sanitize_text_field( $in['width'] );
		$out['height']               = sanitize_text_field( $in['height'] );
		$out['html5']                = ( '1' === $in['html5'] );
		$out['public_key']           = sanitize_text_field( $in['public_key'] );
		$out['private_key']          = sanitize_text_field( $in['private_key'] );

		return $out;
	}

	public function view_settings_page() {
	?>
	<div class="wrap">
		<h2><img src="<?php echo esc_url( ANVATO_URL . 'img/logo.png' ) ?>" alt="<?php esc_attr_e( 'Anvato Video Plugin Settings', 'anvato' ); ?>" /></h2>
		<form action="options.php" method="POST">
			<?php settings_fields( self::SLUG ); ?>
			<?php do_settings_sections( self::SLUG ); ?>
			<?php submit_button(); ?>
		</form>
	</div>
	<?php
	}
}

function Anvato_Settings() {
	return Anvato_Settings::instance();
}
add_action( 'after_setup_theme', 'Anvato_Settings' );

endif;

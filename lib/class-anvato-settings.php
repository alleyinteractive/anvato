<?php
/**
 * Anvato Settings
 *
 * @package Anvato
 */
class Anvato_Settings {
	/**
	 * Capability to update Anvato
	 *
	 * @var string
	 */
	public $options_capability = 'manage_options';

	/**
	 * Options storage
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Options key
	 *
	 * @var string
	 */
	const SLUG = 'anvato';

	/**
	 * Instance storage
	 *
	 * @var Anvato_Settings
	 */
	protected static $instance;

	/**
	 * Initialize the settings
	 */
	protected function __construct() {
		add_action( 'admin_init', array( $this, 'action_admin_init' ) );
		add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
	}

	/**
	 * Retrieve the static instance
	 *
	 * @return Anvato_Settings
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Retrieve all of the options for Anvato
	 *
	 * @return array
	 */
	public function get_options() {
		if ( empty( $this->options ) ) {
			$this->options = get_option( self::SLUG );
		}
		return $this->options;
	}

	/**
	 * Retrieve a specific Anvato option
	 *
	 * @param  string $key Option key.
	 * @return mixed
	 */
	public function get_option( $key ) {
		$this->get_options();
		$value = ( ! empty( $this->options[ $key ] ) ) ? $this->options[ $key ] : null;

		/**
		 * Modify the Anvato option value
		 *
		 * Filter the value of the Anvato option. The dynamic portion of the hook name,
		 * `$key`, refers to the key of the option that is being requested.
		 *
		 * @since  0.1.0
		 *
		 * @param  string $value The value of the Anvato option
		 * @return string
		 */
		return apply_filters( 'anvato_option_' . $key, $value );
	}

	/**
	 * Determine if the option exists and is not empty.
	 *
	 * @param string $key Option key.
	 * @return bool
	 */
	public function has_option( $key ) {
		// Run it through `Anvato_Settings::get_option()` to ensure the filter is applied.
		$value = $this->get_option( $key );
		return ! empty( $value );
	}

	/**
	 * Register Anvato settings
	 */
	public function action_admin_init() {
		register_setting( self::SLUG, self::SLUG, array( $this, 'sanitize_options' ) );
		add_settings_section( 'general', false, '__return_false', self::SLUG );
		add_settings_field( 'mcp_url', __( 'MCP URL:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'mcp_url' ) );
		add_settings_field( 'mcp_id', __( 'MCP ID:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'mcp_id' ) );
		add_settings_field( 'profile', __( 'Profile:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'profile' ) );
		add_settings_field( 'station_id', __( 'Station ID:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'station_id' ) );
		add_settings_field( 'player_url', __( 'Player URL:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'player_url' ) );
		add_settings_field( 'tracker_id', __( 'Analytics Tracker ID:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'tracker_id' ) );
		add_settings_field( 'adtag', __( 'Default Adtag:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'adtag' ) );
		add_settings_field( 'advanced_targeting', __( 'DFP Advanced Targeting:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'advanced_targeting', 'type' => 'textarea' ) );
		add_settings_field( 'adobe_profile', __( 'Adobe Analytics Profile:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_profile' ) );
		add_settings_field( 'adobe_account', __( 'Adobe Analytics Account:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_account' ) );
		add_settings_field( 'adobe_trackingserver', __( 'Adobe Analytics Tracking Server:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'adobe_trackingserver' ) );
		add_settings_field( 'width', __( 'Default Width:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'width' ) );
		add_settings_field( 'height', __( 'Default Height:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'height' ) );

		/**
		 * Determine if the public/private key settings should be registered.
		 *
		 * @param $register bool Whether to register the public/private key settings for
		 *     Anvato or not.
		 */
		if ( apply_filters( 'anvato_register_key_settings', true ) ) {
			add_settings_field( 'public_key', __( 'Public Key:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'public_key' ) );
			add_settings_field( 'private_key', __( 'Private Key:', 'anvato' ), array( self::$instance, 'field' ), self::SLUG, 'general', array( 'field' => 'private_key', 'type' => 'password' ) );
		}

		add_settings_field( 'default_share_link', __( 'Default Share Link:', 'anvato' ), array( $this, 'field' ), self::SLUG, 'general', array( 'field' => 'default_share_link' ) );
	}

	/**
	 * Register the Anvato menu page
	 */
	public function action_admin_menu() {
		add_options_page( __( 'Anvato', 'anvato' ), __( 'Anvato', 'anvato' ), $this->options_capability, self::SLUG, array( $this, 'view_settings_page' ) );
	}

	/**
	 * Render the settings field
	 *
	 * @param array Arguments for the field
	 */
	public function field( $args ) {
		$args = wp_parse_args( $args, array(
			'type' => 'text',
			'rows' => 2,
			'cols' => 48,
		) );

		if ( empty( $args['field'] ) ) {
			return;
		}

		switch ( $args['type'] ) {
			case 'textarea' :
				printf( '<textarea name="%s[%s]" rows="%d" cols="%d">%s</textarea>', esc_attr( self::SLUG ), esc_attr( $args['field'] ), intval( $args['rows'] ), intval( $args['cols'] ), esc_attr( $this->get_option( $args['field'] ) ) );
				break;

			default :
				printf( '<input type="%s" name="%s[%s]" value="%s" size="50" />', esc_attr( $args['type'] ), esc_attr( self::SLUG ), esc_attr( $args['field'] ), esc_attr( $this->get_option( $args['field'] ) ) );
				break;
		}
	}

	/**
	 * Sanitize options
	 *
	 * @param  array $in Unsanitized input of options.
	 * @return array
	 */
	public function sanitize_options( $in ) {
		$out['mcp_url']              = sanitize_text_field( $in['mcp_url'] );
		$out['mcp_id']               = sanitize_text_field( $in['mcp_id'] );
		$out['profile']              = sanitize_text_field( $in['profile'] );
		$out['station_id']           = sanitize_text_field( $in['station_id'] );
		$out['player_url']           = esc_url_raw( $in['player_url'] );
		$out['tracker_id']           = sanitize_text_field( $in['tracker_id'] );
		$out['adtag']                = sanitize_text_field( $in['adtag'] );
		$out['advanced_targeting']   = sanitize_text_field( $in['advanced_targeting'] );
		$out['adobe_profile']        = sanitize_text_field( $in['adobe_profile'] );
		$out['adobe_account']        = sanitize_text_field( $in['adobe_account'] );

		// Not stored as a full URL.
		$out['adobe_trackingserver'] = sanitize_text_field( $in['adobe_trackingserver'] );
		$out['width']                = sanitize_text_field( $in['width'] );
		$out['height']               = sanitize_text_field( $in['height'] );
		$out['default_share_link']   = sanitize_text_field( $in['default_share_link'] );

		if ( isset( $in['public_key'] ) ) {
			$out['public_key']       = sanitize_text_field( $in['public_key'] );
		}

		if ( isset( $in['private_key'] ) ) {
			$out['private_key']      = sanitize_text_field( $in['private_key'] );
		}

		return $out;
	}

	/**
	 * Render the settings page
	 */
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

/**
 * Retrieve Anvato Settings instance
 *
 * @return Anvato_Settings
 */
function Anvato_Settings() {
	return Anvato_Settings::instance();
}
add_action( 'after_setup_theme', 'Anvato_Settings' );

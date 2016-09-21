<?php
/**
 * Get data about videos using the Anvato API.
 *
 * @package Anvato
 */
class Anvato_Library {

	/**
	 * printf()-friendly string for querying the Anvato API.
	 *
	 * The arguments are the MCP URL, a timestamp, a unique signature, the
	 * public key, and optional parameters.
	 *
	 * @see $this->build_request_url().
	 * @var string.
	 */
	private $api_request_url = '%s/api?ts=%d&sgn=%s&id=%s&%s';

	/**
	 * API calls
	 *
	 * Use a respective key here as the `$type` parameter to {@see Anvato_Library::search()}.
	 *
	 * @var array
	 */
	private $api_methods = array(
		'categories' => 'list_categories',
		'live'       => 'list_embeddable_channels',
		'playlist'   => 'list_playlists',
		'vod'        => 'list_videos',
	);

	/**
	 * The value of the plugin settings on instantiation.
	 *
	 * @var array.
	 */
	private $option_values;

	/**
	 * The body of the XML request to send to the API.
	 *
	 * @var string.
	 */
	private $xml_body;

	/**
	 * Instance of this class.
	 *
	 * @var Anvato_Library
	 */
	protected static $instance;

	/**
	 * Instance of the Anvato_Settings class
	 *
	 * @var Anvato_Settings
	 */
	protected $settings;

	/**
	 * Initialize the class.
	 */
	protected function __construct() {
		$this->option_values = get_option( Anvato_Settings::SLUG );
		$this->settings = Anvato_Settings::instance();
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	/**
	 * Check whether the settings required for using the API are set.
	 *
	 * @return boolean
	 */
	public function has_required_settings() {
		return ( $this->settings->has_option( 'mcp_url' ) && $this->settings->has_option( 'public_key' ) && $this->settings->has_option( 'private_key' ) );
	}

	/**
	 * Create the unique signature for a request.
	 *
	 * @see  $this->build_request_url() for detail about the timestamp.
	 *
	 * @param  int $time UNIX timestamp of the request.
	 * @return string.
	 */
	private function build_request_signature( $time ) {
		return base64_encode( hash_hmac( 'sha256', $this->xml_body . $time, $this->settings->get_option( 'private_key' ), true ) );
	}

	/**
	 * Set up the filtering conditions to use as part of a search of the library.
	 *
	 * @param array $args {
	 *     @type string $lk Video title search keyword.
	 *     @type string $exp_date Used for video search, if set result includes videos that expire later than this date.
	 *     @type int $page_no page Offset starting with 1.
	 *     @type int $category_id MCP API filter for video list. Only videos with this category id will be returned.
	 *     @type int $video_id MCP API filter for video list. Only video with this video id will be returned.
	 *     @type int $program_id MCP API filter for videos in a program. Only videos with this program id will be returned
	 *     @type bool $published_only MCP API filter for video list. Only published videos will be returned.
	 * }
	 * @return array.
	 */
	private function build_request_parameters( $args = array() ) {
		$params = array();

		if ( isset( $args['lk'] ) ) {
			$params['filter_by'][] = 'name';
			$params['filter_cond'][] = 'lk';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['lk'] ) );
		}

		if ( isset( $args['exp_date'] ) ) {
			$params['filter_by'][] = 'exp_date';
			$params['filter_cond'][] = 'ge';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['exp_date'] ) );
		}

		if ( isset( $args['added_date'] ) ) {
			$params['filter_by'][] = 'added_date';
			$params['filter_cond'][] = 'ge';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['added_date'] ) );
		}

		if ( isset( $args['page_no'] ) ) {
			$params['page_no'] = (int) $args['page_no'];
		}

		if ( isset( $args['page_sz'] ) ) {
			$params['page_sz'] = absint( $args['page_sz'] );
		}

		if ( isset( $args['category_id'] ) ) {
			$params['filter_by'][] = 'category_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['category_id'] ) );
		}

		if ( isset( $args['video_id'] ) ) {
			$params['filter_by'][] = 'video_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['video_id'] ) );
		}

		if ( isset( $args['program_id'] ) ) {
			$params['filter_by'][] = 'program_id';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = rawurlencode( sanitize_text_field( $args['program_id'] ) );
		}

		if ( isset( $args['published_only'] ) && $args['published_only'] ) {
			$params['filter_by'][] = 'published';
			$params['filter_cond'][] = 'eq';
			$params['filter_value'][] = 'true';
		}

		return $params;
	}

	/**
	 * Construct the URL to send to the API.
	 *
	 * @see  $this->api_request_url for detail about the URL.
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param array $params Search parameters.
	 * @param int $time The UNIX timestamp of the request. Passed to the
	 *     function because the same timestamp is needed more than once.
	 * @return string The URL after formatting with sprintf().
	 */
	private function build_request_url( $params = array(), $time ) {
		return sprintf(
			$this->api_request_url,
			esc_url( $this->settings->get_option( 'mcp_url' ) ),
			$time,
			urlencode( $this->build_request_signature( $time ) ),
			$this->settings->get_option( 'public_key' ),
			build_query( $params )
		);
	}

	/**
	 * Check whether the Anvato API reported an unsuccessful request.
	 *
	 * @param array $response The response array from wp_remote_get().
	 * @return boolean.
	 */
	private function is_api_error( $response ) {
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
		if ( is_object( $xml ) ) {
			return 'failure' == $xml->result;
		} else {
			return true;
		}
	}

	/**
	 * Get the error message from the Anvato API during an unsuccessful request.
	 *
	 * @param array $response The response array from wp_remote_get().
	 * @return string The message.
	 */
	private function get_api_error( $response ) {
		$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
		if ( is_object( $xml ) && ! empty( $xml->comment ) ) {
			return sprintf( __( '"%s"', 'anvato' ), esc_html( $xml->comment ) );
		} else {
			// Intentionally uncapitalized.
			return __( 'no error message provided', 'anvato' );
		}
	}

	/**
	 * Request data from the Anvato API.
	 *
	 * @uses  vip_safe_wp_remote_get() if available.
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param array $params Search parameters.
	 * @param string $api_method API Method to call.
	 * @return string|WP_Error String of XML of success, or WP_Error on failure.
	 */
	private function request( $params, $api_method ) {
		if ( ! $this->has_required_settings() ) {
			return new WP_Error( 'missing_required_settings', __( 'The MCP URL, Public Key and Private Key settings are required.', 'anvato' ) );
		}

		$this->set_xml_body( $api_method );
		$response = wp_remote_post( $this->build_request_url( $params, time() ), array( 'body' => sprintf( $this->xml_body, $api_method ) ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( 200 === wp_remote_retrieve_response_code( $response ) ) {
			if ( $this->is_api_error( $response ) ) {
				return new WP_Error( 'api_error', sprintf( __( 'Anvato responded with an error (%s).', 'anvato' ), $this->get_api_error( $response ) ) );
			} else {
				return $response;
			}
		} else {
			return new WP_Error( 'request_unsuccessful', __( 'There was an error contacting Anvato.', 'anvato' ) );
		}
	}

	/**
	 * Search the library for videos.
	 *
	 * @see  $this->build_request_parameters() for allowed search parameters.
	 *
	 * @param  array $args Search parameters.
	 * @param  string Request type {@see Anvato_Library::$api_methods}
	 * @return array|WP_Error Array with SimpleXMLElements of any videos found, or WP_Error on failure.
	 */
	public function search( $args = array(), $type = 'vod' ) {
		if ( empty( $this->api_methods[ $type ] ) ) {
			return new WP_Error( 'anvato', sprintf( __( 'Unknown API call: %s', 'anvato' ), $type ) );
		}

		$api_method = $this->api_methods[ $type ];

		if ( 'list_videos' === $api_method ) {
			$args['published_only'] = true;
		}

		$response = $this->request( $this->build_request_parameters( $args ), $api_method );
		if ( is_wp_error( $response ) ) {
			$data = $response;
		} else {
			$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
			if ( ! is_object( $xml ) ) {
				$data = new WP_Error( 'parse_error', __( 'There was an error processing the search results.', 'anvato' ) );
			} else {
				switch ( $api_method ) {
					case 'list_categories':
						$data = $xml->params->category_list->xpath( '//category' );
						break;

					case 'list_embeddable_channels':
						$data = $xml->params->channel_list->xpath( '//channel' );
						break;

					case 'list_playlists':
						$data = $xml->params->video_list->xpath( '//playlist' );
						break;

					case 'list_videos':
						$data = $xml->params->video_list->xpath( '//video' );
						break;
				}
			}
		}

		/**
		 * Fires after a search of the Anvato library.
		 *
		 * @param array|WP_Error $videos Array of SimpleXMLElement videos or WP_Error.
		 * @param string $api_method API Method requested {@see Anvato_Library::$api_methods}
		 */
		do_action( 'anvato_library_after_search_' . $api_method, $data, $api_method );

		/**
		 * Fires after a search of the Anvato library.
		 *
		 * @param array|WP_Error $videos Array of SimpleXMLElement videos or WP_Error.
		 * @param string $api_method API Method requested {@see Anvato_Library::$api_methods}
		 */
		do_action( 'anvato_library_after_search', $data, $api_method );

		return $data;
	}

	/**
	 * Update the XML Body with the current method being used
	 *
	 * @param string $method
	 */
	protected function set_xml_body( $method ) {
		$this->xml_body = sprintf( '<?xml version="1.0" encoding="utf-8"?><request><type>%s</type><params></params></request>', $method );
	}
}

/**
 * Helper function to use the class instance.
 *
 * @return Anvato_Library
 */
function Anvato_Library() {
	return Anvato_Library::get_instance();
}

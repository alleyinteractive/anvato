<?php
/**
 * Get data about videos using the Anvato API.
 *
 * @package
 */
class Anvato_Library {

	/**
	 * printf()-friendly string for querying the Anvato API.
	 *
	 * The arguments are the MCP URL, a timestamp, a unique signature, the
	 * public key, and optional parameters.
	 *
	 * @see $this->build_request_url().
	 *
	 * @var string.
	 */
	private $api_request_url = '%s/api?ts=%d&sgn=%s&id=%s&%s';

	/**
	 * The body of the XML request to send to the API.
	 *
	 * @todo Possibly convert to a printf()-friendly string for substituting
	 *     "list_groups" for "list_videos.""
	 *
	 * @var string.
	 */
	private $xml_body = "<?xml version=\"1.0\" encoding=\"utf-8\"?>
		<request>
			<type>list_videos</type>
			<params></params>
		</request>";

	/**
	 * Instance of this class.
	 *
	 * @var object.
	 */
	protected static $instance = null;

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
	 * Retrieve a stored option value
	 *
	 * @param string $key The key of the option to retrieve
	 * @return string
	 */
	public function get_option( $key ) {
		return Anvato_Settings::instance()->get_option( $key );
	}

	/**
	 * Check whether the settings required for using the API are set.
	 *
	 * @return boolean.
	 */
	public function has_required_settings() {
		return ! ( empty( $this->option_values ) || false !== array_search( '', array( $this->get_option( 'mcp_url' ), $this->get_option( 'public_key' ), $this->get_option( 'private_key' ) ) ) );
	}

	/**
	 * Create the unique signature for a request.
	 *
	 * @see  $this->build_request_url() for detail about the timestamp.
	 *
	 * @param  int $time UNIX timestamp of the request.
	 * @return string.
	 */
	private function build_request_signature( $time, $private_key ) {
		return base64_encode( hash_hmac( 'sha256', $this->xml_body . $time, $private_key, true ) );
	}

	/**
	 * Set up the filtering conditions to use as part of a search of the library.
	 *
	 * @param array $args {
	 *		@type string $lk Search keyword.
	 * }
	 * @return array.
	 */
	private function build_request_params( $args = array() ) {
		$params = array();

		foreach ( $args as $key => $value ) {
			switch ( $key ) {
				case 'lk' :
					$params['filter_by'][] = 'name';
					$params['filter_cond'][] = 'lk';
					$params['filter_value'][] = sanitize_text_field( $value );
				break;
			}
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
	private function build_request_url( $params = array(), $time, $public_key, $private_key ) {
		return sprintf(
			$this->api_request_url,
			esc_url( $this->option_values['mcp_url'] ),
			$time,
			urlencode( $this->build_request_signature( $time, $private_key ) ),
			$public_key,
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
	 * @return string|WP_Error String of XML of success, or WP_Error on failure.
	 */
	private function request( $params, $time, $public_key, $private_key ) {
		if ( ! $this->has_required_settings() ) {
			return new WP_Error( 'missing_required_settings', __( 'The MCP URL, Public Key, and Private Key settings are required.', 'anvato' ) );
		}

		$url = $this->build_request_url( $params, $time, $public_key, $private_key );
		$args = array( 'body' => $this->xml_body );
		if ( function_exists( 'vip_safe_wp_remote_get' ) ) {
			$response = vip_safe_wp_remote_get( $url, false, 3, 2, 20, $args );
		} else {
			$response = wp_remote_get( $url, $args );
		}

		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( wp_remote_retrieve_response_code( $response ) === 200 ) {
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
	 * @return array|WP_Error Array with SimpleXMLElements of any videos found, or WP_Error on failure.
	 */
	public function search( $args = array() ) {
		$defaults = array(
			'lk' => '',
		);
		$args = wp_parse_args( $args, $defaults );

		/**
		 * Filter the Anvato Library search arguments.
		 *
		 * Tranform the search arguments passed to the Anvato Library when retrieving
		 * videos to display inside of the media explorer.
		 *
		 * @since 0.1.0
		 *
		 * @param array $args Arguments to be passed to the Anvato Library
		 */
		$args = apply_filters( 'anvato_search_args', $args );

		/**
		 * Filter the public key used to search the Anvato library.
		 *
		 * @since
		 *
		 * @param string $public_key
		 * @param array $args
		 */
		$public_key = apply_filters( 'anvato_search_public_key', $this->get_option( 'public_key' ), $args );

		/**
		 * Filter the private key used to search the Anvato library.
		 *
		 * @since
		 *
		 * @param string $private_key
		 * @param array $args
		 */
		$private_key = apply_filters( 'anvato_search_private_key', $this->get_option( 'private_key' ), $args );

		$response = $this->request( $this->build_request_params( $args ), time(), $public_key, $private_key );

		if ( is_wp_error( $response ) ) {
			$videos = $response;
		} else {
			$xml = simplexml_load_string( wp_remote_retrieve_body( $response ) );
			if ( is_object( $xml ) ) {
				$videos = $xml->params->video_list->xpath( '//video' );
			} else {
				$videos = new WP_Error( 'parse_error', __( 'There was an error processing the search results.', 'anvato' ) );
			}
		}

		/**
		 * Fires after a search of the Anvato library.
		 *
		 * @param array|WP_Error $videos Array of SimpleXMLElement videos or WP_Error.
		 */
		do_action( 'anvato_library_after_search', $videos );

		return $videos;
	}
}

/**
 * Helper function to use the class instance.
 *
 * @return object.
 */
function Anvato_Library() {
	return Anvato_Library::get_instance();
}

<?php
/**
 * Anvato service for Media Explorer.
 */
class MEXP_Anvato_Service extends MEXP_Service {

	/**
	 * The values of the Anvato plugin option on instantiation.
	 *
	 * @var array
	 */
	private $option_values;

	/**
	 * Constructor.
	 *
	 * Creates the Backbone view template.
	 */
	public function __construct() {
		$this->option_values = get_option( Anvato_Settings::SLUG );
		$this->set_template( new MEXP_Anvato_Template );
	}

	/**
	 * Fired when the Anvato service is loaded.
	 *
	 * Allows the service to enqueue JS/CSS only when it's required.
	 */
	public function load() {
		add_filter( 'mexp_tabs',   array( $this, 'tabs' ),   10, 1 );
		add_filter( 'mexp_labels', array( $this, 'labels' ), 10, 1 );
		wp_enqueue_style( 'mexp-service-anvato', ANVATO_URL . 'mexp/style.css', array( 'mexp' ), '0.1' );
	}

	/**
	 * Handles the AJAX request for videos and returns an appropriate response.
	 *
	 * @see  Anvato_Library::search() for documentation of request parameters
	 *     and returned video data.
	 *
	 * @param array $request The request parameters.
	 * @return MEXP_Response|bool|WP_Error A MEXP_Response object should be
	 *     returned on success, boolean false should be returned if there are no
	 *     results to show, and a WP_Error should be returned if there is an
	 *     error.
	 */
	public function request( array $request ) {
		$params = array();
		if ( ! empty( $request['params']['q'] ) ) {
			$params['lk'] = sanitize_text_field( $request['params']['q'] );
		}

		/**
		 * Handle the search parameters passed to {@see Anvato_Library::search()}.
		 *
		 * @param array $params Search parameters. {@see Anvato_Library::search()}.
		 */
		apply_filters( 'anvato_search_params', $params, $this, $request );

		$results = Anvato_Library()->search( $params );

		if ( is_wp_error( $results ) ) {
			return $results;
		} elseif ( empty( $results ) ) {
			return false;
		}

		/**
		 * Filter the raw search results from the Anvato library.
		 *
		 * @param array $results Array with SimpleXMLElements of videos.
		 * @param array $params Search parameters. {@see Anvato_Library::search()}.
		 */
		$results = apply_filters( 'anvato_mexp_request_results', $results, $params );

		if ( isset( $request['params']['max_results'] ) && 0 !== absint( $request['params']['max_results'] ) ) {
			$results = array_slice( $results, 0, absint( $request['params']['max_results'] ), true );
		}

		$response = new MEXP_Response();
		foreach ( $results as $video ) {
			$item = new MEXP_Response_Item();
			$item->set_content( sanitize_text_field( $video->title->__toString() ) );
			$item->set_date( strtotime( sanitize_text_field( $video->ts_added->__toString() ) ) );
			$item->set_date_format( sprintf( __( '%s @ %s', 'anvato' ), get_option( 'date_format' ), get_option( 'time_format' ) ) );
			$item->set_id( intval( $video->upload_id->__toString() ) );
			$item->set_thumbnail( $video->src_image_url->__toString() );
			$item->url = anvato_generate_shortcode( $video->upload_id->__toString() );

			/**
			 * Filter the video item to be added to the response.
			 *
			 * @param  MEXP_Response_Item $item The response item.
			 * @param  SimpleXMLElement $video The XML for the video from the API.
			 * @param  array $results Array with SimpleXMLElements of videos.
			 * @param  array $params Search parameters.
			 */
			$response->add_item( apply_filters( 'anvato_mexp_response_item', $item, $video, $results, $params ) );
		}
		return $response;
	}

	/**
	 * Returns an array of tabs for the Anvato service's media manager panel.
	 *
	 * @param array $tabs Associative array of default tab items.
	 * @return array Associative array of tabs. The key is the tab ID and the value is an array of tab attributes.
	 */
	public function tabs( array $tabs ) {
		$tabs['anvato'] = array(
			'all' => array(
				'defaultTab' => true,
				'text' => _x( 'All', 'Tab title', 'anvato' ),
				'fetchOnRender' => true,
			),
		);

		return $tabs;
	}

	/**
	 * Returns an array of custom text labels for the Anvato service.
	 *
	 * @param array $labels Associative array of default labels.
	 * @return array Associative array of labels.
	 */
	public function labels( array $labels ) {
		$labels['anvato'] = array(
			'insert'    => __( 'Insert Video', 'anvato' ),
			'noresults' => __( 'No videos matched your search query.', 'anvato' ),
			'title'     => __( 'Insert Anvato Video', 'anvato' ),
			'loadmore'  => __( 'Load more videos', 'anvato' ),
		);

		return $labels;
	}

}

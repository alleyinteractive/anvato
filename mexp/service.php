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
		wp_enqueue_style( 'mexp-service-anvato', ANVATO_URL . 'mexp/style.css', array( 'mexp' ), '0.1.1' );
	}

	/**
	 * Handles the AJAX request for videos and returns an appropriate response.
	 *
	 * @see  Anvato_Library::search() for documentation of request parameters
	 *     and returned video data.
	 *
	 * @param array $request The request parameters.
	 * @return mixed A MEXP_Response object should be
	 *     returned on success, boolean false should be returned if there are no
	 *     results to show and a WP_Error should be returned if there was an
	 *     error.
	 */
	public function request( array $request ) {
		$type = ( ! empty( $request['params']['type'] ) ) ? sanitize_text_field( $request['params']['type'] ) : 'vod';
		$params = array(
			'page_no' => ( ! empty( $request['page'] ) && intval( $request['page'] ) > 0 ) ? intval( $request['page'] ) : 1,
		);

		if ( ! empty( $request['params']['q'] ) ) {
			$params['lk'] = sanitize_text_field( $request['params']['q'] );
		} else {
			/**
			 * Pass in an `added_date` filter to optimize the results to videos
			 * posted in the last 30 days if they aren't searching by title.
			 */
			$params['added_date'] = date( 'F d, Y', current_time( 'timestamp' ) - ( DAY_IN_SECONDS * 30 ) );
		}

		if ( ! empty( $request['params']['max_results'] ) ) {
			$max_results = absint( $request['params']['max_results'] );

			// Default back to 25 per page.
			if ( $max_results > 50 || $max_results < 1 ) {
				$max_results = 25;
			}

			$params['page_sz'] = $max_results;
		}

		/**
		 * Handle the search parameters passed to {@see Anvato_Library::search()}.
		 *
		 * @param array $params Search parameters. {@see Anvato_Library::search()}.
		 * @param MEXP_Anvato_Service $this Anvato Service Object.
		 * @param array $request Request parameters.
		 * @return array|WP_Error Search parameters or `WP_Error` if there was an
		 *     error with the request.
		 */
		$params = apply_filters( 'anvato_search_request_mexp', $params, $this, $request );
		if ( is_wp_error( $params ) ) {
			return $params;
		}

		$results = Anvato_Library()->search( $params, $type );

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

		switch ( $type ) {
			case 'playlist' :
				return $this->get_playlist_response( $params, $results );

			case 'live' :
				return $this->get_live_response( $params, $results );

			default :
				return $this->get_vod_response( $params, $results );
		}
	}

	/**
	 * Build a response for a Video on Demand request
	 *
	 * @param array $params Search parameters. {@see Anvato_Library::search()}.
	 * @param array $results Array with SimpleXMLElements of videos.
	 * @return MEXP_Response
	 */
	protected function get_vod_response( $params, $results ) {
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
			 */
			$response->add_item( apply_filters( 'anvato_mexp_response_item', $item, $video ) );
		}
		return $response;
	}

	/**
	 * Build a response for playlist content
	 *
	 * @param array $params Search parameters. {@see Anvato_Library::search()}.
	 * @param array $results Array with SimpleXMLElements of videos.
	 * @return MEXP_Response
	 */
	protected function get_playlist_response( $params, $results ) {
		$response = new MEXP_Response();
		foreach ( $results as $playlist ) {
			$item = new MEXP_Response_Item();
			$item->set_content( sanitize_text_field( (string) $playlist->playlist_title ) );
			$item->add_meta( 'description', sanitize_text_field( wp_trim_words( (string) $playlist->description, 50, '...' ) ) );
			$item->add_meta( 'video_count', sanitize_text_field( sprintf( _n( '%s video in playlist', '%s videos in playlist', intval( $playlist->item_count ), 'anvato' ), intval( $playlist->item_count ) ) ) );
			$item->add_meta( 'type', 'playlist' );
			$item->set_id( intval( $playlist->playlist_id ) );

			$item->url = anvato_generate_shortcode( (string) $playlist->playlist_id, 'playlist' );

			/**
			 * Filter the video item to be added to the response.
			 *
			 * @param  MEXP_Response_Item $item The response item.
			 * @param  SimpleXMLElement $playlist The XML for the playlist from the API.
			 */
			$response->add_item( apply_filters( 'anvato_mexp_response_item', $item, $playlist ) );
		}

		return $response;
	}

	/**
	 * Build a response for live video content
	 *
	 * @return MEXP_Response
	 */
	protected function get_live_response( $params, $results ) {
		$response = new MEXP_Response();

		foreach ( $results as $channel ) {
			$item = new MEXP_Response_Item();
			$item->set_content( sanitize_text_field( (string) $channel->channel_name ) );
			$item->add_meta( 'category', __( 'Live Stream', 'anvato' ) );
			$item->add_meta( 'embed_id', $channel->embed_id );

			$icon_url = (string) $channel->icon_url;
			$icon_url = ( empty( $icon_url ) ) ? ANVATO_URL . 'img/channel_icon.png' : $icon_url;
			$item->set_id( (string) $channel->embed_id );
			$item->set_thumbnail( $icon_url );
			$item->url = anvato_generate_shortcode( (string) $channel->embed_id );
			$item->set_date( time() );
			$item->set_date_format( sprintf( __( '%s @ %s', 'anvato' ), get_option( 'date_format' ), get_option( 'time_format' ) ) );
			$item->add_meta( 'type', 'live' );

			/**
			 * Filter the live video channel to be added to the response.
			 *
			 * @param  MEXP_Response_Item $item The response item.
			 * @param  SimpleXMLElement $channel The XML for the channel from the API.
			 */
			$response->add_item( apply_filters( 'anvato_mexp_response_item', $item, $channel ) );

			/**
			 * Add Monetized Channels
			 */
			if ( ! empty( $channel->monetized_channels ) ) {
				foreach ( (array) $channel->monetized_channels as $mchannel ) {
					$item = new MEXP_Response_Item();
					$item->set_content( sanitize_text_field( (string) $mchannel->monetized_name ) );
					$item->add_meta( 'category', __( 'Monetized Live Stream', 'anvato' ) );
					$item->add_meta( 'embed_id', $mchannel->embed_id );
					$item->add_meta( 'type', 'live' );
					$item->set_id( (string) $mchannel->embed_id );
					$icon_url = (string) $channel->icon_url;
					$icon_url = ( empty( $icon_url ) ) ? ANVATO_URL . 'img/channel_icon.png' : $icon_url;
					$item->set_thumbnail( (string) $icon_url );
					$item->set_date( time() );
					$item->set_date_format( sprintf( __( '%s @ %s', 'anvato' ), get_option( 'date_format' ), get_option( 'time_format' ) ) );
					$item->url = anvato_generate_shortcode( (string) $mchannel->embed_id );

					/**
					 * Filter the monetized channel item to be added to the response.
					 *
					 * @param  MEXP_Response_Item $item The response item.
					 * @param  SimpleXMLElement $channel The XML for the channel from the API.
					 */
					$response->add_item( apply_filters( 'anvato_mexp_response_item', $item, $mchannel ) );
				}
			}
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

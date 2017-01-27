<?php
global $anvato_player_index, $anvato_player_data;
$anvato_player_index = 0;
$anvato_player_data = array();

/**
 * Implement the Anvato shortcode.
 *
 * @param  array $attr {
 *     Array of shortcode attributes.
 *
 *     @type  string      $video                The Anvato video ID.
 *     @type  string      $playlist             The Anvato playlist ID. If both $playlist and
 *                                              $video are present, $playlist takes precedence.
 *     @type  bool        $autoplay             Optional. Autoplay the video? Default false.
 *     @type  int         $seek_to              Optional. Start the video N seconds in.
 *     @type  bool        $adobe_analytics      Optional. Accepts false to remove all Adobe
 *                                              settings from the output.
 *     @type  string      $mcp                  Optional. Override the "mcp" setting.
 *     @type  string      $station_id           Optional. Override the "station_id" setting.
 *     @type  string      $profile              Optional. Override the "profile" setting.
 *     @type  string      $width                Optional. Override the player "width" setting.
 *     @type  string      $height               Optional. Override the player "height" setting.
 *     @type  string      $player_url           Optional. Override the "player_url" setting.
 *     @type  string|bool $plugin_dfp_adtagurl  Optional. Override the "plugin_dfp_adtagurl" setting,
 *                                              or use false to remove it from the output.
 *     @type  string|bool $tracker_id           Optional. Override the "tracker_id" setting, or use
 *                                              false to remove it from the output.
 *     @type  string|bool $adobe_profile        Optional. Override the "adobe_profile" setting, or use
 *                                              false to remove it from the output.
 *     @type  string|bool $adobe_account        Optional. Override the "adobe_account" setting, or use
 *                                              false to remove it from the output.
 *     @type  string|bool $adobe_trackingserver Optional. Override the "adobe_trackingserver" setting,
 *                                              or use false to remove it from the output.
 * }
 * @return string       HTML to replace the shortcode.
 */
function anvato_shortcode( $attr ) {
	global $anvato_player_index, $anvato_player_data;
	$settings = Anvato_Settings();

	// Set the attributes which the shortcode can override.
	$json = shortcode_atts( array(
		'mcp'        => $settings->get_option( 'mcp_id' ),
		'profile'    => $settings->get_option( 'profile' ),
		'station_id' => $settings->get_option( 'station_id' ),
		'width'      => $settings->get_option( 'width' ),
		'height'     => $settings->get_option( 'height' ),
		'video'      => null,
		'playlist'   => null,
		'autoplay'   => false,
		'sharelink'  => null,
	), $attr, 'anvplayer' );

	// Validate the video/playlist id(s).
	$video_ids = explode( ',', $json['video'] );

	if ( count( $video_ids ) > 1 ) {
		unset( $json['video'] );
		$json['playlist'] = $video_ids;
	} elseif ( ! empty( $attr['playlist'] ) ) {
		unset( $json['video'] );
		$json['playlist'] = intval( $attr['playlist'] );
	} else {
		$json['video'] = $json['video'];
	}

	// Share link
	if ( ! empty( $json['shareLink'] ) && $settings->has_option( 'default_share_link' ) ) {
		$json['shareLink'] = $settings->get_option( 'default_share_link' );
	}

	// Set other attributes that can't be overridden.
	$json['pInstance'] = 'p' . $anvato_player_index++;

	// Set the player URL, which isn't part of the JSON but can be overridden.
	$player_url = ( ! empty( $attr['player_url'] ) ) ? $attr['player_url'] : $settings->get_option( 'player_url' );

	// Set the DFP Ad Tag, which can also be overridden.
	if ( $settings->has_option( 'adtag' ) && ( ! isset( $attr['plugin_dfp_adtagurl'] ) || ( empty( $attr['plugin_dfp_adtagurl'] ) && 'false' !== $attr['plugin_dfp_adtagurl'] ) ) ) {
		$json['plugins']['dfp'] = array( 'adTagUrl' => $settings->get_option( 'adtag' ) );
	} elseif ( ! empty( $attr['plugin_dfp_adtagurl'] ) && 'false' !== $attr['plugin_dfp_adtagurl'] ) {
		$json['plugins']['dfp'] = array( 'adTagUrl' => $attr['plugin_dfp_adtagurl'] );
	}

	// DFP advanced tracking.
	if ( $settings->has_option( 'advanced_targeting' ) ) {
		$json['plugins']['dfp'] = json_decode( $settings->get_option( 'advanced_targeting' ) );
	}

	// Set the Tracker ID, which can be overridden.
	if ( ! isset( $attr['tracker_id'] ) && $settings->has_option( 'tracker_id' ) ) {
		$json['plugins']['analytics'] = array( 'pdb' => $settings->get_option( 'tracker_id' ) );
	} elseif ( isset( $attr['tracker_id'] ) && 'false' !== $attr['tracker_id'] ) {
		$json['plugins']['analytics'] = array( 'pdb' => $attr['tracker_id'] );
	}

	// Set the Adobe Analytics information, which can be overridden or canceled.
	if ( ! isset( $attr['adobe_analytics'] ) || ( isset( $attr['adobe_analytics'] ) && 'false' != $attr['adobe_analytics'] ) ) {
		if ( ! isset( $attr['adobe_profile'] ) && $settings->has_option( 'adobe_profile' ) ) {
			$json['plugins']['omniture']['profile'] = $settings->get_option( 'adobe_profile' );
		} elseif ( isset( $attr['adobe_profile'] ) && 'false' !== $attr['adobe_profile'] ) {
			$json['plugins']['omniture']['profile'] = $attr['adobe_profile'];
		}

		if ( ! isset( $attr['adobe_account'] ) && $settings->has_option( 'adobe_account' ) ) {
			$json['plugins']['omniture']['account'] = $settings->get_option( 'adobe_account' );
		} elseif ( isset( $attr['adobe_account'] ) && 'false' !== $attr['adobe_account'] ) {
			$json['plugins']['omniture']['account'] = $attr['adobe_account'];
		}

		if ( ! isset( $attr['adobe_trackingserver'] ) && $settings->has_option( 'adobe_trackingserver' ) ) {
			$json['plugins']['omniture']['trackingServer'] = $settings->get_option( 'adobe_trackingserver' );
		} elseif ( isset( $attr['adobe_trackingserver'] ) && 'false' !== $attr['adobe_trackingserver'] ) {
			$json['plugins']['omniture']['trackingServer'] = $attr['adobe_trackingserver'];
		}
	}

	// Include the "seek to" time in this instance's localized data.
	if ( isset( $attr['seek_to'] ) && is_numeric( $attr['seek_to'] ) ) {
		$anvato_player_data[ $json['pInstance'] ]['seekTo'] = absint( $attr['seek_to'] ) * 1000;
	}

	// Clean up attributes as need be.
	$json['autoplay'] = ( 'true' == $json['autoplay'] );

	// Only in video mode, not in playlist mode.
	if ( isset( $attr['no_pr'] ) && 'true' === $attr['no_pr'] && isset( $json['video'] ) ) {
		unset( $json['plugins']['dfp'] );
	}

	if ( isset( $json['video'] ) && is_string( $json['video'] ) && 'c' === substr( $json['video'], 0, 1 ) ) {
		$json['androidIntentPlayer'] = 'true';
	}

	// Ensure live video is not HTML5
	$json['html5'] = ! ( isset( $json['video'] ) && boolval( preg_match( '/\D/', $json['video'] ) ) );

	/**
	 * Determine if the current request is for AMP
	 *
	 * @see https://www.ampproject.org/
	 * @param $is_amp bool Determines if the current request is for AMP. Defaults
	 *     to false.
	 */
	$is_amp_request = apply_filters( 'anvato_is_amp_request', false );

	/**
	 * Determine if the current request is for Facebook Instant
	 *
	 * @see https://developers.facebook.com/docs/instant-articles
	 * @param $is_fbia bool Determines if the current request is for Facebook Instant.
	 *     Defaults to false.
	 */
	$is_fbia_request = apply_filters( 'anvato_is_fbia_request', false );

	// AMP/Facebook Instant request modifications.
	if ( $is_amp_request || $is_fbia_request ) {
		if ( isset( $json['video'] ) ) {
			$json['v'] = $json['video'];
			unset( $json['video'] );
		}

		if ( isset( $json['playlist'] ) ) {
			$json['pl'] = $json['playlist'];
			unset( $json['playlist'] );
		}

		$json['m'] = $json['mcp'];

		unset( $json['mcp'] );
		unset( $json['pInstance'] );

		// Ensure that height/width are pixel values.
		if ( false !== strpos( $json['width'], '%' ) ) {
			$json['width'] = 640;
		} else {
			$json['width'] = absint( $json['width'] );
		}

		if ( false !== strpos( $json['height'], '%' ) ) {
			$json['height'] = 360;
		} else {
			$json['height'] = absint( $json['height'] );
		}

		$json['p'] = 'default';
		$json['html5'] = true;
	}

	/**
	 * Filters the Anvato JSON before outputting.
	 *
	 * @param array $json            Parsed shortcode attributes to encode as JSON.
	 * @param array $attr            Raw shortcode attributes.
	 * @param bool  $is_amp_request  Whether this is Google AMP request.
	 * @param bool  $is_fbia_request Whether this is a Facebook Instant Articles request.
	 */
	$json = apply_filters( 'anvato_anvp_json', $json, $attr, $is_amp_request, $is_fbia_request );

	// AMP/Facebook Instance Response.
	if ( $is_amp_request || $is_fbia_request ) {
		$html = sprintf(
			"<iframe width='%d' height='%d' sandbox='allow-scripts allow-same-origin allow-popups allow-popups-to-escape-sandbox' layout='responsive'
				scrolling='no' frameborder='0' allowfullscreen src='%s'></iframe>",
			absint( $json['width'] ),
			absint( $json['height'] ),
			esc_url( 'https://w3.cdn.anvato.net/player/prod/anvload.html?key=' . base64_encode( wp_json_encode( $json ) ) )
		);

		if ( $is_fbia_request ) {
			$html = '<figure class="op-interactive">' . $html . '</figure>';
		}

		return $html;
	}

	return sprintf(
		"<div id='%s'></div><script data-anvp='%s' src='%s'></script>",
		esc_attr( $json['pInstance'] ),
		wp_json_encode( $json ),
		esc_url( $player_url )
	);
}
add_shortcode( 'anvplayer', 'anvato_shortcode' );

/**
 * Generate an [anvplayer] shortcode for use in the editor.
 *
 * @param  string $id The video/playlist ID
 * @return string The shortcode
 */
function anvato_generate_shortcode( $id, $type = 'vod' ) {
	if ( 'playlist' !== $type ) {
		$type = 'vod';
	}

	$shortcode = sprintf( '[anvplayer %s="%s"]', sanitize_text_field( $type ), esc_attr( $id ) );

	/**
	 * Modify the generated Anvato Shortcode
	 *
	 * @since 0.1
	 * @param string $shortcode The generated shortcode
	 * @param string $id Anvato Video/Playlist ID
	 * @param string $type Shortcode type (vod/playlist)
	 */
	return apply_filters( 'anvato_generate_shortcode', $shortcode, $id, $type );
}

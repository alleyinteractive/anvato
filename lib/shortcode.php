<?php
global $anvato_player_index, $anvato_player_data;
$anvato_player_index = 0;
$anvato_player_data = array();

/**
 * Implement the Anvato shortcode.
 *
 * @param  array $attr Array of shortcode attributes
 * @return string       HTML to replace the shortcode
 */
function anvato_shortcode( $attr ) {
	global $anvato_player_index, $anvato_player_data;
	$defaults = Anvato_Settings()->get_options();

	# Set the attributes which the shortcode can override
	$json = shortcode_atts( array(
		'mcp'        => $defaults['mcp_id'],
		'profile'    => $defaults['profile'],
		'station_id' => $defaults['station_id'],
		'width'      => $defaults['width'],
		'height'     => $defaults['height'],
		'video'      => null,
		'autoplay'   => false
	), $attr, 'anvplayer' );

	# Set other attributes that can't be overridden
	$json['pInstance'] = 'p' . $anvato_player_index++;

	# Set the player URL, which isn't part of the JSON but can be overridden
	$player_url = ! empty( $attr['player_url'] ) ? $attr['player_url'] : $defaults['player_url'];

	# Set the DFP Ad Tag, which can also be overridden
	if ( ! empty( $defaults['adtag'] ) && ( ! isset( $attr['plugin_dfp_adtagurl'] ) || ( empty( $attr['plugin_dfp_adtagurl'] ) && $attr['plugin_dfp_adtagurl'] !== 'false' ) ) ) {
		$json['plugins']['dfp'] = array( 'adTagUrl' => $defaults['adtag'] );
	} elseif ( ! empty( $attr['plugin_dfp_adtagurl'] ) && $attr['plugin_dfp_adtagurl'] !== 'false' ) {
		$json['plugins']['dfp'] = array( 'adTagUrl' => $attr['plugin_dfp_adtagurl'] );
	}

	# Set the Tracker ID, which can be overridden
	if ( ! isset( $attr['tracker_id'] ) && ! empty( $defaults['tracker_id'] ) ) {
		$json['plugins']['analytics'] = array( 'pdb' => $defaults['tracker_id'] );
	} elseif ( isset( $attr['tracker_id'] ) && 'false' !== $attr['tracker_id'] ) {
		$json['plugins']['analytics'] = array( 'pdb' => $attr['tracker_id'] );
	}

	# Set the Adobe Analytics information, which can be overridden or canceled
	if ( ! isset( $attr['adobe_analytics'] ) || ( isset( $attr['adobe_analytics'] ) && 'false' != $attr['adobe_analytics'] ) ) {
		if ( ! isset( $attr['adobe_profile'] ) && ! empty( $defaults['adobe_profile'] ) ) {
			$json['plugins']['omniture']['profile'] = $defaults['adobe_profile'];
		} elseif ( isset( $attr['adobe_profile'] ) && 'false' !== $attr['adobe_profile'] ) {
			$json['plugins']['omniture']['profile'] = $attr['adobe_profile'];
		}

		if ( ! isset( $attr['adobe_account'] ) && ! empty( $defaults['adobe_account'] ) ) {
			$json['plugins']['omniture']['account'] = $defaults['adobe_account'];
		} elseif ( isset( $attr['adobe_account'] ) && 'false' !== $attr['adobe_account'] ) {
			$json['plugins']['omniture']['account'] = $attr['adobe_account'];
		}

		if ( ! isset( $attr['adobe_trackingserver'] ) && ! empty( $defaults['adobe_trackingserver'] ) ) {
			$json['plugins']['omniture']['trackingServer'] = $defaults['adobe_trackingserver'];
		} elseif ( isset( $attr['adobe_trackingserver'] ) && 'false' !== $attr['adobe_trackingserver'] ) {
			$json['plugins']['omniture']['trackingServer'] = $attr['adobe_trackingserver'];
		}
	}

	# Include the "seek to" time in this instance's localized data.
	if ( isset( $attr['seek_to'] ) && is_numeric( $attr['seek_to'] ) ) {
		$anvato_player_data[ $json['pInstance'] ]['seekTo'] = absint( $attr['seek_to'] ) * 1000;
	}

	# Clean up attributes as need be
	$json['autoplay'] = ( 'true' == $json['autoplay'] );

	# Allow theme/plugins to filter the JSON before outputting
	$json = apply_filters( 'anvato_anvp_json', $json, $attr );

	return "<div id='" . esc_attr( $json['pInstance'] ) . "'></div><script data-anvp='" . esc_attr( json_encode( $json ) ) . "' src='" . esc_url( $player_url ) . "'></script>";
}
add_shortcode( 'anvplayer', 'anvato_shortcode' );

/**
 * Generate an [anvplayer] shortcode for use in the editor.
 *
 * @param int $video The video ID
 * @return string The shortcode
 */
function anvato_generate_shortcode( $video ) {
	return '[anvplayer video="' . intval( $video ) . '"]';
}

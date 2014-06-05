<?php
global $anvato_player_index;
$anvato_player_index = 0;

/**
 * Implement the Anvato shortcode.
 *
 * @param  array $attr Array of shortcode attributes
 * @return string       HTML to replace the shortcode
 */
function anvato_shortcode( $attr ) {
	global $anvato_player_index;
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
	} elseif ( isset( $attr['tracker_id'] ) && $attr['tracker_id'] !== 'false' ) {
		$json['plugins']['analytics'] = array( 'pdb' => $attr['tracker_id'] );
	}

	# Clean up attributes as need be
	$json['autoplay'] = ( 'true' == $json['autoplay'] );

	# Allow theme/plugins to filter the JSON before outputting
	$json = apply_filters( 'anvato_anvp_json', $json, $attr );

	return "<div id='" . esc_attr( $json['pInstance'] ) . "'></div><script data-anvp='" . esc_attr( json_encode( $json ) ) . "' src='" . esc_url( $player_url ) . "'></script>";
}
add_shortcode( 'anvplayer', 'anvato_shortcode' );
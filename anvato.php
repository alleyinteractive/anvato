<?php
/*
	Plugin Name: Anvato
	Plugin URI: http://www.alleyinteractive.com/
	Description: Anvato Video Player
	Version: 0.1
	Author: Matthew Boynes
	Author URI: http://www.alleyinteractive.com/
*/
/*  This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

define( 'ANVATO_PATH', dirname( __FILE__ ) );
define( 'ANVATO_URL', trailingslashit( plugins_url( '', __FILE__ ) ) );

require_once ANVATO_PATH . '/lib/class-anvato-settings.php';
require_once ANVATO_PATH . '/lib/class-anvato-library.php';
require_once ANVATO_PATH . '/mexp/load.php';

if ( ! is_admin() ) {
	require_once ANVATO_PATH . '/lib/shortcode.php';
}

/**
 * Register Javascripts for the frontend.
 */
function anvato_register_scripts() {
	wp_register_script( 'anvato', ANVATO_URL . 'js/public.js', array(), '0.1', true );
}
add_action( 'wp_enqueue_scripts', 'anvato_register_scripts' );

/**
 * Enqueue the player-specific Javascript if data to localize to it exist.
 *
 * This function should not be called until you're confident that no additional
 * shortcode instances will be encountered.
 */
function anvato_localize_player_data() {
	global $anvato_player_data;
	if ( ! empty( $anvato_player_data ) ) {
		wp_enqueue_script( 'anvato' );
		wp_localize_script( 'anvato', 'anvatoPlayerData', $anvato_player_data );
	}
}
add_action( 'wp_print_footer_scripts', 'anvato_localize_player_data', 9 );
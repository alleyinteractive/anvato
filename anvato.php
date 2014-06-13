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
<?php
/**
 * Set up Media Explorer integration.
 *
 * @link  https://github.com/Automattic/media-explorer/
 */

/**
 * Load the files required for Media Explorer integration.
 *
 * @see  MEXP_Anvato_Service, which uses anvato_generate_shortcode().
 */
function anvato_require_mexp_files() {
	require_once ANVATO_PATH . '/lib/shortcode.php';
	require_once ANVATO_PATH . '/mexp/service.php';
	require_once ANVATO_PATH . '/mexp/template.php';
}
add_action( 'mexp_init', 'anvato_require_mexp_files' );

/**
 * Register Anvato as a Media Explorer service.
 *
 * @param array $services Associative array of Media Explorer services to load.
 * @return array $services Services to load, including this one.
 */
function mexp_service_anvato( array $services ) {
	$services['anvato'] = new MEXP_Anvato_Service;
	return $services;
}
add_filter( 'mexp_services', 'mexp_service_anvato' );

/**
 * Tell users with privileges about the Media Explorer plugin if it's missing.
 */
function anvato_add_mexp_notice() {
	if ( ! class_exists( 'MEXP_Service' ) && current_user_can( 'install_plugins' ) ) {
		add_action( 'admin_notices', 'anvato_mexp_nag' );
	}
}
add_action( 'load-settings_page_anvato', 'anvato_add_mexp_notice', 10, 1 );

/**
 * Display the notice about the Media Explorer plugin.
 */
function anvato_mexp_nag() {
	?>
	<div class="update-nag">
		<p>
			<?php
				printf( esc_html__( '%sEven easier embedding%s: You can search for Anvato videos and add shortcodes directly from the Add Media screen by installing the %sMedia Explorer plugin%s.', 'anvato' ),
					'<strong>',
					'</strong>',
					'<a href="https://github.com/Automattic/media-explorer/">',
					'</a>'
				);
			?>
		</p>
	</div>
	<?php
}

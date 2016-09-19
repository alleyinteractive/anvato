<?php
/**
 * Backbone templates for various views for the Anvato service.
 */
class MEXP_Anvato_Template extends MEXP_Template {
	/**
	 * Anvato Settings Instance
	 *
	 * @var Anvato_Settings
	 */
	private $settings;

	public function __construct() {
		$this->settings = Anvato_Settings::instance();
	}

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function item( $id, $tab ) {
	?>
	<div id="mexp-item-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area
		<# if ( 'playlist' == data.meta.type ) {#>anvato-playlist-item<# }
		else { if ( 'live' == data.meta.type ){ #>anvato-channel-item<# }
		else { #>anvato-video-item<#}}#>" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">

				<div class="mexp-item-thumb" style="background-image: url({{ data.thumbnail }})" class="thickbox">
					<# if ( data.meta.duration  ) { #>
						<span>{{ data.meta.duration }}</span><#
					} #>
				</div>

				<div class="mexp-item-main">
					<div class="mexp-item-content">
						<span class="anv-title">{{ data.content }}</span>
							<# if(data.meta.description) { #>
								<span class="anv-desc">{{ data.meta.description }}</span><#
							}#>
					</div>
					<# if ( data.meta.video_count ) { #>
						<div class="mexp-item-meta">
							<span>{{ data.meta.video_count }}</span>
						</div>
					<# } #>
					<# if ( data.meta.category ) { #>
						<div class="mexp-item-meta">
							<?php echo sprintf( esc_html__( '%sCategory%s: %s', 'anvato' ), '<span>', '</span>', '{{ data.meta.category }}' ); ?>
						</div>
					<# } #>
				</div>

			</div>
		</div>

		<a href="#" id="mexp-check-{{ data.id }}" data-id="{{ data.id }}" class="check" title="<?php esc_attr_e( 'Deselect', 'anvato' ); ?>">
			<div class="media-modal-icon"></div>
		</a>
	<?php
	}

	/**
	 * Outputs the Backbone template for a select item's thumbnail in the footer toolbar.
	 *
	 * @param string $id The template ID.
	 */
	public function thumbnail( $id ) {
		if ( empty( $id ) ) {
			return;
		}
		?>
		<div class="mexp-item-thumb">
			<img src="{{ data.thumbnail }}">
		</div>
		<?php
	}

	/**
	 * Outputs the Backbone template for a tab's search fields.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function search( $id, $tab ) {
	?>
		<form action="#" class="mexp-toolbar-container clearfix tab-all">
			<label for="anvato-search-input" class="screen-reader-text"><?php esc_html_e( 'Search for videos', 'anvato' ); ?></label>
			<input
				id="anvato-search-input"
				type="text"
				name="q"
				value="{{ data.params.q }}"
				class="mexp-input-text mexp-input-search"
				size="40"
				placeholder="<?php esc_attr_e( 'Search for videos or playlists', 'anvato' ); ?>"
			>

			<label for="anvato-max-results-input" class="screen-reader-text"><?php esc_html_e( 'Filter maximum number of results', 'anvato' ); ?></label>
			<select id="anvato-max-results-input" name="max_results" class="mexp-input-text mexp-input-select">
				<?php foreach ( array( 50, 25, 10, 5 ) as $num ) : ?>
					<option value="<?php echo absint( $num ) ?>" <?php if ( 25 === $num ) { echo 'selected="selected"'; } ?>><?php echo esc_html( sprintf( __( 'Up to %d results', 'anvato' ), $num ) ); ?></option>
				<?php endforeach; ?>
			</select>

			<label for="anvato-type" class="screen-reader-text"><?php esc_html_e( 'Select Type', 'anvato' ); ?></label>
			<select id="anvato-type" name="type">
				<option value="vod"><?php esc_html_e( 'Video on Demand', 'anvato' ); ?></option>
				<option value="playlist"><?php esc_html_e( 'Playlists', 'anvato' ); ?></option>
				<option value="live"><?php esc_html_e( 'Live Channel', 'anvato' ); ?></option>
			</select>

			<?php
			/**
			 * Anvato Search Settings
			 *
			 * Add input fields to Anvato Library Search.
			 *
			 * @since 0.1.0
			 */
			do_action( 'anvato_mexp_search_inputs' );
			?>

			<input class="button button-large" type="submit" value="<?php esc_attr_e( 'Search', 'anvato' ); ?>">
			<div class="spinner"></div>
		</form>
	<?php
	}
}

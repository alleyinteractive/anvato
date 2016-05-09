<?php
/**
 * Backbone templates for various views for the Anvato service.
 */
class MEXP_Anvato_Template extends MEXP_Template {

	/**
	 * Outputs the Backbone template for an item within search results.
	 *
	 * @param string $id  The template ID.
	 * @param string $tab The tab ID.
	 */
	public function item( $id, $tab ) {
	?>
		<div id="mexp-item-<?php echo esc_attr( $tab ); ?>-{{ data.id }}" class="mexp-item-area" data-id="{{ data.id }}">
			<div class="mexp-item-container clearfix">
				<div class="mexp-item-thumb">
					<img src="{{ data.thumbnail }}">
				</div>

				<div class="mexp-item-main">
					<div class="mexp-item-content">
						{{ data.content }}
					</div>
					<div class="mexp-item-date">
						{{ data.date }}
					</div>
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
				placeholder="<?php esc_attr_e( 'Search for videos', 'anvato' ); ?>"
			>

			<label for="anvato-max-results-input" class="screen-reader-text"><?php esc_html_e( 'Filter maximum number of results', 'anvato' ); ?></label>
			<select id="anvato-max-results-input" name="max_results" class="mexp-input-text mexp-input-select">
				<?php foreach ( array( 50, 25, 10, 5 ) as $num ) : ?>
					<option value="<?php echo absint( $num ) ?>"><?php echo esc_html( sprintf( __( 'Up to %d results', 'anvato' ), $num ) ); ?></option>
				<?php endforeach; ?>
			</select>

			<?php
			/**
			 * Fires after the default inputs in the Anvato Library search tab.
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

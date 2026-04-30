<?php
/**
 * Workspace Links admin page — Web Coord / Admin Coord curate the link
 * cards that appear on /my-board/ Links tab.
 *
 * Permission gate: owc_workspace_user_can_edit() (admin-bypass + ASC roles).
 */

defined( 'ABSPATH' ) || exit;

const OWC_WORKSPACE_LINKS_MENU_SLUG = 'owc-workspace-links';
const OWC_WORKSPACE_LINKS_NONCE     = 'owc_workspace_links_save';

add_action( 'admin_menu', function () {
	$client_id = function_exists( 'owc_get_client_id' ) ? owc_get_client_id() : 'owc';
	$parent    = $client_id . '-owc-settings';

	add_submenu_page(
		$parent,
		__( 'Workspace Links', 'owbn-core' ),
		__( 'Workspace Links', 'owbn-core' ),
		'read', // we permission-check manually with ASC inside the page
		OWC_WORKSPACE_LINKS_MENU_SLUG,
		'owc_workspace_links_render_page'
	);
}, 20 );

add_action( 'admin_post_owc_workspace_links_save', 'owc_workspace_links_handle_save' );

function owc_workspace_links_handle_save() {
	if ( ! owc_workspace_user_can_edit() ) {
		wp_die( esc_html__( 'You do not have permission to edit workspace links.', 'owbn-core' ), 403 );
	}
	check_admin_referer( OWC_WORKSPACE_LINKS_NONCE );

	$posted = array(
		'admin'    => isset( $_POST['admin'] )    && is_array( $_POST['admin'] )    ? wp_unslash( $_POST['admin'] )    : array(),
		'my_stuff' => isset( $_POST['my_stuff'] ) && is_array( $_POST['my_stuff'] ) ? wp_unslash( $_POST['my_stuff'] ) : array(),
	);
	owc_save_workspace_links( $posted );

	$redirect = add_query_arg(
		array( 'page' => OWC_WORKSPACE_LINKS_MENU_SLUG, 'updated' => '1' ),
		admin_url( 'admin.php' )
	);
	wp_safe_redirect( $redirect );
	exit;
}

function owc_workspace_links_render_page() {
	if ( ! owc_workspace_user_can_edit() ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Workspace Links', 'owbn-core' ) . '</h1>';
		echo '<p>' . esc_html__( 'You do not have permission to edit workspace links. This page is restricted to Web Coordinator and Admin Coordinator roles (and their staff).', 'owbn-core' ) . '</p></div>';
		return;
	}

	$links = owc_get_workspace_links();
	$updated = ! empty( $_GET['updated'] );
	?>
	<div class="wrap owc-workspace-links">
		<h1><?php esc_html_e( 'Workspace Links', 'owbn-core' ); ?></h1>
		<p class="description"><?php esc_html_e( 'These cards appear on the Links tab of /my-board/. Section A is shown to all logged-in users; Section B is the per-user "My Stuff" panel. URLs should use SSO redirect form (https://host/?auth=sso&redirect_uri=/path/).', 'owbn-core' ); ?></p>

		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Workspace links saved.', 'owbn-core' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="owc-ws-links-form">
			<input type="hidden" name="action" value="owc_workspace_links_save" />
			<?php wp_nonce_field( OWC_WORKSPACE_LINKS_NONCE ); ?>

			<h2><?php esc_html_e( 'Section A — Org Resources (everyone)', 'owbn-core' ); ?></h2>
			<?php owc_workspace_links_render_section_editor( 'admin', $links['admin'] ); ?>

			<h2 style="margin-top:32px;"><?php esc_html_e( 'Section B — My Stuff (everyone, per-user contexts)', 'owbn-core' ); ?></h2>
			<?php owc_workspace_links_render_section_editor( 'my_stuff', $links['my_stuff'] ); ?>

			<p style="margin-top:24px;">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Workspace Links', 'owbn-core' ); ?></button>
			</p>
		</form>
	</div>

	<style>
		.owc-ws-card-editor { border: 1px solid #c3c4c7; padding: 12px; margin-bottom: 12px; background: #fff; }
		.owc-ws-card-editor input[type=text], .owc-ws-card-editor input[type=url] { width: 100%; }
		.owc-ws-card-editor h3 { margin-top: 0; font-size: 1em; }
		.owc-ws-link-row { display: grid; grid-template-columns: 1fr 2fr 80px; gap: 8px; margin-bottom: 6px; align-items: center; }
		.owc-ws-link-row input { padding: 4px 8px; }
		.owc-ws-card-actions { margin-top: 8px; }
		.owc-ws-card-actions button, .owc-ws-section-actions button { margin-right: 6px; }
		.owc-ws-section-actions { margin: 8px 0 16px; }
	</style>

	<script>
	(function(){
		function buildLinkRow(section, cardIdx, linkIdx, label, url) {
			var div = document.createElement('div');
			div.className = 'owc-ws-link-row';
			div.innerHTML =
				'<input type="text" name="' + section + '[' + cardIdx + '][links][' + linkIdx + '][label]" value="' + (label||'') + '" placeholder="<?php echo esc_js( __( 'Link label', 'owbn-core' ) ); ?>" />' +
				'<input type="url"  name="' + section + '[' + cardIdx + '][links][' + linkIdx + '][url]"   value="' + (url||'')   + '" placeholder="https://..." />' +
				'<button type="button" class="button owc-ws-remove-link"><?php echo esc_js( __( 'Remove', 'owbn-core' ) ); ?></button>';
			return div;
		}

		function buildCard(section, cardIdx) {
			var div = document.createElement('div');
			div.className = 'owc-ws-card-editor';
			div.dataset.section = section;
			div.dataset.cardIdx = cardIdx;
			div.innerHTML =
				'<h3><label><?php echo esc_js( __( 'Card title:', 'owbn-core' ) ); ?> ' +
				'<input type="text" name="' + section + '[' + cardIdx + '][card_title]" value="" /></label></h3>' +
				'<div class="owc-ws-links-list"></div>' +
				'<div class="owc-ws-card-actions">' +
					'<button type="button" class="button owc-ws-add-link"><?php echo esc_js( __( '+ Add link', 'owbn-core' ) ); ?></button>' +
					'<button type="button" class="button owc-ws-remove-card"><?php echo esc_js( __( 'Remove card', 'owbn-core' ) ); ?></button>' +
				'</div>';
			return div;
		}

		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('owc-ws-add-link')) {
				e.preventDefault();
				var card = e.target.closest('.owc-ws-card-editor');
				var list = card.querySelector('.owc-ws-links-list');
				var section = card.dataset.section;
				var cardIdx = card.dataset.cardIdx;
				var linkIdx = list.querySelectorAll('.owc-ws-link-row').length;
				list.appendChild(buildLinkRow(section, cardIdx, linkIdx, '', ''));
			}
			if (e.target.classList.contains('owc-ws-remove-link')) {
				e.preventDefault();
				e.target.closest('.owc-ws-link-row').remove();
			}
			if (e.target.classList.contains('owc-ws-remove-card')) {
				e.preventDefault();
				if (confirm('<?php echo esc_js( __( 'Remove this card and all its links?', 'owbn-core' ) ); ?>')) {
					e.target.closest('.owc-ws-card-editor').remove();
				}
			}
			if (e.target.classList.contains('owc-ws-add-card')) {
				e.preventDefault();
				var section = e.target.dataset.section;
				var container = document.querySelector('.owc-ws-section-cards[data-section="' + section + '"]');
				var cardIdx = container.querySelectorAll('.owc-ws-card-editor').length;
				container.appendChild(buildCard(section, cardIdx));
			}
		});
	})();
	</script>
	<?php
}

function owc_workspace_links_render_section_editor( $section_key, array $cards ) {
	?>
	<div class="owc-ws-section-cards" data-section="<?php echo esc_attr( $section_key ); ?>">
		<?php foreach ( $cards as $i => $card ) : ?>
			<div class="owc-ws-card-editor" data-section="<?php echo esc_attr( $section_key ); ?>" data-card-idx="<?php echo (int) $i; ?>">
				<h3>
					<label><?php esc_html_e( 'Card title:', 'owbn-core' ); ?>
						<input type="text"
							name="<?php echo esc_attr( $section_key ); ?>[<?php echo (int) $i; ?>][card_title]"
							value="<?php echo esc_attr( $card['card_title'] ?? '' ); ?>" />
					</label>
				</h3>
				<div class="owc-ws-links-list">
					<?php foreach ( ( $card['links'] ?? array() ) as $j => $link ) : ?>
						<div class="owc-ws-link-row">
							<input type="text"
								name="<?php echo esc_attr( $section_key ); ?>[<?php echo (int) $i; ?>][links][<?php echo (int) $j; ?>][label]"
								value="<?php echo esc_attr( $link['label'] ?? '' ); ?>"
								placeholder="<?php esc_attr_e( 'Link label', 'owbn-core' ); ?>" />
							<input type="url"
								name="<?php echo esc_attr( $section_key ); ?>[<?php echo (int) $i; ?>][links][<?php echo (int) $j; ?>][url]"
								value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"
								placeholder="https://..." />
							<button type="button" class="button owc-ws-remove-link"><?php esc_html_e( 'Remove', 'owbn-core' ); ?></button>
						</div>
					<?php endforeach; ?>
				</div>
				<div class="owc-ws-card-actions">
					<button type="button" class="button owc-ws-add-link"><?php esc_html_e( '+ Add link', 'owbn-core' ); ?></button>
					<button type="button" class="button owc-ws-remove-card"><?php esc_html_e( 'Remove card', 'owbn-core' ); ?></button>
				</div>
			</div>
		<?php endforeach; ?>
	</div>
	<div class="owc-ws-section-actions">
		<button type="button" class="button owc-ws-add-card" data-section="<?php echo esc_attr( $section_key ); ?>"><?php esc_html_e( '+ Add card', 'owbn-core' ); ?></button>
	</div>
	<?php
}

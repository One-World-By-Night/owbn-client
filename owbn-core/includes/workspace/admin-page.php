<?php
/**
 * Workspace Links admin page — Web Coord / Admin Coord curate the link
 * lists shown in the persistent top header (Resources / Bylaws / Voting)
 * on /my-board/.
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
		'read',
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

	$posted = array();
	foreach ( OWC_WORKSPACE_LINK_CATEGORIES as $cat ) {
		$posted[ $cat ] = ( isset( $_POST[ $cat ] ) && is_array( $_POST[ $cat ] ) )
			? wp_unslash( $_POST[ $cat ] )
			: array();
	}
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

	$links   = owc_get_workspace_links();
	$updated = ! empty( $_GET['updated'] );
	$labels  = array(
		'resources' => __( 'Resources', 'owbn-core' ),
		'bylaws'    => __( 'Bylaws', 'owbn-core' ),
		'voting'    => __( 'Voting', 'owbn-core' ),
	);
	?>
	<div class="wrap owc-workspace-links">
		<h1><?php esc_html_e( 'Workspace Links', 'owbn-core' ); ?></h1>
		<p class="description"><?php esc_html_e( 'These three cards appear in the persistent top header of /my-board/. URLs should use SSO redirect form (https://host/?auth=sso&redirect_uri=/path/).', 'owbn-core' ); ?></p>

		<?php if ( $updated ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Workspace links saved.', 'owbn-core' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" id="owc-ws-links-form">
			<input type="hidden" name="action" value="owc_workspace_links_save" />
			<?php wp_nonce_field( OWC_WORKSPACE_LINKS_NONCE ); ?>

			<?php foreach ( OWC_WORKSPACE_LINK_CATEGORIES as $cat ) : ?>
				<h2 style="margin-top:24px;"><?php echo esc_html( $labels[ $cat ] ); ?></h2>
				<?php owc_workspace_links_render_card_editor( $cat, $links[ $cat ] ); ?>
			<?php endforeach; ?>

			<p style="margin-top:24px;">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Workspace Links', 'owbn-core' ); ?></button>
			</p>
		</form>
	</div>

	<style>
		.owc-ws-card-editor { border: 1px solid #c3c4c7; padding: 12px; margin-bottom: 12px; background: #fff; }
		.owc-ws-card-editor input[type=text], .owc-ws-card-editor input[type=url] { width: 100%; }
		.owc-ws-link-row { display: grid; grid-template-columns: 1fr 2fr 80px; gap: 8px; margin-bottom: 6px; align-items: center; }
		.owc-ws-link-row input { padding: 4px 8px; }
		.owc-ws-card-actions { margin-top: 8px; }
	</style>

	<script>
	(function(){
		function buildLinkRow(category, linkIdx) {
			var div = document.createElement('div');
			div.className = 'owc-ws-link-row';
			div.innerHTML =
				'<input type="text" name="' + category + '[' + linkIdx + '][label]" value="" placeholder="<?php echo esc_js( __( 'Link label', 'owbn-core' ) ); ?>" />' +
				'<input type="url"  name="' + category + '[' + linkIdx + '][url]"   value="" placeholder="https://..." />' +
				'<button type="button" class="button owc-ws-remove-link"><?php echo esc_js( __( 'Remove', 'owbn-core' ) ); ?></button>';
			return div;
		}

		document.addEventListener('click', function(e) {
			if (e.target.classList.contains('owc-ws-add-link')) {
				e.preventDefault();
				var card = e.target.closest('.owc-ws-card-editor');
				var list = card.querySelector('.owc-ws-links-list');
				var category = card.dataset.category;
				var linkIdx = list.querySelectorAll('.owc-ws-link-row').length;
				list.appendChild(buildLinkRow(category, linkIdx));
			}
			if (e.target.classList.contains('owc-ws-remove-link')) {
				e.preventDefault();
				e.target.closest('.owc-ws-link-row').remove();
			}
		});
	})();
	</script>
	<?php
}

function owc_workspace_links_render_card_editor( $category, array $links ) {
	?>
	<div class="owc-ws-card-editor" data-category="<?php echo esc_attr( $category ); ?>">
		<div class="owc-ws-links-list">
			<?php foreach ( $links as $j => $link ) : ?>
				<div class="owc-ws-link-row">
					<input type="text"
						name="<?php echo esc_attr( $category ); ?>[<?php echo (int) $j; ?>][label]"
						value="<?php echo esc_attr( $link['label'] ?? '' ); ?>"
						placeholder="<?php esc_attr_e( 'Link label', 'owbn-core' ); ?>" />
					<input type="url"
						name="<?php echo esc_attr( $category ); ?>[<?php echo (int) $j; ?>][url]"
						value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"
						placeholder="https://..." />
					<button type="button" class="button owc-ws-remove-link"><?php esc_html_e( 'Remove', 'owbn-core' ); ?></button>
				</div>
			<?php endforeach; ?>
		</div>
		<div class="owc-ws-card-actions">
			<button type="button" class="button owc-ws-add-link"><?php esc_html_e( '+ Add link', 'owbn-core' ); ?></button>
		</div>
	</div>
	<?php
}

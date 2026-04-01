<?php
/**
 * Settings Tab: OWBN Menu
 *
 * Manage the links in the OWBN dropdown menu on the admin bar.
 */

defined( 'ABSPATH' ) || exit;

$bar_option = owc_option_name( 'admin_bar_links' );
$bar_links  = get_option( $bar_option, array() );
if ( empty( $bar_links ) ) {
    $bar_links = array(
        array( 'title' => 'My Account',  'url' => 'https://sso.owbn.net/site-listing/' ),
        array( 'title' => 'Chronicles', 'url' => 'https://chronicles.owbn.net/' ),
        array( 'title' => 'Council',    'url' => 'https://council.owbn.net/' ),
        array( 'title' => 'Archivist',  'url' => 'https://archivist.owbn.net/' ),
    );
}

// Handle save.
if ( isset( $_POST['owc_save_admin_bar_links'] ) && check_admin_referer( 'owc_admin_bar_links_action' ) ) {
    $new_links = array();
    $titles = isset( $_POST['owbn_bar_title'] ) ? (array) $_POST['owbn_bar_title'] : array();
    $urls   = isset( $_POST['owbn_bar_url'] ) ? (array) $_POST['owbn_bar_url'] : array();
    for ( $i = 0; $i < count( $titles ); $i++ ) {
        $t = sanitize_text_field( $titles[ $i ] ?? '' );
        $u = esc_url_raw( $urls[ $i ] ?? '' );
        if ( $t && $u ) {
            $new_links[] = array( 'title' => $t, 'url' => $u );
        }
    }
    update_option( $bar_option, $new_links );
    $bar_links = $new_links;
    echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Admin bar links saved.', 'owbn-core' ) . '</p></div>';
}
?>

<h3><?php esc_html_e( 'OWBN Admin Bar Menu', 'owbn-core' ); ?></h3>
<p class="description"><?php esc_html_e( 'These links appear in the OWBN dropdown on the admin bar (right side, next to "Howdy"). Edit existing links, remove with ×, or add new ones.', 'owbn-core' ); ?></p>

<form method="post">
    <?php wp_nonce_field( 'owc_admin_bar_links_action' ); ?>
    <table class="widefat" id="owbn-bar-links-table" style="max-width:800px;">
        <thead>
            <tr>
                <th style="width:35%;"><?php esc_html_e( 'Title', 'owbn-core' ); ?></th>
                <th style="width:55%;"><?php esc_html_e( 'URL', 'owbn-core' ); ?></th>
                <th style="width:10%;text-align:center;"><?php esc_html_e( 'Remove', 'owbn-core' ); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ( $bar_links as $link ) : ?>
                <tr>
                    <td><input type="text" name="owbn_bar_title[]" value="<?php echo esc_attr( $link['title'] ); ?>" style="width:100%;"></td>
                    <td><input type="url" name="owbn_bar_url[]" value="<?php echo esc_attr( $link['url'] ); ?>" style="width:100%;"></td>
                    <td style="text-align:center;"><button type="button" class="button" onclick="this.closest('tr').remove();" title="Remove">&times;</button></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p style="margin-top:12px;">
        <button type="button" class="button" onclick="
            var tbody = document.querySelector('#owbn-bar-links-table tbody');
            var tr = document.createElement('tr');
            tr.innerHTML = '<td><input type=\'text\' name=\'owbn_bar_title[]\' style=\'width:100%;\' placeholder=\'Link Title\'></td><td><input type=\'url\' name=\'owbn_bar_url[]\' style=\'width:100%;\' placeholder=\'https://...\'></td><td style=\'text-align:center;\'><button type=\'button\' class=\'button\' onclick=\'this.closest(\\\"tr\\\").remove();\' title=\'Remove\'>&times;</button></td>';
            tbody.appendChild(tr);
        "><?php esc_html_e( '+ Add Link', 'owbn-core' ); ?></button>
    </p>
    <?php submit_button( __( 'Save Links', 'owbn-core' ), 'primary', 'owc_save_admin_bar_links' ); ?>
</form>

<h3><?php esc_html_e( 'Preview', 'owbn-core' ); ?></h3>
<p class="description"><?php esc_html_e( 'The OWBN logo appears on the right side of the admin bar. Hovering shows the dropdown with these links. Save and reload to see changes.', 'owbn-core' ); ?></p>
<ul style="list-style:disc;margin-left:20px;">
    <?php foreach ( $bar_links as $link ) : ?>
        <li><a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank"><?php echo esc_html( $link['title'] ); ?></a> — <code><?php echo esc_html( $link['url'] ); ?></code></li>
    <?php endforeach; ?>
</ul>

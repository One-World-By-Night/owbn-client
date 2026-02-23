<?php
/**
 * OAT Client - Inbox Template
 * location: includes/oat/templates/inbox.php
 *
 * Variables available:
 *   $assignments   array  Pending assignment items.
 *   $watched       array  Watched entries.
 *   $my_entries    array  User's own entries.
 *   $user_map      array  user_id => display_name.
 *   $domains       array  Domain list ({ slug, label }).
 *   $domain_filter string Active domain filter.
 *
 * @package OWBN-Client
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
    <h1>OAT Inbox</h1>

    <form method="get">
        <input type="hidden" name="page" value="owc-oat-inbox">
        <div class="alignleft actions">
            <select name="domain">
                <option value="">All Domains</option>
                <?php foreach ( $domains as $d ) : ?>
                    <option value="<?php echo esc_attr( $d['slug'] ); ?>" <?php selected( $domain_filter, $d['slug'] ); ?>><?php echo esc_html( $d['label'] ); ?></option>
                <?php endforeach; ?>
            </select>
            <?php submit_button( 'Filter', '', 'filter_action', false ); ?>
        </div>
    </form>

    <?php if ( empty( $assignments ) ) : ?>
        <p>No pending items in your inbox.</p>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Entry</th>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Step</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $assignments as $item ) : ?>
                    <?php $url = admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $item['entry_id'] ); ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $url ); ?>"><strong>#<?php echo esc_html( $item['entry_id'] ); ?></strong></a></td>
                        <td><?php echo esc_html( isset( $item['domain_label'] ) ? $item['domain_label'] : $item['domain'] ); ?></td>
                        <td><span class="oat-status oat-status-<?php echo esc_attr( $item['status'] ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $item['status'] ) ) ); ?></span></td>
                        <td><?php echo esc_html( str_replace( '_', ' ', $item['current_step'] ) ); ?></td>
                        <td><?php echo esc_html( $item['created_at'] ); ?></td>
                        <td><a href="<?php echo esc_url( $url ); ?>" class="button button-small">Review</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ( ! empty( $watched ) ) : ?>
        <h2>Watched Entries</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Entry</th>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $watched as $w ) : ?>
                    <?php $url = admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $w['entry_id'] ); ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $url ); ?>">#<?php echo esc_html( $w['entry_id'] ); ?></a></td>
                        <td><?php echo esc_html( isset( $w['domain_label'] ) ? $w['domain_label'] : $w['domain'] ); ?></td>
                        <td><span class="oat-status oat-status-<?php echo esc_attr( $w['status'] ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $w['status'] ) ) ); ?></span></td>
                        <td><?php echo esc_html( $w['updated_at'] ); ?></td>
                        <td><a href="<?php echo esc_url( $url ); ?>" class="button button-small">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if ( ! empty( $my_entries ) ) : ?>
        <h2>My Entries</h2>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Entry</th>
                    <th>Domain</th>
                    <th>Status</th>
                    <th>Step</th>
                    <th>Created</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $my_entries as $e ) : ?>
                    <?php $url = admin_url( 'admin.php?page=owc-oat-entry&entry_id=' . $e['entry_id'] ); ?>
                    <tr>
                        <td><a href="<?php echo esc_url( $url ); ?>">#<?php echo esc_html( $e['entry_id'] ); ?></a></td>
                        <td><?php echo esc_html( isset( $e['domain_label'] ) ? $e['domain_label'] : $e['domain'] ); ?></td>
                        <td><span class="oat-status oat-status-<?php echo esc_attr( $e['status'] ); ?>"><?php echo esc_html( ucfirst( str_replace( '_', ' ', $e['status'] ) ) ); ?></span></td>
                        <td><?php echo esc_html( str_replace( '_', ' ', $e['current_step'] ) ); ?></td>
                        <td><?php echo esc_html( $e['created_at'] ); ?></td>
                        <td><a href="<?php echo esc_url( $url ); ?>" class="button button-small">View</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

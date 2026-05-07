<?php

declare( strict_types=1 );

namespace em_rest_api_cpt\init_plugin\Classes;

use em_rest_api_cpt\init_plugin\EM_Rest_API_CPT;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Admin_Settings {

    const MENU_SLUG = 'em-rest-api-cpt-settings';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_page' ] );
        add_action( 'admin_post_em_regenerate_api_key', [ $this, 'regenerate_api_key' ] );
    }

    public function add_page(): void {
        add_submenu_page(
            'edit.php?post_type=apidata',
            __( 'API Settings', 'em-rest-api-cpt' ),
            __( 'Settings',     'em-rest-api-cpt' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_page' ]
        );
    }

    public function render_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $api_key      = get_option( EM_Rest_API_CPT::OPTION_API_KEY, '' );
        $base_url     = rest_url( 'esmond-api/v1' );
        $updated      = isset( $_GET['updated'] ) && '1' === $_GET['updated']; // phpcs:ignore WordPress.Security.NonceVerification
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'EM REST API CPT — Settings', 'em-rest-api-cpt' ); ?></h1>

            <?php if ( $updated ) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php esc_html_e( 'API key regenerated successfully.', 'em-rest-api-cpt' ); ?></p>
                </div>
            <?php endif; ?>

            <h2><?php esc_html_e( 'Authentication', 'em-rest-api-cpt' ); ?></h2>
            <p><?php esc_html_e( 'All API endpoints require the following header on every request:', 'em-rest-api-cpt' ); ?></p>
            <code>X-API-Key: <?php echo esc_html( $api_key ); ?></code>

            <table class="form-table" role="presentation" style="max-width:700px">
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Key', 'em-rest-api-cpt' ); ?></th>
                    <td>
                        <input type="text" class="regular-text" readonly
                            value="<?php echo esc_attr( $api_key ); ?>"
                            style="font-family:monospace" />
                        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;margin-left:8px">
                            <?php wp_nonce_field( 'em_regenerate_api_key', 'em_nonce' ); ?>
                            <input type="hidden" name="action" value="em_regenerate_api_key" />
                            <button type="submit" class="button">
                                <?php esc_html_e( 'Regenerate Key', 'em-rest-api-cpt' ); ?>
                            </button>
                        </form>
                        <p class="description">
                            <?php esc_html_e( 'Regenerating invalidates the current key immediately. Update any external clients before regenerating.', 'em-rest-api-cpt' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <h2><?php esc_html_e( 'Available Endpoints', 'em-rest-api-cpt' ); ?></h2>
            <table class="widefat striped" style="max-width:800px">
                <thead>
                    <tr>
                        <th style="width:80px"><?php esc_html_e( 'Method', 'em-rest-api-cpt' ); ?></th>
                        <th><?php esc_html_e( 'URL', 'em-rest-api-cpt' ); ?></th>
                        <th><?php esc_html_e( 'Description', 'em-rest-api-cpt' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>POST</strong></td>
                        <td><code><?php echo esc_url( $base_url . '/receive' ); ?></code></td>
                        <td><?php esc_html_e( 'Create a new API Data entry. Required fields: title, body. Optional: source, external_id.', 'em-rest-api-cpt' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>GET</strong></td>
                        <td><code><?php echo esc_url( $base_url . '/entries' ); ?></code></td>
                        <td><?php esc_html_e( 'List all entries. Optional query params: source, per_page, page.', 'em-rest-api-cpt' ); ?></td>
                    </tr>
                    <tr>
                        <td><strong>DELETE</strong></td>
                        <td><code><?php echo esc_url( $base_url . '/entries/{id}' ); ?></code></td>
                        <td><?php esc_html_e( 'Permanently delete an entry by post ID.', 'em-rest-api-cpt' ); ?></td>
                    </tr>
                </tbody>
            </table>

            <h2><?php esc_html_e( 'Example cURL Requests', 'em-rest-api-cpt' ); ?></h2>
            <h3><?php esc_html_e( 'Create an entry (POST)', 'em-rest-api-cpt' ); ?></h3>
            <pre style="background:#f0f0f0;padding:12px;max-width:800px;overflow-x:auto">curl -X POST <?php echo esc_url( $base_url . '/receive' ); ?> \
  -H "Content-Type: application/json" \
  -H "X-API-Key: <?php echo esc_html( $api_key ); ?>" \
  -d '{"title":"My Entry","body":"Entry content here.","source":"my-app","external_id":"abc-123"}'</pre>

            <h3><?php esc_html_e( 'List entries (GET)', 'em-rest-api-cpt' ); ?></h3>
            <pre style="background:#f0f0f0;padding:12px;max-width:800px;overflow-x:auto">curl -X GET "<?php echo esc_url( $base_url . '/entries?source=my-app&per_page=5' ); ?>" \
  -H "X-API-Key: <?php echo esc_html( $api_key ); ?>"</pre>

            <h3><?php esc_html_e( 'Delete an entry (DELETE)', 'em-rest-api-cpt' ); ?></h3>
            <pre style="background:#f0f0f0;padding:12px;max-width:800px;overflow-x:auto">curl -X DELETE <?php echo esc_url( $base_url . '/entries/42' ); ?> \
  -H "X-API-Key: <?php echo esc_html( $api_key ); ?>"</pre>
        </div>
        <?php
    }

    public function regenerate_api_key(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized.', 'em-rest-api-cpt' ) );
        }

        if ( ! check_admin_referer( 'em_regenerate_api_key', 'em_nonce' ) ) {
            wp_die( esc_html__( 'Invalid nonce.', 'em-rest-api-cpt' ) );
        }

        update_option( EM_Rest_API_CPT::OPTION_API_KEY, wp_generate_password( 32, false ) );

        $redirect = add_query_arg(
            array(
                'post_type' => 'apidata',
                'page'      => self::MENU_SLUG,
                'updated'   => '1',
            ),
            admin_url( 'edit.php' )
        );

        wp_safe_redirect( $redirect );
        exit;
    }
}

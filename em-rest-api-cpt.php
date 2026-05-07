<?php
/**
 * Plugin Name: EM REST API CPT
 * Plugin URI:  https://github.com/Esmond-M/em-rest-api-cpt
 * Author:      Esmond McCain
 * Author URI:  https://esmondmccain.com/
 * Description: Registers a custom post type and exposes REST API endpoints (POST, GET, DELETE) to create, retrieve, and delete entries — secured with a plugin-managed API key and configurable via an admin settings page.
 * Version:     0.2.0
 * License:     GPL-2.0-or-later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: em-rest-api-cpt
 */

namespace em_rest_api_cpt\init_plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class EM_Rest_API_CPT {

    const VERSION             = '0.2.0';
    const PHP_MINIMUM_VERSION = '8.0';
    const OPTION_API_KEY      = 'em_rest_api_cpt_api_key';

    private static ?self $instance = null;

    private function __construct() {
        if ( ! self::meets_php_requirement() ) {
            add_action( 'admin_notices', [ $this, 'php_version_notice' ] );
            return;
        }

        add_action( 'plugins_loaded', [ $this, 'init' ] );
    }

    public static function get_instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        $this->load_textdomain();
        $this->load_classes();
    }

    private function load_textdomain(): void {
        load_plugin_textdomain(
            'em-rest-api-cpt',
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages'
        );
    }

    private function load_classes(): void {
        require_once __DIR__ . '/classes/register-cpt.php';
        require_once __DIR__ . '/classes/admin-settings.php';
        require_once __DIR__ . '/classes/make-endpoint.php';

        new Classes\Register_CPT();
        new Classes\Admin_Settings();
        new Classes\Make_Endpoint();
    }

    private static function meets_php_requirement(): bool {
        return version_compare( PHP_VERSION, self::PHP_MINIMUM_VERSION, '>=' );
    }

    public function php_version_notice(): void {
        echo '<div class="notice notice-error"><p>';
        printf(
            /* translators: 1: required version, 2: current version */
            esc_html__( 'EM REST API CPT requires PHP %1$s or higher. You are running PHP %2$s. Please upgrade to activate this plugin.', 'em-rest-api-cpt' ),
            esc_html( self::PHP_MINIMUM_VERSION ),
            esc_html( PHP_VERSION )
        );
        echo '</p></div>';
    }

    public static function activate(): void {
        if ( ! get_option( self::OPTION_API_KEY ) ) {
            update_option( self::OPTION_API_KEY, wp_generate_password( 32, false ) );
        }
    }

    public static function deactivate(): void {
        // Intentionally left blank — data is preserved on deactivation.
    }
}

register_activation_hook( __FILE__, [ 'em_rest_api_cpt\init_plugin\EM_Rest_API_CPT', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'em_rest_api_cpt\init_plugin\EM_Rest_API_CPT', 'deactivate' ] );

EM_Rest_API_CPT::get_instance();
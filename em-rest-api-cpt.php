<?php
/**
 * Plugin Name: EM Rest API CPT
 * Plugin URI: https://github.com/Esmond-M
 * Author: Esmond Mccain
 * Author URI: https://esmondmccain.com/
 * Description: Use REST API to populate custom post type. 
 * Version: 0.1.0
 * License: 0.1.0
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: em-rest-api-cpt
*/
namespace  em_rest_api_cpt\init_plugin;

use em_rest_api_cpt\init_plugin\Classes\Make_Endpoint;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class em_rest_api_cpt {

    const VERSION = '0.1.0';
    const PHP_MINIMUM_VERSION = '7.0';

    private static $_instance = null;

    public function __construct() {
    
       add_action( 'init', [ $this, 'i18n' ] );        
       add_action( 'plugins_loaded', [ $this, 'init_class' ] );
       add_action( 'init',  [ $this, 'create_post_type']   );
    }

    public function i18n() {
        load_plugin_textdomain( 'em-rest-api-cpt' );
    }

    public function init_class() {
        require_once __DIR__ . '/classes/make-endpoint.php';
   
     
    }

    public function create_post_type(){
        
        $args = array(
            'public'             => true,
            'publicly_queryable' => false,
            'label'              => __( 'API Data', ' em-rest-api-cpt' ),
            'menu_icon'          => 'dashicons-analytics',
            'supports'           => array( 'title', 'editor' )
        );
        
        register_post_type( 'apidata', $args );
    }

    public static function get_instance() {

        if ( null == self::$_instance ) {
            self::$_instance = new Self();
        }

        return self::$_instance;

    }

}


em_rest_api_cpt::get_instance();
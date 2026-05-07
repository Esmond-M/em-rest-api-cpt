<?php

declare( strict_types=1 );

namespace em_rest_api_cpt\init_plugin\Classes;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Register_CPT {

    const POST_TYPE = 'apidata';

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'init', [ $this, 'register_meta' ] );
        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns',       [ $this, 'admin_columns' ] );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', [ $this, 'admin_column_content' ], 10, 2 );
    }

    public function register(): void {
        $labels = array(
            'name'               => __( 'API Data',           'em-rest-api-cpt' ),
            'singular_name'      => __( 'API Data Entry',     'em-rest-api-cpt' ),
            'add_new_item'       => __( 'Add New Entry',      'em-rest-api-cpt' ),
            'edit_item'          => __( 'Edit Entry',         'em-rest-api-cpt' ),
            'not_found'          => __( 'No entries found.',  'em-rest-api-cpt' ),
            'not_found_in_trash' => __( 'No entries in trash.', 'em-rest-api-cpt' ),
        );

        $args = array(
            'labels'          => $labels,
            'public'          => false,
            'show_ui'         => true,
            'show_in_menu'    => true,
            'show_in_rest'    => true,
            'menu_icon'       => 'dashicons-analytics',
            'supports'        => array( 'title', 'editor', 'custom-fields' ),
            'capability_type' => 'post',
        );

        register_post_type( self::POST_TYPE, $args );
    }

    public function register_meta(): void {
        $fields = array(
            '_api_source'   => __( 'The origin/source identifier sent with the request.', 'em-rest-api-cpt' ),
            '_received_at'  => __( 'MySQL datetime when the entry was received.', 'em-rest-api-cpt' ),
            '_external_id'  => __( 'Optional ID from the external system that sent the data.', 'em-rest-api-cpt' ),
        );

        foreach ( $fields as $key => $description ) {
            register_post_meta(
                self::POST_TYPE,
                $key,
                array(
                    'type'              => 'string',
                    'single'            => true,
                    'show_in_rest'      => true,
                    'description'       => $description,
                    'sanitize_callback' => 'sanitize_text_field',
                    'auth_callback'     => fn() => current_user_can( 'edit_posts' ),
                )
            );
        }
    }

    public function admin_columns( array $columns ): array {
        $reordered = array();
        foreach ( $columns as $key => $label ) {
            $reordered[ $key ] = $label;
            if ( 'title' === $key ) {
                $reordered['api_source']  = __( 'Source',      'em-rest-api-cpt' );
                $reordered['external_id'] = __( 'External ID', 'em-rest-api-cpt' );
                $reordered['received_at'] = __( 'Received',    'em-rest-api-cpt' );
            }
        }
        return $reordered;
    }

    public function admin_column_content( string $column, int $post_id ): void {
        switch ( $column ) {
            case 'api_source':
                echo esc_html( get_post_meta( $post_id, '_api_source', true ) ?: '—' );
                break;
            case 'external_id':
                echo esc_html( get_post_meta( $post_id, '_external_id', true ) ?: '—' );
                break;
            case 'received_at':
                $ts = get_post_meta( $post_id, '_received_at', true );
                if ( $ts ) {
                    $format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
                    echo esc_html( date_i18n( $format, strtotime( $ts ) ) );
                } else {
                    echo '—';
                }
                break;
        }
    }
}

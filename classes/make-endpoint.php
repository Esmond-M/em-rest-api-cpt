<?php

declare( strict_types=1 );

namespace em_rest_api_cpt\init_plugin\Classes;

use em_rest_api_cpt\init_plugin\EM_Rest_API_CPT;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Make_Endpoint {

    const API_NAMESPACE = 'esmond-api/v1';
    const POST_TYPE     = 'apidata';

    public function __construct() {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // POST — create a new entry.
        register_rest_route(
            self::API_NAMESPACE,
            '/receive',
            array(
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_entry' ],
                'permission_callback' => [ $this, 'check_api_key' ],
                'args'                => $this->get_create_args(),
            )
        );

        // GET — list entries with optional filtering.
        register_rest_route(
            self::API_NAMESPACE,
            '/entries',
            array(
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_entries' ],
                'permission_callback' => [ $this, 'check_api_key' ],
                'args'                => array(
                    'source'   => array(
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                        'description'       => __( 'Filter entries by source identifier.', 'em-rest-api-cpt' ),
                    ),
                    'per_page' => array(
                        'type'        => 'integer',
                        'default'     => 10,
                        'minimum'     => 1,
                        'maximum'     => 100,
                        'description' => __( 'Number of entries to return per page.', 'em-rest-api-cpt' ),
                    ),
                    'page'     => array(
                        'type'        => 'integer',
                        'default'     => 1,
                        'minimum'     => 1,
                        'description' => __( 'Page number.', 'em-rest-api-cpt' ),
                    ),
                ),
            )
        );

        // DELETE — permanently remove a single entry.
        register_rest_route(
            self::API_NAMESPACE,
            '/entries/(?P<id>\d+)',
            array(
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_entry' ],
                'permission_callback' => [ $this, 'check_api_key' ],
                'args'                => array(
                    'id' => array(
                        'required'    => true,
                        'type'        => 'integer',
                        'minimum'     => 1,
                        'description' => __( 'Post ID of the entry to delete.', 'em-rest-api-cpt' ),
                    ),
                ),
            )
        );
    }

    /**
     * Validate the X-API-Key header against the stored key.
     * Returns true on success or WP_Error for a descriptive failure response.
     */
    public function check_api_key( WP_REST_Request $request ): bool|WP_Error {
        $stored_key   = get_option( EM_Rest_API_CPT::OPTION_API_KEY, '' );
        $provided_key = (string) $request->get_header( 'X-API-Key' );

        if ( empty( $stored_key ) ) {
            return new WP_Error(
                'rest_api_key_not_configured',
                __( 'The API key has not been configured. Please visit the plugin settings page.', 'em-rest-api-cpt' ),
                array( 'status' => 500 )
            );
        }

        if ( ! hash_equals( $stored_key, $provided_key ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'Invalid or missing API key. Pass your key in the X-API-Key header.', 'em-rest-api-cpt' ),
                array( 'status' => 401 )
            );
        }

        return true;
    }

    /**
     * POST /receive
     * Creates a new apidata post and stores associated meta.
     */
    public function create_entry( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $title       = sanitize_text_field( (string) $request->get_param( 'title' ) );
        $body        = wp_kses_post( (string) $request->get_param( 'body' ) );
        $source      = sanitize_text_field( (string) ( $request->get_param( 'source' )      ?? '' ) );
        $external_id = sanitize_text_field( (string) ( $request->get_param( 'external_id' ) ?? '' ) );

        $post_id = wp_insert_post(
            array(
                'post_title'   => $title,
                'post_content' => $body,
                'post_status'  => 'publish',
                'post_type'    => self::POST_TYPE,
            ),
            true // Return WP_Error on failure.
        );

        if ( is_wp_error( $post_id ) ) {
            return new WP_Error(
                'rest_cannot_create',
                $post_id->get_error_message(),
                array( 'status' => 500 )
            );
        }

        $received_at = current_time( 'mysql' );

        update_post_meta( $post_id, '_api_source',  $source );
        update_post_meta( $post_id, '_received_at', $received_at );
        update_post_meta( $post_id, '_external_id', $external_id );

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => __( 'Entry created successfully.', 'em-rest-api-cpt' ),
                'data'    => array(
                    'id'          => $post_id,
                    'title'       => $title,
                    'source'      => $source,
                    'external_id' => $external_id,
                    'received_at' => $received_at,
                ),
            ),
            201
        );
    }

    /**
     * GET /entries
     * Returns a paginated list of apidata posts with optional source filtering.
     */
    public function get_entries( WP_REST_Request $request ): WP_REST_Response {
        $args = array(
            'post_type'      => self::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => (int) $request->get_param( 'per_page' ),
            'paged'          => (int) $request->get_param( 'page' ),
            'orderby'        => 'date',
            'order'          => 'DESC',
        );

        $source = $request->get_param( 'source' );
        if ( ! empty( $source ) ) {
            $args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery
                array(
                    'key'     => '_api_source',
                    'value'   => $source,
                    'compare' => '=',
                ),
            );
        }

        $query   = new WP_Query( $args );
        $entries = array();

        foreach ( $query->posts as $post ) {
            $entries[] = array(
                'id'          => $post->ID,
                'title'       => $post->post_title,
                'body'        => $post->post_content,
                'source'      => get_post_meta( $post->ID, '_api_source', true ),
                'external_id' => get_post_meta( $post->ID, '_external_id', true ),
                'received_at' => get_post_meta( $post->ID, '_received_at', true ),
            );
        }

        return new WP_REST_Response(
            array(
                'success'     => true,
                'total'       => (int) $query->found_posts,
                'total_pages' => (int) $query->max_num_pages,
                'page'        => (int) $request->get_param( 'page' ),
                'data'        => $entries,
            ),
            200
        );
    }

    /**
     * DELETE /entries/{id}
     * Permanently deletes an apidata entry (bypasses trash).
     */
    public function delete_entry( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $post_id = (int) $request->get_param( 'id' );
        $post    = get_post( $post_id );

        if ( ! $post || self::POST_TYPE !== $post->post_type ) {
            return new WP_Error(
                'rest_not_found',
                __( 'No entry found with that ID.', 'em-rest-api-cpt' ),
                array( 'status' => 404 )
            );
        }

        $deleted = wp_delete_post( $post_id, true );

        if ( ! $deleted ) {
            return new WP_Error(
                'rest_cannot_delete',
                __( 'The entry could not be deleted.', 'em-rest-api-cpt' ),
                array( 'status' => 500 )
            );
        }

        return new WP_REST_Response(
            array(
                'success' => true,
                'message' => sprintf(
                    /* translators: %d: entry post ID */
                    __( 'Entry #%d has been permanently deleted.', 'em-rest-api-cpt' ),
                    $post_id
                ),
            ),
            200
        );
    }

    private function get_create_args(): array {
        return array(
            'title'       => array(
                'required'          => true,
                'type'              => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'validate_callback' => fn( $value ) => ! empty( trim( (string) $value ) ),
                'description'       => __( 'Title for the new entry.', 'em-rest-api-cpt' ),
            ),
            'body'        => array(
                'required'    => true,
                'type'        => 'string',
                'description' => __( 'Content body of the entry.', 'em-rest-api-cpt' ),
            ),
            'source'      => array(
                'required'          => false,
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'description'       => __( 'Identifier for the origin of this data (e.g. "mobile-app", "crm").', 'em-rest-api-cpt' ),
            ),
            'external_id' => array(
                'required'          => false,
                'type'              => 'string',
                'default'           => '',
                'sanitize_callback' => 'sanitize_text_field',
                'description'       => __( 'Optional ID from the external system for cross-referencing.', 'em-rest-api-cpt' ),
            ),
        );
    }
}
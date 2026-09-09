<?php

class EM_REST_API_CPT_REST_Baseline_Test extends WP_UnitTestCase {
    private const API_KEY = 'test-api-key';

    public function setUp(): void {
        parent::setUp();
        update_option( 'em_rest_api_cpt_api_key', self::API_KEY );
    }

    private function request( string $method, string $path, array $body = array(), array $headers = array(), array $query = array() ): WP_REST_Response {
        $parsed = wp_parse_url( $path );
        $route  = '/esmond-api/v1' . ( $parsed['path'] ?? $path );

        $request = new WP_REST_Request( $method, $route );

        if ( ! empty( $query ) ) {
            $request->set_query_params( $query );
        }

        foreach ( $headers as $name => $value ) {
            $request->set_header( $name, $value );
        }

        if ( ! empty( $body ) ) {
            $request->set_body( wp_json_encode( $body ) );
            $request->set_header( 'content-type', 'application/json' );
        }

        $response = rest_do_request( $request );

        $this->assertInstanceOf( WP_REST_Response::class, $response );

        return $response;
    }

    public function test_missing_api_key_is_rejected_for_create_route(): void {
        $response = $this->request(
            'POST',
            '/receive',
            array(
                'title' => 'Alpha',
                'body'  => 'Example note',
            )
        );

        $this->assertSame( 401, $response->get_status() );
        $this->assertSame( 'rest_forbidden', $response->as_error()->get_error_code() );
    }

    public function test_create_list_and_delete_entry_work_with_api_key(): void {
        $response = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Alpha Entry',
                'body'       => 'Example body',
                'source'     => 'mobile-app',
                'external_id' => 'abc-123',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $response->get_status() );
        $data = $response->get_data();
        $this->assertTrue( $data['success'] );
        $this->assertNotEmpty( $data['data']['id'] );

        $list = $this->request(
            'GET',
            '/entries',
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            ),
            array(
                'source'   => 'mobile-app',
                'per_page' => 10,
                'page'     => 1,
            )
        );

        $this->assertSame( 200, $list->get_status() );
        $payload = $list->get_data();
        $this->assertSame( 1, $payload['total'] );
        $this->assertCount( 1, $payload['data'] );
        $this->assertSame( 'Alpha Entry', $payload['data'][0]['title'] );

        $delete = $this->request(
            'DELETE',
            '/entries/' . $data['data']['id'],
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 200, $delete->get_status() );
    }

    public function test_required_fields_are_validated_before_inserting_entry(): void {
        $response = $this->request(
            'POST',
            '/receive',
            array(
                'title' => '',
                'body'  => 'Example note',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'rest_invalid_param', $response->as_error()->get_error_code() );
    }

    public function test_source_filtering_and_pagination_are_stable(): void {
        $this->factory()->post->create_and_get(
            array(
                'post_type'    => 'apidata',
                'post_title'   => 'First',
                'post_content' => 'Body one',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    '_api_source' => 'alpha',
                ),
            )
        );

        $this->factory()->post->create_and_get(
            array(
                'post_type'    => 'apidata',
                'post_title'   => 'Second',
                'post_content' => 'Body two',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    '_api_source' => 'alpha',
                ),
            )
        );

        $this->factory()->post->create_and_get(
            array(
                'post_type'    => 'apidata',
                'post_title'   => 'Third',
                'post_content' => 'Body three',
                'post_status'  => 'publish',
                'meta_input'   => array(
                    '_api_source' => 'beta',
                ),
            )
        );

        $response = $this->request(
            'GET',
            '/entries',
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            ),
            array(
                'source'   => 'alpha',
                'per_page' => 1,
                'page'     => 1,
            )
        );

        $payload = $response->get_data();
        $this->assertSame( 200, $response->get_status() );
        $this->assertSame( 2, $payload['total'] );
        $this->assertSame( 2, $payload['total_pages'] );
        $this->assertCount( 1, $payload['data'] );
    }

    public function test_wrong_post_type_and_missing_id_are_not_found(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type' => 'post',
                'post_title' => 'Regular post',
            )
        );

        $wrong_type = $this->request(
            'DELETE',
            '/entries/' . $post_id,
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 404, $wrong_type->get_status() );

        $missing = $this->request(
            'DELETE',
            '/entries/999999',
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 404, $missing->get_status() );
    }
}

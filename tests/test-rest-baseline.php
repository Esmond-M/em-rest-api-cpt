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

    public function test_anonymous_core_rest_access_is_blocked(): void {
        $post_id = self::factory()->post->create(
            array(
                'post_type'    => 'apidata',
                'post_title'   => 'Private data',
                'post_content' => 'Sensitive content',
                'post_status'  => 'publish',
            )
        );

        $request = new WP_REST_Request( 'GET', '/wp/v2/apidata/' . $post_id );
        $response = rest_do_request( $request );

        $this->assertSame( 404, $response->get_status() );
    }

    public function test_empty_titles_are_rejected_without_creating_entry(): void {
        $response = $this->request(
            'POST',
            '/receive',
            array(
                'title' => '   ',
                'body'  => 'Example note',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 400, $response->get_status() );
        $this->assertSame( 'rest_invalid_param', $response->as_error()->get_error_code() );
    }

    public function test_detail_get_and_patch_endpoints_work_for_existing_entry(): void {
        $create = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Original',
                'body'       => 'Original body',
                'source'     => 'legacy-app',
                'external_id' => 'orig-1',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $create->get_status() );
        $entry_id = $create->get_data()['data']['id'];

        $detail = $this->request(
            'GET',
            '/entries/' . $entry_id,
            array(),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 200, $detail->get_status() );
        $this->assertSame( 'Original', $detail->get_data()['data']['title'] );

        $alias = $this->request(
            'POST',
            '/entries',
            array(
                'title'      => 'Alias Entry',
                'body'       => 'Alias body',
                'source'     => 'new-app',
                'external_id' => 'alias-2',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $alias->get_status() );

        $patch = $this->request(
            'PATCH',
            '/entries/' . $entry_id,
            array(
                'title' => 'Updated title',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 200, $patch->get_status() );
        $this->assertSame( 'Updated title', $patch->get_data()['data']['title'] );
        $this->assertSame( 'Original body', $patch->get_data()['data']['body'] );

        $invalid = $this->request(
            'PATCH',
            '/entries/' . $entry_id,
            array(
                'title' => '   ',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 400, $invalid->get_status() );
    }

    public function test_duplicate_identity_rejected_with_existing_entry_id(): void {
        $first = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Original entry',
                'body'       => 'Original body',
                'source'     => 'Mobile-App',
                'external_id' => 'AB-123',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $first->get_status() );
        $existing_id = $first->get_data()['data']['id'];

        $duplicate = $this->request(
            'POST',
            '/entries',
            array(
                'title'      => 'Duplicate entry',
                'body'       => 'Should be rejected',
                'source'     => 'mobile-app',
                'external_id' => 'ab-123',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 409, $duplicate->get_status() );
        $this->assertSame( 'rest_duplicate_entry', $duplicate->as_error()->get_error_code() );
        $this->assertSame( $existing_id, $duplicate->as_error()->get_error_data()['existing_id'] );
    }

    public function test_duplicate_identity_is_normalized_and_allows_different_sources(): void {
        $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Case-sensitive source',
                'body'       => 'First body',
                'source'     => 'Alpha',
                'external_id' => 'A-1',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $duplicate = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Same normalized identity',
                'body'       => 'Should fail',
                'source'     => 'alpha',
                'external_id' => 'a-1',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 409, $duplicate->get_status() );

        $allowed = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Different source',
                'body'       => 'Allowed',
                'source'     => 'beta',
                'external_id' => 'A-1',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $allowed->get_status() );
    }

    public function test_patch_conflict_is_rejected_when_resulting_identity_matches_another_entry(): void {
        $first = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Lead one',
                'body'       => 'Lead body',
                'source'     => 'crm',
                'external_id' => 'lead-7',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $second = $this->request(
            'POST',
            '/receive',
            array(
                'title'      => 'Lead two',
                'body'       => 'Second body',
                'source'     => 'portal',
                'external_id' => 'lead-9',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 201, $second->get_status() );
        $second_id = $second->get_data()['data']['id'];

        $conflict = $this->request(
            'PATCH',
            '/entries/' . $second_id,
            array(
                'source'     => 'crm',
                'external_id' => 'lead-7',
            ),
            array(
                'X-API-Key' => self::API_KEY,
            )
        );

        $this->assertSame( 409, $conflict->get_status() );
        $this->assertSame( 'rest_duplicate_entry', $conflict->as_error()->get_error_code() );
        $this->assertSame( $first->get_data()['data']['id'], $conflict->as_error()->get_error_data()['existing_id'] );
    }
}

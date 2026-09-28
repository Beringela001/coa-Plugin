<?php
/** Private administrative metadata must never ride a public COA response. */
class PepSelect_COA_Archive_REST_Meta_Privacy_Test extends WP_UnitTestCase {
	private $post_id;
	private $administrator;
	private $private_values;

	public function set_up() {
		parent::set_up();
		do_action( 'init' );
		// WordPress resets meta registrations between tests; register on a fresh instance.
		$fields = new PepSelect\COAArchive\COA_Test_Fields( new PepSelect\COAArchive\Dependencies() );
		$fields->register_rest_meta();
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		$this->administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$this->post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish' ) );
		$this->private_values = array( 'internal_notes' => 'PRIVATE-OPERATIONS-NOTE', 'verification_code' => 'PRIVATE-ACCESS-CODE', 'lab_verification_url' => 'https://example.org/private-verification' );
		foreach ( $this->private_values as $key => $value ) { update_post_meta( $this->post_id, $key, $value ); }
		update_post_meta( $this->post_id, 'public_notes', 'A public batch note.' );
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tear_down() {
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	public function test_anonymous_public_response_omits_private_metadata() {
		wp_set_current_user( 0 );
		$response = rest_do_request( new WP_REST_Request( 'GET', '/wp/v2/ps_coa_test/' . $this->post_id ) );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'A public batch note.', $data['meta']['public_notes'] );
		foreach ( $this->private_values as $key => $value ) {
			$this->assertArrayNotHasKey( $key, $data['meta'] );
			$this->assertStringNotContainsString( $value, wp_json_encode( $data ) );
		}
	}

	public function test_authenticated_ops_edit_context_retains_private_metadata() {
		wp_set_current_user( $this->administrator );
		$request = new WP_REST_Request( 'GET', '/wp/v2/ps_coa_test/' . $this->post_id );
		$request->set_param( 'context', 'edit' );
		$response = rest_do_request( $request );
		$this->assertSame( 200, $response->get_status() );
		$data = $response->get_data();
		foreach ( $this->private_values as $key => $value ) { $this->assertSame( $value, $data['meta'][ $key ] ); }
	}

	public function test_anonymous_cannot_request_edit_context() {
		wp_set_current_user( 0 );
		$request = new WP_REST_Request( 'GET', '/wp/v2/ps_coa_test/' . $this->post_id );
		$request->set_param( 'context', 'edit' );
		$response = rest_do_request( $request );
		$this->assertContains( $response->get_status(), array( 401, 403 ) );
	}
}

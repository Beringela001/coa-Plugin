<?php
/** Validated REST write endpoint, merge semantics, side effects, and wp/v2 guard. */
class PepSelect_COA_Archive_REST_Write_Endpoint_Test extends WP_UnitTestCase {
	private $endpoint;
	private $test_validation;
	private $compound_validation;
	private $administrator;

	public function set_up() {
		parent::set_up();
		do_action( 'init' );
		$this->test_validation     = new PepSelect\COAArchive\COA_Test_Validation();
		$this->compound_validation = new PepSelect\COAArchive\Compound_Validation();
		$this->endpoint            = new PepSelect\COAArchive\REST_Write_Endpoint( $this->test_validation, $this->compound_validation, new PepSelect\COAArchive\COA_Test_Service() );
		// Grant before the user object is built: WP_User caches allcaps on creation.
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		$this->administrator = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->administrator );
		global $wp_rest_server;
		$wp_rest_server = new WP_REST_Server();
		$this->endpoint->register_routes();
		PepSelect\COAArchive\REST_Write_Guard::register_hooks();
		do_action( 'rest_api_init', $wp_rest_server );
	}

	public function tear_down() {
		unset( $_POST['acf'], $_POST['post_status'], $_POST['post_ID'] );
		PepSelect\COAArchive\COA_Test_Validation::flush_legacy_photo_exempt_cache();
		global $wp_rest_server;
		$wp_rest_server = null;
		parent::tear_down();
	}

	/* ---------------------------------------------------------------- M1 */

	public function test_set_context_replaces_post_and_clear_context_restores_it() {
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_status' => 'failed', 'field_ps_coa_test_release_decision_note' => '' );
		// $_POST says the note is missing, so the failed outcome is rejected.
		$this->assertNotTrue( $this->test_validation->validate_approval( true, 'failed', array(), '' ) );
		$this->test_validation->set_context( array( 'workflow_stage' => 'complete', 'coa_status' => 'failed', 'release_decision_note' => 'Rejected after review.', 'is_current' => 0 ), 0, 'publish' );
		$this->assertTrue( $this->test_validation->validate_approval( true, 'failed', array(), '' ) );
		$this->test_validation->clear_context();
		$this->assertNotTrue( $this->test_validation->validate_approval( true, 'failed', array(), '' ) );
	}

	public function test_admin_acf_path_is_unchanged_when_no_context_is_set() {
		$this->assertFalse( $this->test_validation->has_context() );
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_status' => 'failed', 'field_ps_coa_test_release_decision_note' => 'Rejected.', 'field_ps_coa_test_is_current' => '0' );
		$this->assertTrue( $this->test_validation->validate_approval( true, 'failed', array(), '' ) );
	}

	/* ---------------------- M1 parity: constraints that were ACF-only */

	public function test_acf_only_constraints_are_now_enforced_by_validate() {
		$gif = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/gif' ) );
		$this->test_validation->set_context( array( 'workflow_stage' => 'complete' ), 0, 'publish' );
		$this->assertNotTrue( $this->test_validation->validate( true, '', array( 'name' => 'fentanyl_status' ), '' ), 'empty fentanyl_status must be rejected' );
		$this->assertNotTrue( $this->test_validation->validate( true, '0', array( 'name' => 'vials_tested' ), '' ), 'vials_tested below 1 must be rejected' );
		$this->assertNotTrue( $this->test_validation->validate( true, $gif, array( 'name' => 'batch_vial_photo' ), '' ), 'GIF vial photo must be rejected' );
		$this->test_validation->clear_context();

		$post = self::factory()->post->create( array( 'post_type' => 'post' ) );
		$this->compound_validation->set_context( array(), 0 );
		$this->assertNotTrue( $this->compound_validation->validate( true, $post, array( 'name' => 'compound_image_id' ), '' ), 'non-image compound image must be rejected' );
		$this->compound_validation->clear_context();
	}

	/* ---------------------------------------------------------------- M2 */

	public function test_partial_patch_never_fails_a_record_against_its_own_stored_data() {
		$compound = $this->compound();
		$test     = $this->failed_test( $compound, 'B-1001', 'Rejected after review.' );
		// Sends one unrelated field. Every cross-field rule must read stored values.
		$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'public_notes' => 'Updated copy.' ) );
		$this->assertSame( 200, $response->get_status(), $this->explain( $response ) );
		$this->assertSame( 'Rejected after review.', get_post_meta( $test, 'release_decision_note', true ) );
		$this->assertSame( 'Updated copy.', get_post_meta( $test, 'public_notes', true ) );
	}

	public function test_partial_patch_still_rejects_a_genuine_violation() {
		$compound = $this->compound();
		$test     = $this->failed_test( $compound, 'B-1002', 'Rejected after review.' );
		// Clearing the note is a real violation even though nothing else changed.
		$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'release_decision_note' => '' ) );
		$this->assertSame( 400, $response->get_status() );
		$this->assertSame( 'Rejected after review.', get_post_meta( $test, 'release_decision_note', true ), 'a rejected write must not persist' );
	}

	public function test_create_applies_the_same_defaults_the_admin_form_would() {
		$compound = $this->compound();
		$response = $this->dispatch( 'POST', '/pepselect-coa/v1/coa-test', array( 'compound_id' => $compound ) );
		$this->assertSame( 201, $response->get_status(), $this->explain( $response ) );
		$id = $response->get_data()['id'];
		$this->assertSame( 'vendor-vetting', get_post_meta( $id, 'workflow_stage', true ) );
		$this->assertSame( 'pending', get_post_meta( $id, 'coa_status', true ) );
		$this->assertSame( 'not-tested', get_post_meta( $id, 'fentanyl_status', true ) );
		$this->assertSame( 'mg', get_post_meta( $id, 'content_unit', true ) );
	}

	/* ---------------------------------------------------------------- M3 */

	public function test_validation_failure_returns_400_with_field_and_plugin_message() {
		$response = $this->dispatch( 'POST', '/pepselect-coa/v1/coa-test', array( 'compound_id' => 0 ) );
		$this->assertSame( 400, $response->get_status() );
		$data = $response->get_data();
		$this->assertSame( 'pepselect_coa_invalid_record', $data['code'] );
		$this->assertArrayHasKey( 'errors', $data['data'] );
		$fields = wp_list_pluck( $data['data']['errors'], 'field' );
		$this->assertContains( 'compound_id', $fields );
		foreach ( $data['data']['errors'] as $error ) {
			$this->assertArrayHasKey( 'field', $error );
			$this->assertArrayHasKey( 'message', $error );
			$this->assertNotSame( '', $error['message'] );
		}
	}

	public function test_unknown_field_is_rejected_rather_than_silently_dropped() {
		$response = $this->dispatch( 'POST', '/pepselect-coa/v1/coa-test', array( 'compound_id' => $this->compound(), 'failure_reason' => 'nope' ) );
		$this->assertSame( 400, $response->get_status() );
		$this->assertContains( 'failure_reason', wp_list_pluck( $response->get_data()['data']['errors'], 'field' ) );
	}

	/* ---------------------------------------------------------------- M4 */

	public function test_write_applies_the_acf_save_post_side_effects() {
		$compound = $this->compound();
		$first    = $this->approved_test( $compound, 'B-2001' );
		update_post_meta( $first, 'is_current', 1 );
		$second   = $this->approved_test( $compound, 'B-2002' );

		// ILS approvals additionally require a passing fentanyl screen, so send it.
		$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $second, array( 'is_current' => 1, 'testing_lab' => 'ils-labs', 'fentanyl_status' => 'pass', 'fentanyl_result' => 'Not detected' ) );
		$this->assertSame( 200, $response->get_status(), $this->explain( $response ) );
		// clear_other_current_tests
		$this->assertSame( '0', (string) get_post_meta( $first, 'is_current', true ) );
		// apply_ils_verification_default
		$this->assertSame( 'https://lab.ils-lab.com', get_post_meta( $second, 'lab_verification_url', true ) );
		// synchronize_title
		$this->assertStringContainsString( 'Batch B-2002', get_post( $second )->post_title );
	}

	public function test_compound_create_backfills_title_so_the_slug_is_not_the_post_id() {
		$response = $this->dispatch( 'POST', '/pepselect-coa/v1/compound', array( 'display_name' => 'GHK-Cu 50 mg', 'compound_name' => 'GHK-Cu', 'strength_value' => 50, 'strength_unit' => 'mg' ) );
		$this->assertSame( 201, $response->get_status(), $this->explain( $response ) );
		$id = $response->get_data()['id'];
		$this->assertSame( 'GHK-Cu 50 mg', get_post( $id )->post_title );
		$this->assertNotSame( (string) $id, get_post( $id )->post_name, 'an empty title would make WordPress fall back to the post ID' );
	}

	public function test_advisory_guidance_is_returned_as_warnings_and_never_a_400() {
		$compound = $this->compound();
		$future   = gmdate( 'Y-m-d', time() + ( 10 * DAY_IN_SECONDS ) );
		$test     = $this->failed_test( $compound, 'B-3001', 'Rejected after review.' );
		$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'test_date' => $future ) );
		$this->assertSame( 200, $response->get_status(), $this->explain( $response ) );
		$data = $response->get_data();
		$this->assertArrayHasKey( 'warnings', $data );
		$codes = wp_list_pluck( $data['warnings'], 'code' );
		$this->assertContains( 'future_test_date', $codes );
	}

	/* ---------------------------------------------------------------- M6 */

	public function test_core_rest_writes_are_blocked_but_reads_stay_open() {
		$compound = $this->compound();
		$create   = new WP_REST_Request( 'POST', '/wp/v2/ps_coa_test' );
		$create->set_body_params( array( 'title' => 'Direct write', 'status' => 'publish' ) );
		$blocked = rest_get_server()->dispatch( $create );
		$this->assertSame( 403, $blocked->get_status() );
		$this->assertSame( 'pepselect_coa_write_route_required', $blocked->get_data()['code'] );

		$read = rest_get_server()->dispatch( new WP_REST_Request( 'GET', '/wp/v2/ps_compound/' . $compound ) );
		$this->assertSame( 200, $read->get_status(), 'ops must still be able to read records back' );
	}

	public function test_core_rest_write_block_can_be_reopened_for_migrations() {
		add_filter( 'pepselect_coa_allow_core_rest_write', '__return_true' );
		$create = new WP_REST_Request( 'POST', '/wp/v2/ps_coa_test' );
		$create->set_body_params( array( 'title' => 'Migration write', 'status' => 'publish' ) );
		$response = rest_get_server()->dispatch( $create );
		remove_filter( 'pepselect_coa_allow_core_rest_write', '__return_true' );
		$this->assertNotSame( 403, $response->get_status() );
	}

	/* ------------------------------------------- legacy photo allowlist */

	public function test_legacy_photo_exemption_is_allowlist_only_under_context() {
		$compound = $this->compound();
		$test     = $this->failed_test( $compound, 'B-4001', 'Rejected after review.' );
		delete_post_meta( $test, 'batch_vial_photo' );

		$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'public_notes' => 'x' ) );
		$this->assertSame( 400, $response->get_status(), 'omitting fields must not buy the exemption' );

		$allow = static function () use ( $test ) { return array( $test ); };
		add_filter( 'pepselect_coa_legacy_photo_exempt_ids', $allow );
		PepSelect\COAArchive\COA_Test_Validation::flush_legacy_photo_exempt_cache();
		$allowed = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'public_notes' => 'x' ) );
		remove_filter( 'pepselect_coa_legacy_photo_exempt_ids', $allow );
		$this->assertSame( 200, $allowed->get_status(), $this->explain( $allowed ) );
	}

	public function test_shipped_legacy_batches_resolve_by_batch_number() {
		$compound = $this->compound();
		foreach ( PepSelect\COAArchive\COA_Test_Validation::LEGACY_PHOTO_EXEMPT_BATCHES as $batch ) {
			$test = $this->failed_test( $compound, $batch, 'Rejected after review.' );
			delete_post_meta( $test, 'batch_vial_photo' );
			PepSelect\COAArchive\COA_Test_Validation::flush_legacy_photo_exempt_cache();
			$this->assertContains( $test, PepSelect\COAArchive\COA_Test_Validation::legacy_photo_exempt_ids(), $batch . ' must resolve to its post ID' );
			$response = $this->dispatch( 'PATCH', '/pepselect-coa/v1/coa-test/' . $test, array( 'public_notes' => 'x' ) );
			$this->assertSame( 200, $response->get_status(), $this->explain( $response ) );
		}
	}

	/* -------------------------------------------------------- helpers */

	private function dispatch( $method, $route, array $body ) {
		$request = new WP_REST_Request( $method, $route );
		$request->add_header( 'content-type', 'application/json' );
		$request->set_body( wp_json_encode( $body ) );
		return rest_get_server()->dispatch( $request );
	}

	private function explain( $response ) {
		$data = $response->get_data();
		return isset( $data['data']['errors'] ) ? wp_json_encode( $data['data']['errors'] ) : wp_json_encode( $data );
	}

	private function compound() {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Retatrutide 10 mg' ) );
		foreach ( array( 'display_name' => 'Retatrutide 10 mg', 'compound_name' => 'Retatrutide', 'strength_value' => 10, 'strength_unit' => 'mg', 'is_active' => 1 ) as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	private function image() {
		return self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg' ) );
	}

	/** A stored record that is complete + failed and passes every rule. */
	private function failed_test( $compound, $batch, $note ) {
		return $this->stored_test( $compound, $batch, array( 'coa_status' => 'failed', 'release_decision_note' => $note, 'is_current' => 0 ) );
	}

	/** A stored record that is complete + approved and passes every rule. */
	private function approved_test( $compound, $batch ) {
		$pdf = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'application/pdf' ) );
		return $this->stored_test(
			$compound,
			$batch,
			array(
				'coa_status'      => 'approved',
				'test_date'       => '2026-01-15',
				'testing_lab'     => 'janoshik',
				'vials_tested'    => 3,
				'coa_pdf_id'      => $pdf,
				'coa_page_images' => array( $this->image() ),
				'lab_report_url'  => 'https://lab.example/report',
				'is_current'      => 0,
			)
		);
	}

	private function stored_test( $compound, $batch, array $overrides ) {
		$id   = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => $batch ) );
		$meta = array_merge(
			array(
				'compound_id'            => $compound,
				'batch_number'           => $batch,
				'workflow_stage'         => 'complete',
				'vial_cap_color'         => 'silver',
				'vial_crimp_color'       => 'blue',
				'batch_vial_photo'       => $this->image(),
				'content_unit'           => 'mg',
				'fentanyl_status'        => 'not-tested',
				'fentanyl_result'        => '',
				'fentanyl_method'        => 'Immunoassay',
				'fentanyl_specification' => 'Immunoassay, 50 ng/mL cutoff',
			),
			$overrides
		);
		foreach ( $meta as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

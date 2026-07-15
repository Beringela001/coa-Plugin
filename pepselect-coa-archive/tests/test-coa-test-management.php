<?php
/** COA-3 management tests and environment-dependent scaffolding. */
class PepSelect_COA_Archive_COA_Test_Management_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\COA_Test_Validation */
	private $validator;

	public function set_up() { parent::set_up(); $this->validator = new PepSelect\COAArchive\COA_Test_Validation(); }

	public function test_field_group_uses_stable_key_and_location() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' ) . file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-validation.php' );
		$this->assertStringContainsString( 'group_ps_coa_test_details', $source );
		$this->assertStringContainsString( "'value' => Post_Types::COA_TEST", $source );
		foreach ( array( 'compound_id', 'batch_number', 'batch_vial_photo', 'batch_identity_photos', 'workflow_stage', 'test_date', 'expected_coa_date', 'vendor_status_note', 'public_status_note', 'partial_results_available', 'release_decision_note', 'testing_lab', 'status', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'pending_lab_url', 'coa_pdf_id', 'page_images', 'internal_notes' ) as $suffix ) { $this->assertStringContainsString( 'field_ps_coa_test_' . $suffix, $source ); }
	}

	public function test_group_registers_on_acf_init_when_available() {
		if ( ! function_exists( 'acf_get_local_field_group' ) ) { $this->markTestSkipped( 'ACF test utilities are unavailable.' ); }
		do_action( 'acf/init' );
		$this->assertNotFalse( acf_get_local_field_group( 'group_ps_coa_test_details' ) );
	}

	public function test_required_and_choice_validation() {
		$this->assertNotTrue( $this->validate( 0, 'compound_id' ) );
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound' ) );
		$this->assertTrue( $this->validate( $compound, 'compound_id' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_status' => 'approved' );
		$this->assertNotTrue( $this->validate( '', 'batch_number' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'testing_lab' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'coa_status' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'workflow_stage' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'purity_status' ) );
		unset( $_POST['acf'] );
	}

	public function test_cross_field_validation() {
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_testing_lab' => 'other' );
		$this->assertNotTrue( $this->validate( '', 'other_testing_lab' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_maximum_net_content' => '8' );
		$this->assertNotTrue( $this->validate( '9', 'minimum_net_content' ) );
		unset( $_POST['acf'] );
	}

	public function test_release_states_require_identity_data_but_incoming_states_do_not() {
		$_POST['acf'] = array( 'field_ps_coa_test_status' => 'approved', 'field_ps_coa_test_workflow_stage' => 'complete' );
		foreach ( array( 'test_date', 'testing_lab', 'vial_crimp_color', 'vial_cap_color', 'vials_tested' ) as $name ) { $this->assertNotTrue( $this->validate( '', $name ) ); }
		$_POST['acf']['field_ps_coa_test_status'] = 'failed';
		$this->assertTrue( $this->validate( '', 'test_date' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_status' => 'pending', 'field_ps_coa_test_workflow_stage' => 'vendor-vetting' );
		foreach ( array( 'batch_number', 'testing_lab', 'vial_crimp_color', 'vial_cap_color' ) as $name ) { $this->assertTrue( $this->validate( '', $name ) ); }
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'waiting-on-vendor';
		$this->assertTrue( $this->validate( '', 'batch_number' ) ); $this->assertTrue( $this->validate( '', 'testing_lab' ) ); $this->assertNotTrue( $this->validate( '', 'vial_crimp_color' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'submitted-to-lab';
		$this->assertTrue( $this->validate( '', 'batch_number' ) ); $this->assertTrue( $this->validate( '', 'testing_lab' ) ); $this->assertNotTrue( $this->validate( '', 'expected_coa_date' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'in-testing';
		$this->assertNotTrue( $this->validate( '', 'batch_number' ) ); $this->assertNotTrue( $this->validate( '', 'testing_lab' ) ); $this->assertNotTrue( $this->validate( '', 'expected_coa_date' ) );
		unset( $_POST['acf'] );
	}

	public function test_other_vial_colors_require_their_custom_values() {
		$_POST['acf'] = array( 'field_ps_coa_test_status' => 'pending', 'field_ps_coa_test_workflow_stage' => 'waiting-on-vendor', 'field_ps_coa_test_vial_crimp_color' => 'other', 'field_ps_coa_test_vial_cap_color' => 'other' );
		$this->assertNotTrue( $this->validate( '', 'other_vial_crimp_color' ) );
		$this->assertNotTrue( $this->validate( '', 'other_vial_cap_color' ) );
		$this->assertTrue( $this->validate( 'Teal', 'other_vial_crimp_color' ) );
		$this->assertTrue( $this->validate( 'Ivory', 'other_vial_cap_color' ) );
		unset( $_POST['acf'] );
	}

	public function test_approved_requires_valid_documents_and_no_failed_results() {
		$pdf = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf' ) );
		$image = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_coa_pdf_id' => $pdf, 'field_ps_coa_test_page_images' => array( $image ), 'field_ps_coa_test_lab_report_url' => 'https://lab.example/report/42', 'field_ps_coa_test_purity_status' => 'pass' );
		$this->assertTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		$_POST['acf']['field_ps_coa_test_lab_report_url'] = '';
		$this->assertNotTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		$_POST['acf']['field_ps_coa_test_lab_report_url'] = 'https://lab.example/report/42';
		$_POST['acf']['field_ps_coa_test_purity_status'] = 'fail';
		$this->assertNotTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		unset( $_POST['acf'] );
	}

	public function test_numeric_and_date_validation() {
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_status' => 'approved' );
		$this->assertNotTrue( $this->validate( '2026-02-30', 'test_date' ) );
		$this->assertNotTrue( $this->validate( '20260230', 'date_received' ) );
		$this->assertTrue( $this->validate( '20260710', 'test_date' ) );
		$this->assertTrue( $this->validate( '2026-07-08', 'test_date' ) );
		$this->assertTrue( $this->validate( '20260710', 'date_received' ) );
		$this->assertTrue( $this->validate( '2026-07-10', 'date_received' ) );
		$this->assertNotTrue( $this->validate( 0, 'vials_tested' ) );
		$this->assertNotTrue( $this->validate( 1.5, 'vials_tested' ) );
		$this->assertTrue( $this->validate( 3, 'vials_tested' ) );
		$this->assertNotTrue( $this->validate( 100.01, 'purity_percentage' ) );
		$this->assertTrue( $this->validate( 99.79, 'purity_percentage' ) );
		unset( $_POST['acf'] );
	}

	public function test_ils_defaults_and_result_choice_are_declared() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' );
		$this->assertStringContainsString( "'default_value' => 'White lyophilized powder'", $source );
		$this->assertStringContainsString( "'default_value' => 'EU/mL'", $source );
		$this->assertStringContainsString( "'endotoxin_status', 'Endotoxin Status', 'reported'", $source );
		$this->assertStringContainsString( "'reported' => 'Reported'", $source );
		$this->assertStringContainsString( "Arsenic, cadmium, chromium, mercury, and lead were not detected.", $source );
		$this->assertStringContainsString( "'default_value' => 'No Growth'", $source );
	}

	public function test_saved_ils_text_values_are_not_overwritten() {
		PepSelect\COAArchive\Capabilities::grant_to_administrators(); $user = self::factory()->user->create( array( 'role' => 'administrator' ) ); wp_set_current_user( $user );
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) );
		update_post_meta( $post_id, 'sample_appearance', 'Clear solution' ); update_post_meta( $post_id, 'heavy_metals_summary', 'Manual summary' ); update_post_meta( $post_id, 'sterility_result', 'Manual result' );
		( new PepSelect\COAArchive\COA_Test_Service() )->after_save( $post_id );
		$this->assertSame( 'Clear solution', get_post_meta( $post_id, 'sample_appearance', true ) );
		$this->assertSame( 'Manual summary', get_post_meta( $post_id, 'heavy_metals_summary', true ) );
		$this->assertSame( 'Manual result', get_post_meta( $post_id, 'sterility_result', true ) );
	}

	public function test_hidden_legacy_fields_are_not_in_active_group_or_rest() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' );
		foreach ( array( 'bioburden_status', 'bioburden_result', 'residual_solvents_status', 'residual_solvents_result' ) as $name ) { $this->assertStringNotContainsString( "'" . $name . "'", $source ); }
		$this->assertStringContainsString( "'lab_report_url'", $source );
		do_action( 'init' ); $keys = get_registered_meta_keys( 'post', 'ps_coa_test' );
		foreach ( array( 'bioburden_status', 'bioburden_result', 'residual_solvents_status', 'residual_solvents_result' ) as $name ) { $this->assertArrayNotHasKey( $name, $keys ); }
		foreach ( array( 'lab_report_url', 'expected_coa_date', 'release_decision_note', 'vial_crimp_color', 'vial_cap_color', 'pending_lab_url' ) as $name ) { $this->assertArrayNotHasKey( $name, $keys ); }
		foreach ( array( 'workflow_stage', 'public_status_note' ) as $name ) { $this->assertArrayHasKey( $name, $keys ); } $this->assertArrayNotHasKey( 'vendor_status_note', $keys );
	}

	public function test_hidden_legacy_metadata_is_not_deleted() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) );
		foreach ( array( 'bioburden_status' => 'pass', 'residual_solvents_result' => 'legacy', 'lab_report_url' => 'https://example.com/report' ) as $key => $value ) { update_post_meta( $post_id, $key, $value ); }
		( new PepSelect\COAArchive\COA_Test_Service() )->load_verification_url( '', $post_id, array() );
		$this->assertSame( 'pass', get_post_meta( $post_id, 'bioburden_status', true ) );
		$this->assertSame( 'legacy', get_post_meta( $post_id, 'residual_solvents_result', true ) );
		$this->assertSame( 'https://example.com/report', get_post_meta( $post_id, 'lab_report_url', true ) );
	}

	public function test_verification_fields_are_backward_compatible() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' );
		$this->assertStringContainsString( "'coa_number', 'COA Number'", $source );
		$this->assertStringContainsString( "field_ps_coa_test_coa_number", file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-validation.php' ) );
		$this->assertStringContainsString( "'verification_code', 'Access Code'", $source );
	}

	public function test_ils_verification_url_defaults_without_overwriting_manual_value() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) ); update_post_meta( $post_id, 'testing_lab', 'ils-labs' );
		$service = new PepSelect\COAArchive\COA_Test_Service();
		$this->assertSame( 'https://lab.ils-lab.com', $service->load_verification_url( '', $post_id, array() ) );
		$this->assertSame( 'https://manual.example/verify', $service->load_verification_url( 'https://manual.example/verify', $post_id, array() ) );
	}

	public function test_duplicate_is_blocked_and_current_post_excluded() {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound' ) );
		$existing = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_title' => 'Existing' ) );
		update_post_meta( $existing, 'compound_id', $compound ); update_post_meta( $existing, 'batch_number', 'RT30-0726-A' );
		$_POST['acf'] = array( 'field_ps_coa_test_compound_id' => $compound ); $_POST['post_ID'] = 0;
		$this->assertNotTrue( $this->validator->validate_duplicate( true, ' rt30-0726-a ', array(), '' ) );
		$_POST['post_ID'] = $existing;
		$this->assertTrue( $this->validator->validate_duplicate( true, 'RT30-0726-A', array(), '' ) );
		unset( $_POST['acf'], $_POST['post_ID'] );
	}

	public function test_only_one_current_test_per_compound_and_other_compounds_untouched() {
		PepSelect\COAArchive\Capabilities::grant_to_administrators(); $user = self::factory()->user->create( array( 'role' => 'administrator' ) ); wp_set_current_user( $user );
		$compound_a = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_title' => 'A' ) ); $compound_b = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_title' => 'B' ) );
		$first = $this->test_post( $compound_a, 'A-1', true ); $second = $this->test_post( $compound_a, 'A-2', true ); $other = $this->test_post( $compound_b, 'B-1', true );
		( new PepSelect\COAArchive\COA_Test_Service() )->after_save( $second );
		$this->assertSame( '0', get_post_meta( $first, 'is_current', true ) ); $this->assertSame( '1', get_post_meta( $second, 'is_current', true ) ); $this->assertSame( '1', get_post_meta( $other, 'is_current', true ) );
		update_post_meta( $second, 'is_current', 0 ); ( new PepSelect\COAArchive\COA_Test_Service() )->after_save( $second ); $this->assertSame( '0', get_post_meta( $first, 'is_current', true ) );
	}

	public function test_empty_title_initializes_and_manual_title_remains() {
		PepSelect\COAArchive\Capabilities::grant_to_administrators(); $user = self::factory()->user->create( array( 'role' => 'administrator' ) ); wp_set_current_user( $user );
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_title' => 'Retatrutide 30mg' ) ); update_post_meta( $compound, 'display_name', 'Retatrutide 30mg' );
		$id = $this->test_post( $compound, 'RT30-0726-A', false ); $service = new PepSelect\COAArchive\COA_Test_Service(); $service->after_save( $id );
		$this->assertSame( 'Retatrutide 30mg — Batch RT30-0726-A', get_post( $id )->post_title ); wp_update_post( array( 'ID' => $id, 'post_title' => 'Manual' ) ); $service->after_save( $id ); $this->assertSame( 'Manual', get_post( $id )->post_title );
	}

	public function test_private_fields_are_not_in_rest_schema() {
		do_action( 'init' ); $keys = get_registered_meta_keys( 'post', 'ps_coa_test' );
		$this->assertArrayNotHasKey( 'internal_notes', $keys ); $this->assertArrayNotHasKey( 'internal_batch_id', $keys ); $this->assertArrayNotHasKey( 'batch_number', $keys );
		$this->assertArrayNotHasKey( 'pending_lab_url', $keys ); $this->assertArrayNotHasKey( 'expected_coa_date', $keys ); $this->assertArrayHasKey( 'workflow_stage', $keys ); $this->assertArrayNotHasKey( 'release_decision_note', $keys );
	}

	public function test_admin_columns_are_coa_test_specific() {
		$admin = new PepSelect\COAArchive\COA_Test_Admin(); $columns = $admin->columns( array( 'cb' => 'cb', 'title' => 'Title', 'date' => 'Date' ) );
		$this->assertArrayHasKey( 'compound_id', $columns ); $this->assertArrayHasKey( 'workflow_stage', $columns ); $this->assertArrayHasKey( 'expected_coa_date', $columns ); $this->assertArrayHasKey( 'coa_pdf_id', $columns ); $this->assertFalse( has_filter( 'manage_ps_compound_posts_columns', array( $admin, 'columns' ) ) );
	}

	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
	private function test_post( $compound, $batch, $current ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_title' => '' ) ); update_post_meta( $id, 'compound_id', $compound ); update_post_meta( $id, 'batch_number', $batch ); update_post_meta( $id, 'is_current', $current ? 1 : 0 ); return $id; }
}

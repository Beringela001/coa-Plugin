<?php
/** COA-4D workflow, grouping, compatibility, and cache tests. */
class PepSelect_COA_Archive_COA_Workflow_Test extends WP_UnitTestCase {
	private $validator;
	private $visibility;
	private $repository;

	public function set_up() {
		parent::set_up(); do_action( 'init' );
		$this->validator = new PepSelect\COAArchive\COA_Test_Validation();
		$this->visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->repository = new PepSelect\COAArchive\COA_Test_Repository( $this->visibility );
		delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache();
	}

	public function tear_down() { unset( $_POST['acf'], $_POST['post_status'] ); delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache(); parent::tear_down(); }

	public function test_legacy_workflow_fallbacks_preserve_public_records() {
		$compound = $this->compound();
		$approved = $this->record( $compound, 'approved', '' );
		$pending = $this->record( $compound, 'pending', '' );
		$legacy_testing = $this->record( $compound, 'in-testing', '' );
		$this->assertSame( 'complete', PepSelect\COAArchive\COA_Workflow::stage( $approved ) );
		$this->assertSame( 'vendor-vetting', PepSelect\COAArchive\COA_Workflow::stage( $pending ) );
		$this->assertSame( 'in-testing', PepSelect\COAArchive\COA_Workflow::stage( $legacy_testing ) );
		$this->assertSame( 'pending', PepSelect\COAArchive\COA_Workflow::outcome( $legacy_testing ) );
		$this->assertTrue( $this->visibility->is_test_public( $approved, $compound ) );
		$this->assertTrue( $this->visibility->is_test_public( $pending, $compound ) );
	}

	public function test_final_outcomes_and_current_require_complete_workflow() {
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'in-testing', 'field_ps_coa_test_status' => 'approved', 'field_ps_coa_test_is_current' => '1' );
		$this->assertNotTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		$this->assertNotTrue( $this->validate( '1', 'is_current' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'complete'; $_POST['post_status'] = 'publish';
		$this->assertTrue( $this->validate( '1', 'is_current' ) );
		$_POST['acf']['field_ps_coa_test_status'] = 'failed'; $_POST['acf']['field_ps_coa_test_release_decision_note'] = 'Not released.';
		$this->assertNotTrue( $this->validate( '1', 'is_current' ) );
		$this->assertNotTrue( $this->validator->validate_approval( true, 'failed', array(), '' ) );
	}

	public function test_failed_requires_release_decision_and_approved_requires_final_url() {
		$pdf = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf' ) );
		$image = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'complete', 'field_ps_coa_test_is_current' => '0', 'field_ps_coa_test_release_decision_note' => '' );
		$this->assertNotTrue( $this->validator->validate_approval( true, 'failed', array(), '' ) );
		$_POST['acf']['field_ps_coa_test_release_decision_note'] = 'Rejected after review.';
		$this->assertTrue( $this->validator->validate_approval( true, 'failed', array(), '' ) );
		$_POST['acf'] += array( 'field_ps_coa_test_coa_pdf_id' => $pdf, 'field_ps_coa_test_page_images' => array( $image ), 'field_ps_coa_test_pending_lab_url' => 'https://lab.example/pending', 'field_ps_coa_test_lab_report_url' => '' );
		$this->assertNotTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		$_POST['acf']['field_ps_coa_test_lab_report_url'] = 'https://lab.example/final';
		$this->assertTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
	}

	public function test_testing_stages_require_valid_expected_date() {
		foreach ( array( 'submitted-to-lab', 'in-testing' ) as $stage ) {
			$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => $stage );
			$this->assertNotTrue( $this->validate( '', 'expected_coa_date' ) );
			$this->assertTrue( $this->validate( '2026-07-30', 'expected_coa_date' ) );
		}
		$this->assertNotTrue( $this->validate( '2026-02-30', 'expected_coa_date' ) );
	}

	public function test_history_classification_keeps_latest_incoming_and_failed_distinct() {
		$compound = $this->compound();
		$latest = $this->record( $compound, 'approved', 'complete', '20260720' );
		$incoming = $this->record( $compound, 'pending', 'in-testing', '', '20260730' ); update_post_meta( $incoming, 'testing_lab', 'ils-labs' ); update_post_meta( $incoming, 'batch_number', 'INCOMING' );
		$failed = $this->record( $compound, 'failed', 'complete', '20260710' );
		$classified = $this->repository->classified_for_compound( $compound );
		$this->assertSame( array( $latest ), wp_list_pluck( $classified['approved'], 'ID' ) );
		$this->assertSame( array( $incoming ), wp_list_pluck( $classified['incoming'], 'ID' ) );
		$this->assertSame( array( $failed ), wp_list_pluck( $classified['failed'], 'ID' ) );
		$this->assertNotContains( $failed, wp_list_pluck( $classified['approved'], 'ID' ) );
	}

	public function test_failed_only_compounds_are_opt_in_for_archive() {
		$compound = $this->compound(); $this->record( $compound, 'failed', 'complete' );
		$this->assertNotContains( $compound, $this->repository->compound_ids_with_public_tests() );
		$this->assertContains( $compound, $this->repository->compound_ids_with_public_tests( true ) );
	}

	public function test_workflow_changes_advance_plugin_cache_namespace() {
		$before = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( 10 ) );
		PepSelect\COAArchive\Archive_Cache::invalidate_for_post( $this->record( $this->compound(), 'pending', 'vendor-vetting' ) );
		$after = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( 10 ) );
		$this->assertNotSame( $before, $after );
	}

	public function test_public_status_styles_use_distinct_semantic_classes() {
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' );
		foreach ( array( 'ps-coa-batch-pill--success', 'ps-coa-batch-pill--progress', 'ps-coa-batch-pill--vendor', 'ps-coa-batch-pill--failed' ) as $class ) { $this->assertStringContainsString( $class, $css ); }
	}

	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
	private function compound() { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish' ) ); update_post_meta( $id, 'is_active', 1 ); return $id; }
	private function record( $compound, $status, $stage, $test_date = '20260701', $expected = '' ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish' ) );
		update_post_meta( $id, 'compound_id', $compound ); update_post_meta( $id, 'coa_status', $status ); update_post_meta( $id, 'batch_number', 'B-' . $id ); update_post_meta( $id, 'testing_lab', 'ils-labs' ); update_post_meta( $id, 'vials_tested', 1 );
		if ( '' !== $stage ) { update_post_meta( $id, 'workflow_stage', $stage ); }
		if ( '' !== $test_date ) { update_post_meta( $id, 'test_date', $test_date ); }
		if ( '' !== $expected ) { update_post_meta( $id, 'expected_coa_date', $expected ); }
		return $id;
	}
}

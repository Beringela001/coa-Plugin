<?php
/** Focused COA-5D shared timing, requirements, and validation tests. */
class PepSelect_COA_Archive_COA_5D_Admin_Workflow_Test extends WP_UnitTestCase {
	private $admin_id;
	private $compound_id;

	public function set_up() {
		parent::set_up();
		do_action( 'init' );
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
		$this->compound_id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Workflow Compound' ) );
	}

	public function tear_down() { unset( $_POST['acf'], $_POST['post_ID'], $_POST['post_status'] ); wp_set_current_user( 0 ); parent::tear_down(); }

	public function test_shared_timing_uses_site_calendar_and_excludes_closed_outcomes() {
		update_option( 'timezone_string', 'America/New_York' );
		$today = new DateTimeImmutable( '2026-07-15 00:00:00', wp_timezone() );
		$overdue = $this->record( 'submitted-to-lab', 'pending', '20260710' );
		$due_today = $this->record( 'in-testing', 'pending', '2026-07-15' );
		$due_three = $this->record( 'in-testing', 'pending', '20260718' );
		$not_soon = $this->record( 'in-testing', 'pending', '20260719' );
		$missing = $this->record( 'in-testing', 'pending', '' );
		$complete = $this->record( 'complete', 'approved', '20260701' );
		$failed = $this->record( 'in-testing', 'failed', '20260701' );
		$this->assertSame( 'overdue', PepSelect\COAArchive\COA_Admin_Workflow::timing( $overdue, $today )['status'] );
		$this->assertSame( -5, PepSelect\COAArchive\COA_Admin_Workflow::timing( $overdue, $today )['days'] );
		$this->assertSame( 'due-soon', PepSelect\COAArchive\COA_Admin_Workflow::timing( $due_today, $today )['status'] );
		$this->assertSame( 'due-soon', PepSelect\COAArchive\COA_Admin_Workflow::timing( $due_three, $today )['status'] );
		$this->assertSame( 'none', PepSelect\COAArchive\COA_Admin_Workflow::timing( $not_soon, $today )['status'] );
		$this->assertSame( 'no-date', PepSelect\COAArchive\COA_Admin_Workflow::timing( $missing, $today )['status'] );
		$this->assertSame( 'none', PepSelect\COAArchive\COA_Admin_Workflow::timing( $complete, $today )['status'] );
		$this->assertSame( 'none', PepSelect\COAArchive\COA_Admin_Workflow::timing( $failed, $today )['status'] );
	}

	public function test_early_stage_checklists_do_not_require_guessed_physical_data() {
		$test = $this->record( 'vendor-vetting', 'pending', '' );
		$vendor = $this->states( $test, 'vendor-vetting', 'pending' );
		foreach ( array( 'Batch Number', 'Testing Laboratory', 'Cap Color', 'Crimp Color', 'Batch Vial Photo', 'Expected COA Date', 'Documented Test Results', 'Original COA PDF', 'Lab Report URL' ) as $label ) { $this->assertSame( 'not-required', $vendor[ $label ], $label ); }
		$waiting = $this->states( $test, 'waiting-on-vendor', 'pending' );
		foreach ( array( 'Cap Color', 'Crimp Color', 'Batch Vial Photo' ) as $label ) { $this->assertSame( 'not-required', $waiting[ $label ], $label ); }
		$this->assertSame( 'optional', $waiting['Claimed Content'] );
		$this->assertStringContainsString( 'Do not guess', PepSelect\COAArchive\COA_Test_Validation::workflow_guidance( 'vendor-vetting', 'pending' ) );
	}

	public function test_verification_and_final_checklists_match_active_validation() {
		$test = $this->record( 'in-testing', 'pending', '' );
		$testing = $this->states( $test, 'in-testing', 'pending' );
		foreach ( array( 'Batch Number', 'Testing Laboratory', 'Cap Color', 'Crimp Color', 'Batch Vial Photo', 'Expected COA Date' ) as $label ) { $this->assertSame( 'missing', $testing[ $label ], $label ); }
		$this->assertSame( 'optional', $testing['Documented Test Results'] );
		$approved = $this->states( $test, 'complete', 'approved' );
		foreach ( array( 'Test Date', 'Vials Tested', 'Original COA PDF', 'Certificate Page Images', 'Lab Report URL' ) as $label ) { $this->assertSame( 'missing', $approved[ $label ], $label ); }
		update_post_meta( $test, 'testing_lab', 'ils-labs' );
		$approved_ils = $this->states( $test, 'complete', 'approved' );
		$this->assertSame( 'missing', $approved_ils['Fentanyl Screen'] );
		$failed = $this->states( $test, 'complete', 'failed' );
		$this->assertSame( 'missing', $failed['Release Decision Note'] );
		$this->assertSame( 'missing', $failed['Fentanyl Screen Method'] );
		$this->assertSame( 'missing', $failed['Fentanyl Screen Specification'] );
		$this->assertSame( 'optional', $failed['Lab Report URL'] );
	}

	public function test_clear_messages_keep_invalid_transitions_blocked() {
		$validator = new PepSelect\COAArchive\COA_Test_Validation();
		$_POST['acf'] = array( 'field_ps_coa_test_workflow_stage' => 'in-testing', 'field_ps_coa_test_status' => 'pending' );
		$this->assertSame( 'Batch Number is required before moving this test to Verification in Progress.', $validator->validate( true, '', array( 'name' => 'batch_number' ), '' ) );
		$this->assertSame( 'Batch Vial Photo is required before moving this test to Verification in Progress.', $validator->validate( true, '', array( 'name' => 'batch_vial_photo' ), '' ) );
		$this->assertStringContainsString( 'Testing Laboratory', $validator->validate( true, '', array( 'name' => 'testing_lab' ), '' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'submitted-to-lab';
		$this->assertStringContainsString( 'Cap Color', $validator->validate( true, '', array( 'name' => 'vial_cap_color' ), '' ) );
		$this->assertStringContainsString( 'Crimp Color', $validator->validate( true, '', array( 'name' => 'vial_crimp_color' ), '' ) );
		$_POST['acf']['field_ps_coa_test_status'] = 'approved';
		$this->assertStringContainsString( 'Workflow Stage must be Completed', $validator->validate_approval( true, 'approved', array(), '' ) );
	}

	private function record( $stage, $status, $expected ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Workflow record', 'post_author' => $this->admin_id ) );
		foreach ( array( 'compound_id' => $this->compound_id, 'workflow_stage' => $stage, 'coa_status' => $status, 'expected_coa_date' => $expected ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}

	private function states( $post_id, $stage, $status ) { $states = array(); foreach ( PepSelect\COAArchive\COA_Test_Validation::workflow_requirements( $post_id, $stage, $status ) as $item ) { $states[ $item['label'] ] = $item['state']; } return $states; }
}

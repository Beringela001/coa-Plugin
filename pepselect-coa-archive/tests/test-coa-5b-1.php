<?php
/** Focused COA-5B.1 admin workflow, validation, and title synchronization tests. */
class PepSelect_COA_Archive_COA_5B_1_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\COA_Test_Validation */
	private $validator;

	/** @var PepSelect\COAArchive\COA_Test_Service */
	private $service;

	public function set_up() {
		parent::set_up();
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->validator = new PepSelect\COAArchive\COA_Test_Validation();
		$this->service = new PepSelect\COAArchive\COA_Test_Service();
	}

	public function tear_down() { unset( $_POST['acf'], $_POST['post_ID'] ); parent::tear_down(); }

	public function test_active_and_featured_explanations_are_exact() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-compound-fields.php' );
		$this->assertStringContainsString( 'Controls whether this compound is eligible to appear publicly in the COA Archive and Vetting History pages. Turning it off does not delete its tests or reports.', $source );
		$this->assertStringContainsString( 'Active controls public eligibility.', $source );
		$this->assertStringContainsString( 'Gives this compound priority placement in supported archive or promotional sections. Featured does not control whether the compound is publicly visible.', $source );
		$this->assertStringContainsString( 'Featured controls priority or emphasis.', $source );
	}

	public function test_inactive_featured_compound_and_its_tests_are_retained_but_not_public() {
		$compound = $this->compound( 'Tesamorelin 10 mg' );
		update_post_meta( $compound, 'is_active', 0 );
		update_post_meta( $compound, 'is_featured', 1 );
		$test = $this->test_record( $compound, 'complete', 'TESA-10-A', 'approved', 'publish' );
		$visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->assertFalse( $visibility->is_compound_public( $compound ) );
		$this->assertFalse( $visibility->is_test_public( $test ) );
		$this->assertNotNull( get_post( $compound ) );
		$this->assertNotNull( get_post( $test ) );
		$this->assertSame( '1', get_post_meta( $compound, 'is_featured', true ) );
	}

	public function test_stage_validation_matches_batch_identity_matrix() {
		foreach ( array( 'vendor-vetting', 'waiting-on-vendor' ) as $stage ) {
			$this->post_stage( $stage );
			foreach ( array( 'batch_number', 'vial_cap_color', 'vial_crimp_color', 'batch_vial_photo', 'batch_identity_photos' ) as $field ) {
				$this->assertTrue( $this->validate( 'batch_identity_photos' === $field ? array() : '', $field ), $stage . ': ' . $field );
			}
		}
		$this->post_stage( 'submitted-to-lab' );
		$this->assertNotTrue( $this->validate( '', 'vial_cap_color' ) );
		$this->assertNotTrue( $this->validate( '', 'vial_crimp_color' ) );
		$this->assertTrue( $this->validate( '', 'batch_vial_photo' ) );
		foreach ( array( 'in-testing', 'complete' ) as $stage ) {
			$this->post_stage( $stage );
			foreach ( array( 'batch_number', 'vial_cap_color', 'vial_crimp_color', 'batch_vial_photo' ) as $field ) { $this->assertNotTrue( $this->validate( '', $field ), $stage . ': ' . $field ); }
			$this->assertTrue( $this->validate( array(), 'batch_identity_photos' ), $stage );
		}
		$this->post_stage( 'in-testing' );
		$this->assertTrue( $this->validate( '', 'coa_pdf_id' ) );
		$this->assertTrue( $this->validate( '', 'purity_percentage' ) );
	}

	public function test_title_rules_cover_early_verification_complete_approved_and_failed() {
		$compound = $this->compound( 'PT-141 10 mg' );
		$test = $this->test_record( $compound, 'vendor-vetting', '', 'pending', 'draft' );
		$this->service->after_save( $test );
		$this->assertSame( 'PT-141 10 mg', get_post( $test )->post_title );
		update_post_meta( $test, 'workflow_stage', 'waiting-on-vendor' ); update_post_meta( $test, 'batch_number', 'EARLY-BATCH' ); $this->service->after_save( $test );
		$this->assertSame( 'PT-141 10 mg', get_post( $test )->post_title );
		update_post_meta( $test, 'workflow_stage', 'submitted-to-lab' ); $this->service->after_save( $test );
		$this->assertSame( 'PT-141 10 mg', get_post( $test )->post_title );
		update_post_meta( $test, 'workflow_stage', 'in-testing' ); update_post_meta( $test, 'batch_number', 'PSPT14162926JP' ); $this->service->after_save( $test );
		$this->assertSame( 'PT-141 10 mg — Batch PSPT14162926JP', get_post( $test )->post_title );
		foreach ( array( 'pending', 'approved', 'failed' ) as $status ) {
			update_post_meta( $test, 'workflow_stage', 'complete' ); update_post_meta( $test, 'coa_status', $status ); $this->service->after_save( $test );
			$this->assertSame( 'PT-141 10 mg — Batch PSPT14162926JP', get_post( $test )->post_title, $status );
		}
	}

	public function test_batch_change_updates_title_without_changing_published_slug_or_url() {
		$compound = $this->compound( 'GHK-CU 50 mg' );
		$test = $this->test_record( $compound, 'in-testing', 'GHK5062926JP', 'pending', 'publish', 'stable-public-report' );
		$before_url = get_permalink( $test );
		$this->service->after_save( $test );
		$this->assertSame( 'GHK-CU 50 mg — Batch GHK5062926JP', get_post( $test )->post_title );
		update_post_meta( $test, 'batch_number', 'GHK5062926JP-R2' ); $this->service->after_save( $test );
		$this->assertSame( 'GHK-CU 50 mg — Batch GHK5062926JP-R2', get_post( $test )->post_title );
		$this->assertSame( 'stable-public-report', get_post( $test )->post_name );
		$this->assertSame( $before_url, get_permalink( $test ) );
	}

	public function test_compound_display_name_change_updates_only_linked_test_titles() {
		$compound = $this->compound( 'Old Scientific Name' );
		$other = $this->compound( 'Unrelated Name' );
		$testing = $this->test_record( $compound, 'in-testing', 'BATCH-1', 'pending', 'draft' );
		$early = $this->test_record( $compound, 'waiting-on-vendor', '', 'pending', 'draft' );
		$unrelated = $this->test_record( $other, 'in-testing', 'OTHER-1', 'pending', 'draft' );
		$this->service->after_save( $testing ); $this->service->after_save( $early ); $this->service->after_save( $unrelated );
		update_post_meta( $testing, 'sentinel_scientific_data', 'preserved' );
		update_post_meta( $compound, 'display_name', 'New Scientific Name' );
		$this->service->after_compound_save( $compound );
		$this->assertSame( 'New Scientific Name — Batch BATCH-1', get_post( $testing )->post_title );
		$this->assertSame( 'New Scientific Name', get_post( $early )->post_title );
		$this->assertSame( 'Unrelated Name — Batch OTHER-1', get_post( $unrelated )->post_title );
		$this->assertSame( 'preserved', get_post_meta( $testing, 'sentinel_scientific_data', true ) );
	}

	public function test_title_sync_hook_and_csv_normal_save_contract_are_declared() {
		$plugin = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin.php' );
		$service = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-service.php' );
		$importer = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-importer.php' );
		$this->assertStringContainsString( "add_action( 'acf/save_post', array( \$this->coa_test_service, 'after_save' ), 30 )", $plugin );
		$this->assertStringContainsString( "add_action( 'acf/save_post', array( \$this->coa_test_service, 'after_compound_save' ), 35 )", $plugin );
		$this->assertStringContainsString( "if ( \$title === \$post->post_title ) { return; }", $service );
		$this->assertStringContainsString( "\$update['post_name'] = \$post->post_name", $service );
		$this->assertStringContainsString( 'Nothing is saved or published until you use the normal WordPress controls.', $importer );
		$this->assertStringContainsString( "'compound_id', 'batch_number', 'internal_batch_id', 'workflow_stage'", $importer );
		$this->assertStringContainsString( "\$map = array( 'coa_status' => 'field_ps_coa_test_status' )", $importer );
	}

	private function post_stage( $stage ) { $_POST['acf'] = array( 'field_ps_coa_test_status' => 'pending', 'field_ps_coa_test_workflow_stage' => $stage ); }
	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
	private function compound( $name ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $name ) ); update_post_meta( $id, 'display_name', $name ); update_post_meta( $id, 'is_active', 1 ); return $id; }
	private function test_record( $compound, $stage, $batch, $status, $post_status, $slug = '' ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => $post_status, 'post_title' => '', 'post_name' => $slug ) );
		foreach ( array( 'compound_id' => $compound, 'workflow_stage' => $stage, 'batch_number' => $batch, 'coa_status' => $status ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

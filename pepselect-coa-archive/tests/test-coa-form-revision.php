<?php
/** Focused COA-4D_Form_Rev editor, validation, and public-privacy tests. */
class PepSelect_COA_Archive_COA_Form_Revision_Test extends WP_UnitTestCase {
	private $validator;
	private $view_model;

	public function set_up() { parent::set_up(); do_action( 'init' ); $this->validator = new PepSelect\COAArchive\COA_Test_Validation(); $this->view_model = new PepSelect\COAArchive\Frontend_View_Model(); }
	public function tear_down() { unset( $_POST['acf'], $_POST['post_ID'], $_GET['post'] ); parent::tear_down(); }

	public function test_post_type_supports_native_featured_image_without_editor() {
		$this->assertTrue( post_type_supports( 'ps_coa_test', 'thumbnail' ) );
		$this->assertFalse( post_type_supports( 'ps_coa_test', 'editor' ) );
		if ( ! function_exists( 'post_thumbnail_meta_box' ) ) { $this->markTestSkipped( 'WordPress admin metabox functions are unavailable.' ); }
		global $wp_meta_boxes; $wp_meta_boxes = array();
		$post = get_post( self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) ) );
		( new PepSelect\COAArchive\COA_Test_Form() )->register_featured_image_metabox( $post );
		$this->assertArrayHasKey( 'postimagediv', $wp_meta_boxes['ps_coa_test']['side']['low'] );
		$this->assertSame( 'post_thumbnail_meta_box', $wp_meta_boxes['ps_coa_test']['side']['low']['postimagediv']['callback'] );
	}

	public function test_normal_status_and_workflow_choices_are_strict() {
		$this->assertSame( array( 'pending' => 'Pending', 'approved' => 'Approved', 'failed' => 'Failed' ), PepSelect\COAArchive\COA_Test_Fields::statuses() );
		$this->assertSame( array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab', 'in-testing', 'complete' ), array_keys( PepSelect\COAArchive\COA_Workflow::stages() ) );
		$this->assertSame( 'submitted-to-lab', PepSelect\COAArchive\COA_Workflow::normalize_stage( 'sample-received' ) );
		$this->assertSame( 'in-testing', PepSelect\COAArchive\COA_Workflow::normalize_stage( 'coa-pending' ) );
	}

	public function test_archived_and_superseded_values_are_preserved_read_only() {
		$form = new PepSelect\COAArchive\COA_Test_Form();
		foreach ( array( 'archived', 'superseded' ) as $legacy ) {
			$post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) ); update_post_meta( $post_id, 'coa_status', $legacy ); $_GET['post'] = $post_id;
			$field = $form->preserve_legacy_status( array( 'choices' => PepSelect\COAArchive\COA_Test_Fields::statuses() ) );
			$this->assertArrayHasKey( $legacy, $field['choices'] ); $this->assertSame( 1, $field['disabled'] ); $this->assertSame( $legacy, get_post_meta( $post_id, 'coa_status', true ) );
		}
	}

	public function test_stage_validation_requires_only_available_operational_data() {
		$_POST['acf'] = array( 'field_ps_coa_test_status' => 'pending', 'field_ps_coa_test_workflow_stage' => 'vendor-vetting' );
		$this->assertTrue( $this->validate( '', 'batch_number' ) ); $this->assertTrue( $this->validate( '', 'testing_lab' ) ); $this->assertTrue( $this->validate( 'impossible', 'purity_status' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'waiting-on-vendor';
		$this->assertNotTrue( $this->validate( '', 'vial_crimp_color' ) ); $this->assertNotTrue( $this->validate( '', 'vial_cap_color' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'submitted-to-lab';
		$this->assertNotTrue( $this->validate( '', 'expected_coa_date' ) ); $this->assertTrue( $this->validate( 'impossible', 'purity_status' ) ); $this->assertTrue( $this->validate( 999999, 'coa_pdf_id' ) );
		$_POST['acf']['field_ps_coa_test_workflow_stage'] = 'in-testing';
		$this->assertNotTrue( $this->validate( '', 'batch_number' ) ); $this->assertNotTrue( $this->validate( '', 'testing_lab' ) ); $this->assertTrue( $this->validate( 999999, 'coa_pdf_id' ) );
	}

	public function test_partial_results_gate_controls_result_editability_without_deleting_values() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test' ) ); update_post_meta( $post_id, 'purity_percentage', '98.75' );
		$this->assertFalse( PepSelect\COAArchive\COA_Test_Form::field_available( 'purity_percentage', 'in-testing', false ) );
		$this->assertTrue( PepSelect\COAArchive\COA_Test_Form::field_available( 'purity_percentage', 'in-testing', true ) );
		$this->assertSame( '98.75', get_post_meta( $post_id, 'purity_percentage', true ) );
	}

	public function test_public_view_model_masks_fields_until_their_stage_allows_them() {
		$compound = $this->compound();
		foreach ( array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab' ) as $stage ) {
			$test = $this->incoming( $compound, $stage ); $model = $this->view_model->report( get_post( $test ), get_post( $compound ) );
			$this->assertSame( '', $model['batch_number'], $stage ); $this->assertSame( '', $model['laboratory'], $stage ); $this->assertSame( '', $model['pending_lab_url'], $stage ); $this->assertSame( '', $model['purity_percentage'], $stage ); $this->assertSame( '', $model['content_unit'], $stage ); $this->assertSame( '', $model['endotoxin_unit'], $stage ); $this->assertFalse( $model['is_current'], $stage ); $this->assertEmpty( $model['page_images'], $stage );
		}
		$testing = $this->incoming( $compound, 'in-testing' ); $model = $this->view_model->report( get_post( $testing ), get_post( $compound ) );
		$this->assertSame( 'PRIVATE-BATCH', $model['batch_number'] ); $this->assertSame( 'ILS Labs', $model['laboratory'] ); $this->assertSame( 'https://lab.example/pending', $model['pending_lab_url'] ); $this->assertSame( '', $model['purity_percentage'] );
		update_post_meta( $testing, 'partial_results_available', 1 ); update_post_meta( $testing, 'purity_percentage', '98.75' );
		$model = $this->view_model->report( get_post( $testing ), get_post( $compound ) ); $this->assertTrue( $model['has_partial_results'] ); $this->assertSame( '98.75', $model['purity_percentage'] );
	}

	public function test_featured_image_fallback_order_and_admin_asset_scope_are_declared() {
		$view = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' );
		$this->assertLessThan( strpos( $view, "get_post_meta( \$compound->ID, 'compound_image_id'" ), strpos( $view, 'get_post_thumbnail_id( $test->ID )' ) );
		$form = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-form.php' );
		$this->assertStringContainsString( "array( 'post.php', 'post-new.php' )", $form ); $this->assertStringContainsString( "Post_Types::COA_TEST !== \$screen->post_type", $form );
		$this->assertStringContainsString( 'assets/js/pepselect-coa-test-form.js', $form ); $this->assertStringContainsString( 'assets/css/pepselect-coa-test-form.css', $form );
		$this->assertFileExists( dirname( __DIR__ ) . '/assets/js/pepselect-coa-test-form.js' ); $this->assertFileExists( dirname( __DIR__ ) . '/assets/css/pepselect-coa-test-form.css' );
	}

	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
	private function compound() { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Compound' ) ); update_post_meta( $id, 'is_active', 1 ); return $id; }
	private function incoming( $compound, $stage ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Private Batch Title' ) );
		foreach ( array( 'compound_id' => $compound, 'coa_status' => 'pending', 'workflow_stage' => $stage, 'batch_number' => 'PRIVATE-BATCH', 'testing_lab' => 'ils-labs', 'expected_coa_date' => '20260730', 'pending_lab_url' => 'https://lab.example/pending', 'purity_percentage' => '99.1', 'content_unit' => 'mg', 'endotoxin_unit' => 'EU/mL', 'is_current' => 1 ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

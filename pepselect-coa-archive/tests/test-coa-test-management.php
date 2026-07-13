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
		foreach ( array( 'compound_id', 'batch_number', 'test_date', 'testing_lab', 'status', 'is_current', 'coa_pdf_id', 'page_images', 'internal_notes' ) as $suffix ) { $this->assertStringContainsString( 'field_ps_coa_test_' . $suffix, $source ); }
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
		$this->assertNotTrue( $this->validate( '', 'batch_number' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'testing_lab' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'coa_status' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'purity_status' ) );
	}

	public function test_cross_field_validation() {
		$_POST['acf'] = array( 'field_ps_coa_test_testing_lab' => 'other' );
		$this->assertNotTrue( $this->validate( '', 'other_testing_lab' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_maximum_net_content' => '8' );
		$this->assertNotTrue( $this->validate( '9', 'minimum_net_content' ) );
		unset( $_POST['acf'] );
	}

	public function test_approved_requires_valid_documents_and_no_failed_results() {
		$pdf = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'application/pdf' ) );
		$image = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_mime_type' => 'image/jpeg' ) );
		$_POST['acf'] = array( 'field_ps_coa_test_coa_pdf_id' => $pdf, 'field_ps_coa_test_page_images' => array( $image ), 'field_ps_coa_test_purity_status' => 'pass' );
		$this->assertTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		$_POST['acf']['field_ps_coa_test_purity_status'] = 'fail';
		$this->assertNotTrue( $this->validator->validate_approval( true, 'approved', array(), '' ) );
		unset( $_POST['acf'] );
	}

	public function test_numeric_and_date_validation() {
		$this->assertNotTrue( $this->validate( '2026-02-30', 'test_date' ) );
		$this->assertTrue( $this->validate( '2026-07-08', 'test_date' ) );
		$this->assertNotTrue( $this->validate( 0, 'vials_tested' ) );
		$this->assertNotTrue( $this->validate( 1.5, 'vials_tested' ) );
		$this->assertTrue( $this->validate( 3, 'vials_tested' ) );
		$this->assertNotTrue( $this->validate( 100.01, 'purity_percentage' ) );
		$this->assertTrue( $this->validate( 99.79, 'purity_percentage' ) );
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
		$this->assertArrayNotHasKey( 'internal_notes', $keys ); $this->assertArrayNotHasKey( 'internal_batch_id', $keys ); $this->assertArrayHasKey( 'batch_number', $keys );
	}

	public function test_admin_columns_are_coa_test_specific() {
		$admin = new PepSelect\COAArchive\COA_Test_Admin(); $columns = $admin->columns( array( 'cb' => 'cb', 'title' => 'Title', 'date' => 'Date' ) );
		$this->assertArrayHasKey( 'compound_id', $columns ); $this->assertArrayHasKey( 'coa_pdf_id', $columns ); $this->assertFalse( has_filter( 'manage_ps_compound_posts_columns', array( $admin, 'columns' ) ) );
	}

	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
	private function test_post( $compound, $batch, $current ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_title' => '' ) ); update_post_meta( $id, 'compound_id', $compound ); update_post_meta( $id, 'batch_number', $batch ); update_post_meta( $id, 'is_current', $current ? 1 : 0 ); return $id; }
}

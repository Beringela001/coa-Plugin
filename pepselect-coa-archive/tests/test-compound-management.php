<?php
/** COA-2 compound-management tests and integration scaffolding. */
class PepSelect_COA_Archive_Compound_Management_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Compound_Validation */
	private $validator;

	public function set_up() { parent::set_up(); $this->validator = new PepSelect\COAArchive\Compound_Validation(); }

	public function test_stable_field_keys_are_declared() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-compound-fields.php' );
		foreach ( array( 'field_ps_compound_display_name', 'field_ps_compound_name', 'field_ps_compound_strength_value', 'field_ps_compound_strength_unit', 'field_ps_compound_internal_notes' ) as $key ) { $this->assertStringContainsString( $key, $source ); }
	}

	public function test_required_and_enum_validation() {
		$this->assertNotTrue( $this->validate( '', 'display_name' ) );
		$this->assertNotTrue( $this->validate( '', 'compound_name' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'strength_unit' ) );
		$this->assertNotTrue( $this->validate( 'invalid', 'compound_category' ) );
	}

	public function test_numeric_validation() {
		$this->assertNotTrue( $this->validate( 0, 'strength_value' ) );
		$this->assertNotTrue( $this->validate( -2, 'strength_value' ) );
		$this->assertNotTrue( $this->validate( -1, 'display_order' ) );
		$this->assertTrue( $this->validate( 5.5, 'strength_value' ) );
	}

	public function test_title_is_initialized_but_not_overwritten() {
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		$user = self::factory()->user->create( array( 'role' => 'administrator' ) ); wp_set_current_user( $user );
		$id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_title' => '' ) );
		update_post_meta( $id, 'display_name', 'Retatrutide 30mg' ); $this->validator->populate_empty_title( $id );
		$this->assertSame( 'Retatrutide 30mg', get_post( $id )->post_title );
		update_post_meta( $id, 'display_name', 'Changed' ); $this->validator->populate_empty_title( $id );
		$this->assertSame( 'Retatrutide 30mg', get_post( $id )->post_title );
	}

	public function test_internal_notes_are_not_registered_for_rest() {
		do_action( 'init' ); $keys = get_registered_meta_keys( 'post', 'ps_compound' );
		$this->assertArrayNotHasKey( 'internal_notes', $keys );
		$this->assertArrayHasKey( 'display_name', $keys );
	}

	public function test_optional_dependencies_are_safe() {
		$dependencies = new PepSelect\COAArchive\Dependencies();
		$this->assertIsBool( $dependencies->has_acf() );
		$this->assertIsBool( $dependencies->has_woocommerce() );
	}

	public function test_woocommerce_detection_does_not_require_product_post_type() {
		if ( ! defined( 'WC_VERSION' ) ) { define( 'WC_VERSION', 'test' ); }
		$dependencies = new PepSelect\COAArchive\Dependencies();
		$this->assertTrue( $dependencies->has_woocommerce() );
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-dependencies.php' );
		$this->assertStringNotContainsString( "post_type_exists( 'product' )", $source );
	}

	public function test_product_field_definition_and_position_are_stable() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-compound-fields.php' );
		$this->assertStringContainsString( "array_splice( \$fields, 6, 0", $source );
		$this->assertStringContainsString( "'field_ps_compound_woocommerce_product_id'", $source );
		$this->assertStringContainsString( "'woocommerce_product_id'", $source );
		$this->assertStringContainsString( "'post_object'", $source );
		$this->assertStringContainsString( "'post_type' => array( 'product' )", $source );
		$this->assertStringContainsString( "'return_format' => 'id'", $source );
		$this->assertStringContainsString( "'allow_null' => 1", $source );
		$this->assertStringContainsString( "'multiple' => 0", $source );
	}

	public function test_product_field_is_included_when_woocommerce_is_available() {
		if ( ! function_exists( 'acf_get_local_field_group' ) ) { $this->markTestSkipped( 'ACF test utilities are unavailable.' ); }
		if ( ! defined( 'WC_VERSION' ) ) { define( 'WC_VERSION', 'test' ); }
		$fields = new PepSelect\COAArchive\Compound_Fields( new PepSelect\COAArchive\Dependencies() );
		$fields->register();
		$group = acf_get_local_field_group( 'group_ps_compound_details' );
		$keys = wp_list_pluck( acf_get_fields( $group ), 'key' );
		$this->assertContains( 'field_ps_compound_woocommerce_product_id', $keys );
	}

	public function test_dependency_check_does_not_delete_saved_product_id() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_compound' ) );
		update_post_meta( $post_id, 'woocommerce_product_id', 123 );
		( new PepSelect\COAArchive\Dependencies() )->has_woocommerce();
		$this->assertSame( '123', get_post_meta( $post_id, 'woocommerce_product_id', true ) );
	}

	public function test_acf_group_registers_when_api_is_available() {
		if ( ! function_exists( 'acf_get_local_field_group' ) ) { $this->markTestSkipped( 'ACF test utilities are unavailable.' ); }
		do_action( 'acf/init' );
		$this->assertNotFalse( acf_get_local_field_group( 'group_ps_compound_details' ) );
	}

	public function test_field_group_registration_uses_acf_init() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin.php' );
		$this->assertStringContainsString( "add_action( 'acf/init', array( \$this->compound_fields, 'register' ) )", $source );
		$this->assertStringNotContainsString( "add_action( 'init', array( \$this->compound_fields, 'register' )", $source );
	}

	public function test_field_group_location_and_key_are_stable() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-compound-fields.php' );
		$this->assertStringContainsString( "'key' => 'group_ps_compound_details'", $source );
		$this->assertStringContainsString( "'param' => 'post_type'", $source );
		$this->assertStringContainsString( "'value' => Post_Types::COMPOUND", $source );
	}

	public function test_local_field_group_is_not_registered_twice() {
		if ( ! function_exists( 'acf_get_local_field_groups' ) ) { $this->markTestSkipped( 'ACF test utilities are unavailable.' ); }
		do_action( 'acf/init' );
		do_action( 'acf/init' );
		$matches = array_filter( acf_get_local_field_groups(), static function ( $group ) { return isset( $group['key'] ) && 'group_ps_compound_details' === $group['key']; } );
		$this->assertCount( 1, $matches );
	}

	public function test_duplicate_detection_blocks_other_post_but_excludes_current_post() {
		$existing = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_title' => 'Retatrutide 30mg' ) );
		update_post_meta( $existing, 'compound_name', 'Retatrutide' ); update_post_meta( $existing, 'strength_value', 30 ); update_post_meta( $existing, 'strength_unit', 'mg' );
		$_POST['acf'] = array( 'field_ps_compound_name' => ' retatrutide ', 'field_ps_compound_strength_value' => '30' );
		$_POST['post_ID'] = 0;
		$result = $this->validator->validate_duplicate( true, 'mg', array(), '' );
		$this->assertNotTrue( $result );
		$_POST['post_ID'] = $existing;
		$this->assertTrue( $this->validator->validate_duplicate( true, 'mg', array(), '' ) );
		unset( $_POST['acf'], $_POST['post_ID'] );
	}

	public function test_admin_columns_are_compound_specific() {
		$admin = new PepSelect\COAArchive\Compound_Admin();
		$columns = $admin->columns( array( 'cb' => 'cb', 'title' => 'Title', 'date' => 'Date' ) );
		$this->assertArrayHasKey( 'strength', $columns );
		$this->assertFalse( has_filter( 'manage_ps_coa_test_posts_columns', array( $admin, 'columns' ) ) );
	}

	private function validate( $value, $name ) { return $this->validator->validate( true, $value, array( 'name' => $name ), '' ); }
}

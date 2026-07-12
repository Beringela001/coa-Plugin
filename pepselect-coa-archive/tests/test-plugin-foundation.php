<?php
/** Foundation integration tests. */
class PepSelect_COA_Archive_Foundation_Test extends WP_UnitTestCase {
	public function test_plugin_bootstraps() { $this->assertSame( '0.2.0', pepselect_coa_archive_version() ); }
	public function test_post_types_and_rest_are_registered() {
		do_action( 'init' );
		foreach ( array( 'ps_compound', 'ps_coa_test' ) as $name ) { $object = get_post_type_object( $name ); $this->assertNotNull( $object ); $this->assertTrue( $object->show_in_rest ); }
	}
	public function test_activation_grants_capabilities() {
		PepSelect\COAArchive\Activator::activate();
		$role = get_role( 'administrator' );
		foreach ( PepSelect\COAArchive\Capabilities::all() as $capability ) { $this->assertTrue( $role->has_cap( $capability ) ); }
	}
	public function test_nested_rewrite_is_registered() {
		do_action( 'init' );
		global $wp_rewrite; $rules = $wp_rewrite->rewrite_rules();
		$this->assertArrayHasKey( 'testing/([^/]+)/([^/]+)/?$', $rules );
	}
	public function test_runtime_does_not_flush_rewrites() {
		$this->assertFalse( has_action( 'init', 'flush_rewrite_rules' ) );
	}
	public function test_deactivation_preserves_content() {
		$post_id = self::factory()->post->create( array( 'post_type' => 'ps_compound' ) );
		PepSelect\COAArchive\Deactivator::deactivate();
		$this->assertNotNull( get_post( $post_id ) );
	}
	public function test_uninstall_is_documented_as_non_destructive() {
		$source = file_get_contents( dirname( __DIR__ ) . '/uninstall.php' );
		$this->assertStringNotContainsString( 'wp_delete_post(', $source );
		$this->assertStringNotContainsString( 'delete_option(', $source );
	}
}

<?php
/** Foundation integration tests. */
class PepSelect_COA_Archive_Foundation_Test extends WP_UnitTestCase {
	public function test_plugin_bootstraps() { $this->assertSame( '0.4.0', pepselect_coa_archive_version() ); }
	public function test_post_types_and_rest_are_registered() {
		do_action( 'init' );
		foreach ( array( 'ps_compound', 'ps_coa_test' ) as $name ) { $object = get_post_type_object( $name ); $this->assertNotNull( $object ); $this->assertTrue( $object->show_in_rest ); }
	}
	public function test_activation_grants_capabilities() {
		PepSelect\COAArchive\Activator::activate();
		$role = get_role( 'administrator' );
		foreach ( PepSelect\COAArchive\Capabilities::all() as $capability ) { $this->assertTrue( $role->has_cap( $capability ) ); }
	}
	public function test_compound_supports_structured_editor_features_without_content_editor() {
		do_action( 'init' );
		$this->assertTrue( post_type_supports( 'ps_compound', 'title' ) );
		$this->assertFalse( post_type_supports( 'ps_compound', 'editor' ) );
		$this->assertTrue( post_type_supports( 'ps_compound', 'thumbnail' ) );
		$this->assertTrue( post_type_supports( 'ps_compound', 'custom-fields' ) );
		$this->assertTrue( post_type_supports( 'ps_compound', 'revisions' ) );
	}
	public function test_coa_test_supports_remain_unchanged() {
		do_action( 'init' );
		$this->assertFalse( post_type_supports( 'ps_coa_test', 'editor' ) );
		foreach ( array( 'title', 'thumbnail', 'custom-fields', 'revisions' ) as $feature ) {
			$this->assertTrue( post_type_supports( 'ps_coa_test', $feature ) );
		}
	}
	public function test_administrator_can_manage_both_post_types() {
		PepSelect\COAArchive\Activator::activate();
		do_action( 'init' );
		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		foreach ( array( 'ps_compound', 'ps_coa_test' ) as $post_type ) {
			$object = get_post_type_object( $post_type );
			$this->assertSame( array( 'ps_coa', 'ps_coas' ), $object->capability_type );
			$this->assertTrue( $object->map_meta_cap );
			$this->assertSame( 'edit_ps_coa', $object->cap->edit_post );
			$this->assertSame( 'edit_ps_coas', $object->cap->edit_posts );
			$this->assertSame( 'create_ps_coas', $object->cap->create_posts );
			$this->assertTrue( current_user_can( $object->cap->edit_posts ) );
			$this->assertTrue( current_user_can( $object->cap->create_posts ) );
			$post_id = self::factory()->post->create( array( 'post_type' => $post_type, 'post_author' => $user_id ) );
			$this->assertTrue( current_user_can( 'edit_post', $post_id ) );
			$this->assertTrue( current_user_can( 'delete_post', $post_id ) );
		}
	}
	public function test_capabilities_persist_across_reactivation() {
		PepSelect\COAArchive\Activator::activate();
		$before = get_role( 'administrator' )->capabilities;
		PepSelect\COAArchive\Activator::activate();
		$role = get_role( 'administrator' );
		$this->assertSame( $before, $role->capabilities );
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

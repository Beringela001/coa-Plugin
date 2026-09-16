<?php
use PepSelect\COAArchive\Design_Settings;
use PepSelect\COAArchive\Design_Editor;
use PepSelect\COAArchive\Design_Editor_Endpoint;

class PepSelect_COA_Design_Editor_Test extends WP_UnitTestCase {
	public function set_up() {
		parent::set_up(); do_action( 'init' );
		delete_option( Design_Settings::OPTION ); Design_Settings::clear_cache();
		global $wp_rest_server; $wp_rest_server = new WP_REST_Server(); do_action( 'rest_api_init' );
		( new Design_Editor_Endpoint() )->routes();
	}
	public function tear_down() { wp_set_current_user( 0 ); Design_Settings::clear_cache(); parent::tear_down(); }
	private function authorize() {
		$id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		get_user_by( 'id', $id )->add_cap( 'manage_ps_coas' ); wp_set_current_user( $id );
	}
	private function request( $method, $path, $data = array() ) {
		$request = new WP_REST_Request( $method, '/pepselect-coa/v1/design' . $path );
		$request->set_body_params( $data ); return rest_do_request( $request );
	}
	private function report( $stage = 'waiting-on-vendor', $status = 'pending' ) {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Preview compound' ) );
		update_post_meta( $compound, 'is_active', 1 );
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Preview batch' ) );
		foreach ( array( 'compound_id' => $compound, 'workflow_stage' => $stage, 'coa_status' => $status, 'batch_number' => 'DESIGN-1' ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
	public function test_routes_require_permission_for_all_operations() {
		foreach ( array( array( 'GET', '' ), array( 'PATCH', '' ), array( 'POST', '/preview' ) ) as $route ) {
			$this->assertContains( $this->request( $route[0], $route[1] )->get_status(), array( 401, 403 ) );
		}
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->assertSame( 403, $this->request( 'GET', '' )->get_status() );
	}
	public function test_read_and_preview_do_not_save_settings_or_business_records() {
		$this->authorize(); $id = $this->report();
		$before = get_post_meta( $id ); $settings = get_option( Design_Settings::OPTION );
		$read = $this->request( 'GET', '' ); $this->assertSame( 200, $read->get_status() );
		$response = $this->request( 'POST', '/preview', array( 'settings' => array( 'waiting_vendor_label' => 'More arriving', 'waiting_vendor_copy' => 'Our new batch is in transit.' ), 'view' => 'report', 'id' => $id ) );
		$this->assertSame( 200, $response->get_status() );
		$html = $response->get_data()['html'];
		$this->assertStringContainsString( '<h2>More arriving</h2>', $html );
		$this->assertStringContainsString( 'Our new batch is in transit.', $html );
		$this->assertStringNotContainsString( 'ps-coa-state-pill', explode( '</header>', explode( '<header class="ps-coa-report-hero">', $html )[1] )[0] );
		$this->assertSame( $before, get_post_meta( $id ) );
		$this->assertSame( $settings, get_option( Design_Settings::OPTION ) );
		$this->assertSame( 'Waiting on Vendor', Design_Settings::copy( 'waiting_vendor_label' ) );
		$this->assertFalse( $response->get_data()['saved'] );
	}
	public function test_save_merges_preserves_saved_copy_and_rejects_stale_revision() {
		$this->authorize();
		update_option( Design_Settings::OPTION, array( 'waiting_vendor_label' => 'We have more arriving.', 'report_hero_copy' => 'Saved legacy wording', 'accent' => '#123456' ) ); Design_Settings::clear_cache();
		$revision = Design_Editor::revision();
		$response = $this->request( 'PATCH', '', array( 'settings' => array( 'report_passed_heading' => '<b>Testing complete</b>' ), 'revision' => $revision ) );
		$this->assertSame( 200, $response->get_status() );
		$this->assertSame( 'Testing complete', Design_Settings::copy( 'report_passed_heading' ) );
		$this->assertSame( 'We have more arriving.', Design_Settings::copy( 'waiting_vendor_label' ) );
		$this->assertSame( 'Saved legacy wording', Design_Settings::copy( 'report_hero_copy' ) );
		$this->assertSame( '#123456', Design_Settings::get()['accent'] );
		$this->assertSame( 409, $this->request( 'PATCH', '', array( 'settings' => array( 'accent' => '#ffffff' ), 'revision' => $revision ) )->get_status() );
	}
	public function test_preview_restores_cache_when_render_throws() {
		try { Design_Settings::with_preview( array( 'waiting_vendor_label' => 'Draft' ), function () { throw new RuntimeException( 'render failed' ); } ); } catch ( RuntimeException $error ) {}
		$this->assertSame( 'Waiting on Vendor', Design_Settings::copy( 'waiting_vendor_label' ) );
	}
	public function test_invalid_and_inactive_fields_are_rejected() {
		foreach ( array( array( 'coa_status' => 'approved' ), array( 'accent' => array() ), array( 'show_failed_only_compounds' => 0 ) ) as $patch ) { $this->assertWPError( Design_Editor::validate_patch( $patch ) ); }
		$this->assertWPError( Design_Editor::preview( array(), '../../anything', 0 ) );
		$this->assertWPError( Design_Editor::preview( array(), 'report', 99999999 ) );
	}
	public function test_preview_matches_production_hero_and_preserves_notes() {
		$id = $this->report( 'complete', 'failed' ); update_post_meta( $id, 'public_notes', 'Important customer disclosure.' );
		$preview = Design_Editor::preview( array(), 'report', $id );
		$this->assertStringContainsString( 'Important customer disclosure.', $preview['html'] );
		$this->assertStringContainsString( 'See important batch note below', $preview['html'] );
		$vm = new PepSelect\COAArchive\Frontend_View_Model();
		$post = get_post( $id ); $compound_post = get_post( get_post_meta( $id, 'compound_id', true ) );
		$test = $vm->report( $post, $compound_post ); $compound = $vm->compound( $compound_post );
		ob_start(); include pepselect_coa_template_path( 'partials/report-hero.php' ); $hero = ob_get_clean();
		$this->assertStringContainsString( $hero, $preview['html'] );
	}
	public function test_report_copy_does_not_change_product_carousel_workflow_text() {
		$id = $this->report( 'in-testing' );
		$vm = new PepSelect\COAArchive\Frontend_View_Model(); $post = get_post( $id ); $compound = get_post( get_post_meta( $id, 'compound_id', true ) );
		$before = $vm->product_carousel_incoming( $post, $compound );
		$after = Design_Settings::with_preview( array( 'in_testing_label' => 'Custom report title', 'in_testing_copy' => 'Custom report description' ), function () use ( $vm, $post, $compound ) { return $vm->product_carousel_incoming( $post, $compound ); } );
		$this->assertSame( $before, $after );
		$this->assertSame( 'in-testing', get_post_meta( $id, 'workflow_stage', true ) );
	}
}

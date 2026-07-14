<?php
/** COA-4C scientific display and design-settings tests. */
class PepSelect_COA_Archive_Design_Settings_Test extends WP_UnitTestCase {
	private $view_model;

	public function set_up() {
		parent::set_up(); do_action( 'init' );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
		delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache();
	}

	public function tear_down() { delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache(); parent::tear_down(); }

	public function test_public_number_formatter_removes_float_artifacts() {
		$this->assertSame( '30', pepselect_coa_format_number( '29.999999' ) );
		$this->assertSame( '30', pepselect_coa_format_number( '30.000000' ) );
		$this->assertSame( '31.01', pepselect_coa_format_number( '31.010000' ) );
		$this->assertSame( '10.69', pepselect_coa_format_number( '10.690000' ) );
		$this->assertSame( '5.5', pepselect_coa_format_number( '5.500000' ) );
		$this->assertSame( '99.7899', pepselect_coa_format_number( '99.789900', 'purity' ) );
		$this->assertSame( '99.79', pepselect_coa_format_number( '99.790000', 'purity' ) );
		$this->assertSame( '99', pepselect_coa_format_number( '99.000000', 'purity' ) );
		$this->assertSame( '4', pepselect_coa_format_number( '3.9', 'integer' ) );
		$this->assertSame( '0', pepselect_coa_format_number( '0' ) );
	}

	public function test_full_qc_documented_requires_every_required_status() {
		$test = $this->test_record();
		foreach ( array( 'purity_status', 'identity_status', 'heavy_metals_status', 'sterility_status' ) as $key ) { update_post_meta( $test, $key, 'pass' ); }
		update_post_meta( $test, 'endotoxin_status', 'reported' ); update_post_meta( $test, 'endotoxin_result', '<0.05' );
		$this->assertTrue( $this->view_model->is_full_qc_documented( $test ) );
		update_post_meta( $test, 'identity_status', '' ); $this->assertFalse( $this->view_model->is_full_qc_documented( $test ) );
		update_post_meta( $test, 'identity_status', 'pass' ); update_post_meta( $test, 'endotoxin_result', '' ); $this->assertFalse( $this->view_model->is_full_qc_documented( $test ) );
		update_post_meta( $test, 'endotoxin_status', 'pass' ); $this->assertTrue( $this->view_model->is_full_qc_documented( $test ) );
	}

	public function test_approved_reported_endotoxin_uses_success_icon_but_keeps_reported_label() {
		$test = $this->test_record(); update_post_meta( $test, 'endotoxin_status', 'reported' ); update_post_meta( $test, 'endotoxin_result', '<0.05' );
		$model = $this->view_model->test_summary( get_post( $test ) );
		$this->assertSame( 'Reported', $model['endotoxin_status']['label'] );
		$this->assertSame( 'pass', $model['endotoxin_status']['icon'] );
		$this->assertTrue( $model['endotoxin_status']['success'] );
		wp_update_post( array( 'ID' => $test, 'post_status' => 'pending' ) );
		$model = $this->view_model->test_summary( get_post( $test ) );
		$this->assertSame( 'reported', $model['endotoxin_status']['icon'] );
		$this->assertFalse( $model['endotoxin_status']['success'] );
		wp_update_post( array( 'ID' => $test, 'post_status' => 'publish' ) ); update_post_meta( $test, 'coa_status', 'failed' );
		$model = $this->view_model->test_summary( get_post( $test ) ); $this->assertFalse( $model['endotoxin_status']['success'] );
	}

	public function test_pass_results_have_success_semantics_for_confirmed_no_growth_and_heavy_metals() {
		$status = $this->view_model->status( 'pass' );
		$this->assertSame( 'Pass', $status['label'] ); $this->assertSame( 'pass', $status['icon'] ); $this->assertTrue( $status['success'] );
		$template = file_get_contents( dirname( __DIR__ ) . '/templates/partials/report-results.php' );
		$this->assertStringContainsString( 'identity_method', $template );
		$this->assertStringContainsString( 'sterility_result', $template );
		$this->assertStringContainsString( 'heavy_metals_summary', $template );
		$this->assertStringContainsString( 'ps-coa-result--success', $template );
	}

	public function test_compound_public_name_and_strength_are_separate_without_duplication() {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Retatrutide 30mg' ) );
		update_post_meta( $compound, 'display_name', 'Retatrutide 30mg' ); update_post_meta( $compound, 'compound_name', 'Retatrutide' ); update_post_meta( $compound, 'strength_value', '29.999999' ); update_post_meta( $compound, 'strength_unit', 'mg' );
		$model = $this->view_model->compound( get_post( $compound ) );
		$this->assertSame( 'Retatrutide', $model['public_name'] ); $this->assertSame( '30', $model['strength_value_display'] ); $this->assertTrue( $model['display_strength_separately'] );
		update_post_meta( $compound, 'compound_name', '' ); $model = $this->view_model->compound( get_post( $compound ) ); $this->assertFalse( $model['display_strength_separately'] );
	}

	public function test_public_copy_is_sanitized_and_empty_values_fall_back() {
		$sanitized = PepSelect\COAArchive\Design_Settings::sanitize( array( 'archive_title' => '<b>Quality</b>', 'history_suffix' => '' ) );
		$this->assertSame( 'Quality', $sanitized['archive_title'] ); $this->assertSame( 'Vetting History', $sanitized['history_suffix'] );
		update_option( PepSelect\COAArchive\Design_Settings::OPTION, $sanitized ); PepSelect\COAArchive\Design_Settings::clear_cache();
		$this->assertSame( 'Quality', PepSelect\COAArchive\Design_Settings::copy( 'archive_title' ) );
	}

	public function test_settings_api_sanitization_and_scoped_css_variables() {
		$admin = new PepSelect\COAArchive\Design_Settings_Admin(); $admin->register_settings();
		$registered = get_registered_settings(); $this->assertArrayHasKey( PepSelect\COAArchive\Design_Settings::OPTION, $registered );
		$sanitized = PepSelect\COAArchive\Design_Settings::sanitize( array( 'accent' => 'red', 'card_radius' => 99, 'lightbox_opacity' => .1 ) );
		$this->assertSame( '#315d58', $sanitized['accent'] ); $this->assertSame( 40, $sanitized['card_radius'] ); $this->assertSame( .5, $sanitized['lightbox_opacity'] );
		$css = PepSelect\COAArchive\Design_Settings::inline_css();
		$this->assertStringStartsWith( '.ps-coa-app{', $css ); $this->assertStringContainsString( '--ps-coa-accent:', $css ); $this->assertStringNotContainsString( ':root', $css );
		$this->assertSame( 'manage_ps_coas', $admin->settings_capability() );
	}

	public function test_reset_assets_search_and_lightbox_are_scoped_and_safe() {
		$admin = file_get_contents( dirname( __DIR__ ) . '/includes/class-design-settings-admin.php' );
		$loader = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$archive = file_get_contents( dirname( __DIR__ ) . '/templates/archive-testing.php' );
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' );
		$this->assertStringContainsString( "check_admin_referer( 'pepselect_coa_reset_design' )", $admin );
		$this->assertStringContainsString( "current_user_can( 'manage_ps_coas' )", $admin );
		$this->assertStringContainsString( 'if ( $hook !== $this->hook )', $admin );
		$this->assertStringContainsString( 'wp_add_inline_style', $loader );
		$this->assertStringContainsString( 'ps-coa-search__button', $archive ); $this->assertStringContainsString( "search_button_copy", $archive );
		$this->assertStringContainsString( 'object-fit: contain', $css ); $this->assertStringContainsString( 'max-width: min(96vw, 100%)', $css ); $this->assertStringContainsString( 'max-height: min(92vh', $css );
		$this->assertStringContainsString( '--ps-coa-lightbox-control-radius', $css );
	}

	private function test_record() {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Compound' ) ); update_post_meta( $compound, 'is_active', 1 );
		$test = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Batch' ) );
		update_post_meta( $test, 'compound_id', $compound ); update_post_meta( $test, 'coa_status', 'approved' ); update_post_meta( $test, 'batch_number', 'B-1' );
		return $test;
	}
}

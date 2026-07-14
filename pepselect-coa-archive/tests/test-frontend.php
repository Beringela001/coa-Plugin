<?php
/** COA-4A public routing, visibility, repository, and view-model tests. */
class PepSelect_COA_Archive_Frontend_Test extends WP_UnitTestCase {
	private $visibility;
	private $tests;
	private $compounds;
	private $view_model;

	public function set_up() {
		parent::set_up();
		do_action( 'init' );
		$this->visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->tests = new PepSelect\COAArchive\COA_Test_Repository( $this->visibility );
		$this->compounds = new PepSelect\COAArchive\Compound_Repository( $this->visibility );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
	}

	public function test_query_variables_and_three_routes_are_registered() {
		$rewrites = new PepSelect\COAArchive\Rewrites();
		$vars = $rewrites->register_query_vars( array() );
		foreach ( array( 'ps_coa_view', 'ps_compound_slug', 'ps_batch_slug' ) as $name ) { $this->assertContains( $name, $vars ); }
		global $wp_rewrite; $rules = $wp_rewrite->rewrite_rules();
		$this->assertArrayHasKey( 'testing/?$', $rules );
		$this->assertArrayHasKey( 'testing/([^/]+)/?$', $rules );
		$this->assertArrayHasKey( 'testing/([^/]+)/([^/]+)/?$', $rules );
		$this->assertSame( 'index.php?ps_coa_view=archive', $rules['testing/?$'] );
		$this->assertStringContainsString( 'ps_coa_view=compound', $rules['testing/([^/]+)/?$'] );
		$this->assertStringContainsString( 'ps_coa_view=report', $rules['testing/([^/]+)/([^/]+)/?$'] );
		$keys = array_keys( $rules );
		$this->assertLessThan( array_search( 'testing/([^/]+)/?$', $keys, true ), array_search( 'testing/([^/]+)/([^/]+)/?$', $keys, true ) );
		$this->assertLessThan( array_search( 'testing/?$', $keys, true ), array_search( 'testing/([^/]+)/?$', $keys, true ) );
		$this->assertFalse( get_post_type_object( 'ps_compound' )->has_archive );
	}

	public function test_virtual_routes_render_without_page_shells_or_frontend_redirects() {
		$rewrites = file_get_contents( dirname( __DIR__ ) . '/includes/class-rewrites.php' );
		$router = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-router.php' );
		$loader = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertStringNotContainsString( 'pagename=testing', $rewrites );
		$this->assertStringNotContainsString( 'wp_redirect', $router );
		$this->assertStringNotContainsString( 'wp_safe_redirect', $router );
		$this->assertStringNotContainsString( "add_filter( 'the_content'", $loader );
		$this->assertStringContainsString( "return \$this->locate( \$context['template'] )", $loader );
	}

	public function test_redirect_canonical_is_disabled_only_for_prefixed_coa_routes() {
		$router = $this->router(); $loader = new PepSelect\COAArchive\Frontend_Template_Loader( $router );
		set_query_var( 'ps_coa_view', '' );
		foreach ( array( '/page/', '/post/', '/product/item/', '/shop/', '/cart/', '/checkout/' ) as $path ) {
			$destination = 'https://example.org' . $path;
			$this->assertSame( $destination, $loader->filter_redirect_canonical( $destination, $destination . '?source=test' ) );
		}
		set_query_var( 'ps_coa_view', 'archive' );
		$this->assertFalse( $loader->filter_redirect_canonical( 'https://example.org/testing/', 'https://example.org/testing/' ) );
		$this->assertFalse( $loader->filter_redirect_canonical( 'https://example.org/testing/', 'https://example.org/testing/?source=test' ) );
		set_query_var( 'ps_coa_view', '' );
	}

	public function test_valid_route_contexts_resolve_without_redirect_helpers() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'RT30-0726-A' );
		$router = $this->router();
		$this->assertSame( 'archive-testing.php', $router->build_archive( 1 )['template'] );
		$this->assertSame( 'single-compound-history.php', $router->build_compound( '', $compound, 1 )['template'] );
		$this->assertSame( 'single-coa-report.php', $router->build_report_by_ids( $compound, $test )['template'] );
	}

	public function test_visibility_allows_only_active_compounds_and_approved_published_tests() {
		$compound = $this->compound(); $this->assertTrue( $this->visibility->is_compound_public( $compound ) );
		$approved = $this->test_record( $compound, 'approved', 'publish', 'A-1' );
		$this->assertTrue( $this->visibility->is_test_public( $approved, $compound ) );
		foreach ( array( 'pending', 'failed', 'archived', 'superseded' ) as $status ) { $this->assertFalse( $this->visibility->is_test_public( $this->test_record( $compound, $status, 'publish', $status ), $compound ) ); }
		foreach ( array( 'draft', 'private', 'trash' ) as $post_status ) { $this->assertFalse( $this->visibility->is_test_public( $this->test_record( $compound, 'approved', $post_status, $post_status ), $compound ) ); }
		update_post_meta( $compound, 'is_active', 0 );
		$this->assertFalse( $this->visibility->is_compound_public( $compound ) );
		$this->assertFalse( $this->visibility->is_test_public( $approved ) );
	}

	public function test_current_report_is_latest_and_is_not_duplicated_in_previous_reports() {
		$compound = $this->compound();
		$this->test_record( $compound, 'approved', 'publish', 'NEWER', '20260720', 0 );
		$current = $this->test_record( $compound, 'approved', 'publish', 'CURRENT', '20260701', 1 );
		$context = $this->router()->build_compound( '', $compound, 1 );
		$this->assertSame( $current, $context['latest_report']['test_id'] );
		$this->assertNotContains( $current, wp_list_pluck( $context['previous_reports'], 'test_id' ) );
	}

	public function test_newest_test_date_is_latest_when_no_current_exists() {
		$compound = $this->compound();
		$this->test_record( $compound, 'approved', 'publish', 'OLD', '20260101', 0 );
		$newest = $this->test_record( $compound, 'approved', 'publish', 'NEW', '20260701', 0 );
		$this->assertSame( $newest, $this->router()->build_compound( '', $compound, 1 )['latest_report']['test_id'] );
	}

	public function test_batch_is_restricted_to_its_related_compound() {
		$first = $this->compound( 'First' ); $second = $this->compound( 'Second' );
		$this->test_record( $first, 'approved', 'publish', 'SAME-BATCH' );
		$this->assertNotEmpty( $this->router()->build_report( get_post( $first )->post_name, 'same-batch' ) );
		$this->assertEmpty( $this->router()->build_report( get_post( $second )->post_name, 'same-batch' ) );
		$this->assertEmpty( $this->router()->build_report( 'missing-compound', 'same-batch' ) );
	}

	public function test_archive_excludes_compounds_without_approved_tests() {
		$without = $this->compound( 'Without Tests' ); $with = $this->compound( 'With Tests' );
		$this->test_record( $with, 'approved', 'publish', 'VISIBLE' );
		$ids = wp_list_pluck( $this->router()->build_archive( 1 )['compounds'], 'compound_id' );
		$this->assertContains( $with, $ids ); $this->assertNotContains( $without, $ids );
	}

	public function test_laboratory_and_status_values_are_not_reinterpreted() {
		$this->assertSame( 'ILS Labs', $this->view_model->laboratory_name( 'ils-labs' ) );
		$this->assertSame( 'Janoshik Analytical', $this->view_model->laboratory_name( 'janoshik' ) );
		$this->assertSame( 'MZ Biolabs', $this->view_model->laboratory_name( 'mz-biotech' ) );
		$this->assertSame( 'Custom Lab', $this->view_model->laboratory_name( 'other', 'Custom Lab' ) );
		$this->assertSame( 'Reported', $this->view_model->status( 'reported' )['label'] );
		$this->assertSame( 'Not Tested', $this->view_model->status( 'not-tested' )['label'] );
		$this->assertNotSame( 'Pass', $this->view_model->status( 'reported' )['label'] );
	}

	public function test_public_view_models_never_expose_internal_fields() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'SAFE' );
		update_post_meta( $test, 'internal_notes', 'secret' ); update_post_meta( $test, 'internal_batch_id', 'private-id' );
		$model = $this->view_model->report( get_post( $test ), get_post( $compound ) );
		$this->assertArrayNotHasKey( 'internal_notes', $model );
		$this->assertArrayNotHasKey( 'internal_batch_id', $model );
		$this->assertStringNotContainsString( 'secret', wp_json_encode( $model ) );
	}

	public function test_pdf_must_be_a_valid_pdf_and_gallery_order_is_preserved() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'MEDIA' );
		$pdf = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'application/pdf', 'guid' => 'https://example.org/report.pdf' ) );
		$first = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'guid' => 'https://example.org/first.jpg' ) );
		$second = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/png', 'guid' => 'https://example.org/second.png' ) );
		$image_urls = array( $first => 'https://example.org/first.jpg', $second => 'https://example.org/second.png' );
		$downsize = static function ( $value, $id ) use ( $image_urls ) { return isset( $image_urls[ $id ] ) ? array( $image_urls[ $id ], 800, 1000, true ) : $value; };
		add_filter( 'image_downsize', $downsize, 10, 2 );
		update_post_meta( $test, 'coa_pdf_id', $pdf ); update_post_meta( $test, 'coa_page_images', array( $second, $first ) );
		$model = $this->view_model->report( get_post( $test ), get_post( $compound ) );
		$this->assertNotEmpty( $model['pdf_url'] ); $this->assertSame( array( $second, $first ), wp_list_pluck( $model['page_images'], 'attachment_id' ) );
		update_post_meta( $test, 'coa_pdf_id', $first );
		$this->assertSame( '', $this->view_model->report( get_post( $test ), get_post( $compound ) )['pdf_url'] );
		remove_filter( 'image_downsize', $downsize, 10 );
	}

	public function test_shortcode_parameters_do_not_bypass_visibility() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'pending', 'publish', 'HIDDEN' );
		$this->assertSame( '', do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' ) );
		update_post_meta( $test, 'coa_status', 'approved' );
		$this->assertStringContainsString( 'ps-coa-report', do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' ) );
	}

	public function test_invalid_route_support_uses_true_404_and_theme_template() {
		$router_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-router.php' );
		$loader_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertStringContainsString( 'set_404()', $router_source );
		$this->assertStringContainsString( 'status_header( 404 )', $router_source );
		$this->assertStringContainsString( 'nocache_headers()', $router_source );
		$this->assertStringContainsString( 'get_404_template()', $loader_source );
		$this->assertStringContainsString( "return \$this->router->is_route() ? false", $loader_source );
	}

	public function test_upgrade_flushes_only_when_installed_version_changes() {
		$calls = 0; $listener = static function () use ( &$calls ) { $calls++; };
		add_action( 'generate_rewrite_rules', $listener );
		update_option( PepSelect\COAArchive\Upgrade::VERSION_OPTION, PEPSELECT_COA_ARCHIVE_VERSION );
		PepSelect\COAArchive\Upgrade::maybe_upgrade(); $this->assertSame( 0, $calls );
		update_option( PepSelect\COAArchive\Upgrade::VERSION_OPTION, '0.4.0-alpha.1' );
		PepSelect\COAArchive\Upgrade::maybe_upgrade(); $this->assertGreaterThan( 0, $calls ); $after = $calls;
		PepSelect\COAArchive\Upgrade::maybe_upgrade(); $this->assertSame( $after, $calls );
		remove_action( 'generate_rewrite_rules', $listener );
	}

	public function test_templates_assets_and_deferred_integrations_are_scoped() {
		foreach ( array( 'archive-testing.php', 'single-compound-history.php', 'single-coa-report.php', 'partials/archive-compound-item.php', 'partials/report-summary.php', 'partials/report-status.php' ) as $template ) { $this->assertFileExists( dirname( __DIR__ ) . '/templates/' . $template ); }
		$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $this->router() );
		$this->assertStringEndsWith( 'templates/archive-testing.php', str_replace( '\\', '/', $loader->locate( 'archive-testing.php' ) ) );
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertStringContainsString( "if ( ! \$this->router->is_route() && ! \$shortcode )", $source );
		$this->assertStringNotContainsString( 'elementor/widgets', strtolower( $source ) );
		$this->assertStringNotContainsString( 'woocommerce_single_product', strtolower( $source ) );
	}

	private function router() {
		return new PepSelect\COAArchive\Frontend_Router( new PepSelect\COAArchive\Frontend_Query(), $this->compounds, $this->tests, $this->view_model );
	}

	private function compound( $title = 'Retatrutide 30mg' ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $title ) );
		update_post_meta( $id, 'display_name', $title ); update_post_meta( $id, 'is_active', 1 ); update_post_meta( $id, 'strength_value', 30 ); update_post_meta( $id, 'strength_unit', 'mg' );
		return $id;
	}

	private function test_record( $compound, $status, $post_status, $batch, $date = '20260701', $current = 0 ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => $post_status, 'post_title' => $batch ) );
		update_post_meta( $id, 'compound_id', $compound ); update_post_meta( $id, 'coa_status', $status ); update_post_meta( $id, 'batch_number', $batch ); update_post_meta( $id, 'test_date', $date ); update_post_meta( $id, 'is_current', $current ); update_post_meta( $id, 'testing_lab', 'ils-labs' ); update_post_meta( $id, 'vials_tested', 1 );
		return $id;
	}
}

<?php
/** COA-4 public routing, visibility, presentation, and view-model tests. */
class PepSelect_COA_Archive_Frontend_Test extends WP_UnitTestCase {
	private $visibility;
	private $tests;
	private $compounds;
	private $view_model;

	public function set_up() {
		parent::set_up();
		unset( $_GET['coa_search'] );
		do_action( 'init' );
		$this->visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->tests = new PepSelect\COAArchive\COA_Test_Repository( $this->visibility );
		$this->compounds = new PepSelect\COAArchive\Compound_Repository( $this->visibility );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
	}

	public function tear_down() { unset( $_GET['coa_search'] ); parent::tear_down(); }

	public function test_archive_search_request_normalization_treats_absent_empty_whitespace_and_invalid_values_as_no_search() {
		$query = new PepSelect\COAArchive\Frontend_Query();
		$this->assertSame( '', $query->search() );
		foreach ( array( '', '   ', "\t\r\n", array( 'Retatrutide' ) ) as $value ) {
			$_GET['coa_search'] = $value;
			$this->assertSame( '', $query->search() );
		}
		$_GET['coa_search'] = '  Retatrutide  ';
		$this->assertSame( 'Retatrutide', $query->search() );
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

	public function test_virtual_routes_supply_specific_seo_metadata_and_schema_without_touching_visible_copy() {
		set_query_var( 'ps_coa_view', 'archive' );
		$router = $this->router(); $router->resolve();
		$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $router );
		$this->assertSame( 'Certificate of Analysis Archive - ' . get_bloginfo( 'name' ), $loader->filter_title( 'Pep Select' ) );
		$this->assertStringContainsString( 'Certificates of Analysis', $loader->filter_description( '' ) );
		$graph = $loader->filter_schema_graph( array() );
		$this->assertSame( 'CollectionPage', $graph[0]['@type'] );
		$this->assertSame( home_url( '/testing/' ), $graph[0]['url'] );
		$this->assertSame( 'BreadcrumbList', $graph[1]['@type'] );
		$this->assertCount( 2, $graph[1]['itemListElement'] );

		$compound_id = $this->compound();
		$test_id = $this->test_record( $compound_id, 'approved', 'publish', 'RT30-0726-A' );
		set_query_var( 'ps_coa_view', 'report' );
		set_query_var( 'ps_compound_slug', get_post_field( 'post_name', $compound_id ) );
		set_query_var( 'ps_batch_slug', get_post_field( 'post_name', $test_id ) );
		$report_router = $this->router(); $report_router->resolve();
		$report_loader = new PepSelect\COAArchive\Frontend_Template_Loader( $report_router );
		$this->assertSame( 'Retatrutide 30mg Batch RT30-0726-A COA - ' . get_bloginfo( 'name' ), $report_loader->filter_title( 'Pep Select' ) );
		$this->assertStringContainsString( 'batch RT30-0726-A', $report_loader->filter_description( '' ) );
		$this->assertCount( 4, $report_loader->filter_schema_graph( array() )[1]['itemListElement'] );

		set_query_var( 'ps_coa_view', '' );
		set_query_var( 'ps_compound_slug', '' );
		set_query_var( 'ps_batch_slug', '' );
	}

	public function test_valid_route_contexts_resolve_without_redirect_helpers() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'RT30-0726-A' );
		$router = $this->router();
		$this->assertSame( 'archive-testing.php', $router->build_archive( 1 )['template'] );
		$this->assertSame( 'single-compound-history.php', $router->build_compound( '', $compound, 1 )['template'] );
		$this->assertSame( 'single-coa-report.php', $router->build_report_by_ids( $compound, $test )['template'] );
	}

	public function test_visibility_allows_public_release_states_but_not_archived_or_unpublished_tests() {
		$compound = $this->compound(); $this->assertTrue( $this->visibility->is_compound_public( $compound ) );
		$approved = $this->test_record( $compound, 'approved', 'publish', 'A-1' );
		$this->assertTrue( $this->visibility->is_test_public( $approved, $compound ) );
		foreach ( array( 'pending', 'in-testing', 'vendor-vetting', 'failed' ) as $status ) { $this->assertTrue( $this->visibility->is_test_public( $this->test_record( $compound, $status, 'publish', $status ), $compound ) ); }
		foreach ( array( 'archived', 'superseded' ) as $status ) { $this->assertFalse( $this->visibility->is_test_public( $this->test_record( $compound, $status, 'publish', $status ), $compound ) ); }
		foreach ( array( 'draft', 'private', 'trash' ) as $post_status ) { $this->assertFalse( $this->visibility->is_test_public( $this->test_record( $compound, 'approved', $post_status, $post_status ), $compound ) ); }
		update_post_meta( $compound, 'is_active', 0 );
		$this->assertFalse( $this->visibility->is_compound_public( $compound ) );
		$this->assertFalse( $this->visibility->is_test_public( $approved ) );
	}

	public function test_legacy_approved_report_without_new_vial_colors_remains_public_and_keeps_compound_in_archive() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'LEGACY' );
		delete_post_meta( $test, 'vial_crimp_color' ); delete_post_meta( $test, 'vial_cap_color' );
		$this->assertTrue( $this->visibility->is_test_public( $test, $compound ) );
		$this->assertContains( $compound, wp_list_pluck( $this->router()->build_archive( 1 )['compounds'], 'compound_id' ) );
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

	public function test_compound_history_separates_incoming_and_keeps_failed_in_previous_reports() {
		$compound = $this->compound();
		$approved = $this->test_record( $compound, 'approved', 'publish', 'APPROVED', '20260701' );
		$incoming = $this->test_record( $compound, 'in-testing', 'publish', 'INCOMING', '' );
		$failed = $this->test_record( $compound, 'failed', 'publish', 'FAILED', '20260601' );
		update_post_meta( $incoming, 'expected_coa_date', '20260730' );
		$context = $this->router()->build_compound( '', $compound, 1 );
		$this->assertSame( $approved, $context['latest_report']['test_id'] );
		$this->assertSame( array( $incoming ), wp_list_pluck( $context['incoming_reports'], 'test_id' ) );
		$this->assertContains( $failed, wp_list_pluck( $context['previous_reports'], 'test_id' ) );
	}

	public function test_batch_is_restricted_to_its_related_compound() {
		$first = $this->compound( 'First' ); $second = $this->compound( 'Second' );
		$this->test_record( $first, 'approved', 'publish', 'SAME-BATCH' );
		$this->assertNotEmpty( $this->router()->build_report( get_post( $first )->post_name, 'same-batch' ) );
		$this->assertEmpty( $this->router()->build_report( get_post( $second )->post_name, 'same-batch' ) );
		$this->assertEmpty( $this->router()->build_report( 'missing-compound', 'same-batch' ) );
	}

	public function test_archive_excludes_compounds_without_public_tests_and_includes_incoming_tests() {
		$without = $this->compound( 'Without Tests' ); $with = $this->compound( 'With Tests' ); $incoming = $this->compound( 'Incoming Tests' );
		$this->test_record( $with, 'approved', 'publish', 'VISIBLE' );
		$this->test_record( $incoming, 'pending', 'publish', 'EXPECTED' );
		$ids = wp_list_pluck( $this->router()->build_archive( 1 )['compounds'], 'compound_id' );
		$this->assertContains( $with, $ids ); $this->assertContains( $incoming, $ids ); $this->assertNotContains( $without, $ids );
	}

	public function test_archive_searches_public_compound_names_and_sanitizes_input() {
		$display = $this->compound( 'Display Match' ); update_post_meta( $display, 'display_name', 'Retatrutide' );
		$compound_name = $this->compound( 'Compound Match' ); update_post_meta( $compound_name, 'compound_name', 'Tesamorelin' );
		$short = $this->compound( 'Short Match' ); update_post_meta( $short, 'short_name', 'BPC' );
		foreach ( array( $display, $compound_name, $short ) as $id ) { $this->test_record( $id, 'approved', 'publish', 'VISIBLE-' . $id ); }
		$this->assertSame( array( $display ), wp_list_pluck( $this->router()->build_archive( 1, 'Retatrutide' )['compounds'], 'compound_id' ) );
		$this->assertSame( array( $compound_name ), wp_list_pluck( $this->router()->build_archive( 1, 'Tesamorelin' )['compounds'], 'compound_id' ) );
		$this->assertSame( array( $short ), wp_list_pluck( $this->router()->build_archive( 1, '<b>BPC</b>' )['compounds'], 'compound_id' ) );
	}

	public function test_archive_empty_whitespace_matching_missing_and_cleared_search_results() {
		$retatrutide = $this->compound( 'Retatrutide 30mg' ); $other = $this->compound( 'BPC-157' );
		update_post_meta( $retatrutide, 'display_name', 'Retatrutide' ); update_post_meta( $retatrutide, 'short_name', 'Reta' );
		$this->test_record( $retatrutide, 'approved', 'publish', 'RETA-BATCH' ); $this->test_record( $other, 'approved', 'publish', 'BPC-BATCH' );
		$all_ids = wp_list_pluck( $this->router()->build_archive( 1 )['compounds'], 'compound_id' );
		$this->assertContains( $retatrutide, $all_ids ); $this->assertContains( $other, $all_ids );
		$this->assertSame( $all_ids, wp_list_pluck( $this->router()->build_archive( 1, '' )['compounds'], 'compound_id' ) );
		$this->assertSame( $all_ids, wp_list_pluck( $this->router()->build_archive( 1, '   ' )['compounds'], 'compound_id' ) );
		$this->assertSame( array( $retatrutide ), wp_list_pluck( $this->router()->build_archive( 1, 'Retatrutide' )['compounds'], 'compound_id' ) );
		$this->assertSame( array( $retatrutide ), wp_list_pluck( $this->router()->build_archive( 1, 'Reta' )['compounds'], 'compound_id' ) );
		$this->assertEmpty( $this->router()->build_archive( 1, 'NothingExists123' )['compounds'] );
		$_GET['coa_search'] = 'NothingExists123';
		$this->assertStringContainsString( 'No matching compounds', do_shortcode( '[pepselect_coa_archive]' ) );
		$this->assertSame( $all_ids, wp_list_pluck( $this->router()->build_archive( 1, '' )['compounds'], 'compound_id' ) );
	}

	public function test_archive_search_does_not_bypass_visibility_or_attach_global_sql_filters() {
		$public = $this->compound( 'Public Retatrutide' ); $inactive = $this->compound( 'Inactive Retatrutide' );
		$this->test_record( $public, 'approved', 'publish', 'PUBLIC' ); $this->test_record( $inactive, 'approved', 'publish', 'INACTIVE' ); update_post_meta( $inactive, 'is_active', 0 );
		$this->assertSame( array( $public ), wp_list_pluck( $this->router()->build_archive( 1, 'Retatrutide' )['compounds'], 'compound_id' ) );
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-compound-repository.php' );
		$this->assertStringNotContainsString( "add_filter( 'posts_search'", $source );
		$this->assertStringNotContainsString( "add_filter( 'posts_where'", $source );
	}

	public function test_archive_cache_keys_separate_normalized_search_and_unsearched_requests() {
		$all = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( 3, 2, 1 ) );
		$this->assertSame( $all, PepSelect\COAArchive\Archive_Cache::key( '   ', 1, 24, array( 1, 2, 3 ) ) );
		$this->assertNotSame( $all, PepSelect\COAArchive\Archive_Cache::key( 'Retatrutide', 1, 24, array( 1, 2, 3 ) ) );
		$this->assertSame( PepSelect\COAArchive\Archive_Cache::key( 'Retatrutide', 1, 24, array( 1 ) ), PepSelect\COAArchive\Archive_Cache::key( '  retatrutide ', 1, 24, array( 1 ) ) );
	}

	public function test_relevant_post_save_advances_archive_cache_namespace() {
		$compound = $this->compound();
		$before = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ) );
		wp_update_post( array( 'ID' => $compound, 'post_title' => 'Updated Retatrutide' ) );
		$this->assertNotSame( $before, PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ) ) );
	}

	public function test_plugin_upgrade_invalidates_cached_empty_archive_namespace() {
		$ids = array( 101 );
		PepSelect\COAArchive\Archive_Cache::set( '', 1, 24, $ids, array( 'posts' => array(), 'total' => 0, 'pages' => 0, 'page' => 1 ) );
		$this->assertNotNull( PepSelect\COAArchive\Archive_Cache::get( '', 1, 24, $ids ) );
		update_option( PepSelect\COAArchive\Upgrade::VERSION_OPTION, '0.4.0-beta.3' );
		PepSelect\COAArchive\Upgrade::maybe_upgrade();
		$this->assertNull( PepSelect\COAArchive\Archive_Cache::get( '', 1, 24, $ids ) );
	}

	public function test_compound_cards_preview_only_three_newest_batches_and_count_all_reports() {
		$compound = $this->compound();
		for ( $number = 1; $number <= 5; $number++ ) { $this->test_record( $compound, 'approved', 'publish', 'BATCH-' . $number, '2026070' . $number ); }
		$model = $this->router()->build_archive( 1 )['compounds'][0];
		$this->assertCount( 3, $model['recent_batches'] );
		$this->assertSame( 5, $model['approved_test_count'] );
		$this->assertSame( 5, $model['public_report_count'] );
		$this->assertSame( array( 'BATCH-5', 'BATCH-4', 'BATCH-3' ), wp_list_pluck( $model['recent_batches'], 'batch_number' ) );
		$this->assertSame( $model['url'], $this->view_model->compound( get_post( $compound ) )['url'] );
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
		$this->assertArrayNotHasKey( 'verification_code', $model );
		$this->assertArrayNotHasKey( 'lab_verification_url', $model );
		$this->assertStringNotContainsString( 'secret', wp_json_encode( $model ) );
	}

	public function test_access_codes_and_verification_urls_are_not_registered_as_public_rest_meta() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' );
		preg_match( '/\$safe = array\( (.*?) \);/s', $source, $matches );
		$this->assertNotEmpty( $matches );
		$this->assertStringNotContainsString( 'verification_code', $matches[1] );
		$this->assertStringNotContainsString( 'lab_verification_url', $matches[1] );
	}

	public function test_public_documentation_uses_exact_report_url_and_hides_verification_metadata() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'approved', 'publish', 'LINKS' );
		$url = 'https://lab.example/reports/exact?id=42';
		update_post_meta( $test, 'lab_report_url', $url );
		update_post_meta( $test, 'lab_verification_url', 'https://lab.example/verify' );
		update_post_meta( $test, 'verification_code', 'PRIVATE-CODE' );
		$html = do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' );
		$this->assertStringContainsString( 'View Verified Lab Report', $html );
		$this->assertStringContainsString( esc_url( $url ), $html );
		$this->assertStringContainsString( 'rel="noopener noreferrer"', $html );
		$this->assertStringNotContainsString( 'lab.example/verify', $html );
		$this->assertStringNotContainsString( 'PRIVATE-CODE', $html );
		$this->assertStringNotContainsString( 'Verify with Laboratory', $html );
		$this->assertStringNotContainsString( 'Access Code', $html );
	}

	public function test_incoming_report_uses_exact_progress_url_without_empty_scientific_panels() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'in-testing', 'publish', 'IN-PROGRESS', '' );
		$url = 'https://lab.example/progress?id=84';
		update_post_meta( $test, 'pending_lab_url', $url ); update_post_meta( $test, 'expected_coa_date', '20260730' );
		$html = do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' );
		$this->assertStringContainsString( 'Verification in Progress', $html );
		$this->assertStringContainsString( 'View Lab Progress', $html );
		$this->assertStringContainsString( esc_url( $url ), $html );
		$this->assertStringNotContainsString( 'Summary Metrics', $html );
		$this->assertStringNotContainsString( 'Full-QC Results', $html );
	}

	public function test_failed_report_remains_inspectable_and_discloses_non_release() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'failed', 'publish', 'FAILED-LOT' );
		$html = do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' );
		$this->assertStringContainsString( 'Did Not Pass Release Review', $html );
		$this->assertStringContainsString( 'This batch was not released for sale.', $html );
		$this->assertStringContainsString( 'Measured values', $html );
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

	public function test_shortcode_allows_published_incoming_reports_but_does_not_bypass_post_visibility() {
		$compound = $this->compound(); $test = $this->test_record( $compound, 'pending', 'publish', 'INCOMING' );
		$this->assertStringContainsString( 'ps-coa-report', do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' ) );
		wp_update_post( array( 'ID' => $test, 'post_status' => 'draft' ) );
		$this->assertSame( '', do_shortcode( '[pepselect_coa_report compound_id="' . $compound . '" test_id="' . $test . '"]' ) );
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
		foreach ( array( 'archive-testing.php', 'single-compound-history.php', 'single-coa-report.php', 'partials/archive-compound-item.php', 'partials/report-summary.php', 'partials/report-status.php', 'partials/compound-card.php', 'partials/latest-report-card.php', 'partials/previous-report-card.php', 'partials/incoming-report-card.php', 'partials/batch-identity.php', 'partials/status-indicator.php', 'partials/report-metrics.php', 'partials/report-results.php', 'partials/report-documentation.php', 'partials/certificate-gallery.php', 'partials/report-hero.php', 'partials/batch-vial-image.php', 'partials/batch-identity-meta.php', 'partials/report-summary-metrics.php', 'partials/full-qc-status-strip.php', 'partials/full-qc-results-table.php', 'partials/certificate-pages.php', 'partials/batch-identity-gallery.php', 'partials/gallery-lightbox.php', 'partials/laboratory-report-panel.php', 'partials/report-navigation.php' ) as $template ) { $this->assertFileExists( dirname( __DIR__ ) . '/templates/' . $template ); }
		$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $this->router() );
		$this->assertStringEndsWith( 'templates/archive-testing.php', str_replace( '\\', '/', $loader->locate( 'archive-testing.php' ) ) );
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertStringContainsString( "if ( ! \$this->router->is_route() && ! \$shortcode )", $source );
		$this->assertStringContainsString( 'assets/css/pepselect-coa-frontend.css', $source );
		$this->assertStringContainsString( 'context_has_gallery', $source );
		$this->assertFileExists( dirname( __DIR__ ) . '/assets/js/pepselect-coa-lightbox.js' );
		$this->assertStringNotContainsString( 'elementor/widgets', strtolower( $source ) );
		$this->assertStringNotContainsString( 'woocommerce_single_product', strtolower( $source ) );
	}

	public function test_templates_render_compact_cards_metrics_statuses_and_accessible_gallery_controls() {
		$archive = file_get_contents( dirname( __DIR__ ) . '/templates/partials/compound-card.php' );
		$archive_page = file_get_contents( dirname( __DIR__ ) . '/templates/archive-testing.php' );
		$report = file_get_contents( dirname( __DIR__ ) . '/templates/single-coa-report.php' );
		$gallery = file_get_contents( dirname( __DIR__ ) . '/templates/partials/certificate-pages.php' ) . file_get_contents( dirname( __DIR__ ) . '/templates/partials/gallery-lightbox.php' );
		$status = file_get_contents( dirname( __DIR__ ) . '/templates/partials/status-indicator.php' );
		$this->assertStringContainsString( 'array_slice( $compound[\'recent_batches\'], 0, 3 )', $archive );
		$this->assertStringContainsString( 'method="get"', $archive_page );
		$this->assertStringContainsString( 'name="coa_search"', $archive_page );
		$this->assertStringContainsString( 'value="<?php echo esc_attr( $search ); ?>"', $archive_page );
		$this->assertStringContainsString( 'type="submit"', $archive_page );
		$this->assertStringContainsString( 'Clear', $archive_page );
		$this->assertStringContainsString( "Design_Settings::copy( 'view_history' )", $archive );
		$this->assertStringContainsString( 'report-summary-metrics.php', $report );
		$this->assertStringContainsString( 'full-qc-results-table.php', $report );
		$this->assertStringContainsString( 'medium_large', file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' ) );
		foreach ( array( 'data-ps-coa-close', 'data-ps-coa-prev', 'data-ps-coa-next', 'aria-modal="true"', 'loading="lazy"' ) as $needle ) { $this->assertStringContainsString( $needle, $gallery ); }
		$this->assertStringContainsString( "'pass' === \$icon", $status );
		$this->assertStringContainsString( "array( 'fail', 'failed' )", $status );
		$this->assertStringNotContainsString( 'emoji', strtolower( $status ) );
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
		update_post_meta( $id, 'compound_id', $compound ); update_post_meta( $id, 'coa_status', $status ); update_post_meta( $id, 'batch_number', $batch ); update_post_meta( $id, 'test_date', $date ); update_post_meta( $id, 'is_current', $current ); update_post_meta( $id, 'testing_lab', 'ils-labs' ); update_post_meta( $id, 'vials_tested', 1 ); update_post_meta( $id, 'vial_crimp_color', 'silver' ); update_post_meta( $id, 'vial_cap_color', 'blue' );
		if ( 'in-testing' === $status ) { update_post_meta( $id, 'expected_coa_date', '20260730' ); }
		return $id;
	}
}

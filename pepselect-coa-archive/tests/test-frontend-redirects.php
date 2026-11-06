<?php
/** Redirects for retired compound slugs and progressed batch URLs. */
class PepSelect_COA_Archive_Frontend_Redirects_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Frontend_View_Model */
	private $view_model;

	/** @var PepSelect\COAArchive\Frontend_Router */
	private $router;

	/** @var PepSelect\COAArchive\Frontend_Redirects */
	private $redirects;

	public function set_up() {
		parent::set_up();
		$visibility       = new PepSelect\COAArchive\Frontend_Visibility();
		$compounds        = new PepSelect\COAArchive\Compound_Repository( $visibility );
		$tests            = new PepSelect\COAArchive\COA_Test_Repository( $visibility );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
		$this->router     = new PepSelect\COAArchive\Frontend_Router( new PepSelect\COAArchive\Frontend_Query(), $compounds, $tests, $this->view_model );
		$this->redirects  = new PepSelect\COAArchive\Frontend_Redirects( $this->router, new PepSelect\COAArchive\Frontend_Query(), $compounds, $tests, $this->view_model );
	}

	public function tear_down() {
		remove_all_filters( 'pepselect_coa_compound_slug_aliases' );
		unset( $_GET['utm'] );
		set_query_var( 'ps_coa_view', '' );
		set_query_var( 'ps_compound_slug', '' );
		set_query_var( 'ps_batch_slug', '' );
		parent::tear_down();
	}

	public function test_explicit_alias_map_points_both_legacy_slugs_at_compound_961() {
		$map = PepSelect\COAArchive\Frontend_Redirects::explicit_aliases();
		$this->assertSame( 961, $map['961'] );
		$this->assertSame( 961, $map['retatrutide-10mg'] );
	}

	public function test_submitted_to_lab_progress_url_stays_valid_and_batch_number_stays_out_of_the_url() {
		$compound = $this->compound( 'glp-1-s-20-mg' );
		$test     = $this->pending_submitted( $compound, 'SG2026295JP' );
		$post     = get_post( $test );

		$this->assertSame( 'progress-' . $test, $this->view_model->batch_slug( $post ) );
		$this->assertNotEmpty( $this->router->build_report( 'glp-1-s-20-mg', 'progress-' . $test ) );
		$this->assertSame( '', $this->view_model->test_summary( $post, get_post( $compound ) )['batch_number'] );
		$this->assertEmpty( $this->router->build_report( 'glp-1-s-20-mg', 'sg2026295jp' ) );
		$this->assertEmpty( $this->router->build_report( 'glp-1-s-20-mg', 'glp-1-s-sg2026295jp' ) );

		$_GET['utm'] = 'qr';
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'progress-' . $test ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'sg2026295jp' ) );
	}

	public function test_progress_id_redirects_in_one_hop_after_the_stage_publishes_a_batch_slug() {
		$compound = $this->compound( 'glp-1-s-20-mg' );
		$test     = $this->pending_submitted( $compound, 'SG2026295JP' );
		update_post_meta( $test, 'workflow_stage', 'in-testing' );
		update_post_meta( $test, 'testing_lab', 'ils-labs' );

		$canonical = $this->view_model->test_url( get_post( $compound ), get_post( $test ) );
		$target    = $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'progress-' . $test );

		$this->assertSame( 'sg2026295jp', $this->view_model->batch_slug( get_post( $test ) ) );
		$this->assertSame( $canonical, $target );
		$this->assertStringNotContainsString( 'progress-', $target );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'sg2026295jp' ) );
	}

	public function test_compound_rename_records_the_old_slug_and_redirects_history_and_batch_paths() {
		$compound = $this->compound( 'retatrutide-10mg' );
		$test     = $this->approved( $compound, 'RT1026258JP', 'glp-3-r-rt1026258jp' );
		wp_update_post( array( 'ID' => $compound, 'post_name' => '3g-rt-10' ) );

		$this->assertContains( 'retatrutide-10mg', get_post_meta( $compound, PepSelect\COAArchive\Frontend_Redirects::FORMER_SLUG_META, false ) );
		$this->assertSame( '3g-rt-10', get_post( $compound )->post_name );
		$this->assertSame( 'RT1026258JP', get_post_meta( $test, 'batch_number', true ) );

		$compound_url = $this->view_model->compound_url( get_post( $compound ) );
		$batch_url    = home_url( user_trailingslashit( 'testing/3g-rt-10/rt1026258jp' ) );
		$alias_url    = home_url( user_trailingslashit( 'testing/3g-rt-10/glp-3-r-rt1026258jp' ) );

		$this->assertSame( $compound_url, $this->redirects->redirect_target( 'compound', 'retatrutide-10mg' ) );
		$this->assertSame( $batch_url, $this->redirects->redirect_target( 'report', 'retatrutide-10mg', 'rt1026258jp' ) );
		$this->assertSame( $alias_url, $this->redirects->redirect_target( 'report', 'retatrutide-10mg', 'glp-3-r-rt1026258jp' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', '3g-rt-10' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '3g-rt-10', 'rt1026258jp' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'retatrutide-10mg', 'missing-batch' ) );
	}

	public function test_renamed_compound_keeps_a_pending_progress_url_on_the_new_slug() {
		$compound = $this->compound( 'glp-1-s-20-mg' );
		$test     = $this->pending_submitted( $compound, 'SG2026295JP' );
		wp_update_post( array( 'ID' => $compound, 'post_name' => '1g-sm-20-mg' ) );

		$target = $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'progress-' . $test );

		$this->assertSame( home_url( user_trailingslashit( 'testing/1g-sm-20-mg/progress-' . $test ) ), $target );
		$this->assertStringNotContainsString( 'sg2026295jp', $target );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '1g-sm-20-mg', 'progress-' . $test ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '1g-sm-20-mg', 'sg2026295jp' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'sg2026295jp' ) );
	}

	public function test_printed_961_paths_follow_compound_961_without_an_intermediate_hop() {
		$compound = wp_insert_post(
			array(
				'import_id'   => 961,
				'post_type'   => 'ps_compound',
				'post_status' => 'publish',
				'post_title'  => '3G-RT 10',
				'post_name'   => '3g-rt-10',
			)
		);
		$this->assertSame( 961, $compound );
		update_post_meta( 961, 'is_active', 1 );
		$this->approved( 961, 'RT2026205JP', 'glp-3-r-rt2026205jp' );
		$qr = new PepSelect\COAArchive\QR_Redirects();

		$this->assertSame( home_url( '/testing/3g-rt-10/' ), $qr->destination_for_path( '/testing/961/' ) );
		$this->assertSame( home_url( '/testing/3g-rt-10/rt2026205jp/' ), $qr->destination_for_path( '/testing/961/rt2026205jp/' ) );
		$this->assertSame( home_url( '/testing/nad-500-mg/nd50026205js/' ), $qr->destination_for_path( '/testing/nad-500-mg/progress-1269/' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', '3g-rt-10' ) );
	}

	public function test_legacy_map_sends_961_and_the_old_slug_straight_to_the_current_url() {
		$compound = $this->compound( '3g-rt-10' );
		$this->approved( $compound, 'RT1026258JP', 'glp-3-r-rt1026258jp' );
		add_filter(
			'pepselect_coa_compound_slug_aliases',
			static function () use ( $compound ) {
				return array(
					'961'              => $compound,
					'retatrutide-10mg' => $compound,
				);
			}
		);

		$compound_url = $this->view_model->compound_url( get_post( $compound ) );
		$batch_url    = home_url( user_trailingslashit( 'testing/3g-rt-10/rt1026258jp' ) );

		$this->assertSame( $compound_url, $this->redirects->redirect_target( 'compound', '961' ) );
		$this->assertSame( $compound_url, $this->redirects->redirect_target( 'compound', 'retatrutide-10mg' ) );
		$this->assertSame( $batch_url, $this->redirects->redirect_target( 'report', '961', 'rt1026258jp' ) );
		$this->assertSame( $batch_url, $this->redirects->redirect_target( 'report', 'retatrutide-10mg', 'RT1026258JP' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', '3g-rt-10' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '3g-rt-10', 'rt1026258jp' ) );
	}

	public function test_redirect_keeps_the_query_string_and_does_not_loop() {
		$compound = $this->compound( 'retatrutide-10mg' );
		$this->approved( $compound, 'RT2026205JP', 'glp-3-r-rt2026205jp' );
		wp_update_post( array( 'ID' => $compound, 'post_name' => '3g-rt-10' ) );
		$_GET['utm'] = 'qr';

		$url = $this->redirects->redirect_target( 'report', 'retatrutide-10mg', 'rt2026205jp' );
		parse_str( (string) wp_parse_url( $url, PHP_URL_QUERY ), $query );

		$this->assertSame( 'qr', $query['utm'] );
		$this->assertSame( untrailingslashit( (string) wp_parse_url( home_url( user_trailingslashit( 'testing/3g-rt-10/rt2026205jp' ) ), PHP_URL_PATH ) ), untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '3g-rt-10', 'rt2026205jp' ) );
	}

	public function test_progress_id_for_another_compound_is_not_redirected() {
		$owner = $this->compound( 'glp-1-s-20-mg' );
		$test  = $this->pending_submitted( $owner, 'SG2026295JP' );
		update_post_meta( $test, 'workflow_stage', 'in-testing' );
		update_post_meta( $test, 'testing_lab', 'ils-labs' );
		$other = $this->compound( 'bpc-157-10-mg' );
		$this->approved( $other, 'BPC1', 'bpc1' );

		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'bpc-157-10-mg', 'progress-' . $test ) );
		$this->assertNotEmpty( $this->router->build_report( 'glp-1-s-20-mg', 'sg2026295jp' ) );
	}

	public function test_ambiguous_former_slugs_do_not_redirect() {
		$first  = $this->compound( 'first-compound' );
		$second = $this->compound( 'second-compound' );
		$this->approved( $first, 'FIRST1', 'first-1' );
		$this->approved( $second, 'SECOND1', 'second-1' );
		add_post_meta( $first, PepSelect\COAArchive\Frontend_Redirects::FORMER_SLUG_META, 'shared-old' );
		add_post_meta( $second, PepSelect\COAArchive\Frontend_Redirects::FORMER_SLUG_META, 'shared-old' );

		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'shared-old' ) );
	}

	public function test_current_stage_rules_and_redirect_hook_order_stay_in_place() {
		$view   = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' );
		$plugin = file_get_contents( dirname( __DIR__ ) . '/includes/class-plugin.php' );
		$self   = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-redirects.php' );

		$this->assertStringContainsString( "'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab'", $view );
		$this->assertStringContainsString( 'register_hooks()', $plugin );
		$this->assertStringContainsString( "add_action( 'template_redirect', array( \$this, 'maybe_redirect' ), 2 )", $self );
		$loader = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$sitemaps = file_get_contents( dirname( __DIR__ ) . '/includes/class-seo-sitemaps.php' );
		$this->assertStringContainsString( "add_filter( 'wpseo_robots_array'", $loader );
		$this->assertStringContainsString( 'exclude_unpassed_from_llms_txt_query', $sitemaps );
		$this->assertStringContainsString( 'wp_safe_redirect( $target, 301 )', $self );
		$this->assertStringContainsString( 'new QR_Redirects()', $plugin );
		$this->assertStringContainsString( '$this->seo_sitemaps->register_hooks()', $plugin );
	}

	public function test_registry_lists_retired_slugs_and_skips_s31_and_description_only_compounds() {
		$map = PepSelect\COAArchive\Frontend_Redirects::explicit_aliases();
		$expected = array(
			'961' => 961,
			'retatrutide-10mg' => 961,
			'885' => 885,
			'retatrutide-20mg' => 885,
			'883' => 883,
			'retatrutide-30mg' => 883,
			'2062' => 2062,
			'glp-2t-20-mg' => 2062,
			'2724' => 2724,
			'glp-1-s-20-mg' => 2724,
			'974' => 974,
			'tesamorelin-10-mg' => 974,
			'984' => 984,
			'pt-141-10-mg' => 984,
			'1690' => 1690,
			'ss-31-10-mg' => 1690,
			'1684' => 1684,
			'cagrilintide-10-mg' => 1684,
			'2324' => 2324,
			'wolverine-bpc-157-tb-500-10-mg' => 2324,
		);
		foreach ( $expected as $slug => $id ) {
			$this->assertSame( $id, $map[ $slug ] );
		}
		foreach ( array( 's31', 's31-10', 'ps-31-10', '3g-rt-10', '3g-rt-20', '3g-rt-30', '2g-tz-20', '1g-sm-20', 'tesa-10', 'ps-141-10', 'cag-10', 'bpc157-tb500-10' ) as $absent ) {
			$this->assertArrayNotHasKey( $absent, $map );
		}
		$ids = array_map( 'intval', array_values( $map ) );
		foreach ( array( 972, 1686, 1901, 1904, 970, 979, 1688, 988 ) as $left_alone ) {
			$this->assertNotContains( $left_alone, $ids );
		}
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-redirects.php' );
		$this->assertStringNotContainsString( "'s31-10'", $source );
		$this->assertStringNotContainsString( "'ps-31-10'", $source );
		$this->assertStringNotContainsString( 'strength_value', $source );
	}

	public function test_each_renamed_family_sends_old_and_numeric_paths_straight_to_the_saved_slug() {
		$families = array(
			array( 961, 'retatrutide-10mg', '3g-rt-10', 'RT1026258JP' ),
			array( 885, 'retatrutide-20mg', '3g-rt-20', 'RT2026233GX' ),
			array( 883, 'retatrutide-30mg', '3g-rt-30', 'RT3026233GX' ),
			array( 2062, 'glp-2t-20-mg', '2g-tz-20', 'TZ2026258JP' ),
			array( 2724, 'glp-1-s-20-mg', '1g-sm-20', 'SG2026295JP' ),
			array( 974, 'tesamorelin-10-mg', 'tesa-10', 'TS1026264GX' ),
			array( 984, 'pt-141-10-mg', 'ps-141-10', 'PSPT14162926JP' ),
			array( 1690, 'ss-31-10-mg', 'ps-31-10', 'SS1026233GX' ),
			array( 1684, 'cagrilintide-10-mg', 'cag-10', 'CG1026233GX' ),
			array( 2324, 'wolverine-bpc-157-tb-500-10-mg', 'bpc157-tb500-10', 'WV1026263JP' ),
		);
		foreach ( $families as $family ) {
			list( $id, $old, $new, $batch ) = $family;
			$this->compound_at( $id, $old );
			$test = $this->approved( $id, $batch, sanitize_title( $batch ) );
			update_post_meta( $test, 'coa_pdf_id', 1000 + $id );
			wp_update_post( array( 'ID' => $id, 'post_name' => $new ) );

			$compound_url = $this->testing_url( $new );
			$batch_url = $this->testing_url( $new, sanitize_title( $batch ) );
			$this->assertSame( $compound_url, $this->redirects->redirect_target( 'compound', $old ), $old );
			$this->assertSame( $compound_url, $this->redirects->redirect_target( 'compound', (string) $id ), $id );
			$this->assertSame( $batch_url, $this->redirects->redirect_target( 'report', $old, sanitize_title( $batch ) ), $old );
			$this->assertSame( $batch_url, $this->redirects->redirect_target( 'report', (string) $id, sanitize_title( $batch ) ), $id );
			$this->assertSame( '', $this->redirects->redirect_target( 'compound', $new ), $new );
			$this->assertSame( '', $this->redirects->redirect_target( 'report', $new, sanitize_title( $batch ) ), $new );
			$this->assertStringNotContainsString( '/testing/' . $old . '/', $this->redirects->redirect_target( 'compound', (string) $id ) );
			$this->assertSame( $batch, get_post_meta( $test, 'batch_number', true ) );
			$this->assertSame( 1000 + $id, absint( get_post_meta( $test, 'coa_pdf_id', true ) ) );
			$this->assertSame( '10', get_post_meta( $id, 'strength_value', true ) );
		}
	}

	public function test_ps31_follows_the_saved_slug_without_an_s31_hop() {
		$this->compound_at( 1690, 'ss-31-10-mg' );
		update_post_meta( 1690, 'display_name', 'SS-31 10 mg' );
		update_post_meta( 1690, 'compound_name', 'SS-31' );
		update_post_meta( 1690, 'short_name', 'SS-31' );
		$issued = $this->approved( 1690, 'SS1026233GX', 'ss1026233gx' );
		update_post_meta( $issued, 'coa_pdf_id', 1895 );
		update_post_meta( $issued, 'purity_percentage', '99.1' );
		update_post_meta( $issued, 'report_notes', 'issued note stays' );

		update_post_meta( 1690, 'display_name', 'PS-31 10' );
		update_post_meta( 1690, 'compound_name', 'PS-31' );
		update_post_meta( 1690, 'short_name', 'PS-31' );

		$this->assertSame( 'ss-31-10-mg', get_post( 1690 )->post_name );
		$this->assertSame( '10', get_post_meta( 1690, 'strength_value', true ) );
		$this->assertSame( 'mg', get_post_meta( 1690, 'strength_unit', true ) );
		$this->assertSame( 'SS1026233GX', get_post_meta( $issued, 'batch_number', true ) );
		$this->assertSame( 1895, absint( get_post_meta( $issued, 'coa_pdf_id', true ) ) );
		$this->assertSame( '99.1', get_post_meta( $issued, 'purity_percentage', true ) );
		$this->assertSame( 'issued note stays', get_post_meta( $issued, 'report_notes', true ) );
		$this->assertSame( 'SS-31 10 mg', get_post_meta( $issued, PepSelect\COAArchive\COA_Test_Service::ISSUED_DISPLAY_NAME, true ) );
		$this->assertSame( 'SS-31', get_post_meta( $issued, PepSelect\COAArchive\COA_Test_Service::ISSUED_COMPOUND_NAME, true ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 's31' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 's31-10' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'ps-31-10' ) );

		wp_update_post( array( 'ID' => 1690, 'post_name' => 'ps-31-10' ) );
		$_GET['utm'] = 'family';
		$compound_target = $this->redirects->redirect_target( 'compound', 'ss-31-10-mg' );
		$numeric_target = $this->redirects->redirect_target( 'compound', '1690' );
		$batch_target = $this->redirects->redirect_target( 'report', 'ss-31-10-mg', 'ss1026233gx' );
		$this->assertSame( $this->testing_url( 'ps-31-10' ), remove_query_arg( 'utm', $compound_target ) );
		$this->assertSame( $this->testing_url( 'ps-31-10' ), remove_query_arg( 'utm', $numeric_target ) );
		$this->assertSame( $this->testing_url( 'ps-31-10', 'ss1026233gx' ), remove_query_arg( 'utm', $batch_target ) );
		$this->assertStringContainsString( 'utm=family', $compound_target );
		$this->assertStringNotContainsString( 's31-10', $compound_target );
		$this->assertSame( wp_parse_url( home_url( '/' ), PHP_URL_HOST ), wp_parse_url( $compound_target, PHP_URL_HOST ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'ps-31-10' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 's31-10' ) );

		$live = $this->view_model->compound( get_post( 1690 ) );
		$report = $this->router->build_report( 'ps-31-10', 'ss1026233gx' );
		$this->assertSame( 'PS-31 10', $live['listing_name'] );
		$this->assertSame( 'PS-31', $live['public_name'] );
		$this->assertSame( 'PS-31 10', $report['compound']['listing_name'] );
		$this->assertSame( 'PS-31', $report['compound']['public_name'] );
		$this->assertSame( 'SS-31 10 mg', $report['compound']['issued_display_name'] );
		$this->assertSame( 'SS-31', $report['compound']['issued_compound_name'] );
		$this->assertSame( 'ss1026233gx', $report['test']['slug'] );

		set_query_var( 'ps_coa_view', 'compound' );
		set_query_var( 'ps_compound_slug', 'ps-31-10' );
		set_query_var( 'ps_batch_slug', '' );
		$router = $this->fresh_router();
		$router->resolve();
		$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $router );
		$this->assertSame( 'PS-31 10 COAs & Batch History | ' . get_bloginfo( 'name' ), $loader->filter_title( '' ) );
		$graph = $loader->filter_schema_graph( array() );
		$this->assertSame( 'PS-31 10', $graph[1]['itemListElement'][2]['name'] );

		set_query_var( 'ps_coa_view', 'report' );
		set_query_var( 'ps_compound_slug', 'ps-31-10' );
		set_query_var( 'ps_batch_slug', 'ss1026233gx' );
		$report_router = $this->fresh_router();
		$report_router->resolve();
		$report_loader = new PepSelect\COAArchive\Frontend_Template_Loader( $report_router );
		$this->assertSame( 'PS-31 10 Batch SS1026233GX Lab Report | ' . get_bloginfo( 'name' ), $report_loader->filter_title( '' ) );
		$this->assertStringNotContainsString( 'SS-31', $report_loader->filter_title( '' ) );
		set_query_var( 'ps_coa_view', '' );
		set_query_var( 'ps_compound_slug', '' );
		set_query_var( 'ps_batch_slug', '' );
	}

	public function test_progress_parents_move_with_the_compound_and_keep_the_progress_id() {
		$cases = array(
			array( 2724, 'glp-1-s-20-mg', '1g-sm-20', 2729, 'SG2026295JP' ),
			array( 885, 'retatrutide-20mg', '3g-rt-20', 2533, 'RT2026233GX' ),
			array( 883, 'retatrutide-30mg', '3g-rt-30', 2639, 'RT3026233GX' ),
		);
		foreach ( $cases as $case ) {
			list( $compound_id, $old, $new, $test_id, $batch ) = $case;
			$this->compound_at( $compound_id, $old );
			$this->progress_at( $test_id, $compound_id, $batch );
			wp_update_post( array( 'ID' => $compound_id, 'post_name' => $new ) );

			$progress = 'progress-' . $test_id;
			$target = $this->redirects->redirect_target( 'report', $old, $progress );
			$this->assertSame( $this->testing_url( $new, $progress ), $target, $old );
			$this->assertSame( $this->testing_url( $new, $progress ), $this->redirects->redirect_target( 'report', (string) $compound_id, $progress ), (string) $compound_id );
			$this->assertSame( '', $this->redirects->redirect_target( 'report', $new, $progress ), $new );
			$this->assertSame( '', $this->redirects->redirect_target( 'report', $old, sanitize_title( $batch ) ), $batch );
			$this->assertSame( '', $this->redirects->redirect_target( 'report', $new, sanitize_title( $batch ) ), $batch );
			$this->assertNotEmpty( $this->router->build_report( $new, $progress ) );
			$this->assertSame( '', $this->view_model->test_summary( get_post( $test_id ), get_post( $compound_id ) )['batch_number'] );
		}

		update_post_meta( 2533, 'workflow_stage', 'in-testing' );
		update_post_meta( 2533, 'testing_lab', 'ils-labs' );
		$published = $this->redirects->redirect_target( 'report', 'retatrutide-20mg', 'progress-2533' );
		$this->assertSame( $this->testing_url( '3g-rt-20', 'rt2026233gx' ), $published );
		$this->assertStringNotContainsString( 'progress-', $published );
		$this->assertStringNotContainsString( 'retatrutide-20mg', $published );
	}

	public function test_unrenamed_bpc_progress_2642_stays_on_its_parent_until_the_stage_changes() {
		$this->compound_at( 970, 'bpc-157-10-mg' );
		$this->progress_at( 2642, 970, 'BP1026232JP' );
		$this->compound_at( 2724, '1g-sm-20' );

		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'bpc-157-10-mg', 'progress-2642' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'bpc-157-10-mg', 'bp1026232jp' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'glp-1-s-20-mg', 'progress-2642' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '1g-sm-20', 'progress-2642' ) );
		$this->assertNotContains( 970, array_map( 'intval', array_values( PepSelect\COAArchive\Frontend_Redirects::explicit_aliases() ) ) );

		update_post_meta( 2642, 'workflow_stage', 'in-testing' );
		update_post_meta( 2642, 'testing_lab', 'ils-labs' );
		$target = $this->redirects->redirect_target( 'report', 'bpc-157-10-mg', 'progress-2642' );
		$this->assertSame( $this->testing_url( 'bpc-157-10-mg', 'bp1026232jp' ), $target );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', 'bpc-157-10-mg', 'bp1026232jp' ) );
	}

	public function test_repeated_renames_unknown_slugs_and_offsite_input_stay_on_this_site() {
		$this->compound_at( 885, 'retatrutide-20mg' );
		$this->approved( 885, 'RT2026233GX', 'rt2026233gx' );
		wp_update_post( array( 'ID' => 885, 'post_name' => '3g-rt-20' ) );
		wp_update_post( array( 'ID' => 885, 'post_name' => '3g-rt-20-b' ) );

		$this->assertContains( 'retatrutide-20mg', get_post_meta( 885, PepSelect\COAArchive\Frontend_Redirects::FORMER_SLUG_META, false ) );
		$this->assertContains( '3g-rt-20', get_post_meta( 885, PepSelect\COAArchive\Frontend_Redirects::FORMER_SLUG_META, false ) );
		$this->assertSame( $this->testing_url( '3g-rt-20-b' ), $this->redirects->redirect_target( 'compound', 'retatrutide-20mg' ) );
		$this->assertSame( $this->testing_url( '3g-rt-20-b' ), $this->redirects->redirect_target( 'compound', '3g-rt-20' ) );
		$this->assertSame( $this->testing_url( '3g-rt-20-b' ), $this->redirects->redirect_target( 'compound', '885' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', '3g-rt-20-b' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'not-a-real-compound' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'https://evil.example/testing/ps-31-10' ) );
		$this->assertSame( '', $this->redirects->redirect_target( 'report', '//evil.example', 'progress-1' ) );
		$this->assertSame( '10', get_post_meta( 885, 'strength_value', true ) );
	}

	public function test_linked_product_permalink_changes_without_a_testing_rewrite() {
		if ( ! post_type_exists( 'product' ) ) {
			register_post_type( 'product', array( 'public' => true ) );
		}
		$product = self::factory()->post->create(
			array(
				'post_type' => 'product',
				'post_status' => 'publish',
				'post_title' => 'BPC-157 10',
				'post_name' => 'bpc157-10',
			)
		);
		$compound = $this->compound( 'bpc-157-10-mg' );
		update_post_meta( $compound, 'woocommerce_product_id', $product );
		$batch = $this->approved( $compound, 'BP1026232JP', 'bp1026232jp' );

		$before = $this->view_model->compound( get_post( $compound ) )['woocommerce_product_url'];
		$this->assertStringContainsString( 'bpc157-10', $before );
		wp_update_post( array( 'ID' => $product, 'post_name' => 'bpc-157-10' ) );
		$after = $this->view_model->compound( get_post( $compound ) )['woocommerce_product_url'];
		$this->assertStringContainsString( 'bpc-157-10', $after );
		$this->assertStringNotContainsString( 'bpc157-10', $after );
		$this->assertSame( '', $this->redirects->redirect_target( 'compound', 'bpc157-10' ) );
		$this->assertSame( 'bpc-157-10-mg', get_post( $compound )->post_name );
		$this->assertSame( 'BP1026232JP', get_post_meta( $batch, 'batch_number', true ) );
	}

	public function test_listing_titles_keep_each_approved_strength_once() {
		$this->assertSame(
			array( 961, 885, 883, 2062, 2724, 974, 984, 1690, 1684, 2324 ),
			PepSelect\COAArchive\Frontend_Redirects::approved_rename_ids()
		);
		$cases = array(
			array( 961, '3g-rt-10', '3G-RT 10', '3G-RT', '10' ),
			array( 885, '3g-rt-20', '3G-RT 20', '3G-RT', '20' ),
			array( 883, '3g-rt-30', '3G-RT 30', '3G-RT', '30' ),
			array( 2062, '2g-tz-20', '2G-TZ 20', '2G-TZ', '20' ),
			array( 2724, '1g-sm-20', '1G-SM 20', '1G-SM', '20' ),
			array( 974, 'tesa-10', 'TESA 10', 'TESA', '10' ),
			array( 984, 'ps-141-10', 'PS-141 10', 'PS-141', '10' ),
			array( 1690, 'ps-31-10', 'PS-31 10', 'PS-31', '10' ),
			array( 1684, 'cag-10', 'CAG 10', 'CAG', '10' ),
			array( 2324, 'bpc157-tb500-10', 'BPC157/TB500 10', 'BPC157/TB500', '10' ),
		);
		$loader_source = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertStringContainsString( "add_filter( 'pre_get_document_title', array( \$this, 'filter_title' ), 20 )", $loader_source );
		$this->assertStringContainsString( "add_filter( 'wpseo_title', array( \$this, 'filter_title' ), 20 )", $loader_source );
		$this->assertStringContainsString( "add_filter( 'wpseo_opengraph_title', array( \$this, 'filter_title' ), 20 )", $loader_source );
		$this->assertStringContainsString( "add_filter( 'wpseo_twitter_title', array( \$this, 'filter_title' ), 20 )", $loader_source );
		$titles = array();
		foreach ( $cases as $case ) {
			list( $id, $slug, $display, $base, $strength ) = $case;
			$this->compound_at( $id, $slug );
			update_post_meta( $id, 'display_name', $display );
			update_post_meta( $id, 'compound_name', $base );
			update_post_meta( $id, 'short_name', $base );
			update_post_meta( $id, 'strength_value', $strength );
			update_post_meta( $id, 'strength_unit', 'mg' );
			$this->approved( $id, 'BATCH-' . strtoupper( $slug ), 'batch-' . $slug );
			$model = $this->view_model->compound( get_post( $id ) );
			$this->assertSame( $base, $model['public_name'], $slug );
			$this->assertSame( $display, $model['listing_name'], $slug );
			$this->assertSame( $strength, $model['strength_value_display'], $slug );
			set_query_var( 'ps_coa_view', 'compound' );
			set_query_var( 'ps_compound_slug', $slug );
			set_query_var( 'ps_batch_slug', '' );
			$router = $this->fresh_router();
			$router->resolve();
			$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $router );
			$expected = $display . ' COAs & Batch History | ' . get_bloginfo( 'name' );
			$title = $loader->filter_title( 'Theme title' );
			$this->assertSame( $expected, $title, $slug );
			$this->assertSame( $expected, $loader->filter_title( 'Theme title' ), $slug );
			$this->assertStringContainsString( 'for ' . $display . '.', $loader->filter_description( 'Theme description' ), $slug );
			$graph = $loader->filter_schema_graph( array() );
			$this->assertSame( $expected, $graph[0]['name'], $slug );
			$this->assertSame( $display, $graph[1]['itemListElement'][2]['name'], $slug );
			$this->assertStringNotContainsString( $strength . ' mg', $title, $slug );
			$titles[ $slug ] = $title;
		}
		$this->assertNotSame( $titles['3g-rt-20'], $titles['3g-rt-30'] );
		$this->assertSame( '3G-RT 20 COAs & Batch History | ' . get_bloginfo( 'name' ), $titles['3g-rt-20'] );
		$this->assertSame( '3G-RT 30 COAs & Batch History | ' . get_bloginfo( 'name' ), $titles['3g-rt-30'] );
		$this->assertSame( '20', get_post_meta( 885, 'strength_value', true ) );
		$this->assertSame( '3G-RT 20', get_post_meta( 885, 'display_name', true ) );
		$this->assertSame( '3G-RT', get_post_meta( 885, 'compound_name', true ) );
	}

	public function test_nad_cjc_and_description_only_listings_keep_the_prior_name() {
		$kept = array(
			array( 972, 'ghk-cu-50-mg', 'GHK-Cu 50', 'GHK-Cu', '50' ),
			array( 1686, 'kpv-10-mg', 'KPV 10', 'KPV', '10' ),
			array( 1901, 'selank-10-mg', 'Selank 10', 'Selank', '10' ),
			array( 1904, 'semax-10-mg', 'Semax 10', 'Semax', '10' ),
			array( 970, 'bpc-157-10-mg', 'BPC-157 10', 'BPC-157', '10' ),
			array( 979, 'tb-500-10-mg', 'TB-500 10', 'TB-500', '10' ),
			array( 1688, 'mots-c-10-mg', 'MOTS-c 10', 'MOTS-c', '10' ),
			array( 988, 'glutathione-600-mg', 'Glutathione 600', 'Glutathione', '600' ),
			array( 1501, 'nad-500-mg', 'NAD+ 500 mg', 'NAD+', '500' ),
			array( 2318, 'cjc-1295-no-dac-ipamorelin', 'CJC-1295 no DAC / Ipamorelin', 'CJC-1295/Ipamorelin', '10' ),
		);
		foreach ( $kept as $case ) {
			list( $id, $slug, $display, $base, $strength ) = $case;
			$this->assertNotContains( $id, PepSelect\COAArchive\Frontend_Redirects::approved_rename_ids() );
			$this->compound_at( $id, $slug );
			update_post_meta( $id, 'display_name', $display );
			update_post_meta( $id, 'compound_name', $base );
			update_post_meta( $id, 'short_name', $base );
			update_post_meta( $id, 'strength_value', $strength );
			update_post_meta( $id, 'strength_unit', 'mg' );
			$this->approved( $id, 'KEEP-' . $id, 'keep-' . $id );
			$model = $this->view_model->compound( get_post( $id ) );
			$this->assertSame( $base, $model['public_name'], $slug );
			$this->assertSame( $base, $model['listing_name'], $slug );
			$this->assertNotSame( $display, $model['listing_name'], $slug );
			set_query_var( 'ps_coa_view', 'compound' );
			set_query_var( 'ps_compound_slug', $slug );
			set_query_var( 'ps_batch_slug', '' );
			$router = $this->fresh_router();
			$router->resolve();
			$loader = new PepSelect\COAArchive\Frontend_Template_Loader( $router );
			$expected = $base . ' COAs & Batch History | ' . get_bloginfo( 'name' );
			$this->assertSame( $expected, $loader->filter_title( 'Theme title' ), $slug );
			$this->assertStringContainsString( 'for ' . $base . '.', $loader->filter_description( 'Theme description' ), $slug );
			$graph = $loader->filter_schema_graph( array() );
			$this->assertSame( $expected, $graph[0]['name'], $slug );
			$this->assertSame( $base, $graph[1]['itemListElement'][2]['name'], $slug );
			$this->assertSame( $display, get_post_meta( $id, 'display_name', true ), $slug );
			$this->assertSame( $strength, get_post_meta( $id, 'strength_value', true ), $slug );
		}

		update_post_meta( $this->tests_for( 1501 ), PepSelect\COAArchive\COA_Test_Service::ISSUED_DISPLAY_NAME, 'NAD+ 500 mg' );
		update_post_meta( $this->tests_for( 1501 ), PepSelect\COAArchive\COA_Test_Service::ISSUED_COMPOUND_NAME, 'NAD+ Historical' );
		$nad = $this->router->build_report( 'nad-500-mg', 'keep-1501' );
		$this->assertSame( 'NAD+ Historical', $nad['compound']['listing_name'] );
		$this->assertSame( 'NAD+ Historical', $nad['compound']['public_name'] );
		$this->assertArrayNotHasKey( 'issued_display_name', $nad['compound'] );
		$this->assertSame( 'NAD+', get_post_meta( 1501, 'compound_name', true ) );

		$this->compound_at( 885, '3g-rt-20' );
		update_post_meta( 885, 'display_name', '3G-RT 20' );
		update_post_meta( 885, 'compound_name', '3G-RT' );
		update_post_meta( 885, 'strength_value', '20' );
		update_post_meta( 885, 'strength_unit', 'mg' );
		$issued = $this->approved( 885, 'RT2026233GX', 'rt2026233gx' );
		update_post_meta( $issued, PepSelect\COAArchive\COA_Test_Service::ISSUED_DISPLAY_NAME, 'Retatrutide 20mg' );
		update_post_meta( $issued, PepSelect\COAArchive\COA_Test_Service::ISSUED_COMPOUND_NAME, 'Retatrutide' );
		$renamed = $this->router->build_report( '3g-rt-20', 'rt2026233gx' );
		$this->assertSame( '3G-RT 20', $renamed['compound']['listing_name'] );
		$this->assertSame( '3G-RT', $renamed['compound']['public_name'] );
		$this->assertSame( 'Retatrutide 20mg', $renamed['compound']['issued_display_name'] );
		$this->assertSame( '20', get_post_meta( 885, 'strength_value', true ) );
		$this->assertSame( '3G-RT 20', get_post_meta( 885, 'display_name', true ) );

		add_filter(
			'pepselect_coa_compound_slug_aliases',
			static function ( $aliases ) {
				$aliases['nad-500-mg'] = 1501;
				return $aliases;
			}
		);
		$filtered = $this->view_model->compound( get_post( 1501 ) );
		$this->assertSame( 'NAD+', $filtered['listing_name'] );
	}

	private function tests_for( $compound_id ) {
		$ids = get_posts(
			array(
				'post_type' => 'ps_coa_test',
				'post_status' => 'publish',
				'posts_per_page' => 1,
				'fields' => 'ids',
				'meta_key' => 'compound_id',
				'meta_value' => $compound_id,
			)
		);
		$this->assertNotEmpty( $ids );
		return (int) $ids[0];
	}

	private function compound( $slug ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ps_compound',
				'post_status' => 'publish',
				'post_title'  => $slug,
				'post_name'   => $slug,
			)
		);
		update_post_meta( $id, 'is_active', 1 );
		update_post_meta( $id, 'display_name', $slug );
		return $id;
	}

	private function approved( $compound, $batch, $slug ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ps_coa_test',
				'post_status' => 'publish',
				'post_title'  => $batch,
				'post_name'   => $slug,
			)
		);
		foreach ( array(
			'compound_id'    => $compound,
			'workflow_stage' => 'complete',
			'coa_status'     => 'approved',
			'batch_number'   => $batch,
			'test_date'      => '20260701',
			'testing_lab'    => 'ils-labs',
			'vials_tested'   => 1,
		) as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	private function pending_submitted( $compound, $batch ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ps_coa_test',
				'post_status' => 'publish',
				'post_title'  => $batch,
				'post_name'   => 'glp-1-s-' . strtolower( $batch ),
			)
		);
		foreach ( array(
			'compound_id'       => $compound,
			'workflow_stage'    => 'submitted-to-lab',
			'coa_status'        => 'pending',
			'batch_number'      => $batch,
			'expected_coa_date' => '20261110',
			'is_current'        => 0,
		) as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	private function compound_at( $id, $slug ) {
		$created = wp_insert_post(
			array(
				'import_id' => $id,
				'post_type' => 'ps_compound',
				'post_status' => 'publish',
				'post_title' => $slug,
				'post_name' => $slug,
			),
			true
		);
		$this->assertSame( $id, $created );
		update_post_meta( $id, 'is_active', 1 );
		update_post_meta( $id, 'display_name', $slug );
		update_post_meta( $id, 'strength_value', '10' );
		update_post_meta( $id, 'strength_unit', 'mg' );
		return $id;
	}

	private function progress_at( $id, $compound, $batch ) {
		$created = wp_insert_post(
			array(
				'import_id' => $id,
				'post_type' => 'ps_coa_test',
				'post_status' => 'publish',
				'post_title' => 'Progress ' . $id,
				'post_name' => 'lot-' . $id,
			),
			true
		);
		$this->assertSame( $id, $created );
		foreach ( array(
			'compound_id' => $compound,
			'workflow_stage' => 'submitted-to-lab',
			'coa_status' => 'pending',
			'batch_number' => $batch,
			'expected_coa_date' => '20261110',
			'is_current' => 0,
		) as $key => $value ) {
			update_post_meta( $id, $key, $value );
		}
		return $id;
	}

	private function testing_url( $compound_slug, $batch_slug = '' ) {
		$path = 'testing/' . $compound_slug;
		if ( '' !== $batch_slug ) {
			$path .= '/' . $batch_slug;
		}
		return home_url( user_trailingslashit( $path ) );
	}

	private function fresh_router() {
		$visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$compounds = new PepSelect\COAArchive\Compound_Repository( $visibility );
		$tests = new PepSelect\COAArchive\COA_Test_Repository( $visibility );
		return new PepSelect\COAArchive\Frontend_Router( new PepSelect\COAArchive\Frontend_Query(), $compounds, $tests, new PepSelect\COAArchive\Frontend_View_Model() );
	}
}

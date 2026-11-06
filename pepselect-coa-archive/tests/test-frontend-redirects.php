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
		$this->assertStringContainsString( "add_action( 'wp', array( \$this, 'maybe_redirect' ), 9 )", $self );
		$this->assertStringContainsString( 'wp_safe_redirect( $target, 301 )', $self );
		$this->assertStringNotContainsString( 'wp_safe_redirect', file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-router.php' ) );
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
}

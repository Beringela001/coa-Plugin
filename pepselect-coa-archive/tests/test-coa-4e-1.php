<?php
/** COA-4E.1 exact Full-QC visual bar and regression coverage. */
class PepSelect_COA_Archive_COA_4E_1_Test extends WP_UnitTestCase {
	private $view;

	public function set_up() {
		parent::set_up();
		do_action( 'init' );
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'administrator' ) ) );
		$this->view = new PepSelect\COAArchive\Frontend_View_Model();
	}

	public function test_01_bar_renders_for_approved_complete_report() { $model = $this->report( $this->fixture() ); $this->assertTrue( $model['is_full_qc_documented'] ); $this->assertNotEmpty( $model['qc_strip_rows'] ); $this->assertStringContainsString( "\$test['show_qc_strip']", $this->template( 'single-coa-report.php' ) ); }
	public function test_02_bar_renders_directly_after_summary_metrics() { $source = $this->template( 'single-coa-report.php' ); $metrics = strpos( $source, 'partials/report-summary-metrics.php' ); $strip = strpos( $source, 'partials/full-qc-status-strip.php' ); $this->assertGreaterThan( $metrics, $strip ); }
	public function test_03_bar_renders_before_independent_laboratory_data() { $source = $this->template( 'single-coa-report.php' ); $this->assertGreaterThan( strpos( $source, 'partials/full-qc-status-strip.php' ), strpos( $source, 'ps-coa-laboratory-data' ) ); }
	public function test_04_bar_renders_only_once() { $this->assertSame( 1, substr_count( $this->template( 'single-coa-report.php' ), 'partials/full-qc-status-strip.php' ) ); }
	public function test_05_identity_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'identity' ); $this->assertSame( 'Identity', $row['short_label'] ); $this->assertSame( 'LC-MS · Confirmed', $row['detail'] ); }
	public function test_06_purity_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'purity' ); $this->assertSame( 'Purity', $row['short_label'] ); $this->assertSame( 'RP-HPLC · 99.79%', $row['detail'] ); }
	public function test_07_net_content_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'net-content' ); $this->assertSame( 'Net Content', $row['short_label'] ); $this->assertSame( '31.01 mg avg', $row['detail'] ); }
	public function test_08_heavy_metals_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'heavy-metals' ); $this->assertSame( 'Heavy Metals', $row['short_label'] ); $this->assertSame( 'Below limits', $row['detail'] ); }
	public function test_09_sterility_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'sterility' ); $this->assertSame( 'Sterility', $row['short_label'] ); $this->assertSame( 'No growth', $row['detail'] ); }
	public function test_10_endotoxins_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'endotoxins' ); $this->assertSame( 'Endotoxins', $row['short_label'] ); $this->assertSame( '<0.05 EU/mg', $row['detail'] ); }
	public function test_11_fentanyl_screen_renders_when_saved() { $row = $this->row( $this->report( $this->fixture() ), 'fentanyl' ); $this->assertSame( 'Fentanyl Screen', $row['short_label'] ); $this->assertSame( 'Not detected', $row['detail'] ); }
	public function test_12_missing_categories_remain_neutral() { $ids = $this->fixture(); delete_post_meta( $ids['test'], 'identity_status' ); delete_post_meta( $ids['test'], 'identity_method' ); $row = $this->row( $this->report( $ids ), 'identity' ); $this->assertSame( '--', $row['detail'] ); $this->assertFalse( $row['reported'] ); $this->assertFalse( $row['status']['success'] ); }
	public function test_13_failed_categories_do_not_receive_success() { $ids = $this->fixture( array( 'fentanyl_status' => 'fail', 'fentanyl_result' => 'Detected' ) ); $this->assertFalse( $this->row( $this->report( $ids ), 'fentanyl' )['status']['success'] ); }
	public function test_14_unsupported_legacy_status_is_neutral() { $ids = $this->fixture( array( 'fentanyl_status' => 'pending' ) ); $row = $this->row( $this->report( $ids ), 'fentanyl' ); $this->assertSame( '--', $row['detail'] ); $this->assertFalse( $row['reported'] ); $this->assertFalse( $row['status']['success'] ); }
	public function test_15_not_tested_categories_do_not_receive_success() { $ids = $this->fixture( array( 'fentanyl_status' => 'not-tested', 'fentanyl_result' => 'Not tested' ) ); $row = $this->row( $this->report( $ids ), 'fentanyl' ); $this->assertSame( '--', $row['detail'] ); $this->assertFalse( $row['reported'] ); $this->assertFalse( $row['status']['success'] ); }
	public function test_16_category_count_matches_rendered_categories() { $model = $this->report( $this->fixture() ); $this->assertSame( count( $model['qc_strip_rows'] ), $model['qc_category_count'] ); $this->assertSame( 7, $model['qc_category_count'] ); $this->assertStringContainsString( '%1$d of %2$d categories reported', $this->template( 'partials/full-qc-status-strip.php' ) ); }
	public function test_17_existing_results_table_remains_intact() { $table = $this->template( 'partials/full-qc-results-table.php' ); $this->assertSame( 2, substr_count( $table, "foreach ( \$test['result_rows'] as \$row )" ) ); foreach ( array( 'Test', 'Method', 'Specification', 'Result', 'Status' ) as $heading ) { $this->assertStringContainsString( "'" . $heading . "'", $table ); } }
	public function test_18_existing_certificate_lightbox_remains_intact() { $this->assertFileExists( dirname( __DIR__ ) . '/assets/js/pepselect-coa-lightbox.js' ); $this->assertStringContainsString( 'data-ps-coa-gallery', $this->template( 'partials/certificate-pages.php' ) ); }
	public function test_19_existing_archive_and_history_routes_remain_intact() { $source = file_get_contents( dirname( __DIR__ ) . '/includes/class-rewrites.php' ); $this->assertStringContainsString( 'testing/?$', $source ); $this->assertStringContainsString( 'testing/([^/]+)/?$', $source ); }
	public function test_20_bar_has_exact_two_tier_responsive_structure() { $partial = $this->template( 'partials/full-qc-status-strip.php' ); $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' ); $this->assertStringContainsString( 'ps-coa-qc-strip__header', $partial ); $this->assertStringContainsString( 'ps-coa-qc-strip__categories', $partial ); $this->assertStringContainsString( 'grid-template-columns: repeat(auto-fit, minmax(128px, 1fr))', $css ); $this->assertStringContainsString( '@media (max-width: 390px)', $css ); }

	private function fixture( $overrides = array() ) {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Retatrutide' ) );
		$test = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Batch QC-1' ) );
		$values = array_merge( array(
			'compound_id' => $compound, 'workflow_stage' => 'complete', 'coa_status' => 'approved', 'testing_lab' => 'ils-labs', 'batch_number' => 'QC-1',
			'claimed_content' => '30', 'content_unit' => 'mg', 'vials_tested' => '3', 'average_net_content' => '31.01', 'minimum_net_content' => '30.82', 'maximum_net_content' => '31.22',
			'purity_percentage' => '99.79', 'purity_status' => 'pass', 'purity_method' => 'RP-HPLC', 'identity_status' => 'pass', 'identity_method' => 'LC-MS',
			'heavy_metals_status' => 'pass', 'heavy_metals_summary' => 'Below limits', 'sterility_status' => 'pass', 'sterility_result' => 'No growth',
			'endotoxin_status' => 'pass', 'endotoxin_result' => '<0.05', 'endotoxin_unit' => 'EU/mg', 'fentanyl_status' => 'pass', 'fentanyl_result' => 'Not detected',
			'fentanyl_method' => 'Immunoassay', 'fentanyl_specification' => 'Immunoassay, 50 ng/mL cutoff',
		), $overrides );
		foreach ( $values as $key => $value ) { update_post_meta( $test, $key, $value ); }
		return array( 'compound' => $compound, 'test' => $test );
	}

	private function report( $ids ) { return $this->view->report( get_post( $ids['test'] ), get_post( $ids['compound'] ) ); }
	private function row( $model, $key ) { $rows = array_values( array_filter( $model['qc_strip_rows'], static function ( $row ) use ( $key ) { return $key === $row['key']; } ) ); $this->assertCount( 1, $rows ); return $rows[0]; }
	private function template( $path ) { return file_get_contents( dirname( __DIR__ ) . '/templates/' . $path ); }
}

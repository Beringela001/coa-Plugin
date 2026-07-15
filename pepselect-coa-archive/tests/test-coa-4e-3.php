<?php
/** COA-4E.3 focused report-state, hero, certificate, and regression coverage. */
class PepSelect_COA_Archive_COA_4E_3_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Frontend_View_Model */
	private $view;

	public function set_up() { parent::set_up(); do_action( 'init' ); $this->view = new PepSelect\COAArchive\Frontend_View_Model(); }

	public function test_full_report_uses_all_seven_success_positions() {
		$model = $this->report( $this->fixture() );
		$this->assertSame( array( 'Identity', 'Purity', 'Net Content', 'Heavy Metals', 'Sterility', 'Endotoxins', 'Fentanyl Screen' ), wp_list_pluck( $model['qc_strip_rows'], 'short_label' ) );
		$this->assertSame( 7, $model['reported_category_count'] );
		$this->assertSame( 7, $model['qc_success_category_count'] );
		$this->assertSame( 'Full-QC Testing Passed', $model['qc_strip_title'] );
	}

	public function test_partial_report_keeps_seven_positions_and_truthful_count() {
		$model = $this->report( $this->fixture( array( 'heavy_metals_status' => 'not-tested', 'heavy_metals_summary' => '', 'sterility_status' => 'not-tested', 'sterility_result' => '', 'endotoxin_status' => 'not-tested', 'endotoxin_result' => '', 'fentanyl_status' => 'not-tested' ) ) );
		$this->assertCount( 7, $model['qc_strip_rows'] );
		$this->assertSame( 3, $model['reported_category_count'] );
		$this->assertSame( 'QC Testing Passed', $model['qc_strip_title'] );
		foreach ( array_slice( $model['qc_strip_rows'], 3 ) as $row ) { $this->assertSame( '--', $row['detail'] ); $this->assertFalse( $row['reported'] ); $this->assertFalse( $row['status']['success'] ); }
		$this->assertSame( 'Fentanyl Screen', $model['qc_strip_rows'][6]['short_label'] );
	}

	public function test_failed_saved_category_never_receives_success_claim() {
		$model = $this->report( $this->fixture( array( 'fentanyl_status' => 'fail' ) ) );
		$row = $model['qc_strip_rows'][6];
		$this->assertSame( 'Detected', $row['detail'] );
		$this->assertTrue( $row['reported'] );
		$this->assertFalse( $row['status']['success'] );
		$this->assertSame( 'QC Testing Results', $model['qc_strip_title'] );
	}

	public function test_blank_purity_result_is_not_inferred_successful() {
		$model = $this->report( $this->fixture( array( 'purity_percentage' => '' ) ) );
		$this->assertSame( '--', $model['qc_strip_rows'][1]['detail'] );
		$this->assertFalse( $model['qc_strip_rows'][1]['status']['success'] );
		$this->assertSame( 6, $model['reported_category_count'] );
	}

	public function test_qc_template_uses_dynamic_title_and_seven_denominator() {
		$template = $this->template( 'partials/full-qc-status-strip.php' );
		$this->assertStringContainsString( "\$test['qc_strip_title']", $template );
		$this->assertStringContainsString( '$total_categories', $template );
		$this->assertStringContainsString( "\$row['reported']", file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' ) );
	}

	public function test_hero_metadata_has_exact_two_row_source_order() {
		$template = $this->template( 'partials/batch-identity-meta.php' );
		$labels = array( 'COA reference', 'Report date', 'Cap', 'Certificate version', 'Batch status', 'Crimp' );
		$positions = array_map( static function ( $label ) use ( $template ) { return strpos( $template, "'" . $label . "'" ); }, $labels );
		$this->assertSame( $positions, $sorted = ( function ( $values ) { sort( $values ); return $values; } )( $positions ) );
		$this->assertStringContainsString( "__( 'Current'", $template );
		$this->assertStringContainsString( "__( 'Past'", $template );
		$this->assertStringNotContainsString( 'Current batch', $template );
	}

	public function test_certificate_section_has_exact_header_and_large_card_markup() {
		$template = $this->template( 'partials/certificate-pages.php' );
		foreach ( array( 'Original document', 'Certificate pages', 'Click any page for full-screen review', 'data-ps-coa-certificate-gallery', 'data-ps-coa-attachment-id', 'ps-coa-certificate__preview', 'ps-coa-certificate__meta' ) as $needle ) { $this->assertStringContainsString( $needle, $template ); }
	}

	public function test_certificate_template_preserves_order_and_real_captions() {
		$test = array( 'page_images' => array(
			array( 'attachment_id' => 11, 'full_url' => 'https://example.org/one.jpg', 'thumbnail_url' => 'https://example.org/one-small.jpg', 'srcset' => '', 'alt' => 'One', 'caption' => 'First saved caption' ),
			array( 'attachment_id' => 22, 'full_url' => 'https://example.org/two.jpg', 'thumbnail_url' => 'https://example.org/two-small.jpg', 'srcset' => '', 'alt' => 'Two', 'caption' => 'Second saved caption' ),
		) );
		ob_start(); include dirname( __DIR__ ) . '/templates/partials/certificate-pages.php'; $html = ob_get_clean();
		$this->assertSame( 2, substr_count( $html, 'data-ps-coa-full=' ) );
		$this->assertLessThan( strpos( $html, 'Second saved caption' ), strpos( $html, 'First saved caption' ) );
	}

	public function test_lightbox_markup_and_enqueue_are_scoped_to_certificate_pages() {
		$report = $this->template( 'single-coa-report.php' );
		$lightbox = $this->template( 'partials/gallery-lightbox.php' );
		$loader = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-template-loader.php' );
		$this->assertSame( 1, substr_count( $report, 'partials/gallery-lightbox.php' ) );
		foreach ( array( 'data-ps-coa-count', 'data-ps-coa-close', 'data-ps-coa-prev', 'data-ps-coa-next', 'aria-modal="true"' ) as $needle ) { $this->assertStringContainsString( $needle, $lightbox ); }
		$this->assertStringContainsString( "! empty( \$context['test']['page_images'] )", $loader );
		$this->assertStringNotContainsString( "|| ! empty( \$context['test']['batch_identity_photos'] )", $loader );
	}

	public function test_lightbox_css_is_uncropped_and_responsive() {
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' );
		foreach ( array( 'max-width: calc(100vw - 160px)', 'max-height: calc(100vh - 80px)', 'object-fit: contain', 'grid-template-columns: repeat(2, minmax(0, 1fr))', '@media (max-width: 620px)' ) as $needle ) { $this->assertStringContainsString( $needle, $css ); }
	}

	public function test_existing_table_source_panel_routes_and_scope_remain_intact() {
		$this->assertStringContainsString( "foreach ( \$test['result_rows'] as \$row )", $this->template( 'partials/full-qc-results-table.php' ) );
		$this->assertStringContainsString( 'ps-coa-laboratory-panel', $this->template( 'partials/laboratory-report-panel.php' ) );
		$rewrites = file_get_contents( dirname( __DIR__ ) . '/includes/class-rewrites.php' );
		$this->assertStringContainsString( 'testing/?$', $rewrites ); $this->assertStringContainsString( 'testing/([^/]+)/?$', $rewrites );
		$php = implode( '', array_map( 'file_get_contents', glob( dirname( __DIR__ ) . '/includes/*.php' ) ) );
		$this->assertStringNotContainsString( 'woocommerce_single_product', $php ); $this->assertStringNotContainsString( 'qrcode', strtolower( $php ) );
	}

	private function fixture( $overrides = array() ) {
		$compound = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Retatrutide' ) );
		$test = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Batch QC-3' ) );
		$values = array_merge( array( 'compound_id' => $compound, 'workflow_stage' => 'complete', 'coa_status' => 'approved', 'is_current' => 1, 'batch_number' => 'QC-3', 'testing_lab' => 'ils-labs', 'claimed_content' => '30', 'content_unit' => 'mg', 'average_net_content' => '30', 'purity_percentage' => '99.9', 'purity_status' => 'pass', 'purity_method' => 'HPLC', 'identity_status' => 'pass', 'identity_method' => 'LC-MS', 'heavy_metals_status' => 'pass', 'heavy_metals_summary' => 'Below limits', 'sterility_status' => 'pass', 'sterility_result' => 'No growth', 'endotoxin_status' => 'pass', 'endotoxin_result' => '<0.05', 'endotoxin_unit' => 'EU/mL', 'fentanyl_status' => 'pass' ), $overrides );
		foreach ( $values as $key => $value ) { update_post_meta( $test, $key, $value ); }
		return array( 'compound' => $compound, 'test' => $test );
	}
	private function report( $ids ) { return $this->view->report( get_post( $ids['test'] ), get_post( $ids['compound'] ) ); }
	private function template( $path ) { return file_get_contents( dirname( __DIR__ ) . '/templates/' . $path ); }
}

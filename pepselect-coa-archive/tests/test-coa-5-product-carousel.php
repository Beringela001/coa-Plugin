<?php
/** Focused COA-5 WooCommerce product-page carousel coverage. */
class PepSelect_COA_5_Product_Carousel_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Product_Matching */ private $matching;
	/** @var PepSelect\COAArchive\Compound_Repository */ private $compounds;
	/** @var PepSelect\COAArchive\COA_Test_Repository */ private $tests;
	/** @var PepSelect\COAArchive\Frontend_View_Model */ private $view_model;

	public function set_up() {
		parent::set_up(); do_action( 'init' );
		if ( ! post_type_exists( 'product' ) ) { register_post_type( 'product', array( 'public' => true, 'supports' => array( 'title', 'thumbnail' ) ) ); }
		$visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->matching = new PepSelect\COAArchive\Product_Matching( new PepSelect\COAArchive\Dependencies(), true );
		$this->compounds = new PepSelect\COAArchive\Compound_Repository( $visibility );
		$this->tests = new PepSelect\COAArchive\COA_Test_Repository( $visibility );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
		wp_dequeue_style( 'pepselect-coa-product-carousel' ); wp_dequeue_script( 'pepselect-coa-product-carousel' );
		delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache();
	}

	public function tear_down() { delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache(); parent::tear_down(); }

	public function test_current_product_and_optional_product_id_resolve_only_the_exact_relationship() {
		$product = $this->product( 'GLP-3 R', 'GLP3R30' ); $connected = $this->compound( 'Retatrutide 30 mg', $product ); $this->record( $connected, 'RT30-B', '2026-07-10' );
		$lookalike_product = $this->product( 'GLP-3 R', 'GLP3R30-LOOKALIKE' ); $lookalike = $this->compound( 'Retatrutide 30 mg lookalike', 0 ); update_post_meta( $lookalike, PepSelect\COAArchive\Product_Matching::SKU_SNAPSHOT_META, 'GLP3R30' ); $this->record( $lookalike, 'WRONG-BATCH', '2026-07-11' );
		$this->go_to( get_permalink( $product ) );
		$html = $this->carousel()->shortcode();
		$this->assertStringContainsString( 'RT30-B', $html ); $this->assertStringNotContainsString( 'WRONG-BATCH', $html );
		$this->go_to( get_permalink( $lookalike_product ) );
		$this->assertSame( '', $this->carousel()->shortcode() );
		$this->assertStringContainsString( 'RT30-B', $this->carousel()->shortcode( array( 'product_id' => $product ) ) );
	}

	public function test_unconnected_invalid_and_non_product_contexts_render_nothing() {
		$product = $this->product( 'Unconnected', 'NONE' );
		$this->go_to( get_permalink( $product ) ); $carousel = $this->carousel();
		$this->assertSame( '', $carousel->shortcode() );
		$this->assertFalse( wp_style_is( 'pepselect-coa-product-carousel', 'enqueued' ) );
		$page = self::factory()->post->create( array( 'post_status' => 'publish' ) ); $this->go_to( get_permalink( $page ) );
		$this->assertSame( '', $this->carousel()->shortcode( array( 'product_id' => $product ) ) );
	}

	public function test_selection_excludes_nonpublic_nonapproved_failed_and_other_strength_records() {
		$product = $this->product( 'GLP-3 R', 'GLP3R30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product );
		$valid = $this->record( $compound, 'VALID', '2026-07-10' );
		$this->record( $compound, 'PENDING', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'in-testing' ) );
		$this->record( $compound, 'SUBMITTED', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'submitted-to-lab' ) );
		$this->record( $compound, 'WAITING', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor' ) );
		$this->record( $compound, 'VENDOR', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'vendor-vetting' ) );
		$this->record( $compound, 'FAILED', '2026-07-12', array( 'coa_status' => 'failed' ) );
		$this->record( $compound, 'DRAFT', '2026-07-13', array( 'post_status' => 'draft' ) );
		$this->record( $compound, 'PRIVATE', '2026-07-14', array( 'post_status' => 'private' ) );
		$this->record( $compound, 'HIDDEN', '2026-07-15', array( 'private' => 1 ) );
		$other = $this->compound( 'Retatrutide 20 mg', $this->product( 'GLP-3 R 20', 'GLP3R20' ) ); $this->record( $other, 'WRONG-STRENGTH', '2026-07-16' );
		$this->assertSame( array( $valid ), wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' ) );
	}

	public function test_latest_six_use_test_date_publication_date_and_id_tie_breakers() {
		$product = $this->product( 'Retatrutide', 'RETA30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product ); $ids = array();
		for ( $day = 1; $day <= 7; $day++ ) { $ids[ $day ] = $this->record( $compound, 'B-' . $day, sprintf( '2026-07-%02d', $day ) ); }
		$ordered = wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' );
		$this->assertSame( array( $ids[7], $ids[6], $ids[5], $ids[4], $ids[3], $ids[2], $ids[1] ), $ordered );
		$this->go_to( get_permalink( $product ) ); $html = $this->carousel()->shortcode();
		$this->assertSame( 6, substr_count( $html, 'ps-coa-product-carousel__card' ) ); $this->assertStringNotContainsString( 'B-1', $html );
		$older_publication = $this->record( $compound, 'TIE-OLD', '2026-08-01', array( 'post_date' => '2026-07-01 10:00:00' ) );
		$newer_publication = $this->record( $compound, 'TIE-NEW', '2026-08-01', array( 'post_date' => '2026-07-02 10:00:00' ) );
		$same_publication_newer_id = $this->record( $compound, 'TIE-ID', '2026-08-01', array( 'post_date' => '2026-07-02 10:00:00' ) );
		$tied = wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' );
		$this->assertLessThan( array_search( $newer_publication, $tied, true ), array_search( $same_publication_newer_id, $tied, true ) );
		$this->assertLessThan( array_search( $older_publication, $tied, true ), array_search( $newer_publication, $tied, true ) );
	}

	public function test_truthful_status_projection_and_failed_category_exclusion() {
		$compound = get_post( $this->compound( 'Retatrutide 30 mg', $this->product( 'Retatrutide', 'RETA30' ) ) );
		$full = get_post( $this->record( $compound->ID, 'FULL', '2026-07-10', array( 'full_qc' => true ) ) );
		$partial = get_post( $this->record( $compound->ID, 'PARTIAL', '2026-07-09' ) );
		$reported = get_post( $this->record( $compound->ID, 'REPORTED', '2026-07-08', array( 'purity_status' => 'reported' ) ) );
		$failed = get_post( $this->record( $compound->ID, 'CATEGORY-FAIL', '2026-07-07', array( 'purity_status' => 'fail' ) ) );
		$full_model = $this->view_model->product_carousel_report( $full, $compound ); $partial_model = $this->view_model->product_carousel_report( $partial, $compound ); $reported_model = $this->view_model->product_carousel_report( $reported, $compound );
		$this->assertTrue( $full_model['is_fully_vetted'] ); $this->assertSame( 'Fully Vetted', $full_model['status_label'] );
		$this->assertTrue( $partial_model['is_qc_passed'] ); $this->assertSame( 'QC Passed', $partial_model['status_label'] ); $this->assertFalse( $partial_model['is_fully_vetted'] );
		$this->assertFalse( $reported_model['is_qc_passed'] ); $this->assertSame( 'Report Published', $reported_model['status_label'] );
		$this->assertSame( array(), $this->view_model->product_carousel_report( $failed, $compound ) );
	}

	public function test_pending_not_tested_and_missing_purity_never_fabricate_pass_or_value() {
		$compound = get_post( $this->compound( 'Compound', $this->product( 'Compound', 'CMP10' ) ) );
		$test = get_post( $this->record( $compound->ID, 'NO-PURITY', '2026-07-10', array( 'purity_percentage' => '', 'purity_status' => 'pending', 'identity_status' => 'not-tested' ) ) );
		$model = $this->view_model->product_carousel_report( $test, $compound );
		$this->assertFalse( $model['purity_reported'] ); $this->assertSame( '', $model['purity_percentage_display'] );
		$this->assertFalse( $model['is_qc_passed'] ); $this->assertFalse( $model['is_fully_vetted'] );
	}

	public function test_shortcode_output_is_accessible_escaped_deduplicated_and_loads_assets_only_after_output() {
		$product = $this->product( 'Retatrutide', 'RETA30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product ); $this->record( $compound, '<Batch & One>', '2026-07-10' );
		$this->go_to( get_permalink( $product ) ); $carousel = $this->carousel(); $html = $carousel->shortcode();
		foreach ( array( 'Independent Testing History', 'role="region"', 'aria-roledescription="carousel"', 'aria-label=', '<button', 'ps-coa-product-carousel__card', 'Latest Report', 'View full batch report' ) as $needle ) { $this->assertStringContainsString( $needle, $html ); }
		$this->assertStringContainsString( '&lt;Batch &amp; One&gt;', $html ); $this->assertSame( 1, substr_count( $html, 'Latest Report' ) );
		$this->assertStringNotContainsString( 'lab_report_url', $html ); $this->assertStringNotContainsString( '.pdf', strtolower( $html ) ); $this->assertStringNotContainsString( 'qr', strtolower( $html ) );
		$this->assertTrue( wp_style_is( 'pepselect-coa-product-carousel', 'enqueued' ) ); $this->assertTrue( wp_script_is( 'pepselect-coa-product-carousel', 'enqueued' ) );
		$this->assertSame( '', $carousel->shortcode() );
	}

	private function carousel() { return new PepSelect\COAArchive\Product_COA_Carousel( $this->matching, $this->compounds, $this->tests, $this->view_model ); }
	private function product( $title, $sku ) { $id = self::factory()->post->create( array( 'post_type' => 'product', 'post_status' => 'publish', 'post_title' => $title ) ); update_post_meta( $id, '_sku', $sku ); return $id; }
	private function compound( $title, $product_id ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $title ) ); update_post_meta( $id, 'display_name', $title ); update_post_meta( $id, 'is_active', 1 ); if ( $product_id ) { update_post_meta( $id, PepSelect\COAArchive\Product_Matching::PRODUCT_ID_META, $product_id ); } return $id; }
	private function record( $compound_id, $batch, $date, $args = array() ) {
		$args = wp_parse_args( $args, array( 'post_status' => 'publish', 'post_date' => $date . ' 12:00:00', 'coa_status' => 'approved', 'workflow_stage' => 'complete', 'private' => 0, 'purity_percentage' => '99.7899', 'purity_status' => 'pass', 'identity_status' => 'not-tested', 'full_qc' => false ) );
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => $args['post_status'], 'post_title' => $batch, 'post_date' => $args['post_date'] ) );
		$meta = array( 'compound_id' => $compound_id, 'batch_number' => $batch, 'test_date' => $date, 'coa_status' => $args['coa_status'], 'workflow_stage' => $args['workflow_stage'], 'testing_lab' => 'ils-labs', 'vials_tested' => 3, '_ps_coa_private' => $args['private'], 'purity_percentage' => $args['purity_percentage'], 'purity_status' => $args['purity_status'], 'identity_status' => $args['identity_status'], 'endotoxin_status' => 'not-tested', 'heavy_metals_status' => 'not-tested', 'sterility_status' => 'not-tested', 'fentanyl_status' => 'not-tested' );
		if ( $args['full_qc'] ) { $meta = array_merge( $meta, array( 'identity_status' => 'pass', 'average_net_content' => '30.84', 'heavy_metals_status' => 'pass', 'heavy_metals_summary' => 'Below limits', 'sterility_status' => 'pass', 'sterility_result' => 'No growth', 'endotoxin_status' => 'pass', 'endotoxin_result' => '< 0.05', 'endotoxin_unit' => 'EU/mL', 'fentanyl_status' => 'pass' ) ); }
		foreach ( $meta as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

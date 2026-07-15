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
		$inactive_product = $this->product( 'Inactive', 'INACTIVE' ); $inactive = $this->compound( 'Inactive 10 mg', $inactive_product ); update_post_meta( $inactive, 'is_active', 0 ); $this->record( $inactive, 'INACTIVE-BATCH', '2026-07-10' );
		$this->go_to( get_permalink( $inactive_product ) ); $this->assertSame( '', $this->carousel()->shortcode() );
	}

	public function test_selection_excludes_nonpublic_nonapproved_failed_and_other_strength_records() {
		$product = $this->product( 'GLP-3 R', 'GLP3R30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product );
		$valid = $this->record( $compound, 'VALID', '2026-07-10' );
		$this->record( $compound, 'PENDING', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'in-testing' ) );
		$this->record( $compound, 'SUBMITTED', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'submitted-to-lab' ) );
		$this->record( $compound, 'WAITING', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor' ) );
		$this->record( $compound, 'VENDOR', '2026-07-11', array( 'coa_status' => 'pending', 'workflow_stage' => 'vendor-vetting' ) );
		$failed = $this->record( $compound, 'FAILED', '2026-07-12', array( 'coa_status' => 'failed', 'is_current' => 1 ) );
		$this->record( $compound, 'DRAFT', '2026-07-13', array( 'post_status' => 'draft' ) );
		$this->record( $compound, 'PRIVATE', '2026-07-14', array( 'post_status' => 'private' ) );
		$this->record( $compound, 'HIDDEN', '2026-07-15', array( 'private' => 1 ) );
		$other = $this->compound( 'Retatrutide 20 mg', $this->product( 'GLP-3 R 20', 'GLP3R20' ) ); $this->record( $other, 'WRONG-STRENGTH', '2026-07-16' );
		$this->assertSame( array( $valid ), wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' ) );
		$this->assertSame( array( $failed ), wp_list_pluck( $this->tests->classified_for_compound( $compound )['failed'], 'ID' ) );
	}

	public function test_explicit_current_leads_and_only_four_documented_cards_render() {
		$product = $this->product( 'Retatrutide', 'RETA30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product ); $ids = array();
		for ( $day = 1; $day <= 7; $day++ ) { $ids[ $day ] = $this->record( $compound, 'B-' . $day, sprintf( '2026-07-%02d', $day ) ); }
		update_post_meta( $ids[2], 'is_current', 1 );
		$ordered = wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' );
		$this->assertSame( $ids[2], $ordered[0] );
		$this->go_to( get_permalink( $product ) ); $html = $this->carousel()->shortcode();
		$this->assertSame( 4, substr_count( $html, 'ps-coa-product-carousel__card ' ) );
		$this->assertSame( 1, substr_count( $html, '>Current Batch<' ) ); $this->assertSame( 3, substr_count( $html, '>Previous Report<' ) );
		$this->assertStringContainsString( 'ps-coa-product-carousel__card--current', $html ); $this->assertStringContainsString( 'ps-coa-product-carousel__card--previous', $html );
		$this->assertLessThan( strpos( $html, 'B-7' ), strpos( $html, 'B-2' ) ); $this->assertStringNotContainsString( 'B-4', $html );
	}

	public function test_latest_fallback_uses_documented_tie_breakers_without_claiming_current() {
		$product = $this->product( 'Retatrutide', 'RETA30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product );
		$older_publication = $this->record( $compound, 'TIE-OLD', '2026-08-01', array( 'post_date' => '2026-07-01 10:00:00' ) );
		$newer_publication = $this->record( $compound, 'TIE-NEW', '2026-08-01', array( 'post_date' => '2026-07-02 10:00:00' ) );
		$same_publication_newer_id = $this->record( $compound, 'TIE-ID', '2026-08-01', array( 'post_date' => '2026-07-02 10:00:00' ) );
		$tied = wp_list_pluck( $this->tests->approved_for_product_carousel( $compound ), 'ID' );
		$this->assertLessThan( array_search( $newer_publication, $tied, true ), array_search( $same_publication_newer_id, $tied, true ) );
		$this->assertLessThan( array_search( $older_publication, $tied, true ), array_search( $newer_publication, $tied, true ) );
		$this->go_to( get_permalink( $product ) ); $html = $this->carousel()->shortcode();
		$this->assertStringContainsString( 'Latest Report', $html ); $this->assertStringNotContainsString( 'Current Batch', $html );
		$this->assertLessThan( strpos( $html, 'TIE-NEW' ), strpos( $html, 'TIE-ID' ) );
	}

	public function test_one_most_advanced_incoming_card_renders_second_with_privacy_safe_data() {
		$product = $this->product( 'Retatrutide', 'RETA30' ); $compound = $this->compound( 'Retatrutide 30 mg', $product );
		$current = $this->record( $compound, 'CURRENT', '2026-07-10', array( 'is_current' => 1 ) );
		$this->record( $compound, 'PREVIOUS', '2026-07-01' );
		$this->record( $compound, 'VENDOR-PRIVATE', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'vendor-vetting' ) );
		$this->record( $compound, 'WAITING-PRIVATE', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor', 'expected_coa_date' => '2099-08-01' ) );
		$this->record( $compound, 'SUBMITTED-PRIVATE', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'submitted-to-lab', 'expected_coa_date' => '2099-07-20' ) );
		$testing = $this->record( $compound, 'TESTING-PUBLIC', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'in-testing', 'expected_coa_date' => '2099-07-18' ) );
		$records = $this->tests->for_product_carousel( $compound );
		$this->assertSame( $testing, $records['incoming']->ID ); $this->assertSame( $current, $records['documented'][0]->ID );
		$incoming_model = $this->view_model->product_carousel_incoming( get_post( $testing ), get_post( $compound ) );
		$this->assertSame( 'Verification in Progress', $incoming_model['workflow_stage_label'] );
		$this->assertSame( 'Independent testing is underway.', $incoming_model['supporting_copy'] );
		$this->assertArrayNotHasKey( 'purity_percentage', $incoming_model ); $this->assertSame( $this->view_model->compound_url( get_post( $compound ) ), $incoming_model['detail_url'] );
		$this->go_to( get_permalink( $product ) ); $html = $this->carousel()->shortcode();
		$this->assertSame( 1, substr_count( $html, '>Incoming<' ) ); $this->assertStringContainsString( 'ps-coa-product-carousel__card--incoming', $html );
		$this->assertLessThan( strpos( $html, '>Incoming<' ), strpos( $html, 'Current Batch' ) );
		$this->assertLessThan( strpos( $html, 'Previous Report' ), strpos( $html, '>Incoming<' ) );
		$this->assertStringContainsString( 'VIEW VETTING STATUS', $html );
	}

	public function test_incoming_same_stage_uses_nearest_future_date_then_modified_and_id() {
		$compound = $this->compound( 'Compound', $this->product( 'Compound', 'CMP10' ) );
		$later = $this->record( $compound, 'LATER', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor', 'expected_coa_date' => '2099-09-01' ) );
		$nearest = $this->record( $compound, 'NEAREST', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor', 'expected_coa_date' => '2099-08-01' ) );
		$this->assertSame( $nearest, $this->tests->for_product_carousel( $compound )['incoming']->ID );
		update_post_meta( $later, 'expected_coa_date', '' ); update_post_meta( $nearest, 'expected_coa_date', '' );
		global $wpdb; $wpdb->update( $wpdb->posts, array( 'post_modified' => '2026-07-14 12:00:00', 'post_modified_gmt' => '2026-07-14 12:00:00' ), array( 'ID' => $later ) ); $wpdb->update( $wpdb->posts, array( 'post_modified' => '2026-07-15 12:00:00', 'post_modified_gmt' => '2026-07-15 12:00:00' ), array( 'ID' => $nearest ) ); clean_post_cache( $later ); clean_post_cache( $nearest );
		$this->assertSame( $nearest, $this->tests->for_product_carousel( $compound )['incoming']->ID );
		$wpdb->update( $wpdb->posts, array( 'post_modified' => '2026-07-15 12:00:00', 'post_modified_gmt' => '2026-07-15 12:00:00' ), array( 'ID' => $later ) ); clean_post_cache( $later );
		$this->assertSame( max( $later, $nearest ), $this->tests->for_product_carousel( $compound )['incoming']->ID );
	}

	public function test_incoming_stage_copy_and_field_privacy_follow_the_existing_allowlist() {
		$compound_id = $this->compound( 'Compound', $this->product( 'Compound', 'CMP10' ) ); $compound = get_post( $compound_id );
		$vendor = get_post( $this->record( $compound_id, 'VENDOR-SECRET', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'vendor-vetting', 'expected_coa_date' => '2099-08-01' ) ) );
		$waiting = get_post( $this->record( $compound_id, 'WAITING-SECRET', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'waiting-on-vendor', 'expected_coa_date' => '2099-08-02' ) ) );
		$submitted = get_post( $this->record( $compound_id, 'SUBMITTED-SECRET', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'submitted-to-lab', 'expected_coa_date' => '2099-08-03' ) ) );
		$testing = get_post( $this->record( $compound_id, 'TESTING-PUBLIC', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'in-testing', 'expected_coa_date' => '2099-08-04' ) ) );
		$vendor_model = $this->view_model->product_carousel_incoming( $vendor, $compound ); $waiting_model = $this->view_model->product_carousel_incoming( $waiting, $compound ); $submitted_model = $this->view_model->product_carousel_incoming( $submitted, $compound ); $testing_model = $this->view_model->product_carousel_incoming( $testing, $compound );
		$this->assertSame( 'Vendor Vetting', $vendor_model['workflow_stage_label'] ); $this->assertSame( '', $vendor_model['expected_coa_date_label'] ); $this->assertSame( '', $vendor_model['batch_number'] ); $this->assertSame( '', $vendor_model['laboratory'] );
		$this->assertSame( 'Waiting on Vendor', $waiting_model['workflow_stage_label'] ); $this->assertNotSame( '', $waiting_model['expected_coa_date_label'] ); $this->assertSame( '', $waiting_model['batch_number'] ); $this->assertSame( '', $waiting_model['laboratory'] );
		$this->assertSame( 'Submitted to Laboratory', $submitted_model['workflow_stage_label'] ); $this->assertNotSame( '', $submitted_model['expected_coa_date_label'] ); $this->assertSame( '', $submitted_model['batch_number'] ); $this->assertSame( '', $submitted_model['laboratory'] );
		$this->assertSame( 'Verification in Progress', $testing_model['workflow_stage_label'] ); $this->assertSame( 'TESTING-PUBLIC', $testing_model['batch_number'] ); $this->assertSame( 'ILS Labs', $testing_model['laboratory'] );
	}

	public function test_incoming_only_has_no_fake_documented_card_and_transitions_without_elementor_changes() {
		$product = $this->product( 'Compound', 'CMP10' ); $compound = $this->compound( 'Compound 10 mg', $product );
		$test = $this->record( $compound, 'INCOMING-BATCH', '', array( 'coa_status' => 'pending', 'workflow_stage' => 'in-testing', 'expected_coa_date' => '2099-08-01' ) );
		$this->go_to( get_permalink( $product ) ); $html = $this->carousel()->shortcode();
		$this->assertSame( 1, substr_count( $html, 'ps-coa-product-carousel__card ' ) ); $this->assertStringContainsString( 'Incoming', $html );
		$this->assertStringNotContainsString( 'Current Batch', $html ); $this->assertStringNotContainsString( 'Latest Report', $html ); $this->assertStringNotContainsString( '>Purity<', $html );
		update_post_meta( $test, 'coa_status', 'approved' ); update_post_meta( $test, 'workflow_stage', 'complete' ); update_post_meta( $test, 'test_date', '2026-08-01' ); update_post_meta( $test, 'is_current', 1 );
		$html = $this->carousel()->shortcode();
		$this->assertStringContainsString( 'Current Batch', $html ); $this->assertStringNotContainsString( '>Incoming<', $html ); $this->assertStringContainsString( '/incoming-batch/', strtolower( $html ) );
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
		$this->assertStringContainsString( '&lt;Batch &amp; One&gt;', $html ); $this->assertSame( 1, substr_count( $html, '>Latest Report<' ) );
		$this->assertStringNotContainsString( 'lab_report_url', $html ); $this->assertStringNotContainsString( '.pdf', strtolower( $html ) ); $this->assertStringNotContainsString( 'qr', strtolower( $html ) );
		$this->assertTrue( wp_style_is( 'pepselect-coa-product-carousel', 'enqueued' ) ); $this->assertTrue( wp_script_is( 'pepselect-coa-product-carousel', 'enqueued' ) );
		$this->assertSame( '', $carousel->shortcode() );
	}

	private function carousel() { return new PepSelect\COAArchive\Product_COA_Carousel( $this->matching, $this->compounds, $this->tests, $this->view_model ); }
	private function product( $title, $sku ) { $id = self::factory()->post->create( array( 'post_type' => 'product', 'post_status' => 'publish', 'post_title' => $title ) ); update_post_meta( $id, '_sku', $sku ); return $id; }
	private function compound( $title, $product_id ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $title ) ); update_post_meta( $id, 'display_name', $title ); update_post_meta( $id, 'is_active', 1 ); if ( $product_id ) { update_post_meta( $id, PepSelect\COAArchive\Product_Matching::PRODUCT_ID_META, $product_id ); } return $id; }
	private function record( $compound_id, $batch, $date, $args = array() ) {
		$args = wp_parse_args( $args, array( 'post_status' => 'publish', 'post_date' => ( $date ?: '2026-07-15' ) . ' 12:00:00', 'coa_status' => 'approved', 'workflow_stage' => 'complete', 'private' => 0, 'purity_percentage' => '99.7899', 'purity_status' => 'pass', 'identity_status' => 'not-tested', 'full_qc' => false, 'is_current' => 0, 'expected_coa_date' => '' ) );
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => $args['post_status'], 'post_title' => $batch, 'post_date' => $args['post_date'] ) );
		$meta = array( 'compound_id' => $compound_id, 'batch_number' => $batch, 'test_date' => $date, 'coa_status' => $args['coa_status'], 'workflow_stage' => $args['workflow_stage'], 'testing_lab' => 'ils-labs', 'vials_tested' => 3, '_ps_coa_private' => $args['private'], 'purity_percentage' => $args['purity_percentage'], 'purity_status' => $args['purity_status'], 'identity_status' => $args['identity_status'], 'endotoxin_status' => 'not-tested', 'heavy_metals_status' => 'not-tested', 'sterility_status' => 'not-tested', 'fentanyl_status' => 'not-tested', 'is_current' => $args['is_current'], 'expected_coa_date' => $args['expected_coa_date'] );
		if ( $args['full_qc'] ) { $meta = array_merge( $meta, array( 'identity_status' => 'pass', 'average_net_content' => '30.84', 'heavy_metals_status' => 'pass', 'heavy_metals_summary' => 'Below limits', 'sterility_status' => 'pass', 'sterility_result' => 'No growth', 'endotoxin_status' => 'pass', 'endotoxin_result' => '< 0.05', 'endotoxin_unit' => 'EU/mL', 'fentanyl_status' => 'pass' ) ); }
		foreach ( $meta as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

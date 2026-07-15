<?php
/** COA-4G archive-catalog, search, image-fallback, and carousel-control coverage. */
class PepSelect_COA_Archive_COA_4G_Test extends WP_UnitTestCase {
	private $visibility; private $tests; private $compounds; private $view; private $router;

	public function set_up() {
		parent::set_up(); unset( $_GET['coa_search'] ); do_action( 'init' );
		$this->visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->tests = new PepSelect\COAArchive\COA_Test_Repository( $this->visibility );
		$this->compounds = new PepSelect\COAArchive\Compound_Repository( $this->visibility );
		$this->view = new PepSelect\COAArchive\Frontend_View_Model();
		$this->router = new PepSelect\COAArchive\Frontend_Router( new PepSelect\COAArchive\Frontend_Query(), $this->compounds, $this->tests, $this->view );
	}

	public function tear_down() { unset( $_GET['coa_search'] ); parent::tear_down(); }

	public function test_archive_catalog_template_renders_required_scoped_sections_and_preserves_card_structure() {
		$root = dirname( __DIR__ ); $template = file_get_contents( $root . '/templates/archive-testing.php' ); $hero = file_get_contents( $root . '/templates/partials/archive-hero.php' ); $card = file_get_contents( $root . '/templates/partials/compound-card.php' );
		foreach ( array( 'ps-coa-archive--catalog-layout', 'partials/archive-hero.php', 'Documented Compounds', 'Certificate archive', 'Showing %1$s of %2$s compounds', 'No matching compounds', 'Clear search' ) as $needle ) { $this->assertStringContainsString( $needle, $template ); }
		foreach ( array( 'Every batch. Every peptide.', 'Independently verified.', 'Independent labs', 'Batch-level COAs', 'Published unedited', 'role="search"', 'name="coa_search"', 'esc_attr( $search )' ) as $needle ) { $this->assertStringContainsString( $needle, $hero ); }
		foreach ( array( 'ps-coa-compound-card__media', 'wp_get_attachment_image', 'ps-coa-compound-card__body', 'ps-coa-strength', 'ps-coa-assurance', 'ps-coa-incoming-count', 'ps-coa-compact-facts', 'ps-coa-batch-preview', 'array_slice( $compound[\'recent_batches\'], 0, 3 )', 'ps-coa-compound-card__footer' ) as $needle ) { $this->assertStringContainsString( $needle, $card ); }
	}

	public function test_archive_search_matches_public_names_strength_and_batch_and_reports_filtered_counts() {
		$retatrutide = $this->compound( 'Retatrutide 30 mg', 'Retatrutide', 'Reta', 30 );
		$tirzepatide = $this->compound( 'Tirzepatide 15 mg', 'Tirzepatide', 'Tirz', 15 );
		$draft = $this->compound( 'Draft Retatrutide 30 mg', 'Draft Retatrutide', 'Draft Reta', 30, 'draft' );
		$this->complete_test( $retatrutide, 'RT30-0726-B', true ); $this->complete_test( $tirzepatide, 'TZ15-0626-D', true ); $this->complete_test( $draft, 'PRIVATE-30', true );
		$all = $this->router->build_archive( 1 );
		$this->assertSame( 2, $all['pagination']['total'] ); $this->assertSame( 2, $all['pagination']['available_total'] );
		foreach ( array( 'Retatrutide', 'Reta', '30 mg', 'RT30-0726-B' ) as $search ) { $context = $this->router->build_archive( 1, $search ); $this->assertSame( array( $retatrutide ), wp_list_pluck( $context['compounds'], 'compound_id' ) ); $this->assertSame( 1, $context['pagination']['total'] ); $this->assertSame( 2, $context['pagination']['available_total'] ); }
		$this->assertSame( array( $tirzepatide ), wp_list_pluck( $this->router->build_archive( 1, 'Tirz' )['compounds'], 'compound_id' ) );
		$empty = $this->router->build_archive( 1, 'NothingExists123' ); $this->assertEmpty( $empty['compounds'] ); $this->assertSame( 0, $empty['pagination']['total'] ); $this->assertSame( 2, $empty['pagination']['available_total'] );
		$this->assertEmpty( $this->router->build_archive( 1, 'PRIVATE-30' )['compounds'] );
		$_GET['coa_search'] = 'Retatrutide'; $rendered = do_shortcode( '[pepselect_coa_archive]' ); $this->assertStringContainsString( 'Showing 1 of 2 compounds', $rendered ); $this->assertStringContainsString( 'value="Retatrutide"', $rendered ); $this->assertStringNotContainsString( 'Tirzepatide', $rendered );
	}

	public function test_archive_card_image_uses_completed_current_report_fallback_order_without_cross_compound_or_incoming_leaks() {
		$compound = $this->compound( 'Retatrutide 30 mg', 'Retatrutide', 'Reta', 30 ); $current = $this->complete_test( $compound, 'CURRENT', true ); $incoming = $this->incoming_test( $compound );
		$other = $this->compound( 'Other 10 mg', 'Other', 'Other', 10 ); $other_test = $this->complete_test( $other, 'OTHER', true );
		$batch_image = $this->image( 'batch.jpg' ); $featured_image = $this->image( 'featured.jpg' ); $compound_image = $this->image( 'compound.jpg' ); $incoming_image = $this->image( 'incoming.jpg' ); $other_image = $this->image( 'other.jpg' );
		update_post_meta( $current, 'batch_vial_photo', $batch_image ); update_post_meta( $incoming, 'batch_vial_photo', $incoming_image ); update_post_meta( $other_test, 'batch_vial_photo', $other_image );
		$downsize = static function ( $value, $id ) use ( $batch_image, $featured_image, $compound_image, $incoming_image, $other_image ) { return in_array( $id, array( $batch_image, $featured_image, $compound_image, $incoming_image, $other_image ), true ) ? array( 'https://example.org/image-' . $id . '.jpg', 600, 800, true ) : $value; }; add_filter( 'image_downsize', $downsize, 10, 2 );
		$model = $this->view->archive_compound( get_post( $compound ), array( get_post( $incoming ), get_post( $current ) ) ); $this->assertSame( $batch_image, $model['compound_image_id'] ); $this->assertSame( 'batch-vial-photo', $model['archive_image_source'] ); $this->assertNotContains( $model['compound_image_id'], array( $incoming_image, $other_image ) );
		delete_post_meta( $current, 'batch_vial_photo' ); update_post_meta( $current, '_thumbnail_id', $featured_image ); $model = $this->view->archive_compound( get_post( $compound ), array( get_post( $incoming ), get_post( $current ) ) ); $this->assertSame( $featured_image, $model['compound_image_id'] ); $this->assertSame( 'featured-image', $model['archive_image_source'] );
		delete_post_meta( $current, '_thumbnail_id' ); update_post_meta( $compound, 'compound_image_id', $compound_image ); $model = $this->view->archive_compound( get_post( $compound ), array( get_post( $incoming ), get_post( $current ) ) ); $this->assertSame( $compound_image, $model['compound_image_id'] ); $this->assertSame( 'compound-image', $model['archive_image_source'] );
		delete_post_meta( $compound, 'compound_image_id' ); $model = $this->view->archive_compound( get_post( $compound ), array( get_post( $incoming ), get_post( $current ) ) ); $this->assertSame( 0, $model['compound_image_id'] ); $this->assertSame( 'local-placeholder', $model['archive_image_source'] ); $this->assertStringContainsString( 'neutral-vial.svg', $model['compound_image_url'] );
		remove_filter( 'image_downsize', $downsize, 10 );
	}

	public function test_catalog_grid_and_carousel_controls_are_responsive_scoped_and_nonshrinking() {
		$root = dirname( __DIR__ ); $css = file_get_contents( $root . '/assets/css/pepselect-coa-frontend.css' ); $carousel = file_get_contents( $root . '/templates/partials/history-previous-carousel.php' ); $script = file_get_contents( $root . '/assets/js/pepselect-coa-history-carousel.js' );
		$this->assertStringContainsString( '.ps-coa-archive--catalog-layout .ps-coa-compound-grid', $css );
		foreach ( array( 'repeat(3, minmax(0, 1fr))', 'repeat(2, minmax(0, 1fr))', 'grid-template-columns: 1fr', 'align-items: start' ) as $needle ) { $this->assertStringContainsString( $needle, $css ); }
		$this->assertMatchesRegularExpression( '/\\.ps-coa-history-carousel__control \\{[^}]*aspect-ratio: 1 \/ 1;[^}]*border-radius: 50% !important;[^}]*flex: 0 0 48px;[^}]*height: 48px !important;[^}]*min-height: 48px;[^}]*min-width: 48px;[^}]*width: 48px !important;/s', $css );
		$this->assertSame( 1, substr_count( $carousel, 'data-ps-history-previous' ) ); $this->assertSame( 1, substr_count( $carousel, 'data-ps-history-next' ) ); $this->assertStringContainsString( 'aria-label', $carousel );
		$this->assertStringContainsString( "event.key === 'ArrowLeft'", $script ); $this->assertStringContainsString( "event.key === 'ArrowRight'", $script ); $this->assertStringNotContainsString( 'setInterval', $script );
		$all_php = implode( '', array_map( 'file_get_contents', glob( $root . '/includes/*.php' ) ) ); $this->assertStringNotContainsString( 'woocommerce_single_product', $all_php ); $this->assertStringNotContainsString( 'qrcode', strtolower( $all_php ) );
	}

	private function compound( $title, $name, $short, $strength, $status = 'publish' ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => $status, 'post_title' => $title ) ); foreach ( array( 'is_active' => 1, 'display_name' => $title, 'compound_name' => $name, 'short_name' => $short, 'strength_value' => $strength, 'strength_unit' => 'mg' ) as $key => $value ) { update_post_meta( $id, $key, $value ); } return $id; }
	private function complete_test( $compound, $batch, $current = false ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => $batch ) ); foreach ( array( 'compound_id' => $compound, 'workflow_stage' => 'complete', 'coa_status' => 'approved', 'batch_number' => $batch, 'test_date' => '20260710', 'is_current' => $current ? 1 : 0, 'testing_lab' => 'ils-labs' ) as $key => $value ) { update_post_meta( $id, $key, $value ); } return $id; }
	private function incoming_test( $compound ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Incoming' ) ); foreach ( array( 'compound_id' => $compound, 'workflow_stage' => 'submitted-to-lab', 'coa_status' => 'pending', 'expected_coa_date' => '20260720' ) as $key => $value ) { update_post_meta( $id, $key, $value ); } return $id; }
	private function image( $name ) { return self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'image/jpeg', 'guid' => 'https://example.org/' . $name ) ); }
}

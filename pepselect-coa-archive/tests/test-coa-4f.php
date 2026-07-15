<?php
/** COA-4F compound-history and laboratory-logo coverage. */
class PepSelect_COA_Archive_COA_4F_Test extends WP_UnitTestCase {
	private $view;

	public function set_up() { parent::set_up(); do_action( 'init' ); $this->view = new PepSelect\COAArchive\Frontend_View_Model(); }

	public function test_history_hero_uses_current_exact_batch_image_and_never_unrelated_media() {
		$compound = $this->compound(); $current = $this->complete_test( $compound, 'CURRENT', '20260710', true );
		$image = $this->image( 'current.jpg' ); $unrelated_compound = $this->compound( 'Other Compound' ); $unrelated = $this->complete_test( $unrelated_compound, 'OTHER', '20260712', true ); $other_image = $this->image( 'other.jpg' );
		update_post_meta( $current, 'batch_vial_photo', $image ); update_post_meta( $unrelated, 'batch_vial_photo', $other_image );
		$downsize = $this->image_downsize_filter( array( $image => 'https://example.org/current.jpg', $other_image => 'https://example.org/other.jpg' ) ); add_filter( 'image_downsize', $downsize, 10, 2 );
		$context = $this->router()->build_compound( '', $compound );
		$this->assertSame( $image, $context['hero_image']['id'] ); $this->assertSame( 'batch-vial-photo', $context['hero_image']['source'] ); $this->assertStringNotContainsString( 'other.jpg', wp_json_encode( $context ) );
		remove_filter( 'image_downsize', $downsize, 10 );
	}

	public function test_incoming_image_is_scoped_and_missing_image_uses_local_placeholder() {
		$compound = $this->compound(); $this->complete_test( $compound, 'CURRENT', '20260710', true );
		$incoming = $this->incoming_test( $compound ); $image = $this->image( 'incoming.jpg' ); update_post_meta( $incoming, 'batch_vial_photo', $image );
		$downsize = $this->image_downsize_filter( array( $image => 'https://example.org/incoming.jpg' ) ); add_filter( 'image_downsize', $downsize, 10, 2 );
		$context = $this->router()->build_compound( '', $compound );
		$this->assertSame( $image, $context['incoming_reports'][0]['vial_image_id'] ); $this->assertSame( 'batch-vial-photo', $context['incoming_reports'][0]['vial_image_source'] );
		delete_post_meta( $incoming, 'batch_vial_photo' ); $model = $this->view->test_summary( get_post( $incoming ), get_post( $compound ) );
		$this->assertSame( 'local-placeholder', $model['vial_image_source'] ); $this->assertStringContainsString( 'neutral-vial.svg', $model['vial_image_url'] );
		remove_filter( 'image_downsize', $downsize, 10 );
	}

	public function test_latest_history_report_has_fixed_truthful_seven_category_model() {
		$compound = $this->compound(); $test = $this->complete_test( $compound, 'FULL', '20260710', true ); $this->full_results( $test );
		$model = $this->view->history_report( get_post( $test ), get_post( $compound ) );
		$this->assertSame( array( 'Identity', 'Purity', 'Net Content', 'Heavy Metals', 'Sterility', 'Endotoxins', 'Fentanyl Screen' ), wp_list_pluck( $model['qc_strip_rows'], 'short_label' ) );
		$this->assertSame( 7, $model['reported_category_count'] ); $this->assertSame( 'Full-QC', $model['history_report_type'] ); $this->assertSame( 'Full-QC testing passed.', $model['history_qc_title'] );
		update_post_meta( $test, 'fentanyl_status', 'not-tested' ); $partial = $this->view->history_report( get_post( $test ), get_post( $compound ) );
		$this->assertSame( '--', $partial['qc_strip_rows'][6]['detail'] ); $this->assertFalse( $partial['qc_strip_rows'][6]['status']['success'] ); $this->assertSame( 'Partial QC', $partial['history_report_type'] );
	}

	public function test_previous_carousel_is_sorted_capped_and_non_destructive() {
		$compound = $this->compound(); $current = $this->complete_test( $compound, 'CURRENT', '20261231', true ); $ids = array();
		for ( $number = 1; $number <= 12; $number++ ) { $ids[] = $this->complete_test( $compound, 'PREVIOUS-' . $number, sprintf( '2026%02d01', $number ), false, 12 === $number ? 'failed' : 'approved' ); }
		$context = $this->router()->build_compound( '', $compound );
		$this->assertSame( $current, $context['latest_report']['test_id'] ); $this->assertCount( 10, $context['previous_reports'] ); $this->assertSame( 12, $context['previous_report_total'] );
		$this->assertSame( array( 'PREVIOUS-12', 'PREVIOUS-11' ), array_slice( wp_list_pluck( $context['previous_reports'], 'batch_number' ), 0, 2 ) );
		$this->assertSame( 'failed', $context['previous_reports'][0]['coa_status'] );
		foreach ( $ids as $id ) { $this->assertSame( 'publish', get_post_status( $id ) ); $this->assertNotEmpty( $this->view->test_url( get_post( $compound ), get_post( $id ) ) ); }
	}

	public function test_history_templates_include_all_sections_empty_states_and_accessible_carousel() {
		$root = dirname( __DIR__ ); $template = file_get_contents( $root . '/templates/single-compound-history.php' ); $carousel = file_get_contents( $root . '/templates/partials/history-previous-carousel.php' ); $incoming = file_get_contents( $root . '/templates/partials/history-incoming-empty.php' );
		foreach ( array( 'history-hero.php', 'history-latest-report.php', 'history-incoming-report.php', 'history-incoming-empty.php', 'history-previous-carousel.php' ) as $partial ) { $this->assertStringContainsString( $partial, $template ); }
		$this->assertStringContainsString( 'No new batches currently under vetting.', $incoming ); $this->assertStringContainsString( 'No previous completed reports are available.', $carousel );
		foreach ( array( 'aria-roledescription', 'data-ps-history-previous', 'data-ps-history-next', 'aria-live="polite"' ) as $needle ) { $this->assertStringContainsString( $needle, $carousel ); }
		$this->assertStringNotContainsString( 'ps-coa-report-card__vial', $template );
	}

	public function test_coa_4f_1_carousel_polish_preserves_controls_behavior_and_card_structure() {
		$root = dirname( __DIR__ ); $css = file_get_contents( $root . '/assets/css/pepselect-coa-frontend.css' ); $carousel = file_get_contents( $root . '/templates/partials/history-previous-carousel.php' ); $card = file_get_contents( $root . '/templates/partials/history-previous-card.php' ); $script = file_get_contents( $root . '/assets/js/pepselect-coa-history-carousel.js' ); $router = file_get_contents( $root . '/includes/class-frontend-router.php' );
		$this->assertMatchesRegularExpression( '/\\.ps-coa-history-carousel__control \\{[^}]*font-size: 1\\.5rem;[^}]*height: 48px;[^}]*top: calc\\(50% - 24px\\);[^}]*width: 48px;/s', $css );
		$this->assertStringContainsString( 'box-shadow: 0 5px 14px rgba(24, 53, 79, .14)', $css );
		$this->assertMatchesRegularExpression( '/\\.ps-coa-history-previous__results li strong \\{[^}]*font-size: \\.58rem;/s', $css );
		$this->assertMatchesRegularExpression( '/\\.ps-coa-history-previous__results li small \\{[^}]*font-size: \\.56rem;[^}]*line-height: 1\\.35;[^}]*overflow: hidden;[^}]*text-overflow: ellipsis;[^}]*white-space: nowrap;/s', $css );
		$this->assertSame( 1, substr_count( $carousel, 'data-ps-history-previous' ) ); $this->assertSame( 1, substr_count( $carousel, 'data-ps-history-next' ) );
		foreach ( array( 'ps-coa-history-previous__identity', 'ps-coa-history-previous__results', 'ps-coa-history-previous__actions' ) as $class ) { $this->assertSame( 1, substr_count( $card, $class ) ); }
		$this->assertStringContainsString( "event.key === 'ArrowLeft'", $script ); $this->assertStringContainsString( "event.key === 'ArrowRight'", $script ); $this->assertStringNotContainsString( 'setInterval', $script );
		$this->assertStringContainsString( 'array_slice( $previous_all, 0, 10 )', $router );
		$this->assertStringContainsString( '.ps-coa button:focus-visible', $css );
	}

	public function test_laboratory_logo_field_validation_priority_and_ils_fallback_are_wired() {
		$root = dirname( __DIR__ ); $fields = file_get_contents( $root . '/includes/class-coa-test-fields.php' ); $validation = file_get_contents( $root . '/includes/class-coa-test-validation.php' );
		$this->assertStringContainsString( "'laboratory_logo', 'Laboratory Logo', 'image'", $fields ); $this->assertStringContainsString( 'valid_laboratory_logo', $validation );
		$field_service = new PepSelect\COAArchive\COA_Test_Fields( new PepSelect\COAArchive\Dependencies() ); $method = new ReflectionMethod( $field_service, 'fields' ); $method->setAccessible( true ); $definitions = $method->invoke( $field_service ); $logo_field = current( array_filter( $definitions, static function ( $field ) { return 'laboratory_logo' === $field['name']; } ) );
		$this->assertSame( 'field_ps_coa_test_laboratory_logo', $logo_field['key'] ); $this->assertSame( 'id', $logo_field['return_format'] ); $this->assertSame( 0, $logo_field['required'] );
		$admin = self::factory()->user->create( array( 'role' => 'administrator' ) ); wp_set_current_user( $admin ); $_POST['acf']['field_ps_coa_test_workflow_stage'] = 'complete';
		$safe = $this->image( 'safe.png', 'image/png' ); $unsafe = self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => 'application/x-php', 'guid' => 'https://example.org/unsafe.php' ) );
		$validator = new PepSelect\COAArchive\COA_Test_Validation(); $this->assertTrue( $validator->validate( true, $safe, $logo_field, '' ) ); $this->assertIsString( $validator->validate( true, $unsafe, $logo_field, '' ) ); unset( $_POST['acf'] );
		$compound = $this->compound(); $test = $this->complete_test( $compound, 'LOGO', '20260710', true ); $model = $this->view->report( get_post( $test ), get_post( $compound ) );
		$this->assertSame( 'bundled-ils', $model['laboratory_logo_source'] ); $this->assertStringContainsString( 'assets/images/ils-labs-logo.png', $model['laboratory_logo_url'] );
		$this->assertArrayNotHasKey( 'laboratory_logo_url', $this->view->test_summary( get_post( $test ), get_post( $compound ) ) );
		$image = $this->image( 'logo.png', 'image/png' ); update_post_meta( $test, 'laboratory_logo', $image ); $downsize = $this->image_downsize_filter( array( $image => 'https://example.org/logo.png' ) ); add_filter( 'image_downsize', $downsize, 10, 2 );
		$model = $this->view->report( get_post( $test ), get_post( $compound ) ); $this->assertSame( $image, $model['laboratory_logo_id'] ); $this->assertSame( 'attachment', $model['laboratory_logo_source'] );
		$mapped = $this->complete_test( $compound, 'MAPPED', '20260709' ); update_post_meta( $mapped, 'testing_lab', 'other' ); update_post_meta( $mapped, 'other_testing_lab', 'Custom QA Laboratory' ); update_post_meta( $mapped, 'laboratory_logo', $image );
		$reuse = $this->complete_test( $compound, 'REUSE', '20260708' ); update_post_meta( $reuse, 'testing_lab', 'other' ); update_post_meta( $reuse, 'other_testing_lab', 'Custom QA Laboratory' ); $reused = $this->view->report( get_post( $reuse ), get_post( $compound ) ); $this->assertSame( $image, $reused['laboratory_logo_id'] );
		remove_filter( 'image_downsize', $downsize, 10 );
	}

	public function test_assets_routes_and_deferred_features_remain_scoped() {
		$root = dirname( __DIR__ ); $loader = file_get_contents( $root . '/includes/class-frontend-template-loader.php' ); $report = file_get_contents( $root . '/templates/partials/laboratory-report-panel.php' );
		$this->assertStringContainsString( 'context_has_history_carousel', $loader ); $this->assertStringContainsString( 'pepselect-coa-history-carousel.js', $loader ); $this->assertStringContainsString( 'laboratory-logo.php', $report );
		$this->assertFileExists( $root . '/assets/images/ils-labs-logo.png' ); $this->assertFileExists( $root . '/assets/js/pepselect-coa-history-carousel.js' );
		$all_php = implode( '', array_map( 'file_get_contents', glob( $root . '/includes/*.php' ) ) ); $this->assertStringNotContainsString( 'woocommerce_single_product', $all_php ); $this->assertStringNotContainsString( 'qrcode', strtolower( $all_php ) );
	}

	private function router() { $visibility = new PepSelect\COAArchive\Frontend_Visibility(); return new PepSelect\COAArchive\Frontend_Router( new PepSelect\COAArchive\Frontend_Query(), new PepSelect\COAArchive\Compound_Repository( $visibility ), new PepSelect\COAArchive\COA_Test_Repository( $visibility ), $this->view ); }
	private function compound( $title = 'Retatrutide 30 mg' ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $title ) ); update_post_meta( $id, 'is_active', 1 ); update_post_meta( $id, 'display_name', $title ); update_post_meta( $id, 'compound_name', preg_replace( '/\s+30 mg$/', '', $title ) ); update_post_meta( $id, 'strength_value', 30 ); update_post_meta( $id, 'strength_unit', 'mg' ); return $id; }
	private function complete_test( $compound, $batch, $date, $current = false, $status = 'approved' ) { $post_date = substr( $date, 0, 4 ) . '-' . substr( $date, 4, 2 ) . '-' . substr( $date, 6, 2 ) . ' 12:00:00'; $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => $batch, 'post_date_gmt' => $post_date ) ); foreach ( array( 'compound_id' => $compound, 'workflow_stage' => 'complete', 'coa_status' => $status, 'batch_number' => $batch, 'test_date' => $date, 'is_current' => $current ? 1 : 0, 'testing_lab' => 'ils-labs', 'vials_tested' => 3, 'content_unit' => 'mg', 'release_decision_note' => 'Did not pass release review.' ) as $key => $value ) { update_post_meta( $id, $key, $value ); } return $id; }
	private function incoming_test( $compound ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Incoming' ) ); foreach ( array( 'compound_id' => $compound, 'workflow_stage' => 'in-testing', 'coa_status' => 'pending', 'batch_number' => 'INCOMING', 'expected_coa_date' => '20260730', 'testing_lab' => 'ils-labs' ) as $key => $value ) { update_post_meta( $id, $key, $value ); } return $id; }
	private function full_results( $test ) { foreach ( array( 'claimed_content' => '30', 'average_net_content' => '30.84', 'minimum_net_content' => '30.71', 'maximum_net_content' => '30.99', 'purity_percentage' => '99.99', 'purity_status' => 'pass', 'purity_method' => 'HPLC', 'identity_status' => 'pass', 'identity_method' => 'LC-MS', 'heavy_metals_status' => 'pass', 'heavy_metals_summary' => 'Below limits', 'sterility_status' => 'pass', 'sterility_result' => 'No growth', 'endotoxin_status' => 'pass', 'endotoxin_result' => '< 0.05', 'endotoxin_unit' => 'EU/mL', 'fentanyl_status' => 'pass' ) as $key => $value ) { update_post_meta( $test, $key, $value ); } }
	private function image( $name, $mime = 'image/jpeg' ) { return self::factory()->post->create( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'post_mime_type' => $mime, 'guid' => 'https://example.org/' . $name ) ); }
	private function image_downsize_filter( $urls ) { return static function ( $value, $id ) use ( $urls ) { return isset( $urls[ $id ] ) ? array( $urls[ $id ], 600, 800, true ) : $value; }; }
}

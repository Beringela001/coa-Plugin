<?php
/** Focused COA-5C WordPress Dashboard workflow-center tests. */
class PepSelect_COA_Archive_COA_5C_Dashboard_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Dashboard_Workflow */
	private $dashboard;

	/** @var int */
	private $admin_id;

	public function set_up() {
		parent::set_up();
		set_current_screen( 'dashboard' );
		do_action( 'init' );
		PepSelect\COAArchive\Capabilities::grant_to_administrators();
		$this->admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $this->admin_id );
		$this->dashboard = new PepSelect\COAArchive\Dashboard_Workflow();
		wp_dequeue_style( 'pepselect-coa-dashboard-workflow' );
	}

	public function tear_down() {
		wp_set_current_user( 0 );
		wp_dequeue_style( 'pepselect-coa-dashboard-workflow' );
		set_current_screen( 'front' );
		parent::tear_down();
	}

	public function test_registration_title_and_authorized_widget_visibility() {
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard'] = array();
		$this->dashboard->register_hooks();
		$this->assertNotFalse( has_action( 'wp_dashboard_setup', array( $this->dashboard, 'register_widget' ) ) );
		$this->dashboard->register_widget();
		$this->assertTrue( $this->widget_registered( PepSelect\COAArchive\Dashboard_Workflow::WIDGET_ID, 'COA Workflow Center' ) );
	}

	public function test_unauthorized_and_public_users_receive_no_widget_or_output() {
		global $wp_meta_boxes;
		$wp_meta_boxes['dashboard'] = array();
		$subscriber = self::factory()->user->create( array( 'role' => 'subscriber' ) );
		foreach ( array( $subscriber, 0 ) as $user_id ) {
			wp_set_current_user( $user_id );
			$this->dashboard->register_widget();
			ob_start(); $this->dashboard->render(); $output = ob_get_clean();
			$this->assertSame( '', $output );
			$this->assertSame( 0, $this->dashboard->build_view_model()['total'] );
		}
		$this->assertFalse( $this->widget_registered( PepSelect\COAArchive\Dashboard_Workflow::WIDGET_ID ) );
	}

	public function test_active_stages_legacy_aliases_counters_and_inactive_compounds() {
		$active = $this->compound( 'Inactive but administratively visible', false );
		$this->record( $active, 'vendor-vetting', 'pending' );
		$this->record( $active, 'waiting-on-vendor', 'pending' );
		$this->record( $active, 'sample-received', 'pending' );
		$this->record( $active, 'coa-pending', 'pending' );
		$this->record( $active, 'complete', 'approved' );
		$this->record( $active, 'complete', 'failed' );
		self::factory()->post->create( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Unrelated' ) );
		$model = $this->model();
		$this->assertSame( 4, $model['total'] );
		$this->assertSame( array( 1, 1, 1, 1, 0 ), array_values( $model['counters'] ) );
		$this->assertSame( array( 'in-testing', 'submitted-to-lab', 'waiting-on-vendor', 'vendor-vetting' ), wp_list_pluck( $model['rows'], 'stage' ) );
		$this->assertSame( array( 'Verification in Progress', 'Submitted to Laboratory', 'Waiting on Vendor', 'Vendor Vetting' ), wp_list_pluck( $model['rows'], 'stage_label' ) );
	}

	public function test_overdue_rules_use_site_calendar_days_and_ignore_early_or_closed_stages() {
		update_option( 'timezone_string', 'America/New_York' );
		$compound = $this->compound( 'Timezone Compound' );
		$testing = $this->record( $compound, 'in-testing', 'pending', '20260710' );
		$submitted = $this->record( $compound, 'submitted-to-lab', 'pending', '2026-07-14' );
		$this->record( $compound, 'in-testing', 'pending', '20260720' );
		$this->record( $compound, 'in-testing', 'pending', '' );
		$this->record( $compound, 'vendor-vetting', 'pending', '20260701' );
		$this->record( $compound, 'waiting-on-vendor', 'pending', '20260701' );
		$this->record( $compound, 'complete', 'approved', '20260701' );
		$this->record( $compound, 'complete', 'failed', '20260701' );
		$model = $this->model( '2026-07-15' );
		$this->assertSame( 2, $model['counters']['overdue'] );
		$rows = array_column( $model['rows'], null, 'id' );
		$this->assertTrue( $rows[ $testing ]['overdue'] ); $this->assertSame( 5, $rows[ $testing ]['overdue_days'] );
		$this->assertTrue( $rows[ $submitted ]['overdue'] ); $this->assertSame( 1, $rows[ $submitted ]['overdue_days'] );
		foreach ( $rows as $id => $row ) { if ( ! in_array( $id, array( $testing, $submitted ), true ) ) { $this->assertFalse( $row['overdue'] ); } }
	}

	public function test_urgency_stage_date_modified_and_id_sorting_are_deterministic() {
		$compound = $this->compound( 'Sort Compound' );
		$most_overdue = $this->record( $compound, 'submitted-to-lab', 'pending', '20260701' );
		$overdue_testing = $this->record( $compound, 'in-testing', 'pending', '20260710' );
		$testing = $this->record( $compound, 'in-testing', 'pending', '20260720' );
		$submitted = $this->record( $compound, 'submitted-to-lab', 'pending', '20260718' );
		$waiting_early = $this->record( $compound, 'waiting-on-vendor', 'pending', '20260717' );
		$waiting_late = $this->record( $compound, 'waiting-on-vendor', 'pending', '20260719' );
		$waiting_none = $this->record( $compound, 'waiting-on-vendor', 'pending', '', '', '2026-07-13 10:00:00' );
		$waiting_newer = $this->record( $compound, 'waiting-on-vendor', 'pending', '', '', '2026-07-14 10:00:00' );
		$vendor = $this->record( $compound, 'vendor-vetting', 'pending', '', '', '2026-07-12 10:00:00' );
		$vendor_tie = $this->record( $compound, 'vendor-vetting', 'pending', '', '', '2026-07-12 10:00:00' );
		$model = $this->model();
		$this->assertSame( array( $most_overdue, $overdue_testing, $testing, $submitted, $waiting_early, $waiting_late, $waiting_newer, $waiting_none, $vendor_tie, $vendor ), wp_list_pluck( $model['rows'], 'id' ) );
	}

	public function test_table_output_is_normalized_escaped_complete_and_exactly_linked() {
		$compound = $this->compound( 'Unsafe <script>alert(1)</script>' );
		$dated = $this->record( $compound, 'in-testing', 'pending', '20260717', 'BATCH-17' );
		$this->record( $compound, 'vendor-vetting', 'pending', '', '' );
		$output = $this->render();
		$this->assertStringContainsString( 'Unsafe &lt;script&gt;alert(1)&lt;/script&gt;', $output );
		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( 'Verification in Progress', $output );
		$this->assertStringContainsString( 'Jul 17, 2026', $output );
		$this->assertStringContainsString( 'BATCH-17', $output );
		$this->assertStringContainsString( '&mdash;', $output );
		$this->assertStringContainsString( 'post=' . $dated . '&amp;action=edit', $output );
		foreach ( array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab', 'in-testing' ) as $raw_stage ) { $this->assertStringNotContainsString( $raw_stage, $output ); }
	}

	public function test_limit_selects_ten_most_urgent_and_exposes_view_all() {
		$compound = $this->compound( 'Limit Compound' );
		$ids = array();
		for ( $day = 1; $day <= 12; ++$day ) { $ids[] = $this->record( $compound, 'in-testing', 'pending', sprintf( '202607%02d', $day ) ); }
		$model = $this->model();
		$this->assertSame( 12, $model['total'] );
		$this->assertCount( 10, $model['rows'] );
		$this->assertTrue( $model['has_more'] );
		$this->assertSame( array_slice( $ids, 0, 10 ), wp_list_pluck( $model['rows'], 'id' ) );
		$this->assertStringContainsString( 'View all active COA Tests', $this->render() );
	}

	public function test_empty_state_and_footer_actions_use_existing_permission_gated_urls() {
		$model = $this->model();
		$this->assertSame( 0, $model['total'] );
		$this->assertStringContainsString( 'post-new.php?post_type=ps_coa_test', $model['actions']['add']['url'] );
		$this->assertStringContainsString( 'edit.php?post_type=ps_coa_test', $model['actions']['view']['url'] );
		$this->assertStringContainsString( 'admin.php?page=pepselect-coa-product-matching', $model['actions']['matching']['url'] );
		$output = $this->render();
		$this->assertStringContainsString( 'No active COA workflows need attention.', $output );
		$this->assertStringContainsString( 'Add New COA Test', $output );

		$role = add_role( 'ps_coa_operator', 'COA Operator', array( 'read' => true, 'edit_ps_coas' => true ) );
		$user = self::factory()->user->create( array( 'role' => 'ps_coa_operator' ) );
		wp_set_current_user( $user );
		$limited = $this->dashboard->build_view_model();
		$this->assertArrayHasKey( 'view', $limited['actions'] );
		$this->assertArrayNotHasKey( 'add', $limited['actions'] );
		$this->assertArrayNotHasKey( 'matching', $limited['actions'] );
		remove_role( 'ps_coa_operator' );
	}

	public function test_dashboard_stylesheet_is_route_and_capability_scoped() {
		$this->dashboard->enqueue_assets( 'post.php' );
		$this->assertFalse( wp_style_is( 'pepselect-coa-dashboard-workflow', 'enqueued' ) );
		$this->dashboard->enqueue_assets( 'index.php' );
		$this->assertTrue( wp_style_is( 'pepselect-coa-dashboard-workflow', 'enqueued' ) );
		wp_dequeue_style( 'pepselect-coa-dashboard-workflow' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->dashboard->enqueue_assets( 'index.php' );
		$this->assertFalse( wp_style_is( 'pepselect-coa-dashboard-workflow', 'enqueued' ) );
	}

	private function model( $date = '2026-07-15' ) { return $this->dashboard->build_view_model( new DateTimeImmutable( $date . ' 00:00:00', wp_timezone() ) ); }
	private function render() { ob_start(); $this->dashboard->render(); return ob_get_clean(); }
	private function compound( $name, $active = true ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => wp_strip_all_tags( $name ) ) ); update_post_meta( $id, 'display_name', $name ); update_post_meta( $id, 'is_active', $active ? 1 : 0 ); return $id; }
	private function record( $compound, $stage, $status = 'pending', $expected = '', $batch = '', $modified = '' ) {
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'Workflow ' . $stage, 'post_author' => $this->admin_id ) );
		foreach ( array( 'compound_id' => $compound, 'workflow_stage' => $stage, 'coa_status' => $status, 'expected_coa_date' => $expected, 'batch_number' => $batch ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		if ( $modified ) { global $wpdb; $wpdb->update( $wpdb->posts, array( 'post_modified' => $modified, 'post_modified_gmt' => get_gmt_from_date( $modified ) ), array( 'ID' => $id ) ); clean_post_cache( $id ); }
		return $id;
	}
	private function widget_registered( $id, $title = '' ) {
		global $wp_meta_boxes;
		foreach ( isset( $wp_meta_boxes['dashboard'] ) ? $wp_meta_boxes['dashboard'] : array() as $contexts ) { foreach ( $contexts as $boxes ) { if ( isset( $boxes[ $id ] ) && ( '' === $title || $title === $boxes[ $id ]['title'] ) ) { return true; } } }
		return false;
	}
}

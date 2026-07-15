<?php
/** Focused COA-5B.2 public archive card alignment and workflow sorting tests. */
class PepSelect_COA_Archive_COA_5B_2_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\COA_Test_Repository */
	private $tests;

	/** @var PepSelect\COAArchive\Compound_Repository */
	private $compounds;

	public function set_up() {
		parent::set_up(); do_action( 'init' );
		$visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->tests = new PepSelect\COAArchive\COA_Test_Repository( $visibility );
		$this->compounds = new PepSelect\COAArchive\Compound_Repository( $visibility );
		delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache(); PepSelect\COAArchive\Archive_Cache::invalidate();
	}

	public function tear_down() { delete_option( PepSelect\COAArchive\Design_Settings::OPTION ); PepSelect\COAArchive\Design_Settings::clear_cache(); parent::tear_down(); }

	public function test_archive_priority_order_overrides_display_order() {
		$complete = $this->compound( 'Target Complete', 50 ); $this->record( $complete, 'approved', 'complete', array( 'is_current' => 1 ) );
		$testing = $this->compound( 'Target Verification', 1 ); $this->record( $testing, 'pending', 'in-testing' );
		$submitted = $this->compound( 'Target Submitted', 1 ); $this->record( $submitted, 'pending', 'submitted-to-lab' );
		$waiting = $this->compound( 'Target Waiting', 1 ); $this->record( $waiting, 'pending', 'waiting-on-vendor' );
		$vendor = $this->compound( 'Target Vendor', 1 ); $this->record( $vendor, 'pending', 'vendor-vetting' );
		$no_active = $this->compound( 'Target Previous', 1 ); $this->record( $no_active, 'approved', 'complete', array( 'is_current' => 0 ) );
		$this->assertSame( array( $complete, $testing, $submitted, $waiting, $vendor, $no_active ), $this->ordered_ids() );
	}

	public function test_display_order_name_and_post_id_are_stable_within_a_status_group() {
		$later = $this->compound( 'Zulu', 20 ); $this->record( $later, 'pending', 'waiting-on-vendor' );
		$beta = $this->compound( 'Beta', 10 ); $this->record( $beta, 'pending', 'waiting-on-vendor' );
		$alpha_first = $this->compound( 'Alpha', 10 ); $this->record( $alpha_first, 'pending', 'waiting-on-vendor' );
		$alpha_second = $this->compound( 'Alpha', 10 ); $this->record( $alpha_second, 'pending', 'waiting-on-vendor' );
		$this->assertSame( array( $alpha_first, $alpha_second, $beta, $later ), $this->ordered_ids() );
	}

	public function test_current_approved_wins_and_most_advanced_incoming_controls_without_one() {
		$current = $this->compound( 'Current and Incoming' );
		$this->record( $current, 'approved', 'complete', array( 'is_current' => 1 ) ); $this->record( $current, 'pending', 'in-testing' );
		$advanced = $this->compound( 'Several Incoming' );
		$this->record( $advanced, 'pending', 'vendor-vetting' ); $this->record( $advanced, 'pending', 'submitted-to-lab' ); $this->record( $advanced, 'pending', 'in-testing' );
		$index = $this->tests->archive_index();
		$this->assertSame( 1, $index['sort_priorities'][ $current ] );
		$this->assertSame( 2, $index['sort_priorities'][ $advanced ] );
	}

	public function test_failed_and_previous_approved_do_not_override_public_incoming_state() {
		$replacement = $this->compound( 'Replacement' );
		$this->record( $replacement, 'approved', 'complete', array( 'is_current' => 0 ) ); $this->record( $replacement, 'failed', 'complete' ); $this->record( $replacement, 'pending', 'submitted-to-lab' );
		$failed_only = $this->compound( 'Failed Only' ); $this->record( $failed_only, 'failed', 'complete' );
		$default = $this->tests->archive_index(); $with_failed = $this->tests->archive_index( '', true );
		$this->assertSame( 3, $default['sort_priorities'][ $replacement ] );
		$this->assertNotContains( $failed_only, $default['compound_ids'] );
		$this->assertSame( 6, $with_failed['sort_priorities'][ $failed_only ] );
	}

	public function test_draft_private_and_inactive_records_do_not_influence_sorting() {
		$public = $this->compound( 'Public Vendor' ); $this->record( $public, 'pending', 'vendor-vetting' );
		$this->record( $public, 'pending', 'in-testing', array( 'post_status' => 'draft' ) );
		$this->record( $public, 'approved', 'complete', array( 'post_status' => 'private', 'is_current' => 1 ) );
		$inactive = $this->compound( 'Inactive', 0, false ); $this->record( $inactive, 'approved', 'complete', array( 'is_current' => 1 ) );
		$index = $this->tests->archive_index();
		$this->assertSame( 5, $index['sort_priorities'][ $public ] );
		$this->assertNotContains( $inactive, $index['compound_ids'] );
	}

	public function test_search_preserves_status_order_and_accurate_counts_without_mutation() {
		$complete = $this->compound( 'Target Complete', 90 ); $complete_test = $this->record( $complete, 'approved', 'complete', array( 'is_current' => 1 ) );
		$waiting = $this->compound( 'Target Waiting', 2 ); $this->record( $waiting, 'pending', 'waiting-on-vendor' );
		$other = $this->compound( 'Unrelated', 1 ); $this->record( $other, 'pending', 'in-testing' );
		$display_order = get_post_meta( $complete, 'display_order', true ); $stage = get_post_meta( $complete_test, 'workflow_stage', true );
		$result = $this->archive_result( 'Target' );
		$this->assertSame( array( $complete, $waiting ), wp_list_pluck( $result['posts'], 'ID' ) );
		$this->assertSame( 2, $result['total'] ); $this->assertSame( 3, $result['available_total'] );
		$this->assertSame( array( $complete, $other, $waiting ), $this->ordered_ids() );
		$this->assertSame( $display_order, get_post_meta( $complete, 'display_order', true ) ); $this->assertSame( $stage, get_post_meta( $complete_test, 'workflow_stage', true ) );
	}

	public function test_priority_scope_and_relevant_saves_refresh_cached_order() {
		$compound = $this->compound( 'Cache Test' ); $test = $this->record( $compound, 'pending', 'vendor-vetting' );
		$vendor_key = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ), array( $compound => 5 ) );
		$testing_key = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ), array( $compound => 2 ) );
		$this->assertNotSame( $vendor_key, $testing_key );
		$before = PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ) ); do_action( 'acf/save_post', $test );
		$this->assertNotSame( $before, PepSelect\COAArchive\Archive_Cache::key( '', 1, 24, array( $compound ) ) );
	}

	private function archive_result( $search = '', $show_failed_only = false ) { $index = $this->tests->archive_index( $search, $show_failed_only ); return $this->compounds->archive_page( $index['compound_ids'], 1, 100, $search, $index['batch_matches'], $index['sort_priorities'] ); }
	private function ordered_ids() { return wp_list_pluck( $this->archive_result()['posts'], 'ID' ); }
	private function compound( $name, $order = 0, $active = true ) { $id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => $name ) ); update_post_meta( $id, 'display_name', $name ); update_post_meta( $id, 'display_order', $order ); update_post_meta( $id, 'is_active', $active ? 1 : 0 ); return $id; }
	private function record( $compound, $status, $stage, $args = array() ) {
		$args = wp_parse_args( $args, array( 'post_status' => 'publish', 'is_current' => 0 ) );
		$id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => $args['post_status'], 'post_title' => 'Test' ) );
		foreach ( array( 'compound_id' => $compound, 'coa_status' => $status, 'workflow_stage' => $stage, 'batch_number' => 'B-' . $id, 'test_date' => '20260715', 'expected_coa_date' => '20260730', 'testing_lab' => 'ils-labs', 'vials_tested' => 1, 'is_current' => $args['is_current'] ) as $key => $value ) { update_post_meta( $id, $key, $value ); }
		return $id;
	}
}

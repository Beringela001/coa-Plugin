<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Applies post-save COA invariants without changing scientific status. */
final class COA_Test_Service {
	/** @var bool */
	private $running = false;

	/** Initializes an empty title and enforces one current COA per compound. @param int|string $post_id Post ID. @return void */
	public function after_save( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || $this->running || Post_Types::COA_TEST !== get_post_type( $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		$this->running = true;
		$this->initialize_title( $post_id );
		if ( get_post_meta( $post_id, 'is_current', true ) ) { $this->clear_other_current_tests( $post_id, absint( get_post_meta( $post_id, 'compound_id', true ) ) ); }
		$this->flag_future_date( $post_id );
		$this->running = false;
	}

	/** Displays a one-time non-blocking warning for a clearly future test date. @return void */
	public function render_future_date_notice() {
		if ( ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$key = 'ps_coa_future_date_' . get_current_user_id(); $post_id = absint( get_transient( $key ) );
		if ( ! $post_id ) { return; }
		delete_transient( $key );
		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( sprintf( __( 'The test date for “%s” is in the future. Confirm that this is intentional.', 'pepselect-coa-archive' ), get_the_title( $post_id ) ) ) );
	}

	/** Clears current flags only for other tests of the same compound. @param int $post_id Saved test. @param int $compound_id Compound. @return void */
	private function clear_other_current_tests( $post_id, $compound_id ) {
		if ( ! $compound_id ) { return; }
		$others = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => 'any', 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'post__not_in' => array( $post_id ), 'meta_query' => array( array( 'key' => 'compound_id', 'value' => $compound_id ), array( 'key' => 'is_current', 'value' => '1' ) ) ) );
		foreach ( $others as $other_id ) { update_post_meta( $other_id, 'is_current', 0 ); }
	}

	/** Creates the initial administrative title without overwriting a manual title. @param int $post_id Test ID. @return void */
	private function initialize_title( $post_id ) {
		$post = get_post( $post_id ); if ( ! $post || '' !== trim( $post->post_title ) ) { return; }
		$compound_id = absint( get_post_meta( $post_id, 'compound_id', true ) );
		$batch = trim( (string) get_post_meta( $post_id, 'batch_number', true ) );
		if ( ! $compound_id || '' === $batch ) { return; }
		$name = trim( (string) get_post_meta( $compound_id, 'display_name', true ) );
		if ( '' === $name ) { $name = get_the_title( $compound_id ); }
		if ( '' !== $name ) { wp_update_post( array( 'ID' => $post_id, 'post_title' => sprintf( __( '%1$s — Batch %2$s', 'pepselect-coa-archive' ), $name, $batch ) ) ); }
	}

	/** Records a short-lived future-date warning for the saving administrator. @param int $post_id Test ID. @return void */
	private function flag_future_date( $post_id ) {
		$date = get_post_meta( $post_id, 'test_date', true );
		$test_date = $date ? \DateTimeImmutable::createFromFormat( '!Y-m-d', $date, wp_timezone() ) : false;
		if ( $test_date && $test_date > current_datetime()->modify( '+1 day' ) ) { set_transient( 'ps_coa_future_date_' . get_current_user_id(), $post_id, MINUTE_IN_SECONDS ); }
	}
}

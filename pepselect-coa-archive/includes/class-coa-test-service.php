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
		$this->apply_ils_verification_default( $post_id );
		if ( get_post_meta( $post_id, 'is_current', true ) ) { $this->clear_other_current_tests( $post_id, absint( get_post_meta( $post_id, 'compound_id', true ) ) ); }
		$this->flag_future_date( $post_id );
		$this->flag_workflow_guidance( $post_id );
		$this->running = false;
	}

	/** Supplies the ILS portal only when no saved/manual URL exists. @param mixed $value Current value. @param int|string $post_id Post ID. @param array $field ACF field. @return mixed */
	public function load_verification_url( $value, $post_id, $field ) {
		unset( $field );
		if ( '' !== trim( (string) $value ) ) { return $value; }
		$post_id = absint( $post_id );
		return $post_id && 'ils-labs' === get_post_meta( $post_id, 'testing_lab', true ) ? 'https://lab.ils-lab.com' : $value;
	}

	/** Displays a one-time non-blocking warning for a clearly future test date. @return void */
	public function render_future_date_notice() {
		if ( ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$key = 'ps_coa_future_date_' . get_current_user_id(); $post_id = absint( get_transient( $key ) );
		if ( ! $post_id ) { return; }
		delete_transient( $key );
		printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( sprintf( __( 'The test date for “%s” is in the future. Confirm that this is intentional.', 'pepselect-coa-archive' ), get_the_title( $post_id ) ) ) );
	}

	/** Displays non-blocking operational workflow guidance after a save. @return void */
	public function render_workflow_notices() {
		if ( ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $post_id && Post_Types::COA_TEST === get_post_type( $post_id ) && in_array( COA_Workflow::stage( $post_id ), array( 'in-testing', 'complete' ), true ) ) {
			$image_id = absint( get_post_meta( $post_id, 'batch_vial_photo', true ) );
			if ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) { printf( '<div class="notice notice-warning"><p>%s</p></div>', esc_html__( 'This legacy report has no Batch Vial Photo. Its public URL remains available, but a valid exact-vial photo is required before changing its workflow or materially updating and republishing it.', 'pepselect-coa-archive' ) ); }
		}
		$key = 'ps_coa_workflow_notices_' . get_current_user_id();
		$messages = get_transient( $key );
		if ( ! is_array( $messages ) || ! $messages ) { return; }
		delete_transient( $key );
		foreach ( $messages as $message ) { printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $message ) ); }
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
		$format = preg_match( '/^\d{8}$/', (string) $date ) ? '!Ymd' : '!Y-m-d';
		$test_date = $date ? \DateTimeImmutable::createFromFormat( $format, $date, wp_timezone() ) : false;
		if ( $test_date && $test_date > current_datetime()->modify( '+1 day' ) ) { set_transient( 'ps_coa_future_date_' . get_current_user_id(), $post_id, MINUTE_IN_SECONDS ); }
	}

	/** Records non-destructive warnings for incomplete operational metadata. @param int $post_id Test ID. @return void */
	private function flag_workflow_guidance( $post_id ) {
		$stage = COA_Workflow::stage( $post_id );
		$outcome = COA_Workflow::outcome( $post_id );
		$messages = array();
		if ( 'complete' === $stage && 'pending' === $outcome ) { $messages[] = __( 'Workflow is complete, but a final Approved or Failed COA outcome has not been selected.', 'pepselect-coa-archive' ); }
		if ( 'in-testing' === $stage && '' === trim( (string) get_post_meta( $post_id, 'pending_lab_url', true ) ) ) { $messages[] = __( 'A pending laboratory URL is recommended while verification is in progress.', 'pepselect-coa-archive' ); }
		$image_id = absint( get_post_meta( $post_id, 'batch_vial_photo', true ) );
		if ( in_array( $stage, array( 'in-testing', 'complete' ), true ) && ( ! $image_id || ! wp_attachment_is_image( $image_id ) ) ) { $messages[] = __( 'Add a valid Batch Vial Photo showing the exact tested vial before the next material update.', 'pepselect-coa-archive' ); }
		$expected = get_post_meta( $post_id, 'expected_coa_date', true );
		$format = preg_match( '/^\d{8}$/', (string) $expected ) ? '!Ymd' : '!Y-m-d';
		$date = $expected ? \DateTimeImmutable::createFromFormat( $format, $expected, wp_timezone() ) : false;
		if ( $date && 'complete' !== $stage && $date < current_datetime()->setTime( 0, 0 ) ) { $messages[] = __( 'The expected COA date is in the past. Update it if the estimate has changed.', 'pepselect-coa-archive' ); }
		if ( $messages ) { set_transient( 'ps_coa_workflow_notices_' . get_current_user_id(), $messages, MINUTE_IN_SECONDS ); }
	}

	/** Stores the general ILS portal on first save without replacing a manual URL. @param int $post_id Test ID. @return void */
	private function apply_ils_verification_default( $post_id ) {
		if ( 'ils-labs' === get_post_meta( $post_id, 'testing_lab', true ) && '' === trim( (string) get_post_meta( $post_id, 'lab_verification_url', true ) ) ) { update_post_meta( $post_id, 'lab_verification_url', 'https://lab.ils-lab.com' ); }
	}
}

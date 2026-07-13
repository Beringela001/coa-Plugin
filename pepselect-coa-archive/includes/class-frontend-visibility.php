<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Centralizes every public compound and COA Test visibility decision. */
final class Frontend_Visibility {
	/** Returns whether a compound may appear publicly. @param int|\WP_Post $compound Compound. @return bool */
	public function is_compound_public( $compound ) {
		if ( ! $compound ) { return false; }
		$post = get_post( $compound );
		return $post && Post_Types::COMPOUND === $post->post_type && 'publish' === $post->post_status && $this->truthy( get_post_meta( $post->ID, 'is_active', true ) );
	}

	/** Returns whether a test is eligible for all COA-4A public views. @param int|\WP_Post $test Test. @param int $compound_id Optional expected compound. @return bool */
	public function is_test_public( $test, $compound_id = 0 ) {
		if ( ! $test ) { return false; }
		$post = get_post( $test );
		if ( ! $post || Post_Types::COA_TEST !== $post->post_type || 'publish' !== $post->post_status ) { return false; }
		if ( 'approved' !== get_post_meta( $post->ID, 'coa_status', true ) ) { return false; }
		if ( $this->truthy( get_post_meta( $post->ID, '_ps_coa_private', true ) ) || 'private' === get_post_meta( $post->ID, 'public_visibility', true ) ) { return false; }
		$related = absint( get_post_meta( $post->ID, 'compound_id', true ) );
		if ( ! $related || ( $compound_id && $related !== absint( $compound_id ) ) || ! $this->is_compound_public( $related ) ) { return false; }
		if ( '' === trim( (string) get_post_meta( $post->ID, 'batch_number', true ) ) ) { return false; }
		$date = preg_replace( '/\D/', '', (string) get_post_meta( $post->ID, 'test_date', true ) );
		if ( 8 !== strlen( $date ) || ! checkdate( (int) substr( $date, 4, 2 ), (int) substr( $date, 6, 2 ), (int) substr( $date, 0, 4 ) ) ) { return false; }
		$lab = (string) get_post_meta( $post->ID, 'testing_lab', true );
		if ( ! in_array( $lab, array( 'ils-labs', 'janoshik', 'mz-biotech', 'other' ), true ) || ( 'other' === $lab && '' === trim( (string) get_post_meta( $post->ID, 'other_testing_lab', true ) ) ) ) { return false; }
		return absint( get_post_meta( $post->ID, 'vials_tested', true ) ) >= 1;
	}

	/** Normalizes common stored boolean representations. @param mixed $value Value. @return bool */
	private function truthy( $value ) {
		return in_array( $value, array( true, 1, '1', 'true', 'yes', 'on' ), true );
	}
}

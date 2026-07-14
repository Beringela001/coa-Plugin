<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** WordPress-native repository for publicly eligible COA tests. */
final class COA_Test_Repository {
	/** @var Frontend_Visibility */
	private $visibility;

	public function __construct( Frontend_Visibility $visibility ) { $this->visibility = $visibility; }

	/** Returns every eligible test for a compound in public display order. @param int $compound_id Compound ID. @return \WP_Post[] */
	public function all_for_compound( $compound_id ) {
		$ids = $this->eligible_ids( array( absint( $compound_id ) ) );
		$posts = array_values( array_filter( array_map( 'get_post', $ids ) ) );
		usort( $posts, array( $this, 'compare_tests' ) );
		return $posts;
	}

	/** Returns classified public tests for one compound. @return array */
	public function classified_for_compound( $compound_id ) {
		$result = array( 'approved' => array(), 'incoming' => array(), 'failed' => array() );
		foreach ( $this->all_for_compound( $compound_id ) as $test ) {
			if ( $this->visibility->is_approved( $test ) ) { $result['approved'][] = $test; }
			elseif ( $this->visibility->is_incoming( $test ) ) { $result['incoming'][] = $test; }
			elseif ( $this->visibility->is_failed( $test ) ) { $result['failed'][] = $test; }
		}
		return $result;
	}

	/** Returns eligible tests grouped by compound ID. @param int[] $compound_ids Compound IDs. @return array */
	public function grouped_for_compounds( $compound_ids ) {
		$grouped = array();
		foreach ( array_map( 'absint', $compound_ids ) as $compound_id ) { $grouped[ $compound_id ] = array(); }
		foreach ( $this->eligible_ids( array_keys( $grouped ) ) as $test_id ) {
			$compound_id = absint( get_post_meta( $test_id, 'compound_id', true ) );
			if ( isset( $grouped[ $compound_id ] ) ) { $grouped[ $compound_id ][] = get_post( $test_id ); }
		}
		foreach ( $grouped as &$tests ) { usort( $tests, array( $this, 'compare_tests' ) ); }
		unset( $tests );
		return $grouped;
	}

	/** Returns unique compound IDs that own eligible tests. @return int[] */
	public function compound_ids_with_public_tests() {
		$compound_ids = array();
		foreach ( $this->eligible_ids() as $test_id ) { $compound_ids[] = absint( get_post_meta( $test_id, 'compound_id', true ) ); }
		return array_values( array_unique( array_filter( $compound_ids ) ) );
	}

	/** Finds an eligible test by ID and optional expected compound. @param int $test_id Test ID. @param int $compound_id Expected compound. @return \WP_Post|null */
	public function find_public_by_id( $test_id, $compound_id = 0 ) {
		$post = get_post( absint( $test_id ) );
		return $this->visibility->is_test_public( $post, $compound_id ) ? $post : null;
	}

	/** Finds an exact eligible batch under one compound without guessing. @param int $compound_id Compound ID. @param string $batch_slug Batch slug. @return \WP_Post|null */
	public function find_public_by_batch_slug( $compound_id, $batch_slug ) {
		$slug = sanitize_title( $batch_slug );
		if ( '' === $slug ) { return null; }
		$matches = array();
		foreach ( $this->all_for_compound( $compound_id ) as $test ) {
			$batch = sanitize_title( (string) get_post_meta( $test->ID, 'batch_number', true ) );
			if ( $slug === $batch || $slug === sanitize_title( $test->post_name ) ) { $matches[ $test->ID ] = $test; }
		}
		return 1 === count( $matches ) ? reset( $matches ) : null;
	}

	/** Returns previous/next reports in the public compound ordering. @param int $compound_id Compound. @param int $test_id Test. @return array */
	public function adjacent( $compound_id, $test_id ) {
		$tests = $this->all_for_compound( $compound_id );
		foreach ( $tests as $index => $test ) {
			if ( absint( $test_id ) === $test->ID ) {
				return array( 'previous' => isset( $tests[ $index + 1 ] ) ? $tests[ $index + 1 ] : null, 'next' => $index > 0 ? $tests[ $index - 1 ] : null );
			}
		}
		return array( 'previous' => null, 'next' => null );
	}

	/** Returns eligible IDs after centralized visibility filtering and cache priming. @param int[] $compound_ids Optional compounds. @return int[] */
	private function eligible_ids( $compound_ids = array() ) {
		$meta_query = array( array( 'key' => 'coa_status', 'value' => array( 'approved', 'failed', 'pending', 'in-testing', 'vendor-vetting' ), 'compare' => 'IN' ) );
		if ( $compound_ids ) { $meta_query[] = array( 'key' => 'compound_id', 'value' => array_map( 'absint', $compound_ids ), 'compare' => 'IN', 'type' => 'NUMERIC' ); }
		$ids = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids', 'orderby' => 'date', 'order' => 'DESC', 'meta_query' => $meta_query, 'no_found_rows' => true, 'suppress_filters' => false ) );
		$ids = array_map( 'absint', $ids );
		if ( $ids ) { _prime_post_caches( $ids, false, false ); update_meta_cache( 'post', $ids ); }
		$related = array_values( array_unique( array_filter( array_map( static function ( $id ) { return absint( get_post_meta( $id, 'compound_id', true ) ); }, $ids ) ) ) );
		if ( $related ) { _prime_post_caches( $related, false, false ); update_meta_cache( 'post', $related ); }
		return array_values( array_filter( $ids, function ( $id ) { return $this->visibility->is_test_public( $id ); } ) );
	}

	/** Sorts current first, then test date and publish date descending. @return int */
	private function compare_tests( $left, $right ) {
		$current = absint( get_post_meta( $right->ID, 'is_current', true ) ) <=> absint( get_post_meta( $left->ID, 'is_current', true ) );
		if ( 0 !== $current ) { return $current; }
		$left_date = preg_replace( '/\D/', '', (string) get_post_meta( $left->ID, 'test_date', true ) );
		$right_date = preg_replace( '/\D/', '', (string) get_post_meta( $right->ID, 'test_date', true ) );
		$date = strcmp( $right_date, $left_date );
		return 0 !== $date ? $date : strcmp( $right->post_date_gmt, $left->post_date_gmt );
	}
}

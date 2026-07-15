<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Provides narrowly scoped, versioned caching for public archive result pages. */
final class Archive_Cache {
	const GROUP = 'pepselect_coa_archive';
	const VERSION_OPTION = 'pepselect_coa_archive_cache_version';

	/** Registers invalidation hooks for records that can affect archive eligibility or display. @return void */
	public static function register_hooks() {
		add_action( 'save_post_' . Post_Types::COMPOUND, array( __CLASS__, 'invalidate_for_post' ), 50 );
		add_action( 'save_post_' . Post_Types::COA_TEST, array( __CLASS__, 'invalidate_for_post' ), 50 );
		add_action( 'acf/save_post', array( __CLASS__, 'invalidate_for_post' ), 50 );
		add_action( 'before_delete_post', array( __CLASS__, 'invalidate_for_post' ) );
		add_action( 'trashed_post', array( __CLASS__, 'invalidate_for_post' ) );
		add_action( 'untrashed_post', array( __CLASS__, 'invalidate_for_post' ) );
		add_action( 'update_option_' . Design_Settings::OPTION, array( __CLASS__, 'invalidate' ) );
	}

	/** Returns a cached archive result or null on a miss. @return array|null */
	public static function get( $search, $page, $per_page, $eligible_ids, $sort_priorities = array() ) {
		$found = false;
		$value = wp_cache_get( self::key( $search, $page, $per_page, $eligible_ids, $sort_priorities ), self::GROUP, false, $found );
		return $found && is_array( $value ) ? $value : null;
	}

	/** Stores one archive result in the plugin-only cache group. @return void */
	public static function set( $search, $page, $per_page, $eligible_ids, $value, $sort_priorities = array() ) {
		wp_cache_set( self::key( $search, $page, $per_page, $eligible_ids, $sort_priorities ), $value, self::GROUP, HOUR_IN_SECONDS );
	}

	/** Builds a stable key that separates unsearched and normalized searched archives. @return string */
	public static function key( $search, $page, $per_page, $eligible_ids, $sort_priorities = array() ) {
		$search = Frontend_Query::normalize_search( $search );
		$ids = array_values( array_unique( array_filter( array_map( 'absint', (array) $eligible_ids ) ) ) );
		sort( $ids, SORT_NUMERIC );
		$priority_scope = array(); foreach ( $ids as $id ) { $priority_scope[ $id ] = isset( $sort_priorities[ $id ] ) ? absint( $sort_priorities[ $id ] ) : 6; }
		$scope = '' === $search ? 'all' : 'search-' . md5( strtolower( $search ) );
		return sprintf( 'archive-v%d-%s-p%d-n%d-%s-%s', self::version(), $scope, max( 1, absint( $page ) ), max( 1, absint( $per_page ) ), md5( implode( ',', $ids ) ), md5( wp_json_encode( $priority_scope ) ) );
	}

	/** Advances only this plugin's archive namespace, making prior results unreachable. @return int */
	public static function invalidate( $unused = null ) {
		unset( $unused );
		$version = self::version() + 1;
		update_option( self::VERSION_OPTION, $version, false );
		return $version;
	}

	/** Invalidates only when the affected record can participate in the archive. @param int|string $post_id Post ID. @return void */
	public static function invalidate_for_post( $post_id ) {
		$post_type = get_post_type( absint( $post_id ) );
		if ( in_array( $post_type, array( Post_Types::COMPOUND, Post_Types::COA_TEST ), true ) ) { self::invalidate(); }
	}

	/** Returns the current cache namespace. @return int */
	private static function version() { return max( 1, absint( get_option( self::VERSION_OPTION, 1 ) ) ); }
}

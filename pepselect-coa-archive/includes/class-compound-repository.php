<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** WordPress-native repository for public compound records. */
final class Compound_Repository {
	/** @var Frontend_Visibility */
	private $visibility;

	public function __construct( Frontend_Visibility $visibility ) { $this->visibility = $visibility; }

	/** Finds a public compound by slug. @param string $slug Slug. @return \WP_Post|null */
	public function find_public_by_slug( $slug ) {
		$posts = get_posts( array( 'name' => sanitize_title( $slug ), 'post_type' => Post_Types::COMPOUND, 'post_status' => 'publish', 'posts_per_page' => 1, 'no_found_rows' => true, 'suppress_filters' => false ) );
		$post = $posts ? $posts[0] : null;
		return $this->visibility->is_compound_public( $post ) ? $post : null;
	}

	/** Finds a public compound by ID. @param int $id ID. @return \WP_Post|null */
	public function find_public_by_id( $id ) {
		$post = get_post( absint( $id ) );
		return $this->visibility->is_compound_public( $post ) ? $post : null;
	}

	/** Returns an ordered, paginated public compound result. @param int[] $eligible_ids IDs with tests. @param int $page Page. @param int $per_page Page size. @param string $search Search term. @param int[] $public_batch_matches Compounds whose public batch number matches. @return array */
	public function archive_page( $eligible_ids, $page = 1, $per_page = 24, $search = '', $public_batch_matches = array() ) {
		$ids = array_values( array_unique( array_filter( array_map( 'absint', $eligible_ids ) ) ) );
		if ( ! $ids ) { return array( 'posts' => array(), 'total' => 0, 'available_total' => 0, 'pages' => 0, 'page' => 1 ); }
		$search = Frontend_Query::normalize_search( $search );
		$public_batch_matches = array_values( array_unique( array_filter( array_map( 'absint', $public_batch_matches ) ) ) );
		$cached = Archive_Cache::get( $search, $page, $per_page, $ids );
		if ( null !== $cached ) { return $cached; }
		$posts = get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => 'publish', 'post__in' => $ids, 'posts_per_page' => -1, 'no_found_rows' => true, 'suppress_filters' => false ) );
		$posts = array_values( array_filter( $posts, array( $this->visibility, 'is_compound_public' ) ) );
		if ( $posts ) { update_meta_cache( 'post', wp_list_pluck( $posts, 'ID' ) ); }
		$available_total = count( $posts );
		if ( '' !== $search ) {
			$posts = array_values( array_filter( $posts, static function ( $post ) use ( $search, $public_batch_matches ) {
				$strength = trim( (string) get_post_meta( $post->ID, 'strength_value', true ) . ' ' . (string) get_post_meta( $post->ID, 'strength_unit', true ) );
				$haystack = implode( ' ', array(
					(string) $post->post_title,
					(string) get_post_meta( $post->ID, 'display_name', true ),
					(string) get_post_meta( $post->ID, 'compound_name', true ),
					(string) get_post_meta( $post->ID, 'short_name', true ),
					$strength,
				) );
				return in_array( $post->ID, $public_batch_matches, true ) || false !== stripos( $haystack, $search );
			} ) );
		}
		usort( $posts, array( $this, 'compare_compounds' ) );
		$total = count( $posts ); $pages = (int) ceil( $total / $per_page ); $page = min( max( 1, absint( $page ) ), max( 1, $pages ) );
		$result = array( 'posts' => array_slice( $posts, ( $page - 1 ) * $per_page, $per_page ), 'total' => $total, 'available_total' => $available_total, 'pages' => $pages, 'page' => $page );
		Archive_Cache::set( $search, $page, $per_page, $ids, $result );
		return $result;
	}

	/** Sorts featured desc, display order asc, display name asc. @return int */
	private function compare_compounds( $left, $right ) {
		$featured = absint( get_post_meta( $right->ID, 'is_featured', true ) ) <=> absint( get_post_meta( $left->ID, 'is_featured', true ) );
		if ( 0 !== $featured ) { return $featured; }
		$order = absint( get_post_meta( $left->ID, 'display_order', true ) ) <=> absint( get_post_meta( $right->ID, 'display_order', true ) );
		if ( 0 !== $order ) { return $order; }
		$left_name = get_post_meta( $left->ID, 'display_name', true ) ?: $left->post_title;
		$right_name = get_post_meta( $right->ID, 'display_name', true ) ?: $right->post_title;
		return strnatcasecmp( $left_name, $right_name );
	}
}

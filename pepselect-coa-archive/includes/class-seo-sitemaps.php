<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Keeps SEO-plugin sitemaps aligned with the COA archive's real public routes. */
final class SEO_Sitemaps {
	/** @var Frontend_Visibility */
	private $visibility;

	/** @var COA_Test_Repository */
	private $tests;

	/** @var Frontend_View_Model */
	private $view_model;

	public function __construct( Frontend_Visibility $visibility, COA_Test_Repository $tests, Frontend_View_Model $view_model ) {
		$this->visibility = $visibility;
		$this->tests      = $tests;
		$this->view_model = $view_model;
	}

	/** Registers only additive Yoast sitemap integrations. */
	public function register_hooks() {
		add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', array( $this, 'exclude_unroutable_posts' ) );
		add_filter( 'wpseo_xml_sitemap_post_url', array( $this, 'filter_post_url' ), 10, 2 );
		add_filter( 'wpseo_sitemap_post_type_first_links', array( $this, 'add_archive_url' ), 10, 2 );
		// Yoast's llms.txt lists posts whose stored indexable is public; keep unpassed reports out of it.
		add_filter( 'wpseo_should_save_indexable', array( $this, 'mark_unpassed_indexable_private' ), 10, 2 );
		// Without indexables (any non-production environment, e.g. staging) Yoast builds the list with get_posts() instead.
		add_action( 'pre_get_posts', array( $this, 'exclude_unpassed_from_llms_txt_query' ) );
	}

	/**
	 * Excludes unpassed COA Tests from Yoast's llms.txt fallback query.
	 *
	 * Yoast's Automatic_Post_Collection::get_recently_modified_posts_wp_query()
	 * runs get_posts() with post_type, orderby=modified, post_status=publish and
	 * has_password=false. Only that exact shape for ps_coa_test is touched; the
	 * plugin's own queries never set has_password.
	 *
	 * @param \WP_Query $query Query.
	 * @return void
	 */
	public function exclude_unpassed_from_llms_txt_query( $query ) {
		if ( ! is_object( $query ) || ! method_exists( $query, 'get' ) ) { return; }
		if ( Post_Types::COA_TEST !== $query->get( 'post_type' ) || 'modified' !== $query->get( 'orderby' ) || 'publish' !== $query->get( 'post_status' ) || false !== $query->get( 'has_password' ) ) { return; }
		$ids = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => 'publish', 'posts_per_page' => 500, 'fields' => 'ids', 'no_found_rows' => true ) );
		$unpassed = array_values( array_filter( array_map( 'absint', $ids ), array( __CLASS__, 'is_unpassed_test' ) ) );
		if ( ! $unpassed ) { return; }
		$existing = array_filter( array_map( 'absint', (array) $query->get( 'post__not_in' ) ) );
		$query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $unpassed ) ) ) );
	}

	/**
	 * Marks a not-yet-passed COA Test indexable as non-public just before Yoast stores it.
	 *
	 * Only is_public changes; the per-post Yoast robots fields and the save decision
	 * are untouched. Yoast's llms.txt automatic lists (and other indexable-based
	 * public lists) read is_public, so unpassed reports drop out of them.
	 *
	 * @param bool  $intend_to_save Yoast's own decision, returned unchanged.
	 * @param mixed $indexable      Yoast indexable model.
	 * @return bool
	 */
	public function mark_unpassed_indexable_private( $intend_to_save, $indexable = null ) {
		if ( ! is_object( $indexable ) || ! isset( $indexable->object_type, $indexable->object_sub_type, $indexable->object_id ) ) { return $intend_to_save; }
		if ( 'post' !== $indexable->object_type || Post_Types::COA_TEST !== $indexable->object_sub_type ) { return $intend_to_save; }
		if ( self::is_unpassed_test( absint( $indexable->object_id ) ) ) { $indexable->is_public = false; }
		return $intend_to_save;
	}

	/**
	 * Excludes COA records whose plugin-owned public route would return 404.
	 *
	 * Yoast otherwise indexes the raw custom-post-type permalinks. Those URLs are
	 * intentionally blocked by Frontend_Router because they bypass compound and
	 * report visibility. Existing exclusions from other integrations are kept.
	 *
	 * @param int[] $excluded Existing excluded post IDs.
	 * @return int[]
	 */
	public function exclude_unroutable_posts( $excluded ) {
		$excluded = is_array( $excluded ) ? array_map( 'absint', $excluded ) : array();
		$ids      = get_posts(
			array(
				'post_type'      => array( Post_Types::COMPOUND, Post_Types::COA_TEST ),
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'suppress_filters' => false,
			)
		);

		foreach ( $ids as $post_id ) {
			if ( '' === $this->public_url( $post_id ) ) {
				$excluded[] = absint( $post_id );
			} elseif ( Post_Types::COA_TEST === get_post_type( $post_id ) && self::is_unpassed_test( $post_id ) ) {
				// Matches the routed-page default: reports that have not passed are noindex, so keep them out of the sitemap too.
				$excluded[] = absint( $post_id );
			}
		}

		return array_values( array_unique( array_filter( $excluded ) ) );
	}

	/** True when a test has not passed and carries no explicit per-post Yoast "index" choice. @param int $post_id Test. @return bool */
	public static function is_unpassed_test( $post_id ) {
		if ( '2' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) { return false; }
		$passed = 'complete' === COA_Workflow::stage( $post_id ) && 'approved' === COA_Workflow::outcome( $post_id );
		return ! $passed;
	}

	/**
	 * Replaces an internal CPT permalink with the canonical public archive URL.
	 *
	 * @param string   $url  URL generated by Yoast.
	 * @param \WP_Post $post Sitemap post.
	 * @return string
	 */
	public function filter_post_url( $url, $post ) {
		if ( ! $post instanceof \WP_Post || ! in_array( $post->post_type, array( Post_Types::COMPOUND, Post_Types::COA_TEST ), true ) ) {
			return $url;
		}

		$public_url = $this->public_url( $post );
		return '' !== $public_url ? $public_url : $url;
	}

	/**
	 * Adds the plugin-owned archive route to the first compound sitemap page.
	 *
	 * @param array[] $links     Existing first-page sitemap links.
	 * @param string  $post_type Sitemap post type.
	 * @return array[]
	 */
	public function add_archive_url( $links, $post_type ) {
		if ( Post_Types::COMPOUND !== $post_type ) {
			return $links;
		}

		$links   = is_array( $links ) ? $links : array();
		$archive = $this->view_model->archive_url();
		foreach ( $links as $link ) {
			if ( isset( $link['loc'] ) && untrailingslashit( $link['loc'] ) === untrailingslashit( $archive ) ) {
				return $links;
			}
		}

		array_unshift(
			$links,
			array(
				'loc' => $archive,
				'mod' => gmdate( 'c', $this->archive_modified_time() ),
			)
		);

		return $links;
	}

	/** Returns a public URL only when the exact frontend route resolves. */
	public function public_url( $post ) {
		$post = get_post( $post );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return '';
		}

		if ( Post_Types::COMPOUND === $post->post_type ) {
			if ( ! $this->visibility->is_compound_public( $post ) || ! $this->tests->all_for_compound( $post->ID ) ) {
				return '';
			}
			return $this->view_model->compound_url( $post );
		}

		if ( Post_Types::COA_TEST !== $post->post_type || ! $this->visibility->is_test_public( $post ) ) {
			return '';
		}

		$compound_id = absint( get_post_meta( $post->ID, 'compound_id', true ) );
		$compound    = get_post( $compound_id );
		if ( ! $compound || ! $this->visibility->is_compound_public( $compound ) ) {
			return '';
		}

		$batch_slug = $this->view_model->batch_slug( $post );
		$resolved   = $this->tests->find_public_by_batch_slug( $compound_id, $batch_slug );
		if ( ! $resolved || absint( $resolved->ID ) !== absint( $post->ID ) ) {
			return '';
		}

		return $this->view_model->test_url( $compound, $post );
	}

	/** Returns the latest public COA modification time for archive lastmod. */
	private function archive_modified_time() {
		$latest = get_posts(
			array(
				'post_type'      => array( Post_Types::COMPOUND, Post_Types::COA_TEST ),
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				'orderby'        => 'modified',
				'order'          => 'DESC',
				'no_found_rows'  => true,
			)
		);
		if ( ! $latest ) {
			return time();
		}

		$modified = get_post_modified_time( 'U', true, $latest[0] );
		return $modified ? absint( $modified ) : time();
	}
}

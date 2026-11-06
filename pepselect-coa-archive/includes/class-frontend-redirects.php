<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sends retired /testing/ compound slugs to the compound's current URL.
 *
 * A request that already resolves is never redirected. Progress IDs move to a
 * batch slug only after batch_slug() itself has stopped returning progress-ID
 * and that batch URL resolves. Submitted-to-laboratory records keep progress-ID
 * as their public address, so their batch numbers stay out of the URL.
 */
final class Frontend_Redirects {
	const FORMER_SLUG_META = '_ps_coa_former_slug';

	/** @var Frontend_Router */
	private $router;

	/** @var Frontend_Query */
	private $query;

	/** @var Compound_Repository */
	private $compounds;

	/** @var COA_Test_Repository */
	private $tests;

	/** @var Frontend_View_Model */
	private $view_model;

	/**
	 * @param Frontend_Router     $router     Route builder.
	 * @param Frontend_Query      $query      Current request.
	 * @param Compound_Repository $compounds  Public compounds.
	 * @param COA_Test_Repository $tests      Public tests.
	 * @param Frontend_View_Model $view_model Canonical URLs.
	 */
	public function __construct( Frontend_Router $router, Frontend_Query $query, Compound_Repository $compounds, COA_Test_Repository $tests, Frontend_View_Model $view_model ) {
		$this->router     = $router;
		$this->query      = $query;
		$this->compounds  = $compounds;
		$this->tests      = $tests;
		$this->view_model = $view_model;
	}

	/** Registers slug recording and the public redirect. @return void */
	public function register_hooks() {
		add_action( 'post_updated', array( $this, 'record_former_slug' ), 10, 3 );
		// After QR_Redirects (priority 0) so printed NAD and /testing/961 paths stay exact.
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 2 );
	}

	/**
	 * Retired public slugs and printed numeric IDs, each pinned to one compound.
	 *
	 * The value is the compound post ID. The redirect reads that post's current
	 * permalink, which is saved from the editor. This registry does not store
	 * the replacement slug, does not derive one from a display name, and does
	 * not write strength. s31 and s31-10 were never public, so they are absent.
	 * Description-only compounds are absent too.
	 *
	 * @return array<string,int>
	 */
	public static function explicit_aliases() {
		$aliases = array(
			'961'              => 961,
			'retatrutide-10mg' => 961,
			'885'              => 885,
			'retatrutide-20mg' => 885,
			'883'              => 883,
			'retatrutide-30mg' => 883,
			'2062'             => 2062,
			'glp-2t-20-mg'     => 2062,
			'2724'             => 2724,
			'glp-1-s-20-mg'    => 2724,
			'974'              => 974,
			'tesamorelin-10-mg' => 974,
			'984'              => 984,
			'pt-141-10-mg'     => 984,
			'1690'             => 1690,
			'ss-31-10-mg'      => 1690,
			'1684'             => 1684,
			'cagrilintide-10-mg' => 1684,
			'2324'             => 2324,
			'wolverine-bpc-157-tb-500-10-mg' => 2324,
		);
		$filtered = apply_filters( 'pepselect_coa_compound_slug_aliases', $aliases );
		if ( ! is_array( $filtered ) ) {
			return $aliases;
		}
		$clean = array();
		foreach ( $filtered as $slug => $id ) {
			$slug = sanitize_title( (string) $slug );
			$id   = absint( $id );
			if ( '' !== $slug && $id ) {
				$clean[ $slug ] = $id;
			}
		}
		return $clean;
	}

	/**
	 * Remembers a published compound slug when the permalink changes.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $after   Post after the update.
	 * @param \WP_Post $before  Post before the update.
	 * @return void
	 */
	public function record_former_slug( $post_id, $after, $before ) {
		if ( ! $after instanceof \WP_Post || ! $before instanceof \WP_Post ) {
			return;
		}
		if ( Post_Types::COMPOUND !== $after->post_type ) {
			return;
		}
		if ( 'publish' !== $after->post_status && 'publish' !== $before->post_status ) {
			return;
		}
		$old = sanitize_title( $before->post_name );
		$new = sanitize_title( $after->post_name );
		if ( '' === $old || $old === $new ) {
			return;
		}
		delete_post_meta( $post_id, self::FORMER_SLUG_META, $new );
		$existing = array_map( 'sanitize_title', (array) get_post_meta( $post_id, self::FORMER_SLUG_META, false ) );
		if ( ! in_array( $old, $existing, true ) ) {
			add_post_meta( $post_id, self::FORMER_SLUG_META, $old );
		}
	}

	/** Redirects a retired testing URL before the router can mark it 404. @return void */
	public function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || is_preview() ) {
			return;
		}
		$view = $this->query->view();
		if ( ! in_array( $view, array( 'compound', 'report' ), true ) ) {
			return;
		}
		$target = $this->redirect_target( $view, $this->query->compound_slug(), 'report' === $view ? $this->query->batch_slug() : '' );
		if ( '' === $target ) {
			return;
		}
		wp_safe_redirect( $target, 301 );
		exit;
	}

	/**
	 * Returns the one-hop redirect URL, or an empty string when the request must stay put.
	 *
	 * @param string $view          archive is ignored; compound or report.
	 * @param string $compound_slug Requested compound slug.
	 * @param string $batch_slug    Requested batch slug. Empty on a compound URL.
	 * @return string
	 */
	public function redirect_target( $view, $compound_slug, $batch_slug = '' ) {
		$compound_slug = sanitize_title( $compound_slug );
		$batch_slug    = sanitize_title( $batch_slug );
		if ( '' === $compound_slug || ! in_array( $view, array( 'compound', 'report' ), true ) ) {
			return '';
		}
		if ( 'compound' === $view ) {
			$batch_slug = '';
		}
		if ( $this->resolves( $view, $compound_slug, $batch_slug ) ) {
			return '';
		}

		$live       = $this->compounds->find_public_by_slug( $compound_slug );
		$historical = $live ? null : $this->historical_compound( $compound_slug );
		$named      = $live ? $live : $historical;

		if ( 'report' === $view && preg_match( '/^progress-(\d+)$/', $batch_slug, $matches ) ) {
			$progress = $this->progress_transition_target( (int) $matches[1], $named );
			if ( '' !== $progress && ! $this->same_path( $progress, $compound_slug, $batch_slug ) ) {
				return $this->append_query( $progress );
			}
		}

		if ( ! $historical ) {
			return '';
		}
		if ( 'compound' === $view ) {
			if ( ! $this->router->build_compound( $historical->post_name ) ) {
				return '';
			}
			$url = $this->view_model->compound_url( $historical );
		} else {
			if ( ! $this->router->build_report( $historical->post_name, $batch_slug ) ) {
				return '';
			}
			$url = $this->path_url( $historical->post_name, $batch_slug );
		}
		if ( $this->same_path( $url, $compound_slug, $batch_slug ) ) {
			return '';
		}
		return $this->append_query( $url );
	}

	/**
	 * Redirects progress-ID only after the stage's own batch_slug() has moved on.
	 *
	 * @param int           $test_id Test post ID parsed from the URL.
	 * @param \WP_Post|null $named   Compound named by the URL, when it is public or a known alias.
	 * @return string
	 */
	private function progress_transition_target( $test_id, $named ) {
		$test = get_post( $test_id );
		if ( ! $test || Post_Types::COA_TEST !== $test->post_type ) {
			return '';
		}
		$owner = get_post( (int) get_post_meta( $test->ID, 'compound_id', true ) );
		if ( ! $owner || ! $this->compounds->find_public_by_id( $owner->ID ) ) {
			return '';
		}
		if ( ! $named || (int) $named->ID !== (int) $owner->ID ) {
			return '';
		}
		if ( ! $this->tests->find_public_by_id( $test->ID, $owner->ID ) ) {
			return '';
		}
		$canonical = $this->view_model->batch_slug( $test );
		if ( 'progress-' . $test_id === $canonical ) {
			return '';
		}
		if ( ! $this->router->build_report( $owner->post_name, $canonical ) ) {
			return '';
		}
		return $this->view_model->test_url( $owner, $test );
	}

	/**
	 * Finds the public compound that used to own a slug.
	 *
	 * An explicit alias wins and is not chained through another stored slug.
	 * A missing explicit target does not fall through to a different compound.
	 *
	 * @param string $slug Requested slug.
	 * @return \WP_Post|null
	 */
	private function historical_compound( $slug ) {
		$slug = sanitize_title( $slug );
		if ( '' === $slug ) {
			return null;
		}
		$aliases = self::explicit_aliases();
		if ( isset( $aliases[ $slug ] ) ) {
			$post = get_post( absint( $aliases[ $slug ] ) );
			if ( $post && Post_Types::COMPOUND === $post->post_type ) {
				if ( $this->compounds->find_public_by_id( $post->ID ) && $post->post_name !== $slug ) {
					return $post;
				}
				return null;
			}
		}
		$ids = get_posts(
			array(
				'post_type'      => Post_Types::COMPOUND,
				'post_status'    => 'publish',
				'posts_per_page' => 5,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exact slug lookup, capped at five rows.
					'relation' => 'OR',
					array(
						'key'   => self::FORMER_SLUG_META,
						'value' => $slug,
					),
					array(
						'key'   => '_wp_old_slug',
						'value' => $slug,
					),
				),
			)
		);
		$ids = array_values(
			array_unique(
				array_filter(
					array_map( 'absint', (array) $ids ),
					function ( $id ) use ( $slug ) {
						$post = get_post( $id );
						return $post && $post->post_name !== $slug && $this->compounds->find_public_by_id( $id );
					}
				)
			)
		);
		if ( 1 !== count( $ids ) ) {
			return null;
		}
		return get_post( $ids[0] );
	}

	/**
	 * Returns whether the public router would render this request.
	 *
	 * @param string $view          compound or report.
	 * @param string $compound_slug Compound slug.
	 * @param string $batch_slug    Batch slug.
	 * @return bool
	 */
	private function resolves( $view, $compound_slug, $batch_slug ) {
		if ( 'compound' === $view ) {
			return (bool) $this->router->build_compound( $compound_slug );
		}
		return (bool) $this->router->build_report( $compound_slug, $batch_slug );
	}

	/**
	 * Builds the public testing path with the site's trailing-slash style.
	 *
	 * @param string $compound_slug Compound slug.
	 * @param string $batch_slug    Batch slug. Empty for the compound page.
	 * @return string
	 */
	private function path_url( $compound_slug, $batch_slug ) {
		$path = 'testing/' . $compound_slug;
		if ( '' !== $batch_slug ) {
			$path .= '/' . $batch_slug;
		}
		return home_url( user_trailingslashit( $path ) );
	}

	/**
	 * Compares request and target paths without the query string.
	 *
	 * @param string $url           Candidate URL.
	 * @param string $compound_slug Requested compound slug.
	 * @param string $batch_slug    Requested batch slug.
	 * @return bool
	 */
	private function same_path( $url, $compound_slug, $batch_slug ) {
		$left  = untrailingslashit( (string) wp_parse_url( $url, PHP_URL_PATH ) );
		$right = untrailingslashit( (string) wp_parse_url( $this->path_url( $compound_slug, $batch_slug ), PHP_URL_PATH ) );
		return '' !== $left && $left === $right;
	}

	/**
	 * Copies scalar query arguments onto the redirect target.
	 *
	 * @param string $url Target URL.
	 * @return string
	 */
	private function append_query( $url ) {
		$args = array();
		foreach ( $_GET as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Redirect preserves a public read query.
			if ( ! is_scalar( $key ) || ! is_scalar( $value ) ) {
				continue;
			}
			$key = sanitize_text_field( wp_unslash( (string) $key ) );
			if ( '' === $key ) {
				continue;
			}
			$args[ $key ] = sanitize_text_field( wp_unslash( (string) $value ) );
		}
		return $args ? add_query_arg( $args, $url ) : $url;
	}
}

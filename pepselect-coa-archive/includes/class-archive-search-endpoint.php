<?php
/**
 * COA archive typeahead search endpoint.
 *
 * Provides a public, read-only REST route that powers the live search on the
 * COA archive hero. It searches only certificate-archive data (compounds and
 * their public batch codes), never the store catalog. Two result kinds:
 *
 * - compound: a compound-and-strength entry (e.g. "GLP-3 RT 30 mg") with its
 *   public status label ("Latest record", "Testing underway", ...), linking
 *   to that compound's history page. A base query like "GLP" returns every
 *   matching strength variant.
 * - batch: an exact-or-partial public batch code, linking straight to that
 *   batch report.
 *
 * @package PepSelect\COAArchive
 */

namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and serves the COA archive typeahead endpoint.
 */
final class Archive_Search_Endpoint {

	const NAMESPACE = 'pepselect-coa/v1';
	const ROUTE     = '/search';
	const MAX       = 8;

	/**
	 * Frontend view model, for URLs and labels.
	 *
	 * @var Frontend_View_Model
	 */
	private $view_model;

	/**
	 * Visibility gate for public compounds.
	 *
	 * @var Frontend_Visibility
	 */
	private $visibility;

	/**
	 * Test repository.
	 *
	 * @var COA_Test_Repository
	 */
	private $tests;

	/**
	 * Constructor.
	 *
	 * @param Frontend_View_Model $view_model View model.
	 * @param Frontend_Visibility $visibility Visibility.
	 * @param COA_Test_Repository $tests      Test repository.
	 */
	public function __construct( Frontend_View_Model $view_model, Frontend_Visibility $visibility, COA_Test_Repository $tests ) {
		$this->view_model = $view_model;
		$this->visibility = $visibility;
		$this->tests      = $tests;
	}

	/**
	 * Hook registration.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_route' ) );
	}

	/**
	 * Register the REST route.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'handle' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'q' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Handle a search request.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( $request ) {
		$term = trim( (string) $request->get_param( 'q' ) );

		if ( mb_strlen( $term ) < 2 ) {
			return new \WP_REST_Response( array( 'results' => array() ), 200 );
		}

		$results = array();

		// Batch-code matches first: a specific code jumps to its report.
		$batch_results = $this->search_batches( $term );
		foreach ( $batch_results as $batch ) {
			$results[] = $batch;
			if ( count( $results ) >= self::MAX ) {
				return $this->respond( $results );
			}
		}

		// Compound + strength variants.
		$compound_results = $this->search_compounds( $term );
		foreach ( $compound_results as $compound ) {
			$results[] = $compound;
			if ( count( $results ) >= self::MAX ) {
				break;
			}
		}

		return $this->respond( $results );
	}

	/**
	 * Find public compounds whose name or strength matches the term.
	 *
	 * @param string $term Search term.
	 * @return array<int,array<string,string>>
	 */
	private function search_compounds( $term ) {
		$posts = get_posts(
			array(
				'post_type'      => Post_Types::COMPOUND,
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				's'              => $term,
				'no_found_rows'  => true,
				'suppress_filters' => false,
			)
		);

		// Also match on display_name / strength that core search may miss.
		$by_meta = get_posts(
			array(
				'post_type'      => Post_Types::COMPOUND,
				'post_status'    => 'publish',
				'posts_per_page' => 40,
				'no_found_rows'  => true,
				'fields'         => 'all',
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded, cached by object cache.
				'meta_query'     => array(
					'relation' => 'OR',
					array(
						'key'     => 'display_name',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			)
		);

		$merged = array();
		foreach ( array_merge( $posts, $by_meta ) as $post ) {
			$merged[ $post->ID ] = $post;
		}

		$out = array();
		foreach ( $merged as $post ) {
			if ( ! $this->visibility->is_compound_public( $post ) ) {
				continue;
			}

			$name     = (string) ( get_post_meta( $post->ID, 'display_name', true ) ?: $post->post_title );
			$strength = trim( (string) get_post_meta( $post->ID, 'strength_value', true ) . ' ' . (string) get_post_meta( $post->ID, 'strength_unit', true ) );
			$tests    = $this->tests->all_for_compound( $post->ID );
			$model    = $this->view_model->archive_compound( $post, $tests );

			$stage = isset( $model['status_workflow_stage'] ) ? (string) $model['status_workflow_stage'] : '';

			$out[] = array(
				'kind'     => 'compound',
				'name'     => $name,
				'strength' => $strength,
				'status'   => isset( $model['public_status_label'] ) ? (string) $model['public_status_label'] : '',
				// Batch numbers never change once assigned, so surface them for
				// stages where the batch is public: in lab testing and complete.
				'batch'    => in_array( $stage, array( 'in-testing', 'complete' ), true ) && isset( $model['status_batch_number'] ) ? (string) $model['status_batch_number'] : '',
				'url'      => $this->view_model->compound_url( $post ),
			);
		}

		usort(
			$out,
			static function ( $a, $b ) {
				return strcasecmp( $a['name'] . $a['strength'], $b['name'] . $b['strength'] );
			}
		);

		return $out;
	}

	/**
	 * Find public batch codes matching the term.
	 *
	 * @param string $term Search term.
	 * @return array<int,array<string,string>>
	 */
	private function search_batches( $term ) {
		$tests = get_posts(
			array(
				'post_type'      => Post_Types::COA_TEST,
				'post_status'    => 'publish',
				'posts_per_page' => 20,
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Bounded, cached.
				'meta_query'     => array(
					array(
						'key'     => 'batch_number',
						'value'   => $term,
						'compare' => 'LIKE',
					),
				),
			)
		);

		$out = array();
		foreach ( $tests as $test ) {
			$batch_number = (string) get_post_meta( $test->ID, 'batch_number', true );

			if ( '' === $batch_number ) {
				continue;
			}

			$compound = $this->resolve_compound_for_test( $test );

			if ( ! $compound || ! $this->visibility->is_compound_public( $compound ) ) {
				continue;
			}

			// Only surface batch codes that are public for their stage.
			if ( ! $this->visibility->is_test_public( $test, $compound->ID ) ) {
				continue;
			}

			$name = (string) ( get_post_meta( $compound->ID, 'display_name', true ) ?: $compound->post_title );

			$out[] = array(
				'kind'  => 'batch',
				'name'  => $name,
				'batch' => $batch_number,
				'url'   => $this->view_model->test_url( $compound, $test ),
			);
		}

		return $out;
	}

	/**
	 * Resolve the compound post a test belongs to.
	 *
	 * @param \WP_Post $test Test post.
	 * @return \WP_Post|null
	 */
	private function resolve_compound_for_test( $test ) {
		$compound_id = absint( get_post_meta( $test->ID, 'compound_id', true ) );

		if ( 0 === $compound_id ) {
			return null;
		}

		$compound = get_post( $compound_id );

		return ( $compound && Post_Types::COMPOUND === $compound->post_type ) ? $compound : null;
	}

	/**
	 * Wrap results in a response.
	 *
	 * @param array $results Results.
	 * @return \WP_REST_Response
	 */
	private function respond( $results ) {
		return new \WP_REST_Response( array( 'results' => array_slice( $results, 0, self::MAX ) ), 200 );
	}
}

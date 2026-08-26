<?php
/**
 * Compound resolver endpoint — Ops Spec §16.4.
 *
 * A narrow, AUTHENTICATED REST route for the ops app (control.pepselect.co). It
 * resolves a WooCommerce product (by ID, then SKU) to its ps_compound post so the
 * ops app can stamp ps_coa_test.compound_id without a second mapping table. The
 * link lives in `woocommerce_product_id` post meta on ps_compound, which is not
 * exposed on the core REST schema and cannot be queried by meta — hence this
 * route, backed by the existing Product_Matching lookups.
 *
 * Mirrors the shape of Archive_Search_Endpoint but shares NONE of its public
 * contract: this route is gated on edit_posts (the same capability required to
 * write a ps_coa_test), never public, and reads only the Woo↔compound relation.
 *
 * @package PepSelect\COAArchive
 */

namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and serves the product → compound resolver.
 */
final class Compound_Resolver_Endpoint {

	const NAMESPACE   = 'pepselect-coa/v1';
	const ROUTE       = '/compound';
	const ROUTE_CONNECT = '/compound/connect';
	const ROUTE_TEST  = '/coa-test';

	/**
	 * Woo ↔ compound relationship service.
	 *
	 * @var Product_Matching
	 */
	private $matching;

	/**
	 * Constructor.
	 *
	 * @param Product_Matching $matching Relationship service.
	 */
	public function __construct( Product_Matching $matching ) {
		$this->matching = $matching;
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
	 * Register the REST routes.
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
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'product_id' => array(
						'type'              => 'integer',
						'required'          => false,
						'sanitize_callback' => 'absint',
					),
					'sku'        => array(
						'type'              => 'string',
						'required'          => false,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);

		// Ops-safe equivalent of Product Matching's "Create and Connect" action.
		// Delegate to Product_Matching so its uniqueness, eligibility, locking,
		// and duplicate protections remain identical to the wp-admin workflow.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE_CONNECT,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_and_connect' ),
				'permission_callback' => array( $this, 'can_create' ),
				'args'                => array(
					'product_id' => array(
						'type'              => 'integer',
						'required'          => true,
						'sanitize_callback' => 'absint',
					),
				),
			)
		);

		// Ops Spec §16.7: the ops app addresses a ps_coa_test by its batch_number
		// META — the real key that ties a vial to its certificate — NOT the post
		// slug (owner-entered records carry numeric/auto slugs, so a slug lookup
		// always missed and created duplicates). This route returns the existing
		// post id for an exact batch_number, so the ops app updates in place.
		register_rest_route(
			self::NAMESPACE,
			self::ROUTE_TEST,
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'find_by_batch_number' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'batch_number' => array(
						'type'              => 'string',
						'required'          => true,
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
			)
		);
	}

	/**
	 * Resolve a ps_coa_test post id by its exact batch_number meta. Returns the
	 * single id when exactly one matches, plus the full list so the caller can
	 * detect and report duplicates rather than guess.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function find_by_batch_number( $request ) {
		$batch = trim( (string) $request->get_param( 'batch_number' ) );
		if ( '' === $batch ) {
			return new \WP_REST_Response( array( 'batch_number' => '', 'id' => 0, 'ids' => array() ), 200 );
		}
		$ids = get_posts(
			array(
				'post_type'      => Post_Types::COA_TEST,
				'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- Exact, bounded, cached.
				'meta_query'     => array(
					array( 'key' => 'batch_number', 'value' => $batch, 'compare' => '=' ),
				),
			)
		);
		$ids = array_values( array_map( 'absint', $ids ) );
		return new \WP_REST_Response(
			array(
				'batch_number' => $batch,
				'id'           => 1 === count( $ids ) ? $ids[0] : 0,
				'ids'          => $ids,
			),
			200
		);
	}

	/**
	 * Only users who can edit COA content may resolve compounds.
	 *
	 * @return bool
	 */
	public function can_read() {
		return current_user_can( 'edit_posts' );
	}

	/** Only compound managers may create a new archive relationship. */
	public function can_create() {
		return current_user_can( 'manage_ps_compounds' );
	}

	/**
	 * Create and connect through the plugin's canonical matching service.
	 * Existing unambiguous connections are returned unchanged, so retries are
	 * idempotent. Product_Matching returns WP_Error for every unsafe case.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_and_connect( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		if ( ! $product_id ) {
			return new \WP_Error( 'missing_product_id', __( 'A valid WooCommerce product ID is required.', 'pepselect-coa-archive' ), array( 'status' => 400 ) );
		}

		$result = $this->matching->create_and_connect( $product_id );
		if ( is_wp_error( $result ) ) {
			$result->add_data( array( 'status' => 400 ) );
			return $result;
		}

		$compound_id = absint( $result );
		return new \WP_REST_Response(
			array(
				'product_id'  => $product_id,
				'compound_id' => $compound_id,
			),
			201
		);
	}

	/**
	 * Resolve a Woo product (by ID, then SKU) to its compound(s). Returns the
	 * single compound_id when the link is unambiguous, plus the full list so the
	 * caller can detect and report ambiguity rather than guess.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function handle( $request ) {
		$product_id = absint( $request->get_param( 'product_id' ) );
		$sku        = trim( (string) $request->get_param( 'sku' ) );

		$compound_ids = array();
		if ( $product_id > 0 ) {
			$compound_ids = $this->matching->compounds_for_product( $product_id );
		}
		if ( empty( $compound_ids ) && '' !== $sku ) {
			$compound_ids = $this->matching->compounds_for_sku( $sku );
		}
		$compound_ids = array_values( array_map( 'absint', $compound_ids ) );

		return new \WP_REST_Response(
			array(
				'product_id'   => $product_id,
				'sku'          => $sku,
				'compound_id'  => 1 === count( $compound_ids ) ? $compound_ids[0] : 0,
				'compound_ids' => $compound_ids,
				'ambiguous'    => count( $compound_ids ) > 1,
			),
			200
		);
	}
}

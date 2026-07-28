<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Narrow REST surface for the ops app (control.pepselect.co) — Ops Spec §16.4.
 *
 * The ops app knows a product by its WooCommerce product ID and SKU, but the
 * link to the compound lives in `woocommerce_product_id` post meta on ps_compound,
 * which is NOT exposed on the core REST schema and cannot be queried by meta.
 * This route resolves product_id (or sku) → compound_id using the existing
 * Product_Matching lookups, so the ops app can stamp ps_coa_test.compound_id
 * without a second mapping table. Authenticated (Application Password) and gated
 * on edit_posts, matching the capability already required to write ps_coa_test.
 */
final class REST_Controller {
	const NAMESPACE = 'pepselect/v1';

	/** @var Product_Matching */
	private $matching;

	/** @param Product_Matching $matching Woo↔compound relationship service. */
	public function __construct( Product_Matching $matching ) { $this->matching = $matching; }

	/** Registers hooks. @return void */
	public function register_hooks() { add_action( 'rest_api_init', array( $this, 'register_routes' ) ); }

	/** Registers the resolver route. @return void */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE,
			'/compound',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'resolve_compound' ),
				'permission_callback' => array( $this, 'can_read' ),
				'args'                => array(
					'product_id' => array( 'type' => 'integer', 'required' => false, 'sanitize_callback' => 'absint' ),
					'sku'        => array( 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ),
				),
			)
		);
	}

	/** Only users who can edit COA content may resolve compounds. @return bool */
	public function can_read() { return current_user_can( 'edit_posts' ); }

	/**
	 * Resolves a Woo product (by ID, then SKU) to its compound(s). Returns the
	 * single compound_id when the link is unambiguous, plus the full list so the
	 * caller can detect and report ambiguity rather than guess.
	 *
	 * @param \WP_REST_Request $request Request.
	 * @return \WP_REST_Response
	 */
	public function resolve_compound( $request ) {
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

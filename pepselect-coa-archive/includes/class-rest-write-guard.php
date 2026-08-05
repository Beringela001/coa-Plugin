<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Closes core's wp/v2 write routes for the plugin's post types.
 *
 * A validated endpoint standing beside an open unvalidated one is worse than
 * having neither, because the guarantee looks real and is not. REST_Write_Endpoint
 * writes through wp_insert_post()/update_post_meta() rather than the core posts
 * controller, so rest_pre_insert_* never fires for it and the block below can be
 * unconditional.
 *
 * READS ARE UNAFFECTED. rest_pre_insert_* runs only on create and update, so
 * GET /wp/v2/ps_coa_test and GET /wp/v2/ps_compound keep working — ops reads
 * records back to confirm a write landed.
 */
final class REST_Write_Guard {
	/** @var bool */
	private static $registered = false;

	/** Registers the write block for both post types. @return void */
	public static function register_hooks() {
		if ( self::$registered ) { return; }
		self::$registered = true;
		add_filter( 'rest_pre_insert_' . Post_Types::COA_TEST, array( __CLASS__, 'block' ), 10, 2 );
		add_filter( 'rest_pre_insert_' . Post_Types::COMPOUND, array( __CLASS__, 'block' ), 10, 2 );
	}

	/**
	 * Rejects a core REST write with a pointer to the validated route.
	 *
	 * @param \stdClass        $prepared Prepared post.
	 * @param \WP_REST_Request $request  Request.
	 * @return \stdClass|\WP_Error
	 */
	public static function block( $prepared, $request ) {
		unset( $request );
		/**
		 * Escape hatch for an integration that must keep using wp/v2 writes.
		 *
		 * Returning true restores the unvalidated behaviour for BOTH post types,
		 * including every rule this plugin enforces. Intended for migrations.
		 *
		 * @param bool $allow Whether to permit core REST writes.
		 */
		if ( apply_filters( 'pepselect_coa_allow_core_rest_write', false ) ) { return $prepared; }
		return new \WP_Error(
			'pepselect_coa_write_route_required',
			sprintf(
				/* translators: 1: create route, 2: update route. */
				__( 'Direct wp/v2 writes to COA Archive records are disabled because they bypass COA validation. Use POST %1$s to create or PATCH %2$s to update. Reads are unaffected.', 'pepselect-coa-archive' ),
				'/' . REST_Write_Endpoint::NAMESPACE_V1 . REST_Write_Endpoint::ROUTE_TEST,
				'/' . REST_Write_Endpoint::NAMESPACE_V1 . REST_Write_Endpoint::ROUTE_TEST . '/<id>'
			),
			array( 'status' => 403 )
		);
	}
}

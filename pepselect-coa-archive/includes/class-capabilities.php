<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Owns the plugin's role capability definitions. */
final class Capabilities {
	/** Returns all plugin capabilities. @return string[] */
	public static function all() {
		return array(
			'manage_ps_coas',
			'edit_ps_coas',
			'create_ps_coas',
			'edit_others_ps_coas',
			'edit_private_ps_coas',
			'edit_published_ps_coas',
			'publish_ps_coas',
			'read_private_ps_coas',
			'delete_ps_coas',
			'delete_private_ps_coas',
			'delete_published_ps_coas',
			'delete_others_ps_coas',
			'manage_ps_compounds',
		);
	}

	/** Returns the complete WordPress post-type capability map. @return array */
	public static function post_type_map() {
		return array(
			'edit_post'              => 'edit_ps_coa',
			'read_post'              => 'read_ps_coa',
			'delete_post'            => 'delete_ps_coa',
			'edit_posts'             => 'edit_ps_coas',
			'edit_others_posts'      => 'edit_others_ps_coas',
			'edit_private_posts'     => 'edit_private_ps_coas',
			'edit_published_posts'   => 'edit_published_ps_coas',
			'publish_posts'          => 'publish_ps_coas',
			'read_private_posts'     => 'read_private_ps_coas',
			'delete_posts'           => 'delete_ps_coas',
			'delete_others_posts'    => 'delete_others_ps_coas',
			'delete_private_posts'   => 'delete_private_ps_coas',
			'delete_published_posts' => 'delete_published_ps_coas',
			'create_posts'           => 'create_ps_coas',
		);
	}

	/** Adds capabilities idempotently to administrators. @return void */
	public static function grant_to_administrators() {
		$role = get_role( 'administrator' );
		if ( ! $role ) { return; }
		foreach ( self::all() as $capability ) { $role->add_cap( $capability ); }
	}

	/** Repairs incomplete Administrator grants after an in-place plugin update. @return void */
	public static function ensure_administrator_capabilities() {
		$role = get_role( 'administrator' );
		if ( ! $role ) { return; }
		foreach ( self::all() as $capability ) {
			if ( ! $role->has_cap( $capability ) ) { $role->add_cap( $capability ); }
		}
	}
}

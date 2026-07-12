<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Owns the plugin's role capability definitions. */
final class Capabilities {
	/** Returns all plugin capabilities. @return string[] */
	public static function all() {
		return array( 'manage_ps_coas', 'edit_ps_coas', 'edit_others_ps_coas', 'publish_ps_coas', 'delete_ps_coas', 'delete_others_ps_coas', 'read_private_ps_coas', 'manage_ps_compounds' );
	}

	/** Adds capabilities idempotently to administrators. @return void */
	public static function grant_to_administrators() {
		$role = get_role( 'administrator' );
		if ( ! $role ) { return; }
		foreach ( self::all() as $capability ) { $role->add_cap( $capability ); }
	}
}

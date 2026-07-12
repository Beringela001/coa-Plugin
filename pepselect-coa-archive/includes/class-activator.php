<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Handles the minimal activation transaction. */
final class Activator {
	/** Registers runtime structures, grants capabilities, and flushes once. @return void */
	public static function activate() {
		( new Post_Types() )->register();
		( new Rewrites() )->register();
		Capabilities::grant_to_administrators();
		flush_rewrite_rules();
	}
}

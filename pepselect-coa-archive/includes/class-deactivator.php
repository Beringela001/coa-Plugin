<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Handles non-destructive deactivation. */
final class Deactivator {
	/** Flushes rewrites without deleting plugin data or capabilities. @return void */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}

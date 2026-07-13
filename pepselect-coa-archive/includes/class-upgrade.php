<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Runs idempotent, versioned plugin upgrades after runtime rewrites are registered. */
final class Upgrade {
	const VERSION_OPTION = 'pepselect_coa_archive_version';

	/** Flushes rewrite rules once per installed plugin version. @return void */
	public static function maybe_upgrade() {
		if ( PEPSELECT_COA_ARCHIVE_VERSION === get_option( self::VERSION_OPTION ) ) { return; }
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, PEPSELECT_COA_ARCHIVE_VERSION, false );
	}

	/** Records activation after the activation transaction has flushed rules. @return void */
	public static function mark_current() { update_option( self::VERSION_OPTION, PEPSELECT_COA_ARCHIVE_VERSION, false ); }
}

<?php
/**
 * Pep Select COA Archive uninstall handler.
 *
 * Intentionally deletes nothing. A future release may offer destructive cleanup,
 * but only behind an explicit administrator opt-in setting.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Posts, metadata, attachments, options, and capabilities are preserved by design.

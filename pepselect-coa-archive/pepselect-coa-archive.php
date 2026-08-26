<?php
/**
 * Plugin Name:       Pep Select COA Archive
 * Description:       Provides the extensible data and rewrite foundation for the Pep Select COA archive.
 * Version:           0.7.6
 * Requires at least: 6.5
 * Requires PHP:      8.1
 * Author:            Pep Select
 * Text Domain:       pepselect-coa-archive
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PEPSELECT_COA_ARCHIVE_VERSION', '0.7.6' );
define( 'PEPSELECT_COA_ARCHIVE_FILE', __FILE__ );
define( 'PEPSELECT_COA_ARCHIVE_DIR', plugin_dir_path( __FILE__ ) );

require_once PEPSELECT_COA_ARCHIVE_DIR . 'includes/helpers.php';

spl_autoload_register(
	static function ( $class ) {
		$prefix = 'PepSelect\\COAArchive\\';
		if ( 0 !== strpos( $class, $prefix ) ) {
			return;
		}

		$relative = substr( $class, strlen( $prefix ) );
		$file     = PEPSELECT_COA_ARCHIVE_DIR . 'includes/class-' . strtolower( str_replace( '_', '-', $relative ) ) . '.php';
		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
);

register_activation_hook( __FILE__, array( 'PepSelect\\COAArchive\\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'PepSelect\\COAArchive\\Deactivator', 'deactivate' ) );

PepSelect\COAArchive\Plugin::instance()->run();

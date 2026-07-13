<?php
/**
 * Shared helper functions.
 *
 * @package PepSelect_COA_Archive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Returns the plugin version.
 *
 * @return string
 */
function pepselect_coa_archive_version() {
	return PEPSELECT_COA_ARCHIVE_VERSION;
}

/**
 * Locates a child-theme, parent-theme, or plugin COA template.
 *
 * @param string $template Relative template path.
 * @return string
 */
function pepselect_coa_template_path( $template ) {
	$template = ltrim( str_replace( array( '..', '\\' ), '', (string) $template ), '/' );
	foreach ( array( trailingslashit( get_stylesheet_directory() ) . 'pepselect-coa/' . $template, trailingslashit( get_template_directory() ) . 'pepselect-coa/' . $template, PEPSELECT_COA_ARCHIVE_DIR . 'templates/' . $template ) as $candidate ) {
		if ( is_readable( $candidate ) ) { return $candidate; }
	}
	return PEPSELECT_COA_ARCHIVE_DIR . 'templates/' . $template;
}

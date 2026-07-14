<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Reads and sanitizes the strongly prefixed frontend query context. */
final class Frontend_Query {
	/** Returns the requested view or an empty string. @return string */
	public function view() {
		$view = sanitize_key( (string) get_query_var( 'ps_coa_view' ) );
		return in_array( $view, array( 'archive', 'compound', 'report' ), true ) ? $view : '';
	}

	/** Returns a sanitized compound slug. @return string */
	public function compound_slug() { return sanitize_title( (string) get_query_var( 'ps_compound_slug' ) ); }

	/** Returns a sanitized batch slug. @return string */
	public function batch_slug() { return sanitize_title( (string) get_query_var( 'ps_batch_slug' ) ); }

	/** Returns the current public page number. @return int */
	public function page() { return max( 1, absint( get_query_var( 'paged', 1 ) ) ); }
}

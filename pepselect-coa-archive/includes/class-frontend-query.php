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

	/** Returns the sanitized archive search term. @return string */
	public function search() {
		return isset( $_GET['coa_search'] ) ? self::normalize_search( $_GET['coa_search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	/** Normalizes request/search input; invalid or empty values become no search. @param mixed $value Search input. @return string */
	public static function normalize_search( $value ) {
		if ( ! is_scalar( $value ) ) { return ''; }
		return trim( sanitize_text_field( wp_unslash( (string) $value ) ) );
	}
}

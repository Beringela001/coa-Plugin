<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Preserves exact printed QR destinations when a public batch URL changes. */
final class QR_Redirects {
	private const NAD500_PRINTED_PATH = '/testing/nad-500-mg/progress-1269/';
	private const NAD500_BATCH_PATH   = '/testing/nad-500-mg/nd50026205jp/';

	/** Registers the isolated public redirect before normal template handling. */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
	}

	/** Permanently redirects only the printed NAD500 QR path. */
	public function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return; }
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$destination = $this->destination_for_path( $path );
		if ( ! $destination ) { return; }
		wp_safe_redirect( $destination, 301, 'Pep Select COA QR correction' );
		exit;
	}

	/** Returns a destination for the one approved path and nothing else. */
	public function destination_for_path( $path ) {
		if ( ! is_string( $path ) || '' === $path ) { return ''; }
		$source_path = wp_parse_url( home_url( self::NAD500_PRINTED_PATH ), PHP_URL_PATH );
		if ( ! is_string( $source_path ) || untrailingslashit( $path ) !== untrailingslashit( $source_path ) ) { return ''; }
		return home_url( self::NAD500_BATCH_PATH );
	}
}

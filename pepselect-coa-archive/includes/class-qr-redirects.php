<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Preserves exact public destinations when an approved COA URL changes. */
final class QR_Redirects {
	private const REDIRECTS = array(
		'/testing/nad-500-mg/progress-1269/' => '/testing/nad-500-mg/nd50026205js/',
		'/testing/nad-500-mg/nd50026205jp/' => '/testing/nad-500-mg/nd50026205js/',
		'/testing/961/'                       => '/testing/retatrutide-10mg/',
		'/testing/961/rt2026205jp/'           => '/testing/retatrutide-10mg/rt2026205jp/',
	);

	/** Registers the isolated public redirect before normal template handling. */
	public function register_hooks() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
	}

	/** Permanently redirects only the approved exact legacy paths. */
	public function maybe_redirect() {
		if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return; }
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( (string) $_SERVER['REQUEST_URI'] ) : '';
		$path        = wp_parse_url( $request_uri, PHP_URL_PATH );
		$destination = $this->destination_for_path( $path );
		if ( ! $destination ) { return; }
		wp_safe_redirect( $destination, 301, 'Pep Select COA URL correction' );
		exit;
	}

	/** Returns a destination for an approved exact path and nothing else. */
	public function destination_for_path( $path ) {
		if ( ! is_string( $path ) || '' === $path ) { return ''; }
		foreach ( self::REDIRECTS as $source => $destination ) {
			$source_path = wp_parse_url( home_url( $source ), PHP_URL_PATH );
			if ( is_string( $source_path ) && untrailingslashit( $path ) === untrailingslashit( $source_path ) ) {
				return home_url( $destination );
			}
		}
		return '';
	}
}

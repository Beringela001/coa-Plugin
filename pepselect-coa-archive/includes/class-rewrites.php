<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Registers narrowly scoped public COA routes and query variables. */
final class Rewrites {
	/** Registers archive, compound, and report routes. @return void */
	public function register() {
		add_rewrite_tag( '%ps_coa_view%', '(archive|compound|report)' );
		add_rewrite_tag( '%ps_compound_slug%', '([^/]+)' );
		add_rewrite_tag( '%ps_batch_slug%', '([^/]+)' );
		add_rewrite_rule( '^testing/?$', 'index.php?pagename=testing&ps_coa_view=archive', 'top' );
		add_rewrite_rule( '^testing/([^/]+)/?$', 'index.php?pagename=testing/$matches[1]&ps_coa_view=compound&ps_compound_slug=$matches[1]', 'top' );
		add_rewrite_rule( '^testing/([^/]+)/([^/]+)/?$', 'index.php?pagename=testing/$matches[1]/$matches[2]&ps_coa_view=report&ps_compound_slug=$matches[1]&ps_batch_slug=$matches[2]', 'top' );
	}

	/** Adds only strongly prefixed public query variables. @param string[] $variables Query vars. @return string[] */
	public function register_query_vars( $variables ) {
		return array_values( array_unique( array_merge( $variables, array( 'ps_coa_view', 'ps_compound_slug', 'ps_batch_slug' ) ) ) );
	}
}

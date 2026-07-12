<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Registers non-destructive rewrite placeholders for future nested COA URLs. */
final class Rewrites {
	/** Registers future query variables and the nested route. @return void */
	public function register() {
		add_rewrite_tag( '%ps_compound_slug%', '([^&]+)' );
		add_rewrite_tag( '%ps_batch_slug%', '([^&]+)' );
		add_rewrite_rule( '^testing/([^/]+)/([^/]+)/?$', 'index.php?ps_compound_slug=$matches[1]&ps_batch_slug=$matches[2]', 'top' );
	}
}

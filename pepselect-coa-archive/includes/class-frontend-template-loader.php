<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Loads theme-overridable templates, shortcodes, scoped CSS, and canonicals. */
final class Frontend_Template_Loader {
	/** @var Frontend_Router */ private $router;
	/** @var bool */ private $archive_rendered = false;

	public function __construct( Frontend_Router $router ) { $this->router = $router; }

	public function register_hooks() {
		add_filter( 'template_include', array( $this, 'template_include' ), 50 );
		add_filter( 'redirect_canonical', array( $this, 'filter_redirect_canonical' ), 10, 2 );
		add_filter( 'the_content', array( $this, 'append_archive_to_page' ), 99 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'output_canonical' ), 1 );
		add_filter( 'wpseo_canonical', array( $this, 'filter_canonical' ) );
		add_filter( 'rank_math/frontend/canonical', array( $this, 'filter_canonical' ) );
		add_filter( 'seopress_titles_canonical', array( $this, 'filter_canonical' ) );
		add_shortcode( 'pepselect_coa_archive', array( $this, 'archive_shortcode' ) );
		add_shortcode( 'pepselect_compound_history', array( $this, 'compound_shortcode' ) );
		add_shortcode( 'pepselect_coa_report', array( $this, 'report_shortcode' ) );
	}

	/** Selects the plugin or theme override for non-page-shell routes. */
	public function template_include( $template ) {
		if ( $this->router->is_404() ) { $not_found = get_404_template(); return $not_found ?: $template; }
		if ( ! $this->router->is_route() ) { return $template; }
		$context = $this->router->context();
		if ( ! $context || ! empty( $context['page_shell'] ) ) { return $template; }
		return $this->locate( $context['template'] );
	}

	/** Uses an existing published /testing/ page as an unmodified runtime shell. */
	public function append_archive_to_page( $content ) {
		$context = $this->router->context();
		if ( ! $context || empty( $context['page_shell'] ) || ! is_main_query() || ! in_the_loop() || $this->archive_rendered ) { return $content; }
		return $content . $this->render( 'archive-testing.php', $context );
	}

	/** Enqueues minimal structural CSS only for routes or posts containing COA shortcodes. */
	public function enqueue_assets() {
		$post = get_queried_object(); $content = $post instanceof \WP_Post ? $post->post_content : '';
		$shortcode = has_shortcode( $content, 'pepselect_coa_archive' ) || has_shortcode( $content, 'pepselect_compound_history' ) || has_shortcode( $content, 'pepselect_coa_report' );
		if ( ! $this->router->is_route() && ! $shortcode ) { return; }
		wp_enqueue_style( 'pepselect-coa-frontend', plugins_url( 'assets/css/coa-frontend.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
	}

	public function archive_shortcode() {
		$this->ensure_assets();
		$this->archive_rendered = true; $context = $this->router->context();
		if ( ! $context || 'archive' !== $context['view'] ) { $context = $this->router->build_archive( 1 ); }
		return $this->render( 'archive-testing.php', $context );
	}

	public function compound_shortcode( $attributes ) {
		$this->ensure_assets();
		$attributes = shortcode_atts( array( 'compound' => '', 'id' => 0 ), $attributes, 'pepselect_compound_history' );
		$context = $this->router->context();
		if ( ! $context || 'compound' !== $context['view'] ) { $context = $this->router->build_compound( sanitize_title( $attributes['compound'] ), absint( $attributes['id'] ), 1 ); }
		return $context ? $this->render( 'single-compound-history.php', $context ) : '';
	}

	public function report_shortcode( $attributes ) {
		$this->ensure_assets();
		$attributes = shortcode_atts( array( 'compound_id' => 0, 'test_id' => 0 ), $attributes, 'pepselect_coa_report' );
		$context = $this->router->context();
		if ( ! $context || 'report' !== $context['view'] ) { $context = $this->router->build_report_by_ids( absint( $attributes['compound_id'] ), absint( $attributes['test_id'] ) ); }
		return $context ? $this->render( 'single-coa-report.php', $context ) : '';
	}

	/** Finds child theme, parent theme, then plugin template. @param string $template Relative template. @return string */
	public function locate( $template ) {
		return pepselect_coa_template_path( $template );
	}

	public function filter_canonical( $canonical ) { $url = $this->router->canonical_url(); return $url ?: $canonical; }
	public function filter_redirect_canonical( $redirect_url, $requested_url ) { unset( $requested_url ); return $this->router->is_route() ? false : $redirect_url; }

	/** Outputs a fallback canonical only when core/known SEO integrations will not. */
	public function output_canonical() {
		$url = $this->router->canonical_url();
		if ( ! $url || is_singular() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'SURERANK_VERSION' ) || class_exists( 'SureRank\\Inc\\Frontend\\Meta_Tag_Injection' ) ) { return; }
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $url ) );
	}

	private function render( $template, $context ) {
		if ( ! $context ) { return ''; }
		$ps_context = $context; $ps_embedded = true;
		ob_start(); include $this->locate( $template ); return (string) ob_get_clean();
	}

	private function ensure_assets() {
		wp_enqueue_style( 'pepselect-coa-frontend', plugins_url( 'assets/css/coa-frontend.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		if ( did_action( 'wp_head' ) && ! wp_style_is( 'pepselect-coa-frontend', 'done' ) ) { wp_print_styles( 'pepselect-coa-frontend' ); }
	}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Coordinates the product-specific COA shortcode without changing WooCommerce templates. */
final class Product_COA_Carousel {
	/** @var Product_Matching */ private $matching;
	/** @var Compound_Repository */ private $compounds;
	/** @var COA_Test_Repository */ private $tests;
	/** @var Frontend_View_Model */ private $view_model;
	/** @var string[] */ private $rendered_instances = array();
	/** @var bool */ private $variables_added = false;

	public function __construct( Product_Matching $matching, Compound_Repository $compounds, COA_Test_Repository $tests, Frontend_View_Model $view_model ) {
		$this->matching = $matching;
		$this->compounds = $compounds;
		$this->tests = $tests;
		$this->view_model = $view_model;
	}

	/** Registers the Elementor-compatible shortcode. @return void */
	public function register_hooks() { add_shortcode( 'pepselect_product_coa_carousel', array( $this, 'shortcode' ) ); }

	/** Renders the full history carousel or one compact summary for the exact connected product. @param array $attributes Shortcode attributes. @return string */
	public function shortcode( $attributes = array() ) {
		$attributes = shortcode_atts( array( 'product_id' => 0, 'variant' => 'carousel' ), $attributes, 'pepselect_product_coa_carousel' );
		$variant_attribute = is_scalar( $attributes['variant'] ) ? (string) $attributes['variant'] : '';
		$variant = 'compact' === sanitize_key( $variant_attribute ) ? 'compact' : 'carousel';
		$product_id = $this->resolve_product_id( absint( $attributes['product_id'] ) );
		$instance_key = $variant . ':' . $product_id;
		if ( ! $product_id || in_array( $instance_key, $this->rendered_instances, true ) ) { return ''; }

		$compound_ids = array_values( array_unique( array_map( 'absint', $this->matching->compounds_for_product( $product_id ) ) ) );
		if ( 1 !== count( $compound_ids ) ) { return ''; }
		$compound = $this->compounds->find_public_by_id( $compound_ids[0] );
		if ( ! $compound ) { return ''; }

		$records = $this->tests->for_product_carousel( $compound->ID );
		$documented = array();
		foreach ( $records['documented'] as $test ) {
			$report = $this->view_model->product_carousel_report( $test, $compound );
			if ( ! $report ) { continue; }
			$documented[] = $report;
			if ( 4 === count( $documented ) ) { break; }
		}

		$reports = array();
		if ( $documented ) {
			$lead = array_shift( $documented );
			$lead['role'] = 'current';
			$lead['role_label'] = $lead['is_current'] ? __( 'Current Batch', 'pepselect-coa-archive' ) : __( 'Latest Report', 'pepselect-coa-archive' );
			$reports[] = $lead;
		}
		if ( $records['incoming'] ) {
			$incoming = $this->view_model->product_carousel_incoming( $records['incoming'], $compound );
			if ( $incoming ) { $reports[] = $incoming; }
		}
		foreach ( $documented as $previous ) {
			if ( 4 === count( $reports ) ) { break; }
			$previous['role'] = 'previous';
			$previous['role_label'] = __( 'Previous Report', 'pepselect-coa-archive' );
			$reports[] = $previous;
		}
		if ( ! $reports ) { return ''; }

		$this->rendered_instances[] = $instance_key;
		$this->ensure_assets();
		if ( 'compact' === $variant ) {
			return $this->render_compact( array(
				'product_id' => $product_id,
				'compound_id' => $compound->ID,
				'report' => $reports[0],
			) );
		}

		return $this->render( array(
			'instance_id' => wp_unique_id( 'ps-coa-product-carousel-' ),
			'product_id' => $product_id,
			'compound_id' => $compound->ID,
			'reports' => $reports,
			'eyebrow' => Design_Settings::copy( 'product_carousel_eyebrow' ),
			'heading' => Design_Settings::copy( 'product_carousel_title' ),
			'intro' => Design_Settings::copy( 'product_carousel_intro' ),
		) );
	}

	/** Resolves only a published product in a real single-product request. @return int */
	private function resolve_product_id( $requested_id ) {
		if ( is_admin() || ! $this->matching->is_available() || ! is_singular( 'product' ) ) { return 0; }
		$product_id = $requested_id ?: absint( get_queried_object_id() );
		$product = $this->matching->product( $product_id );
		return $product && 'publish' === $product->post_status ? $product->ID : 0;
	}

	/** Loads the dedicated assets only after report output is known to exist. @return void */
	private function ensure_assets() {
		wp_enqueue_style( 'pepselect-coa-product-carousel', plugins_url( 'assets/css/pepselect-coa-product-carousel.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		if ( ! $this->variables_added ) { wp_add_inline_style( 'pepselect-coa-product-carousel', Design_Settings::inline_css() ); $this->variables_added = true; }
		$dependencies = wp_script_is( 'elementor-frontend', 'registered' ) ? array( 'elementor-frontend' ) : array();
		wp_enqueue_script( 'pepselect-coa-product-carousel', plugins_url( 'assets/js/pepselect-coa-product-carousel.js', PEPSELECT_COA_ARCHIVE_FILE ), $dependencies, PEPSELECT_COA_ARCHIVE_VERSION, true );
		if ( did_action( 'wp_head' ) && ! wp_style_is( 'pepselect-coa-product-carousel', 'done' ) ) { wp_print_styles( 'pepselect-coa-product-carousel' ); }
	}

	/** Renders a theme-overridable product carousel template. @return string */
	private function render( $context ) {
		$ps_product_carousel = $context;
		ob_start();
		include pepselect_coa_template_path( 'shortcodes/product-coa-carousel.php' );
		return (string) ob_get_clean();
	}

	/** Renders the compact purchase-area summary from the same report model as the full carousel. */
	private function render_compact( $context ) {
		$ps_product_summary = $context;
		ob_start();
		include pepselect_coa_template_path( 'shortcodes/product-coa-summary.php' );
		return (string) ob_get_clean();
	}
}

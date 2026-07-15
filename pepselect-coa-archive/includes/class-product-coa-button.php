<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Keeps the existing Elementor product COA button synchronized with public COA data. */
final class Product_COA_Button {
	const ACF_FIELD_NAME = 'view_latest_co';

	/** @var Product_Matching */ private $matching;
	/** @var Compound_Repository */ private $compounds;
	/** @var COA_Test_Repository */ private $tests;
	/** @var Frontend_View_Model */ private $view_model;

	public function __construct( Product_Matching $matching, Compound_Repository $compounds, COA_Test_Repository $tests, Frontend_View_Model $view_model ) {
		$this->matching = $matching;
		$this->compounds = $compounds;
		$this->tests = $tests;
		$this->view_model = $view_model;
	}

	/** Registers only the exact existing ACF-backed Elementor button integration. @return void */
	public function register_hooks() {
		add_filter( 'acf/load_value/name=' . self::ACF_FIELD_NAME, array( $this, 'filter_acf_url' ), 20, 3 );
		add_filter( 'elementor/widget/render_content', array( $this, 'filter_widget_content' ), 20, 2 );
	}

	/** Replaces any saved or stale product URL with the canonical public destination. @return string */
	public function filter_acf_url( $value, $post_id, $field = array() ) {
		unset( $value, $field );
		$destination = $this->destination_for_product( $this->normalize_product_id( $post_id ) );
		return $destination ? $destination['url'] : '';
	}

	/** Updates or hides the existing button while preserving Elementor's approved markup and styles. @return string */
	public function filter_widget_content( $content, $widget ) {
		if ( ! $this->is_target_widget( $widget ) ) { return $content; }
		$destination = $this->destination_for_product( absint( get_queried_object_id() ) );
		if ( ! $destination ) { return ''; }

		$content = $this->replace_button_url( $content, $destination['url'] );
		$pattern = '/(<span\b[^>]*class=(["\'])[^"\']*\belementor-button-text\b[^"\']*\2[^>]*>).*?(<\/span>)/is';
		$updated = preg_replace( $pattern, '$1' . esc_html( $destination['label'] ) . '$3', $content, 1 );
		return is_string( $updated ) ? $updated : $content;
	}

	/** Resolves Current/Latest first, then one public Incoming record, for one exact product link. @return array */
	public function destination_for_product( $product_id ) {
		$product_id = absint( $product_id );
		$product = $this->matching->product( $product_id );
		if ( ! $product || 'publish' !== $product->post_status || ! $this->matching->is_available() ) { return array(); }

		$compound_ids = array_values( array_unique( array_map( 'absint', $this->matching->compounds_for_product( $product_id ) ) ) );
		if ( 1 !== count( $compound_ids ) ) { return array(); }
		$compound = $this->compounds->find_public_by_id( $compound_ids[0] );
		if ( ! $compound ) { return array(); }

		$records = $this->tests->for_product_carousel( $compound->ID );
		foreach ( $records['documented'] as $test ) {
			$report = $this->view_model->product_carousel_report( $test, $compound );
			if ( $report && ! empty( $report['detail_url'] ) ) {
				return array( 'kind' => 'report', 'label' => __( 'View Latest COA', 'pepselect-coa-archive' ), 'url' => $report['detail_url'], 'test_id' => $test->ID, 'compound_id' => $compound->ID );
			}
		}

		if ( $records['incoming'] ) {
			$incoming = $this->view_model->product_carousel_incoming( $records['incoming'], $compound );
			if ( $incoming && ! empty( $incoming['detail_url'] ) ) {
				return array( 'kind' => 'incoming', 'label' => __( 'View Vetting Status', 'pepselect-coa-archive' ), 'url' => $incoming['detail_url'], 'test_id' => $records['incoming']->ID, 'compound_id' => $compound->ID );
			}
		}
		return array();
	}

	/** Targets only a Button widget whose dynamic link consumes the known ACF URL field. @return bool */
	private function is_target_widget( $widget ) {
		if ( ! is_object( $widget ) || ! method_exists( $widget, 'get_name' ) || 'button' !== $widget->get_name() || ! method_exists( $widget, 'get_settings' ) ) { return false; }
		$settings = $widget->get_settings();
		$dynamic = isset( $settings['__dynamic__']['link'] ) ? (string) $settings['__dynamic__']['link'] : '';
		if ( '' === $dynamic && method_exists( $widget, 'get_data' ) ) {
			$data = $widget->get_data();
			$dynamic = isset( $data['settings']['__dynamic__']['link'] ) ? (string) $data['settings']['__dynamic__']['link'] : '';
		}
		return false !== strpos( $dynamic, self::ACF_FIELD_NAME );
	}

	/** Changes only the first anchor destination in the already-rendered target widget. @return string */
	private function replace_button_url( $content, $url ) {
		if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
			$processor = new \WP_HTML_Tag_Processor( $content );
			if ( $processor->next_tag( 'a' ) ) { $processor->set_attribute( 'href', esc_url( $url ) ); return $processor->get_updated_html(); }
		}
		return preg_replace_callback( '/(<a\b[^>]*\bhref=)([\"\']).*?\2/i', static function ( $match ) use ( $url ) { return $match[1] . $match[2] . esc_url( $url ) . $match[2]; }, $content, 1 );
	}

	/** Accepts the integer and post_123 forms ACF can supply. @return int */
	private function normalize_product_id( $post_id ) {
		if ( is_numeric( $post_id ) ) { return absint( $post_id ); }
		return preg_match( '/(?:^|_)(\d+)$/', (string) $post_id, $match ) ? absint( $match[1] ) : 0;
	}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Loads theme-overridable templates, shortcodes, scoped CSS, and canonicals. */
final class Frontend_Template_Loader {
	/** @var Frontend_Router */ private $router;
	/** @var bool */ private $variables_added = false;

	public function __construct( Frontend_Router $router ) { $this->router = $router; }

	public function register_hooks() {
		add_filter( 'template_include', array( $this, 'template_include' ), 50 );
		add_filter( 'redirect_canonical', array( $this, 'filter_redirect_canonical' ), 10, 2 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_head', array( $this, 'output_canonical' ), 1 );
		add_filter( 'pre_get_document_title', array( $this, 'filter_title' ), 20 );
		add_filter( 'wpseo_title', array( $this, 'filter_title' ), 20 );
		add_filter( 'wpseo_metadesc', array( $this, 'filter_description' ), 20 );
		add_filter( 'wpseo_opengraph_title', array( $this, 'filter_title' ), 20 );
		add_filter( 'wpseo_opengraph_desc', array( $this, 'filter_description' ), 20 );
		add_filter( 'wpseo_twitter_title', array( $this, 'filter_title' ), 20 );
		add_filter( 'wpseo_twitter_description', array( $this, 'filter_description' ), 20 );
		add_filter( 'wpseo_schema_graph', array( $this, 'filter_schema_graph' ), 20, 2 );
		add_action( 'wp_head', array( $this, 'output_fallback_seo' ), 2 );
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
		if ( ! $context ) { return $template; }
		return $this->locate( $context['template'] );
	}

	/** Enqueues scoped presentation assets only for routes or posts containing COA shortcodes. */
	public function enqueue_assets() {
		$post = get_queried_object(); $content = $post instanceof \WP_Post ? $post->post_content : '';
		$shortcode = has_shortcode( $content, 'pepselect_coa_archive' ) || has_shortcode( $content, 'pepselect_compound_history' ) || has_shortcode( $content, 'pepselect_coa_report' );
		if ( ! $this->router->is_route() && ! $shortcode ) { return; }
		$context = $this->router->context();
		$is_archive = $context && isset( $context['view'] ) && 'archive' === $context['view'];
		$this->ensure_assets( $this->context_has_gallery( $context ), $this->context_has_history_carousel( $context ), $is_archive );
	}

	public function archive_shortcode() {
		$context = $this->router->context();
		$search = isset( $_GET['coa_search'] ) ? Frontend_Query::normalize_search( $_GET['coa_search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! $context || 'archive' !== $context['view'] ) { $context = $this->router->build_archive( 1, $search ); }
		$this->ensure_assets( false, false, true );
		return $this->render( 'archive-testing.php', $context );
	}

	public function compound_shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'compound' => '', 'id' => 0 ), $attributes, 'pepselect_compound_history' );
		$context = $this->router->context();
		if ( ! $context || 'compound' !== $context['view'] ) { $context = $this->router->build_compound( sanitize_title( $attributes['compound'] ), absint( $attributes['id'] ), 1 ); }
		$this->ensure_assets( false, $this->context_has_history_carousel( $context ) );
		return $context ? $this->render( 'single-compound-history.php', $context ) : '';
	}

	public function report_shortcode( $attributes ) {
		$attributes = shortcode_atts( array( 'compound_id' => 0, 'test_id' => 0 ), $attributes, 'pepselect_coa_report' );
		$context = $this->router->context();
		if ( ! $context || 'report' !== $context['view'] ) { $context = $this->router->build_report_by_ids( absint( $attributes['compound_id'] ), absint( $attributes['test_id'] ) ); }
		$this->ensure_assets( $this->context_has_gallery( $context ) );
		return $context ? $this->render( 'single-coa-report.php', $context ) : '';
	}

	/** Finds child theme, parent theme, then plugin template. @param string $template Relative template. @return string */
	public function locate( $template ) {
		return pepselect_coa_template_path( $template );
	}

	public function filter_canonical( $canonical ) { $url = $this->router->canonical_url(); return $url ?: $canonical; }
	public function filter_redirect_canonical( $redirect_url, $requested_url ) { unset( $requested_url ); return $this->router->is_route() ? false : $redirect_url; }

	/** Supplies route-aware titles without changing any visible archive copy. */
	public function filter_title( $title ) {
		$seo = $this->seo_data();
		return $seo ? $seo['title'] : $title;
	}

	/** Supplies factual route-aware meta descriptions from already-public data. */
	public function filter_description( $description ) {
		$seo = $this->seo_data();
		return $seo ? $seo['description'] : $description;
	}

	/** Adds the missing COA WebPage and breadcrumb pieces to Yoast's graph. */
	public function filter_schema_graph( $graph, $yoast_context = null ) {
		unset( $yoast_context );
		$pieces = $this->schema_pieces();
		if ( ! $pieces ) { return $graph; }
		foreach ( $pieces as $piece ) {
			$existing_ids = array_filter( wp_list_pluck( $graph, '@id' ) );
			$index = array_search( $piece['@id'], $existing_ids, true );
			if ( false === $index ) {
				$graph[] = $piece;
			} elseif ( isset( $piece['mainEntity'] ) ) {
				$graph[ $index ]['mainEntity'] = $piece['mainEntity'];
			}
		}
		return $graph;
	}

	/** Outputs metadata only when a supported SEO plugin is not already presenting it. */
	public function output_fallback_seo() {
		$seo = $this->seo_data();
		if ( ! $seo ) { return; }
		$known_seo = defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'SURERANK_VERSION' );
		if ( ! $known_seo ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $seo['description'] ) );
			printf( "<script type=\"application/ld+json\">%s</script>\n", wp_json_encode( array( '@context' => 'https://schema.org', '@graph' => $this->schema_pieces() ), JSON_UNESCAPED_SLASHES ) );
		}
	}

	/** Outputs a fallback canonical only when core/known SEO integrations will not. */
	public function output_canonical() {
		$url = $this->router->canonical_url();
		if ( ! $url || is_singular() || defined( 'WPSEO_VERSION' ) || defined( 'RANK_MATH_VERSION' ) || defined( 'SEOPRESS_VERSION' ) || defined( 'SURERANK_VERSION' ) || class_exists( 'SureRank\\Inc\\Frontend\\Meta_Tag_Injection' ) ) { return; }
		printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $url ) );
	}

	/** Returns the route's title, description, and breadcrumb labels. */
	private function seo_data() {
		$context = $this->router->context();
		if ( ! $this->router->is_route() || $this->router->is_404() || empty( $context['view'] ) ) { return array(); }
		$site = get_bloginfo( 'name' ) ?: 'Pep Select';
		$archive_label = __( 'Certificate of Analysis Archive', 'pepselect-coa-archive' );
		if ( 'archive' === $context['view'] ) {
			return array(
				'title' => sprintf( __( 'Peptide COA Archive: Search by Compound & Batch | %s', 'pepselect-coa-archive' ), $site ),
				'description' => __( 'Browse Pep Select Certificates of Analysis and batch documentation by compound and batch.', 'pepselect-coa-archive' ),
				'breadcrumbs' => array( $archive_label ),
			);
		}
		$name = isset( $context['compound']['display_name'] ) ? trim( (string) $context['compound']['display_name'] ) : '';
		if ( ! $name ) { return array(); }
		if ( 'compound' === $context['view'] ) {
			return array(
				'title' => sprintf( __( '%1$s COAs & Batch History | %2$s', 'pepselect-coa-archive' ), $name, $site ),
				'description' => sprintf( __( 'View Pep Select Certificates of Analysis and batch documentation for %s.', 'pepselect-coa-archive' ), $name ),
				'breadcrumbs' => array( $archive_label, $name ),
			);
		}
		$batch = isset( $context['test']['batch_number'] ) ? trim( (string) $context['test']['batch_number'] ) : '';
		$label = $batch ? sprintf( __( 'Batch %s COA', 'pepselect-coa-archive' ), $batch ) : __( 'Batch Documentation', 'pepselect-coa-archive' );
		return array(
			'title' => $batch
				? sprintf( __( '%1$s Batch %2$s Lab Report | %3$s', 'pepselect-coa-archive' ), $name, $batch, $site )
				: sprintf( __( '%1$s Batch Documentation | %2$s', 'pepselect-coa-archive' ), $name, $site ),
			'description' => $batch
				? sprintf( __( 'View the Pep Select Certificate of Analysis and batch documentation for %1$s, batch %2$s.', 'pepselect-coa-archive' ), $name, $batch )
				: sprintf( __( 'View Pep Select batch documentation for %s.', 'pepselect-coa-archive' ), $name ),
			'breadcrumbs' => array( $archive_label, $name, $label ),
		);
	}

	/** Builds connected, public-only Schema.org pieces for the virtual routes. */
	private function schema_pieces() {
		$seo = $this->seo_data();
		$canonical = $this->router->canonical_url();
		if ( ! $seo || ! $canonical ) { return array(); }
		$breadcrumb_id = trailingslashit( $canonical ) . '#breadcrumb';
		$items = array( array( '@type' => 'ListItem', 'position' => 1, 'name' => 'Home', 'item' => home_url( '/' ) ) );
		$archive_url = home_url( user_trailingslashit( 'testing' ) );
		foreach ( $seo['breadcrumbs'] as $index => $label ) {
			$item = array( '@type' => 'ListItem', 'position' => $index + 2, 'name' => $label );
			if ( 0 === $index ) { $item['item'] = $archive_url; }
			elseif ( $index < count( $seo['breadcrumbs'] ) - 1 ) {
				$context = $this->router->context();
				$item['item'] = isset( $context['compound']['url'] ) ? $context['compound']['url'] : $canonical;
			}
			$items[] = $item;
		}
		$context = $this->router->context();
		$webpage = array(
				'@type' => 'archive' === $context['view'] ? 'CollectionPage' : 'WebPage',
				'@id' => trailingslashit( $canonical ) . '#webpage',
				'url' => $canonical,
				'name' => $seo['title'],
				'description' => $seo['description'],
				'isPartOf' => array( '@id' => trailingslashit( home_url( '/' ) ) . '#website' ),
				'breadcrumb' => array( '@id' => $breadcrumb_id ),
				'inLanguage' => get_bloginfo( 'language' ),
			);
		$dataset = $this->report_dataset_piece( $context, $canonical );
		if ( $dataset ) {
			$webpage['mainEntity'] = array( '@id' => $dataset['@id'] );
		}
		$pieces = array(
			$webpage,
			array( '@type' => 'BreadcrumbList', '@id' => $breadcrumb_id, 'itemListElement' => $items ),
		);
		if ( $dataset ) { $pieces[] = $dataset; }
		return $pieces;
	}

	/** Builds a truthful Dataset entity from completed, public report values. */
	private function report_dataset_piece( $context, $canonical ) {
		if ( 'report' !== $context['view'] || empty( $context['test'] ) || 'complete' !== $context['test']['workflow_stage'] ) {
			return array();
		}

		$test  = $context['test'];
		$name  = isset( $context['compound']['display_name'] ) ? trim( (string) $context['compound']['display_name'] ) : '';
		$batch = isset( $test['batch_number'] ) ? trim( (string) $test['batch_number'] ) : '';
		if ( ! $name || ! $batch ) { return array(); }

		$variables = array();
		$this->add_dataset_variable( $variables, __( 'Claimed content', 'pepselect-coa-archive' ), $test['claimed_content_display'], $test['content_unit'] );
		$this->add_dataset_variable( $variables, __( 'Average net content', 'pepselect-coa-archive' ), $test['average_net_content_display'], $test['content_unit'] );
		$this->add_dataset_variable( $variables, __( 'Purity', 'pepselect-coa-archive' ), $test['purity_percentage_display'], '%' );
		if ( ! empty( $test['identity_status']['value'] ) ) { $this->add_dataset_variable( $variables, __( 'Identity', 'pepselect-coa-archive' ), $test['identity_status']['label'] ); }
		$this->add_dataset_variable( $variables, __( 'Endotoxin', 'pepselect-coa-archive' ), $test['endotoxin_result'], $test['endotoxin_unit'] );
		$this->add_dataset_variable( $variables, __( 'Heavy metals', 'pepselect-coa-archive' ), $test['heavy_metals_summary'] );
		$this->add_dataset_variable( $variables, __( 'Sterility', 'pepselect-coa-archive' ), $test['sterility_result'] );
		$this->add_dataset_variable( $variables, __( 'Fentanyl screen', 'pepselect-coa-archive' ), $test['fentanyl_result'] );
		if ( ! $variables ) { return array(); }

		$dataset = array(
			'@type' => 'Dataset',
			'@id' => trailingslashit( $canonical ) . '#dataset',
			'name' => sprintf( __( '%1$s batch %2$s laboratory results', 'pepselect-coa-archive' ), $name, $batch ),
			'description' => sprintf( __( 'Batch-specific laboratory results published by Pep Select for %1$s, batch %2$s. This dataset records the public measurements and methods shown on the laboratory report page.', 'pepselect-coa-archive' ), $name, $batch ),
			'url' => $canonical,
			'identifier' => $batch,
			'mainEntityOfPage' => array( '@id' => trailingslashit( $canonical ) . '#webpage' ),
			'isAccessibleForFree' => true,
			'creator' => array( '@type' => 'Organization', 'name' => get_bloginfo( 'name' ) ?: 'Pep Select', 'url' => home_url( '/' ) ),
			'includedInDataCatalog' => array( '@type' => 'DataCatalog', 'name' => __( 'Pep Select Quality Archive', 'pepselect-coa-archive' ), 'url' => home_url( user_trailingslashit( 'testing' ) ) ),
			'variableMeasured' => $variables,
		);

		if ( ! empty( $test['laboratory'] ) ) { $dataset['provider'] = array( '@type' => 'Organization', 'name' => $test['laboratory'] ); }
		$date = $this->schema_date( isset( $test['test_date'] ) ? $test['test_date'] : '' );
		if ( $date ) { $dataset['dateCreated'] = $date; }
		$methods = array_values( array_unique( array_filter( array( $test['identity_method'], $test['purity_method'], $test['fentanyl_method'] ) ) ) );
		if ( $methods ) { $dataset['measurementTechnique'] = $methods; }
		if ( ! empty( $test['pdf_url'] ) ) {
			$dataset['distribution'] = array( array( '@type' => 'DataDownload', 'encodingFormat' => 'application/pdf', 'contentUrl' => $test['pdf_url'] ) );
		}

		$images = array();
		if ( ! empty( $test['vial_image_is_exact'] ) && ! empty( $test['vial_image_url'] ) ) { $images[] = $test['vial_image_url']; }
		foreach ( array( 'page_images', 'batch_identity_photos' ) as $gallery_key ) {
			foreach ( isset( $test[ $gallery_key ] ) && is_array( $test[ $gallery_key ] ) ? $test[ $gallery_key ] : array() as $image ) {
				if ( ! empty( $image['full_url'] ) ) { $images[] = $image['full_url']; }
			}
		}
		$images = array_values( array_unique( $images ) );
		if ( $images ) { $dataset['image'] = $images; }

		return $dataset;
	}

	/** Adds one non-empty public measurement. */
	private function add_dataset_variable( &$variables, $name, $value, $unit = '' ) {
		$value = trim( (string) $value );
		if ( '' === $value ) { return; }
		$variable = array( '@type' => 'PropertyValue', 'name' => $name, 'value' => $value );
		if ( '' !== trim( (string) $unit ) ) { $variable['unitText'] = trim( (string) $unit ); }
		$variables[] = $variable;
	}

	/** Normalizes the plugin's supported report-date shapes for Schema.org. */
	private function schema_date( $value ) {
		$digits = preg_replace( '/\D/', '', (string) $value );
		if ( 8 !== strlen( $digits ) ) { return ''; }
		$date = \DateTimeImmutable::createFromFormat( '!Ymd', $digits );
		return $date && $date->format( 'Ymd' ) === $digits ? $date->format( 'Y-m-d' ) : '';
	}

	private function render( $template, $context ) {
		if ( ! $context ) { return ''; }
		$ps_context = $context; $ps_embedded = true;
		ob_start(); include $this->locate( $template ); return (string) ob_get_clean();
	}

	private function ensure_assets( $gallery = false, $history_carousel = false, $search = false ) {
		wp_enqueue_style( 'pepselect-coa-frontend', plugins_url( 'assets/css/pepselect-coa-frontend.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		if ( ! $this->variables_added ) { wp_add_inline_style( 'pepselect-coa-frontend', Design_Settings::inline_css() ); $this->variables_added = true; }
		if ( $gallery ) { wp_enqueue_script( 'pepselect-coa-lightbox', plugins_url( 'assets/js/pepselect-coa-lightbox.js', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION, true ); }
		if ( $history_carousel ) { wp_enqueue_script( 'pepselect-coa-history-carousel', plugins_url( 'assets/js/pepselect-coa-history-carousel.js', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION, true ); }
		if ( $search ) { wp_enqueue_script( 'pepselect-coa-search', plugins_url( 'assets/js/pepselect-coa-search.js', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION, true ); wp_localize_script( 'pepselect-coa-search', 'PepSelectCoaSearch', array( 'endpoint' => esc_url_raw( rest_url( 'pepselect-coa/v1/search' ) ) ) ); }
		if ( did_action( 'wp_head' ) && ! wp_style_is( 'pepselect-coa-frontend', 'done' ) ) { wp_print_styles( 'pepselect-coa-frontend' ); }
	}

	private function context_has_gallery( $context ) {
		return is_array( $context ) && 'report' === ( isset( $context['view'] ) ? $context['view'] : '' ) && ! empty( $context['test']['page_images'] );
	}

	private function context_has_history_carousel( $context ) {
		return is_array( $context ) && 'compound' === ( isset( $context['view'] ) ? $context['view'] : '' ) && ! empty( $context['previous_reports'] );
	}
}

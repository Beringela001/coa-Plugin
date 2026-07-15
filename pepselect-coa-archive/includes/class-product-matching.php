<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Owns the WooCommerce-to-COA relationship and its deliberately narrow synchronization boundary. */
final class Product_Matching {
	const PRODUCT_ID_META       = 'woocommerce_product_id';
	const SKU_SNAPSHOT_META     = 'related_woocommerce_sku';
	const PREVIOUS_SKU_META     = 'previous_woocommerce_sku';
	const LAST_SYNC_META        = 'woocommerce_last_sync';
	const SYNC_STATUS_META      = 'woocommerce_sync_status';
	const PRODUCT_STATUS_META   = 'woocommerce_product_status';
	const PRODUCT_URL_META      = 'woocommerce_product_url';
	const PRODUCT_IMAGE_META    = 'woocommerce_product_image_id';
	const PRODUCT_TITLE_META    = 'woocommerce_product_title_snapshot';
	const INCLUDE_META          = '_pepselect_coa_include';
	const DISPLAY_NAME_META     = '_pepselect_coa_display_name';
	const STRENGTH_META         = '_pepselect_coa_strength';
	const STRENGTH_UNIT_META    = '_pepselect_coa_strength_unit';

	/** @var Dependencies */ private $dependencies;
	/** @var bool|null */ private $availability_override;

	public function __construct( Dependencies $dependencies, $availability_override = null ) {
		$this->dependencies = $dependencies;
		$this->availability_override = is_bool( $availability_override ) ? $availability_override : null;
	}

	public function is_available() { return null !== $this->availability_override ? $this->availability_override : $this->dependencies->has_woocommerce(); }

	/** Returns a normalized product without requiring WC_Product during plugin recovery. */
	public function product( $product_id ) {
		$post = get_post( absint( $product_id ) );
		return $post && 'product' === $post->post_type ? $post : null;
	}

	/** Returns the current unique product SKU. */
	public function sku( $product_id ) { return trim( (string) get_post_meta( absint( $product_id ), '_sku', true ) ); }

	/** Returns safe product identity for admin display and synchronization. */
	public function product_summary( $product_id ) {
		$product = $this->product( $product_id );
		if ( ! $product ) { return array(); }
		$strength = $this->strength( $product_id );
		return array(
			'id' => $product->ID,
			'title' => $product->post_title,
			'sku' => $this->sku( $product->ID ),
			'status' => $product->post_status,
			'type' => $this->product_type( $product->ID ),
			'url' => 'publish' === $product->post_status ? (string) get_permalink( $product ) : '',
			'edit_url' => (string) get_edit_post_link( $product->ID, 'raw' ),
			'image_id' => absint( get_post_thumbnail_id( $product->ID ) ),
			'include' => 'yes' === get_post_meta( $product->ID, self::INCLUDE_META, true ),
			'coa_display_name' => trim( (string) get_post_meta( $product->ID, self::DISPLAY_NAME_META, true ) ),
			'strength' => $strength,
		);
	}

	/** Searches standard catalog products with deterministic SKU-first ranking. */
	public function search( $query, $limit = 20 ) {
		global $wpdb;
		$query = sanitize_text_field( trim( (string) $query ) );
		if ( '' === $query ) { return array(); }
		$limit = max( 1, min( 50, absint( $limit ) ) );
		$like = '%' . $wpdb->esc_like( $query ) . '%';
		$prefix = $wpdb->esc_like( $query ) . '%';
		$numeric_id = ctype_digit( $query ) ? absint( $query ) : 0;
		$sql = $wpdb->prepare(
			"SELECT DISTINCT p.ID FROM {$wpdb->posts} p
			 LEFT JOIN {$wpdb->postmeta} sku ON sku.post_id = p.ID AND sku.meta_key = '_sku'
			 WHERE p.post_type = 'product' AND p.post_status <> 'trash'
			 AND (p.post_title LIKE %s OR sku.meta_value LIKE %s OR p.ID = %d)
			 ORDER BY CASE
			 WHEN LOWER(sku.meta_value) = LOWER(%s) THEN 1
			 WHEN p.ID = %d AND %d > 0 THEN 2
			 WHEN sku.meta_value LIKE %s THEN 3
			 WHEN sku.meta_value LIKE %s THEN 4
			 WHEN LOWER(p.post_title) = LOWER(%s) THEN 5
			 ELSE 6 END, p.post_title ASC, p.ID ASC LIMIT %d",
			$like, $like, $numeric_id, $query, $numeric_id, $numeric_id, $prefix, $like, $query, $limit
		); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Fully prepared immediately above.
		$ids = $wpdb->get_col( $sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return array_values( array_filter( array_map( array( $this, 'product_summary' ), $ids ) ) );
	}

	/** Resolves structured strength. Parser results are suggestions and never silently persisted. */
	public function strength( $product_id ) {
		$value = trim( (string) get_post_meta( $product_id, self::STRENGTH_META, true ) );
		$unit = trim( (string) get_post_meta( $product_id, self::STRENGTH_UNIT_META, true ) );
		if ( is_numeric( $value ) && (float) $value > 0 && isset( Compound_Validation::units()[ $unit ] ) ) {
			return array( 'value' => (string) (float) $value, 'unit' => $unit, 'source' => 'dedicated', 'confirmed' => true );
		}
		$attribute = $this->attribute_strength( $product_id );
		if ( $attribute ) { return $attribute; }
		$tag = $this->tag_strength( $product_id );
		if ( $tag ) { return $tag; }
		$suggested = $this->parse_strength( $this->sku( $product_id ) . ' ' . get_the_title( $product_id ) );
		return $suggested ? array_merge( $suggested, array( 'source' => 'suggested', 'confirmed' => false ) ) : array( 'value' => '', 'unit' => '', 'source' => '', 'confirmed' => false );
	}

	/** Resolves the COA scientific name without changing the storefront title. */
	public function coa_name( $product_id ) {
		$override = trim( (string) get_post_meta( $product_id, self::DISPLAY_NAME_META, true ) );
		if ( '' !== $override ) { return array( 'value' => $override, 'source' => 'override', 'requires_review' => false ); }
		$title = trim( (string) get_the_title( $product_id ) );
		$mapping = apply_filters( 'pepselect_coa_scientific_name_mapping', array( 'GLP-3 R' => 'Retatrutide' ) );
		foreach ( (array) $mapping as $storefront => $scientific ) {
			if ( 0 === strcasecmp( trim( (string) $storefront ), $title ) && '' !== trim( (string) $scientific ) ) {
				return array( 'value' => sanitize_text_field( $scientific ), 'source' => 'mapping', 'requires_review' => false );
			}
		}
		return array( 'value' => $title, 'source' => 'title-fallback', 'requires_review' => true );
	}

	/** Connects an existing compound after an explicit administrator choice. */
	public function connect_existing( $product_id, $compound_id ) {
		if ( ! $this->is_available() ) { return new \WP_Error( 'woocommerce_inactive', __( 'WooCommerce is inactive; saved relationships were not changed.', 'pepselect-coa-archive' ) ); }
		$product = $this->product( $product_id ); $compound = get_post( absint( $compound_id ) );
		if ( ! $product || 'trash' === $product->post_status ) { return new \WP_Error( 'missing_product', __( 'The selected WooCommerce product is unavailable.', 'pepselect-coa-archive' ) ); }
		if ( ! $compound || Post_Types::COMPOUND !== $compound->post_type || 'trash' === $compound->post_status ) { return new \WP_Error( 'missing_compound', __( 'Select a valid COA Compound.', 'pepselect-coa-archive' ) ); }
		$sku = $this->sku( $product_id );
		if ( '' === $sku ) { return new \WP_Error( 'missing_sku', __( 'A unique SKU is required before connecting this product.', 'pepselect-coa-archive' ) ); }
		if ( 1 !== count( $this->products_with_sku( $sku ) ) ) { return new \WP_Error( 'duplicate_sku', __( 'This SKU is assigned to more than one product.', 'pepselect-coa-archive' ) ); }
		$product_links = $this->compounds_for_product( $product_id, $compound_id );
		if ( $product_links ) { return new \WP_Error( 'duplicate_product_link', __( 'This product is already linked to another compound.', 'pepselect-coa-archive' ), $product_links ); }
		$sku_links = $this->compounds_for_sku( $sku, $compound_id );
		if ( $sku_links ) { return new \WP_Error( 'duplicate_sku_link', __( 'This SKU snapshot is already used by another compound.', 'pepselect-coa-archive' ), $sku_links ); }
		$existing_product_id = absint( get_post_meta( $compound_id, self::PRODUCT_ID_META, true ) );
		if ( $existing_product_id && $existing_product_id !== absint( $product_id ) ) { return new \WP_Error( 'compound_connected', __( 'This compound is already connected to a different product.', 'pepselect-coa-archive' ) ); }
		update_post_meta( $compound_id, self::PRODUCT_ID_META, absint( $product_id ) );
		return $this->sync( $compound_id );
	}

	/** Creates exactly one draft compound for an eligible, unambiguous product. */
	public function create_and_connect( $product_id ) {
		$product_id = absint( $product_id );
		if ( ! $this->is_available() ) { return new \WP_Error( 'woocommerce_inactive', __( 'WooCommerce is inactive.', 'pepselect-coa-archive' ) ); }
		$product = $this->product( $product_id );
		if ( ! $product || 'trash' === $product->post_status ) { return new \WP_Error( 'missing_product', __( 'The product no longer exists.', 'pepselect-coa-archive' ) ); }
		if ( 'yes' !== get_post_meta( $product_id, self::INCLUDE_META, true ) ) { return new \WP_Error( 'not_included', __( 'Enable Include in COA Archive first.', 'pepselect-coa-archive' ) ); }
		$sku = $this->sku( $product_id );
		if ( '' === $sku ) { return new \WP_Error( 'missing_sku', __( 'A SKU is required before creating a compound.', 'pepselect-coa-archive' ) ); }
		if ( 1 !== count( $this->products_with_sku( $sku ) ) ) { return new \WP_Error( 'duplicate_sku', __( 'The SKU must be unique before creating a compound.', 'pepselect-coa-archive' ) ); }
		$existing = $this->compounds_for_product( $product_id );
		if ( count( $existing ) > 1 ) { return new \WP_Error( 'duplicate_product_link', __( 'This product is linked to more than one compound and requires review.', 'pepselect-coa-archive' ), $existing ); }
		if ( $existing ) { return absint( $existing[0] ); }
		$sku_compounds = $this->compounds_for_sku( $sku );
		if ( $sku_compounds ) { return new \WP_Error( 'existing_compound', __( 'An existing compound already uses this SKU. Connect it explicitly instead of creating a duplicate.', 'pepselect-coa-archive' ), $sku_compounds ); }
		$strength = $this->strength( $product_id ); $name = $this->coa_name( $product_id );
		if ( empty( $strength['confirmed'] ) ) { return new \WP_Error( 'strength_review', __( 'Confirm Strength and Strength Unit before creating the compound.', 'pepselect-coa-archive' ) ); }
		if ( ! empty( $name['requires_review'] ) ) { return new \WP_Error( 'name_review', __( 'Enter a COA Display Name before creating the compound.', 'pepselect-coa-archive' ) ); }
		$lock = 'pepselect_coa_create_lock_' . $product_id;
		if ( ! add_option( $lock, time(), '', false ) ) {
			$locked_at = absint( get_option( $lock ) );
			if ( $locked_at && $locked_at > time() - 120 ) { return new \WP_Error( 'creation_in_progress', __( 'This product is already being processed.', 'pepselect-coa-archive' ) ); }
			delete_option( $lock );
			if ( ! add_option( $lock, time(), '', false ) ) { return new \WP_Error( 'creation_in_progress', __( 'This product is already being processed.', 'pepselect-coa-archive' ) ); }
		}
		try {
			$existing = $this->compounds_for_product( $product_id );
			if ( count( $existing ) > 1 ) { return new \WP_Error( 'duplicate_product_link', __( 'This product is linked to more than one compound and requires review.', 'pepselect-coa-archive' ), $existing ); }
			if ( $existing ) { return absint( $existing[0] ); }
			$title = trim( $name['value'] . ' ' . pepselect_coa_format_number( $strength['value'] ) . ' ' . $strength['unit'] );
			$compound_id = wp_insert_post( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => 'draft', 'post_title' => $title ), true );
			if ( is_wp_error( $compound_id ) ) { return $compound_id; }
			update_post_meta( $compound_id, 'display_name', $name['value'] );
			update_post_meta( $compound_id, 'compound_name', $name['value'] );
			update_post_meta( $compound_id, 'strength_value', $strength['value'] );
			update_post_meta( $compound_id, 'strength_unit', $strength['unit'] );
			update_post_meta( $compound_id, 'is_active', 0 );
			update_post_meta( $compound_id, self::PRODUCT_ID_META, $product_id );
			$result = $this->sync( $compound_id );
			return is_wp_error( $result ) ? $result : absint( $compound_id );
		} finally {
			delete_option( $lock );
		}
	}

	/** Synchronizes only product-owned identity fields; all testing and editorial COA fields are untouched. */
	public function sync( $compound_id, $acknowledge_sku_change = true ) {
		$compound_id = absint( $compound_id ); $product_id = absint( get_post_meta( $compound_id, self::PRODUCT_ID_META, true ) ); $changes = array();
		if ( ! $this->is_available() ) { update_post_meta( $compound_id, self::SYNC_STATUS_META, 'woocommerce-inactive' ); return new \WP_Error( 'woocommerce_inactive', __( 'WooCommerce is inactive; the relationship was preserved.', 'pepselect-coa-archive' ) ); }
		$product = $this->product( $product_id );
		if ( ! $product || 'trash' === $product->post_status ) { update_post_meta( $compound_id, self::SYNC_STATUS_META, 'product-missing' ); return new \WP_Error( 'product_missing', __( 'The product is missing; the compound and its history were preserved.', 'pepselect-coa-archive' ) ); }
		$sku = $this->sku( $product_id ); $previous = trim( (string) get_post_meta( $compound_id, self::SKU_SNAPSHOT_META, true ) );
		$links = $this->compounds_for_product( $product_id );
		if ( count( $links ) > 1 ) { update_post_meta( $compound_id, self::SYNC_STATUS_META, 'duplicate-product-link' ); return new \WP_Error( 'duplicate_product_link', __( 'This product is linked to more than one compound; no relationship was reassigned.', 'pepselect-coa-archive' ), $links ); }
		if ( $sku && 1 !== count( $this->products_with_sku( $sku ) ) ) { update_post_meta( $compound_id, self::SYNC_STATUS_META, 'duplicate-sku' ); return new \WP_Error( 'duplicate_sku', __( 'The current SKU is not unique; the saved snapshot was preserved.', 'pepselect-coa-archive' ) ); }
		$sku_changed = $previous && $sku && 0 !== strcasecmp( $previous, $sku );
		if ( $sku_changed ) { update_post_meta( $compound_id, self::PREVIOUS_SKU_META, $previous ); }
		if ( $sku && ( ! $sku_changed || $acknowledge_sku_change ) ) { $this->update_sync_meta( $compound_id, self::SKU_SNAPSHOT_META, $sku, __( 'SKU snapshot', 'pepselect-coa-archive' ), $changes ); }
		$this->update_sync_meta( $compound_id, self::PRODUCT_TITLE_META, $product->post_title, __( 'product title', 'pepselect-coa-archive' ), $changes );
		$this->update_sync_meta( $compound_id, self::PRODUCT_STATUS_META, $product->post_status, __( 'product status', 'pepselect-coa-archive' ), $changes );
		$this->update_sync_meta( $compound_id, self::PRODUCT_URL_META, 'publish' === $product->post_status ? esc_url_raw( get_permalink( $product ) ) : '', __( 'product URL', 'pepselect-coa-archive' ), $changes );
		$this->update_sync_meta( $compound_id, self::PRODUCT_IMAGE_META, absint( get_post_thumbnail_id( $product_id ) ), __( 'fallback image', 'pepselect-coa-archive' ), $changes );
		$strength = $this->strength( $product_id );
		if ( ! empty( $strength['confirmed'] ) ) { $this->update_sync_meta( $compound_id, 'strength_value', $strength['value'], __( 'strength', 'pepselect-coa-archive' ), $changes ); $this->update_sync_meta( $compound_id, 'strength_unit', $strength['unit'], __( 'strength unit', 'pepselect-coa-archive' ), $changes ); }
		$status = '' === $sku ? 'missing-sku' : ( $sku_changed && ! $acknowledge_sku_change ? 'sku-changed' : ( 'publish' === $product->post_status ? 'connected' : 'needs-review' ) );
		$this->update_sync_meta( $compound_id, self::SYNC_STATUS_META, $status, __( 'synchronization status', 'pepselect-coa-archive' ), $changes );
		if ( 'connected' === $status || 'needs-review' === $status ) { update_post_meta( $compound_id, self::LAST_SYNC_META, current_time( 'mysql', true ) ); }
		return array( 'compound_id' => $compound_id, 'product_id' => $product_id, 'status' => $status, 'changes' => array_values( array_unique( $changes ) ) );
	}

	/** Deliberately disconnects without deleting either record or any COA-owned data. */
	public function disconnect( $compound_id ) {
		$compound_id = absint( $compound_id );
		$sku = trim( (string) get_post_meta( $compound_id, self::SKU_SNAPSHOT_META, true ) );
		if ( $sku ) { update_post_meta( $compound_id, self::PREVIOUS_SKU_META, $sku ); }
		foreach ( array( self::PRODUCT_ID_META, self::SKU_SNAPSHOT_META, self::LAST_SYNC_META, self::PRODUCT_STATUS_META, self::PRODUCT_URL_META, self::PRODUCT_IMAGE_META, self::PRODUCT_TITLE_META ) as $key ) { delete_post_meta( $compound_id, $key ); }
		update_post_meta( $compound_id, self::SYNC_STATUS_META, 'disconnected' );
		return true;
	}

	/** Returns a product-primary matching state. */
	public function product_status( $product_id ) {
		if ( ! $this->is_available() ) { return array( 'key' => 'woocommerce-inactive', 'label' => __( 'WooCommerce Inactive', 'pepselect-coa-archive' ) ); }
		$summary = $this->product_summary( $product_id );
		if ( ! $summary ) { return array( 'key' => 'product-missing', 'label' => __( 'Product Missing', 'pepselect-coa-archive' ) ); }
		if ( ! $summary['include'] ) { return array( 'key' => 'not-included', 'label' => __( 'Not Included', 'pepselect-coa-archive' ) ); }
		if ( '' === $summary['sku'] ) { return array( 'key' => 'missing-sku', 'label' => __( 'Missing SKU', 'pepselect-coa-archive' ) ); }
		if ( 1 !== count( $this->products_with_sku( $summary['sku'] ) ) ) { return array( 'key' => 'duplicate-sku', 'label' => __( 'Duplicate SKU', 'pepselect-coa-archive' ) ); }
		$sku_links = $this->compounds_for_sku( $summary['sku'] );
		if ( count( $sku_links ) > 1 ) { return array( 'key' => 'duplicate-sku', 'label' => __( 'Duplicate SKU', 'pepselect-coa-archive' ), 'compound_ids' => $sku_links ); }
		$links = $this->compounds_for_product( $product_id );
		if ( count( $links ) > 1 ) { return array( 'key' => 'duplicate-product-link', 'label' => __( 'Duplicate Product Link', 'pepselect-coa-archive' ), 'compound_ids' => $links ); }
		if ( $links ) {
			$snapshot = trim( (string) get_post_meta( $links[0], self::SKU_SNAPSHOT_META, true ) );
			if ( $snapshot && 0 !== strcasecmp( $snapshot, $summary['sku'] ) ) { return array( 'key' => 'sku-changed', 'label' => __( 'SKU Changed', 'pepselect-coa-archive' ), 'compound_ids' => $links ); }
			if ( 'publish' !== $summary['status'] ) { return array( 'key' => 'needs-review', 'label' => __( 'Needs Review', 'pepselect-coa-archive' ), 'compound_ids' => $links ); }
			return array( 'key' => 'connected', 'label' => __( 'Connected', 'pepselect-coa-archive' ), 'compound_ids' => $links );
		}
		if ( $sku_links ) { return array( 'key' => 'needs-review', 'label' => __( 'Needs Review', 'pepselect-coa-archive' ), 'compound_ids' => $sku_links ); }
		$name = $this->coa_name( $product_id ); $strength = $summary['strength'];
		if ( ! empty( $name['requires_review'] ) || empty( $strength['confirmed'] ) ) { return array( 'key' => 'needs-review', 'label' => __( 'Needs Review', 'pepselect-coa-archive' ) ); }
		return array( 'key' => 'ready-to-create', 'label' => __( 'Ready to Create', 'pepselect-coa-archive' ) );
	}

	/** Repairs only blank audit snapshots on existing canonical links during upgrades. */
	public function backfill_existing_links() {
		if ( ! $this->is_available() ) { return 0; }
		$ids = get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( array( 'key' => self::PRODUCT_ID_META, 'compare' => 'EXISTS' ) ) ) );
		$count = 0;
		foreach ( $ids as $compound_id ) {
			$product_id = absint( get_post_meta( $compound_id, self::PRODUCT_ID_META, true ) );
			if ( $product_id && $this->product( $product_id ) && '' === trim( (string) get_post_meta( $compound_id, self::SKU_SNAPSHOT_META, true ) ) ) { $this->sync( $compound_id ); ++$count; }
		}
		return $count;
	}

	public function compounds_for_product( $product_id, $exclude_id = 0 ) { return $this->compound_meta_matches( self::PRODUCT_ID_META, absint( $product_id ), $exclude_id ); }
	public function compounds_for_sku( $sku, $exclude_id = 0 ) { return '' === trim( (string) $sku ) ? array() : $this->compound_meta_matches( self::SKU_SNAPSHOT_META, trim( (string) $sku ), $exclude_id ); }

	public function products_with_sku( $sku ) {
		global $wpdb; $sku = trim( (string) $sku ); if ( '' === $sku ) { return array(); }
		$sql = $wpdb->prepare( "SELECT DISTINCT p.ID FROM {$wpdb->posts} p INNER JOIN {$wpdb->postmeta} m ON m.post_id=p.ID AND m.meta_key='_sku' WHERE p.post_type='product' AND p.post_status<>'trash' AND LOWER(m.meta_value)=LOWER(%s) ORDER BY p.ID ASC", $sku );
		return array_map( 'absint', $wpdb->get_col( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	private function compound_meta_matches( $key, $value, $exclude_id = 0 ) {
		return get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'posts_per_page' => -1, 'fields' => 'ids', 'post__not_in' => $exclude_id ? array( absint( $exclude_id ) ) : array(), 'no_found_rows' => true, 'meta_query' => array( array( 'key' => $key, 'value' => (string) $value, 'compare' => '=' ) ) ) );
	}

	private function product_type( $product_id ) {
		$terms = wp_get_post_terms( $product_id, 'product_type', array( 'fields' => 'slugs' ) );
		return ! is_wp_error( $terms ) && $terms ? (string) $terms[0] : 'simple';
	}

	private function attribute_strength( $product_id ) {
		if ( function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product( $product_id );
			if ( $product ) { foreach ( array( 'COA Strength', 'Strength', 'pa_strength' ) as $name ) { $parsed = $this->parse_strength( $product->get_attribute( $name ) ); if ( $parsed ) { return array_merge( $parsed, array( 'source' => 'attribute', 'confirmed' => true ) ); } } }
		}
		return array();
	}

	private function tag_strength( $product_id ) {
		$terms = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'names' ) );
		if ( is_wp_error( $terms ) ) { return array(); }
		$matches = array(); foreach ( $terms as $term ) { $parsed = $this->parse_strength( $term ); if ( $parsed ) { $matches[ strtolower( $parsed['value'] . $parsed['unit'] ) ] = $parsed; } }
		return 1 === count( $matches ) ? array_merge( reset( $matches ), array( 'source' => 'tag', 'confirmed' => true ) ) : array();
	}

	private function parse_strength( $value ) {
		if ( ! preg_match_all( '/(?:^|[^0-9.])(\d+(?:\.\d+)?)\s*(mg\/mL|mcg|mg|mL|IU|g)(?:$|[^a-z])/i', (string) $value, $matches, PREG_SET_ORDER ) || 1 !== count( $matches ) ) { return array(); }
		$units = array( 'mg/ml' => 'mg/mL', 'mcg' => 'mcg', 'mg' => 'mg', 'ml' => 'mL', 'iu' => 'IU', 'g' => 'g' );
		$unit = isset( $units[ strtolower( $matches[0][2] ) ] ) ? $units[ strtolower( $matches[0][2] ) ] : '';
		return $unit && (float) $matches[0][1] > 0 ? array( 'value' => (string) (float) $matches[0][1], 'unit' => $unit ) : array();
	}

	private function update_sync_meta( $post_id, $key, $value, $label, &$changes ) {
		if ( (string) get_post_meta( $post_id, $key, true ) === (string) $value ) { return; }
		update_post_meta( $post_id, $key, $value ); $changes[] = $label;
	}
}

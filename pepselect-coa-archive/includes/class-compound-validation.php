<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Centralizes compound normalization, validation, duplicate detection, and title initialization. */
final class Compound_Validation {
	/** @var bool Prevents recursive title updates. */
	private $updating_title = false;

	/** @var bool Prevents duplicate hook registration. */
	private $hooks_registered = false;

	/** Registers ACF validation, normalization, and save hooks. @return void */
	public function register_hooks() {
		if ( $this->hooks_registered ) { return; }
		$this->hooks_registered = true;
		foreach ( self::field_keys() as $key => $name ) {
			add_filter( 'acf/validate_value/key=' . $key, array( $this, 'validate' ), 10, 4 );
			add_filter( 'acf/update_value/key=' . $key, array( $this, 'sanitize_acf_value' ), 10, 3 );
		}
		add_filter( 'acf/validate_value/key=field_ps_compound_strength_unit', array( $this, 'validate_duplicate' ), 20, 4 );
		add_action( 'acf/save_post', array( $this, 'populate_empty_title' ), 20 );
	}

	/** Returns allowed strength units. @return array */
	public static function units() {
		return array( 'mg' => 'mg', 'mcg' => 'mcg', 'g' => 'g', 'mL' => 'mL', 'IU' => 'IU', 'mg/mL' => 'mg/mL' );
	}

	/** Returns allowed categories. @return array */
	public static function categories() {
		return array( 'metabolic' => __( 'Metabolic', 'pepselect-coa-archive' ), 'recovery' => __( 'Recovery', 'pepselect-coa-archive' ), 'growth-hormone' => __( 'Growth Hormone', 'pepselect-coa-archive' ), 'mitochondrial' => __( 'Mitochondrial', 'pepselect-coa-archive' ), 'cosmetic' => __( 'Cosmetic', 'pepselect-coa-archive' ), 'longevity' => __( 'Longevity', 'pepselect-coa-archive' ), 'other' => __( 'Other', 'pepselect-coa-archive' ) );
	}

	/** Sanitizes a structured value by meta key. @param string $name Field name. @param mixed $value Value. @return mixed */
	public static function sanitize( $name, $value ) {
		if ( in_array( $name, array( 'display_name', 'compound_name', 'short_name', 'strength_unit', 'compound_category' ), true ) ) { return sanitize_text_field( trim( (string) $value ) ); }
		if ( in_array( $name, array( 'archive_description', 'internal_notes' ), true ) ) { return sanitize_textarea_field( trim( (string) $value ) ); }
		if ( 'strength_value' === $name ) { return is_numeric( $value ) ? (float) $value : $value; }
		if ( in_array( $name, array( 'display_order', 'compound_image_id', 'woocommerce_product_id' ), true ) ) { return absint( $value ); }
		if ( in_array( $name, array( 'is_active', 'is_featured' ), true ) ) { return empty( $value ) ? 0 : 1; }
		return $value;
	}

	/** Validates an ACF field value. @param mixed $valid Existing result. @param mixed $value Value. @param array $field Field. @param string $input Input key. @return mixed */
	public function validate( $valid, $value, $field, $input ) {
		unset( $input );
		if ( true !== $valid ) { return $valid; }
		$name  = $field['name'];
		$raw_value = is_string( $value ) ? trim( $value ) : $value;
		$value = self::sanitize( $name, $value );
		if ( in_array( $name, array( 'display_name', 'compound_name' ), true ) && '' === $value ) { return __( 'This field is required and cannot be empty.', 'pepselect-coa-archive' ); }
		$limits = array( 'display_name' => 120, 'compound_name' => 100, 'short_name' => 40, 'archive_description' => 500 );
		if ( isset( $limits[ $name ] ) && $this->length( $value ) > $limits[ $name ] ) { return sprintf( __( 'This value must be %d characters or fewer.', 'pepselect-coa-archive' ), $limits[ $name ] ); }
		if ( 'strength_value' === $name && ( ! is_numeric( $value ) || (float) $value <= 0 ) ) { return __( 'Strength Value must be a number greater than zero.', 'pepselect-coa-archive' ); }
		if ( 'strength_unit' === $name && ! array_key_exists( $value, self::units() ) ) { return __( 'Select a valid strength unit.', 'pepselect-coa-archive' ); }
		if ( 'compound_category' === $name && '' !== $value && ! array_key_exists( $value, self::categories() ) ) { return __( 'Select a valid compound category.', 'pepselect-coa-archive' ); }
		if ( 'display_order' === $name && ( false === filter_var( $raw_value, FILTER_VALIDATE_INT ) || (int) $raw_value < 0 ) ) { return __( 'Display Order must be a whole number of zero or greater.', 'pepselect-coa-archive' ); }
		if ( 'woocommerce_product_id' === $name && $value && ( 'product' !== get_post_type( (int) $value ) || 'trash' === get_post_status( (int) $value ) ) ) { return __( 'Select a valid WooCommerce product.', 'pepselect-coa-archive' ); }
		return $valid;
	}

	/** Blocks an exact compound-name, strength, and unit duplicate. @param mixed $valid Existing result. @param mixed $value Unit. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_duplicate( $valid, $value, $field, $input ) {
		unset( $field, $input );
		if ( true !== $valid || empty( $_POST['acf'] ) || ! is_array( $_POST['acf'] ) ) { return $valid; } // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF validates its form nonce.
		$acf      = wp_unslash( $_POST['acf'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$name     = isset( $acf['field_ps_compound_name'] ) ? sanitize_text_field( trim( $acf['field_ps_compound_name'] ) ) : '';
		$strength = isset( $acf['field_ps_compound_strength_value'] ) ? $acf['field_ps_compound_strength_value'] : '';
		$unit     = sanitize_text_field( (string) $value );
		if ( '' === $name || ! is_numeric( $strength ) || ! array_key_exists( $unit, self::units() ) ) { return $valid; }
		$current_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$matches = get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 20, 'post__not_in' => $current_id ? array( $current_id ) : array(), 'fields' => 'ids', 'no_found_rows' => true, 'meta_query' => array( 'relation' => 'AND', array( 'key' => 'strength_value', 'value' => (string) (float) $strength, 'compare' => '=' ), array( 'key' => 'strength_unit', 'value' => $unit, 'compare' => '=' ) ) ) );
		foreach ( $matches as $match_id ) {
			if ( 0 === strcasecmp( trim( (string) get_post_meta( $match_id, 'compound_name', true ) ), $name ) ) {
				$link = get_edit_post_link( $match_id, 'raw' );
				return $link ? sprintf( __( 'An exact compound already exists: %1$s (edit: %2$s).', 'pepselect-coa-archive' ), get_the_title( $match_id ), esc_url_raw( $link ) ) : __( 'An exact compound with this name, strength, and unit already exists.', 'pepselect-coa-archive' );
			}
		}
		return $valid;
	}

	/** Sanitizes values before ACF writes post meta. @param mixed $value Value. @param int|string $post_id Post ID. @param array $field Field. @return mixed */
	public function sanitize_acf_value( $value, $post_id, $field ) {
		unset( $post_id );
		return self::sanitize( $field['name'], $value );
	}

	/** Populates a genuinely empty title once from Display Name. @param int|string $post_id Post ID. @return void */
	public function populate_empty_title( $post_id ) {
		$post_id = absint( $post_id );
		if ( ! $post_id || $this->updating_title || Post_Types::COMPOUND !== get_post_type( $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) { return; }
		$post = get_post( $post_id );
		$name = trim( (string) get_post_meta( $post_id, 'display_name', true ) );
		if ( ! $post || '' !== trim( $post->post_title ) || '' === $name ) { return; }
		$this->updating_title = true;
		wp_update_post( array( 'ID' => $post_id, 'post_title' => $name ) );
		$this->updating_title = false;
	}

	/** Returns stable field key/name mappings. @return array */
	private static function field_keys() {
		return array( 'field_ps_compound_display_name' => 'display_name', 'field_ps_compound_name' => 'compound_name', 'field_ps_compound_short_name' => 'short_name', 'field_ps_compound_strength_value' => 'strength_value', 'field_ps_compound_strength_unit' => 'strength_unit', 'field_ps_compound_category' => 'compound_category', 'field_ps_compound_woocommerce_product_id' => 'woocommerce_product_id', 'field_ps_compound_archive_description' => 'archive_description', 'field_ps_compound_image_id' => 'compound_image_id', 'field_ps_compound_display_order' => 'display_order', 'field_ps_compound_is_active' => 'is_active', 'field_ps_compound_is_featured' => 'is_featured', 'field_ps_compound_internal_notes' => 'internal_notes' );
	}

	/** Returns multibyte-safe string length when available. @param string $value Value. @return int */
	private function length( $value ) { return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value ); }
}

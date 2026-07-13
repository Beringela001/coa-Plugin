<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Owns deterministic ACF definitions and safe REST metadata. */
final class Compound_Fields {
	/** @var Dependencies */
	private $dependencies;

	/** @var bool Prevents duplicate local group registration. */
	private $group_registered = false;

	/** @var bool Prevents duplicate REST registration. */
	private $rest_registered = false;

	/** @param Dependencies $dependencies Optional integration detector. */
	public function __construct( Dependencies $dependencies ) { $this->dependencies = $dependencies; }

	/** Registers the Compound Details field group when ACF is available. @return void */
	public function register() {
		if ( $this->group_registered || ! $this->dependencies->has_acf() ) { return; }
		$this->group_registered = true;
		$fields = $this->base_fields();
		if ( $this->dependencies->has_woocommerce() ) {
			array_splice( $fields, 6, 0, array( $this->product_field() ) );
		}
		acf_add_local_field_group( array( 'key' => 'group_ps_compound_details', 'title' => __( 'Compound Details', 'pepselect-coa-archive' ), 'fields' => $fields, 'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => Post_Types::COMPOUND ) ) ), 'position' => 'normal', 'style' => 'default', 'active' => true, 'show_in_rest' => 0 ) );
	}

	/** Registers safe structured fields in the core REST post meta schema. @return void */
	public function register_rest_meta() {
		if ( $this->rest_registered ) { return; }
		$this->rest_registered = true;
		$schema = array(
			'display_name' => 'string', 'compound_name' => 'string', 'short_name' => 'string',
			'strength_value' => 'number', 'strength_unit' => 'string', 'compound_category' => 'string',
			'archive_description' => 'string', 'compound_image_id' => 'integer', 'display_order' => 'integer',
			'is_active' => 'boolean', 'is_featured' => 'boolean', 'woocommerce_product_id' => 'integer',
		);
		foreach ( $schema as $key => $type ) {
			register_post_meta( Post_Types::COMPOUND, $key, array( 'single' => true, 'type' => $type, 'show_in_rest' => true, 'sanitize_callback' => array( $this, 'sanitize_rest_value' ), 'auth_callback' => array( $this, 'authorize_meta_edit' ) ) );
		}
	}

	/** Sanitizes REST meta according to the registered field. @param mixed $value Value. @param string $meta_key Key. @return mixed */
	public function sanitize_rest_value( $value, $meta_key ) {
		return Compound_Validation::sanitize( $meta_key, $value );
	}

	/** Restricts metadata modification to users who can edit the compound. @param bool $allowed Existing result. @param string $meta_key Key. @param int $post_id Post ID. @return bool */
	public function authorize_meta_edit( $allowed, $meta_key, $post_id ) {
		unset( $allowed, $meta_key );
		return current_user_can( 'edit_post', $post_id );
	}

	/** Returns the stable base field definitions. @return array */
	private function base_fields() {
		return array(
			$this->field( 'field_ps_compound_display_name', 'display_name', __( 'Display Name', 'pepselect-coa-archive' ), 'text', array( 'required' => 1, 'maxlength' => 120, 'instructions' => __( 'Enter the exact name customers should see in the COA archive.', 'pepselect-coa-archive' ) ) ),
			$this->field( 'field_ps_compound_name', 'compound_name', __( 'Base Compound Name', 'pepselect-coa-archive' ), 'text', array( 'required' => 1, 'maxlength' => 100 ) ),
			$this->field( 'field_ps_compound_short_name', 'short_name', __( 'Short Name', 'pepselect-coa-archive' ), 'text', array( 'maxlength' => 40 ) ),
			$this->field( 'field_ps_compound_strength_value', 'strength_value', __( 'Strength Value', 'pepselect-coa-archive' ), 'number', array( 'required' => 1, 'min' => 0.000001, 'step' => 0.000001, 'instructions' => __( 'Enter a positive numeric value without units.', 'pepselect-coa-archive' ) ) ),
			$this->field( 'field_ps_compound_strength_unit', 'strength_unit', __( 'Strength Unit', 'pepselect-coa-archive' ), 'select', array( 'required' => 1, 'choices' => Compound_Validation::units(), 'default_value' => 'mg', 'allow_null' => 0, 'return_format' => 'value' ) ),
			$this->field( 'field_ps_compound_category', 'compound_category', __( 'Compound Category', 'pepselect-coa-archive' ), 'select', array( 'choices' => Compound_Validation::categories(), 'allow_null' => 1, 'return_format' => 'value' ) ),
			$this->field( 'field_ps_compound_archive_description', 'archive_description', __( 'Archive Description', 'pepselect-coa-archive' ), 'textarea', array( 'maxlength' => 500, 'rows' => 4, 'new_lines' => 'wpautop' ) ),
			$this->field( 'field_ps_compound_image_id', 'compound_image_id', __( 'Compound Image', 'pepselect-coa-archive' ), 'image', array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all' ) ),
			$this->field( 'field_ps_compound_display_order', 'display_order', __( 'Display Order', 'pepselect-coa-archive' ), 'number', array( 'default_value' => 0, 'min' => 0, 'step' => 1 ) ),
			$this->field( 'field_ps_compound_is_active', 'is_active', __( 'Active', 'pepselect-coa-archive' ), 'true_false', array( 'default_value' => 1, 'ui' => 1 ) ),
			$this->field( 'field_ps_compound_is_featured', 'is_featured', __( 'Featured', 'pepselect-coa-archive' ), 'true_false', array( 'default_value' => 0, 'ui' => 1 ) ),
			$this->field( 'field_ps_compound_internal_notes', 'internal_notes', __( 'Internal Notes', 'pepselect-coa-archive' ), 'textarea', array( 'rows' => 4, 'instructions' => __( 'Administrative notes only. Never exposed through the public REST schema.', 'pepselect-coa-archive' ) ) ),
		);
	}

	/** Returns the optional product selector. @return array */
	private function product_field() {
		return $this->field( 'field_ps_compound_woocommerce_product_id', 'woocommerce_product_id', __( 'Related WooCommerce Product', 'pepselect-coa-archive' ), 'post_object', array( 'post_type' => array( 'product' ), 'return_format' => 'id', 'allow_null' => 1, 'multiple' => 0 ) );
	}

	/** Builds an ACF field definition. @param string $key Key. @param string $name Name. @param string $label Label. @param string $type Type. @param array $options Options. @return array */
	private function field( $key, $name, $label, $type, $options = array() ) {
		return array_merge( array( 'key' => $key, 'name' => $name, 'label' => $label, 'type' => $type, 'required' => 0 ), $options );
	}
}

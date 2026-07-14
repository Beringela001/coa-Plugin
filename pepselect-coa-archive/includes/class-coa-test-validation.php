<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Centralizes COA Test sanitization and ACF validation. */
final class COA_Test_Validation {
	/** @var bool */
	private $registered = false;

	/** Registers stable field-specific ACF hooks. @return void */
	public function register_hooks() {
		if ( $this->registered ) { return; }
		$this->registered = true;
		foreach ( self::field_map() as $key => $name ) {
			add_filter( 'acf/validate_value/key=' . $key, array( $this, 'validate' ), 10, 4 );
			add_filter( 'acf/update_value/key=' . $key, array( $this, 'sanitize_acf_value' ), 10, 3 );
		}
		add_filter( 'acf/validate_value/key=field_ps_coa_test_date_received', array( $this, 'validate' ), 10, 4 );
		add_filter( 'acf/update_value/key=field_ps_coa_test_date_received', array( $this, 'sanitize_acf_value' ), 10, 3 );
		add_filter( 'acf/validate_value/key=field_ps_coa_test_batch_number', array( $this, 'validate_duplicate' ), 20, 4 );
		add_filter( 'acf/validate_value/key=field_ps_coa_test_status', array( $this, 'validate_approval' ), 20, 4 );
	}

	/** Sanitizes a REST meta value. @param mixed $value Value. @param string $key Meta key. @return mixed */
	public static function sanitize( $value, $key = '' ) {
		if ( in_array( $key, self::integer_fields(), true ) ) { return absint( $value ); }
		if ( in_array( $key, self::number_fields(), true ) ) { return '' === $value ? '' : ( is_numeric( $value ) ? (float) $value : $value ); }
		if ( 'is_current' === $key ) { return empty( $value ) ? 0 : 1; }
		if ( in_array( $key, array( 'public_notes', 'report_notes', 'internal_notes', 'heavy_metals_summary' ), true ) ) { return sanitize_textarea_field( trim( (string) $value ) ); }
		if ( in_array( $key, array( 'lab_verification_url', 'lab_report_url', 'pending_lab_url' ), true ) ) { return esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) ); }
		return sanitize_text_field( trim( (string) $value ) );
	}

	/** Sanitizes gallery IDs while preserving order. @param mixed $value Value. @return array */
	public static function sanitize_gallery( $value ) { return is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array(); }

	/** Sanitizes an ACF value before storage. @param mixed $value Value. @param int|string $post_id Post ID. @param array $field Field. @return mixed */
	public function sanitize_acf_value( $value, $post_id, $field ) { unset( $post_id ); return 'coa_page_images' === $field['name'] ? self::sanitize_gallery( $value ) : self::sanitize( $value, $field['name'] ); }

	/** Validates an individual ACF value. @param mixed $valid Existing result. @param mixed $value Value. @param array $field Field. @param string $input Input. @return mixed */
	public function validate( $valid, $value, $field, $input ) {
		unset( $input ); if ( true !== $valid ) { return $valid; }
		$name = $field['name']; $raw = is_string( $value ) ? trim( $value ) : $value;
		if ( 'compound_id' === $name && ( ! $raw || Post_Types::COMPOUND !== get_post_type( absint( $raw ) ) ) ) { return __( 'Select a valid related compound.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && '' === $raw ) { return __( 'Batch Number is required.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && ( $this->length( $raw ) > 120 || ! preg_match( '/^[\p{L}\p{N} _\.\/-]+$/u', $raw ) ) ) { return __( 'Batch Number must be 120 characters or fewer and use only letters, numbers, spaces, hyphens, underscores, periods, or slashes.', 'pepselect-coa-archive' ); }
		$lengths = array( 'internal_batch_id' => 120, 'other_testing_lab' => 120, 'lab_accession_number' => 120, 'vial_crimp_color_other' => 80, 'vial_cap_color_other' => 80, 'sample_appearance' => 200, 'purity_method' => 120, 'identity_method' => 120, 'endotoxin_result' => 120, 'endotoxin_unit' => 50, 'heavy_metals_summary' => 500, 'sterility_result' => 200, 'coa_number' => 120, 'verification_code' => 200, 'certificate_version' => 50, 'public_notes' => 1000, 'report_notes' => 1000 );
		if ( isset( $lengths[ $name ] ) && $this->length( $raw ) > $lengths[ $name ] ) { return sprintf( __( 'This value must be %d characters or fewer.', 'pepselect-coa-archive' ), $lengths[ $name ] ); }
		if ( in_array( $name, array( 'test_date', 'date_received', 'expected_coa_date' ), true ) && '' !== $raw && ! $this->valid_date( $raw ) ) { return __( 'Enter a valid date.', 'pepselect-coa-archive' ); }
		if ( 'test_date' === $name && ! $this->is_pre_release() && '' === $raw ) { return __( 'Test Date is required for approved and failed reports.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::labs() ) ) { return __( 'Select a valid testing laboratory.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && ! $this->is_pre_release() && '' === $raw ) { return __( 'Testing Laboratory is required for approved and failed reports.', 'pepselect-coa-archive' ); }
		if ( 'other_testing_lab' === $name && 'other' === $this->posted( 'testing_lab' ) && '' === $raw ) { return __( 'Enter the other laboratory name.', 'pepselect-coa-archive' ); }
		if ( 'coa_status' === $name && ! array_key_exists( $raw, COA_Test_Fields::statuses() ) ) { return __( 'Select a valid COA status.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vial_crimp_color', 'vial_cap_color' ), true ) && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::vial_colors() ) ) { return __( 'Select a valid vial color.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vial_crimp_color', 'vial_cap_color' ), true ) && ! $this->is_pre_release() && '' === $raw ) { return __( 'Vial crimp and cap colors are required for approved and failed reports.', 'pepselect-coa-archive' ); }
		if ( 'vial_crimp_color_other' === $name && 'other' === $this->posted( 'vial_crimp_color' ) && '' === $raw ) { return __( 'Enter the other crimp color.', 'pepselect-coa-archive' ); }
		if ( 'vial_cap_color_other' === $name && 'other' === $this->posted( 'vial_cap_color' ) && '' === $raw ) { return __( 'Enter the other cap color.', 'pepselect-coa-archive' ); }
		if ( 'content_unit' === $name && '' !== $raw && ! array_key_exists( $raw, Compound_Validation::units() ) ) { return __( 'Select a valid content unit.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, self::result_fields(), true ) && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::result_choices() ) ) { return __( 'Select a valid result status.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, self::number_fields(), true ) && '' !== $raw && ! is_numeric( $raw ) ) { return __( 'Enter a valid number.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, self::nonnegative_fields(), true ) && '' !== $raw && (float) $raw < 0 ) { return __( 'This value cannot be negative.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vials_submitted', 'vials_tested' ), true ) && '' !== $raw && false === filter_var( $raw, FILTER_VALIDATE_INT ) ) { return __( 'Enter a whole number.', 'pepselect-coa-archive' ); }
		if ( 'vials_tested' === $name && ( '' !== $raw || ! $this->is_pre_release() ) && ( false === filter_var( $raw, FILTER_VALIDATE_INT ) || (int) $raw < 1 ) ) { return __( 'Vials Tested must be a whole number of at least 1 for approved and failed reports.', 'pepselect-coa-archive' ); }
		if ( 'purity_percentage' === $name && '' !== $raw && ( (float) $raw < 0 || (float) $raw > 100 ) ) { return __( 'Purity Percentage must be between 0 and 100.', 'pepselect-coa-archive' ); }
		if ( 'maximum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'minimum_net_content' ) && (float) $this->posted( 'minimum_net_content' ) > (float) $raw ) { return __( 'Maximum Net Content cannot be less than Minimum Net Content.', 'pepselect-coa-archive' ); }
		if ( 'minimum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'maximum_net_content' ) && (float) $raw > (float) $this->posted( 'maximum_net_content' ) ) { return __( 'Minimum Net Content cannot exceed Maximum Net Content.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'lab_verification_url', 'lab_report_url', 'pending_lab_url' ), true ) && '' !== $raw && ! wp_http_validate_url( $raw ) ) { return __( 'Enter a valid HTTP or HTTPS URL.', 'pepselect-coa-archive' ); }
		if ( 'coa_pdf_id' === $name && $raw && ! $this->valid_pdf( absint( $raw ) ) ) { return __( 'Select a valid PDF attachment.', 'pepselect-coa-archive' ); }
		if ( 'coa_page_images' === $name && ! $this->valid_images( $value ) ) { return __( 'Every certificate page must be a JPG, PNG, or WebP image attachment.', 'pepselect-coa-archive' ); }
		return $valid;
	}

	/** Blocks an exact compound/batch duplicate. @param mixed $valid Existing result. @param mixed $value Batch. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_duplicate( $valid, $value, $field, $input ) {
		unset( $field, $input ); if ( true !== $valid ) { return $valid; }
		$compound = absint( $this->posted( 'compound_id' ) ); $batch = trim( (string) $value ); if ( ! $compound || '' === $batch ) { return $valid; }
		$current = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF validates its nonce.
		$ids = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 50, 'fields' => 'ids', 'no_found_rows' => true, 'post__not_in' => $current ? array( $current ) : array(), 'meta_key' => 'compound_id', 'meta_value' => $compound ) );
		foreach ( $ids as $id ) { if ( 0 === strcasecmp( trim( (string) get_post_meta( $id, 'batch_number', true ) ), $batch ) ) { $link = get_edit_post_link( $id, 'raw' ); return sprintf( __( 'This compound and batch already exist as “%1$s”. Edit: %2$s', 'pepselect-coa-archive' ), get_the_title( $id ), $link ? esc_url_raw( $link ) : __( 'unavailable', 'pepselect-coa-archive' ) ); } }
		return $valid;
	}

	/** Enforces approved-document and failed-result consistency. @param mixed $valid Existing result. @param mixed $value Status. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_approval( $valid, $value, $field, $input ) {
		unset( $field, $input ); if ( true !== $valid || 'approved' !== $value ) { return $valid; }
		if ( ! $this->valid_pdf( absint( $this->posted( 'coa_pdf_id' ) ) ) ) { return __( 'Approved COAs require a valid original PDF.', 'pepselect-coa-archive' ); }
		if ( ! $this->valid_images( $this->posted( 'coa_page_images' ) ) || empty( $this->posted( 'coa_page_images' ) ) ) { return __( 'Approved COAs require at least one valid certificate page image.', 'pepselect-coa-archive' ); }
		if ( ! wp_http_validate_url( trim( (string) $this->posted( 'lab_report_url' ) ) ) ) { return __( 'Approved COAs require a valid Public Lab Report URL.', 'pepselect-coa-archive' ); }
		foreach ( self::result_fields() as $name ) { if ( 'fail' === $this->posted( $name ) ) { return sprintf( __( 'Approved status conflicts with the failed result in %s.', 'pepselect-coa-archive' ), str_replace( '_', ' ', $name ) ); } }
		return $valid;
	}

	/** Reads an ACF value from the current validated request. @param string $name Field name. @return mixed */
	private function posted( $name ) { $key = array_search( $name, self::field_map(), true ); return $key && isset( $_POST['acf'][ $key ] ) ? wp_unslash( $_POST['acf'][ $key ] ) : ''; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	/** Returns whether the submitted overall state is pre-release. @return bool */
	private function is_pre_release() { return in_array( $this->posted( 'coa_status' ), array( 'pending', 'in-testing', 'vendor-vetting' ), true ); }
	/** Validates ACF's raw Ymd or normalized Y-m-d date without locale parsing. @param string $date Date. @return bool */
	private function valid_date( $date ) {
		$format = preg_match( '/^\d{8}$/', $date ) ? '!Ymd' : ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ? '!Y-m-d' : '' );
		if ( ! $format ) { return false; }
		$parsed = \DateTimeImmutable::createFromFormat( $format, $date );
		return $parsed && $parsed->format( '!Ymd' === $format ? 'Ymd' : 'Y-m-d' ) === $date;
	}
	/** Validates a PDF attachment. @param int $id Attachment ID. @return bool */
	private function valid_pdf( $id ) { return $id > 0 && 'attachment' === get_post_type( $id ) && 'application/pdf' === get_post_mime_type( $id ); }
	/** Validates gallery attachment MIME types. @param mixed $ids IDs. @return bool */
	private function valid_images( $ids ) { if ( empty( $ids ) ) { return true; } if ( ! is_array( $ids ) || count( $ids ) > 20 ) { return false; } foreach ( $ids as $id ) { if ( 'attachment' !== get_post_type( absint( $id ) ) || ! in_array( get_post_mime_type( absint( $id ) ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return false; } } return true; }
	/** Returns string length. @param mixed $value Value. @return int */
	private function length( $value ) { return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value ); }
	/** Returns number fields. @return string[] */
	private static function number_fields() { return array( 'claimed_content', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'purity_percentage' ); }
	/** Returns nonnegative fields. @return string[] */
	private static function nonnegative_fields() { return array( 'claimed_content', 'vials_submitted', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'purity_percentage' ); }
	/** Returns integer fields. @return string[] */
	private static function integer_fields() { return array( 'compound_id', 'vials_submitted', 'vials_tested', 'coa_pdf_id' ); }
	/** Returns result status fields. @return string[] */
	private static function result_fields() { return array( 'purity_status', 'identity_status', 'endotoxin_status', 'heavy_metals_status', 'sterility_status' ); }
	/** Maps stable ACF keys to stored names. @return array */
	private static function field_map() { return array( 'field_ps_coa_test_compound_id' => 'compound_id', 'field_ps_coa_test_batch_number' => 'batch_number', 'field_ps_coa_test_internal_batch_id' => 'internal_batch_id', 'field_ps_coa_test_test_date' => 'test_date', 'field_ps_coa_test_expected_coa_date' => 'expected_coa_date', 'field_ps_coa_test_testing_lab' => 'testing_lab', 'field_ps_coa_test_other_testing_lab' => 'other_testing_lab', 'field_ps_coa_test_lab_accession_number' => 'lab_accession_number', 'field_ps_coa_test_status' => 'coa_status', 'field_ps_coa_test_is_current' => 'is_current', 'field_ps_coa_test_vial_crimp_color' => 'vial_crimp_color', 'field_ps_coa_test_vial_crimp_color_other' => 'vial_crimp_color_other', 'field_ps_coa_test_vial_cap_color' => 'vial_cap_color', 'field_ps_coa_test_vial_cap_color_other' => 'vial_cap_color_other', 'field_ps_coa_test_claimed_content' => 'claimed_content', 'field_ps_coa_test_content_unit' => 'content_unit', 'field_ps_coa_test_vials_submitted' => 'vials_submitted', 'field_ps_coa_test_vials_tested' => 'vials_tested', 'field_ps_coa_test_average_net_content' => 'average_net_content', 'field_ps_coa_test_minimum_net_content' => 'minimum_net_content', 'field_ps_coa_test_maximum_net_content' => 'maximum_net_content', 'field_ps_coa_test_net_content_std_dev' => 'net_content_std_dev', 'field_ps_coa_test_content_variance_percent' => 'content_variance_percent', 'field_ps_coa_test_sample_appearance' => 'sample_appearance', 'field_ps_coa_test_purity_percentage' => 'purity_percentage', 'field_ps_coa_test_purity_status' => 'purity_status', 'field_ps_coa_test_purity_method' => 'purity_method', 'field_ps_coa_test_identity_status' => 'identity_status', 'field_ps_coa_test_identity_method' => 'identity_method', 'field_ps_coa_test_endotoxin_status' => 'endotoxin_status', 'field_ps_coa_test_endotoxin_result' => 'endotoxin_result', 'field_ps_coa_test_endotoxin_unit' => 'endotoxin_unit', 'field_ps_coa_test_heavy_metals_status' => 'heavy_metals_status', 'field_ps_coa_test_heavy_metals_summary' => 'heavy_metals_summary', 'field_ps_coa_test_sterility_status' => 'sterility_status', 'field_ps_coa_test_sterility_result' => 'sterility_result', 'field_ps_coa_test_coa_number' => 'coa_number', 'field_ps_coa_test_lab_report_url' => 'lab_report_url', 'field_ps_coa_test_pending_lab_url' => 'pending_lab_url', 'field_ps_coa_test_verification_code' => 'verification_code', 'field_ps_coa_test_lab_verification_url' => 'lab_verification_url', 'field_ps_coa_test_certificate_version' => 'certificate_version', 'field_ps_coa_test_coa_pdf_id' => 'coa_pdf_id', 'field_ps_coa_test_page_images' => 'coa_page_images', 'field_ps_coa_test_public_notes' => 'public_notes', 'field_ps_coa_test_report_notes' => 'report_notes', 'field_ps_coa_test_internal_notes' => 'internal_notes' ); }
}

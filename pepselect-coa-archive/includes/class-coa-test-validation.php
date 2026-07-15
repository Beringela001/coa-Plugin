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
		if ( 'workflow_stage' === $key ) { return COA_Workflow::normalize_stage( $value ); }
		if ( in_array( $key, self::integer_fields(), true ) ) { return absint( $value ); }
		if ( in_array( $key, self::number_fields(), true ) ) { return '' === $value ? '' : ( is_numeric( $value ) ? (float) $value : $value ); }
		if ( in_array( $key, array( 'is_current', 'partial_results_available' ), true ) ) { return empty( $value ) ? 0 : 1; }
		if ( in_array( $key, array( 'public_notes', 'report_notes', 'internal_notes', 'heavy_metals_summary', 'release_decision_note' ), true ) ) { return sanitize_textarea_field( trim( (string) $value ) ); }
		if ( in_array( $key, array( 'lab_verification_url', 'lab_report_url', 'pending_lab_url' ), true ) ) { return esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) ); }
		return sanitize_text_field( trim( (string) $value ) );
	}

	/** Sanitizes gallery IDs while preserving order. @param mixed $value Value. @return array */
	public static function sanitize_gallery( $value ) { return is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array(); }

	/** Sanitizes an ACF value before storage. @param mixed $value Value. @param int|string $post_id Post ID. @param array $field Field. @return mixed */
	public function sanitize_acf_value( $value, $post_id, $field ) { unset( $post_id ); if ( 'workflow_stage' === $field['name'] ) { return COA_Workflow::normalize_stage( $value ); } return in_array( $field['name'], array( 'coa_page_images', 'batch_identity_photos' ), true ) ? self::sanitize_gallery( $value ) : self::sanitize( $value, $field['name'] ); }

	/** Validates an individual ACF value. @param mixed $valid Existing result. @param mixed $value Value. @param array $field Field. @param string $input Input. @return mixed */
	public function validate( $valid, $value, $field, $input ) {
		unset( $input ); if ( true !== $valid ) { return $valid; }
		$name = $field['name']; $raw = is_string( $value ) ? trim( $value ) : $value;
		$stage = COA_Workflow::normalize_stage( $this->posted( 'workflow_stage' ) );
		$partial = ! empty( $this->posted( 'partial_results_available' ) );
		if ( ! COA_Test_Form::field_available( $name, $stage, $partial ) ) { return $valid; }
		if ( 'compound_id' === $name && ( ! $raw || Post_Types::COMPOUND !== get_post_type( absint( $raw ) ) ) ) { return __( 'Select a valid related compound.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && in_array( $stage, array( 'in-testing', 'complete' ), true ) && '' === $raw ) { return __( 'Batch Number is required during verification and for complete reports.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && '' !== $raw && ( $this->length( $raw ) > 120 || ! preg_match( '/^[\p{L}\p{N} _\.\/-]+$/u', $raw ) ) ) { return __( 'Batch Number must be 120 characters or fewer and use only letters, numbers, spaces, hyphens, underscores, periods, or slashes.', 'pepselect-coa-archive' ); }
		$lengths = array( 'internal_batch_id' => 120, 'other_testing_lab' => 120, 'lab_accession_number' => 120, 'vendor_status_note' => 200, 'public_status_note' => 240, 'release_decision_note' => 500, 'other_vial_crimp_color' => 80, 'other_vial_cap_color' => 80, 'sample_appearance' => 200, 'purity_method' => 120, 'identity_method' => 120, 'endotoxin_result' => 120, 'endotoxin_unit' => 50, 'heavy_metals_summary' => 500, 'sterility_result' => 200, 'fentanyl_result' => 200, 'fentanyl_method' => 120, 'fentanyl_specification' => 200, 'fentanyl_notes' => 500, 'coa_number' => 120, 'verification_code' => 200, 'certificate_version' => 50, 'public_notes' => 1000, 'report_notes' => 1000 );
		if ( isset( $lengths[ $name ] ) && $this->length( $raw ) > $lengths[ $name ] ) { return sprintf( __( 'This value must be %d characters or fewer.', 'pepselect-coa-archive' ), $lengths[ $name ] ); }
		if ( 'workflow_stage' === $name && ! array_key_exists( COA_Workflow::normalize_stage( $raw ), COA_Workflow::stages() ) ) { return __( 'Select a valid workflow stage.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'test_date', 'date_received', 'expected_coa_date' ), true ) && '' !== $raw && ! $this->valid_date( $raw ) ) { return __( 'Enter a valid date.', 'pepselect-coa-archive' ); }
		if ( 'expected_coa_date' === $name && in_array( $stage, array( 'submitted-to-lab', 'in-testing' ), true ) && '' === $raw ) { return __( 'Expected COA Date is required after submission and during verification.', 'pepselect-coa-archive' ); }
		if ( 'test_date' === $name && $this->is_approved() && '' === $raw ) { return __( 'Test Date is required for approved reports.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::labs() ) ) { return __( 'Select a valid testing laboratory.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && ( 'in-testing' === $stage || $this->is_approved() ) && '' === $raw ) { return __( 'Testing Laboratory is required during verification and for approved reports.', 'pepselect-coa-archive' ); }
		if ( 'other_testing_lab' === $name && 'other' === $this->posted( 'testing_lab' ) && '' === $raw ) { return __( 'Enter the other laboratory name.', 'pepselect-coa-archive' ); }
		if ( 'coa_status' === $name && ! array_key_exists( $raw, COA_Test_Fields::statuses() ) && ! $this->preserves_legacy_status( $raw ) ) { return __( 'Select Pending, Approved, or Failed.', 'pepselect-coa-archive' ); }
		if ( 'coa_status' === $name && in_array( $raw, array( 'approved', 'failed' ), true ) && 'complete' !== $this->posted( 'workflow_stage' ) ) { return __( 'Approved and failed outcomes require Workflow Stage to be Complete.', 'pepselect-coa-archive' ); }
		if ( 'release_decision_note' === $name && 'failed' === $this->posted( 'coa_status' ) && '' === $raw ) { return __( 'Failed reports require a Release Decision Note.', 'pepselect-coa-archive' ); }
		if ( 'is_current' === $name && ! empty( $raw ) && ( 'approved' !== $this->posted( 'coa_status' ) || 'complete' !== $this->posted( 'workflow_stage' ) ) ) { return __( 'Current COA requires an Approved outcome and Complete workflow stage.', 'pepselect-coa-archive' ); }
		if ( 'is_current' === $name && ! empty( $raw ) && 'publish' !== $this->posted_post_status() ) { return __( 'Current COA must be published.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vial_crimp_color', 'vial_cap_color' ), true ) && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::vial_colors() ) && ! $this->preserves_legacy_color( $name, $raw ) ) { return __( 'Select a valid vial color.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vial_crimp_color', 'vial_cap_color' ), true ) && in_array( $stage, array( 'waiting-on-vendor', 'submitted-to-lab', 'in-testing', 'complete' ), true ) && '' === $raw ) { return __( 'Vial crimp and cap colors are required at this workflow stage.', 'pepselect-coa-archive' ); }
		if ( 'other_vial_crimp_color' === $name && 'other' === $this->posted( 'vial_crimp_color' ) && '' === $raw ) { return __( 'Enter the other crimp color.', 'pepselect-coa-archive' ); }
		if ( 'other_vial_cap_color' === $name && 'other' === $this->posted( 'vial_cap_color' ) && '' === $raw ) { return __( 'Enter the other cap color.', 'pepselect-coa-archive' ); }
		if ( 'content_unit' === $name && '' !== $raw && ! array_key_exists( $raw, Compound_Validation::units() ) ) { return __( 'Select a valid content unit.', 'pepselect-coa-archive' ); }
		if ( 'fentanyl_status' === $name && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::fentanyl_choices() ) ) { return __( 'Select a valid Fentanyl Screen status.', 'pepselect-coa-archive' ); }
		if ( 'fentanyl_result' === $name ) {
			$status = sanitize_key( (string) $this->posted( 'fentanyl_status' ) );
			$expected = 'pass' === $status ? 'Not detected' : ( 'fail' === $status ? 'Detected' : '' );
			if ( $expected !== $raw ) { return __( 'The Fentanyl Screen result must match its selected status.', 'pepselect-coa-archive' ); }
		}
		if ( 'fentanyl_method' === $name && 'Immunoassay' !== $raw ) { return __( 'The Fentanyl Screen method must be Immunoassay.', 'pepselect-coa-archive' ); }
		if ( 'fentanyl_specification' === $name && 'Immunoassay, 50 ng/mL cutoff' !== $raw ) { return __( 'The Fentanyl Screen specification must use the 50 ng/mL cutoff.', 'pepselect-coa-archive' ); }
		if ( 'fentanyl_status' !== $name && in_array( $name, self::result_fields(), true ) && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::result_choices() ) ) { return __( 'Select a valid result status.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, self::number_fields(), true ) && '' !== $raw && ! is_numeric( $raw ) ) { return __( 'Enter a valid number.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, self::nonnegative_fields(), true ) && '' !== $raw && (float) $raw < 0 ) { return __( 'This value cannot be negative.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vials_submitted', 'vials_tested' ), true ) && '' !== $raw && false === filter_var( $raw, FILTER_VALIDATE_INT ) ) { return __( 'Enter a whole number.', 'pepselect-coa-archive' ); }
		if ( 'vials_tested' === $name && ( '' !== $raw || $this->is_approved() ) && ( false === filter_var( $raw, FILTER_VALIDATE_INT ) || ( $this->is_approved() && (int) $raw < 1 ) ) ) { return __( 'Vials Tested must be a whole number of at least 1 for approved reports.', 'pepselect-coa-archive' ); }
		if ( 'purity_percentage' === $name && '' !== $raw && ( (float) $raw < 0 || (float) $raw > 100 ) ) { return __( 'Purity Percentage must be between 0 and 100.', 'pepselect-coa-archive' ); }
		if ( 'maximum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'minimum_net_content' ) && (float) $this->posted( 'minimum_net_content' ) > (float) $raw ) { return __( 'Maximum Net Content cannot be less than Minimum Net Content.', 'pepselect-coa-archive' ); }
		if ( 'minimum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'maximum_net_content' ) && (float) $raw > (float) $this->posted( 'maximum_net_content' ) ) { return __( 'Minimum Net Content cannot exceed Maximum Net Content.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'lab_verification_url', 'lab_report_url', 'pending_lab_url' ), true ) && '' !== $raw && ! wp_http_validate_url( $raw ) ) { return __( 'Enter a valid HTTP or HTTPS URL.', 'pepselect-coa-archive' ); }
		if ( 'batch_vial_photo' === $name && $raw && ! $this->valid_image( absint( $raw ), true ) ) { return __( 'Select an image attachment you have permission to use.', 'pepselect-coa-archive' ); }
		if ( 'batch_vial_photo' === $name && ! $raw && in_array( $stage, array( 'in-testing', 'complete' ), true ) && ! $this->legacy_batch_photo_exempt( $stage ) ) { return __( 'A photo of the exact tested vial is required before Verification in Progress or Complete can be saved.', 'pepselect-coa-archive' ); }
		if ( 'batch_identity_photos' === $name && ! $this->valid_images( $value, true ) ) { return __( 'Every Batch Identity Photo must be an image attachment you have permission to use.', 'pepselect-coa-archive' ); }
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

	/** Enforces final-outcome workflow, documentation, and current-state consistency. @param mixed $valid Existing result. @param mixed $value Status. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_approval( $valid, $value, $field, $input ) {
		unset( $field, $input ); if ( true !== $valid ) { return $valid; }
		if ( in_array( $value, array( 'approved', 'failed' ), true ) && 'complete' !== $this->posted( 'workflow_stage' ) ) { return __( 'Approved and failed outcomes require Workflow Stage to be Complete.', 'pepselect-coa-archive' ); }
		if ( 'failed' === $value ) {
			if ( $this->posted( 'is_current' ) ) { return __( 'Failed reports cannot be marked Current COA.', 'pepselect-coa-archive' ); }
			if ( '' === trim( (string) $this->posted( 'release_decision_note' ) ) ) { return __( 'Failed reports require a Release Decision Note.', 'pepselect-coa-archive' ); }
			return $valid;
		}
		if ( 'approved' !== $value ) { return $valid; }
		if ( 'ils-labs' === $this->posted( 'testing_lab' ) ) {
			$fentanyl_status = sanitize_key( (string) $this->posted( 'fentanyl_status' ) );
			$fentanyl_result = trim( (string) $this->posted( 'fentanyl_result' ) );
			$fentanyl_method = trim( (string) $this->posted( 'fentanyl_method' ) );
			$fentanyl_spec = trim( (string) $this->posted( 'fentanyl_specification' ) );
			$fentanyl_success = 'pass' === $fentanyl_status && 'Not detected' === $fentanyl_result && 'Immunoassay' === $fentanyl_method && 'Immunoassay, 50 ng/mL cutoff' === $fentanyl_spec;
			if ( ! $fentanyl_success ) { return __( 'Completed approved ILS reports require a passing Fentanyl Screen at the 50 ng/mL cutoff.', 'pepselect-coa-archive' ); }
		}
		if ( ! $this->valid_pdf( absint( $this->posted( 'coa_pdf_id' ) ) ) ) { return __( 'Approved COAs require a valid original PDF.', 'pepselect-coa-archive' ); }
		if ( ! $this->valid_images( $this->posted( 'coa_page_images' ) ) || empty( $this->posted( 'coa_page_images' ) ) ) { return __( 'Approved COAs require at least one valid certificate page image.', 'pepselect-coa-archive' ); }
		if ( ! wp_http_validate_url( trim( (string) $this->posted( 'lab_report_url' ) ) ) ) { return __( 'Approved reports require the direct laboratory report URL.', 'pepselect-coa-archive' ); }
		foreach ( self::result_fields() as $name ) { if ( 'fail' === $this->posted( $name ) ) { return sprintf( __( 'Approved status conflicts with the failed result in %s.', 'pepselect-coa-archive' ), str_replace( '_', ' ', $name ) ); } }
		return $valid;
	}

	/** Reads an ACF value from the current validated request. @param string $name Field name. @return mixed */
	private function posted( $name ) { $key = array_search( $name, self::field_map(), true ); return $key && isset( $_POST['acf'][ $key ] ) ? wp_unslash( $_POST['acf'][ $key ] ) : ''; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	/** Returns whether the submitted final outcome is approved. @return bool */
	private function is_approved() { return 'approved' === $this->posted( 'coa_status' ); }
	/** Returns whether the current record is retaining its own read-only legacy outcome. @param string $status Submitted status. @return bool */
	private function preserves_legacy_status( $status ) { $post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; return $post_id && in_array( $status, array( 'archived', 'superseded' ), true ) && $status === get_post_meta( $post_id, 'coa_status', true ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	/** Returns whether an edit retains its own formerly supported vial-color value. @param string $name Field. @param string $value Submitted value. @return bool */
	private function preserves_legacy_color( $name, $value ) { $post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; return $post_id && $value === get_post_meta( $post_id, $name, true ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
	/** Allows a published legacy record to remain untouched without forcing a destructive migration. @param string $stage Submitted stage. @return bool */
	private function legacy_batch_photo_exempt( $stage ) {
		$post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) || $stage !== COA_Workflow::stage( $post_id ) || get_post_meta( $post_id, 'batch_vial_photo', true ) ) { return false; }
		$material = array( 'coa_status', 'batch_number', 'batch_identity_photos', 'test_date', 'date_received', 'testing_lab', 'other_testing_lab', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'claimed_content', 'content_unit', 'vials_submitted', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance', 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'coa_number', 'lab_report_url', 'certificate_version', 'coa_pdf_id', 'coa_page_images', 'public_notes', 'report_notes', 'release_decision_note' );
		foreach ( $material as $name ) {
			$key = array_search( $name, self::field_map(), true ); if ( ! $key || ! isset( $_POST['acf'][ $key ] ) ) { continue; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted = wp_unslash( $_POST['acf'][ $key ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted = is_array( $posted ) ? self::sanitize_gallery( $posted ) : self::sanitize( $posted, $name );
			$stored = get_post_meta( $post_id, $name, true ); $stored = is_array( $stored ) ? self::sanitize_gallery( $stored ) : self::sanitize( $stored, $name );
			if ( $posted !== $stored ) { return false; }
		}
		return true;
	}
	/** Returns the submitted/core post status. @return string */
	private function posted_post_status() { if ( isset( $_POST['post_status'] ) ) { return sanitize_key( wp_unslash( $_POST['post_status'] ) ); } $post_id = isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; return $post_id ? (string) get_post_status( $post_id ) : 'publish'; } // phpcs:ignore WordPress.Security.NonceVerification.Missing
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
	private function valid_image( $id, $check_permission = false ) { return $id > 0 && 'attachment' === get_post_type( $id ) && wp_attachment_is_image( $id ) && ( ! $check_permission || current_user_can( 'edit_post', $id ) ); }
	/** Validates image galleries while preserving order. @param mixed $ids IDs. @param bool $check_permission Require attachment permission. @return bool */
	private function valid_images( $ids, $check_permission = false ) { if ( empty( $ids ) ) { return true; } if ( ! is_array( $ids ) || count( $ids ) > 20 ) { return false; } foreach ( $ids as $id ) { if ( ! $this->valid_image( absint( $id ), $check_permission ) || ! in_array( get_post_mime_type( absint( $id ) ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return false; } } return true; }
	/** Returns string length. @param mixed $value Value. @return int */
	private function length( $value ) { return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value ); }
	/** Returns number fields. @return string[] */
	private static function number_fields() { return array( 'claimed_content', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'purity_percentage' ); }
	/** Returns nonnegative fields. @return string[] */
	private static function nonnegative_fields() { return array( 'claimed_content', 'vials_submitted', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'purity_percentage' ); }
	/** Returns integer fields. @return string[] */
	private static function integer_fields() { return array( 'compound_id', 'vials_submitted', 'vials_tested', 'coa_pdf_id', 'batch_vial_photo' ); }
	/** Returns result status fields. @return string[] */
	private static function result_fields() { return array( 'purity_status', 'identity_status', 'endotoxin_status', 'heavy_metals_status', 'sterility_status', 'fentanyl_status' ); }
	/** Returns whether a reported Fentanyl result explicitly communicates a successful screen. @param mixed $value Result text. @return bool */
	public static function fentanyl_result_is_successful( $value ) {
		$normalized = strtolower( trim( (string) $value ) );
		$normalized = preg_replace( '/[\s\._-]+/', ' ', $normalized );
		if ( in_array( $normalized, array( 'nd', 'n/d', 'negative' ), true ) ) { return true; }
		return (bool) preg_match( '/(?:not detected|none detected|no fentanyl detected|below (?:the )?(?:limit of )?(?:detection|quantitation|reporting))/', $normalized );
	}
	/** Maps stable ACF keys to stored names. @return array */
	private static function field_map() {
		$names = array( 'compound_id', 'batch_number', 'batch_vial_photo', 'batch_identity_photos', 'internal_batch_id', 'test_date', 'workflow_stage', 'expected_coa_date', 'vendor_status_note', 'public_status_note', 'partial_results_available', 'release_decision_note', 'testing_lab', 'other_testing_lab', 'lab_accession_number', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'claimed_content', 'content_unit', 'vials_submitted', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance', 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'coa_number', 'lab_report_url', 'pending_lab_url', 'verification_code', 'lab_verification_url', 'certificate_version', 'coa_pdf_id', 'public_notes', 'report_notes', 'internal_notes' );
		$map = array( 'field_ps_coa_test_status' => 'coa_status', 'field_ps_coa_test_page_images' => 'coa_page_images' );
		foreach ( $names as $name ) { $map[ 'field_ps_coa_test_' . $name ] = $name; }
		return $map;
	}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Centralizes COA Test sanitization and ACF validation. */
final class COA_Test_Validation {
	/** @var bool */
	private $registered = false;

	/**
	 * Injected value context used instead of $_POST when validating a non-form
	 * write (the REST write endpoint). Null means "read the ACF form payload",
	 * which keeps the admin path and the existing test suite byte-identical.
	 *
	 * Shape: array( 'values' => array<string,mixed>, 'post_id' => int, 'post_status' => string ).
	 *
	 * @var array|null
	 */
	private $context = null;

	/**
	 * Supplies field values from outside an ACF form submission.
	 *
	 * Callers must pass a COMPLETE merged value set (stored values overlaid with
	 * the submitted ones). Cross-field rules read siblings through posted(), so a
	 * partial set would make a valid stored record fail against its own data.
	 *
	 * @param array  $values      Field name => value, already merged.
	 * @param int    $post_id     Target post, 0 for a create.
	 * @param string $post_status Intended post status.
	 * @return void
	 */
	public function set_context( array $values, $post_id = 0, $post_status = '' ) {
		$this->context = array( 'values' => $values, 'post_id' => absint( $post_id ), 'post_status' => sanitize_key( (string) $post_status ) );
	}

	/** Restores $_POST-backed reads. @return void */
	public function clear_context() { $this->context = null; }

	/** Returns whether an injected context is active. @return bool */
	public function has_context() { return null !== $this->context; }

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
		if ( in_array( $key, array( 'is_current' ), true ) ) { return empty( $value ) ? 0 : 1; }
		if ( in_array( $key, array( 'public_notes', 'report_notes', 'internal_notes', 'heavy_metals_summary', 'release_decision_note' ), true ) ) { return sanitize_textarea_field( trim( (string) $value ) ); }
		if ( in_array( $key, array( 'lab_verification_url', 'lab_report_url' ), true ) ) { return esc_url_raw( trim( (string) $value ), array( 'http', 'https' ) ); }
		return sanitize_text_field( trim( (string) $value ) );
	}

	/** Sanitizes gallery IDs while preserving order. @param mixed $value Value. @return array */
	public static function sanitize_gallery( $value ) { return is_array( $value ) ? array_values( array_filter( array_map( 'absint', $value ) ) ) : array(); }

	/**
	 * Returns the guidance checklist derived from the same stage/status conditions
	 * enforced below. This method is read-only and never substitutes for ACF validation.
	 *
	 * @param int    $post_id COA Test ID.
	 * @param string $stage Optional normalized stage.
	 * @param string $status Optional outcome.
	 * @param string|null $lab_override Optional selected laboratory used by live guidance.
	 * @return array
	 */
	public static function workflow_requirements( $post_id, $stage = '', $status = '', $lab_override = null ) {
		$stage = COA_Workflow::normalize_stage( $stage ? $stage : get_post_meta( $post_id, 'workflow_stage', true ) );
		if ( ! array_key_exists( $stage, COA_Workflow::stages() ) ) { $stage = 'vendor-vetting'; }
		$status = sanitize_key( $status ? $status : get_post_meta( $post_id, 'coa_status', true ) );
		if ( ! array_key_exists( $status, COA_Test_Fields::statuses() ) ) { $status = 'pending'; }
		$lab = sanitize_key( (string) ( null === $lab_override ? get_post_meta( $post_id, 'testing_lab', true ) : $lab_override ) );
		$rules = array(
			array( 'label' => __( 'Related Compound', 'pepselect-coa-archive' ), 'fields' => array( 'compound_id' ), 'required' => 'always' ),
			array( 'label' => __( 'Batch Number', 'pepselect-coa-archive' ), 'fields' => array( 'batch_number' ), 'required' => 'stage', 'stages' => array( 'in-testing', 'complete' ), 'starts' => 'in-testing' ),
			array( 'label' => __( 'Testing Laboratory', 'pepselect-coa-archive' ), 'fields' => array( 'testing_lab' ), 'required' => 'lab', 'starts' => 'in-testing' ),
			array( 'label' => __( 'Other Laboratory Name', 'pepselect-coa-archive' ), 'fields' => array( 'other_testing_lab' ), 'required' => 'other-lab', 'starts' => 'submitted-to-lab' ),
			array( 'label' => __( 'Cap Color', 'pepselect-coa-archive' ), 'fields' => array( 'vial_cap_color' ), 'required' => 'stage', 'stages' => array( 'submitted-to-lab', 'in-testing', 'complete' ), 'starts' => 'submitted-to-lab' ),
			array( 'label' => __( 'Crimp Color', 'pepselect-coa-archive' ), 'fields' => array( 'vial_crimp_color' ), 'required' => 'stage', 'stages' => array( 'submitted-to-lab', 'in-testing', 'complete' ), 'starts' => 'submitted-to-lab' ),
			array( 'label' => __( 'Batch Vial Photo', 'pepselect-coa-archive' ), 'fields' => array( 'batch_vial_photo' ), 'required' => 'stage', 'stages' => array( 'in-testing', 'complete' ), 'starts' => 'in-testing' ),
			array( 'label' => __( 'Expected COA Date', 'pepselect-coa-archive' ), 'fields' => array( 'expected_coa_date' ), 'required' => 'stage', 'stages' => array( 'submitted-to-lab', 'in-testing' ), 'starts' => 'submitted-to-lab' ),
			array( 'label' => __( 'Claimed Content', 'pepselect-coa-archive' ), 'fields' => array( 'claimed_content' ), 'required' => 'optional' ),
			array( 'label' => __( 'Claimed-Content Unit', 'pepselect-coa-archive' ), 'fields' => array( 'content_unit' ), 'required' => 'optional' ),
			array( 'label' => __( 'Vials Tested', 'pepselect-coa-archive' ), 'fields' => array( 'vials_tested' ), 'required' => 'approved', 'starts' => 'complete', 'validator' => 'positive' ),
			array( 'label' => __( 'Sample Information', 'pepselect-coa-archive' ), 'fields' => array( 'sample_appearance', 'average_net_content' ), 'required' => 'optional', 'starts' => 'submitted-to-lab' ),
			array( 'label' => __( 'Test Date', 'pepselect-coa-archive' ), 'fields' => array( 'test_date' ), 'required' => 'approved', 'starts' => 'complete' ),
			array( 'label' => __( 'Documented Test Results', 'pepselect-coa-archive' ), 'fields' => self::result_evidence_fields(), 'required' => 'optional', 'starts' => 'in-testing', 'validator' => 'result-evidence' ),
			array( 'label' => __( 'Original COA PDF', 'pepselect-coa-archive' ), 'fields' => array( 'coa_pdf_id' ), 'required' => 'approved', 'starts' => 'complete', 'validator' => 'pdf' ),
			array( 'label' => __( 'Certificate Page Images', 'pepselect-coa-archive' ), 'fields' => array( 'coa_page_images' ), 'required' => 'approved', 'starts' => 'complete' ),
			array( 'label' => __( 'Lab Report URL', 'pepselect-coa-archive' ), 'fields' => array( 'lab_report_url' ), 'required' => 'approved', 'starts' => 'complete', 'validator' => 'url' ),
			array( 'label' => __( 'Fentanyl Screen Method', 'pepselect-coa-archive' ), 'fields' => array( 'fentanyl_method' ), 'required' => 'stage', 'stages' => array( 'complete' ), 'starts' => 'complete', 'validator' => 'fentanyl-method' ),
			array( 'label' => __( 'Fentanyl Screen Specification', 'pepselect-coa-archive' ), 'fields' => array( 'fentanyl_specification' ), 'required' => 'stage', 'stages' => array( 'complete' ), 'starts' => 'complete', 'validator' => 'fentanyl-specification' ),
			array( 'label' => __( 'Fentanyl Screen', 'pepselect-coa-archive' ), 'fields' => array( 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification' ), 'required' => 'approved-ils', 'starts' => 'complete', 'validator' => 'fentanyl' ),
			array( 'label' => __( 'Release Decision Note', 'pepselect-coa-archive' ), 'fields' => array( 'release_decision_note' ), 'required' => 'failed', 'starts' => 'complete' ),
			array( 'label' => __( 'Current or Past Designation', 'pepselect-coa-archive' ), 'fields' => array( 'is_current' ), 'required' => 'designation' ),
		);
		$requirements = array();
		foreach ( $rules as $rule ) {
			if ( 'other-lab' === $rule['required'] && 'other' !== $lab ) { continue; }
			$required = self::requirement_is_required( $rule, $stage, $status, $lab );
			$complete = self::requirement_is_complete( $post_id, $rule );
			if ( $required ) { $state = $complete ? 'complete' : 'missing'; }
			elseif ( 'designation' === $rule['required'] ) { $state = 'complete'; }
			elseif ( $complete ) { $state = 'complete'; }
			elseif ( isset( $rule['starts'] ) && self::stage_rank( $stage ) < self::stage_rank( $rule['starts'] ) ) { $state = 'not-required'; }
			else { $state = 'optional'; }
			$requirements[] = array( 'label' => $rule['label'], 'state' => $state );
		}
		return $requirements;
	}

	/** Returns stage-specific, non-prescriptive guidance. @param string $stage Stage. @param string $status Outcome. @return string */
	public static function workflow_guidance( $stage, $status ) {
		$stage = COA_Workflow::normalize_stage( $stage );
		if ( 'vendor-vetting' === $stage ) { return __( 'Do not guess physical-batch or laboratory data. Add it only after a real vendor batch exists.', 'pepselect-coa-archive' ); }
		if ( 'waiting-on-vendor' === $stage ) { return __( 'Claimed content, unit, vial counts, and known packaging details may be recorded. Cap, crimp, and the exact Batch Vial Photo remain optional.', 'pepselect-coa-archive' ); }
		if ( 'submitted-to-lab' === $stage ) { return __( 'Expected COA Date and the known vial colors are required by current validation. Laboratory identity and batch information remain protected from public display until Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'in-testing' === $stage ) { return __( 'Complete the actual batch, vial photo, cap, crimp, laboratory, and expected-date requirements before saving Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'failed' === $status ) { return __( 'A Failed completed record requires a Release Decision Note. Successful result language is not required.', 'pepselect-coa-archive' ); }
		if ( 'approved' === $status ) { return __( 'Approved completed records require their final date, laboratory, documents, Lab Report URL, and applicable ILS Fentanyl Screen evidence.', 'pepselect-coa-archive' ); }
		return __( 'Complete final evidence before changing COA Status to Approved or Failed.', 'pepselect-coa-archive' );
	}

	/** Sanitizes an ACF value before storage. @param mixed $value Value. @param int|string $post_id Post ID. @param array $field Field. @return mixed */
	public function sanitize_acf_value( $value, $post_id, $field ) { unset( $post_id ); if ( 'workflow_stage' === $field['name'] ) { return COA_Workflow::normalize_stage( $value ); } return in_array( $field['name'], array( 'coa_page_images', 'batch_identity_photos' ), true ) ? self::sanitize_gallery( $value ) : self::sanitize( $value, $field['name'] ); }

	/** Validates an individual ACF value. @param mixed $valid Existing result. @param mixed $value Value. @param array $field Field. @param string $input Input. @return mixed */
	public function validate( $valid, $value, $field, $input ) {
		unset( $input ); if ( true !== $valid ) { return $valid; }
		$name = $field['name']; $raw = is_string( $value ) ? trim( $value ) : $value;
		$stage = COA_Workflow::normalize_stage( $this->posted( 'workflow_stage' ) );
		$partial = false; // Partial-results feature removed (Ops §16); never gate on it.
		if ( ! COA_Test_Form::field_available( $name, $stage, $partial ) ) { return $valid; }
		if ( 'compound_id' === $name && ( ! $raw || Post_Types::COMPOUND !== get_post_type( absint( $raw ) ) ) ) { return __( 'Select a valid related compound.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && 'in-testing' === $stage && '' === $raw ) { return __( 'Batch Number is required before moving this test to Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && 'complete' === $stage && '' === $raw ) { return __( 'Batch Number is required before saving a Completed test.', 'pepselect-coa-archive' ); }
		if ( 'batch_number' === $name && '' !== $raw && ( $this->length( $raw ) > 120 || ! preg_match( '/^[\p{L}\p{N} _\.\/-]+$/u', $raw ) ) ) { return __( 'Batch Number must be 120 characters or fewer and use only letters, numbers, spaces, hyphens, underscores, periods, or slashes.', 'pepselect-coa-archive' ); }
		$lengths = array( 'other_testing_lab' => 120, 'lab_accession_number' => 120, 'vendor_status_note' => 200, 'public_status_note' => 240, 'release_decision_note' => 500, 'other_vial_crimp_color' => 80, 'other_vial_cap_color' => 80, 'sample_appearance' => 200, 'purity_method' => 120, 'identity_method' => 120, 'endotoxin_result' => 120, 'endotoxin_unit' => 50, 'heavy_metals_summary' => 500, 'sterility_result' => 200, 'fentanyl_result' => 200, 'fentanyl_method' => 120, 'fentanyl_specification' => 200, 'fentanyl_notes' => 500, 'coa_number' => 120, 'verification_code' => 200, 'certificate_version' => 50, 'public_notes' => 1000, 'report_notes' => 1000 );
		if ( isset( $lengths[ $name ] ) && $this->length( $raw ) > $lengths[ $name ] ) { return sprintf( __( 'This value must be %d characters or fewer.', 'pepselect-coa-archive' ), $lengths[ $name ] ); }
		if ( 'workflow_stage' === $name && ! array_key_exists( COA_Workflow::normalize_stage( $raw ), COA_Workflow::stages() ) ) { return __( 'Select a valid workflow stage.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'test_date', 'date_received', 'expected_coa_date' ), true ) && '' !== $raw && ! $this->valid_date( $raw ) ) { return __( 'Enter a valid date.', 'pepselect-coa-archive' ); }
		if ( 'expected_coa_date' === $name && 'submitted-to-lab' === $stage && '' === $raw ) { return __( 'Expected COA Date is required at the Submitted to Laboratory stage.', 'pepselect-coa-archive' ); }
		if ( 'expected_coa_date' === $name && 'in-testing' === $stage && '' === $raw ) { return __( 'Expected COA Date is required while the test is in Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'test_date' === $name && $this->is_approved() && '' === $raw ) { return __( 'Test Date is required before saving an Approved completed report.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::labs() ) ) { return __( 'Select a valid testing laboratory.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && 'in-testing' === $stage && '' === $raw ) { return __( 'Testing Laboratory is required before moving this test to Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'testing_lab' === $name && $this->is_approved() && '' === $raw ) { return __( 'Testing Laboratory is required before saving an Approved completed report.', 'pepselect-coa-archive' ); }
		if ( 'other_testing_lab' === $name && 'other' === $this->posted( 'testing_lab' ) && '' === $raw ) { return __( 'Enter the other laboratory name.', 'pepselect-coa-archive' ); }
		if ( 'coa_status' === $name && ! array_key_exists( $raw, COA_Test_Fields::statuses() ) && ! $this->preserves_legacy_status( $raw ) ) { return __( 'Select Pending, Approved, or Failed.', 'pepselect-coa-archive' ); }
		if ( 'coa_status' === $name && in_array( $raw, array( 'approved', 'failed' ), true ) && 'complete' !== $this->posted( 'workflow_stage' ) ) { return __( 'Workflow Stage must be Completed before COA Status can be Approved or Failed.', 'pepselect-coa-archive' ); }
		if ( 'release_decision_note' === $name && 'failed' === $this->posted( 'coa_status' ) && '' === $raw ) { return __( 'Release Decision Note is required before saving a Failed completed report.', 'pepselect-coa-archive' ); }
		if ( 'is_current' === $name && ! empty( $raw ) && ( 'approved' !== $this->posted( 'coa_status' ) || 'complete' !== $this->posted( 'workflow_stage' ) ) ) { return __( 'Current COA requires an Approved outcome and Complete workflow stage.', 'pepselect-coa-archive' ); }
		if ( 'is_current' === $name && ! empty( $raw ) && 'publish' !== $this->posted_post_status() ) { return __( 'Current COA must be published.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'vial_crimp_color', 'vial_cap_color' ), true ) && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::vial_colors() ) && ! $this->preserves_legacy_color( $name, $raw ) ) { return __( 'Select a valid vial color.', 'pepselect-coa-archive' ); }
		if ( 'vial_cap_color' === $name && in_array( $stage, array( 'submitted-to-lab', 'in-testing', 'complete' ), true ) && '' === $raw ) { return __( 'Cap Color is required once the physical vendor batch has arrived at the Submitted to Laboratory stage.', 'pepselect-coa-archive' ); }
		if ( 'vial_crimp_color' === $name && in_array( $stage, array( 'submitted-to-lab', 'in-testing', 'complete' ), true ) && '' === $raw ) { return __( 'Crimp Color is required once the physical vendor batch has arrived at the Submitted to Laboratory stage.', 'pepselect-coa-archive' ); }
		if ( 'other_vial_crimp_color' === $name && 'other' === $this->posted( 'vial_crimp_color' ) && '' === $raw ) { return __( 'Enter the other crimp color.', 'pepselect-coa-archive' ); }
		if ( 'other_vial_cap_color' === $name && 'other' === $this->posted( 'vial_cap_color' ) && '' === $raw ) { return __( 'Enter the other cap color.', 'pepselect-coa-archive' ); }
		if ( 'content_unit' === $name && '' !== $raw && ! array_key_exists( $raw, Compound_Validation::units() ) ) { return __( 'Select a valid content unit.', 'pepselect-coa-archive' ); }
		if ( 'fentanyl_status' === $name && '' !== $raw && ! array_key_exists( $raw, COA_Test_Fields::fentanyl_choices() ) ) { return __( 'Select a valid Fentanyl Screen status.', 'pepselect-coa-archive' ); }
		// Parity with the ACF definition: the Fentanyl Screen select is allow_null=0
		// with a 'not-tested' default, so a form can never submit an empty value.
		if ( 'fentanyl_status' === $name && '' === $raw ) { return __( 'Select a valid Fentanyl Screen status.', 'pepselect-coa-archive' ); }
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
		if ( 'vials_tested' === $name && '' !== $raw && false === filter_var( $raw, FILTER_VALIDATE_INT ) ) { return __( 'Enter a whole number.', 'pepselect-coa-archive' ); }
		if ( 'vials_tested' === $name && ( '' !== $raw || $this->is_approved() ) && ( false === filter_var( $raw, FILTER_VALIDATE_INT ) || ( $this->is_approved() && (int) $raw < 1 ) ) ) { return __( 'Vials Tested must be a whole number of at least 1 for approved reports.', 'pepselect-coa-archive' ); }
		// Parity with the ACF definition (min => 1): a recorded vial count is always
		// at least one, at every stage, not only for approved reports.
		if ( 'vials_tested' === $name && '' !== $raw && (int) $raw < 1 ) { return __( 'Vials Tested must be at least 1 when recorded.', 'pepselect-coa-archive' ); }
		if ( 'purity_percentage' === $name && '' !== $raw && ( (float) $raw < 0 || (float) $raw > 100 ) ) { return __( 'Purity Percentage must be between 0 and 100.', 'pepselect-coa-archive' ); }
		if ( 'maximum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'minimum_net_content' ) && (float) $this->posted( 'minimum_net_content' ) > (float) $raw ) { return __( 'Maximum Net Content cannot be less than Minimum Net Content.', 'pepselect-coa-archive' ); }
		if ( 'minimum_net_content' === $name && '' !== $raw && '' !== $this->posted( 'maximum_net_content' ) && (float) $raw > (float) $this->posted( 'maximum_net_content' ) ) { return __( 'Minimum Net Content cannot exceed Maximum Net Content.', 'pepselect-coa-archive' ); }
		if ( in_array( $name, array( 'lab_verification_url', 'lab_report_url' ), true ) && '' !== $raw && ! wp_http_validate_url( $raw ) ) { return __( 'Enter a valid HTTP or HTTPS URL.', 'pepselect-coa-archive' ); }
		if ( 'batch_vial_photo' === $name && $raw && ! $this->valid_image( absint( $raw ), true ) ) { return __( 'Select an image attachment you have permission to use.', 'pepselect-coa-archive' ); }
		// Parity with the ACF definition (mime_types jpg,jpeg,png,webp): valid_image()
		// alone would also accept GIF, which the form can never produce.
		if ( 'batch_vial_photo' === $name && $raw && ! in_array( get_post_mime_type( absint( $raw ) ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return __( 'The Batch Vial Photo must be a JPG, PNG, or WebP image.', 'pepselect-coa-archive' ); }
		if ( 'laboratory_logo' === $name && $raw && ! $this->valid_laboratory_logo( absint( $raw ) ) ) { return __( 'Select a safe image attachment you have permission to use as the laboratory logo.', 'pepselect-coa-archive' ); }
		if ( 'batch_vial_photo' === $name && ! $raw && 'in-testing' === $stage && ! $this->legacy_batch_photo_exempt( $stage ) ) { return __( 'Batch Vial Photo is required before moving this test to Verification in Progress.', 'pepselect-coa-archive' ); }
		if ( 'batch_vial_photo' === $name && ! $raw && 'complete' === $stage && ! $this->legacy_batch_photo_exempt( $stage ) ) { return __( 'Batch Vial Photo is required before saving a Completed test.', 'pepselect-coa-archive' ); }
		if ( 'batch_identity_photos' === $name && ! $this->valid_images( $value, true ) ) { return __( 'Every Batch Identity Photo must be an image attachment you have permission to use.', 'pepselect-coa-archive' ); }
		if ( 'coa_pdf_id' === $name && $raw && ! $this->valid_pdf( absint( $raw ) ) ) { return __( 'Select a valid PDF attachment.', 'pepselect-coa-archive' ); }
		if ( 'coa_page_images' === $name && ! $this->valid_images( $value ) ) { return __( 'Every certificate page must be a JPG, PNG, or WebP image attachment.', 'pepselect-coa-archive' ); }
		return $valid;
	}

	/** Blocks an exact compound/batch duplicate. @param mixed $valid Existing result. @param mixed $value Batch. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_duplicate( $valid, $value, $field, $input ) {
		unset( $field, $input ); if ( true !== $valid ) { return $valid; }
		$compound = absint( $this->posted( 'compound_id' ) ); $batch = trim( (string) $value ); if ( ! $compound || '' === $batch ) { return $valid; }
		$current = $this->context_post_id();
		$ids = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => 50, 'fields' => 'ids', 'no_found_rows' => true, 'post__not_in' => $current ? array( $current ) : array(), 'meta_key' => 'compound_id', 'meta_value' => $compound ) );
		foreach ( $ids as $id ) { if ( 0 === strcasecmp( trim( (string) get_post_meta( $id, 'batch_number', true ) ), $batch ) ) { $link = get_edit_post_link( $id, 'raw' ); return sprintf( __( 'This compound and batch already exist as “%1$s”. Edit: %2$s', 'pepselect-coa-archive' ), get_the_title( $id ), $link ? esc_url_raw( $link ) : __( 'unavailable', 'pepselect-coa-archive' ) ); } }
		return $valid;
	}

	/** Enforces final-outcome workflow, documentation, and current-state consistency. @param mixed $valid Existing result. @param mixed $value Status. @param array $field Field. @param string $input Input. @return mixed */
	public function validate_approval( $valid, $value, $field, $input ) {
		unset( $field, $input ); if ( true !== $valid ) { return $valid; }
		if ( in_array( $value, array( 'approved', 'failed' ), true ) && 'complete' !== $this->posted( 'workflow_stage' ) ) { return __( 'Workflow Stage must be Completed before COA Status can be Approved or Failed.', 'pepselect-coa-archive' ); }
		if ( 'failed' === $value ) {
			if ( $this->posted( 'is_current' ) ) { return __( 'Failed reports cannot be marked Current COA.', 'pepselect-coa-archive' ); }
			if ( '' === trim( (string) $this->posted( 'release_decision_note' ) ) ) { return __( 'Release Decision Note is required before saving a Failed completed report.', 'pepselect-coa-archive' ); }
			return $valid;
		}
		if ( 'approved' !== $value ) { return $valid; }
		if ( 'ils-labs' === $this->posted( 'testing_lab' ) ) {
			$fentanyl_status = sanitize_key( (string) $this->posted( 'fentanyl_status' ) );
			$fentanyl_result = trim( (string) $this->posted( 'fentanyl_result' ) );
			$fentanyl_method = trim( (string) $this->posted( 'fentanyl_method' ) );
			$fentanyl_spec = trim( (string) $this->posted( 'fentanyl_specification' ) );
			$fentanyl_success = 'pass' === $fentanyl_status && 'Not detected' === $fentanyl_result && 'Immunoassay' === $fentanyl_method && 'Immunoassay, 50 ng/mL cutoff' === $fentanyl_spec;
			if ( ! $fentanyl_success ) { return __( 'Fentanyl Screen must be Pass with “Not detected” at the 50 ng/mL cutoff before saving an Approved completed ILS report.', 'pepselect-coa-archive' ); }
		}
		if ( ! $this->valid_pdf( absint( $this->posted( 'coa_pdf_id' ) ) ) ) { return __( 'Original COA PDF is required before saving an Approved completed report.', 'pepselect-coa-archive' ); }
		if ( ! $this->valid_images( $this->posted( 'coa_page_images' ) ) || empty( $this->posted( 'coa_page_images' ) ) ) { return __( 'At least one Certificate Page Image is required before saving an Approved completed report.', 'pepselect-coa-archive' ); }
		if ( ! wp_http_validate_url( trim( (string) $this->posted( 'lab_report_url' ) ) ) ) { return __( 'Lab Report URL is required before saving an Approved completed report.', 'pepselect-coa-archive' ); }
		foreach ( self::result_field_labels() as $name => $label ) { if ( 'fail' === $this->posted( $name ) ) { return sprintf( __( 'COA Status cannot be Approved while %s is Failed.', 'pepselect-coa-archive' ), $label ); } }
		return $valid;
	}

	/** Reads an ACF value from the current validated request. @param string $name Field name. @return mixed */
	private function posted( $name ) {
		if ( null !== $this->context ) { return array_key_exists( $name, $this->context['values'] ) ? $this->context['values'][ $name ] : ''; }
		$key = array_search( $name, self::field_map(), true );
		return $key && isset( $_POST['acf'][ $key ] ) ? wp_unslash( $_POST['acf'][ $key ] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/** Returns the post being validated, from context or the ACF form. @return int */
	private function context_post_id() {
		if ( null !== $this->context ) { return $this->context['post_id']; }
		return isset( $_POST['post_ID'] ) ? absint( $_POST['post_ID'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}

	/** Returns whether a field was explicitly supplied by the caller. @param string $name Field. @return bool */
	private function supplied( $name ) {
		if ( null !== $this->context ) { return array_key_exists( $name, $this->context['values'] ); }
		$key = array_search( $name, self::field_map(), true );
		return (bool) ( $key && isset( $_POST['acf'][ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
	}
	/** Returns whether the submitted final outcome is approved. @return bool */
	private function is_approved() { return 'approved' === $this->posted( 'coa_status' ); }
	/** Returns whether the current record is retaining its own read-only legacy outcome. @param string $status Submitted status. @return bool */
	private function preserves_legacy_status( $status ) { $post_id = $this->context_post_id(); return $post_id && in_array( $status, array( 'archived', 'superseded' ), true ) && $status === get_post_meta( $post_id, 'coa_status', true ); }
	/** Returns whether an edit retains its own formerly supported vial-color value. @param string $name Field. @param string $value Submitted value. @return bool */
	private function preserves_legacy_color( $name, $value ) { $post_id = $this->context_post_id(); return $post_id && $value === get_post_meta( $post_id, $name, true ); }
	/** Allows a published legacy record to remain untouched without forcing a destructive migration. @param string $stage Submitted stage. @return bool */
	private function legacy_batch_photo_exempt( $stage ) {
		$post_id = $this->context_post_id();
		if ( ! $post_id || 'publish' !== get_post_status( $post_id ) || $stage !== COA_Workflow::stage( $post_id ) || get_post_meta( $post_id, 'batch_vial_photo', true ) ) { return false; }
		// Outside an ACF form the "nothing material changed" inference below is not
		// available: a partial PATCH cannot distinguish an omitted field from an
		// unchanged one, so omitting fields would silently buy the exemption. Under
		// an injected context the exemption is therefore allowlist-only — an
		// explicit, fixed set of batches that predate the vial-photo convention.
		if ( null !== $this->context ) { return in_array( $post_id, self::legacy_photo_exempt_ids(), true ); }
		$material = array( 'coa_status', 'batch_number', 'batch_identity_photos', 'test_date', 'date_received', 'testing_lab', 'laboratory_logo', 'other_testing_lab', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'claimed_content', 'content_unit', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance', 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'coa_number', 'lab_report_url', 'certificate_version', 'coa_pdf_id', 'coa_page_images', 'public_notes', 'report_notes', 'release_decision_note' );
		foreach ( $material as $name ) {
			if ( ! $this->supplied( $name ) ) { continue; }
			$posted = $this->posted( $name );
			$posted = is_array( $posted ) ? self::sanitize_gallery( $posted ) : self::sanitize( $posted, $name );
			$stored = get_post_meta( $post_id, $name, true ); $stored = is_array( $stored ) ? self::sanitize_gallery( $stored ) : self::sanitize( $stored, $name );
			if ( $posted !== $stored ) { return false; }
		}
		return true;
	}
	/** Returns the submitted/core post status. @return string */
	private function posted_post_status() {
		if ( null !== $this->context ) {
			if ( '' !== $this->context['post_status'] ) { return $this->context['post_status']; }
			return $this->context['post_id'] ? (string) get_post_status( $this->context['post_id'] ) : 'publish';
		}
		if ( isset( $_POST['post_status'] ) ) { return sanitize_key( wp_unslash( $_POST['post_status'] ) ); } // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$post_id = $this->context_post_id();
		return $post_id ? (string) get_post_status( $post_id ) : 'publish';
	}

	/**
	 * Batch numbers that predate the Batch Vial Photo convention.
	 *
	 * SITE-SPECIFIC DATA, kept here deliberately: this is the whole exemption
	 * list, it is closed, and it will never grow — every record created since the
	 * convention has a vial photo. Batch numbers rather than post IDs because IDs
	 * differ between staging and production and these must resolve in both.
	 *
	 * @var string[]
	 */
	const LEGACY_PHOTO_EXEMPT_BATCHES = array( 'ND_R30_060326', 'TB10-6926' );

	/** @var int[]|null Resolved exemption IDs for this request. */
	private static $legacy_exempt_cache = null;

	/**
	 * Returns COA Test IDs exempt from the Batch Vial Photo requirement.
	 *
	 * Deliberately an explicit list rather than an inference: a partial request
	 * cannot distinguish an omitted field from an unchanged one, so inferring the
	 * exemption would let a caller buy it by omission.
	 *
	 * @return int[]
	 */
	public static function legacy_photo_exempt_ids() {
		if ( null !== self::$legacy_exempt_cache ) { return self::$legacy_exempt_cache; }
		/**
		 * Batch numbers exempt from the Batch Vial Photo requirement.
		 *
		 * @param string[] $batches Batch numbers.
		 */
		$batches = array_filter( array_map( 'strval', (array) apply_filters( 'pepselect_coa_legacy_photo_exempt_batches', self::LEGACY_PHOTO_EXEMPT_BATCHES ) ) );
		$ids     = array();
		foreach ( $batches as $batch ) {
			$matches = get_posts(
				array(
					'post_type'      => Post_Types::COA_TEST,
					'post_status'    => array( 'publish', 'draft', 'pending', 'private', 'future' ),
					'posts_per_page' => 5,
					'fields'         => 'ids',
					'no_found_rows'  => true,
					'meta_key'       => 'batch_number',
					'meta_value'     => $batch,
				)
			);
			foreach ( $matches as $match ) { $ids[] = absint( $match ); }
		}
		/**
		 * Additional exempt COA Test IDs, for cases a batch number cannot express.
		 *
		 * @param int[] $ids Post IDs.
		 */
		$explicit = array_map( 'absint', (array) apply_filters( 'pepselect_coa_legacy_photo_exempt_ids', array() ) );
		self::$legacy_exempt_cache = array_values( array_unique( array_filter( array_merge( $ids, $explicit ) ) ) );
		return self::$legacy_exempt_cache;
	}

	/** Clears the resolved exemption cache. @return void */
	public static function flush_legacy_photo_exempt_cache() { self::$legacy_exempt_cache = null; }
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
	/** Restricts public laboratory logos to images accepted by WordPress's current upload policy. @param int $id Attachment ID. @return bool */
	private function valid_laboratory_logo( $id ) {
		if ( $id < 1 || 'attachment' !== get_post_type( $id ) || 'inherit' !== get_post_status( $id ) || ! current_user_can( 'edit_post', $id ) ) { return false; }
		$mime = get_post_mime_type( $id );
		if ( in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) ) { return wp_attachment_is_image( $id ); }
		return 'image/svg+xml' === $mime && in_array( 'image/svg+xml', array_values( get_allowed_mime_types() ), true );
	}
	/** Validates image galleries while preserving order. @param mixed $ids IDs. @param bool $check_permission Require attachment permission. @return bool */
	private function valid_images( $ids, $check_permission = false ) { if ( empty( $ids ) ) { return true; } if ( ! is_array( $ids ) || count( $ids ) > 20 ) { return false; } foreach ( $ids as $id ) { if ( ! $this->valid_image( absint( $id ), $check_permission ) || ! in_array( get_post_mime_type( absint( $id ) ), array( 'image/jpeg', 'image/png', 'image/webp' ), true ) ) { return false; } } return true; }
	/** Returns string length. @param mixed $value Value. @return int */
	private function length( $value ) { return function_exists( 'mb_strlen' ) ? mb_strlen( (string) $value ) : strlen( (string) $value ); }
	/** Returns whether a guidance rule is mandatory under the active validation conditions. @return bool */
	private static function requirement_is_required( $rule, $stage, $status, $lab ) {
		if ( 'always' === $rule['required'] ) { return true; }
		if ( 'stage' === $rule['required'] ) { return in_array( $stage, $rule['stages'], true ); }
		if ( 'lab' === $rule['required'] ) { return 'in-testing' === $stage || 'approved' === $status; }
		if ( 'other-lab' === $rule['required'] ) { return 'other' === $lab && in_array( $stage, array( 'submitted-to-lab', 'in-testing', 'complete' ), true ); }
		if ( 'approved' === $rule['required'] ) { return 'approved' === $status; }
		if ( 'approved-ils' === $rule['required'] ) { return 'approved' === $status && 'ils-labs' === $lab; }
		return 'failed' === $rule['required'] && 'failed' === $status;
	}

	/** Returns whether saved evidence currently satisfies one checklist rule. @return bool */
	private static function requirement_is_complete( $post_id, $rule ) {
		$values = array();
		foreach ( $rule['fields'] as $field ) { $values[ $field ] = get_post_meta( $post_id, $field, true ); }
		$validator = isset( $rule['validator'] ) ? $rule['validator'] : '';
		if ( 'positive' === $validator ) { return (int) reset( $values ) > 0; }
		if ( 'pdf' === $validator ) { $id = absint( reset( $values ) ); return $id > 0 && 'attachment' === get_post_type( $id ) && 'application/pdf' === get_post_mime_type( $id ); }
		if ( 'url' === $validator ) { return (bool) wp_http_validate_url( trim( (string) reset( $values ) ) ); }
		if ( 'fentanyl-method' === $validator ) { return 'Immunoassay' === reset( $values ); }
		if ( 'fentanyl-specification' === $validator ) { return 'Immunoassay, 50 ng/mL cutoff' === reset( $values ); }
		if ( 'fentanyl' === $validator ) { return 'pass' === $values['fentanyl_status'] && 'Not detected' === $values['fentanyl_result'] && 'Immunoassay' === $values['fentanyl_method'] && 'Immunoassay, 50 ng/mL cutoff' === $values['fentanyl_specification']; }
		if ( 'result-evidence' === $validator ) {
			foreach ( $values as $field => $value ) {
				if ( false !== strpos( $field, '_status' ) && in_array( $value, array( '', 'pending', 'not-tested', 'not-applicable' ), true ) ) { continue; }
				if ( is_array( $value ) ? ! empty( $value ) : '' !== trim( (string) $value ) ) { return true; }
			}
			return false;
		}
		foreach ( $values as $value ) { if ( is_array( $value ) ? ! empty( $value ) : '' !== trim( (string) $value ) ) { return true; } }
		return false;
	}

	/** Returns the operational order used only to describe "not required yet". @return int */
	private static function stage_rank( $stage ) { $order = array( 'vendor-vetting' => 0, 'waiting-on-vendor' => 1, 'submitted-to-lab' => 2, 'in-testing' => 3, 'complete' => 4 ); return isset( $order[ $stage ] ) ? $order[ $stage ] : 0; }
	/** Returns fields that count as entered laboratory-result evidence. @return string[] */
	private static function result_evidence_fields() { return array( 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'average_net_content', 'minimum_net_content', 'maximum_net_content' ); }
	/** Returns number fields. @return string[] */
	private static function number_fields() { return array( 'claimed_content', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'purity_percentage' ); }
	/** Returns nonnegative fields. @return string[] */
	private static function nonnegative_fields() { return array( 'claimed_content', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'purity_percentage' ); }
	/** Returns integer fields. @return string[] */
	private static function integer_fields() { return array( 'compound_id', 'vials_tested', 'coa_pdf_id', 'batch_vial_photo', 'laboratory_logo' ); }
	/** Returns result status fields. @return string[] */
	private static function result_fields() { return array( 'purity_status', 'identity_status', 'endotoxin_status', 'heavy_metals_status', 'sterility_status', 'fentanyl_status' ); }
	/** Returns exact administrative labels for result fields. @return array */
	private static function result_field_labels() { return array( 'purity_status' => __( 'Purity Status', 'pepselect-coa-archive' ), 'identity_status' => __( 'Identity Status', 'pepselect-coa-archive' ), 'endotoxin_status' => __( 'Endotoxin Status', 'pepselect-coa-archive' ), 'heavy_metals_status' => __( 'Heavy Metals Status', 'pepselect-coa-archive' ), 'sterility_status' => __( 'Sterility Status', 'pepselect-coa-archive' ), 'fentanyl_status' => __( 'Fentanyl Screen Status', 'pepselect-coa-archive' ) ); }
	/** Returns whether a reported Fentanyl result explicitly communicates a successful screen. @param mixed $value Result text. @return bool */
	public static function fentanyl_result_is_successful( $value ) {
		$normalized = strtolower( trim( (string) $value ) );
		$normalized = preg_replace( '/[\s\._-]+/', ' ', $normalized );
		if ( in_array( $normalized, array( 'nd', 'n/d', 'negative' ), true ) ) { return true; }
		return (bool) preg_match( '/(?:not detected|none detected|no fentanyl detected|below (?:the )?(?:limit of )?(?:detection|quantitation|reporting))/', $normalized );
	}
	/** Maps stable ACF keys to stored names. @return array */
	private static function field_map() {
		$names = array( 'compound_id', 'batch_number', 'batch_vial_photo', 'batch_identity_photos', 'test_date', 'workflow_stage', 'expected_coa_date', 'vendor_status_note', 'public_status_note', 'release_decision_note', 'testing_lab', 'laboratory_logo', 'other_testing_lab', 'lab_accession_number', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'claimed_content', 'content_unit', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance', 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'coa_number', 'lab_report_url', 'verification_code', 'lab_verification_url', 'certificate_version', 'coa_pdf_id', 'public_notes', 'report_notes', 'internal_notes' );
		$map = array( 'field_ps_coa_test_status' => 'coa_status', 'field_ps_coa_test_page_images' => 'coa_page_images' );
		foreach ( $names as $name ) { $map[ 'field_ps_coa_test_' . $name ] = $name; }
		return $map;
	}
}

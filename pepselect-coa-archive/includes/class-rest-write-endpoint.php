<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Validated write surface for ops integrations.
 *
 * Core's wp/v2 routes accept meta writes that bypass every acf/validate_value
 * rule and every acf/save_post side effect, so a caller can publish a record
 * that the admin form would have rejected. This endpoint runs the SAME
 * validators against the SAME merged value set the form would have submitted,
 * then performs the SAME post-save work, and reports failures as a 400 carrying
 * the plugin's own message and the field that failed.
 *
 * Partial updates merge stored values under the submitted ones before
 * validating: cross-field rules read siblings, so validating a bare PATCH
 * payload would fail a record against its own stored, valid data.
 */
final class REST_Write_Endpoint {
	const NAMESPACE_V1  = 'pepselect-coa/v1';
	const ROUTE_TEST    = '/coa-test';
	const ROUTE_COMPOUND = '/compound';

	/** @var COA_Test_Validation */
	private $test_validation;

	/** @var Compound_Validation */
	private $compound_validation;

	/** @var COA_Test_Service */
	private $test_service;

	/**
	 * @param COA_Test_Validation $test_validation     COA Test rules.
	 * @param Compound_Validation $compound_validation Compound rules.
	 * @param COA_Test_Service    $test_service        Post-save invariants.
	 */
	public function __construct( COA_Test_Validation $test_validation, Compound_Validation $compound_validation, COA_Test_Service $test_service ) {
		$this->test_validation     = $test_validation;
		$this->compound_validation = $compound_validation;
		$this->test_service        = $test_service;
	}

	/** Registers the write routes. @return void */
	public function register() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/** Declares create and update routes for both post types. @return void */
	public function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			self::ROUTE_TEST,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_test' ),
				'permission_callback' => array( $this, 'can_create_test' ),
			)
		);
		register_rest_route(
			self::NAMESPACE_V1,
			self::ROUTE_TEST . '/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH, PUT',
				'callback'            => array( $this, 'update_test' ),
				'permission_callback' => array( $this, 'can_edit_post' ),
				'args'                => array( 'id' => array( 'type' => 'integer', 'required' => true ) ),
			)
		);
		register_rest_route(
			self::NAMESPACE_V1,
			self::ROUTE_COMPOUND,
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'create_compound' ),
				'permission_callback' => array( $this, 'can_create_test' ),
			)
		);
		register_rest_route(
			self::NAMESPACE_V1,
			self::ROUTE_COMPOUND . '/(?P<id>\d+)',
			array(
				'methods'             => 'PATCH, PUT',
				'callback'            => array( $this, 'update_compound' ),
				'permission_callback' => array( $this, 'can_edit_post' ),
				'args'                => array( 'id' => array( 'type' => 'integer', 'required' => true ) ),
			)
		);
	}

	/** @return bool|\WP_Error */
	public function can_create_test() {
		return current_user_can( 'create_ps_coas' ) ? true : new \WP_Error( 'pepselect_coa_forbidden', __( 'You are not allowed to create COA records.', 'pepselect-coa-archive' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/** @param \WP_REST_Request $request Request. @return bool|\WP_Error */
	public function can_edit_post( $request ) {
		$post_id = absint( $request['id'] );
		return current_user_can( 'edit_post', $post_id ) ? true : new \WP_Error( 'pepselect_coa_forbidden', __( 'You are not allowed to edit this record.', 'pepselect-coa-archive' ), array( 'status' => rest_authorization_required_code() ) );
	}

	/** @param \WP_REST_Request $request Request. @return \WP_REST_Response|\WP_Error */
	public function create_test( $request ) { return $this->write( $request, Post_Types::COA_TEST, 0 ); }

	/** @param \WP_REST_Request $request Request. @return \WP_REST_Response|\WP_Error */
	public function update_test( $request ) { return $this->write( $request, Post_Types::COA_TEST, absint( $request['id'] ) ); }

	/** @param \WP_REST_Request $request Request. @return \WP_REST_Response|\WP_Error */
	public function create_compound( $request ) { return $this->write( $request, Post_Types::COMPOUND, 0 ); }

	/** @param \WP_REST_Request $request Request. @return \WP_REST_Response|\WP_Error */
	public function update_compound( $request ) { return $this->write( $request, Post_Types::COMPOUND, absint( $request['id'] ) ); }

	/**
	 * Validates, writes, and applies post-save invariants.
	 *
	 * @param \WP_REST_Request $request   Request.
	 * @param string           $post_type Target post type.
	 * @param int              $post_id   Existing post, 0 to create.
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function write( $request, $post_type, $post_id ) {
		if ( $post_id && $post_type !== get_post_type( $post_id ) ) {
			return new \WP_Error( 'pepselect_coa_not_found', __( 'No such record.', 'pepselect-coa-archive' ), array( 'status' => 404 ) );
		}
		$body = $request->get_json_params();
		if ( ! is_array( $body ) ) { $body = $request->get_body_params(); }
		if ( ! is_array( $body ) ) { $body = array(); }

		$names     = self::field_names( $post_type );
		$submitted = array_intersect_key( is_array( $body ) ? $body : array(), array_flip( $names ) );
		$unknown   = array_diff( array_keys( array_diff_key( $body, array_flip( array( 'id', 'title', 'status' ) ) ) ), $names );
		if ( $unknown ) {
			return $this->invalid( array_map(
				static function ( $field ) {
					return array( 'field' => (string) $field, 'message' => __( 'Unknown field.', 'pepselect-coa-archive' ) );
				},
				array_values( $unknown )
			) );
		}

		$post_status = $request->get_param( 'status' ) ? sanitize_key( (string) $request->get_param( 'status' ) ) : ( $post_id ? (string) get_post_status( $post_id ) : 'publish' );
		$values      = $this->merge_values( $post_type, $post_id, $submitted );

		$errors = Post_Types::COA_TEST === $post_type
			? $this->validate_test( $values, $post_id, $post_status )
			: $this->validate_compound( $values, $post_id );
		if ( $errors ) { return $this->invalid( $errors ); }

		// On create the merged set includes the same defaults the form would have
		// applied, so persist all of it; on update touch only what was sent.
		$persist = $post_id ? $submitted : $values;
		$written = $this->persist( $post_type, $post_id, $post_status, $persist, $request );
		if ( is_wp_error( $written ) ) { return $written; }

		$warnings = $this->apply_side_effects( $post_type, $written );
		return new \WP_REST_Response( array( 'id' => $written, 'warnings' => $warnings ), $post_id ? 200 : 201 );
	}

	/**
	 * Builds the value set the validators see: stored values overlaid with the
	 * submitted ones, plus form defaults for fields absent on a create.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Existing post, 0 to create.
	 * @param array  $submitted Recognised submitted fields.
	 * @return array
	 */
	private function merge_values( $post_type, $post_id, array $submitted ) {
		$defaults = $post_id ? array() : self::create_defaults( $post_type );
		$values   = array();
		foreach ( self::field_names( $post_type ) as $name ) {
			if ( array_key_exists( $name, $submitted ) ) { $values[ $name ] = $submitted[ $name ]; continue; }
			if ( $post_id ) { $values[ $name ] = get_post_meta( $post_id, $name, true ); continue; }
			$values[ $name ] = array_key_exists( $name, $defaults ) ? $defaults[ $name ] : '';
		}
		return $values;
	}

	/**
	 * Runs every COA Test rule against the merged set.
	 *
	 * @param array  $values      Merged values.
	 * @param int    $post_id     Target post.
	 * @param string $post_status Intended status.
	 * @return array
	 */
	private function validate_test( array $values, $post_id, $post_status ) {
		$errors = array();
		$this->test_validation->set_context( $values, $post_id, $post_status );
		try {
			foreach ( $values as $name => $value ) {
				$result = $this->test_validation->validate( true, $value, array( 'name' => $name ), '' );
				if ( true !== $result ) { $errors[] = array( 'field' => $name, 'message' => (string) $result ); }
			}
			$duplicate = $this->test_validation->validate_duplicate( true, isset( $values['batch_number'] ) ? $values['batch_number'] : '', array(), '' );
			if ( true !== $duplicate ) { $errors[] = array( 'field' => 'batch_number', 'message' => (string) $duplicate ); }
			$approval = $this->test_validation->validate_approval( true, isset( $values['coa_status'] ) ? $values['coa_status'] : '', array(), '' );
			if ( true !== $approval ) { $errors[] = array( 'field' => 'coa_status', 'message' => (string) $approval ); }
		} finally {
			$this->test_validation->clear_context();
		}
		return $errors;
	}

	/**
	 * Runs every compound rule against the merged set.
	 *
	 * @param array $values  Merged values.
	 * @param int   $post_id Target post.
	 * @return array
	 */
	private function validate_compound( array $values, $post_id ) {
		$errors = array();
		$this->compound_validation->set_context( $values, $post_id );
		try {
			foreach ( $values as $name => $value ) {
				$result = $this->compound_validation->validate( true, $value, array( 'name' => $name ), '' );
				if ( true !== $result ) { $errors[] = array( 'field' => $name, 'message' => (string) $result ); }
			}
			$duplicate = $this->compound_validation->validate_duplicate( true, isset( $values['strength_unit'] ) ? $values['strength_unit'] : '', array(), '' );
			if ( true !== $duplicate ) { $errors[] = array( 'field' => 'strength_unit', 'message' => (string) $duplicate ); }
		} finally {
			$this->compound_validation->clear_context();
		}
		return $errors;
	}

	/**
	 * Creates or updates the post and its meta.
	 *
	 * @param string           $post_type   Post type.
	 * @param int              $post_id     Existing post, 0 to create.
	 * @param string           $post_status Intended status.
	 * @param array            $persist     Fields to write.
	 * @param \WP_REST_Request $request     Request.
	 * @return int|\WP_Error
	 */
	private function persist( $post_type, $post_id, $post_status, array $persist, $request ) {
		$title = $request->get_param( 'title' );
		if ( ! $post_id ) {
			$post_id = wp_insert_post(
				array(
					'post_type'   => $post_type,
					'post_status' => $post_status,
					'post_title'  => null === $title ? '' : sanitize_text_field( (string) $title ),
				),
				true
			);
			if ( is_wp_error( $post_id ) ) { return $post_id; }
		} else {
			$update = array( 'ID' => $post_id );
			if ( null !== $title ) { $update['post_title'] = sanitize_text_field( (string) $title ); }
			if ( $post_status !== get_post_status( $post_id ) ) { $update['post_status'] = $post_status; }
			if ( count( $update ) > 1 ) {
				$result = wp_update_post( $update, true );
				if ( is_wp_error( $result ) ) { return $result; }
			}
		}
		$galleries = array( 'coa_page_images', 'batch_identity_photos' );
		foreach ( $persist as $name => $value ) {
			$clean = in_array( $name, $galleries, true )
				? COA_Test_Validation::sanitize_gallery( $value )
				: ( Post_Types::COA_TEST === $post_type ? COA_Test_Validation::sanitize( $value, $name ) : Compound_Validation::sanitize( $name, $value ) );
			update_post_meta( $post_id, $name, $clean );
		}
		return $post_id;
	}

	/**
	 * Applies the acf/save_post work this write would otherwise never receive,
	 * and drains the advisory notices into a returnable array.
	 *
	 * @param string $post_type Post type.
	 * @param int    $post_id   Written post.
	 * @return array
	 */
	private function apply_side_effects( $post_type, $post_id ) {
		if ( Post_Types::COA_TEST === $post_type ) {
			// synchronize_title, apply_ils_verification_default,
			// clear_other_current_tests, flag_future_date, flag_workflow_guidance.
			$this->test_service->after_save( $post_id );
			return $this->drain_warnings( $post_id );
		}
		$this->compound_validation->populate_empty_title( $post_id );
		$this->test_service->after_compound_save( $post_id );
		return $this->drain_warnings( $post_id );
	}

	/**
	 * Converts the advisory admin-notice transients into response warnings.
	 *
	 * Draining them also stops a REST write from leaving a stale notice queued
	 * for the same user's next wp-admin page load.
	 *
	 * @param int $post_id Written post.
	 * @return array
	 */
	private function drain_warnings( $post_id ) {
		$warnings = array();
		$user_id  = get_current_user_id();
		$date_key = 'ps_coa_future_date_' . $user_id;
		if ( absint( get_transient( $date_key ) ) ) {
			delete_transient( $date_key );
			/* translators: %s: record title. */
			$warnings[] = array( 'code' => 'future_test_date', 'message' => sprintf( __( 'The test date for “%s” is in the future. Confirm that this is intentional.', 'pepselect-coa-archive' ), get_the_title( $post_id ) ) );
		}
		$notice_key = 'ps_coa_workflow_notices_' . $user_id;
		$messages   = get_transient( $notice_key );
		if ( is_array( $messages ) && $messages ) {
			delete_transient( $notice_key );
			foreach ( $messages as $message ) { $warnings[] = array( 'code' => 'workflow_guidance', 'message' => (string) $message ); }
		}
		return $warnings;
	}

	/**
	 * Builds the 400 carrying the plugin's own messages.
	 *
	 * @param array $errors Field/message pairs.
	 * @return \WP_Error
	 */
	private function invalid( array $errors ) {
		return new \WP_Error(
			'pepselect_coa_invalid_record',
			__( 'The record failed COA Archive validation.', 'pepselect-coa-archive' ),
			array( 'status' => 400, 'errors' => array_values( $errors ) )
		);
	}

	/**
	 * Returns every writable field name for a post type.
	 *
	 * @param string $post_type Post type.
	 * @return string[]
	 */
	public static function field_names( $post_type ) {
		if ( Post_Types::COMPOUND === $post_type ) {
			return array( 'display_name', 'compound_name', 'short_name', 'strength_value', 'strength_unit', 'compound_category', 'archive_description', 'compound_image_id', 'display_order', 'is_active', 'is_featured', 'internal_notes' );
		}
		return array(
			'compound_id', 'batch_number', 'workflow_stage', 'coa_status', 'test_date',
			'expected_coa_date', 'date_received', 'testing_lab', 'other_testing_lab', 'lab_accession_number',
			'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color',
			'claimed_content', 'content_unit', 'vials_tested',
			'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance',
			'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method',
			'endotoxin_status', 'endotoxin_result', 'endotoxin_unit',
			'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result',
			'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes',
			'coa_number', 'lab_report_url', 'verification_code', 'lab_verification_url', 'certificate_version',
			'vendor_status_note', 'public_status_note', 'release_decision_note', 'public_notes', 'report_notes', 'internal_notes',
			'coa_pdf_id', 'batch_vial_photo', 'laboratory_logo', 'batch_identity_photos', 'coa_page_images',
		);
	}

	/**
	 * Returns the ACF default_value set the admin form would apply on a create.
	 *
	 * Without these a bare create would fail rules the form never triggers —
	 * workflow_stage, coa_status and fentanyl_status are all non-empty by
	 * definition in the form.
	 *
	 * @param string $post_type Post type.
	 * @return array
	 */
	public static function create_defaults( $post_type ) {
		if ( Post_Types::COMPOUND === $post_type ) {
			return array( 'strength_unit' => 'mg', 'display_order' => 0, 'is_active' => 1, 'is_featured' => 0 );
		}
		return array(
			'workflow_stage'         => 'vendor-vetting',
			'coa_status'             => 'pending',
			'is_current'             => 0,
			'content_unit'           => 'mg',
			'sample_appearance'      => 'White Lyophilized Powder',
			'purity_status'          => 'pending',
			'identity_status'        => 'pending',
			'endotoxin_status'       => 'reported',
			'endotoxin_unit'         => 'EU/mL',
			'heavy_metals_status'    => 'not-tested',
			'heavy_metals_summary'   => 'Arsenic, cadmium, chromium, mercury, and lead were not detected.',
			'sterility_status'       => 'not-tested',
			'sterility_result'       => 'No Growth',
			'fentanyl_status'        => 'not-tested',
			'fentanyl_method'        => 'Immunoassay',
			'fentanyl_specification' => 'Immunoassay, 50 ng/mL cutoff',
		);
	}
}

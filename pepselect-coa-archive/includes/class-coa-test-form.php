<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Controls the progressive COA Test editor without modifying stored values. */
final class COA_Test_Form {
	/** Registers strongly scoped editor hooks. @return void */
	public function register_hooks() {
		add_action( 'add_meta_boxes_' . Post_Types::COA_TEST, array( $this, 'register_featured_image_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_filter( 'acf/load_field/key=field_ps_coa_test_status', array( $this, 'preserve_legacy_status' ) );
		add_filter( 'acf/load_value/key=field_ps_coa_test_other_vial_crimp_color', array( $this, 'load_legacy_color' ), 10, 3 );
		add_filter( 'acf/load_value/key=field_ps_coa_test_other_vial_cap_color', array( $this, 'load_legacy_color' ), 10, 3 );
		add_filter( 'acf/load_value/key=field_ps_coa_test_workflow_stage', array( $this, 'load_normalized_stage' ), 10, 3 );
		add_filter( 'acf/load_field/key=field_ps_coa_test_vial_crimp_color', array( $this, 'preserve_legacy_color_choice' ) );
		add_filter( 'acf/load_field/key=field_ps_coa_test_vial_cap_color', array( $this, 'preserve_legacy_color_choice' ) );
	}

	/** Ensures the native Featured Image UI does not depend on active-theme support. @param \WP_Post $post Current test. @return void */
	public function register_featured_image_metabox( $post ) {
		if ( ! $post || Post_Types::COA_TEST !== $post->post_type || ! post_type_supports( Post_Types::COA_TEST, 'thumbnail' ) || ! function_exists( 'post_thumbnail_meta_box' ) ) { return; }
		add_meta_box( 'postimagediv', __( 'Featured Image', 'pepselect-coa-archive' ), 'post_thumbnail_meta_box', Post_Types::COA_TEST, 'side', 'low' );
	}

	/** Loads the stage controller only on COA Test Add/Edit screens. @param string $hook Admin hook. @return void */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Types::COA_TEST !== $screen->post_type || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		wp_enqueue_style( 'pepselect-coa-test-form', plugins_url( 'assets/css/pepselect-coa-test-form.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		wp_enqueue_script( 'pepselect-coa-test-form', plugins_url( 'assets/js/pepselect-coa-test-form.js', PEPSELECT_COA_ARCHIVE_FILE ), array( 'jquery', 'acf-input' ), PEPSELECT_COA_ARCHIVE_VERSION, true );
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = $post_id ? sanitize_key( get_post_meta( $post_id, 'coa_status', true ) ) : '';
		wp_localize_script( 'pepselect-coa-test-form', 'PepSelectCOATestForm', array(
			'stages' => COA_Workflow::stages(),
			'available' => self::availability(),
			'resultFields' => self::result_fields(),
			'legacyStatus' => in_array( $status, array( 'archived', 'superseded' ), true ) ? $status : '',
			'batchPhotoRequired' => __( 'A photo of the exact tested vial is required before Verification in Progress or Complete can be saved.', 'pepselect-coa-archive' ),
			'guidance' => array(
				'vendor-vetting' => __( 'No testing fields are available while suppliers are being evaluated.', 'pepselect-coa-archive' ),
				'waiting-on-vendor' => __( 'Testing fields will become available when a batch is received and submitted.', 'pepselect-coa-archive' ),
				'submitted-to-lab' => __( 'The sample has been shipped. Laboratory and batch details remain private until verification begins.', 'pepselect-coa-archive' ),
				'in-testing' => __( 'Testing is underway. Add the expected report date and public pending-status link when available.', 'pepselect-coa-archive' ),
				'complete' => __( 'Enter the final results and certificate documents before approving the report.', 'pepselect-coa-archive' ),
			),
		) );
	}

	/** Adds and locks a legacy final status only for the record that already owns it. @param array $field ACF field. @return array */
	public function preserve_legacy_status( $field ) {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status = $post_id ? sanitize_key( get_post_meta( $post_id, 'coa_status', true ) ) : '';
		if ( in_array( $status, array( 'archived', 'superseded' ), true ) ) { $field['choices'][ $status ] = ucfirst( $status ) . ' (legacy, read only)'; $field['disabled'] = 1; $field['required'] = 0; }
		return $field;
	}

	/** Reads the former color meta keys without rewriting or deleting them. @param mixed $value Current value. @param int|string $post_id Post ID. @param array $field Field. @return mixed */
	public function load_legacy_color( $value, $post_id, $field ) {
		if ( '' !== trim( (string) $value ) ) { return $value; }
		$legacy = 'other_vial_crimp_color' === $field['name'] ? 'vial_crimp_color_other' : 'vial_cap_color_other';
		return get_post_meta( absint( $post_id ), $legacy, true );
	}

	/** Displays retired stages as their supported runtime equivalent. @param mixed $value Stored value. @param int|string $post_id Post ID. @param array $field Field. @return string */
	public function load_normalized_stage( $value, $post_id, $field ) { unset( $post_id, $field ); return COA_Workflow::normalize_stage( $value ); }

	/** Keeps a formerly supported saved color selectable without adding it to new-record choices. @param array $field ACF field. @return array */
	public function preserve_legacy_color_choice( $field ) {
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$value = $post_id ? sanitize_key( get_post_meta( $post_id, $field['name'], true ) ) : '';
		if ( $value && ! isset( $field['choices'][ $value ] ) ) { $field['choices'][ $value ] = ucwords( str_replace( '-', ' ', $value ) ) . ' (legacy)'; }
		return $field;
	}

	/** Returns whether one field is editable at an effective stage. @param string $name Field name. @param string $stage Stage. @param bool $partial Partial-results flag. @return bool */
	public static function field_available( $name, $stage, $partial = false ) {
		$stage = COA_Workflow::normalize_stage( $stage );
		if ( 'complete' === $stage || 0 === strpos( $name, 'tab_' ) ) { return true; }
		$available = self::availability();
		if ( in_array( $name, $available['vendor-vetting'], true ) ) { return true; }
		if ( in_array( $name, isset( $available[ $stage ] ) ? $available[ $stage ] : array(), true ) ) { return true; }
		return 'in-testing' === $stage && $partial && in_array( $name, self::result_fields(), true );
	}

	/** Field availability shared with the scoped controller script. @return array */
	public static function availability() {
		$identity = array( 'compound_id', 'workflow_stage', 'coa_status', 'public_status_note', 'claimed_content', 'content_unit', 'internal_notes', 'batch_vial_photo', 'batch_identity_photos' );
		$vendor = array_merge( $identity, array( 'vendor_status_note' ) );
		$colors = array( 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color' );
		$waiting = array_merge( $vendor, array( 'expected_coa_date', 'vials_submitted', 'vials_tested' ), $colors );
		$submitted = array_merge( $identity, array( 'expected_coa_date', 'batch_number', 'testing_lab', 'other_testing_lab', 'lab_accession_number', 'pending_lab_url', 'vials_submitted', 'vials_tested', 'sample_appearance' ), $colors );
		$testing = array_merge( $submitted, array( 'internal_batch_id', 'test_date', 'date_received', 'partial_results_available' ) );
		return array( 'vendor-vetting' => $vendor, 'waiting-on-vendor' => $waiting, 'submitted-to-lab' => $submitted, 'in-testing' => $testing, 'complete' => array( '*' ) );
	}

	/** Returns all scientific result field names. @return array */
	public static function result_fields() { return array( 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent' ); }
}

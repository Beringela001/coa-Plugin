<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Registers the deterministic COA Test ACF group and safe REST metadata. */
final class COA_Test_Fields {
	/** @var Dependencies */
	private $dependencies;
	/** @var bool */
	private $registered = false;
	/** @var bool */
	private $rest_registered = false;

	/** @param Dependencies $dependencies Dependency detector. */
	public function __construct( Dependencies $dependencies ) { $this->dependencies = $dependencies; }

	/** Registers the local ACF field group. @return void */
	public function register() {
		if ( $this->registered || ! $this->dependencies->has_acf() ) { return; }
		$this->registered = true;
		acf_add_local_field_group( array( 'key' => 'group_ps_coa_test_details', 'title' => __( 'COA Test Details', 'pepselect-coa-archive' ), 'fields' => $this->fields(), 'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => Post_Types::COA_TEST ) ) ), 'position' => 'normal', 'style' => 'default', 'active' => true, 'show_in_rest' => 0 ) );
	}

	/** Registers safe COA metadata on the existing core REST endpoint. @return void */
	public function register_rest_meta() {
		if ( $this->rest_registered ) { return; }
		$this->rest_registered = true;
		$integer = array( 'compound_id', 'vials_submitted', 'vials_tested', 'coa_pdf_id', 'batch_vial_photo' );
		$number  = array( 'claimed_content', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'purity_percentage' );
		$boolean = array( 'is_current', 'partial_results_available' );
		// Ops is now the single entry point (Ops Spec §16 item — mirror the COA
		// form): the ops app owns the ENTIRE ps_coa_test field vocabulary over REST,
		// matching class-coa-test-importer.php::field_map() so one CSV template and
		// one form serve both. The array/gallery metas are registered separately
		// below. The two ID media metas (coa_pdf_id/batch_vial_photo) are ops-managed
		// uploads, not CSV columns, but ride the same list.
		$safe = array(
			'compound_id', 'batch_number', 'internal_batch_id', 'workflow_stage', 'coa_status', 'test_date',
			'expected_coa_date', 'date_received', 'testing_lab', 'other_testing_lab', 'lab_accession_number',
			'is_current', 'partial_results_available',
			'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color',
			'claimed_content', 'content_unit', 'vials_submitted', 'vials_tested',
			'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance',
			'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method',
			'endotoxin_status', 'endotoxin_result', 'endotoxin_unit',
			'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result',
			'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes',
			'coa_number', 'lab_report_url', 'pending_lab_url', 'verification_code', 'lab_verification_url', 'certificate_version',
			'vendor_status_note', 'public_status_note', 'release_decision_note', 'public_notes', 'report_notes', 'internal_notes',
			'coa_pdf_id', 'batch_vial_photo',
		);
		foreach ( $safe as $key ) {
			$type = in_array( $key, $integer, true ) ? 'integer' : ( in_array( $key, $number, true ) ? 'number' : ( in_array( $key, $boolean, true ) ? 'boolean' : 'string' ) );
			register_post_meta( Post_Types::COA_TEST, $key, array( 'single' => true, 'type' => $type, 'show_in_rest' => true, 'sanitize_callback' => array( 'PepSelect\\COAArchive\\COA_Test_Validation', 'sanitize' ), 'auth_callback' => array( $this, 'authorize_meta_edit' ) ) );
		}

		// Gallery metas are ARRAYS of attachment IDs, so they need an explicit array
		// schema rather than the scalar loop above: batch_identity_photos (vial
		// identity shots) and coa_page_images (the COA report page scans).
		foreach ( array( 'batch_identity_photos', 'coa_page_images' ) as $gallery_key ) {
			register_post_meta(
				Post_Types::COA_TEST,
				$gallery_key,
				array(
					'single'            => true,
					'type'              => 'array',
					'show_in_rest'      => array( 'schema' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ) ) ),
					'auth_callback'     => array( $this, 'authorize_meta_edit' ),
					'sanitize_callback' => array( 'PepSelect\\COAArchive\\COA_Test_Validation', 'sanitize_gallery' ),
				)
			);
		}
	}

	/** Restricts REST writes to users who can edit the record. @param bool $allowed Existing result. @param string $key Meta key. @param int $post_id Post ID. @return bool */
	public function authorize_meta_edit( $allowed, $key, $post_id ) { unset( $allowed, $key ); return current_user_can( 'edit_post', $post_id ); }

	/** Returns all stable ACF field definitions. @return array */
	private function fields() {
		$result = self::result_choices();
		$fentanyl = self::fentanyl_choices();
		$units  = Compound_Validation::units();
		return array(
			$this->tab( 'identification', __( 'Test Identification', 'pepselect-coa-archive' ) ),
			$this->f( 'compound_id', 'Related Compound', 'post_object', array( 'required' => 1, 'post_type' => array( Post_Types::COMPOUND ), 'post_status' => array( 'publish', 'private' ), 'return_format' => 'id', 'multiple' => 0, 'allow_null' => 0 ) ),
			$this->f( 'workflow_stage', 'Workflow Stage', 'select', array( 'required' => 1, 'choices' => COA_Workflow::stages(), 'default_value' => 'vendor-vetting', 'return_format' => 'value', 'instructions' => __( 'Select the current operational stage for this batch.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'status', 'COA Status', 'select', array( 'name' => 'coa_status', 'required' => 1, 'choices' => self::statuses(), 'default_value' => 'pending', 'return_format' => 'value' ) ),
			$this->f( 'internal_batch_id', 'Internal Batch ID', 'text', array( 'maxlength' => 120 ) ),
			$this->f( 'test_date', 'Test Date', 'date_picker', array( 'display_format' => 'F j, Y', 'return_format' => 'Y-m-d', 'first_day' => 1, 'instructions' => __( 'Required for approved reports; record it for failed reports when available.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'date_received', 'Date Received', 'date_picker', array( 'display_format' => 'F j, Y', 'return_format' => 'Y-m-d', 'first_day' => 1 ) ),
			$this->f( 'is_current', 'Current COA', 'true_false', array( 'default_value' => 0, 'ui' => 1 ) ),
			$this->tab( 'vetting_progress', __( 'Vetting Progress', 'pepselect-coa-archive' ) ),
			$this->f( 'expected_coa_date', 'Expected COA Date', 'date_picker', array( 'display_format' => 'F j, Y', 'return_format' => 'Y-m-d', 'first_day' => 1, 'instructions' => __( 'Required after submission while testing or the final COA is pending.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'pending_lab_url', 'Pending Laboratory URL', 'url', array( 'instructions' => __( 'Optional exact public laboratory status page. Kept separate from the final report URL.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'vendor_status_note', 'Vendor Status Note', 'text', array( 'maxlength' => 200 ) ),
			$this->f( 'public_status_note', 'Public Status Note', 'text', array( 'maxlength' => 240 ) ),
			$this->f( 'partial_results_available', 'Partial Results Available', 'true_false', array( 'default_value' => 0, 'ui' => 1, 'instructions' => __( 'Enable only when real partial laboratory results are ready for entry and public display.', 'pepselect-coa-archive' ) ) ),
			$this->tab( 'batch_vial', __( 'Batch & Vial Identity', 'pepselect-coa-archive' ) ),
			$this->f( 'batch_number', 'Batch Number', 'text', array( 'required' => 0, 'maxlength' => 120 ) ),
			$this->f( 'batch_vial_photo', 'Batch Vial Photo', 'image', array( 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp', 'instructions' => __( 'Upload a clear photo of the exact vial submitted for this batch. The label, strength, cap, and crimp should be visible whenever possible. Required during Verification in Progress and Complete.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'batch_identity_photos', 'Batch Identity Photos', 'gallery', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp', 'min' => 0, 'max' => 20, 'instructions' => __( 'Optional supporting photos that help identify the exact tested batch. Use attachment titles or captions to describe each view.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'vial_crimp_color', 'Vial Crimp Color', 'select', array( 'choices' => self::vial_colors(), 'allow_null' => 1, 'return_format' => 'value', 'instructions' => __( 'Required after the pre-release vendor/testing stages.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'other_vial_crimp_color', 'Other Crimp Color', 'text', array( 'maxlength' => 80, 'conditional_logic' => array( array( array( 'field' => 'field_ps_coa_test_vial_crimp_color', 'operator' => '==', 'value' => 'other' ) ) ) ) ),
			$this->f( 'vial_cap_color', 'Vial Cap Color', 'select', array( 'choices' => self::vial_colors(), 'allow_null' => 1, 'return_format' => 'value', 'instructions' => __( 'Required after the pre-release vendor/testing stages.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'other_vial_cap_color', 'Other Cap Color', 'text', array( 'maxlength' => 80, 'conditional_logic' => array( array( array( 'field' => 'field_ps_coa_test_vial_cap_color', 'operator' => '==', 'value' => 'other' ) ) ) ) ),
			$this->number( 'claimed_content', 'Claimed Content', 0 ),
			$this->f( 'content_unit', 'Content Unit', 'select', array( 'choices' => $units, 'default_value' => 'mg', 'return_format' => 'value' ) ),
			$this->number( 'vials_submitted', 'Vials Submitted', 0, 1 ),
			$this->number( 'vials_tested', 'Vials Tested', 1, 1, false ),
			$this->f( 'testing_lab', 'Testing Laboratory', 'select', array( 'choices' => self::labs(), 'allow_null' => 1, 'return_format' => 'value', 'instructions' => __( 'Required during verification and for approved reports.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'laboratory_logo', 'Laboratory Logo', 'image', array( 'return_format' => 'id', 'preview_size' => 'thumbnail', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp,gif,svg', 'instructions' => __( 'Optional logo for the testing laboratory shown on the public report. Upload a transparent PNG, SVG supported safely by WordPress policy, or another appropriately sized image.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'other_testing_lab', 'Other Laboratory Name', 'text', array( 'maxlength' => 120, 'conditional_logic' => array( array( array( 'field' => 'field_ps_coa_test_testing_lab', 'operator' => '==', 'value' => 'other' ) ) ) ) ),
			$this->f( 'lab_accession_number', 'Lab Accession Number', 'text', array( 'maxlength' => 120 ) ),
			$this->tab( 'sample', __( 'Sample Information', 'pepselect-coa-archive' ) ),
			$this->number( 'average_net_content', 'Average Net Content', 0 ),
			$this->number( 'minimum_net_content', 'Minimum Net Content', 0 ),
			$this->number( 'maximum_net_content', 'Maximum Net Content', 0 ),
			$this->number( 'net_content_std_dev', 'Net Content Standard Deviation', 0 ),
			$this->number( 'content_variance_percent', 'Content Variance Percent', null, 0.0001 ),
			$this->f( 'sample_appearance', 'Sample Appearance', 'text', array( 'maxlength' => 200, 'default_value' => 'White lyophilized powder' ) ),
			$this->tab( 'results', __( 'Test Results', 'pepselect-coa-archive' ) ),
			$this->f( 'purity_percentage', 'Purity Percentage', 'number', array( 'min' => 0, 'max' => 100, 'step' => 0.0001 ) ),
			$this->result( 'purity_status', 'Purity Status', 'pending', $result ),
			$this->f( 'purity_method', 'Purity Method', 'text', array( 'maxlength' => 120 ) ),
			$this->result( 'identity_status', 'Identity Status', 'pending', $result ),
			$this->f( 'identity_method', 'Identity Method', 'text', array( 'maxlength' => 120 ) ),
			$this->result( 'endotoxin_status', 'Endotoxin Status', 'reported', $result ),
			$this->f( 'endotoxin_result', 'Endotoxin Result', 'text', array( 'maxlength' => 120 ) ),
			$this->f( 'endotoxin_unit', 'Endotoxin Unit', 'text', array( 'maxlength' => 50, 'default_value' => 'EU/mL' ) ),
			$this->result( 'heavy_metals_status', 'Heavy Metals Status', 'not-tested', $result ),
			$this->f( 'heavy_metals_summary', 'Heavy Metals Summary', 'textarea', array( 'rows' => 3, 'maxlength' => 500, 'default_value' => 'Arsenic, cadmium, chromium, mercury, and lead were not detected.' ) ),
			$this->result( 'sterility_status', 'Sterility Status', 'not-tested', $result ),
			$this->f( 'sterility_result', 'Sterility Result', 'text', array( 'maxlength' => 200, 'default_value' => 'No Growth' ) ),
			$this->f( 'fentanyl_status', 'Fentanyl Screen Status', 'select', array( 'choices' => $fentanyl, 'allow_null' => 0, 'default_value' => 'not-tested', 'return_format' => 'value' ) ),
			$this->f( 'fentanyl_result', 'Fentanyl Screen Result', 'text', array( 'maxlength' => 200, 'readonly' => 1 ) ),
			$this->f( 'fentanyl_method', 'Fentanyl Screen Method', 'text', array( 'maxlength' => 120, 'default_value' => 'Immunoassay', 'readonly' => 1 ) ),
			$this->f( 'fentanyl_specification', 'Fentanyl Screen Specification', 'text', array( 'maxlength' => 200, 'default_value' => 'Immunoassay, 50 ng/mL cutoff', 'readonly' => 1 ) ),
			$this->f( 'fentanyl_notes', 'Fentanyl Screen Notes', 'text', array( 'maxlength' => 500 ) ),
			$this->tab( 'documents', __( 'Certificate Documents', 'pepselect-coa-archive' ) ),
			$this->f( 'coa_number', 'COA Number', 'text', array( 'maxlength' => 120 ) ),
			$this->f( 'lab_report_url', 'Public Lab Report URL', 'url', array( 'instructions' => __( 'Required for approved reports. Used by the public “View at ILS Labs” action.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'verification_code', 'Access Code', 'text', array( 'maxlength' => 200 ) ),
			$this->f( 'lab_verification_url', 'Lab Verification URL', 'url' ),
			$this->f( 'certificate_version', 'Certificate Version', 'text', array( 'maxlength' => 50 ) ),
			$this->f( 'coa_pdf_id', 'Original COA PDF', 'file', array( 'return_format' => 'id', 'library' => 'all', 'mime_types' => 'pdf' ) ),
			$this->f( 'page_images', 'COA Page Images', 'gallery', array( 'name' => 'coa_page_images', 'return_format' => 'id', 'preview_size' => 'medium', 'library' => 'all', 'mime_types' => 'jpg,jpeg,png,webp', 'min' => 0, 'max' => 20, 'instructions' => __( 'Upload page images in display order.', 'pepselect-coa-archive' ) ) ),
			$this->tab( 'public_notes', __( 'Public Notes', 'pepselect-coa-archive' ) ),
			$this->f( 'release_decision_note', 'Release Decision Note', 'textarea', array( 'rows' => 3, 'maxlength' => 500, 'instructions' => __( 'Required for failed reports and shown publicly as the release decision.', 'pepselect-coa-archive' ) ) ),
			$this->f( 'public_notes', 'Public Notes', 'textarea', array( 'rows' => 4, 'maxlength' => 1000 ) ),
			$this->f( 'report_notes', 'Report Notes', 'textarea', array( 'rows' => 4, 'maxlength' => 1000 ) ),
			$this->tab( 'internal_notes', __( 'Internal Notes', 'pepselect-coa-archive' ) ),
			$this->f( 'internal_notes', 'Internal Notes', 'textarea', array( 'rows' => 4, 'instructions' => __( 'Administrative only; excluded from REST.', 'pepselect-coa-archive' ) ) ),
		);
	}

	/** Returns lab choices. @return array */
	public static function labs() { return array( 'ils-labs' => 'ILS Labs', 'freedom-labs' => 'Freedom Diagnostics Testing', 'janoshik' => 'Janoshik Analytical', 'other' => 'Other' ); }
	/** Returns overall statuses. @return array */
	public static function statuses() { return array( 'pending' => 'Pending', 'approved' => 'Approved', 'failed' => 'Failed' ); }
	/** Returns final outcomes accepted for legacy preservation. @return array */
	public static function accepted_statuses() { return array_merge( self::statuses(), array( 'archived' => 'Archived', 'superseded' => 'Superseded', 'in-testing' => 'In Testing (legacy)', 'vendor-vetting' => 'Vendor Vetting (legacy)' ) ); }
	/** Returns vial color choices. @return array */
	public static function vial_colors() { return array( 'silver' => 'Silver', 'blue' => 'Blue', 'black' => 'Black', 'white' => 'White', 'red' => 'Red', 'green' => 'Green', 'gold' => 'Gold', 'purple' => 'Purple', 'other' => 'Other' ); }
	/** Returns standardized result choices. @return array */
	public static function result_choices() { return array( 'pass' => 'Pass', 'fail' => 'Fail', 'pending' => 'Pending', 'reported' => 'Reported', 'not-tested' => 'Not Tested', 'not-applicable' => 'Not Applicable' ); }
	/** Returns the deliberately narrower Fentanyl Screen choices. @return array */
	public static function fentanyl_choices() { return array( 'pass' => 'Pass', 'fail' => 'Fail', 'not-tested' => 'Not Tested' ); }
	/** Builds a field. @param string $suffix Key/name suffix. @param string $label Label. @param string $type Type. @param array $options Options. @return array */
	private function f( $suffix, $label, $type, $options = array() ) { $name = isset( $options['name'] ) ? $options['name'] : $suffix; unset( $options['name'] ); return array_merge( array( 'key' => 'field_ps_coa_test_' . $suffix, 'name' => $name, 'label' => $label, 'type' => $type, 'required' => 0 ), $options ); }
	/** Builds a tab. @param string $suffix Suffix. @param string $label Label. @return array */
	private function tab( $suffix, $label ) { return $this->f( 'tab_' . $suffix, $label, 'tab', array( 'placement' => 'top' ) ); }
	/** Builds a numeric field. @param string $name Name. @param string $label Label. @param float|null $min Minimum. @param float $step Step. @param bool $required Required. @return array */
	private function number( $name, $label, $min = 0, $step = 0.000001, $required = false ) { $options = array( 'step' => $step, 'required' => $required ? 1 : 0 ); if ( null !== $min ) { $options['min'] = $min; } return $this->f( $name, $label, 'number', $options ); }
	/** Builds a result select. @param string $name Name. @param string $label Label. @param string $default Default. @param array $choices Choices. @return array */
	private function result( $name, $label, $default, $choices ) { return $this->f( $name, $label, 'select', array( 'choices' => $choices, 'default_value' => $default, 'return_format' => 'value' ) ); }
}

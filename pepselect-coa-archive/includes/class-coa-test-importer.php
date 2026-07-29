<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Provides the client-side, single-record COA CSV importer. */
final class COA_Test_Importer {
	/** Registers importer and scoped admin-asset hooks. @return void */
	public function register_hooks() {
		add_action( 'add_meta_boxes_' . Post_Types::COA_TEST, array( $this, 'register_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** Registers the importer panel on Add/Edit Test screens. @return void */
	public function register_meta_box() {
		if ( ! current_user_can( 'edit_ps_coas' ) ) { return; }
		add_meta_box( 'ps-coa-test-csv-import', __( 'Import Test CSV', 'pepselect-coa-archive' ), array( $this, 'render_meta_box' ), Post_Types::COA_TEST, 'normal', 'high' );
	}

	/** Renders a database-neutral CSV importer interface. @param \WP_Post $post Current post. @return void */
	public function render_meta_box( $post ) {
		unset( $post );
		echo '<p>' . esc_html__( 'Preview and apply one laboratory test row to this form. Nothing is saved or published until you use the normal WordPress controls.', 'pepselect-coa-archive' ) . '</p>';
		echo '<p><label for="ps-coa-csv-file"><strong>' . esc_html__( 'Choose CSV File', 'pepselect-coa-archive' ) . '</strong></label><br><input type="file" id="ps-coa-csv-file" accept=".csv,text/csv" data-max-size="1048576"></p>';
		echo '<p><button type="button" class="button" id="ps-coa-csv-preview">' . esc_html__( 'Preview CSV', 'pepselect-coa-archive' ) . '</button> <button type="button" class="button button-primary" id="ps-coa-csv-apply" disabled>' . esc_html__( 'Apply CSV to Form', 'pepselect-coa-archive' ) . '</button> <button type="button" class="button" id="ps-coa-csv-clear" disabled>' . esc_html__( 'Clear Imported Values', 'pepselect-coa-archive' ) . '</button></p>';
		echo '<div id="ps-coa-csv-message" role="status" aria-live="polite"></div><div id="ps-coa-csv-preview-table"></div>';
	}

	/** Loads list CSS on the COA Test list and importer JS on Add/Edit screens. @param string $hook Current admin hook. @return void */
	public function enqueue_assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Types::COA_TEST !== $screen->post_type || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		if ( 'edit.php' === $hook ) {
			wp_enqueue_style( 'pepselect-coa-test-admin', plugins_url( 'assets/css/coa-test-admin.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
			return;
		}
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) { return; }
		wp_enqueue_script( 'pepselect-coa-test-importer', plugins_url( 'assets/js/coa-test-importer.js', PEPSELECT_COA_ARCHIVE_FILE ), array( 'jquery', 'acf-input' ), PEPSELECT_COA_ARCHIVE_VERSION, true );
		wp_localize_script( 'pepselect-coa-test-importer', 'PepSelectCOAImporterConfig', array( 'fields' => $this->field_map(), 'compounds' => $this->compounds(), 'messages' => array( 'oneRow' => __( 'This importer accepts one COA test per CSV. Please upload a CSV containing exactly one data row.', 'pepselect-coa-archive' ), 'confirmReplace' => __( 'Some existing values will be replaced. Continue?', 'pepselect-coa-archive' ), 'tooLarge' => __( 'CSV files must be 1 MB or smaller.', 'pepselect-coa-archive' ) ) ) );
	}

	/** Returns supported CSV column-to-ACF-key mappings. @return array */
	private function field_map() {
		$names = array( 'compound_id', 'batch_number', 'workflow_stage', 'test_date', 'expected_coa_date', 'date_received', 'testing_lab', 'other_testing_lab', 'lab_accession_number', 'is_current', 'vial_crimp_color', 'other_vial_crimp_color', 'vial_cap_color', 'other_vial_cap_color', 'claimed_content', 'content_unit', 'vials_tested', 'average_net_content', 'minimum_net_content', 'maximum_net_content', 'net_content_std_dev', 'content_variance_percent', 'sample_appearance', 'purity_percentage', 'purity_status', 'purity_method', 'identity_status', 'identity_method', 'endotoxin_status', 'endotoxin_result', 'endotoxin_unit', 'heavy_metals_status', 'heavy_metals_summary', 'sterility_status', 'sterility_result', 'fentanyl_status', 'fentanyl_result', 'fentanyl_method', 'fentanyl_specification', 'fentanyl_notes', 'coa_number', 'lab_report_url', 'verification_code', 'lab_verification_url', 'certificate_version', 'vendor_status_note', 'public_status_note', 'release_decision_note', 'public_notes', 'report_notes', 'internal_notes' );
		$map = array( 'coa_status' => 'field_ps_coa_test_status' );
		foreach ( $names as $name ) { $map[ $name ] = 'field_ps_coa_test_' . $name; }
		$map['vial_crimp_color_other'] = 'field_ps_coa_test_other_vial_crimp_color';
		$map['vial_cap_color_other'] = 'field_ps_coa_test_other_vial_cap_color';
		// Older templates used vials_submitted; map it onto the single Vials field.
		$map['vials_submitted'] = 'field_ps_coa_test_vials_tested';
		return $map;
	}

	/** Returns lightweight compound matching data only on importer screens. @return array */
	private function compounds() {
		$result = array();
		foreach ( get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'private' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC' ) ) as $compound ) {
			$display = get_post_meta( $compound->ID, 'display_name', true );
			$result[] = array( 'id' => $compound->ID, 'slug' => $compound->post_name, 'displayName' => $display ? $display : $compound->post_title, 'title' => $compound->post_title );
		}
		return $result;
	}
}

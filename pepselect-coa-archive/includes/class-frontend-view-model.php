<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Builds explicit, frontend-safe arrays; private metadata is never copied wholesale. */
final class Frontend_View_Model {
	/** Returns a main-archive compound model. @param \WP_Post $compound Compound. @param \WP_Post[] $tests Eligible tests. @return array */
	public function archive_compound( $compound, $tests ) {
		$approved = array_values( array_filter( $tests, static function ( $test ) { return 'approved' === COA_Workflow::outcome( $test ); } ) );
		$incoming = array_values( array_filter( $tests, static function ( $test ) { return 'pending' === COA_Workflow::outcome( $test ) && COA_Workflow::is_incoming_stage( COA_Workflow::stage( $test ) ); } ) );
		$failed = array_values( array_filter( $tests, static function ( $test ) { return 'failed' === COA_Workflow::outcome( $test ); } ) );
		usort( $approved, array( $this, 'compare_by_test_date' ) ); usort( $incoming, array( $this, 'compare_incoming' ) ); usort( $failed, array( $this, 'compare_by_test_date' ) );
		$latest = $approved ? $approved[0] : null;
		$summary = $latest ? $this->test_summary( $latest, $compound ) : array();
		$status_test = $latest ?: ( $incoming ? $incoming[0] : ( $failed ? $failed[0] : null ) );
		$status_summary = $status_test ? $this->test_summary( $status_test, $compound ) : array();
		$preview = array(); if ( $latest ) { $preview[] = $latest; } foreach ( array_merge( $incoming, array_slice( $approved, 1 ), $failed ) as $candidate ) { if ( count( $preview ) >= 3 ) { break; } $preview[] = $candidate; }
		return array_merge( $this->compound( $compound ), array(
			'approved_test_count' => count( $approved ),
			'public_report_count' => count( $tests ),
			'incoming_report_count' => count( $incoming ),
			'latest_approved_test_date' => $summary ? $summary['test_date'] : '',
			'latest_approved_test_date_label' => $summary ? $summary['test_date_label'] : '',
			'latest_approved_batch_number' => $summary ? $summary['batch_number'] : '',
			'latest_purity_percentage' => $summary ? $summary['purity_percentage'] : '',
			'latest_purity_percentage_display' => $summary ? $summary['purity_percentage_display'] : '',
			'latest_average_net_content' => $summary ? $summary['average_net_content'] : '',
			'latest_laboratory_display_name' => $summary ? $summary['laboratory'] : '',
			'latest_coa_status' => $summary ? $summary['coa_status'] : '',
			'latest_test_url' => $summary ? $summary['detail_url'] : '',
			'is_full_qc_documented' => $summary ? $summary['is_full_qc_documented'] : false,
			'archive_date_heading' => $status_summary && 'pending' === $status_summary['coa_status'] ? Design_Settings::copy( 'expected_date_label' ) : __( 'Latest test', 'pepselect-coa-archive' ),
			'archive_date_label' => $status_summary ? ( 'pending' === $status_summary['coa_status'] ? $status_summary['expected_coa_date_label'] : $status_summary['test_date_label'] ) : '',
			'archive_purity_display' => $status_summary ? $status_summary['purity_percentage_display'] : '',
			'archive_laboratory' => $status_summary ? $status_summary['laboratory'] : '',
			'archive_show_date' => $status_summary && (bool) ( 'pending' === $status_summary['coa_status'] ? $status_summary['expected_coa_date_label'] : $status_summary['test_date_label'] ),
			'archive_show_purity' => $status_summary && 'complete' === $status_summary['workflow_stage'],
			'archive_show_laboratory' => $status_summary && in_array( $status_summary['workflow_stage'], array( 'in-testing', 'complete' ), true ) && (bool) $status_summary['laboratory'],
			'public_status_label' => $status_summary ? $status_summary['public_status_label'] : '',
			'public_status_copy' => $status_summary ? $status_summary['public_status_copy'] : '',
			'public_status_tone' => $status_summary ? $status_summary['public_status_tone'] : 'neutral',
			'recent_batches' => array_map( function ( $test ) use ( $compound ) { return $this->test_summary( $test, $compound ); }, $preview ),
			'has_current_approved_test' => (bool) array_filter( $approved, static function ( $test ) { return (bool) absint( get_post_meta( $test->ID, 'is_current', true ) ); } ),
		) );
	}

	/** Returns public compound fields. @param \WP_Post $compound Compound. @return array */
	public function compound( $compound ) {
		$image_id = absint( get_post_meta( $compound->ID, 'compound_image_id', true ) );
		$product_id = absint( get_post_meta( $compound->ID, 'woocommerce_product_id', true ) );
		$display_name = get_post_meta( $compound->ID, 'display_name', true ) ?: $compound->post_title;
		$compound_name = (string) get_post_meta( $compound->ID, 'compound_name', true );
		$strength = (string) get_post_meta( $compound->ID, 'strength_value', true );
		$strength_unit = (string) get_post_meta( $compound->ID, 'strength_unit', true );
		$strength_pattern = trim( pepselect_coa_format_number( $strength ) . ' ' . $strength_unit );
		return array(
			'compound_id' => $compound->ID,
			'title' => $compound->post_title,
			'slug' => $compound->post_name,
			'display_name' => $display_name,
			'compound_name' => $compound_name,
			'public_name' => trim( $compound_name ) ?: $display_name,
			'display_strength_separately' => '' !== trim( $compound_name ) || '' === $strength_pattern || false === stripos( preg_replace( '/\s+/', '', $display_name ), preg_replace( '/\s+/', '', $strength_pattern ) ),
			'short_name' => (string) get_post_meta( $compound->ID, 'short_name', true ),
			'strength_value' => $strength,
			'strength_value_display' => pepselect_coa_format_number( $strength, 'quantity' ),
			'strength_unit' => $strength_unit,
			'category' => (string) get_post_meta( $compound->ID, 'compound_category', true ),
			'archive_description' => (string) get_post_meta( $compound->ID, 'archive_description', true ),
			'compound_image_id' => $image_id,
			'compound_image_url' => $this->image_url( $image_id, 'medium' ),
			'compound_image_srcset' => $this->image_srcset( $image_id, 'medium' ),
			'compound_image_sizes' => $this->image_sizes( $image_id, 'medium' ),
			'compound_image_alt' => $this->image_alt( $image_id, $compound->post_title ),
			'display_order' => absint( get_post_meta( $compound->ID, 'display_order', true ) ),
			'is_featured' => (bool) absint( get_post_meta( $compound->ID, 'is_featured', true ) ),
			'url' => $this->compound_url( $compound ),
			'woocommerce_product_id' => $product_id,
			'woocommerce_product_url' => $this->product_url( $product_id ),
		);
	}

	/** Returns the public summary allowlist for a test. @param \WP_Post $test Test. @param \WP_Post|null $compound Compound. @return array */
	public function test_summary( $test, $compound = null ) {
		$compound = $compound ?: get_post( absint( get_post_meta( $test->ID, 'compound_id', true ) ) );
		$coa_status = COA_Workflow::outcome( $test ); $workflow_stage = COA_Workflow::stage( $test );
		$complete = 'complete' === $workflow_stage; $testing = 'in-testing' === $workflow_stage;
		$batch_public = $complete || $testing; $lab_public = $complete || $testing; $expected_public = in_array( $workflow_stage, array( 'waiting-on-vendor', 'submitted-to-lab', 'in-testing' ), true );
		$partial = $testing && 'publish' === $test->post_status && (bool) absint( get_post_meta( $test->ID, 'partial_results_available', true ) );
		$results_public = $complete || $partial;
		$date = $complete ? (string) get_post_meta( $test->ID, 'test_date', true ) : '';
		$claimed = $results_public ? (string) get_post_meta( $test->ID, 'claimed_content', true ) : '';
		$content_unit = $results_public ? (string) get_post_meta( $test->ID, 'content_unit', true ) : '';
		$vials = $complete ? (string) get_post_meta( $test->ID, 'vials_tested', true ) : '';
		$average = $results_public ? (string) get_post_meta( $test->ID, 'average_net_content', true ) : '';
		$minimum = $results_public ? (string) get_post_meta( $test->ID, 'minimum_net_content', true ) : '';
		$maximum = $results_public ? (string) get_post_meta( $test->ID, 'maximum_net_content', true ) : '';
		$purity = $results_public ? (string) get_post_meta( $test->ID, 'purity_percentage', true ) : '';
		$endotoxin_result = $results_public ? trim( (string) get_post_meta( $test->ID, 'endotoxin_result', true ) ) : '';
		$endotoxin_value = $results_public ? (string) get_post_meta( $test->ID, 'endotoxin_status', true ) : '';
		$reported_success = 'publish' === $test->post_status && 'approved' === $coa_status && 'reported' === $endotoxin_value && '' !== $endotoxin_result;
		$full_qc = $this->is_full_qc_documented( $test );
		$public_status = $this->public_status( $coa_status, $workflow_stage, $full_qc );
		$public_note = trim( (string) get_post_meta( $test->ID, 'public_status_note', true ) ); if ( $public_note ) { $public_status['copy'] = $public_note; }
		$testing_lab = $lab_public ? (string) get_post_meta( $test->ID, 'testing_lab', true ) : '';
		$other_testing_lab = $lab_public ? (string) get_post_meta( $test->ID, 'other_testing_lab', true ) : '';
		$laboratory = $lab_public ? $this->laboratory_name( $testing_lab, $other_testing_lab ) : '';
		$batch_image_id = absint( get_post_meta( $test->ID, 'batch_vial_photo', true ) );
		$image_source = $this->valid_image_id( $batch_image_id ) ? 'batch-vial-photo' : '';
		$image_id = $image_source ? $batch_image_id : get_post_thumbnail_id( $test->ID );
		if ( ! $image_source && $this->valid_image_id( $image_id ) ) { $image_source = 'featured-image'; }
		if ( ! $image_source && $compound ) { $image_id = absint( get_post_meta( $compound->ID, 'compound_image_id', true ) ) ?: get_post_thumbnail_id( $compound->ID ); if ( $this->valid_image_id( $image_id ) ) { $image_source = 'compound-image'; } }
		if ( ! $image_source ) { $image_id = 0; $image_source = 'local-placeholder'; }
		$image_url = $image_id ? $this->image_url( $image_id, 'large' ) : plugins_url( 'assets/images/neutral-vial.svg', PEPSELECT_COA_ARCHIVE_FILE );
		$public_title = $batch_public ? $test->post_title : ( isset( COA_Workflow::stages()[ $workflow_stage ] ) ? COA_Workflow::stages()[ $workflow_stage ] : __( 'Vetting update', 'pepselect-coa-archive' ) );
		return array(
			'test_id' => $test->ID, 'title' => $public_title, 'slug' => $batch_public ? $test->post_name : 'progress-' . $test->ID,
			'batch_number' => $batch_public ? (string) get_post_meta( $test->ID, 'batch_number', true ) : '', 'batch_is_public' => $batch_public,
			'test_date' => $date, 'test_date_label' => $this->date_label( $date ),
			'expected_coa_date' => $expected_public ? (string) get_post_meta( $test->ID, 'expected_coa_date', true ) : '',
			'expected_coa_date_label' => $expected_public ? $this->date_label( get_post_meta( $test->ID, 'expected_coa_date', true ) ) : '',
			'date_received' => $complete ? (string) get_post_meta( $test->ID, 'date_received', true ) : '',
			'date_received_label' => $complete ? $this->date_label( get_post_meta( $test->ID, 'date_received', true ) ) : '',
			'laboratory' => $laboratory,
			'coa_status' => $coa_status, 'coa_status_data' => $this->status( $coa_status ),
			'workflow_stage' => $workflow_stage, 'workflow_stage_label' => isset( COA_Workflow::stages()[ $workflow_stage ] ) ? COA_Workflow::stages()[ $workflow_stage ] : '',
			'public_status_label' => $public_status['label'], 'public_status_copy' => $public_status['copy'], 'public_status_tone' => $public_status['tone'],
			'is_current' => $complete && 'approved' === $coa_status && (bool) absint( get_post_meta( $test->ID, 'is_current', true ) ),
			'claimed_content' => $claimed, 'claimed_content_display' => pepselect_coa_format_number( $claimed ),
			'content_unit' => $content_unit,
			'vials_tested' => $vials, 'vials_tested_display' => pepselect_coa_format_number( $vials, 'integer' ),
			'average_net_content' => $average, 'average_net_content_display' => pepselect_coa_format_number( $average ),
			'minimum_net_content' => $minimum, 'minimum_net_content_display' => pepselect_coa_format_number( $minimum ),
			'maximum_net_content' => $maximum, 'maximum_net_content_display' => pepselect_coa_format_number( $maximum ),
			'purity_percentage' => $purity, 'purity_percentage_display' => pepselect_coa_format_number( $purity, 'purity' ),
			'purity_status' => $this->status( $results_public ? get_post_meta( $test->ID, 'purity_status', true ) : '' ),
			'identity_status' => $this->status( $results_public ? get_post_meta( $test->ID, 'identity_status', true ) : '' ),
			'endotoxin_status' => $this->status( $endotoxin_value, $reported_success ),
			'endotoxin_result' => $endotoxin_result,
			'endotoxin_unit' => $results_public ? (string) get_post_meta( $test->ID, 'endotoxin_unit', true ) : '',
			'heavy_metals_status' => $this->status( $results_public ? get_post_meta( $test->ID, 'heavy_metals_status', true ) : '' ),
			'sterility_status' => $this->status( $results_public ? get_post_meta( $test->ID, 'sterility_status', true ) : '' ),
			'partial_results_available' => $partial, 'has_partial_results' => $partial && $this->has_real_results( $test ),
			'is_full_qc_documented' => $full_qc,
			'assurance_label' => $full_qc ? Design_Settings::copy( 'full_qc_label' ) : Design_Settings::copy( 'neutral_label' ),
			'coa_number' => $complete ? (string) get_post_meta( $test->ID, 'coa_number', true ) : '',
			'lab_report_url' => $complete ? $this->http_url( get_post_meta( $test->ID, 'lab_report_url', true ) ) : '',
			'pending_lab_url' => $testing ? $this->http_url( get_post_meta( $test->ID, 'pending_lab_url', true ) ) : '',
			'vendor_status_note' => in_array( $workflow_stage, array( 'vendor-vetting', 'waiting-on-vendor' ), true ) ? (string) get_post_meta( $test->ID, 'vendor_status_note', true ) : '',
			'public_status_note' => $public_note,
			'release_decision_note' => $complete && 'failed' === $coa_status ? (string) get_post_meta( $test->ID, 'release_decision_note', true ) : '',
			'vial_crimp_color' => ( $complete || $testing ) ? $this->vial_color( get_post_meta( $test->ID, 'vial_crimp_color', true ), get_post_meta( $test->ID, 'other_vial_crimp_color', true ) ?: get_post_meta( $test->ID, 'vial_crimp_color_other', true ) ) : '',
			'vial_cap_color' => ( $complete || $testing ) ? $this->vial_color( get_post_meta( $test->ID, 'vial_cap_color', true ), get_post_meta( $test->ID, 'other_vial_cap_color', true ) ?: get_post_meta( $test->ID, 'vial_cap_color_other', true ) ) : '',
			'vial_image_id' => $image_id, 'vial_image_source' => $image_source, 'vial_image_is_exact' => 'batch-vial-photo' === $image_source,
			'vial_image_url' => $image_url,
			'vial_image_srcset' => $image_id ? $this->image_srcset( $image_id, 'large' ) : '',
			'vial_image_sizes' => $image_id ? $this->image_sizes( $image_id, 'large' ) : '',
			'vial_image_alt' => $this->image_alt( $image_id, $batch_public ? sprintf( __( 'Batch %s vial', 'pepselect-coa-archive' ), get_post_meta( $test->ID, 'batch_number', true ) ) : sprintf( __( '%s vial image', 'pepselect-coa-archive' ), $public_title ) ),
			'detail_url' => $compound ? $this->test_url( $compound, $test ) : '',
			'public_notes' => $complete ? (string) get_post_meta( $test->ID, 'public_notes', true ) : '',
		);
	}

	/** Builds the compact, truthful data required by the compound-history cards. @return array */
	public function history_report( $test, $compound ) {
		$model = $this->test_summary( $test, $compound );
		$results = 'complete' === $model['workflow_stage'] || $model['has_partial_results'];
		$model['purity_method'] = $results ? (string) get_post_meta( $test->ID, 'purity_method', true ) : '';
		$model['identity_method'] = $results ? (string) get_post_meta( $test->ID, 'identity_method', true ) : '';
		$model['heavy_metals_summary'] = $results ? trim( (string) get_post_meta( $test->ID, 'heavy_metals_summary', true ) ) : '';
		$model['sterility_result'] = $results ? trim( (string) get_post_meta( $test->ID, 'sterility_result', true ) ) : '';
		$fentanyl = $results ? sanitize_key( (string) get_post_meta( $test->ID, 'fentanyl_status', true ) ) : '';
		$model['fentanyl_result'] = 'pass' === $fentanyl ? __( 'Not detected', 'pepselect-coa-archive' ) : ( 'fail' === $fentanyl ? __( 'Detected', 'pepselect-coa-archive' ) : '' );
		$model['fentanyl_method'] = in_array( $fentanyl, array( 'pass', 'fail', 'not-tested' ), true ) ? 'Immunoassay' : '';
		$model['fentanyl_specification'] = in_array( $fentanyl, array( 'pass', 'fail', 'not-tested' ), true ) ? '50 ng/mL cutoff' : '';
		$model['fentanyl_status'] = $this->status( $fentanyl );
		$model['result_rows'] = $results ? $this->result_rows( $test, $model ) : array();
		$model['qc_strip_rows'] = $this->qc_strip_rows( $model['result_rows'], $model );
		$model['reported_category_count'] = count( array_filter( $model['qc_strip_rows'], static function ( $row ) { return ! empty( $row['reported'] ); } ) );
		$model['successful_category_count'] = count( array_filter( $model['qc_strip_rows'], static function ( $row ) { return ! empty( $row['reported'] ) && ! empty( $row['status']['success'] ); } ) );
		$model['all_reported_successful'] = $model['reported_category_count'] > 0 && $model['reported_category_count'] === $model['successful_category_count'];
		$model['is_full_qc_documented'] = 'approved' === $model['coa_status'] && 7 === $model['reported_category_count'] && 7 === $model['successful_category_count'];
		$model['history_report_type'] = $this->history_report_type( $model );
		$model['history_qc_title'] = $model['is_full_qc_documented'] ? __( 'Full-QC testing passed.', 'pepselect-coa-archive' ) : ( $model['all_reported_successful'] ? __( 'QC testing passed.', 'pepselect-coa-archive' ) : __( 'QC testing results.', 'pepselect-coa-archive' ) );
		$model['history_qc_summary'] = sprintf( __( '%1$d of 7 laboratory categories reported. %2$s', 'pepselect-coa-archive' ), $model['reported_category_count'], 'failed' === $model['coa_status'] ? __( 'Release review did not pass.', 'pepselect-coa-archive' ) : __( 'Independent documentation on file.', 'pepselect-coa-archive' ) );
		$model['history_status_label'] = 'failed' === $model['coa_status'] ? __( 'Did not pass release review', 'pepselect-coa-archive' ) : ( $model['is_full_qc_documented'] ? __( 'Full-QC documented', 'pepselect-coa-archive' ) : __( 'QC documented', 'pepselect-coa-archive' ) );
		$model['history_context'] = 'failed' === $model['coa_status'] ? __( 'This batch did not pass release review and was not released for sale.', 'pepselect-coa-archive' ) : __( 'Independent testing record with published batch documentation.', 'pepselect-coa-archive' );
		$model['history_claims'] = array();
		if ( $model['laboratory'] ) { $model['history_claims'][] = __( 'Independently tested', 'pepselect-coa-archive' ); }
		$model['pdf_attachment_id'] = 'complete' === $model['workflow_stage'] ? absint( get_post_meta( $test->ID, 'coa_pdf_id', true ) ) : 0;
		$model['pdf_url'] = $this->pdf_url( $model['pdf_attachment_id'] );
		if ( $model['pdf_url'] ) { $model['history_claims'][] = __( 'Documentation available', 'pepselect-coa-archive' ); }
		if ( ! empty( $model['identity_status']['success'] ) ) { $model['history_claims'][] = __( 'Identity confirmed', 'pepselect-coa-archive' ); }
		return $model;
	}

	/** Returns full public report data including validated attachments. @param \WP_Post $test Test. @param \WP_Post $compound Compound. @return array */
	public function report( $test, $compound ) {
		$model = $this->test_summary( $test, $compound );
		$laboratory_logo = $model['laboratory'] ? $this->laboratory_logo( $test, get_post_meta( $test->ID, 'testing_lab', true ), get_post_meta( $test->ID, 'other_testing_lab', true ) ) : $this->empty_laboratory_logo();
		$model['laboratory_logo_id'] = $laboratory_logo['attachment_id']; $model['laboratory_logo_url'] = $laboratory_logo['url'];
		$model['laboratory_logo_source'] = $laboratory_logo['source']; $model['laboratory_logo_alt'] = $laboratory_logo['alt'];
		$results = 'complete' === $model['workflow_stage'] || $model['has_partial_results']; $complete = 'complete' === $model['workflow_stage'];
		$model['purity_method'] = $results ? (string) get_post_meta( $test->ID, 'purity_method', true ) : '';
		$model['identity_method'] = $results ? (string) get_post_meta( $test->ID, 'identity_method', true ) : '';
		$model['heavy_metals_summary'] = $results ? (string) get_post_meta( $test->ID, 'heavy_metals_summary', true ) : '';
		$model['sterility_result'] = $results ? (string) get_post_meta( $test->ID, 'sterility_result', true ) : '';
		$fentanyl_status = $results ? sanitize_key( (string) get_post_meta( $test->ID, 'fentanyl_status', true ) ) : '';
		$fentanyl_saved = in_array( $fentanyl_status, array( 'pass', 'fail', 'not-tested' ), true );
		$model['fentanyl_result'] = 'pass' === $fentanyl_status ? 'Not detected' : ( 'fail' === $fentanyl_status ? 'Detected' : '' );
		$model['fentanyl_method'] = $fentanyl_saved ? 'Immunoassay' : '';
		$model['fentanyl_specification'] = $fentanyl_saved ? '50 ng/mL cutoff' : '';
		$model['fentanyl_status'] = $this->status( $fentanyl_status );
		$model['certificate_version'] = $complete ? (string) get_post_meta( $test->ID, 'certificate_version', true ) : '';
		$model['report_notes'] = $complete ? (string) get_post_meta( $test->ID, 'report_notes', true ) : '';
		$model['pdf_attachment_id'] = $complete ? absint( get_post_meta( $test->ID, 'coa_pdf_id', true ) ) : 0;
		$model['pdf_url'] = $complete ? $this->pdf_url( $model['pdf_attachment_id'] ) : '';
		$model['page_images'] = $complete ? $this->gallery( get_post_meta( $test->ID, 'coa_page_images', true ), $compound->post_title, 'certificate page' ) : array();
		$model['batch_identity_photos'] = $complete ? $this->gallery( get_post_meta( $test->ID, 'batch_identity_photos', true ), $compound->post_title, 'batch identity photo' ) : array();
		$model['result_rows'] = $results ? $this->result_rows( $test, $model ) : array();
		$model['has_summary_metrics'] = '' !== $model['purity_percentage_display'] || '' !== $model['average_net_content_display'] || '' !== $model['claimed_content_display'] || '' !== $model['vials_tested_display'];
		$model['qc_strip_rows'] = $this->qc_strip_rows( $model['result_rows'], $model );
		$model['qc_category_count'] = count( $model['qc_strip_rows'] );
		$model['reported_category_count'] = count( array_filter( $model['qc_strip_rows'], static function ( $row ) { return ! empty( $row['reported'] ); } ) );
		$model['qc_success_category_count'] = count( array_filter( $model['qc_strip_rows'], static function ( $row ) { return ! empty( $row['reported'] ) && ! empty( $row['status']['success'] ); } ) );
		$model['qc_all_reported_successful'] = $model['reported_category_count'] > 0 && $model['reported_category_count'] === $model['qc_success_category_count'];
		$model['is_full_qc_documented'] = 'publish' === $test->post_status && 'approved' === $model['coa_status'] && 'complete' === $model['workflow_stage'] && 7 === $model['reported_category_count'] && 7 === $model['qc_success_category_count'];
		$model['qc_strip_title'] = $model['is_full_qc_documented'] ? __( 'Full-QC Testing Passed', 'pepselect-coa-archive' ) : ( $model['qc_all_reported_successful'] ? __( 'QC Testing Passed', 'pepselect-coa-archive' ) : __( 'QC Testing Results', 'pepselect-coa-archive' ) );
		$model['qc_strip_summary'] = $model['qc_all_reported_successful'] ? __( 'All reported tests met the laboratory specifications listed below.', 'pepselect-coa-archive' ) : __( 'Review the reported category results below.', 'pepselect-coa-archive' );
		$model['show_qc_strip'] = 'approved' === $model['coa_status'] && $model['reported_category_count'] > 0;
		$model['lab_report_host'] = $model['lab_report_url'] ? (string) wp_parse_url( $model['lab_report_url'], PHP_URL_HOST ) : '';
		$model['outcome_points'] = array();
		if ( 'approved' === $model['coa_status'] ) {
			if ( $model['laboratory'] ) { $model['outcome_points'][] = sprintf( __( 'Independently tested at %s', 'pepselect-coa-archive' ), $model['laboratory'] ); }
			if ( $model['is_full_qc_documented'] ) { $model['outcome_points'][] = __( 'Full-QC panel documented', 'pepselect-coa-archive' ); }
			$model['outcome_points'][] = __( 'Published batch record on file', 'pepselect-coa-archive' );
		} elseif ( 'failed' === $model['coa_status'] && 'fail' === $fentanyl_status ) {
			$model['outcome_points'][] = __( 'Fentanyl Screen recorded a failed result', 'pepselect-coa-archive' );
		}
		return $model;
	}

	/** Formats a stored lab value once for every frontend consumer. @return string */
	public function laboratory_name( $stored, $other = '' ) {
		$names = array( 'ils-labs' => 'ILS Labs', 'janoshik' => 'Janoshik Analytical', 'mz-biotech' => 'MZ Biolabs' );
		return isset( $names[ $stored ] ) ? $names[ $stored ] : ( 'other' === $stored && trim( (string) $other ) ? trim( (string) $other ) : ucwords( str_replace( '-', ' ', (string) $stored ) ) );
	}

	/** Returns semantic status data with an explicit approved-report success override. @param string $stored Stored value. @param bool $success_override Green icon without relabeling. @return array */
	public function status( $stored, $success_override = false ) {
		$stored = sanitize_key( str_replace( '_', '-', (string) $stored ) );
		$labels = array( 'approved' => 'Approved', 'failed' => 'Failed', 'in-testing' => 'In Testing', 'vendor-vetting' => 'Vendor Vetting', 'pass' => 'Pass', 'fail' => 'Fail', 'pending' => 'Pending', 'not-tested' => 'Not Tested', 'not-applicable' => 'Not Applicable', 'reported' => 'Reported' );
		$value = isset( $labels[ $stored ] ) ? $stored : '';
		$class = $value ? 'ps-coa-status--' . $value : 'ps-coa-status--empty';
		if ( $success_override ) { $class .= ' ps-coa-status--success-reported'; }
		return array( 'value' => $value, 'label' => $value ? $labels[ $value ] : 'Not Reported', 'class' => $class, 'icon' => $success_override ? 'pass' : ( $value ?: 'empty' ), 'public' => true, 'success' => (bool) $success_override || in_array( $value, array( 'pass', 'approved' ), true ) );
	}

	/** Returns true only for a complete approved, published full-QC record. @param \WP_Post|int $test Test. @return bool */
	public function is_full_qc_documented( $test ) {
		$test = get_post( $test ); if ( ! $test || 'publish' !== $test->post_status || 'approved' !== COA_Workflow::outcome( $test ) || 'complete' !== COA_Workflow::stage( $test ) ) { return false; }
		foreach ( array( 'purity_status', 'identity_status', 'heavy_metals_status', 'sterility_status' ) as $key ) { if ( 'pass' !== get_post_meta( $test->ID, $key, true ) ) { return false; } }
		foreach ( array( 'purity_percentage', 'average_net_content', 'heavy_metals_summary', 'sterility_result', 'endotoxin_result' ) as $key ) { if ( '' === trim( (string) get_post_meta( $test->ID, $key, true ) ) ) { return false; } }
		$endotoxin = get_post_meta( $test->ID, 'endotoxin_status', true );
		if ( ! in_array( $endotoxin, array( 'pass', 'reported' ), true ) ) { return false; }
		return $this->fentanyl_is_documented_success( get_post_meta( $test->ID, 'fentanyl_status', true ) );
	}

	/** Returns mission-aligned public status copy without changing stored status. @return array */
	private function public_status( $status, $stage, $full_qc ) {
		unset( $full_qc );
		if ( 'approved' === $status ) { return array( 'label' => Design_Settings::copy( 'full_qc_label' ), 'copy' => Design_Settings::copy( 'full_qc_copy' ), 'tone' => 'success' ); }
		if ( 'failed' === $status ) { return array( 'label' => Design_Settings::copy( 'failed_report_label' ), 'copy' => Design_Settings::copy( 'failed_report_copy' ), 'tone' => 'failed' ); }
		$copy = array(
			'vendor-vetting' => array( 'vendor_vetting_label', 'vendor_vetting_copy', 'vendor' ), 'waiting-on-vendor' => array( 'waiting_vendor_label', 'waiting_vendor_copy', 'vendor' ),
			'submitted-to-lab' => array( 'submitted_lab_label', 'submitted_lab_copy', 'progress' ),
			'in-testing' => array( 'in_testing_label', 'in_testing_copy', 'progress' ), 'complete' => array( 'complete_stage_label', 'complete_stage_copy', 'success' ),
		);
		if ( isset( $copy[ $stage ] ) ) { return array( 'label' => Design_Settings::copy( $copy[ $stage ][0] ), 'copy' => Design_Settings::copy( $copy[ $stage ][1] ), 'tone' => $copy[ $stage ][2] ); }
		return array( 'label' => __( 'Report Published', 'pepselect-coa-archive' ), 'copy' => '', 'tone' => 'neutral' );
	}

	private function vial_color( $stored, $other ) { $choices = COA_Test_Fields::vial_colors(); return 'other' === $stored ? trim( (string) $other ) : ( isset( $choices[ $stored ] ) ? $choices[ $stored ] : '' ); }

	public function archive_url() { return home_url( user_trailingslashit( 'testing' ) ); }
	public function compound_url( $compound ) { return home_url( user_trailingslashit( 'testing/' . $compound->post_name ) ); }
	public function test_url( $compound, $test ) { return home_url( user_trailingslashit( 'testing/' . $compound->post_name . '/' . $this->batch_slug( $test ) ) ); }
	public function batch_slug( $test ) { $stage = COA_Workflow::stage( $test ); if ( in_array( $stage, array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab' ), true ) ) { return 'progress-' . absint( $test->ID ); } $batch = sanitize_title( get_post_meta( $test->ID, 'batch_number', true ) ); return $batch ?: sanitize_title( $test->post_name ); }

	/** Returns whether an explicitly enabled in-progress report contains a real result. @param \WP_Post $test Test. @return bool */
	private function has_real_results( $test ) {
		if ( '' !== trim( (string) get_post_meta( $test->ID, 'purity_percentage', true ) ) ) { return true; }
		if ( '' !== trim( (string) get_post_meta( $test->ID, 'average_net_content', true ) ) ) { return true; }
		foreach ( array( 'purity_status', 'identity_status', 'endotoxin_status', 'heavy_metals_status', 'sterility_status', 'fentanyl_status' ) as $key ) { if ( in_array( get_post_meta( $test->ID, $key, true ), array( 'pass', 'fail', 'reported' ), true ) ) { return true; } }
		return false;
	}

	/** Builds compact rows only from real saved laboratory data. @param \WP_Post $test Test. @param array $model Public model. @return array */
	private function result_rows( $test, $model ) {
		$rows = array(); $unit = $model['content_unit'];
		$this->add_result_row( $rows, 'identity', __( 'Identity', 'pepselect-coa-archive' ), $model['identity_method'], '', '', $model['identity_status'] );
		$purity_result = '' !== $model['purity_percentage_display'] ? $model['purity_percentage_display'] . '%' : '';
		$this->add_result_row( $rows, 'purity', __( 'Purity', 'pepselect-coa-archive' ), $model['purity_method'], '', $purity_result, $model['purity_status'] );
		if ( '' !== $model['average_net_content_display'] ) {
			$range = ( '' !== $model['minimum_net_content_display'] || '' !== $model['maximum_net_content_display'] ) ? trim( $model['minimum_net_content_display'] . '–' . $model['maximum_net_content_display'] . ' ' . $unit ) : '';
			$result = trim( $model['average_net_content_display'] . ' ' . $unit . ( $range ? ' (' . $range . ')' : '' ) );
			$this->add_result_row( $rows, 'net-content', __( 'Average Net Content', 'pepselect-coa-archive' ), '', '', $result, $this->status( 'reported', 'approved' === $model['coa_status'] ), __( 'Net Content', 'pepselect-coa-archive' ) );
		}
		$this->add_result_row( $rows, 'heavy-metals', __( 'Heavy Metals', 'pepselect-coa-archive' ), '', '', $model['heavy_metals_summary'], $model['heavy_metals_status'] );
		$this->add_result_row( $rows, 'sterility', __( 'Sterility', 'pepselect-coa-archive' ), '', '', $model['sterility_result'], $model['sterility_status'] );
		$this->add_result_row( $rows, 'endotoxins', __( 'Endotoxins', 'pepselect-coa-archive' ), '', '', trim( $model['endotoxin_result'] . ' ' . $model['endotoxin_unit'] ), $model['endotoxin_status'] );
		$this->add_result_row( $rows, 'fentanyl', __( 'Fentanyl Screen', 'pepselect-coa-archive' ), $model['fentanyl_method'], $model['fentanyl_specification'], $model['fentanyl_result'], $model['fentanyl_status'] );
		return $rows;
	}

	/** Builds the fixed seven-position QC strip without inventing missing results. @return array */
	private function qc_strip_rows( $result_rows, $model ) {
		$definitions = array(
			'identity' => __( 'Identity', 'pepselect-coa-archive' ),
			'purity' => __( 'Purity', 'pepselect-coa-archive' ),
			'net-content' => __( 'Net Content', 'pepselect-coa-archive' ),
			'heavy-metals' => __( 'Heavy Metals', 'pepselect-coa-archive' ),
			'sterility' => __( 'Sterility', 'pepselect-coa-archive' ),
			'endotoxins' => __( 'Endotoxins', 'pepselect-coa-archive' ),
			'fentanyl' => __( 'Fentanyl Screen', 'pepselect-coa-archive' ),
		);
		$by_key = array();
		foreach ( $result_rows as $row ) { $by_key[ $row['key'] ] = $row; }
		$rows = array();
		foreach ( $definitions as $key => $label ) {
			$row = isset( $by_key[ $key ] ) ? $by_key[ $key ] : array( 'key' => $key, 'label' => $label, 'short_label' => $label, 'method' => '', 'specification' => '', 'result' => '', 'status' => $this->status( '' ) );
			$reported = $this->qc_category_is_reported( $row );
			if ( ! $reported ) { $row['status'] = $this->status( '' ); $row['detail'] = '--'; }
			else { $row['detail'] = $this->qc_strip_detail( $row, $model ); }
			$row['reported'] = $reported;
			$rows[] = $row;
		}
		return $rows;
	}

	/** Returns whether a category has meaningful saved evidence rather than a blank/default state. @return bool */
	private function qc_category_is_reported( $row ) {
		$status = isset( $row['status']['value'] ) ? $row['status']['value'] : '';
		if ( 'fail' === $status ) { return true; }
		if ( ! in_array( $status, array( 'pass', 'reported' ), true ) ) { return false; }
		if ( 'identity' === $row['key'] ) { return 'pass' === $status; }
		return '' !== trim( (string) $row['result'] );
	}

	/** Builds compact strip copy strictly from the row's public saved values. @return string */
	private function qc_strip_detail( $row, $model ) {
		if ( 'net-content' === $row['key'] ) { return trim( $model['average_net_content_display'] . ' ' . $model['content_unit'] . ' ' . __( 'avg', 'pepselect-coa-archive' ) ); }
		if ( 'identity' === $row['key'] && ! empty( $row['status']['success'] ) ) { return implode( ' · ', array_filter( array( $row['method'], __( 'Confirmed', 'pepselect-coa-archive' ) ) ) ); }
		if ( in_array( $row['key'], array( 'sterility', 'endotoxins', 'fentanyl' ), true ) && '' !== $row['result'] ) { return $row['result']; }
		$parts = array_values( array_filter( array( $row['method'], $row['result'] ), static function ( $value ) { return '' !== trim( (string) $value ); } ) );
		if ( $parts ) { return implode( ' · ', $parts ); }
		if ( '' !== $row['specification'] ) { return $row['specification']; }
		return $row['status']['label'];
	}

	/** Returns an accurate compact report type for history cards. @return string */
	private function history_report_type( $model ) {
		if ( 7 === $model['reported_category_count'] ) { return __( 'Full-QC', 'pepselect-coa-archive' ); }
		$reported = array_values( array_filter( $model['qc_strip_rows'], static function ( $row ) { return ! empty( $row['reported'] ); } ) );
		$keys = wp_list_pluck( $reported, 'key' );
		if ( array( 'purity' ) === $keys ) { return __( 'Purity', 'pepselect-coa-archive' ); }
		if ( 2 === count( $keys ) && in_array( 'purity', $keys, true ) && in_array( 'identity', $keys, true ) ) { return __( 'Purity + Identity', 'pepselect-coa-archive' ); }
		return __( 'Partial QC', 'pepselect-coa-archive' );
	}

	/** Resolves a safe reusable laboratory logo without remote requests. @return array */
	private function laboratory_logo( $test, $stored_lab, $other_lab ) {
		$name = $this->laboratory_name( $stored_lab, $other_lab );
		$id = absint( get_post_meta( $test->ID, 'laboratory_logo', true ) );
		if ( ! $this->valid_laboratory_logo_id( $id ) ) { $id = $this->reusable_laboratory_logo_id( $stored_lab, $other_lab, $test->ID ); }
		if ( $this->valid_laboratory_logo_id( $id ) ) {
			return array( 'attachment_id' => $id, 'url' => $this->laboratory_logo_url( $id ), 'source' => 'attachment', 'alt' => sprintf( __( '%s logo', 'pepselect-coa-archive' ), $name ) );
		}
		if ( 'ils-labs' === sanitize_key( $stored_lab ) || 'ils-labs' === sanitize_title( $name ) ) {
			return array( 'attachment_id' => 0, 'url' => plugins_url( 'assets/images/ils-labs-logo.png', PEPSELECT_COA_ARCHIVE_FILE ), 'source' => 'bundled-ils', 'alt' => __( 'ILS Labs logo', 'pepselect-coa-archive' ) );
		}
		return $this->empty_laboratory_logo();
	}

	/** Reuses the newest valid logo assigned to the same normalized laboratory. @return int */
	private function reusable_laboratory_logo_id( $stored_lab, $other_lab, $exclude_id ) {
		static $cache = array();
		$normalized = sanitize_title( $this->laboratory_name( $stored_lab, $other_lab ) );
		if ( isset( $cache[ $normalized ] ) ) { return $cache[ $normalized ]; }
		$cache[ $normalized ] = 0;
		if ( ! $normalized ) { return 0; }
		$ids = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => array( 'publish', 'private', 'draft', 'pending' ), 'posts_per_page' => 50, 'fields' => 'ids', 'orderby' => 'modified', 'order' => 'DESC', 'post__not_in' => array( absint( $exclude_id ) ), 'meta_key' => 'testing_lab', 'meta_value' => (string) $stored_lab, 'no_found_rows' => true, 'suppress_filters' => false ) );
		if ( $ids ) { _prime_post_caches( $ids, false, false ); update_meta_cache( 'post', $ids ); }
		foreach ( $ids as $test_id ) {
			$candidate_name = $this->laboratory_name( get_post_meta( $test_id, 'testing_lab', true ), get_post_meta( $test_id, 'other_testing_lab', true ) );
			$candidate_id = absint( get_post_meta( $test_id, 'laboratory_logo', true ) );
			if ( $normalized === sanitize_title( $candidate_name ) && $this->valid_laboratory_logo_id( $candidate_id ) ) { $cache[ $normalized ] = $candidate_id; break; }
		}
		return $cache[ $normalized ];
	}

	/** Returns the neutral logo model used by the template's local icon fallback. @return array */
	private function empty_laboratory_logo() { return array( 'attachment_id' => 0, 'url' => '', 'source' => 'neutral', 'alt' => '' ); }

	/** Requires the explicit successful Fentanyl Screen status. @return bool */
	private function fentanyl_is_documented_success( $status ) { return 'pass' === sanitize_key( (string) $status ); }

	/** Adds a row only when at least one stored field is public. @param array $rows Rows. @return void */
	private function add_result_row( &$rows, $key, $label, $method, $specification, $result, $status, $short_label = '' ) {
		if ( '' === trim( (string) $method ) && '' === trim( (string) $specification ) && '' === trim( (string) $result ) && empty( $status['value'] ) ) { return; }
		$rows[] = array( 'key' => $key, 'label' => $label, 'short_label' => $short_label ?: $label, 'method' => trim( (string) $method ), 'specification' => trim( (string) $specification ), 'result' => trim( (string) $result ), 'status' => $status );
	}

	private function date_label( $value ) {
		$digits = preg_replace( '/\D/', '', (string) $value );
		if ( 8 !== strlen( $digits ) ) { return ''; }
		$time = strtotime( substr( $digits, 0, 4 ) . '-' . substr( $digits, 4, 2 ) . '-' . substr( $digits, 6, 2 ) . ' 00:00:00' );
		return $time ? wp_date( get_option( 'date_format' ), $time ) : '';
	}

	private function http_url( $url ) { $url = trim( (string) $url ); return $url && wp_http_validate_url( $url ) ? esc_url_raw( $url, array( 'http', 'https' ) ) : ''; }
	private function product_url( $id ) { $post = $id ? get_post( $id ) : null; return $post && 'product' === $post->post_type && 'publish' === $post->post_status ? get_permalink( $post ) : ''; }
	private function valid_image_id( $id ) { $post = $id ? get_post( $id ) : null; return $post && 'attachment' === $post->post_type && 'inherit' === $post->post_status && wp_attachment_is_image( $id ); }
	private function valid_laboratory_logo_id( $id ) {
		$post = $id ? get_post( $id ) : null; if ( ! $post || 'attachment' !== $post->post_type || 'inherit' !== $post->post_status ) { return false; }
		$mime = get_post_mime_type( $id );
		if ( in_array( $mime, array( 'image/jpeg', 'image/png', 'image/gif', 'image/webp' ), true ) ) { return wp_attachment_is_image( $id ); }
		return 'image/svg+xml' === $mime && in_array( 'image/svg+xml', array_values( get_allowed_mime_types() ), true ) && (bool) wp_get_attachment_url( $id );
	}
	private function laboratory_logo_url( $id ) { return 'image/svg+xml' === get_post_mime_type( $id ) ? (string) wp_get_attachment_url( $id ) : $this->image_url( $id, 'thumbnail' ); }
	private function image_url( $id, $size ) { $post = $id ? get_post( $id ) : null; return $post && 'attachment' === $post->post_type && 'inherit' === $post->post_status && wp_attachment_is_image( $id ) ? (string) wp_get_attachment_image_url( $id, $size ) : ''; }
	private function image_srcset( $id, $size ) { return $this->image_url( $id, $size ) ? (string) wp_get_attachment_image_srcset( $id, $size ) : ''; }
	private function image_sizes( $id, $size ) { return $this->image_url( $id, $size ) ? (string) wp_get_attachment_image_sizes( $id, $size ) : ''; }
	private function image_alt( $id, $fallback ) { $alt = $id ? trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : ''; return $alt ?: $fallback; }
	private function pdf_url( $id ) { $post = $id ? get_post( $id ) : null; return $post && 'attachment' === $post->post_type && 'inherit' === $post->post_status && 'application/pdf' === get_post_mime_type( $id ) ? (string) wp_get_attachment_url( $id ) : ''; }
	private function gallery( $value, $fallback, $kind = 'image' ) {
		$images = array(); $ids = is_array( $value ) ? array_map( 'absint', $value ) : array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
		if ( $ids ) { _prime_post_caches( $ids, false, false ); update_meta_cache( 'post', $ids ); }
		foreach ( $ids as $id ) {
			$id = absint( $id ); $thumbnail_url = $this->image_url( $id, 'medium_large' ); $full_url = $this->image_url( $id, 'full' );
			if ( $thumbnail_url && $full_url ) { $post = get_post( $id ); $images[] = array( 'attachment_id' => $id, 'thumbnail_url' => $thumbnail_url, 'full_url' => $full_url, 'srcset' => $this->image_srcset( $id, 'medium_large' ), 'sizes' => $this->image_sizes( $id, 'medium_large' ), 'alt' => $this->image_alt( $id, sprintf( __( '%1$s %2$s', 'pepselect-coa-archive' ), $fallback, $kind ) ), 'caption' => $post ? trim( (string) $post->post_excerpt ) : '', 'title' => $post ? trim( (string) $post->post_title ) : '' ); }
		}
		return $images;
	}

	/** Sorts archive previews by test date and publish date descending. @return int */
	private function compare_by_test_date( $left, $right ) {
		$current = absint( get_post_meta( $right->ID, 'is_current', true ) ) <=> absint( get_post_meta( $left->ID, 'is_current', true ) );
		if ( 0 !== $current ) { return $current; }
		$left_date = preg_replace( '/\D/', '', (string) get_post_meta( $left->ID, 'test_date', true ) );
		$right_date = preg_replace( '/\D/', '', (string) get_post_meta( $right->ID, 'test_date', true ) );
		$date = strcmp( $right_date, $left_date );
		return 0 !== $date ? $date : strcmp( $right->post_date_gmt, $left->post_date_gmt );
	}
	private function compare_incoming( $left, $right ) {
		$ld = preg_replace( '/\D/', '', (string) get_post_meta( $left->ID, 'expected_coa_date', true ) ); $rd = preg_replace( '/\D/', '', (string) get_post_meta( $right->ID, 'expected_coa_date', true ) );
		if ( $ld && $rd && $ld !== $rd ) { return strcmp( $ld, $rd ); } if ( $ld && ! $rd ) { return -1; } if ( ! $ld && $rd ) { return 1; }
		$priority = COA_Workflow::priority( COA_Workflow::stage( $left ) ) <=> COA_Workflow::priority( COA_Workflow::stage( $right ) ); return 0 !== $priority ? $priority : strcmp( $right->post_date_gmt, $left->post_date_gmt );
	}
}

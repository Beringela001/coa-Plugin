<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Builds explicit, frontend-safe arrays; private metadata is never copied wholesale. */
final class Frontend_View_Model {
	/** Returns a main-archive compound model. @param \WP_Post $compound Compound. @param \WP_Post[] $tests Eligible tests. @return array */
	public function archive_compound( $compound, $tests ) {
		$recent = array_values( $tests );
		usort( $recent, array( $this, 'compare_by_test_date' ) );
		$latest = $recent ? $recent[0] : null;
		$summary = $latest ? $this->test_summary( $latest, $compound ) : array();
		return array_merge( $this->compound( $compound ), array(
			'approved_test_count' => count( $tests ),
			'latest_approved_test_date' => $summary ? $summary['test_date'] : '',
			'latest_approved_test_date_label' => $summary ? $summary['test_date_label'] : '',
			'latest_approved_batch_number' => $summary ? $summary['batch_number'] : '',
			'latest_purity_percentage' => $summary ? $summary['purity_percentage'] : '',
			'latest_average_net_content' => $summary ? $summary['average_net_content'] : '',
			'latest_laboratory_display_name' => $summary ? $summary['laboratory'] : '',
			'latest_coa_status' => $summary ? $summary['coa_status'] : '',
			'latest_test_url' => $summary ? $summary['detail_url'] : '',
			'recent_batches' => array_map( function ( $test ) use ( $compound ) { return $this->test_summary( $test, $compound ); }, array_slice( $recent, 0, 3 ) ),
			'has_current_approved_test' => (bool) array_filter( $tests, static function ( $test ) { return (bool) absint( get_post_meta( $test->ID, 'is_current', true ) ); } ),
		) );
	}

	/** Returns public compound fields. @param \WP_Post $compound Compound. @return array */
	public function compound( $compound ) {
		$image_id = absint( get_post_meta( $compound->ID, 'compound_image_id', true ) );
		$product_id = absint( get_post_meta( $compound->ID, 'woocommerce_product_id', true ) );
		return array(
			'compound_id' => $compound->ID,
			'title' => $compound->post_title,
			'slug' => $compound->post_name,
			'display_name' => get_post_meta( $compound->ID, 'display_name', true ) ?: $compound->post_title,
			'compound_name' => (string) get_post_meta( $compound->ID, 'compound_name', true ),
			'short_name' => (string) get_post_meta( $compound->ID, 'short_name', true ),
			'strength_value' => (string) get_post_meta( $compound->ID, 'strength_value', true ),
			'strength_unit' => (string) get_post_meta( $compound->ID, 'strength_unit', true ),
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
		$date = (string) get_post_meta( $test->ID, 'test_date', true );
		return array(
			'test_id' => $test->ID, 'title' => $test->post_title, 'slug' => $test->post_name,
			'batch_number' => (string) get_post_meta( $test->ID, 'batch_number', true ),
			'test_date' => $date, 'test_date_label' => $this->date_label( $date ),
			'date_received' => (string) get_post_meta( $test->ID, 'date_received', true ),
			'date_received_label' => $this->date_label( get_post_meta( $test->ID, 'date_received', true ) ),
			'laboratory' => $this->laboratory_name( get_post_meta( $test->ID, 'testing_lab', true ), get_post_meta( $test->ID, 'other_testing_lab', true ) ),
			'coa_status' => 'approved', 'coa_status_data' => $this->status( 'approved' ),
			'is_current' => (bool) absint( get_post_meta( $test->ID, 'is_current', true ) ),
			'claimed_content' => (string) get_post_meta( $test->ID, 'claimed_content', true ),
			'content_unit' => (string) get_post_meta( $test->ID, 'content_unit', true ),
			'vials_tested' => (string) get_post_meta( $test->ID, 'vials_tested', true ),
			'average_net_content' => (string) get_post_meta( $test->ID, 'average_net_content', true ),
			'minimum_net_content' => (string) get_post_meta( $test->ID, 'minimum_net_content', true ),
			'maximum_net_content' => (string) get_post_meta( $test->ID, 'maximum_net_content', true ),
			'purity_percentage' => (string) get_post_meta( $test->ID, 'purity_percentage', true ),
			'purity_status' => $this->status( get_post_meta( $test->ID, 'purity_status', true ) ),
			'identity_status' => $this->status( get_post_meta( $test->ID, 'identity_status', true ) ),
			'endotoxin_status' => $this->status( get_post_meta( $test->ID, 'endotoxin_status', true ) ),
			'heavy_metals_status' => $this->status( get_post_meta( $test->ID, 'heavy_metals_status', true ) ),
			'sterility_status' => $this->status( get_post_meta( $test->ID, 'sterility_status', true ) ),
			'coa_number' => (string) get_post_meta( $test->ID, 'coa_number', true ),
			'lab_report_url' => $this->http_url( get_post_meta( $test->ID, 'lab_report_url', true ) ),
			'detail_url' => $compound ? $this->test_url( $compound, $test ) : '',
			'public_notes' => (string) get_post_meta( $test->ID, 'public_notes', true ),
		);
	}

	/** Returns full public report data including validated attachments. @param \WP_Post $test Test. @param \WP_Post $compound Compound. @return array */
	public function report( $test, $compound ) {
		$model = $this->test_summary( $test, $compound );
		$model['purity_method'] = (string) get_post_meta( $test->ID, 'purity_method', true );
		$model['identity_method'] = (string) get_post_meta( $test->ID, 'identity_method', true );
		$model['endotoxin_result'] = (string) get_post_meta( $test->ID, 'endotoxin_result', true );
		$model['endotoxin_unit'] = (string) get_post_meta( $test->ID, 'endotoxin_unit', true );
		$model['heavy_metals_summary'] = (string) get_post_meta( $test->ID, 'heavy_metals_summary', true );
		$model['sterility_result'] = (string) get_post_meta( $test->ID, 'sterility_result', true );
		$model['certificate_version'] = (string) get_post_meta( $test->ID, 'certificate_version', true );
		$model['report_notes'] = (string) get_post_meta( $test->ID, 'report_notes', true );
		$model['pdf_attachment_id'] = absint( get_post_meta( $test->ID, 'coa_pdf_id', true ) );
		$model['pdf_url'] = $this->pdf_url( $model['pdf_attachment_id'] );
		$model['page_images'] = $this->gallery( get_post_meta( $test->ID, 'coa_page_images', true ), $compound->post_title );
		return $model;
	}

	/** Formats a stored lab value once for every frontend consumer. @return string */
	public function laboratory_name( $stored, $other = '' ) {
		$names = array( 'ils-labs' => 'ILS Labs', 'janoshik' => 'Janoshik Analytical', 'mz-biotech' => 'MZ Biolabs' );
		return isset( $names[ $stored ] ) ? $names[ $stored ] : ( 'other' === $stored && trim( (string) $other ) ? trim( (string) $other ) : ucwords( str_replace( '-', ' ', (string) $stored ) ) );
	}

	/** Returns semantic, non-interpretive status data. @param string $stored Stored value. @return array */
	public function status( $stored ) {
		$stored = sanitize_key( str_replace( '_', '-', (string) $stored ) );
		$labels = array( 'approved' => 'Approved', 'pass' => 'Pass', 'fail' => 'Fail', 'pending' => 'Pending', 'not-tested' => 'Not Tested', 'not-applicable' => 'Not Applicable', 'reported' => 'Reported' );
		$value = isset( $labels[ $stored ] ) ? $stored : '';
		return array( 'value' => $value, 'label' => $value ? $labels[ $value ] : 'Not Reported', 'class' => $value ? 'ps-coa-status--' . $value : 'ps-coa-status--empty', 'icon' => $value ?: 'empty', 'public' => true );
	}

	public function archive_url() { return home_url( user_trailingslashit( 'testing' ) ); }
	public function compound_url( $compound ) { return home_url( user_trailingslashit( 'testing/' . $compound->post_name ) ); }
	public function test_url( $compound, $test ) { return home_url( user_trailingslashit( 'testing/' . $compound->post_name . '/' . $this->batch_slug( $test ) ) ); }
	public function batch_slug( $test ) { $batch = sanitize_title( get_post_meta( $test->ID, 'batch_number', true ) ); return $batch ?: sanitize_title( $test->post_name ); }

	private function date_label( $value ) {
		$digits = preg_replace( '/\D/', '', (string) $value );
		if ( 8 !== strlen( $digits ) ) { return ''; }
		$time = strtotime( substr( $digits, 0, 4 ) . '-' . substr( $digits, 4, 2 ) . '-' . substr( $digits, 6, 2 ) . ' 00:00:00' );
		return $time ? wp_date( get_option( 'date_format' ), $time ) : '';
	}

	private function http_url( $url ) { $url = trim( (string) $url ); return $url && wp_http_validate_url( $url ) ? esc_url_raw( $url, array( 'http', 'https' ) ) : ''; }
	private function product_url( $id ) { $post = $id ? get_post( $id ) : null; return $post && 'product' === $post->post_type && 'publish' === $post->post_status ? get_permalink( $post ) : ''; }
	private function image_url( $id, $size ) { $post = $id ? get_post( $id ) : null; return $post && 'attachment' === $post->post_type && 'inherit' === $post->post_status && wp_attachment_is_image( $id ) ? (string) wp_get_attachment_image_url( $id, $size ) : ''; }
	private function image_srcset( $id, $size ) { return $this->image_url( $id, $size ) ? (string) wp_get_attachment_image_srcset( $id, $size ) : ''; }
	private function image_sizes( $id, $size ) { return $this->image_url( $id, $size ) ? (string) wp_get_attachment_image_sizes( $id, $size ) : ''; }
	private function image_alt( $id, $fallback ) { $alt = $id ? trim( (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) ) : ''; return $alt ?: $fallback; }
	private function pdf_url( $id ) { $post = $id ? get_post( $id ) : null; return $post && 'attachment' === $post->post_type && 'inherit' === $post->post_status && 'application/pdf' === get_post_mime_type( $id ) ? (string) wp_get_attachment_url( $id ) : ''; }
	private function gallery( $value, $fallback ) {
		$images = array(); $ids = is_array( $value ) ? array_map( 'absint', $value ) : array_filter( array_map( 'absint', explode( ',', (string) $value ) ) );
		if ( $ids ) { _prime_post_caches( $ids, false, false ); update_meta_cache( 'post', $ids ); }
		foreach ( $ids as $id ) {
			$id = absint( $id ); $thumbnail_url = $this->image_url( $id, 'medium_large' ); $full_url = $this->image_url( $id, 'full' );
			if ( $thumbnail_url && $full_url ) { $images[] = array( 'attachment_id' => $id, 'thumbnail_url' => $thumbnail_url, 'full_url' => $full_url, 'srcset' => $this->image_srcset( $id, 'medium_large' ), 'sizes' => $this->image_sizes( $id, 'medium_large' ), 'alt' => $this->image_alt( $id, sprintf( __( '%s certificate page', 'pepselect-coa-archive' ), $fallback ) ) ); }
		}
		return $images;
	}

	/** Sorts archive previews by test date and publish date descending. @return int */
	private function compare_by_test_date( $left, $right ) {
		$left_date = preg_replace( '/\D/', '', (string) get_post_meta( $left->ID, 'test_date', true ) );
		$right_date = preg_replace( '/\D/', '', (string) get_post_meta( $right->ID, 'test_date', true ) );
		$date = strcmp( $right_date, $left_date );
		return 0 !== $date ? $date : strcmp( $right->post_date_gmt, $left->post_date_gmt );
	}
}

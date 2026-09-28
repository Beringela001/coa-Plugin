<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Presentation safeguards: a stored outcome cannot supply an unstored method. */
final class Report_Evidence {
	/** Product URLs are supplied only after published-product resolution by ID. */
	public static function product_url( $compound_id, $resolved_url, $public_compound_ids ) {
		return 1 === count( $public_compound_ids ) && (int) $compound_id === (int) $public_compound_ids[0] ? $resolved_url : '';
	}
	public static function fentanyl( $status, $method = '', $specification = '', $result = '' ) {
		$performed = in_array( $status, array( 'pass', 'fail' ), true );
		return array(
			'result' => $performed && '' !== trim( (string) $result ) ? trim( (string) $result ) : ( 'pass' === $status ? 'Not detected' : ( 'fail' === $status ? 'Detected' : '' ) ),
			'method' => $performed ? trim( (string) $method ) : '',
			'specification' => $performed ? trim( (string) $specification ) : '',
		);
	}

	/** Candidates must already have passed the repository's public visibility rules. */
	public static function current_report( $compound_id, $reports ) {
		$current = array();
		foreach ( $reports as $report ) {
			if ( (int) $compound_id !== (int) ( $report['compound_id'] ?? 0 ) || empty( $report['is_current'] ) ) { continue; }
			// Count all explicit current records before validating to avoid hiding ambiguity.
			$current[] = $report;
		}
		if ( 1 !== count( $current ) ) { return null; }
		$report = $current[0];
		if ( 'approved' !== ( $report['coa_status'] ?? '' ) || 'complete' !== ( $report['workflow_stage'] ?? '' ) || empty( $report['detail_url'] ) ) { return null; }
		foreach ( $report['result_rows'] ?? array() as $row ) {
			if ( in_array( $row['status']['value'] ?? '', array( 'fail', 'failed' ), true ) ) { return null; }
		}
		return $report;
	}
}

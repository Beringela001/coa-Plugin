<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Shared, read-only workflow and calendar logic for administrative screens. */
final class COA_Admin_Workflow {
	/** Returns the WordPress site-local start of day. @param \DateTimeImmutable|null $today Override for deterministic tests. @return \DateTimeImmutable */
	public static function today( ?\DateTimeImmutable $today = null ) {
		$timezone = wp_timezone();
		return $today ? $today->setTimezone( $timezone )->setTime( 0, 0, 0 ) : current_datetime()->setTime( 0, 0, 0 );
	}

	/** Parses the ACF Ymd storage format and the supported ISO fallback. @param mixed $value Date value. @return \DateTimeImmutable|null */
	public static function parse_date( $value ) {
		$value = trim( (string) $value );
		$format = preg_match( '/^\d{8}$/', $value ) ? '!Ymd' : ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? '!Y-m-d' : '' );
		if ( ! $format ) { return null; }
		$date = \DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		return $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ? $date : null;
	}

	/**
	 * Classifies an expected date with the single timing policy used by admin UI.
	 *
	 * @param int                     $post_id COA Test ID.
	 * @param \DateTimeImmutable|null $today Site-local date override.
	 * @return array
	 */
	public static function timing( $post_id, ?\DateTimeImmutable $today = null ) {
		$today = self::today( $today );
		$stage = COA_Workflow::stage( $post_id );
		$outcome = COA_Workflow::outcome( $post_id );
		$date = self::parse_date( get_post_meta( $post_id, 'expected_coa_date', true ) );
		$result = array( 'status' => $date ? 'none' : 'no-date', 'date' => $date, 'days' => null, 'stage' => $stage, 'outcome' => $outcome );
		if ( ! $date || 'pending' !== $outcome || ! in_array( $stage, array( 'submitted-to-lab', 'in-testing' ), true ) ) { return $result; }
		$result['days'] = (int) $today->diff( $date )->format( '%r%a' );
		if ( $date < $today ) { $result['status'] = 'overdue'; }
		elseif ( $result['days'] <= 3 ) { $result['status'] = 'due-soon'; }
		return $result;
	}

	/** Returns consistent administrative stage labels without changing stored values. @param string $stage Normalized stage. @return string */
	public static function stage_label( $stage ) {
		$labels = array(
			'vendor-vetting' => __( 'Vendor Vetting', 'pepselect-coa-archive' ),
			'waiting-on-vendor' => __( 'Waiting on Vendor', 'pepselect-coa-archive' ),
			'submitted-to-lab' => __( 'Submitted to Laboratory', 'pepselect-coa-archive' ),
			'in-testing' => __( 'Verification in Progress', 'pepselect-coa-archive' ),
			'complete' => __( 'Completed', 'pepselect-coa-archive' ),
		);
		return isset( $labels[ $stage ] ) ? $labels[ $stage ] : $stage;
	}

	/** Returns a presentation-only tone suffix. @param string $stage Normalized stage. @return string */
	public static function stage_tone( $stage ) {
		$tones = array( 'vendor-vetting' => 'vendor', 'waiting-on-vendor' => 'waiting', 'submitted-to-lab' => 'submitted', 'in-testing' => 'testing', 'complete' => 'complete' );
		return isset( $tones[ $stage ] ) ? $tones[ $stage ] : 'neutral';
	}
}

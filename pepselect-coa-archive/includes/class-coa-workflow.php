<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Centralizes operational workflow stages and backward-compatible fallbacks. */
final class COA_Workflow {
	/** Returns stable workflow-stage choices. @return array */
	public static function stages() {
		return array(
			'vendor-vetting' => 'Vetting Vendor',
			'waiting-on-vendor' => 'Waiting on Vendor',
			'submitted-to-lab' => 'Submitted to Laboratory',
			'in-testing' => 'Verification in Progress',
			'complete' => 'Complete',
		);
	}

	/** Returns incoming operational stages. @return string[] */
	public static function incoming_stages() { return array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab', 'in-testing' ); }

	/** Normalizes retired operational stages without rewriting stored metadata. @param string $stage Stored stage. @return string */
	public static function normalize_stage( $stage ) {
		$stage = sanitize_key( (string) $stage );
		if ( 'sample-received' === $stage ) { return 'submitted-to-lab'; }
		if ( 'coa-pending' === $stage ) { return 'in-testing'; }
		return $stage;
	}

	/** Returns the effective stage without rewriting legacy records. @param int|\WP_Post $test Test. @return string */
	public static function stage( $test ) {
		$post = get_post( $test ); if ( ! $post ) { return ''; }
		$stored = self::normalize_stage( get_post_meta( $post->ID, 'workflow_stage', true ) );
		if ( isset( self::stages()[ $stored ] ) ) { return $stored; }
		$status = sanitize_key( (string) get_post_meta( $post->ID, 'coa_status', true ) );
		if ( in_array( $status, array( 'approved', 'failed', 'archived', 'superseded' ), true ) ) { return 'complete'; }
		if ( 'in-testing' === $status ) { return 'in-testing'; }
		return 'vendor-vetting';
	}

	/** Returns the final outcome while mapping beta-era operational statuses to pending. @param int|\WP_Post $test Test. @return string */
	public static function outcome( $test ) {
		$post = get_post( $test ); if ( ! $post ) { return ''; }
		$status = sanitize_key( (string) get_post_meta( $post->ID, 'coa_status', true ) );
		return in_array( $status, array( 'in-testing', 'vendor-vetting' ), true ) ? 'pending' : $status;
	}

	/** Returns whether a stage is public-incoming. @param string $stage Stage. @return bool */
	public static function is_incoming_stage( $stage ) { return in_array( $stage, self::incoming_stages(), true ); }

	/** Returns incoming sort priority; lower is more advanced. @param string $stage Stage. @return int */
	public static function priority( $stage ) {
		$order = array( 'in-testing', 'submitted-to-lab', 'waiting-on-vendor', 'vendor-vetting' );
		$position = array_search( $stage, $order, true );
		return false === $position ? 99 : $position;
	}
}

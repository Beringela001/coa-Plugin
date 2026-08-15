<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Runs idempotent, versioned plugin upgrades after runtime rewrites are registered. */
final class Upgrade {
	const VERSION_OPTION = 'pepselect_coa_archive_version';

	/** Flushes rewrite rules once per installed plugin version. @return void */
	public static function maybe_upgrade() {
		if ( PEPSELECT_COA_ARCHIVE_VERSION === get_option( self::VERSION_OPTION ) ) { return; }
		self::migrate_archive_catalog_copy();
		self::migrate_legacy_retatrutide_slug();
		( new Product_Matching( new Dependencies() ) )->backfill_existing_links();
		Archive_Cache::invalidate();
		flush_rewrite_rules( false );
		update_option( self::VERSION_OPTION, PEPSELECT_COA_ARCHIVE_VERSION, false );
	}

	/** Records activation after the activation transaction has flushed rules. @return void */
	public static function mark_current() { self::migrate_archive_catalog_copy(); self::migrate_legacy_retatrutide_slug(); ( new Product_Matching( new Dependencies() ) )->backfill_existing_links(); Archive_Cache::invalidate(); update_option( self::VERSION_OPTION, PEPSELECT_COA_ARCHIVE_VERSION, false ); }

	/** Replaces the one known numeric compound slug after verifying its identity. @return void */
	private static function migrate_legacy_retatrutide_slug() {
		$compound = get_page_by_path( '961', OBJECT, Post_Types::COMPOUND );
		if ( ! $compound || '961' !== $compound->post_name ) { return; }

		$display_name   = strtolower( trim( (string) get_post_meta( $compound->ID, 'display_name', true ) ) );
		$strength_value = (float) get_post_meta( $compound->ID, 'strength_value', true );
		$strength_unit  = strtolower( trim( (string) get_post_meta( $compound->ID, 'strength_unit', true ) ) );
		$valid_names    = array( 'retatrutide', 'retatrutide 10mg', 'retatrutide 10 mg' );
		if ( ! in_array( $display_name, $valid_names, true ) || 10.0 !== $strength_value || 'mg' !== $strength_unit ) { return; }

		$existing = get_page_by_path( 'retatrutide-10mg', OBJECT, Post_Types::COMPOUND );
		if ( $existing && (int) $existing->ID !== (int) $compound->ID ) { return; }

		wp_update_post( array( 'ID' => $compound->ID, 'post_name' => 'retatrutide-10mg' ) );
	}

	/** Replaces only untouched legacy archive defaults while preserving customized copy. @return void */
	private static function migrate_archive_catalog_copy() {
		$settings = get_option( Design_Settings::OPTION, array() );
		if ( ! is_array( $settings ) ) { return; }
		$replacements = array(
			'archive_title' => array(
				array( 'Testing & Documentation', 'Every batch. Every peptide. Independently verified.' ),
				'Every batch has a permanent address.',
			),
			'archive_intro' => array(
				array( 'Independent laboratory reports organized by compound and batch.', 'Pep Select doesn’t ask you to take our word for it. Third-party labs test every release — purity, mass, sterility — and we publish the certificates raw. No cropping, no marketing gloss, no missing pages. Search a compound, open a batch, read the same report our chemists do.' ),
				'Every compound we’ve released keeps its full record here: the raw third-party certificate, the batch it came from, and the date it was tested. We publish these exactly as the lab returns them, and we keep them up after a batch sells out. Search a compound, or enter the batch code from your vial, to read the same report our team reads.',
			),
			'search_placeholder_copy' => array(
				array( 'Search compounds...' ),
				'Search a compound or batch code — e.g. Retatrutide, RT30-0726-B',
			),
			'vendor_vetting_copy' => array(
				array( 'We are currently vetting vendors for this compound.' ),
				'We’re sourcing a new batch from a vetted manufacturer.',
			),
			'waiting_vendor_copy' => array(
				array( 'We are currently waiting on a new batch from our vendor.' ),
				'A new batch is on its way to us.',
			),
			'submitted_lab_copy' => array(
				array( 'Samples have been shipped to the testing laboratory.' ),
				'Samples are with the lab.',
			),
			'in_testing_copy' => array(
				array( 'Independent testing is underway.' ),
				'Testing is underway.',
			),
		);
		$changed = false;
		foreach ( $replacements as $key => $copy ) {
			if ( ! isset( $settings[ $key ] ) ) { continue; }
			$old_values = $copy[0];
			$new_value  = $copy[1];
			if ( in_array( $settings[ $key ], $old_values, true ) || '' === trim( (string) $settings[ $key ] ) ) {
				$settings[ $key ] = $new_value;
				$changed = true;
			}
		}
		if ( $changed ) { update_option( Design_Settings::OPTION, $settings, false ); Design_Settings::clear_cache(); }
	}
}

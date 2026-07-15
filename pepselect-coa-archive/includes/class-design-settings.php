<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Request-cached, sanitized design and public-copy settings. */
final class Design_Settings {
	const OPTION = 'pepselect_coa_design_settings';
	/** @var array|null */ private static $cache = null;

	/** Returns field definitions grouped for the Settings API. @return array */
	public static function fields() {
		$colors = array(
			'page_bg' => array( 'Page background', '#ffffff' ), 'surface' => array( 'Primary surface / card', '#ffffff' ), 'surface_muted' => array( 'Secondary / muted panel', '#f4f7f6' ),
			'text' => array( 'Primary text', '#1d2927' ), 'text_muted' => array( 'Secondary / muted text', '#60706d' ), 'border' => array( 'Border', '#d8e0de' ),
			'accent' => array( 'Primary accent', '#315d58' ), 'accent_word' => array( 'Accent word', '#315d58' ), 'success' => array( 'Success / check', '#28734d' ),
			'info' => array( 'Incoming / testing', '#247182' ), 'vendor' => array( 'Vendor vetting / waiting', '#596f73' ), 'warning' => array( 'Warning', '#8a641d' ), 'danger' => array( 'Failure', '#a33b3b' ), 'neutral_status' => array( 'Neutral / inactive', '#60706d' ),
			'document_bg' => array( 'Laboratory panel background', '#082a4f' ), 'document_surface' => array( 'Laboratory panel surface', '#0d365f' ), 'document_text' => array( 'Laboratory panel text', '#ffffff' ), 'document_muted' => array( 'Laboratory panel muted text', '#c5d4e2' ), 'document_border' => array( 'Laboratory panel border', '#31577b' ),
		);
		$fields = array();
		foreach ( $colors as $key => $data ) { $fields[ $key ] = array( 'section' => 'colors', 'label' => $data[0], 'type' => 'color', 'default' => $data[1] ); }
		$font_options = array( 'inherit' => 'Inherit site typography', 'system' => 'System Sans', 'arial' => 'Arial / Helvetica', 'georgia' => 'Georgia', 'times' => 'Times New Roman' );
		$weight_options = array( 'inherit' => 'Inherit', '400' => 'Regular (400)', '500' => 'Medium (500)', '600' => 'Semibold (600)', '700' => 'Bold (700)', '800' => 'Extra Bold (800)' );
		$fields['heading_font'] = array( 'section' => 'typography', 'label' => 'Heading font', 'type' => 'select', 'default' => 'inherit', 'options' => $font_options );
		$fields['body_font'] = array( 'section' => 'typography', 'label' => 'Body font', 'type' => 'select', 'default' => 'inherit', 'options' => $font_options );
		$fields['heading_weight'] = array( 'section' => 'typography', 'label' => 'Heading font weight', 'type' => 'select', 'default' => 'inherit', 'options' => $weight_options );
		$fields['body_weight'] = array( 'section' => 'typography', 'label' => 'Body font weight', 'type' => 'select', 'default' => 'inherit', 'options' => $weight_options );
		$fields['accent_style'] = array( 'section' => 'typography', 'label' => 'Accent-word style', 'type' => 'select', 'default' => 'normal', 'options' => array( 'normal' => 'Normal', 'italic' => 'Italic' ) );
		foreach ( array( 'card_radius' => 'Card border radius', 'panel_radius' => 'Panel / metric radius', 'image_radius' => 'Image / thumbnail radius', 'search_radius' => 'Search-field radius', 'search_button_radius' => 'Search-button radius', 'primary_button_radius' => 'Primary-button radius', 'secondary_button_radius' => 'Secondary-button radius' ) as $key => $label ) {
			$fields[ $key ] = array( 'section' => 'corners', 'label' => $label, 'type' => 'integer', 'default' => in_array( $key, array( 'card_radius', 'panel_radius', 'image_radius' ), true ) ? 12 : 8, 'min' => 0, 'max' => 40, 'suffix' => 'px' );
		}
		$fields['card_border_width'] = array( 'section' => 'corners', 'label' => 'Card border width', 'type' => 'integer', 'default' => 1, 'min' => 0, 'max' => 4, 'suffix' => 'px' );
		$fields['input_border_width'] = array( 'section' => 'corners', 'label' => 'Input border width', 'type' => 'integer', 'default' => 1, 'min' => 0, 'max' => 4, 'suffix' => 'px' );
		$button_defaults = array(
			'primary_button_bg' => array( 'Primary button background', '#315d58' ), 'primary_button_text' => array( 'Primary button text', '#ffffff' ), 'primary_button_border' => array( 'Primary button border', '#315d58' ), 'primary_button_hover_bg' => array( 'Primary button hover background', '#254b47' ), 'primary_button_hover_text' => array( 'Primary button hover text', '#ffffff' ), 'primary_button_hover_border' => array( 'Primary button hover border', '#254b47' ),
			'secondary_button_bg' => array( 'Secondary button background', '#ffffff' ), 'secondary_button_text' => array( 'Secondary button text', '#1d2927' ), 'secondary_button_border' => array( 'Secondary button border', '#d8e0de' ), 'secondary_button_hover_bg' => array( 'Secondary button hover background', '#ffffff' ), 'secondary_button_hover_text' => array( 'Secondary button hover text', '#315d58' ), 'secondary_button_hover_border' => array( 'Secondary button hover border', '#315d58' ),
			'search_button_bg' => array( 'Search button background', '#315d58' ), 'search_button_text' => array( 'Search button text', '#ffffff' ), 'search_button_border' => array( 'Search button border', '#315d58' ), 'search_button_hover_bg' => array( 'Search button hover background', '#254b47' ), 'search_button_hover_text' => array( 'Search button hover text', '#ffffff' ), 'search_button_hover_border' => array( 'Search button hover border', '#254b47' ),
			'search_input_bg' => array( 'Search input background', '#ffffff' ), 'search_input_text' => array( 'Search input text', '#1d2927' ), 'search_input_border' => array( 'Search input border', '#d8e0de' ), 'search_placeholder' => array( 'Search placeholder', '#60706d' ), 'search_focus_border' => array( 'Search focus border', '#315d58' ),
		);
		foreach ( $button_defaults as $key => $data ) { $fields[ $key ] = array( 'section' => 'buttons', 'label' => $data[0], 'type' => 'color', 'default' => $data[1] ); }
		foreach ( array( 'lightbox_overlay' => array( 'Overlay background', '#101616' ), 'lightbox_control_bg' => array( 'Control background', '#28302f' ), 'lightbox_control_text' => array( 'Control icon / text', '#ffffff' ), 'lightbox_control_border' => array( 'Control border', '#8b9694' ) ) as $key => $data ) { $fields[ $key ] = array( 'section' => 'lightbox', 'label' => $data[0], 'type' => 'color', 'default' => $data[1] ); }
		$fields['lightbox_opacity'] = array( 'section' => 'lightbox', 'label' => 'Overlay opacity', 'type' => 'decimal', 'default' => .94, 'min' => .5, 'max' => 1, 'step' => .01 );
		$fields['lightbox_control_radius'] = array( 'section' => 'lightbox', 'label' => 'Control border radius', 'type' => 'integer', 'default' => 24, 'min' => 0, 'max' => 40, 'suffix' => 'px' );
		$copy = array(
			'report_hero_copy' => array( 'Full-report introduction', 'This certificate documents the independent laboratory analysis for the exact vial shown here. Match the batch number, cap, and crimp with your vial before use in laboratory research.' ),
			'legacy_report_hero_copy' => array( 'Legacy full-report introduction', 'This legacy certificate documents the independent laboratory analysis for the published batch. A representative vial image is shown because an exact batch photo was not stored.' ),
			'archive_eyebrow' => array( 'Archive eyebrow', 'Pep Select Quality Archive' ), 'archive_title' => array( 'Archive title', 'Every batch. Every peptide. Independently verified.' ), 'archive_intro' => array( 'Archive introduction', 'Pep Select doesn’t ask you to take our word for it. Third-party labs test every release — purity, mass, sterility — and we publish the certificates raw. No cropping, no marketing gloss, no missing pages. Search a compound, open a batch, read the same report our chemists do.' ),
			'history_eyebrow' => array( 'Compound history eyebrow', 'Batch Vetting Record' ), 'history_suffix' => array( 'Compound history title suffix', 'Vetting History' ), 'latest_label' => array( 'Latest report label', 'Latest Report' ),
			'full_qc_label' => array( 'Full-QC assurance label', 'Full-QC Documented' ), 'full_qc_copy' => array( 'Full-QC assurance copy', 'Independent report published.' ), 'neutral_label' => array( 'Neutral assurance label', 'Independent Report Published' ),
			'incoming_heading' => array( 'Incoming reports heading', 'Incoming Reports' ), 'previous_heading' => array( 'Previous reports heading', 'Previous Reports' ),
			'vendor_vetting_label' => array( 'Vetting-vendor label', 'Vetting Vendor' ), 'vendor_vetting_copy' => array( 'Vetting-vendor copy', 'We are currently vetting vendors for this compound.' ),
			'waiting_vendor_label' => array( 'Waiting-vendor label', 'Waiting on Vendor' ), 'waiting_vendor_copy' => array( 'Waiting-vendor copy', 'We are currently waiting on a new batch from our vendor.' ),
			'submitted_lab_label' => array( 'Submitted-to-lab label', 'Submitted to Laboratory' ), 'submitted_lab_copy' => array( 'Submitted-to-lab copy', 'Samples have been shipped to the testing laboratory.' ),
			'in_testing_label' => array( 'In-testing label', 'Verification in Progress' ), 'in_testing_copy' => array( 'In-testing copy', 'Independent testing is underway.' ),
			'complete_stage_label' => array( 'Complete-stage label', 'Independently Tested' ), 'complete_stage_copy' => array( 'Complete-stage copy', 'Published batch report available.' ),
			'failed_report_label' => array( 'Failed-report label', 'Did Not Pass Release Review' ), 'failed_report_copy' => array( 'Failed-report copy', 'This batch was not released for sale.' ),
			'expected_date_label' => array( 'Expected-date label', 'Expected report' ), 'view_pending_lab' => array( 'Pending-lab action', 'View Laboratory Status' ), 'view_failed_report' => array( 'Failed-report action', 'View Full Report' ),
			'view_history' => array( 'View-history action', 'View all reports' ), 'view_report' => array( 'View-full-report action', 'View Full Report' ), 'search_placeholder_copy' => array( 'Search placeholder', 'Search a compound or batch code — e.g. Retatrutide, RT30-0726-B' ), 'search_button_copy' => array( 'Search button label', 'Search' ),
		);
		foreach ( $copy as $key => $data ) { $fields[ $key ] = array( 'section' => 'copy', 'label' => $data[0], 'type' => 'text', 'default' => $data[1] ); }
		$fields['show_failed_only_compounds'] = array( 'section' => 'behavior', 'label' => 'Show failed-only compounds in archive', 'type' => 'boolean', 'default' => 0 );
		return $fields;
	}

	/** Returns normalized settings, reading the option once per request. @return array */
	public static function get() {
		if ( null !== self::$cache ) { return self::$cache; }
		$stored = get_option( self::OPTION, array() );
		self::$cache = self::sanitize( is_array( $stored ) ? $stored : array() );
		return self::$cache;
	}

	/** Returns one public-copy value with an empty-value fallback. @param string $key Key. @return string */
	public static function copy( $key ) {
		$settings = self::get(); $fields = self::fields();
		return isset( $settings[ $key ] ) && '' !== trim( $settings[ $key ] ) ? $settings[ $key ] : ( isset( $fields[ $key ] ) ? $fields[ $key ]['default'] : '' );
	}

	/** Sanitizes the complete prefixed option. @param mixed $input Submitted value. @return array */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array(); $output = array();
		foreach ( self::fields() as $key => $field ) {
			$value = array_key_exists( $key, $input ) ? $input[ $key ] : $field['default'];
			if ( 'color' === $field['type'] ) { $output[ $key ] = sanitize_hex_color( $value ) ?: $field['default']; }
			elseif ( 'select' === $field['type'] ) { $value = sanitize_key( $value ); $output[ $key ] = isset( $field['options'][ $value ] ) ? $value : $field['default']; }
			elseif ( 'integer' === $field['type'] ) { $output[ $key ] = max( $field['min'], min( $field['max'], absint( $value ) ) ); }
			elseif ( 'decimal' === $field['type'] ) { $output[ $key ] = max( $field['min'], min( $field['max'], round( (float) $value, 2 ) ) ); }
			elseif ( 'boolean' === $field['type'] ) { $output[ $key ] = empty( $value ) ? 0 : 1; }
			else { $clean = sanitize_text_field( $value ); $output[ $key ] = '' === trim( $clean ) ? $field['default'] : $clean; }
		}
		self::$cache = $output;
		return $output;
	}

	/** Clears only the in-request cache. @return void */
	public static function clear_cache() { self::$cache = null; }

	/** Returns scoped, normalized CSS variables for wp_add_inline_style(). @return string */
	public static function inline_css() {
		$s = self::get();
		$font = array( 'inherit' => 'inherit', 'system' => 'system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif', 'arial' => 'Arial,Helvetica,sans-serif', 'georgia' => 'Georgia,serif', 'times' => '"Times New Roman",Times,serif' );
		$vars = array(
			'page-bg' => $s['page_bg'], 'surface' => $s['surface'], 'surface-muted' => $s['surface_muted'], 'text' => $s['text'], 'text-muted' => $s['text_muted'], 'border' => $s['border'], 'accent' => $s['accent'], 'accent-word' => $s['accent_word'], 'success' => $s['success'], 'info' => $s['info'], 'vendor' => $s['vendor'], 'warning' => $s['warning'], 'danger' => $s['danger'], 'neutral-status' => $s['neutral_status'], 'document-bg' => $s['document_bg'], 'document-surface' => $s['document_surface'], 'document-text' => $s['document_text'], 'document-muted' => $s['document_muted'], 'document-border' => $s['document_border'],
			'heading-font' => $font[ $s['heading_font'] ], 'body-font' => $font[ $s['body_font'] ], 'heading-weight' => 'inherit' === $s['heading_weight'] ? 'inherit' : $s['heading_weight'], 'body-weight' => 'inherit' === $s['body_weight'] ? 'inherit' : $s['body_weight'], 'accent-style' => $s['accent_style'],
			'card-radius' => $s['card_radius'] . 'px', 'panel-radius' => $s['panel_radius'] . 'px', 'image-radius' => $s['image_radius'] . 'px', 'search-radius' => $s['search_radius'] . 'px', 'search-button-radius' => $s['search_button_radius'] . 'px', 'primary-button-radius' => $s['primary_button_radius'] . 'px', 'secondary-button-radius' => $s['secondary_button_radius'] . 'px', 'card-border-width' => $s['card_border_width'] . 'px', 'input-border-width' => $s['input_border_width'] . 'px',
			'primary-button-bg' => $s['primary_button_bg'], 'primary-button-text' => $s['primary_button_text'], 'primary-button-border' => $s['primary_button_border'], 'primary-button-hover-bg' => $s['primary_button_hover_bg'], 'primary-button-hover-text' => $s['primary_button_hover_text'], 'primary-button-hover-border' => $s['primary_button_hover_border'],
			'secondary-button-bg' => $s['secondary_button_bg'], 'secondary-button-text' => $s['secondary_button_text'], 'secondary-button-border' => $s['secondary_button_border'], 'secondary-button-hover-bg' => $s['secondary_button_hover_bg'], 'secondary-button-hover-text' => $s['secondary_button_hover_text'], 'secondary-button-hover-border' => $s['secondary_button_hover_border'],
			'search-button-bg' => $s['search_button_bg'], 'search-button-text' => $s['search_button_text'], 'search-button-border' => $s['search_button_border'], 'search-button-hover-bg' => $s['search_button_hover_bg'], 'search-button-hover-text' => $s['search_button_hover_text'], 'search-button-hover-border' => $s['search_button_hover_border'], 'search-input-bg' => $s['search_input_bg'], 'search-input-text' => $s['search_input_text'], 'search-input-border' => $s['search_input_border'], 'search-placeholder' => $s['search_placeholder'], 'search-focus-border' => $s['search_focus_border'],
			'lightbox-bg' => self::hex_to_rgba( $s['lightbox_overlay'], $s['lightbox_opacity'] ), 'lightbox-control-bg' => $s['lightbox_control_bg'], 'lightbox-control-text' => $s['lightbox_control_text'], 'lightbox-control-border' => $s['lightbox_control_border'], 'lightbox-control-radius' => $s['lightbox_control_radius'] . 'px',
		);
		$declarations = array(); foreach ( $vars as $name => $value ) { $declarations[] = '--ps-coa-' . $name . ':' . $value; }
		return '.ps-coa-app{' . implode( ';', $declarations ) . '}';
	}

	private static function hex_to_rgba( $hex, $opacity ) {
		$hex = ltrim( $hex, '#' );
		return sprintf( 'rgba(%d,%d,%d,%.2F)', hexdec( substr( $hex, 0, 2 ) ), hexdec( substr( $hex, 2, 2 ) ), hexdec( substr( $hex, 4, 2 ) ), $opacity );
	}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Shared design service. No post, inventory or workflow writes. */
final class Design_Editor {
	public static function schema() {
		$fields = Design_Settings::fields();
		$inactive = array( 'report_hero_copy', 'legacy_report_hero_copy', 'history_suffix', 'show_failed_only_compounds', 'view_pending_lab' );
		foreach ( $fields as $key => &$field ) {
			$field['readonly'] = in_array( $key, $inactive, true );
			$field['help'] = $field['readonly'] ? 'Retained for compatibility. The current public templates do not use this setting.' : 'Shared COA presentation setting. Preview the relevant page before saving.';
			if ( str_starts_with( $key, 'product_carousel_' ) ) { $field['help'] = 'Product-page COA carousel copy, not a stock-status pill. This component is outside the report preview.'; }
			if ( 'copy' === $field['section'] ) { $field['help'] = $field['readonly'] ? $field['help'] : 'Public COA copy. Blank text uses the default. Batch-specific notes take priority over standard descriptions.'; }
			if ( str_starts_with( $key, 'product_carousel_' ) ) { $field['help'] = 'Product-page COA carousel copy, not a stock-status pill. This component is outside the report preview.'; }
			if ( 'report_style' === $field['section'] ) { $field['help'] = 'Changes the actual report status panel. Preview an approved report for the passed-panel colors.'; }
			if ( 'colors' === $field['section'] ) { $field['help'] = 'Shared archive, history and report components that use this color. Report status-panel colors have dedicated controls; not every decorative detail follows the shared palette.'; }
			if ( 'corners' === $field['section'] ) { $field['help'] = 'Shared cards and controls using this radius or width. The report hero and status panel retain their compact fixed corners.'; }
			if ( 'lightbox' === $field['section'] ) { $field['help'] = 'Open Certificate pages in the report preview, then select a page to view these fullscreen viewer controls.'; }
			if ( preg_match( '/^(vendor_vetting|waiting_vendor|submitted_lab|in_testing)_/', $key ) ) {
				$field['label'] = str_replace( array( 'label', 'copy' ), array( 'heading', 'description' ), $field['label'] );
				$field['help'] = 'Heading or description for this pending stage on COA reports and archive cards. Does not change product stock pills or the operational stage.';
			}
		}
		unset( $field );
		$fields['show_failed_only_compounds']['help'] = 'Inactive: failed-only compounds always remain visible in the archive. This transparency rule is not a design setting.';
		$fields['report_passed_heading']['help'] = 'Main heading on approved COA reports. Does not change the recorded test outcome.';
		$fields['report_failed_heading']['help'] = 'Main heading on failed COA reports. Does not change the recorded test outcome.';
		$fields['report_passed_copy']['help'] = 'Standard approved report description. Important batch notes still appear below the hero, with a link replacing this description.';
		return $fields;
	}

	public static function revision() { return hash( 'sha256', wp_json_encode( Design_Settings::get() ) ); }

	public static function read() {
		return array( 'schema_version' => 1, 'fields' => self::schema(), 'settings' => Design_Settings::get(), 'revision' => self::revision() );
	}

	/** Reject malformed or unknown fields rather than accidentally resetting unrelated values. */
	public static function validate_patch( $patch ) {
		if ( ! is_array( $patch ) ) { return new \WP_Error( 'invalid_settings', 'Settings must be an object.', array( 'status' => 400 ) ); }
		$schema = self::schema();
		foreach ( $patch as $key => $value ) {
			if ( ! isset( $schema[ $key ] ) || ! is_scalar( $value ) || strlen( (string) $value ) > 5000 ) { return new \WP_Error( 'invalid_setting', 'Unknown or invalid setting: ' . sanitize_key( $key ), array( 'status' => 400 ) ); }
			if ( $schema[ $key ]['readonly'] ) { return new \WP_Error( 'inactive_setting', 'This setting is retained but inactive: ' . $key, array( 'status' => 400 ) ); }
		}
		return $patch;
	}

	public static function save( $patch, $revision ) {
		$patch = self::validate_patch( $patch );
		if ( is_wp_error( $patch ) ) { return $patch; }
		Design_Settings::clear_cache();
		if ( ! is_string( $revision ) || ! hash_equals( self::revision(), $revision ) ) { return new \WP_Error( 'settings_changed', 'Settings changed in another session. Reload before saving.', array( 'status' => 409 ) ); }
		$settings = Design_Settings::sanitize( array_replace( Design_Settings::get(), $patch ) );
		update_option( Design_Settings::OPTION, $settings, false );
		Design_Settings::clear_cache();
		return self::read();
	}

	private static function router() {
		$visibility = new Frontend_Visibility();
		return new Frontend_Router( new Frontend_Query(), new Compound_Repository( $visibility ), new COA_Test_Repository( $visibility ), new Frontend_View_Model() );
	}

	/** Lists public records only; preview never creates sample records. */
	public static function choices() {
		$choices = array( array( 'view' => 'archive', 'id' => 0, 'label' => 'Testing archive' ) );
		$router = self::router(); $seen = array();
		foreach ( get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => 'publish', 'posts_per_page' => -1, 'orderby' => 'date', 'order' => 'DESC' ) ) as $post ) {
			$compound_id = absint( get_post_meta( $post->ID, 'compound_id', true ) );
			$context = $router->build_report_by_ids( $compound_id, $post->ID );
			if ( ! $context ) { continue; }
			$name = $context['compound']['display_name']; $test = $context['test'];
			$choices[] = array( 'view' => 'report', 'id' => $post->ID, 'label' => $name . ' — ' . ( $test['batch_number'] ?: 'Upcoming batch' ) . ' — ' . $test['workflow_stage_label'] . ' / ' . $test['coa_status'] );
			if ( ! isset( $seen[ $compound_id ] ) ) { $choices[] = array( 'view' => 'compound', 'id' => $compound_id, 'label' => $name . ' — batch history' ); $seen[ $compound_id ] = true; }
		}
		return $choices;
	}

	public static function preview( $patch, $view, $id ) {
		$patch = self::validate_patch( $patch );
		if ( is_wp_error( $patch ) ) { return $patch; }
		if ( ! in_array( $view, array( 'report', 'archive', 'compound' ), true ) ) { return new \WP_Error( 'invalid_view', 'Choose a public COA page.', array( 'status' => 400 ) ); }
		return Design_Settings::with_preview( $patch, static function () use ( $view, $id ) {
			$router = self::router();
			if ( 'archive' === $view ) { $context = $router->build_archive(); }
			elseif ( 'compound' === $view ) { $context = $router->build_compound( '', absint( $id ) ); }
			else { $context = $router->build_report_by_ids( absint( get_post_meta( absint( $id ), 'compound_id', true ) ), absint( $id ) ); }
			if ( ! $context ) { return new \WP_Error( 'preview_not_found', 'This public COA page is unavailable.', array( 'status' => 404 ) ); }
			$ps_context = $context; $ps_embedded = true;
			ob_start();
			try { include pepselect_coa_template_path( $context['template'] ); $html = ob_get_contents(); } finally { ob_end_clean(); }
			$css = plugins_url( 'assets/css/pepselect-coa-frontend.css', PEPSELECT_COA_ARCHIVE_FILE );
			$scripts = '';
			foreach ( array( 'pepselect-coa-lightbox', 'pepselect-coa-history-carousel', 'coa-design-preview' ) as $script ) { $scripts .= '<script src="' . esc_url( plugins_url( 'assets/js/' . $script . '.js', PEPSELECT_COA_ARCHIVE_FILE ) . '?ver=' . PEPSELECT_COA_ARCHIVE_VERSION ) . '"></script>'; }
			$document = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><meta name="robots" content="noindex,nofollow"><link rel="stylesheet" href="' . esc_url( $css . '?ver=' . PEPSELECT_COA_ARCHIVE_VERSION ) . '"><style>body{margin:0;font-family:system-ui,sans-serif}*{box-sizing:border-box}.screen-reader-text{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%)}</style><style>' . Design_Settings::inline_css() . '</style></head><body>' . $html . $scripts . '</body></html>';
			return array( 'html' => $document, 'canonical' => $context['canonical'], 'saved' => false );
		} );
	}
}

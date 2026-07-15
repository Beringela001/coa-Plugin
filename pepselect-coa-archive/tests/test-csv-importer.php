<?php
/** COA-3.2 CSV importer integration scaffolding. */
class PepSelect_COA_Archive_CSV_Importer_Test extends WP_UnitTestCase {
	public function test_direct_report_field_is_stable_and_verification_fields_remain() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-fields.php' );
		$this->assertStringContainsString( "'lab_report_url', 'Public Lab Report URL', 'url'", $source );
		$this->assertStringContainsString( 'field_ps_coa_test_lab_report_url', file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-validation.php' ) );
		foreach ( array( 'coa_number', 'verification_code', 'lab_verification_url' ) as $name ) { $this->assertStringContainsString( "'" . $name . "'", $source ); }
	}

	public function test_importer_is_scoped_to_coa_test_edit_screens() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-importer.php' );
		$this->assertStringContainsString( "Post_Types::COA_TEST !== \$screen->post_type", $source );
		$this->assertStringContainsString( "array( 'post.php', 'post-new.php' )", $source );
		$this->assertStringContainsString( "if ( 'edit.php' === \$hook )", $source );
		$this->assertStringContainsString( "current_user_can( 'edit_ps_coas' )", $source );
	}

	public function test_importer_is_client_side_and_does_not_save_or_publish() {
		$source = file_get_contents( dirname( __DIR__ ) . '/assets/js/coa-test-importer.js' );
		$this->assertStringContainsString( 'FileReader', $source );
		$this->assertStringNotContainsString( 'wp_insert_post', $source );
		$this->assertStringNotContainsString( 'wp_update_post', $source );
		$this->assertStringNotContainsString( 'fetch(', $source );
		$this->assertStringNotContainsString( '$.ajax', $source );
	}

	public function test_parser_functions_and_manual_media_exclusions_are_exposed() {
		$source = file_get_contents( dirname( __DIR__ ) . '/assets/js/coa-test-importer.js' );
		foreach ( array( 'parseCsv', 'normalizeDate', 'normalizeBoolean', 'normalizeStatus', 'normalizeLab', 'normalizeFentanylResult', 'matchCompound', 'previewText', 'applyPreview', 'clearImportedValues' ) as $function ) { $this->assertStringContainsString( $function, $source ); }
		$this->assertStringContainsString( "'coa_pdf_id', 'coa_page_images', 'batch_vial_photo', 'batch_identity_photos'", $source );
		$this->assertStringContainsString( "'fentanyl_status'", $source );
		$this->assertStringContainsString( 'fentanyl_notes', file_get_contents( dirname( __DIR__ ) . '/includes/class-coa-test-importer.php' ) );
	}
}

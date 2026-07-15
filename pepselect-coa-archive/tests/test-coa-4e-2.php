<?php
/** COA-4E.2 acceptance coverage for the exact Full-QC bar and Fentanyl contract. */
class PepSelect_COA_Archive_COA_4E_2_Test extends WP_UnitTestCase {
	public function test_01_full_qc_bar_has_exact_copy_and_two_tiers() {
		$partial = file_get_contents( dirname( __DIR__ ) . '/templates/partials/full-qc-status-strip.php' );
		$model = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' );
		$this->assertStringContainsString( 'Full-QC Testing Passed', $model );
		$this->assertStringContainsString( 'All reported tests met the laboratory specifications listed below.', $model );
		$this->assertStringContainsString( 'ps-coa-qc-strip__header', $partial );
		$this->assertStringContainsString( 'ps-coa-qc-strip__categories', $partial );
	}

	public function test_02_bar_cells_are_centered_with_icon_above_copy() {
		$css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' );
		$this->assertStringContainsString( 'flex-direction: column', $css );
		$this->assertStringContainsString( 'text-align: center', $css );
		$this->assertStringContainsString( 'border-left: 1px solid #dcebe4', $css );
	}

	public function test_03_fentanyl_form_uses_only_the_three_requested_states() {
		$this->assertSame( array( 'pass' => 'Pass', 'fail' => 'Fail', 'not-tested' => 'Not Tested' ), PepSelect\COAArchive\COA_Test_Fields::fentanyl_choices() );
	}

	public function test_04_fentanyl_automation_uses_exact_values() {
		$script = file_get_contents( dirname( __DIR__ ) . '/assets/js/pepselect-coa-test-form.js' );
		foreach ( array( 'Immunoassay, 50 ng/mL cutoff', 'Not detected', 'Detected', 'applyFentanyl' ) as $expected ) { $this->assertStringContainsString( $expected, $script ); }
	}

	public function test_05_bar_remains_between_metrics_and_lab_table() {
		$template = file_get_contents( dirname( __DIR__ ) . '/templates/single-coa-report.php' );
		$metrics = strpos( $template, 'partials/report-summary-metrics.php' );
		$bar = strpos( $template, 'partials/full-qc-status-strip.php' );
		$table = strpos( $template, 'ps-coa-laboratory-data' );
		$this->assertGreaterThan( $metrics, $bar );
		$this->assertGreaterThan( $bar, $table );
	}
}

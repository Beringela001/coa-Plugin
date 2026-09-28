<?php
define( 'ABSPATH', __DIR__ );
function __( $text, $domain = '' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' ); }
function esc_url( $text ) { return esc_html( $text ); }
function esc_html_e( $text, $domain = '' ) { echo esc_html( $text ); }
function esc_attr_e( $text, $domain = '' ) { echo esc_html( $text ); }
function render_path( $ps_context ) {
	$compound = $ps_context['compound']; $test = $ps_context['test'];
	ob_start(); include dirname( __DIR__ ) . '/templates/partials/report-current-path.php'; return ob_get_clean();
}
function check_render( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
$past = array( 'test_id' => 1, 'coa_status' => 'approved', 'is_current' => false, 'public_status_label' => 'Approved' );
$current = array( 'test_id' => 2, 'batch_number' => 'CURRENT', 'detail_url' => '/testing/exact/current/' );
$context = array( 'compound' => array( 'url' => '/testing/exact/' ), 'test' => $past, 'current_report' => $current, 'current_product_url' => '/product/exact/' );
$html = render_path( $context );
check_render( str_contains( $html, 'Historical batch record' ), 'Past status hidden.' );
check_render( str_contains( $html, 'href="/testing/exact/current/"' ) && str_contains( $html, 'href="/product/exact/"' ), 'Verified destination missing.' );
check_render( ! str_contains( $html, 'in stock' ), 'Availability invented.' );
$context['test']['test_id'] = 2; $context['test']['is_current'] = true;
$html = render_path( $context );
check_render( str_contains( $html, 'Current documented batch' ), 'Current state hidden.' );
check_render( ! str_contains( $html, 'data-coa-action="current_report"' ), 'Current report links to itself.' );
$context['current_report'] = null; $context['current_product_url'] = '';
$html = render_path( $context );
check_render( str_contains( $html, 'Check batch history' ) && ! str_contains( $html, '/product/' ), 'Unverified mapping lacks safe fallback.' );
$context['current_report'] = $current; $context['current_report']['batch_number'] = '<script>alert(1)</script>';
$context['test'] = $past;
check_render( ! str_contains( render_path( $context ), '<script>' ), 'Batch number not escaped.' );
echo "Report navigation rendering contracts: OK\n";

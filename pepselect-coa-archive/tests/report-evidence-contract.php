<?php
define( 'ABSPATH', __DIR__ );
require dirname( __DIR__ ) . '/includes/class-report-evidence.php';
use PepSelect\COAArchive\Report_Evidence;
function check( $condition, $message ) { if ( ! $condition ) { throw new RuntimeException( $message ); } }
foreach ( array( 'pass', 'fail', 'not-tested', 'pending', '', 'unknown' ) as $status ) {
	$evidence = Report_Evidence::fentanyl( $status );
	check( '' === $evidence['method'] && '' === $evidence['specification'], 'A status invented a laboratory method/cutoff.' );
	if ( ! in_array( $status, array( 'pass', 'fail' ), true ) ) { check( '' === $evidence['result'], 'Missing/unperformed test acquired a result.' ); }
}
check( 'Not detected' === Report_Evidence::fentanyl( 'pass' )['result'], 'Saved passing outcome lost.' );
check( 'Detected' === Report_Evidence::fentanyl( 'fail' )['result'], 'Saved failure lost.' );
$report = array( 'compound_id' => 20, 'is_current' => true, 'coa_status' => 'approved', 'workflow_stage' => 'complete', 'detail_url' => '/testing/exact/current/', 'result_rows' => array() );
check( $report === Report_Evidence::current_report( 20, array( $report ) ), 'Unique current mapping failed.' );
check( null === Report_Evidence::current_report( 21, array( $report ) ), 'Cross-compound link leaked.' );
check( null === Report_Evidence::current_report( 20, array( $report, $report ) ), 'Ambiguous current mapping selected a record.' );
foreach ( array( array( 'is_current', false ), array( 'coa_status', 'failed' ), array( 'workflow_stage', 'in-testing' ), array( 'detail_url', '' ) ) as $change ) {
	$invalid = $report; $invalid[ $change[0] ] = $change[1];
	check( null === Report_Evidence::current_report( 20, array( $invalid ) ), 'Invalid current candidate accepted.' );
}
$failed = $report; $failed['result_rows'][] = array( 'status' => array( 'value' => 'fail' ) );
check( null === Report_Evidence::current_report( 20, array( $failed ) ), 'Failed category accepted as current destination.' );
check( '/product/exact/' === Report_Evidence::product_url( 20, '/product/exact/', array( 20 ) ), 'Exact Product ID mapping lost.' );
check( '' === Report_Evidence::product_url( 20, '/product/exact/', array( 20, 21 ) ), 'Duplicate product mapping leaked.' );
check( '' === Report_Evidence::product_url( 20, '/product/exact/', array( 21 ) ), 'Another compound product leaked.' );
check( '' === Report_Evidence::product_url( 20, '/product/exact/', array() ), 'Missing product mapping leaked.' );
check( '' === Report_Evidence::product_url( 20, '', array( 20 ) ), 'Unpublished product acquired a link.' );
echo "Report evidence and exact-current navigation contracts: OK\n";

<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/* Show only evidence the laboratory actually reports; no wall of dashes. */
$ps_coa_evidence = static function ( $row ) {
	$items = array();
	foreach ( array( 'method' => __( 'Method', 'pepselect-coa-archive' ), 'specification' => __( 'Specification', 'pepselect-coa-archive' ), 'result' => __( 'Result', 'pepselect-coa-archive' ) ) as $key => $label ) {
		$value = isset( $row[ $key ] ) ? trim( (string) $row[ $key ] ) : '';
		if ( '' !== $value ) { $items[] = array( 'label' => $label, 'value' => $value ); }
	}
	return $items;
};
?>
<div class="ps-coa-results-table-wrap">
	<table class="ps-coa-results-table ps-coa-results-table--evidence">
		<colgroup><col class="ps-coa-results-table__test"><col class="ps-coa-results-table__evidence"><col class="ps-coa-results-table__status"></colgroup>
		<thead><tr><th scope="col"><?php esc_html_e( 'Test', 'pepselect-coa-archive' ); ?></th><th scope="col"><?php esc_html_e( 'Laboratory evidence', 'pepselect-coa-archive' ); ?></th><th scope="col"><?php esc_html_e( 'Status', 'pepselect-coa-archive' ); ?></th></tr></thead>
		<tbody><?php foreach ( $test['result_rows'] as $row ) : $items = $ps_coa_evidence( $row ); ?><tr><th scope="row"><?php echo esc_html( $row['label'] ); ?></th><td class="ps-coa-results-table__evidence"><?php if ( $items ) : ?><dl><?php foreach ( $items as $item ) : ?><div><dt><?php echo esc_html( $item['label'] ); ?></dt><dd><?php echo esc_html( $item['value'] ); ?></dd></div><?php endforeach; ?></dl><?php else : ?><span><?php esc_html_e( 'Status reported by the laboratory', 'pepselect-coa-archive' ); ?></span><?php endif; ?></td><td><?php $status = $row['status']; include pepselect_coa_template_path( 'partials/status-indicator.php' ); ?></td></tr><?php endforeach; ?></tbody>
	</table>
</div>
<div class="ps-coa-results-cards"><?php foreach ( $test['result_rows'] as $row ) : $items = $ps_coa_evidence( $row ); ?><article><h3><?php echo esc_html( $row['label'] ); ?></h3><dl><?php foreach ( $items as $item ) : ?><div><dt><?php echo esc_html( $item['label'] ); ?></dt><dd><?php echo esc_html( $item['value'] ); ?></dd></div><?php endforeach; ?><div><dt><?php esc_html_e( 'Status', 'pepselect-coa-archive' ); ?></dt><dd><?php $status = $row['status']; include pepselect_coa_template_path( 'partials/status-indicator.php' ); ?></dd></div></dl></article><?php endforeach; ?></div>

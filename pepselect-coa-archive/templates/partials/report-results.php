<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<div class="ps-coa-results-panel">
	<?php
	$results = array(
		array( 'label' => __( 'Identity', 'pepselect-coa-archive' ), 'status' => $test['identity_status'], 'detail' => $test['identity_method'] ),
		array( 'label' => __( 'Heavy metals', 'pepselect-coa-archive' ), 'status' => $test['heavy_metals_status'], 'detail' => $test['heavy_metals_summary'] ),
		array( 'label' => __( 'Sterility', 'pepselect-coa-archive' ), 'status' => $test['sterility_status'], 'detail' => $test['sterility_result'] ),
		array( 'label' => __( 'Endotoxin', 'pepselect-coa-archive' ), 'status' => $test['endotoxin_status'], 'detail' => trim( $test['endotoxin_result'] . ' ' . $test['endotoxin_unit'] ) ),
	);
	if ( 'pending' === $test['coa_status'] && ( $test['purity_status']['value'] || '' !== $test['purity_percentage_display'] ) ) { array_unshift( $results, array( 'label' => __( 'Purity', 'pepselect-coa-archive' ), 'status' => $test['purity_status'], 'detail' => trim( $test['purity_percentage_display'] . ( '' !== $test['purity_percentage_display'] ? '%' : '' ) . ' ' . $test['purity_method'] ) ) ); }
	foreach ( $results as $result ) : if ( 'pending' === $test['coa_status'] && empty( $result['status']['value'] ) ) { continue; } ?>
		<article class="<?php echo ! empty( $result['status']['success'] ) ? 'ps-coa-result--success' : ''; ?>"><div><h3><?php echo esc_html( $result['label'] ); ?></h3><?php $status = $result['status']; include pepselect_coa_template_path( 'partials/status-indicator.php' ); ?></div><?php if ( $result['detail'] ) : ?><p><?php echo esc_html( $result['detail'] ); ?></p><?php endif; ?></article>
	<?php endforeach; ?>
</div>

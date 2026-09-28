<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $failed = 'failed' === $report['coa_status'] || (bool) array_filter( $report['qc_strip_rows'], static function ( $row ) { return 'fail' === $row['status']['value']; } ); ?>
<div class="ps-coa-history-qc-band<?php echo $failed ? ' ps-coa-history-qc-band--failed' : ( $report['all_reported_successful'] ? '' : ' ps-coa-history-qc-band--neutral' ); ?>">
	<div class="ps-coa-history-qc-band__message"><span aria-hidden="true"><?php echo $failed ? '!' : ( $report['all_reported_successful'] ? '✓' : 'i' ); ?></span><div><strong><?php echo esc_html( $report['history_qc_title'] ); ?></strong><small><?php echo esc_html( $report['history_qc_summary'] ); ?></small></div></div>
	<?php if ( $report['history_claims'] ) : ?><ul><?php foreach ( $report['history_claims'] as $claim ) : ?><li><span aria-hidden="true">✓</span><?php echo esc_html( $claim ); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>

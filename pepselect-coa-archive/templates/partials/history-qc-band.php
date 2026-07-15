<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $failed = 'failed' === $report['coa_status'] || ! $report['all_reported_successful']; ?>
<div class="ps-coa-history-qc-band<?php echo $failed ? ' ps-coa-history-qc-band--failed' : ''; ?>">
	<div class="ps-coa-history-qc-band__message"><span aria-hidden="true"><?php echo $failed ? '!' : '✓'; ?></span><div><strong><?php echo esc_html( $report['history_qc_title'] ); ?></strong><small><?php echo esc_html( $report['history_qc_summary'] ); ?></small></div></div>
	<?php if ( $report['history_claims'] ) : ?><ul><?php foreach ( $report['history_claims'] as $claim ) : ?><li><span aria-hidden="true">✓</span><?php echo esc_html( $claim ); ?></li><?php endforeach; ?></ul><?php endif; ?>
</div>

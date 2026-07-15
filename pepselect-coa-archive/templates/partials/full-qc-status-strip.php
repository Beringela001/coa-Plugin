<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$category_count = absint( $test['reported_category_count'] );
$total_categories = count( $test['qc_strip_rows'] );
?>
<div class="ps-coa-qc-strip<?php echo $test['qc_all_reported_successful'] ? '' : ' ps-coa-qc-strip--attention'; ?>" role="status" aria-label="<?php echo esc_attr( $test['qc_strip_title'] ); ?>">
	<div class="ps-coa-qc-strip__header">
		<div class="ps-coa-qc-strip__summary">
			<span class="ps-coa-outcome-icon" aria-hidden="true"><?php if ( $test['qc_all_reported_successful'] ) : ?><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg><?php else : ?>!<?php endif; ?></span>
			<div>
				<strong><?php echo esc_html( $test['qc_strip_title'] ); ?></strong>
				<span><?php echo esc_html( $test['qc_strip_summary'] ); ?></span>
			</div>
		</div>
		<p class="ps-coa-qc-strip__count"><?php echo esc_html( sprintf( _n( '%1$d of %2$d category reported', '%1$d of %2$d categories reported', $category_count, 'pepselect-coa-archive' ), $category_count, $total_categories ) ); ?></p>
	</div>
	<ul class="ps-coa-qc-strip__categories">
		<?php foreach ( $test['qc_strip_rows'] as $row ) : ?>
			<?php
			$success = ! empty( $row['status']['success'] );
			$state = $success ? 'success' : ( 'fail' === $row['status']['value'] ? 'failed' : 'neutral' );
			?>
			<li class="ps-coa-qc-category ps-coa-qc-category--<?php echo esc_attr( $state ); ?>">
				<span class="ps-coa-qc-category__icon" aria-hidden="true">
					<?php if ( $success ) : ?><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg><?php else : ?><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5m0 3h.01"/></svg><?php endif; ?>
				</span>
				<span class="ps-coa-qc-category__copy"><strong><?php echo esc_html( $row['short_label'] ); ?></strong><small><?php echo esc_html( $row['detail'] ); ?></small></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

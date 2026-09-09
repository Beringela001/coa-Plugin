<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<div class="ps-coa-qc-strip<?php echo $test['qc_all_reported_successful'] ? '' : ' ps-coa-qc-strip--attention'; ?>" role="status" aria-label="<?php echo esc_attr( __( 'Testing overview', 'pepselect-coa-archive' ) ); ?>">
	<div class="ps-coa-qc-strip__header">
		<div class="ps-coa-qc-strip__summary">

			<div>
				<strong><?php echo esc_html( __( 'Testing overview', 'pepselect-coa-archive' ) ); ?></strong>
			</div>
		</div>
	</div>
	<ul class="ps-coa-qc-strip__categories">
		<?php foreach ( $test['qc_strip_rows'] as $row ) : ?>
			<?php
			// Green means a content measurement is present, not that it matches the label.
			$is_content = 'net-content' === $row['key'];
			$measured = $is_content && '' !== trim( (string) $test['average_net_content_display'] );
			$failed = 'fail' === $row['status']['value'];
			// Omit untested categories only here; retain every disclosure in the evidence table.
			$completed = $is_content ? $measured : ( ! empty( $row['reported'] ) && in_array( $row['status']['value'], array( 'pass', 'reported' ), true ) );
			if ( ! $failed && ! $completed ) { continue; }
			$success = ! $failed;
			$state = $failed ? 'failed' : 'success';
			$state_label = $is_content && ! $failed ? __( 'Measured', 'pepselect-coa-archive' ) : $row['status']['label'];
			?>
			<li class="ps-coa-qc-category ps-coa-qc-category--<?php echo esc_attr( $state ); ?>">
				<span class="ps-coa-qc-category__icon" aria-hidden="true">
					<?php if ( $success ) : ?><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg><?php else : ?><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="8"/><path d="M12 8v5m0 3h.01"/></svg><?php endif; ?>
				</span>
				<span class="ps-coa-qc-category__copy"><strong><?php echo esc_html( $row['short_label'] ); ?></strong><span class="screen-reader-text"><?php echo esc_html( $state_label ); ?></span></span>
			</li>
		<?php endforeach; ?>
	</ul>
</div>

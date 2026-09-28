<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<header class="ps-coa-report-hero">
	<div class="ps-coa-report-hero__identity">
		<p class="ps-coa-certificate-pill"><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 20 6v6c0 4.8-3.1 7.6-8 9-4.9-1.4-8-4.2-8-9V6l8-3Z"/><path d="m9 12 2 2 4-5"/></svg> <?php echo esc_html( 'complete' === $test['workflow_stage'] ? __( 'Certificate of Analysis', 'pepselect-coa-archive' ) : __( 'Batch Vetting Record', 'pepselect-coa-archive' ) ); ?></p>
		<h1><?php echo esc_html( $compound['public_name'] ); ?><?php if ( $compound['strength_value_display'] && $compound['display_strength_separately'] ) : ?> <span><?php echo esc_html( trim( $compound['strength_value_display'] . ' ' . $compound['strength_unit'] ) ); ?></span><?php endif; ?></h1>
		<ul class="ps-coa-hero-facts">
			<?php if ( $test['batch_number'] ) : ?><li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20 13 13 20 4 11V4h7l9 9Z"/><circle cx="8.5" cy="8.5" r="1"/></svg> <?php echo esc_html( sprintf( __( 'Batch %s', 'pepselect-coa-archive' ), $test['batch_number'] ) ); ?></li><?php endif; ?>
			<?php if ( $test['test_date_label'] ) : ?><li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg> <?php echo esc_html( sprintf( __( 'Tested %s', 'pepselect-coa-archive' ), $test['test_date_label'] ) ); ?></li><?php elseif ( $test['expected_coa_date_label'] ) : ?><li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 3v3m12-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14H4V6a1 1 0 0 1 1-1Z"/></svg> <?php echo esc_html( sprintf( __( 'Expected %s', 'pepselect-coa-archive' ), $test['expected_coa_date_label'] ) ); ?></li><?php endif; ?>
			<?php if ( $test['laboratory'] ) : ?><li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M9 3h6m-1 0v6l5 9a2 2 0 0 1-1.7 3H6.7A2 2 0 0 1 5 18l5-9V3m-3 12h10"/></svg> <?php echo esc_html( $test['laboratory'] ); ?></li><?php endif; ?>
		</ul>
		<?php include pepselect_coa_template_path( 'partials/batch-identity-meta.php' ); ?>
	</div>
	<?php include pepselect_coa_template_path( 'partials/batch-vial-image.php' ); ?>
	<div class="ps-coa-report-hero__outcome ps-coa-report-hero__outcome--<?php echo esc_attr( $test['public_status_tone'] ); ?>">
		<div class="ps-coa-outcome-top"><span class="ps-coa-outcome-icon" aria-hidden="true"><?php if ( 'approved' === $test['coa_status'] ) : ?><svg viewBox="0 0 24 24"><path d="m6 12 4 4 8-9"/></svg><?php else : ?><?php echo 'failed' === $test['coa_status'] ? '!' : '&hellip;'; ?><?php endif; ?></span></div>
		<h2><?php echo esc_html( 'approved' === $test['coa_status'] ? \PepSelect\COAArchive\Design_Settings::copy( 'report_passed_heading' ) : ( 'failed' === $test['coa_status'] ? \PepSelect\COAArchive\Design_Settings::copy( 'report_failed_heading' ) : $test['public_status_label'] ) ); ?></h2>
		<?php if ( trim( $test['public_notes'] ) || trim( $test['report_notes'] ) || ( 'failed' === $test['coa_status'] && trim( $test['release_decision_note'] ) ) ) : ?>
		<p><a class="ps-coa-note-link" href="#ps-coa-batch-note"><?php esc_html_e( 'See important batch note below', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">↓</span></a></p>
		<?php else : ?>
		<p><?php echo esc_html( 'approved' === $test['coa_status'] ? \PepSelect\COAArchive\Design_Settings::copy( 'report_passed_copy' ) : ( 'failed' === $test['coa_status'] ? \PepSelect\COAArchive\Design_Settings::copy( 'failed_report_copy' ) : $test['public_status_copy'] ) ); ?></p>
		<?php endif; ?>
		<?php if ( 'pending' === $test['coa_status'] && $test['pending_lab_url'] ) : ?><a class="ps-coa-button ps-coa-button--secondary" href="<?php echo esc_url( $test['pending_lab_url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'view_pending_lab' ) ); ?> <span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'pepselect-coa-archive' ); ?></span></a><?php endif; ?>
	</div>
</header>

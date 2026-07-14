<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<article class="ps-coa-card ps-coa-compound-card">
	<div class="ps-coa-compound-card__media">
		<?php if ( $compound['compound_image_url'] ) : ?>
			<img src="<?php echo esc_url( $compound['compound_image_url'] ); ?>"<?php if ( $compound['compound_image_srcset'] ) : ?> srcset="<?php echo esc_attr( $compound['compound_image_srcset'] ); ?>"<?php endif; ?><?php if ( $compound['compound_image_sizes'] ) : ?> sizes="<?php echo esc_attr( $compound['compound_image_sizes'] ); ?>"<?php endif; ?> alt="<?php echo esc_attr( $compound['compound_image_alt'] ); ?>" loading="lazy">
		<?php else : ?>
			<span class="ps-coa-image-fallback" aria-hidden="true"><svg viewBox="0 0 48 48" focusable="false"><path d="M18 5h12v5l-2 3v5.5c5.5 1.7 9.5 6.8 9.5 12.8V39c0 2.2-1.8 4-4 4h-19c-2.2 0-4-1.8-4-4v-7.7c0-6 4-11.1 9.5-12.8V13l-2-3V5Z"/><path d="M16 30h16"/></svg></span>
		<?php endif; ?>
	</div>
	<div class="ps-coa-compound-card__body">
		<h2><a href="<?php echo esc_url( $compound['url'] ); ?>"><?php echo esc_html( $compound['public_name'] ); ?></a></h2>
		<?php if ( $compound['strength_value_display'] && $compound['display_strength_separately'] ) : ?><p class="ps-coa-strength"><span><?php echo esc_html( trim( $compound['strength_value_display'] . ' ' . $compound['strength_unit'] ) ); ?></span></p><?php endif; ?>
		<?php if ( $compound['public_status_label'] ) : ?><div class="ps-coa-assurance ps-coa-assurance--<?php echo esc_attr( $compound['public_status_tone'] ); ?>"><span aria-hidden="true"></span><div><strong><?php echo esc_html( $compound['public_status_label'] ); ?></strong><?php if ( $compound['public_status_copy'] ) : ?><small><?php echo esc_html( $compound['public_status_copy'] ); ?></small><?php endif; ?></div></div><?php endif; ?>
		<dl class="ps-coa-compact-facts">
			<div><dt><?php esc_html_e( 'Latest test', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $compound['latest_approved_test_date_label'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Purity', 'pepselect-coa-archive' ); ?></dt><dd><?php echo '' !== $compound['latest_purity_percentage_display'] ? esc_html( $compound['latest_purity_percentage_display'] . '%' ) : esc_html__( 'Not reported', 'pepselect-coa-archive' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Laboratory', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $compound['latest_laboratory_display_name'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd></div>
		</dl>
		<?php if ( $compound['recent_batches'] ) : ?><div class="ps-coa-batch-preview" aria-label="<?php esc_attr_e( 'Recent public batches', 'pepselect-coa-archive' ); ?>">
			<p class="ps-coa-batch-preview__label"><?php esc_html_e( 'Recent batches', 'pepselect-coa-archive' ); ?></p>
			<ul><?php foreach ( array_slice( $compound['recent_batches'], 0, 3 ) as $batch ) : ?><li><a class="ps-coa-batch-pill ps-coa-batch-pill--<?php echo esc_attr( $batch['public_status_tone'] ); ?>" href="<?php echo esc_url( $batch['detail_url'] ); ?>"><span><?php echo esc_html( $batch['batch_number'] ); ?></span><small><?php echo esc_html( $batch['coa_status_data']['label'] ); ?></small><time datetime="<?php echo esc_attr( $batch['test_date'] ?: $batch['expected_coa_date'] ); ?>"><?php echo esc_html( $batch['test_date_label'] ?: $batch['expected_coa_date_label'] ); ?></time></a></li><?php endforeach; ?></ul>
		</div><?php endif; ?>
	</div>
	<footer class="ps-coa-compound-card__footer"><a class="ps-coa-text-link" href="<?php echo esc_url( $compound['url'] ); ?>"><?php echo esc_html( \PepSelect\COAArchive\Design_Settings::copy( 'view_history' ) ); ?> <span class="ps-coa-report-count"><?php echo esc_html( number_format_i18n( $compound['public_report_count'] ) ); ?></span> <span aria-hidden="true">&rarr;</span></a></footer>
</article>

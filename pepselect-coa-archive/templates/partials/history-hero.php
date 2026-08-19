<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $hero = $ps_context['hero_image']; $current = $ps_context['current_report']; ?>
<header class="ps-coa-history-hero">
	<div class="ps-coa-history-hero__media"><img src="<?php echo esc_url( $hero['url'] ); ?>"<?php if ( $hero['srcset'] ) : ?> srcset="<?php echo esc_attr( $hero['srcset'] ); ?>"<?php endif; ?><?php if ( $hero['sizes'] ) : ?> sizes="<?php echo esc_attr( $hero['sizes'] ); ?>"<?php endif; ?> alt="<?php echo esc_attr( $hero['alt'] ); ?>" data-image-source="<?php echo esc_attr( $hero['source'] ); ?>"></div>
	<div class="ps-coa-history-hero__identity">
		<p class="ps-coa-history-badge"><span aria-hidden="true">▤</span><?php esc_html_e( 'Batch Vetting Record', 'pepselect-coa-archive' ); ?></p>
		<div class="ps-coa-history-hero__title"><h1><?php echo esc_html( $compound['public_name'] ); ?></h1><?php if ( $compound['strength_value_display'] ) : ?><span><?php echo esc_html( trim( $compound['strength_value_display'] . ' ' . $compound['strength_unit'] ) ); ?></span><?php endif; ?></div>
		<p class="ps-coa-history-hero__description"><?php echo esc_html( sprintf( __( 'Independent testing records and certificate history for every documented %1$s%2$s batch released by Pep Select.', 'pepselect-coa-archive' ), $compound['public_name'], $compound['strength_value_display'] ? ' ' . trim( $compound['strength_value_display'] . ' ' . $compound['strength_unit'] ) : '' ) ); ?></p>
		<?php if ( $compound['woocommerce_product_url'] ) : ?><a class="ps-coa-text-link ps-coa-history-hero__product-link" href="<?php echo esc_url( $compound['woocommerce_product_url'] ); ?>"><?php esc_html_e( 'View compound details', 'pepselect-coa-archive' ); ?> <span aria-hidden="true">&rarr;</span></a><?php endif; ?>
	</div>
	<dl class="ps-coa-history-hero__current">
		<div><dt><?php esc_html_e( 'Current Batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $current && $current['batch_number'] ? $current['batch_number'] : __( 'Not yet documented', 'pepselect-coa-archive' ) ); ?></dd></div>
		<div><dt><?php esc_html_e( 'Latest Test', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $current && $current['test_date_label'] ? $current['test_date_label'] : __( 'Not yet documented', 'pepselect-coa-archive' ) ); ?></dd></div>
	</dl>
</header>

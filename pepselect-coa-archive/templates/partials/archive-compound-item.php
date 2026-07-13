<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<article class="ps-coa__item">
	<?php if ( $compound['compound_image_url'] ) : ?><img class="ps-coa__image" src="<?php echo esc_url( $compound['compound_image_url'] ); ?>" alt="<?php echo esc_attr( $compound['compound_image_alt'] ); ?>" loading="lazy"><?php endif; ?>
	<h2><a href="<?php echo esc_url( $compound['url'] ); ?>"><?php echo esc_html( $compound['display_name'] ); ?></a></h2>
	<?php if ( $compound['strength_value'] ) : ?><p><?php echo esc_html( trim( $compound['strength_value'] . ' ' . $compound['strength_unit'] ) ); ?></p><?php endif; ?>
	<dl class="ps-coa__facts">
		<dt><?php esc_html_e( 'Latest test', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $compound['latest_approved_test_date_label'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd>
		<dt><?php esc_html_e( 'Latest batch', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( $compound['latest_approved_batch_number'] ?: __( 'Not available', 'pepselect-coa-archive' ) ); ?></dd>
		<dt><?php esc_html_e( 'Latest purity', 'pepselect-coa-archive' ); ?></dt><dd><?php echo '' !== $compound['latest_purity_percentage'] ? esc_html( $compound['latest_purity_percentage'] . '%' ) : esc_html__( 'Not available', 'pepselect-coa-archive' ); ?></dd>
		<dt><?php esc_html_e( 'Approved reports', 'pepselect-coa-archive' ); ?></dt><dd><?php echo esc_html( number_format_i18n( $compound['approved_test_count'] ) ); ?></dd>
	</dl>
	<p><a href="<?php echo esc_url( $compound['url'] ); ?>"><?php esc_html_e( 'View testing history', 'pepselect-coa-archive' ); ?></a></p>
</article>

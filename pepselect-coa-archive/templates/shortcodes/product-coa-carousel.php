<?php
/** Product-page COA carousel shortcode template. */
if ( ! defined( 'ABSPATH' ) || empty( $ps_product_carousel['reports'] ) ) { return; }
$ps_instance_id = sanitize_html_class( $ps_product_carousel['instance_id'] );
$ps_report_count = count( $ps_product_carousel['reports'] );
?>
<section class="ps-coa-app ps-coa-product-carousel" data-ps-coa-product-carousel data-report-count="<?php echo esc_attr( $ps_report_count ); ?>" aria-labelledby="<?php echo esc_attr( $ps_instance_id . '-title' ); ?>">
	<header class="ps-coa-product-carousel__header">
		<p class="ps-coa-product-carousel__eyebrow"><?php echo esc_html( $ps_product_carousel['eyebrow'] ); ?></p>
		<h2 class="ps-coa-product-carousel__title" id="<?php echo esc_attr( $ps_instance_id . '-title' ); ?>"><?php echo esc_html( $ps_product_carousel['heading'] ); ?></h2>
		<p class="ps-coa-product-carousel__intro"><?php echo esc_html( $ps_product_carousel['intro'] ); ?></p>
	</header>
	<div class="ps-coa-product-carousel__shell">
		<button class="ps-coa-product-carousel__control ps-coa-product-carousel__previous" type="button" aria-label="<?php esc_attr_e( 'Show previous batch reports', 'pepselect-coa-archive' ); ?>" aria-controls="<?php echo esc_attr( $ps_instance_id . '-viewport' ); ?>"<?php disabled( $ps_report_count <= 1 ); ?>>
			<span aria-hidden="true">&#8592;</span>
		</button>
		<div class="ps-coa-product-carousel__viewport" id="<?php echo esc_attr( $ps_instance_id . '-viewport' ); ?>" role="region" aria-roledescription="carousel" aria-label="<?php esc_attr_e( 'Current, incoming, and previous batch records', 'pepselect-coa-archive' ); ?>" tabindex="0">
			<div class="ps-coa-product-carousel__track">
				<?php foreach ( $ps_product_carousel['reports'] as $ps_product_report ) { include pepselect_coa_template_path( 'partials/product-coa-card.php' ); } ?>
			</div>
		</div>
		<button class="ps-coa-product-carousel__control ps-coa-product-carousel__next" type="button" aria-label="<?php esc_attr_e( 'Show next batch reports', 'pepselect-coa-archive' ); ?>" aria-controls="<?php echo esc_attr( $ps_instance_id . '-viewport' ); ?>"<?php disabled( $ps_report_count <= 1 ); ?>>
			<span aria-hidden="true">&#8594;</span>
		</button>
	</div>
</section>

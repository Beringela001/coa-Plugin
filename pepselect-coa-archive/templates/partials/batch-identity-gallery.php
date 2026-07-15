<?php if ( ! defined( 'ABSPATH' ) ) { exit; } ?>
<section class="ps-coa-report-panel ps-coa-batch-gallery" aria-labelledby="ps-batch-photos">
	<div class="ps-coa-section-heading">
		<p class="ps-coa-eyebrow"><?php esc_html_e( 'Batch identification', 'pepselect-coa-archive' ); ?></p>
		<h2 id="ps-batch-photos"><?php esc_html_e( 'Batch identity photos', 'pepselect-coa-archive' ); ?></h2>
	</div>
	<ol class="ps-coa-batch-photo-grid ps-coa-gallery" data-ps-coa-gallery>
		<?php foreach ( $test['batch_identity_photos'] as $index => $image ) : ?>
			<li><button type="button" class="ps-coa-gallery__trigger" data-ps-coa-full="<?php echo esc_url( $image['full_url'] ); ?>" data-ps-coa-alt="<?php echo esc_attr( $image['alt'] ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Open batch identity photo %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?>">
				<img src="<?php echo esc_url( $image['thumbnail_url'] ); ?>"<?php if ( $image['srcset'] ) : ?> srcset="<?php echo esc_attr( $image['srcset'] ); ?>"<?php endif; ?><?php if ( $image['sizes'] ) : ?> sizes="<?php echo esc_attr( $image['sizes'] ); ?>"<?php endif; ?> alt="<?php echo esc_attr( $image['alt'] ); ?>" loading="lazy">
				<span><strong><?php echo esc_html( $image['title'] ?: sprintf( __( 'Batch photo %d', 'pepselect-coa-archive' ), $index + 1 ) ); ?></strong><?php if ( $image['caption'] ) : ?><small><?php echo esc_html( $image['caption'] ); ?></small><?php endif; ?></span>
			</button></li>
		<?php endforeach; ?>
	</ol>
</section>

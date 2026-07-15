<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }
$laboratory = isset( $laboratory ) && is_array( $laboratory ) ? $laboratory : array();
$logo_id = isset( $laboratory['laboratory_logo_id'] ) ? absint( $laboratory['laboratory_logo_id'] ) : 0;
$logo_url = isset( $laboratory['laboratory_logo_url'] ) ? $laboratory['laboratory_logo_url'] : '';
$logo_alt = isset( $laboratory['laboratory_logo_alt'] ) ? $laboratory['laboratory_logo_alt'] : '';
$attachment_markup = $logo_id ? wp_get_attachment_image( $logo_id, 'thumbnail', false, array( 'class' => 'ps-coa-lab-logo', 'alt' => $logo_alt, 'loading' => 'lazy', 'decoding' => 'async' ) ) : '';
?>
<span class="ps-coa-lab-mark" aria-hidden="<?php echo $logo_alt ? 'false' : 'true'; ?>">
	<?php if ( $attachment_markup ) : echo wp_kses_post( $attachment_markup ); ?>
	<?php elseif ( $logo_url ) : ?><img class="ps-coa-lab-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php echo esc_attr( $logo_alt ); ?>" loading="lazy" decoding="async">
	<?php else : ?><svg viewBox="0 0 24 24" focusable="false"><path d="M9 3h6m-1 0v6l5 9a2 2 0 0 1-1.7 3H6.7A2 2 0 0 1 5 18l5-9V3m-3 12h10"/><path d="M8 18c2-2 3 1 5-1s3 1 4 0"/></svg><?php endif; ?>
</span>

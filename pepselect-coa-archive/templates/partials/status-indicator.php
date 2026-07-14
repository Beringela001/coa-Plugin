<?php if ( ! defined( 'ABSPATH' ) ) { exit; } $value = isset( $status['value'] ) ? $status['value'] : ''; $icon = isset( $status['icon'] ) ? $status['icon'] : $value; ?>
<span class="ps-coa-status <?php echo esc_attr( $status['class'] ); ?>">
	<svg class="ps-coa-status__icon" viewBox="0 0 16 16" aria-hidden="true" focusable="false">
		<?php if ( 'pass' === $icon || 'approved' === $icon ) : ?><path d="m4.1 8.2 2.3 2.3 5.5-5.5"/>
		<?php elseif ( in_array( $value, array( 'fail', 'failed' ), true ) ) : ?><path d="m5 5 6 6M11 5l-6 6"/>
		<?php elseif ( 'reported' === $value ) : ?><path d="M8 7v4M8 4.5h.01"/>
		<?php elseif ( in_array( $value, array( 'pending', 'in-testing', 'vendor-vetting' ), true ) ) : ?><path d="M8 4.2V8l2.4 1.5"/>
		<?php else : ?><path d="M5 8h6"/><?php endif; ?>
	</svg>
	<span><?php echo esc_html( $status['label'] ); ?></span>
</span>

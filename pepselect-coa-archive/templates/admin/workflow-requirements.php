<?php
if ( ! defined( 'ABSPATH' ) || ! current_user_can( 'edit_ps_coas' ) || empty( $ps_coa_requirements ) ) { return; }
$ps_coa_states = array(
	'complete' => array( 'icon' => '&#10003;', 'label' => __( 'Complete', 'pepselect-coa-archive' ) ),
	'missing' => array( 'icon' => '!', 'label' => __( 'Missing', 'pepselect-coa-archive' ) ),
	'not-required' => array( 'icon' => '&mdash;', 'label' => __( 'Not required yet', 'pepselect-coa-archive' ) ),
	'optional' => array( 'icon' => '&#9675;', 'label' => __( 'Optional', 'pepselect-coa-archive' ) ),
);
?>
<div class="ps-coa-workflow-requirements" data-ps-coa-requirements aria-live="polite">
	<p class="ps-coa-workflow-requirements__context"><strong data-ps-coa-requirements-stage><?php echo esc_html( $ps_coa_requirements['stage'] ); ?></strong><span data-ps-coa-requirements-status><?php echo esc_html( $ps_coa_requirements['status'] ); ?></span></p>
	<p class="ps-coa-workflow-requirements__guidance" data-ps-coa-requirements-guidance><?php echo esc_html( $ps_coa_requirements['guidance'] ); ?></p>
	<ul class="ps-coa-workflow-requirements__list" data-ps-coa-requirements-list>
		<?php foreach ( $ps_coa_requirements['items'] as $ps_coa_item ) : $ps_coa_state = $ps_coa_states[ $ps_coa_item['state'] ]; ?>
			<li class="ps-coa-workflow-requirements__item ps-coa-workflow-requirements__item--<?php echo esc_attr( $ps_coa_item['state'] ); ?>">
				<span class="ps-coa-workflow-requirements__icon" aria-hidden="true"><?php echo wp_kses_post( $ps_coa_state['icon'] ); ?></span>
				<span class="ps-coa-workflow-requirements__name"><?php echo esc_html( $ps_coa_item['label'] ); ?></span>
				<span class="ps-coa-workflow-requirements__state"><?php echo esc_html( $ps_coa_state['label'] ); ?></span>
			</li>
		<?php endforeach; ?>
	</ul>
	<p class="description"><?php esc_html_e( 'Selected stage, status, and laboratory update this guidance immediately. Completion states reflect saved evidence and refresh after a valid save; ACF validation remains authoritative.', 'pepselect-coa-archive' ); ?></p>
</div>

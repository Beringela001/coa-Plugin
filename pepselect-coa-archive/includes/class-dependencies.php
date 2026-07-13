<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Detects optional integrations and presents narrowly scoped notices. */
final class Dependencies {
	/** Reports whether ACF's PHP field registration API is available. @return bool */
	public function has_acf() {
		return function_exists( 'acf_add_local_field_group' );
	}

	/** Reports whether the WooCommerce plugin runtime is available. @return bool */
	public function has_woocommerce() {
		return class_exists( 'WooCommerce' ) || defined( 'WC_VERSION' ) || function_exists( 'WC' );
	}

	/** Renders dependency notices only on plugin-relevant administration screens. @return void */
	public function render_notices() {
		if ( ! current_user_can( 'manage_ps_coas' ) || ! $this->is_relevant_screen() ) { return; }
		if ( ! $this->has_acf() ) {
			echo '<div class="notice notice-error"><p><strong>' . esc_html__( 'ACF Pro is required to edit Pep Select compound details.', 'pepselect-coa-archive' ) . '</strong> ' . esc_html__( 'The COA Archive remains active and existing compounds remain stored, but structured field editing is unavailable until ACF Pro is restored.', 'pepselect-coa-archive' ) . '</p></div>';
		}
		if ( ! $this->has_woocommerce() ) {
			echo '<div class="notice notice-info"><p>' . esc_html__( 'WooCommerce product linking is temporarily unavailable. Compound records can still be managed and saved product links are preserved.', 'pepselect-coa-archive' ) . '</p></div>';
		}
	}

	/** Determines whether the current admin request belongs to the COA plugin. @return bool */
	private function is_relevant_screen() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		return $screen && ( Post_Types::COMPOUND === $screen->post_type || 0 === strpos( (string) $screen->id, 'pepselect-coa-archive' ) );
	}
}

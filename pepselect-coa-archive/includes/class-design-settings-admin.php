<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** WordPress Settings API screen for scoped COA design and copy controls. */
final class Design_Settings_Admin {
	const PAGE = 'pepselect-coa-design-copy';
	const GROUP = 'pepselect_coa_design';
	/** @var string */ private $hook = '';

	public function register_hooks() {
		add_action( 'admin_menu', array( $this, 'register_menu' ), 20 );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_pepselect_coa_reset_design', array( $this, 'reset_defaults' ) );
		add_filter( 'option_page_capability_' . self::GROUP, array( $this, 'settings_capability' ) );
	}

	public function register_menu() {
		$this->hook = add_submenu_page( 'pepselect-coa-archive', __( 'Design & Copy', 'pepselect-coa-archive' ), __( 'Design & Copy', 'pepselect-coa-archive' ), 'manage_ps_coas', self::PAGE, array( $this, 'render_page' ) );
	}

	public function register_settings() {
		register_setting( self::GROUP, Design_Settings::OPTION, array( 'type' => 'array', 'sanitize_callback' => array( 'PepSelect\\COAArchive\\Design_Settings', 'sanitize' ), 'default' => array() ) );
		$sections = array(
			'colors' => array( 'Colors', 'Core surfaces, text, borders, and semantic status colors.' ),
			'typography' => array( 'Typography', 'Use local system stacks or inherit the active site typography. No remote fonts are loaded.' ),
			'corners' => array( 'Corners & Borders', 'Pixel values are constrained to safe ranges.' ),
			'buttons' => array( 'Buttons & Search', 'Scoped colors for primary, secondary, and archive-search controls.' ),
			'lightbox' => array( 'Lightbox', 'Fullscreen certificate viewer overlay and control appearance.' ),
			'copy' => array( 'Public Copy', 'Plain-text labels used by public COA templates. Empty submissions fall back to defaults.' ),
			'behavior' => array( 'Archive Behavior', 'Controls whether compounds with only failed reports appear in the main archive.' ),
		);
		foreach ( $sections as $key => $section ) { add_settings_section( 'ps_coa_' . $key, __( $section[0], 'pepselect-coa-archive' ), array( $this, 'render_section' ), self::PAGE, array( 'description' => $section[1], 'preview' => $key ) ); }
		foreach ( Design_Settings::fields() as $key => $field ) { add_settings_field( 'ps_coa_' . $key, __( $field['label'], 'pepselect-coa-archive' ), array( $this, 'render_field' ), self::PAGE, 'ps_coa_' . $field['section'], array( 'key' => $key, 'field' => $field, 'label_for' => 'ps-coa-' . $key ) ); }
	}

	public function render_section( $args ) { if ( ! empty( $args['description'] ) ) { printf( '<p>%1$s <a href="#ps-coa-preview-%2$s">%3$s</a></p>', esc_html( $args['description'] ), esc_attr( $args['preview'] ), esc_html__( 'Preview example', 'pepselect-coa-archive' ) ); } }

	public function render_field( $args ) {
		$key = $args['key']; $field = $args['field']; $settings = Design_Settings::get(); $value = $settings[ $key ]; $name = Design_Settings::OPTION . '[' . $key . ']';
		if ( 'boolean' === $field['type'] ) {
			printf( '<input id="%1$s" name="%2$s" type="checkbox" value="1" %3$s>', esc_attr( $args['label_for'] ), esc_attr( $name ), checked( $value, 1, false ) );
		} elseif ( 'select' === $field['type'] ) {
			printf( '<select id="%1$s" name="%2$s">', esc_attr( $args['label_for'] ), esc_attr( $name ) );
			foreach ( $field['options'] as $option => $label ) { printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $option ), selected( $value, $option, false ), esc_html( $label ) ); }
			echo '</select>';
		} elseif ( 'integer' === $field['type'] || 'decimal' === $field['type'] ) {
			printf( '<input id="%1$s" name="%2$s" type="number" value="%3$s" min="%4$s" max="%5$s" step="%6$s" class="small-text">', esc_attr( $args['label_for'] ), esc_attr( $name ), esc_attr( $value ), esc_attr( $field['min'] ), esc_attr( $field['max'] ), esc_attr( isset( $field['step'] ) ? $field['step'] : 1 ) );
			if ( ! empty( $field['suffix'] ) ) { echo ' <span>' . esc_html( $field['suffix'] ) . '</span>'; }
		} elseif ( 'text' === $field['type'] && ( strlen( $field['default'] ) > 70 || str_ends_with( $key, '_copy' ) ) ) {
			printf( '<textarea id="%1$s" name="%2$s" rows="3">%3$s</textarea>', esc_attr( $args['label_for'] ), esc_attr( $name ), esc_textarea( $value ) );
		} else {
			$class = 'color' === $field['type'] ? 'ps-coa-color-field' : 'regular-text';
			printf( '<input id="%1$s" name="%2$s" type="%5$s" value="%3$s" class="%4$s">', esc_attr( $args['label_for'] ), esc_attr( $name ), esc_attr( $value ), esc_attr( $class ), 'color' === $field['type'] ? 'color' : 'text' );
		}

	}

	public function render_page() {
		if ( ! current_user_can( 'manage_ps_coas' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'pepselect-coa-archive' ) ); }
		?>
		<div class="wrap ps-coa-settings"><h1>COA Design &amp; Copy</h1>
		<p>Edit the public COA wording and appearance. Preview changes here, then save when ready. This does not change test results, batch notes, inventory or product stock pills.</p>
		<div class="ps-coa-editor-toolbar"><button type="button" class="button button-primary" id="ps-coa-design-save" disabled>Save Design &amp; Copy</button> <button type="button" class="button" id="ps-coa-design-discard" disabled>Discard changes</button><span id="ps-coa-design-status" role="status" aria-live="polite">Loading saved settings…</span></div>
		<div class="ps-coa-editor-layout"><form id="ps-coa-design-form"><h2>Controls</h2><label for="ps-coa-setting-search">Find a setting</label><input type="search" id="ps-coa-setting-search" placeholder="Heading, waiting, color…">
		<?php
		$sections = array( 'copy' => 'Public wording', 'report_style' => 'Report status panel', 'colors' => 'Shared colors', 'typography' => 'Typography', 'corners' => 'Corners & borders', 'buttons' => 'Buttons & search', 'lightbox' => 'Certificate viewer', 'behavior' => 'Archive rules' );
		foreach ( $sections as $section => $label ) {
			echo '<details' . ( 'copy' === $section ? ' open' : '' ) . '><summary>' . esc_html( $label ) . '</summary>';
			foreach ( Design_Editor::schema() as $key => $field ) {
				if ( $field['section'] !== $section ) { continue; }
				echo '<div class="ps-coa-editor-field"><label for="ps-coa-' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label><fieldset' . ( $field['readonly'] ? ' disabled' : '' ) . '>';
				$this->render_field( array( 'key' => $key, 'field' => $field, 'label_for' => 'ps-coa-' . $key ) );
				echo '</fieldset><p class="description">' . esc_html( $field['help'] ) . '</p></div>';
			}
			echo '</details>';
		}
		?>
		</form><section class="ps-coa-editor-preview" aria-label="Live COA preview"><div class="ps-coa-preview-toolbar"><label for="ps-coa-preview-page">Preview page</label> <select id="ps-coa-preview-page" disabled><option>Loading public pages…</option></select><div class="ps-coa-device-controls"><button type="button" class="button" data-width="1280" aria-pressed="true">Desktop</button><button type="button" class="button" data-width="390" aria-pressed="false">Mobile</button></div></div>
		<p class="description">Real COA templates and public records. Site navigation is omitted. Inherited fonts use the preview’s system font; choose an explicit font to preview it exactly. Important batch notes override standard descriptions.</p>
		<div class="ps-coa-preview-scroll"><iframe id="ps-coa-design-preview" title="Unsaved COA page preview" sandbox="allow-scripts" width="1280" height="760"></iframe></div>
		</section></div></div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook ) { return; }
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'pepselect-coa-design-admin', plugins_url( 'assets/css/coa-design-admin.css', PEPSELECT_COA_ARCHIVE_FILE ), array( 'wp-color-picker' ), PEPSELECT_COA_ARCHIVE_VERSION );
		wp_enqueue_script( 'pepselect-coa-design-admin', plugins_url( 'assets/js/coa-design-admin.js', PEPSELECT_COA_ARCHIVE_FILE ), array( 'jquery', 'wp-color-picker' ), PEPSELECT_COA_ARCHIVE_VERSION, true );
		wp_localize_script( 'pepselect-coa-design-admin', 'PepSelectCoaDesign', array( 'endpoint' => rest_url( 'pepselect-coa/v1/design' ), 'nonce' => wp_create_nonce( 'wp_rest' ) ) );
	}

	public function reset_defaults() {
		if ( ! current_user_can( 'manage_ps_coas' ) ) { wp_die( esc_html__( 'You do not have permission to reset these settings.', 'pepselect-coa-archive' ) ); }
		check_admin_referer( 'pepselect_coa_reset_design' );
		delete_option( Design_Settings::OPTION ); Design_Settings::clear_cache();
		wp_safe_redirect( add_query_arg( 'ps_coa_reset', '1', admin_url( 'admin.php?page=' . self::PAGE ) ) ); exit;
	}

	public function settings_capability() { return 'manage_ps_coas'; }
}

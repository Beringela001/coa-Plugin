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
		} else {
			$class = 'color' === $field['type'] ? 'ps-coa-color-field' : 'regular-text';
			printf( '<input id="%1$s" name="%2$s" type="text" value="%3$s" class="%4$s"%5$s>', esc_attr( $args['label_for'] ), esc_attr( $name ), esc_attr( $value ), esc_attr( $class ), 'color' === $field['type'] ? ' data-default-color="' . esc_attr( $field['default'] ) . '"' : '' );
		}
		printf( '<p class="description">%1$s <a href="#ps-coa-preview-%2$s">%3$s</a></p>', esc_html( sprintf( __( 'Changes the frontend %s.', 'pepselect-coa-archive' ), strtolower( $field['label'] ) ) ), esc_attr( $field['section'] ), esc_html__( 'What this changes', 'pepselect-coa-archive' ) );
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_ps_coas' ) ) { wp_die( esc_html__( 'You do not have permission to access this page.', 'pepselect-coa-archive' ) ); }
		?>
		<div class="wrap ps-coa-settings"><h1><?php esc_html_e( 'COA Archive Design & Copy', 'pepselect-coa-archive' ); ?></h1>
		<?php if ( isset( $_GET['ps_coa_reset'] ) ) : // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?><div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'COA design and copy settings were reset to defaults.', 'pepselect-coa-archive' ); ?></p></div><?php endif; ?>
		<form action="options.php" method="post"><?php settings_fields( self::GROUP ); do_settings_sections( self::PAGE ); submit_button( __( 'Save Design & Copy', 'pepselect-coa-archive' ) ); ?></form>
		<section class="ps-coa-setting-previews" aria-label="<?php esc_attr_e( 'Frontend setting examples', 'pepselect-coa-archive' ); ?>">
			<div id="ps-coa-preview-colors"><h2><?php esc_html_e( 'Colors example', 'pepselect-coa-archive' ); ?></h2><div class="ps-coa-preview-card"><strong><?php esc_html_e( 'Full-QC Documented', 'pepselect-coa-archive' ); ?></strong><span><?php esc_html_e( 'Card, text, border, accent, and status colors', 'pepselect-coa-archive' ); ?></span></div></div>
			<div id="ps-coa-preview-typography"><h2><?php esc_html_e( 'Typography example', 'pepselect-coa-archive' ); ?></h2><p class="ps-coa-preview-heading"><?php esc_html_e( 'Retatrutide Vetting History', 'pepselect-coa-archive' ); ?></p></div>
			<div id="ps-coa-preview-corners"><h2><?php esc_html_e( 'Corners example', 'pepselect-coa-archive' ); ?></h2><div class="ps-coa-preview-shapes"><span><?php esc_html_e( 'Card', 'pepselect-coa-archive' ); ?></span><span><?php esc_html_e( 'Search', 'pepselect-coa-archive' ); ?></span></div></div>
			<div id="ps-coa-preview-buttons"><h2><?php esc_html_e( 'Buttons & Search example', 'pepselect-coa-archive' ); ?></h2><div class="ps-coa-preview-controls"><input type="text" value="<?php esc_attr_e( 'Search compounds...', 'pepselect-coa-archive' ); ?>" readonly><button type="button" class="button button-primary"><?php esc_html_e( 'Search', 'pepselect-coa-archive' ); ?></button></div></div>
			<div id="ps-coa-preview-lightbox"><h2><?php esc_html_e( 'Lightbox example', 'pepselect-coa-archive' ); ?></h2><div class="ps-coa-preview-lightbox"><span>&larr;</span><strong><?php esc_html_e( 'Certificate page', 'pepselect-coa-archive' ); ?></strong><span>&rarr;</span></div></div>
			<div id="ps-coa-preview-copy"><h2><?php esc_html_e( 'Public copy example', 'pepselect-coa-archive' ); ?></h2><p><?php esc_html_e( 'Labels update the archive, history cards, actions, and search without changing URLs or scientific data.', 'pepselect-coa-archive' ); ?></p></div>
			<div id="ps-coa-preview-behavior"><h2><?php esc_html_e( 'Archive behavior example', 'pepselect-coa-archive' ); ?></h2><p><?php esc_html_e( 'Failed-only compounds stay out of the main archive unless explicitly enabled.', 'pepselect-coa-archive' ); ?></p></div>
		</section>
		<hr><h2><?php esc_html_e( 'Reset Defaults', 'pepselect-coa-archive' ); ?></h2><p><?php esc_html_e( 'Reset only COA design and public-copy settings. Compounds, reports, routes, and media are not changed.', 'pepselect-coa-archive' ); ?></p>
		<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return confirm('<?php echo esc_js( __( 'Reset all COA design and copy settings to their defaults?', 'pepselect-coa-archive' ) ); ?>');"><input type="hidden" name="action" value="pepselect_coa_reset_design"><?php wp_nonce_field( 'pepselect_coa_reset_design' ); ?><?php submit_button( __( 'Reset to Defaults', 'pepselect-coa-archive' ), 'secondary', 'submit', false ); ?></form></div>
		<?php
	}

	public function enqueue_assets( $hook ) {
		if ( $hook !== $this->hook ) { return; }
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_style( 'pepselect-coa-design-admin', plugins_url( 'assets/css/coa-design-admin.css', PEPSELECT_COA_ARCHIVE_FILE ), array( 'wp-color-picker' ), PEPSELECT_COA_ARCHIVE_VERSION );
		wp_enqueue_script( 'pepselect-coa-design-admin', plugins_url( 'assets/js/coa-design-admin.js', PEPSELECT_COA_ARCHIVE_FILE ), array( 'jquery', 'wp-color-picker' ), PEPSELECT_COA_ARCHIVE_VERSION, true );
	}

	public function reset_defaults() {
		if ( ! current_user_can( 'manage_ps_coas' ) ) { wp_die( esc_html__( 'You do not have permission to reset these settings.', 'pepselect-coa-archive' ) ); }
		check_admin_referer( 'pepselect_coa_reset_design' );
		delete_option( Design_Settings::OPTION ); Design_Settings::clear_cache();
		wp_safe_redirect( add_query_arg( 'ps_coa_reset', '1', admin_url( 'admin.php?page=' . self::PAGE ) ) ); exit;
	}

	public function settings_capability() { return 'manage_ps_coas'; }
}

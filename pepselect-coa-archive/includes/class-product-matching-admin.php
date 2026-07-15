<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Provides capability-protected product matching, creation, synchronization, and audit interfaces. */
final class Product_Matching_Admin {
	/** @var Product_Matching */ private $matching;
	/** @var string */ private $page_hook = '';
	/** @var bool */ private $saving_product = false;

	public function __construct( Product_Matching $matching ) { $this->matching = $matching; }

	public function register_hooks() {
		add_action( 'add_meta_boxes_' . Post_Types::COMPOUND, array( $this, 'add_compound_box' ) );
		add_action( 'add_meta_boxes_product', array( $this, 'add_product_box' ) );
		add_action( 'save_post_' . Post_Types::COMPOUND, array( $this, 'save_compound_box' ), 20, 2 );
		add_action( 'save_post_product', array( $this, 'save_product_box' ), 20, 2 );
		add_action( 'admin_menu', array( $this, 'register_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_pepselect_coa_search_products', array( $this, 'ajax_search' ) );
		add_action( 'admin_post_pepselect_coa_product_action', array( $this, 'handle_action' ) );
		add_action( 'admin_post_pepselect_coa_bulk_products', array( $this, 'handle_bulk' ) );
		add_action( 'admin_notices', array( $this, 'render_saved_notice' ) );
	}

	public function register_page() {
		$this->page_hook = add_submenu_page( 'pepselect-coa-archive', __( 'Product Matching', 'pepselect-coa-archive' ), __( 'Product Matching', 'pepselect-coa-archive' ), 'manage_ps_compounds', 'pepselect-coa-product-matching', array( $this, 'render_page' ) );
	}

	public function add_compound_box() {
		add_meta_box( 'pepselect-coa-product-matching', __( 'WooCommerce Product Matching', 'pepselect-coa-archive' ), array( $this, 'render_compound_box' ), Post_Types::COMPOUND, 'normal', 'high' );
	}

	public function add_product_box() {
		if ( current_user_can( 'manage_ps_compounds' ) ) { add_meta_box( 'pepselect-product-coa-archive', __( 'COA Archive', 'pepselect-coa-archive' ), array( $this, 'render_product_box' ), 'product', 'side', 'high' ); }
	}

	public function enqueue_assets( $hook ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		$compound = $screen && Post_Types::COMPOUND === $screen->post_type;
		$product = $screen && 'product' === $screen->post_type;
		if ( ! $compound && ! $product && $hook !== $this->page_hook ) { return; }
		wp_enqueue_style( 'pepselect-coa-product-matching-admin', plugins_url( 'assets/css/pepselect-coa-admin-product-matching.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		wp_enqueue_script( 'pepselect-coa-product-matching-admin', plugins_url( 'assets/js/pepselect-coa-product-matching.js', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION, true );
		wp_localize_script( 'pepselect-coa-product-matching-admin', 'PepSelectCOAProductMatching', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce( 'pepselect_coa_product_search' ), 'searching' => __( 'Searching…', 'pepselect-coa-archive' ), 'noResults' => __( 'No matching products found.', 'pepselect-coa-archive' ), 'bulkConfirm' => __( 'Continue with this bulk operation for the selected products? Ineligible or ambiguous products will be skipped.', 'pepselect-coa-archive' ) ) );
	}

	public function render_compound_box( $post ) {
		wp_nonce_field( 'pepselect_coa_save_product_relationship', 'pepselect_coa_product_relationship_nonce' );
		$product_id = absint( get_post_meta( $post->ID, Product_Matching::PRODUCT_ID_META, true ) );
		$summary = $product_id ? $this->matching->product_summary( $product_id ) : array();
		$snapshot = trim( (string) get_post_meta( $post->ID, Product_Matching::SKU_SNAPSHOT_META, true ) );
		$last_sync = (string) get_post_meta( $post->ID, Product_Matching::LAST_SYNC_META, true );
		$status = (string) get_post_meta( $post->ID, Product_Matching::SYNC_STATUS_META, true );
		echo '<div class="ps-coa-product-match" data-ps-coa-product-matching>';
		if ( ! $this->matching->is_available() ) {
			printf( '<div class="notice notice-warning inline"><p><strong>%s</strong> %s</p></div>', esc_html__( 'WooCommerce Inactive.', 'pepselect-coa-archive' ), esc_html__( 'The saved Product ID and SKU snapshot are preserved. Matching controls are disabled until WooCommerce is restored.', 'pepselect-coa-archive' ) );
			$this->render_product_details( $summary, $product_id, $snapshot, $last_sync, $status ); echo '</div>'; return;
		}
		if ( $summary ) {
			echo '<div class="ps-coa-connected-notice"><strong>' . esc_html__( 'Connected to WooCommerce', 'pepselect-coa-archive' ) . '</strong><p>' . esc_html__( 'Product identity fields are synchronized from WooCommerce. Testing, batch, laboratory, packaging, and certificate records remain managed by the COA Archive.', 'pepselect-coa-archive' ) . '</p></div>';
			$this->render_product_details( $summary, $product_id, $snapshot, $last_sync, $status );
		} elseif ( $product_id ) {
			printf( '<div class="notice notice-warning inline"><p>%s</p></div>', esc_html__( 'The saved product is missing. The Product ID, SKU snapshot, compound, and testing history remain preserved.', 'pepselect-coa-archive' ) );
			$this->render_product_details( array(), $product_id, $snapshot, $last_sync, 'product-missing' );
		}
		echo '<div class="ps-coa-product-search"><label for="ps-coa-product-query"><strong>' . esc_html__( 'Search WooCommerce products', 'pepselect-coa-archive' ) . '</strong></label><div><input id="ps-coa-product-query" type="search" data-ps-coa-product-query placeholder="' . esc_attr__( 'Exact or partial SKU, title, or Product ID', 'pepselect-coa-archive' ) . '"><button type="button" class="button" data-ps-coa-product-search>' . esc_html__( 'Search', 'pepselect-coa-archive' ) . '</button></div><div data-ps-coa-product-results aria-live="polite"></div></div>';
		printf( '<input type="hidden" name="pepselect_coa_product_id" value="%d" data-ps-coa-product-id>', $product_id );
		echo '<div class="ps-coa-product-selection" data-ps-coa-product-selection></div>';
		if ( $product_id ) { echo '<label class="ps-coa-disconnect"><input type="checkbox" name="pepselect_coa_disconnect_product" value="1"> ' . esc_html__( 'Disconnect relationship (COA records and testing history are preserved)', 'pepselect-coa-archive' ) . '</label>'; }
		echo '</div>';
	}

	public function render_product_box( $post ) {
		wp_nonce_field( 'pepselect_coa_save_product_panel', 'pepselect_coa_product_panel_nonce' );
		$summary = $this->matching->product_summary( $post->ID ); $status = $this->matching->product_status( $post->ID );
		$compound_ids = isset( $status['compound_ids'] ) ? $status['compound_ids'] : $this->matching->compounds_for_product( $post->ID );
		$compound_id = $compound_ids ? absint( $compound_ids[0] ) : 0;
		printf( '<p><label><input type="checkbox" name="pepselect_coa_include" value="yes"%s> <strong>%s</strong></label></p>', checked( ! empty( $summary['include'] ), true, false ), esc_html__( 'Include in COA Archive', 'pepselect-coa-archive' ) );
		printf( '<p><label for="pepselect-coa-display-name"><strong>%s</strong></label><input class="widefat" id="pepselect-coa-display-name" name="pepselect_coa_display_name" value="%s" placeholder="%s"></p>', esc_html__( 'COA Display Name', 'pepselect-coa-archive' ), esc_attr( isset( $summary['coa_display_name'] ) ? $summary['coa_display_name'] : '' ), esc_attr__( 'e.g. Retatrutide', 'pepselect-coa-archive' ) );
		$strength = isset( $summary['strength'] ) ? $summary['strength'] : array( 'value' => '', 'unit' => '' );
		echo '<div class="ps-coa-product-strength"><p><label><strong>' . esc_html__( 'Strength', 'pepselect-coa-archive' ) . '</strong><input class="widefat" type="number" min="0.000001" step="0.000001" name="pepselect_coa_strength" value="' . esc_attr( 'dedicated' === ( isset( $strength['source'] ) ? $strength['source'] : '' ) ? $strength['value'] : '' ) . '"></label></p><p><label><strong>' . esc_html__( 'Strength Unit', 'pepselect-coa-archive' ) . '</strong><select class="widefat" name="pepselect_coa_strength_unit"><option value="">—</option>';
		foreach ( Compound_Validation::units() as $value => $label ) { printf( '<option value="%1$s"%2$s>%3$s</option>', esc_attr( $value ), selected( 'dedicated' === ( isset( $strength['source'] ) ? $strength['source'] : '' ) ? $strength['unit'] : '', $value, false ), esc_html( $label ) ); }
		echo '</select></label></p></div>';
		printf( '<dl class="ps-coa-product-facts"><div><dt>%s</dt><dd>%s</dd></div><div><dt>%s</dt><dd>%d</dd></div><div><dt>%s</dt><dd><span class="ps-coa-match-status ps-coa-match-status--%s">%s</span></dd></div></dl>', esc_html__( 'Product SKU', 'pepselect-coa-archive' ), esc_html( isset( $summary['sku'] ) && $summary['sku'] ? $summary['sku'] : '—' ), esc_html__( 'Product ID', 'pepselect-coa-archive' ), $post->ID, esc_html__( 'COA connection status', 'pepselect-coa-archive' ), esc_attr( $status['key'] ), esc_html( $status['label'] ) );
		if ( $compound_id ) {
			printf( '<p><strong>%s:</strong> <a href="%s">%s</a></p>', esc_html__( 'Connected COA Compound', 'pepselect-coa-archive' ), esc_url( get_edit_post_link( $compound_id, 'raw' ) ), esc_html( get_the_title( $compound_id ) ) );
			$this->action_link( 'sync', $post->ID, $compound_id, __( 'Sync Now', 'pepselect-coa-archive' ), 'button' );
			$this->action_link( 'disconnect', $post->ID, $compound_id, __( 'Disconnect Relationship', 'pepselect-coa-archive' ), 'button-link-delete' );
			if ( 'publish' === get_post_status( $compound_id ) ) { printf( ' <a class="button" href="%s">%s</a>', esc_url( $this->compound_public_url( $compound_id ) ), esc_html__( 'View Vetting History', 'pepselect-coa-archive' ) ); }
		} elseif ( 'ready-to-create' === $status['key'] ) { $this->action_link( 'create', $post->ID, 0, __( 'Create and Connect', 'pepselect-coa-archive' ), 'button button-primary' ); }
		elseif ( 'needs-review' === $status['key'] ) { echo '<p class="description">' . esc_html__( 'Complete the scientific name and confirmed strength fields, save the product, then create the draft compound.', 'pepselect-coa-archive' ) . '</p>'; }
		$last = $compound_id ? get_post_meta( $compound_id, Product_Matching::LAST_SYNC_META, true ) : '';
		if ( $last ) { echo '<p class="description">' . esc_html__( 'Last synchronized:', 'pepselect-coa-archive' ) . ' ' . esc_html( $last ) . '</p>'; }
	}

	public function save_product_box( $post_id, $post ) {
		if ( $this->saving_product || ! isset( $_POST['pepselect_coa_product_panel_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pepselect_coa_product_panel_nonce'] ) ), 'pepselect_coa_save_product_panel' ) || ! current_user_can( 'manage_ps_compounds' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
		update_post_meta( $post_id, Product_Matching::INCLUDE_META, isset( $_POST['pepselect_coa_include'] ) ? 'yes' : 'no' );
		update_post_meta( $post_id, Product_Matching::DISPLAY_NAME_META, isset( $_POST['pepselect_coa_display_name'] ) ? sanitize_text_field( wp_unslash( $_POST['pepselect_coa_display_name'] ) ) : '' );
		$value = isset( $_POST['pepselect_coa_strength'] ) ? sanitize_text_field( wp_unslash( $_POST['pepselect_coa_strength'] ) ) : '';
		$unit = isset( $_POST['pepselect_coa_strength_unit'] ) ? sanitize_text_field( wp_unslash( $_POST['pepselect_coa_strength_unit'] ) ) : '';
		if ( '' !== $value && is_numeric( $value ) && (float) $value > 0 && isset( Compound_Validation::units()[ $unit ] ) ) { update_post_meta( $post_id, Product_Matching::STRENGTH_META, (string) (float) $value ); update_post_meta( $post_id, Product_Matching::STRENGTH_UNIT_META, $unit ); }
		else { delete_post_meta( $post_id, Product_Matching::STRENGTH_META ); delete_post_meta( $post_id, Product_Matching::STRENGTH_UNIT_META ); }
		$this->saving_product = true; foreach ( $this->matching->compounds_for_product( $post_id ) as $compound_id ) { $result = $this->matching->sync( $compound_id, false ); if ( is_wp_error( $result ) ) { $this->store_notice( $result->get_error_message(), true ); } elseif ( ! empty( $result['changes'] ) ) { $this->store_notice( $this->sync_message( $result ) ); } } $this->saving_product = false;
	}

	public function save_compound_box( $post_id, $post ) {
		unset( $post );
		if ( ! isset( $_POST['pepselect_coa_product_relationship_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pepselect_coa_product_relationship_nonce'] ) ), 'pepselect_coa_save_product_relationship' ) || ! current_user_can( 'manage_ps_compounds' ) || ! current_user_can( 'edit_post', $post_id ) || wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) { return; }
		if ( isset( $_POST['pepselect_coa_disconnect_product'] ) ) { $this->matching->disconnect( $post_id ); $this->store_notice( __( 'WooCommerce relationship disconnected. COA records and testing history were preserved.', 'pepselect-coa-archive' ) ); return; }
		if ( ! isset( $_POST['pepselect_coa_product_id'] ) ) { return; }
		$product_id = absint( $_POST['pepselect_coa_product_id'] );
		$current = absint( get_post_meta( $post_id, Product_Matching::PRODUCT_ID_META, true ) );
		if ( $product_id && $product_id !== $current ) { $result = $this->matching->connect_existing( $product_id, $post_id ); }
		elseif ( $current ) { $result = $this->matching->sync( $post_id ); }
		else { return; }
		$this->store_notice( is_wp_error( $result ) ? $result->get_error_message() : $this->sync_message( $result ), is_wp_error( $result ) );
	}

	public function render_saved_notice() {
		$user_id = get_current_user_id(); if ( ! $user_id ) { return; }
		$key = 'pepselect_coa_product_notice_' . $user_id; $notice = get_transient( $key ); if ( ! is_array( $notice ) ) { return; } delete_transient( $key );
		printf( '<div class="notice %s is-dismissible"><p>%s</p></div>', ! empty( $notice['error'] ) ? 'notice-error' : 'notice-success', esc_html( $notice['message'] ) );
	}

	public function ajax_search() {
		check_ajax_referer( 'pepselect_coa_product_search', 'nonce' );
		if ( ! current_user_can( 'manage_ps_compounds' ) ) { wp_send_json_error( array( 'message' => __( 'You do not have permission to search products.', 'pepselect-coa-archive' ) ), 403 ); }
		$query = isset( $_POST['query'] ) ? sanitize_text_field( wp_unslash( $_POST['query'] ) ) : '';
		wp_send_json_success( array( 'products' => $this->matching->search( $query ) ) );
	}

	public function handle_action() {
		if ( ! current_user_can( 'manage_ps_compounds' ) ) { wp_die( esc_html__( 'You do not have permission to manage product relationships.', 'pepselect-coa-archive' ), 403 ); }
		$action = isset( $_REQUEST['ps_action'] ) ? sanitize_key( wp_unslash( $_REQUEST['ps_action'] ) ) : ''; $product_id = isset( $_REQUEST['product_id'] ) ? absint( $_REQUEST['product_id'] ) : 0; $compound_id = isset( $_REQUEST['compound_id'] ) ? absint( $_REQUEST['compound_id'] ) : 0;
		check_admin_referer( 'pepselect_coa_product_action_' . $action . '_' . $product_id );
		$result = new \WP_Error( 'invalid_action', __( 'Invalid product action.', 'pepselect-coa-archive' ) );
		if ( 'create' === $action ) { $result = $this->matching->create_and_connect( $product_id ); $compound_id = is_wp_error( $result ) ? 0 : absint( $result ); }
		elseif ( 'connect' === $action ) { $result = $this->matching->connect_existing( $product_id, $compound_id ); }
		elseif ( 'sync' === $action ) { $result = $this->matching->sync( $compound_id ); }
		elseif ( 'disconnect' === $action ) { $result = $this->matching->disconnect( $compound_id ); }
		if ( ! is_wp_error( $result ) && 'create' === $action && $compound_id ) { wp_safe_redirect( get_edit_post_link( $compound_id, 'raw' ) ); exit; }
		$this->redirect_with_result( $result );
	}

	public function handle_bulk() {
		if ( ! current_user_can( 'manage_ps_compounds' ) ) { wp_die( esc_html__( 'You do not have permission to manage products.', 'pepselect-coa-archive' ), 403 ); }
		check_admin_referer( 'pepselect_coa_bulk_products' );
		$action = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : ''; $ids = isset( $_POST['product_ids'] ) ? array_values( array_unique( array_map( 'absint', (array) wp_unslash( $_POST['product_ids'] ) ) ) ) : array();
		$done = 0; $skipped = 0;
		foreach ( $ids as $product_id ) {
			if ( 'include' === $action ) { update_post_meta( $product_id, Product_Matching::INCLUDE_META, 'yes' ); ++$done; continue; }
			$status = $this->matching->product_status( $product_id );
			if ( 'create' === $action && 'ready-to-create' === $status['key'] ) { $result = $this->matching->create_and_connect( $product_id ); is_wp_error( $result ) ? ++$skipped : ++$done; }
			elseif ( 'sync' === $action && 'connected' === $status['key'] && ! empty( $status['compound_ids'][0] ) ) { $result = $this->matching->sync( $status['compound_ids'][0] ); is_wp_error( $result ) ? ++$skipped : ++$done; }
			else { ++$skipped; }
		}
		$url = add_query_arg( array( 'page' => 'pepselect-coa-product-matching', 'ps_done' => $done, 'ps_skipped' => $skipped ), admin_url( 'admin.php' ) ); wp_safe_redirect( $url ); exit;
	}

	public function render_page() {
		if ( ! current_user_can( 'manage_ps_compounds' ) ) { wp_die( esc_html__( 'You do not have permission to view Product Matching.', 'pepselect-coa-archive' ) ); }
		$products = get_posts( array( 'post_type' => 'product', 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) );
		$compounds = $this->compound_options();
		echo '<div class="wrap ps-coa-product-matching-page"><h1>' . esc_html__( 'Product Matching', 'pepselect-coa-archive' ) . '</h1><p>' . esc_html__( 'WooCommerce products are the source of truth for product identity. COA scientific names, testing, batch, laboratory, packaging, and certificate records remain independently managed.', 'pepselect-coa-archive' ) . '</p>';
		if ( isset( $_GET['ps_done'] ) ) { printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( sprintf( __( '%1$d product actions completed; %2$d ineligible or ambiguous products skipped.', 'pepselect-coa-archive' ), absint( $_GET['ps_done'] ), absint( isset( $_GET['ps_skipped'] ) ? $_GET['ps_skipped'] : 0 ) ) ) ); }
		if ( isset( $_GET['ps_message'] ) ) { $error = ! empty( $_GET['ps_error'] ); printf( '<div class="notice %s"><p>%s</p></div>', $error ? 'notice-error' : 'notice-success', esc_html( sanitize_text_field( wp_unslash( $_GET['ps_message'] ) ) ) ); }
		if ( ! $this->matching->is_available() ) { echo '<div class="notice notice-warning"><p>' . esc_html__( 'WooCommerce is inactive. Existing Product IDs and SKU snapshots remain stored; modification actions are unavailable.', 'pepselect-coa-archive' ) . '</p></div>'; }
		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" data-ps-coa-bulk-form><input type="hidden" name="action" value="pepselect_coa_bulk_products">'; wp_nonce_field( 'pepselect_coa_bulk_products' );
		echo '<div class="tablenav top"><div class="alignleft actions"><select name="bulk_action"><option value="">' . esc_html__( 'Bulk actions', 'pepselect-coa-archive' ) . '</option><option value="include">' . esc_html__( 'Mark Include in COA Archive', 'pepselect-coa-archive' ) . '</option><option value="create">' . esc_html__( 'Create and Connect eligible products', 'pepselect-coa-archive' ) . '</option><option value="sync">' . esc_html__( 'Sync connected products', 'pepselect-coa-archive' ) . '</option></select><button class="button" type="submit">' . esc_html__( 'Apply', 'pepselect-coa-archive' ) . '</button></div></div>';
		echo '<table class="widefat striped ps-coa-product-table"><thead><tr><td class="check-column"><input type="checkbox" data-ps-coa-select-all></td>';
		foreach ( array( 'WooCommerce Product', 'SKU', 'Product ID', 'Product status', 'Strength', 'COA Display Name', 'Include in COA Archive', 'Connected COA Compound', 'Match Status', 'Last Sync', 'Action' ) as $heading ) { echo '<th>' . esc_html__( $heading, 'pepselect-coa-archive' ) . '</th>'; }
		echo '</tr></thead><tbody>';
		if ( ! $products ) { echo '<tr><td colspan="12">' . esc_html__( 'No WooCommerce products found.', 'pepselect-coa-archive' ) . '</td></tr>'; }
		foreach ( $products as $product ) { $this->render_product_row( $product, $compounds ); }
		echo '</tbody></table></form>';
		$this->render_orphaned_relationships();
		echo '</div>';
	}

	private function render_orphaned_relationships() {
		$compounds = get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'private', 'pending', 'future' ), 'posts_per_page' => -1, 'no_found_rows' => true, 'meta_query' => array( array( 'key' => Product_Matching::PRODUCT_ID_META, 'compare' => 'EXISTS' ) ) ) );
		$orphans = array_filter( $compounds, function ( $compound ) { $product_id = absint( get_post_meta( $compound->ID, Product_Matching::PRODUCT_ID_META, true ) ); return $product_id && ! $this->matching->product( $product_id ); } );
		if ( ! $orphans ) { return; }
		echo '<h2>' . esc_html__( 'Product Missing', 'pepselect-coa-archive' ) . '</h2><p>' . esc_html__( 'These historical compound relationships point to products that no longer exist. No COA records were deleted.', 'pepselect-coa-archive' ) . '</p><table class="widefat striped"><thead><tr><th>' . esc_html__( 'COA Compound', 'pepselect-coa-archive' ) . '</th><th>' . esc_html__( 'Saved Product ID', 'pepselect-coa-archive' ) . '</th><th>' . esc_html__( 'SKU Snapshot', 'pepselect-coa-archive' ) . '</th><th>' . esc_html__( 'Match Status', 'pepselect-coa-archive' ) . '</th><th>' . esc_html__( 'Action', 'pepselect-coa-archive' ) . '</th></tr></thead><tbody>';
		foreach ( $orphans as $compound ) { $product_id = absint( get_post_meta( $compound->ID, Product_Matching::PRODUCT_ID_META, true ) ); printf( '<tr><td><a href="%s">%s</a></td><td>%d</td><td><code>%s</code></td><td><span class="ps-coa-match-status ps-coa-match-status--product-missing">%s</span></td><td>', esc_url( get_edit_post_link( $compound->ID, 'raw' ) ), esc_html( get_the_title( $compound ) ), $product_id, esc_html( get_post_meta( $compound->ID, Product_Matching::SKU_SNAPSHOT_META, true ) ), esc_html__( 'Product Missing', 'pepselect-coa-archive' ) ); $this->action_link( 'disconnect', $product_id, $compound->ID, __( 'Disconnect Relationship', 'pepselect-coa-archive' ) ); echo '</td></tr>'; }
		echo '</tbody></table>';
	}

	private function render_product_row( $product, $compounds ) {
		$summary = $this->matching->product_summary( $product->ID ); $status = $this->matching->product_status( $product->ID ); $compound_ids = isset( $status['compound_ids'] ) ? $status['compound_ids'] : $this->matching->compounds_for_product( $product->ID ); $compound_id = $compound_ids ? absint( $compound_ids[0] ) : 0; $strength = $summary['strength'];
		echo '<tr><th class="check-column"><input type="checkbox" name="product_ids[]" value="' . esc_attr( $product->ID ) . '" data-ps-coa-product-checkbox></th>';
		printf( '<td><strong><a href="%s">%s</a></strong></td><td><code>%s</code></td><td>%d</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td>', esc_url( get_edit_post_link( $product->ID, 'raw' ) ), esc_html( $product->post_title ), esc_html( $summary['sku'] ?: '—' ), $product->ID, esc_html( ucfirst( $product->post_status ) ), esc_html( $strength['value'] ? trim( pepselect_coa_format_number( $strength['value'] ) . ' ' . $strength['unit'] ) : '—' ), esc_html( $summary['coa_display_name'] ?: '—' ), esc_html( $summary['include'] ? __( 'Yes', 'pepselect-coa-archive' ) : __( 'No', 'pepselect-coa-archive' ) ) );
		echo '<td>' . ( $compound_id ? '<a href="' . esc_url( get_edit_post_link( $compound_id, 'raw' ) ) . '">' . esc_html( get_the_title( $compound_id ) ) . '</a>' : '—' ) . '</td>';
		printf( '<td><span class="ps-coa-match-status ps-coa-match-status--%s">%s</span></td><td>%s</td><td class="ps-coa-row-actions">', esc_attr( $status['key'] ), esc_html( $status['label'] ), esc_html( $compound_id ? get_post_meta( $compound_id, Product_Matching::LAST_SYNC_META, true ) : '—' ) );
		if ( $compound_id ) { $this->action_link( 'sync', $product->ID, $compound_id, __( 'Sync Now', 'pepselect-coa-archive' ) ); $this->action_link( 'disconnect', $product->ID, $compound_id, __( 'Disconnect', 'pepselect-coa-archive' ) ); }
		elseif ( 'ready-to-create' === $status['key'] ) { $this->action_link( 'create', $product->ID, 0, __( 'Create and Connect', 'pepselect-coa-archive' ) ); }
		if ( ! $compound_id && $summary['include'] && $summary['sku'] && $compounds ) { $this->render_connect_form( $product->ID, $compounds ); }
		printf( '<a href="%s">%s</a></td></tr>', esc_url( get_edit_post_link( $product->ID, 'raw' ) ), esc_html__( 'Edit Product', 'pepselect-coa-archive' ) );
	}

	private function render_connect_form( $product_id, $compounds ) {
		echo '<span class="ps-coa-connect-existing"><select data-ps-coa-compound-choice><option value="">' . esc_html__( 'Connect existing…', 'pepselect-coa-archive' ) . '</option>';
		foreach ( $compounds as $compound ) { printf( '<option value="%d">%s</option>', $compound['id'], esc_html( $compound['label'] ) ); }
		echo '</select><button type="button" class="button-link" data-ps-coa-connect-existing data-product-id="' . esc_attr( $product_id ) . '" data-action-base="' . esc_attr( $this->action_url_base( 'connect', $product_id ) ) . '">' . esc_html__( 'Connect Existing Compound', 'pepselect-coa-archive' ) . '</button></span> ';
	}

	private function compound_options() {
		$posts = get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) ); $options = array();
		foreach ( $posts as $post ) { $strength = trim( pepselect_coa_format_number( get_post_meta( $post->ID, 'strength_value', true ) ) . ' ' . get_post_meta( $post->ID, 'strength_unit', true ) ); $linked = absint( get_post_meta( $post->ID, Product_Matching::PRODUCT_ID_META, true ) ); $reports = get_posts( array( 'post_type' => Post_Types::COA_TEST, 'post_status' => array( 'publish', 'draft', 'private', 'pending' ), 'posts_per_page' => -1, 'fields' => 'ids', 'no_found_rows' => true, 'meta_key' => 'compound_id', 'meta_value' => $post->ID ) ); $options[] = array( 'id' => $post->ID, 'label' => sprintf( '%1$s — %2$s — %3$s — %4$d reports%5$s', get_the_title( $post ), $strength ?: __( 'strength not set', 'pepselect-coa-archive' ), ucfirst( $post->post_status ), count( $reports ), $linked ? sprintf( __( ' — linked to product %d', 'pepselect-coa-archive' ), $linked ) : '' ) ); }
		return $options;
	}

	private function render_product_details( $summary, $product_id, $snapshot, $last_sync, $status ) {
		$title = $summary ? $summary['title'] : get_post_meta( get_the_ID(), Product_Matching::PRODUCT_TITLE_META, true ); $sku = $summary ? $summary['sku'] : $snapshot;
		echo '<dl class="ps-coa-product-facts">'; foreach ( array( __( 'WooCommerce product title', 'pepselect-coa-archive' ) => $title, __( 'COA Display Name', 'pepselect-coa-archive' ) => ( $summary ? $summary['coa_display_name'] : '' ), __( 'SKU', 'pepselect-coa-archive' ) => $sku, __( 'Product ID', 'pepselect-coa-archive' ) => $product_id, __( 'Product status', 'pepselect-coa-archive' ) => ( $summary ? $summary['status'] : __( 'Missing', 'pepselect-coa-archive' ) ), __( 'Last synchronized', 'pepselect-coa-archive' ) => $last_sync, __( 'Synchronization status', 'pepselect-coa-archive' ) => $status ) as $label => $value ) { printf( '<div><dt>%s</dt><dd>%s</dd></div>', esc_html( $label ), esc_html( $value ?: '—' ) ); } echo '</dl>';
		if ( $summary ) { printf( '<p><a href="%s">%s</a>%s</p>', esc_url( $summary['edit_url'] ), esc_html__( 'Edit Product', 'pepselect-coa-archive' ), $summary['url'] ? ' · <a href="' . esc_url( $summary['url'] ) . '">' . esc_html__( 'View Product', 'pepselect-coa-archive' ) . '</a>' : '' ); }
	}

	private function action_link( $action, $product_id, $compound_id, $label, $class = '' ) { printf( '<a class="%s" href="%s">%s</a> ', esc_attr( $class ), esc_url( $this->action_url( $action, $product_id, $compound_id ) ), esc_html( $label ) ); }
	private function action_url( $action, $product_id, $compound_id ) { return wp_nonce_url( add_query_arg( array( 'action' => 'pepselect_coa_product_action', 'ps_action' => $action, 'product_id' => absint( $product_id ), 'compound_id' => absint( $compound_id ) ), admin_url( 'admin-post.php' ) ), 'pepselect_coa_product_action_' . $action . '_' . absint( $product_id ) ); }
	private function action_url_base( $action, $product_id ) { return wp_nonce_url( add_query_arg( array( 'action' => 'pepselect_coa_product_action', 'ps_action' => $action, 'product_id' => absint( $product_id ) ), admin_url( 'admin-post.php' ) ), 'pepselect_coa_product_action_' . $action . '_' . absint( $product_id ) ); }
	private function compound_public_url( $compound_id ) { $post = get_post( $compound_id ); return $post ? home_url( user_trailingslashit( 'testing/' . $post->post_name ) ) : ''; }

	private function redirect_with_result( $result ) {
		$error = is_wp_error( $result ); $message = $error ? $result->get_error_message() : ( is_array( $result ) ? $this->sync_message( $result ) : __( 'Product relationship updated.', 'pepselect-coa-archive' ) );
		$url = add_query_arg( array( 'page' => 'pepselect-coa-product-matching', 'ps_message' => $message, 'ps_error' => $error ? 1 : 0 ), admin_url( 'admin.php' ) ); wp_safe_redirect( $url ); exit;
	}

	private function store_notice( $message, $error = false ) { $user_id = get_current_user_id(); if ( $user_id ) { set_transient( 'pepselect_coa_product_notice_' . $user_id, array( 'message' => sanitize_text_field( $message ), 'error' => (bool) $error ), 120 ); } }
	private function sync_message( $result ) { return ! empty( $result['changes'] ) ? sprintf( __( 'Synchronized: %s.', 'pepselect-coa-archive' ), implode( ', ', $result['changes'] ) ) : __( 'WooCommerce relationship is already synchronized.', 'pepselect-coa-archive' ); }
}

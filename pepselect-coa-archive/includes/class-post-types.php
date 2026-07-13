<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Registers plugin-owned post types and reports registration conflicts. */
final class Post_Types {
	const COMPOUND = 'ps_compound';
	const COA_TEST = 'ps_coa_test';

	/** @var string[] */
	private $conflicts = array();

	/** Registers post types that are not already owned by another component. @return void */
	public function register() {
		$this->register_compound();
		$this->register_coa_test();
	}

	/** Registers the shared top-level admin menu before WordPress adds post-type submenus. @return void */
	public function register_admin_menu() {
		add_menu_page(
			__( 'COA Archive', 'pepselect-coa-archive' ),
			__( 'COA Archive', 'pepselect-coa-archive' ),
			'manage_ps_coas',
			'pepselect-coa-archive',
			array( $this, 'redirect_archive_menu' ),
			'dashicons-analytics',
			25
		);
	}

	/** Redirects the parent menu landing page to the compounds list. @return void */
	public function redirect_archive_menu() {
		if ( ! current_user_can( 'manage_ps_coas' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'pepselect-coa-archive' ) );
		}
		wp_safe_redirect( admin_url( 'edit.php?post_type=' . self::COMPOUND ) );
		exit;
	}

	/** Removes WordPress's duplicate parent submenu entry. @return void */
	public function remove_duplicate_parent_submenu() {
		remove_submenu_page( 'pepselect-coa-archive', 'pepselect-coa-archive' );
	}

	/** Displays administrator-only conflict messages. @return void */
	public function render_conflict_notices() {
		if ( empty( $this->conflicts ) || ! current_user_can( 'manage_options' ) ) {
			return;
		}
		foreach ( $this->conflicts as $post_type ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( sprintf( __( 'Pep Select COA Archive could not register “%s” because that post type already exists.', 'pepselect-coa-archive' ), $post_type ) )
			);
		}
	}

	/** Registers compounds. @return void */
	private function register_compound() {
		if ( $this->has_conflict( self::COMPOUND ) ) {
			return;
		}
		register_post_type(
			self::COMPOUND,
			array(
				'labels' => $this->labels( __( 'Compound', 'pepselect-coa-archive' ), __( 'Compounds', 'pepselect-coa-archive' ), __( 'Add New Compound', 'pepselect-coa-archive' ) ),
				'public' => true, 'show_ui' => true, 'show_in_rest' => true,
				'show_in_menu' => 'pepselect-coa-archive',
				'supports' => array( 'title', 'thumbnail', 'custom-fields', 'revisions' ),
				'has_archive' => 'testing', 'rewrite' => array( 'slug' => 'testing', 'with_front' => false ),
				'capability_type' => array( 'ps_coa', 'ps_coas' ), 'map_meta_cap' => true,
				'capabilities' => Capabilities::post_type_map(),
				'menu_position' => 25,
			)
		);
	}

	/** Registers laboratory reports. @return void */
	private function register_coa_test() {
		if ( $this->has_conflict( self::COA_TEST ) ) {
			return;
		}
		register_post_type(
			self::COA_TEST,
			array(
				'labels' => $this->labels( __( 'COA Test', 'pepselect-coa-archive' ), __( 'COA Tests', 'pepselect-coa-archive' ), __( 'Add New Test', 'pepselect-coa-archive' ) ),
				'public' => true, 'show_ui' => true, 'show_in_rest' => true,
				'show_in_menu' => 'pepselect-coa-archive',
				'supports' => array( 'title', 'editor', 'thumbnail', 'custom-fields', 'revisions' ),
				'has_archive' => false, 'rewrite' => array( 'slug' => 'coa-test', 'with_front' => false ),
				'capability_type' => array( 'ps_coa', 'ps_coas' ), 'map_meta_cap' => true,
				'capabilities' => Capabilities::post_type_map(),
			)
		);
	}

	/** Detects a pre-existing post type. @param string $post_type Post type key. @return bool */
	private function has_conflict( $post_type ) {
		if ( post_type_exists( $post_type ) ) {
			$this->conflicts[] = $post_type;
			return true;
		}
		return false;
	}

	/** Returns labels. @param string $singular Singular. @param string $plural Plural. @param string $add_new Add-new label. @return array */
	private function labels( $singular, $plural, $add_new ) {
		return array( 'name' => $plural, 'singular_name' => $singular, 'menu_name' => $plural, 'add_new' => __( 'Add New', 'pepselect-coa-archive' ), 'add_new_item' => $add_new, 'edit_item' => sprintf( __( 'Edit %s', 'pepselect-coa-archive' ), $singular ), 'view_item' => sprintf( __( 'View %s', 'pepselect-coa-archive' ), $singular ), 'search_items' => sprintf( __( 'Search %s', 'pepselect-coa-archive' ), $plural ), 'not_found' => __( 'No records found.', 'pepselect-coa-archive' ) );
	}
}

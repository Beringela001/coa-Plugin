<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Coordinates the plugin's runtime services. */
final class Plugin {
	/** @var Plugin|null */
	private static $instance = null;

	/** @var Post_Types */
	private $post_types;

	/** @var Rewrites */
	private $rewrites;

	/** @var Compound_Fields */
	private $compound_fields;

	/** @var Compound_Validation */
	private $compound_validation;

	/** @var Compound_Admin|null */
	private $compound_admin;

	/** @var Dependencies */
	private $dependencies;

	/** Returns the shared plugin instance. @return Plugin */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/** Registers WordPress hooks. @return void */
	public function run() {
		$this->post_types = new Post_Types();
		$this->rewrites   = new Rewrites();
		$this->dependencies        = new Dependencies();
		$this->compound_fields     = new Compound_Fields( $this->dependencies );
		$this->compound_validation = new Compound_Validation();
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this->post_types, 'register' ), 5 );
		add_action( 'init', array( $this->compound_fields, 'register_rest_meta' ), 20 );
		add_action( 'acf/init', array( $this->compound_fields, 'register' ), 5 );
		add_action( 'acf/init', array( $this->compound_validation, 'register_hooks' ), 10 );
		add_action( 'init', array( $this->rewrites, 'register' ), 10 );
		add_action( 'admin_init', array( 'PepSelect\\COAArchive\\Capabilities', 'ensure_administrator_capabilities' ) );
		add_action( 'admin_menu', array( $this->post_types, 'register_admin_menu' ), 5 );
		add_action( 'admin_menu', array( $this->post_types, 'remove_duplicate_parent_submenu' ), 99 );
		add_action( 'admin_notices', array( $this->post_types, 'render_conflict_notices' ) );
		add_action( 'admin_notices', array( $this->dependencies, 'render_notices' ) );
		if ( is_admin() ) {
			$this->compound_admin = new Compound_Admin();
			$this->compound_admin->register_hooks();
		}
	}

	/** Loads translations. @return void */
	public function load_textdomain() {
		load_plugin_textdomain( 'pepselect-coa-archive', false, dirname( plugin_basename( PEPSELECT_COA_ARCHIVE_FILE ) ) . '/languages' );
	}

	/** Prevents direct construction. */
	private function __construct() {}
}

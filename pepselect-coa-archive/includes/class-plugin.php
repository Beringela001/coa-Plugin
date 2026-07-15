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

	/** @var COA_Test_Fields */
	private $coa_test_fields;

	/** @var COA_Test_Validation */
	private $coa_test_validation;

	/** @var COA_Test_Service */
	private $coa_test_service;

	/** @var COA_Test_Admin|null */
	private $coa_test_admin;
	/** @var COA_Test_Form|null */
	private $coa_test_form;

	/** @var COA_Test_Importer|null */
	private $coa_test_importer;

	/** @var Frontend_Router */
	private $frontend_router;

	/** @var Frontend_Template_Loader */
	private $frontend_templates;

	/** @var Design_Settings_Admin|null */
	private $design_settings_admin;

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
		$this->coa_test_fields      = new COA_Test_Fields( $this->dependencies );
		$this->coa_test_validation  = new COA_Test_Validation();
		$this->coa_test_service     = new COA_Test_Service();
		$frontend_visibility        = new Frontend_Visibility();
		$compound_repository        = new Compound_Repository( $frontend_visibility );
		$coa_test_repository        = new COA_Test_Repository( $frontend_visibility );
		$frontend_view_model        = new Frontend_View_Model();
		$this->frontend_router      = new Frontend_Router( new Frontend_Query(), $compound_repository, $coa_test_repository, $frontend_view_model );
		$this->frontend_templates   = new Frontend_Template_Loader( $this->frontend_router );
		Archive_Cache::register_hooks();
		add_action( 'init', array( $this, 'load_textdomain' ), 0 );
		add_action( 'init', array( $this->post_types, 'register' ), 5 );
		if ( false === has_action( 'init', array( $this->compound_fields, 'register_rest_meta' ) ) ) {
			add_action( 'init', array( $this->compound_fields, 'register_rest_meta' ), 20 );
		}
		if ( false === has_action( 'acf/init', array( $this->compound_fields, 'register' ) ) ) {
			add_action( 'acf/init', array( $this->compound_fields, 'register' ) );
		}
		add_action( 'acf/init', array( $this->compound_validation, 'register_hooks' ), 10 );
		add_action( 'init', array( $this->coa_test_fields, 'register_rest_meta' ), 20 );
		add_action( 'acf/init', array( $this->coa_test_fields, 'register' ) );
		add_action( 'acf/init', array( $this->coa_test_validation, 'register_hooks' ), 10 );
		add_action( 'acf/save_post', array( $this->coa_test_service, 'after_save' ), 30 );
		add_filter( 'acf/load_value/key=field_ps_coa_test_lab_verification_url', array( $this->coa_test_service, 'load_verification_url' ), 10, 3 );
		add_action( 'init', array( $this->rewrites, 'register' ), 10 );
		add_filter( 'query_vars', array( $this->rewrites, 'register_query_vars' ) );
		add_action( 'init', array( 'PepSelect\\COAArchive\\Upgrade', 'maybe_upgrade' ), 99 );
		add_action( 'wp', array( $this->frontend_router, 'resolve' ) );
		add_action( 'template_redirect', array( $this->frontend_router, 'protect_legacy_routes' ), 1 );
		$this->frontend_templates->register_hooks();
		add_action( 'admin_init', array( 'PepSelect\\COAArchive\\Capabilities', 'ensure_administrator_capabilities' ) );
		add_action( 'admin_menu', array( $this->post_types, 'register_admin_menu' ), 5 );
		add_action( 'admin_menu', array( $this->post_types, 'remove_duplicate_parent_submenu' ), 99 );
		add_action( 'admin_notices', array( $this->post_types, 'render_conflict_notices' ) );
		add_action( 'admin_notices', array( $this->dependencies, 'render_notices' ) );
		add_action( 'admin_notices', array( $this->coa_test_service, 'render_future_date_notice' ) );
		add_action( 'admin_notices', array( $this->coa_test_service, 'render_workflow_notices' ) );
		if ( is_admin() ) {
			$this->coa_test_form = new COA_Test_Form();
			$this->coa_test_form->register_hooks();
			$this->design_settings_admin = new Design_Settings_Admin();
			$this->design_settings_admin->register_hooks();
			$this->compound_admin = new Compound_Admin();
			$this->compound_admin->register_hooks();
			$this->coa_test_admin = new COA_Test_Admin();
			$this->coa_test_admin->register_hooks();
			$this->coa_test_importer = new COA_Test_Importer();
			$this->coa_test_importer->register_hooks();
		}
	}

	/** Loads translations. @return void */
	public function load_textdomain() {
		load_plugin_textdomain( 'pepselect-coa-archive', false, dirname( plugin_basename( PEPSELECT_COA_ARCHIVE_FILE ) ) . '/languages' );
	}

	/** Prevents direct construction. */
	private function __construct() {}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Renders read-only validation guidance on COA Test Add/Edit screens. */
final class COA_Workflow_Requirements {
	/** Registers edit-screen-only hooks. @return void */
	public function register_hooks() {
		add_action( 'add_meta_boxes_' . Post_Types::COA_TEST, array( $this, 'register_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** Registers the capability-protected guidance panel. @param \WP_Post $post Current post. @return void */
	public function register_metabox( $post ) {
		if ( ! $post || Post_Types::COA_TEST !== $post->post_type || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
		add_meta_box( 'pepselect-coa-workflow-requirements', __( 'Workflow Requirements', 'pepselect-coa-archive' ), array( $this, 'render' ), Post_Types::COA_TEST, 'side', 'high' );
	}

	/** Loads the checklist stylesheet only on COA Test Add/Edit screens. @param string $hook Admin hook. @return void */
	public function enqueue_assets( $hook ) {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Types::COA_TEST !== $screen->post_type ) { return; }
		wp_enqueue_style( 'pepselect-coa-workflow-requirements', plugins_url( 'assets/css/pepselect-coa-workflow-requirements.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
		wp_enqueue_script( 'pepselect-coa-workflow-requirements', plugins_url( 'assets/js/pepselect-coa-workflow-requirements.js', PEPSELECT_COA_ARCHIVE_FILE ), array( 'jquery', 'acf-input' ), PEPSELECT_COA_ARCHIVE_VERSION, true );
		$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$models = array();
		foreach ( array_keys( COA_Workflow::stages() ) as $stage ) {
			foreach ( array_keys( COA_Test_Fields::statuses() ) as $status ) {
				foreach ( array( 'default' => '', 'ils-labs' => 'ils-labs', 'other' => 'other' ) as $scope => $lab ) {
					$models[ $stage . '|' . $status . '|' . $scope ] = array(
						'stage' => COA_Admin_Workflow::stage_label( $stage ),
						'status' => COA_Test_Fields::statuses()[ $status ],
						'guidance' => COA_Test_Validation::workflow_guidance( $stage, $status ),
						'items' => COA_Test_Validation::workflow_requirements( $post_id, $stage, $status, $lab ),
					);
				}
			}
		}
		wp_localize_script( 'pepselect-coa-workflow-requirements', 'PepSelectCOAWorkflowRequirements', array( 'models' => $models, 'states' => array( 'complete' => __( 'Complete', 'pepselect-coa-archive' ), 'missing' => __( 'Missing', 'pepselect-coa-archive' ), 'not-required' => __( 'Not required yet', 'pepselect-coa-archive' ), 'optional' => __( 'Optional', 'pepselect-coa-archive' ) ) ) );
	}

	/** Renders saved-state guidance; ACF remains the only form and validator. @param \WP_Post $post Current post. @return void */
	public function render( $post ) {
		if ( ! $post || ! current_user_can( 'edit_post', $post->ID ) ) { return; }
		$stage = COA_Workflow::stage( $post->ID );
		$outcome = COA_Workflow::outcome( $post->ID );
		$status = array_key_exists( $outcome, COA_Test_Fields::statuses() ) ? $outcome : 'pending';
		$status_labels = COA_Test_Fields::accepted_statuses();
		$ps_coa_requirements = array(
			'stage' => COA_Admin_Workflow::stage_label( $stage ),
			'status' => isset( $status_labels[ $outcome ] ) ? $status_labels[ $outcome ] : COA_Test_Fields::statuses()[ $status ],
			'guidance' => COA_Test_Validation::workflow_guidance( $stage, $status ),
			'items' => COA_Test_Validation::workflow_requirements( $post->ID, $stage, $status ),
		);
		$template = pepselect_coa_template_path( 'admin/workflow-requirements.php' );
		if ( is_readable( $template ) ) { include $template; }
	}
}

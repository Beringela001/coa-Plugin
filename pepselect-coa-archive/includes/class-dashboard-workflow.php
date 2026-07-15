<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Provides the capability-protected COA operations summary on the WordPress Dashboard. */
final class Dashboard_Workflow {
	const WIDGET_ID = 'pepselect-coa-workflow-center';
	const LIMIT     = 10;

	/** Registers dashboard-only hooks. @return void */
	public function register_hooks() {
		add_action( 'wp_dashboard_setup', array( $this, 'register_widget' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** Registers the widget only for users who may edit COA records. @return void */
	public function register_widget() {
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		wp_add_dashboard_widget( self::WIDGET_ID, __( 'COA Workflow Center', 'pepselect-coa-archive' ), array( $this, 'render' ) );
	}

	/** Loads the widget stylesheet only on the main Dashboard. @param string $hook_suffix Admin hook. @return void */
	public function enqueue_assets( $hook_suffix ) {
		if ( 'index.php' !== $hook_suffix || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		wp_enqueue_style( 'pepselect-coa-dashboard-workflow', plugins_url( 'assets/css/pepselect-coa-dashboard-workflow.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
	}

	/** Renders the dashboard widget. @return void */
	public function render() {
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$ps_coa_dashboard = $this->build_view_model();
		$template = pepselect_coa_template_path( 'admin/dashboard-workflow.php' );
		if ( is_readable( $template ) ) { include $template; }
	}

	/**
	 * Builds counters, urgency-sorted records, and permission-gated actions.
	 *
	 * The optional date exists solely to make calendar-day behavior deterministic
	 * in unit tests. Production calls always use the WordPress site timezone.
	 *
	 * @param \DateTimeImmutable|null $today Site-local date override.
	 * @return array
	 */
	public function build_view_model( ?\DateTimeImmutable $today = null ) {
		$model = array(
			'counters' => array( 'vendor-vetting' => 0, 'waiting-on-vendor' => 0, 'submitted-to-lab' => 0, 'in-testing' => 0, 'overdue' => 0 ),
			'rows' => array(), 'total' => 0, 'has_more' => false, 'next_expected' => null, 'actions' => array(),
		);
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) ) { return $model; }

		$timezone = wp_timezone();
		$today = $today ? $today->setTimezone( $timezone )->setTime( 0, 0, 0 ) : current_datetime()->setTime( 0, 0, 0 );
		$rows = $this->active_rows( $today );
		usort( $rows, array( $this, 'compare_rows' ) );
		foreach ( $rows as $row ) {
			++$model['counters'][ $row['stage'] ];
			if ( $row['overdue'] ) { ++$model['counters']['overdue']; }
			if ( $row['expected_date'] && $row['expected_date'] >= $today && ( ! $model['next_expected'] || $row['expected_date'] < $model['next_expected']['date'] ) ) {
				$model['next_expected'] = array( 'date' => $row['expected_date'], 'label' => wp_date( 'F j, Y', $row['expected_date']->getTimestamp(), $timezone ) );
			}
		}
		$model['total'] = count( $rows );
		$model['has_more'] = $model['total'] > self::LIMIT;
		$model['rows'] = array_slice( $rows, 0, self::LIMIT );
		$model['actions'] = $this->actions();
		return $model;
	}

	/** Returns the active operational rows visible to the current user. @param \DateTimeImmutable $today Site-local date. @return array */
	private function active_rows( \DateTimeImmutable $today ) {
		$ids = get_posts(
			array(
				'post_type' => Post_Types::COA_TEST,
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
				'no_found_rows' => true,
				'suppress_filters' => false,
				'meta_query' => array( array( 'key' => 'coa_status', 'value' => array( 'pending', 'in-testing', 'vendor-vetting' ), 'compare' => 'IN' ) ),
			)
		);
		$ids = array_map( 'absint', $ids );
		if ( ! $ids ) { return array(); }
		_prime_post_caches( $ids, false, false );
		update_meta_cache( 'post', $ids );
		$compound_ids = array_values( array_unique( array_filter( array_map( static function ( $id ) { return absint( get_post_meta( $id, 'compound_id', true ) ); }, $ids ) ) ) );
		if ( $compound_ids ) { _prime_post_caches( $compound_ids, false, false ); update_meta_cache( 'post', $compound_ids ); }

		$rows = array();
		foreach ( $ids as $id ) {
			if ( ! current_user_can( 'edit_post', $id ) || 'pending' !== COA_Workflow::outcome( $id ) ) { continue; }
			$stage = COA_Workflow::stage( $id );
			if ( ! COA_Workflow::is_incoming_stage( $stage ) ) { continue; }
			$post = get_post( $id );
			$compound_id = absint( get_post_meta( $id, 'compound_id', true ) );
			$compound = $compound_id ? get_post( $compound_id ) : null;
			$expected = $this->parse_date( get_post_meta( $id, 'expected_coa_date', true ) );
			$overdue = $expected && in_array( $stage, array( 'submitted-to-lab', 'in-testing' ), true ) && $expected < $today;
			$days_until = $expected ? (int) $today->diff( $expected )->format( '%r%a' ) : null;
			$rows[] = array(
				'id' => $id,
				'compound_id' => $compound_id,
				'compound_name' => $this->compound_name( $compound, $post ),
				'stage' => $stage,
				'stage_label' => $this->stage_label( $stage ),
				'stage_tone' => $this->stage_tone( $stage ),
				'batch' => trim( (string) get_post_meta( $id, 'batch_number', true ) ),
				'expected_date' => $expected,
				'expected_label' => $expected ? wp_date( 'M j, Y', $expected->getTimestamp(), wp_timezone() ) : '',
				'overdue' => (bool) $overdue,
				'overdue_days' => $overdue ? abs( $days_until ) : 0,
				'due_soon' => ! $overdue && null !== $days_until && $days_until >= 0 && $days_until <= 3,
				'modified' => $post ? (string) $post->post_modified_gmt : '',
				'edit_url' => get_edit_post_link( $id, 'raw' ),
			);
		}
		return $rows;
	}

	/** Urgency order: overdue/date, stage, date, modified, then ID. @return int */
	private function compare_rows( $left, $right ) {
		if ( $left['overdue'] !== $right['overdue'] ) { return $left['overdue'] ? -1 : 1; }
		if ( $left['overdue'] ) {
			$date = $left['expected_date'] <=> $right['expected_date'];
			if ( 0 !== $date ) { return $date; }
		}
		$stage = COA_Workflow::priority( $left['stage'] ) <=> COA_Workflow::priority( $right['stage'] );
		if ( 0 !== $stage ) { return $stage; }
		if ( (bool) $left['expected_date'] !== (bool) $right['expected_date'] ) { return $left['expected_date'] ? -1 : 1; }
		if ( $left['expected_date'] && $right['expected_date'] ) {
			$date = $left['expected_date'] <=> $right['expected_date'];
			if ( 0 !== $date ) { return $date; }
		}
		$modified = strcmp( $right['modified'], $left['modified'] );
		return 0 !== $modified ? $modified : ( $right['id'] <=> $left['id'] );
	}

	/** Parses ACF Ymd and ISO dates strictly in the WordPress site timezone. @param mixed $value Stored date. @return \DateTimeImmutable|null */
	private function parse_date( $value ) {
		$value = trim( (string) $value );
		$format = preg_match( '/^\d{8}$/', $value ) ? '!Ymd' : ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? '!Y-m-d' : '' );
		if ( ! $format ) { return null; }
		$date = \DateTimeImmutable::createFromFormat( $format, $value, wp_timezone() );
		$errors = \DateTimeImmutable::getLastErrors();
		return $date && ( false === $errors || ( 0 === $errors['warning_count'] && 0 === $errors['error_count'] ) ) ? $date : null;
	}

	/** Returns the administrative compound display name without enforcing public visibility. @return string */
	private function compound_name( $compound, $test ) {
		if ( $compound && Post_Types::COMPOUND === $compound->post_type ) {
			$name = trim( (string) get_post_meta( $compound->ID, 'display_name', true ) );
			if ( '' === $name ) { $name = trim( (string) get_post_meta( $compound->ID, 'compound_name', true ) ); }
			if ( '' === $name ) { $name = trim( (string) $compound->post_title ); }
			if ( '' !== $name ) { return $name; }
		}
		return $test && trim( (string) $test->post_title ) ? trim( (string) $test->post_title ) : __( 'Unknown compound', 'pepselect-coa-archive' );
	}

	/** Returns dashboard-specific normalized labels without changing public workflow copy. @return string */
	private function stage_label( $stage ) {
		$labels = array( 'vendor-vetting' => __( 'Vendor Vetting', 'pepselect-coa-archive' ), 'waiting-on-vendor' => __( 'Waiting on Vendor', 'pepselect-coa-archive' ), 'submitted-to-lab' => __( 'Submitted to Laboratory', 'pepselect-coa-archive' ), 'in-testing' => __( 'Verification in Progress', 'pepselect-coa-archive' ) );
		return isset( $labels[ $stage ] ) ? $labels[ $stage ] : $stage;
	}

	/** Returns a presentation-only class suffix that does not expose stored stage keys. @return string */
	private function stage_tone( $stage ) {
		$tones = array( 'vendor-vetting' => 'vendor', 'waiting-on-vendor' => 'waiting', 'submitted-to-lab' => 'submitted', 'in-testing' => 'testing' );
		return isset( $tones[ $stage ] ) ? $tones[ $stage ] : 'neutral';
	}

	/** Returns only actions the current user may perform. @return array */
	private function actions() {
		$actions = array();
		if ( current_user_can( 'create_ps_coas' ) ) { $actions['add'] = array( 'label' => __( 'Add New COA Test', 'pepselect-coa-archive' ), 'url' => admin_url( 'post-new.php?post_type=' . Post_Types::COA_TEST ) ); }
		if ( current_user_can( 'edit_ps_coas' ) ) { $actions['view'] = array( 'label' => __( 'View All COA Tests', 'pepselect-coa-archive' ), 'url' => admin_url( 'edit.php?post_type=' . Post_Types::COA_TEST ) ); }
		if ( current_user_can( 'manage_ps_compounds' ) ) { $actions['matching'] = array( 'label' => __( 'Product Matching', 'pepselect-coa-archive' ), 'url' => admin_url( 'admin.php?page=pepselect-coa-product-matching' ) ); }
		return $actions;
	}
}

<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Customizes only the COA Test administration list. */
final class COA_Test_Admin {
	/** Registers list-table hooks. @return void */
	public function register_hooks() {
		add_filter( 'manage_' . Post_Types::COA_TEST . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Post_Types::COA_TEST . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . Post_Types::COA_TEST . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_query_controls' ) );
		add_filter( 'posts_clauses', array( $this, 'apply_sort_clauses' ), 10, 2 );
		add_filter( 'the_posts', array( $this, 'prime_compound_cache' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** Loads list presentation only on the COA Tests list. @param string $hook Admin hook. @return void */
	public function enqueue_assets( $hook ) {
		if ( 'edit.php' !== $hook || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || Post_Types::COA_TEST !== $screen->post_type ) { return; }
		wp_enqueue_style( 'pepselect-coa-test-list', plugins_url( 'assets/css/pepselect-coa-test-list.css', PEPSELECT_COA_ARCHIVE_FILE ), array(), PEPSELECT_COA_ARCHIVE_VERSION );
	}

	/** Defines COA Test columns. @param array $columns Existing columns. @return array */
	public function columns( $columns ) {
		return array( 'cb' => $columns['cb'], 'title' => __( 'COA Test / Title', 'pepselect-coa-archive' ), 'compound_id' => __( 'Compound', 'pepselect-coa-archive' ), 'batch_number' => __( 'Batch', 'pepselect-coa-archive' ), 'workflow_stage' => __( 'Workflow Stage', 'pepselect-coa-archive' ), 'expected_coa_date' => __( 'Expected COA', 'pepselect-coa-archive' ), 'test_date' => __( 'Test Date', 'pepselect-coa-archive' ), 'testing_lab' => __( 'Lab', 'pepselect-coa-archive' ), 'purity_percentage' => __( 'Purity', 'pepselect-coa-archive' ), 'vials_tested' => __( 'Vials', 'pepselect-coa-archive' ), 'coa_status' => __( 'Status', 'pepselect-coa-archive' ), 'is_current' => __( 'Current', 'pepselect-coa-archive' ), 'coa_pdf_id' => __( 'PDF', 'pepselect-coa-archive' ), 'date' => isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'pepselect-coa-archive' ) );
	}

	/** Renders one custom column. @param string $column Column. @param int $post_id Test ID. @return void */
	public function render_column( $column, $post_id ) {
		$value = get_post_meta( $post_id, $column, true );
		if ( 'compound_id' === $column ) { $compound = $value ? get_post( absint( $value ) ) : null; if ( ! $compound ) { echo '&mdash;'; } elseif ( current_user_can( 'edit_post', $compound->ID ) ) { printf( '<a href="%1$s">%2$s</a>', esc_url( get_edit_post_link( $compound->ID ) ), esc_html( get_the_title( $compound ) ) ); } else { echo esc_html( get_the_title( $compound ) ); } }
		elseif ( 'batch_number' === $column ) { echo '' !== $value ? esc_html( $value ) : '&mdash;'; }
		elseif ( 'workflow_stage' === $column ) { $stage = COA_Workflow::stage( $post_id ); echo esc_html( COA_Admin_Workflow::stage_label( $stage ) ); }
		elseif ( 'expected_coa_date' === $column ) {
			$date = COA_Admin_Workflow::parse_date( $value );
			echo $date ? esc_html( wp_date( get_option( 'date_format' ), $date->getTimestamp(), wp_timezone() ) ) : '&mdash;';
			$timing = COA_Admin_Workflow::timing( $post_id );
			if ( 'overdue' === $timing['status'] ) { printf( '<span class="ps-coa-list-timing ps-coa-list-timing--overdue">%s</span>', esc_html( sprintf( _n( 'Overdue by %d day', 'Overdue by %d days', abs( $timing['days'] ), 'pepselect-coa-archive' ), abs( $timing['days'] ) ) ) ); }
			elseif ( 'due-soon' === $timing['status'] ) { printf( '<span class="ps-coa-list-timing ps-coa-list-timing--due-soon">%s</span>', esc_html__( 'Due Soon', 'pepselect-coa-archive' ) ); }
		}
		elseif ( 'test_date' === $column ) { $date = COA_Admin_Workflow::parse_date( $value ); echo $date ? esc_html( wp_date( get_option( 'date_format' ), $date->getTimestamp(), wp_timezone() ) ) : '&mdash;'; }
		elseif ( 'testing_lab' === $column ) { $labs = COA_Test_Fields::labs(); $label = isset( $labs[ $value ] ) ? $labs[ $value ] : ''; if ( 'other' === $value && get_post_meta( $post_id, 'other_testing_lab', true ) ) { $label = get_post_meta( $post_id, 'other_testing_lab', true ); } echo $label ? esc_html( $label ) : '&mdash;'; }
		elseif ( 'purity_percentage' === $column ) { echo '' !== (string) $value ? esc_html( $value . '%' ) : '&mdash;'; }
		elseif ( 'vials_tested' === $column ) { echo '' !== (string) $value ? esc_html( (string) absint( $value ) ) : '&mdash;'; }
		elseif ( 'coa_status' === $column ) { $indicator = $this->status_indicator( $post_id ); printf( '<span class="ps-coa-status ps-coa-status--%1$s">%2$s</span>', esc_attr( $indicator['class'] ), esc_html( $indicator['label'] ) ); }
		elseif ( 'is_current' === $column ) { echo $value ? esc_html__( 'Current', 'pepselect-coa-archive' ) : '&mdash;'; }
		elseif ( 'coa_pdf_id' === $column ) { echo $value && 'application/pdf' === get_post_mime_type( absint( $value ) ) ? esc_html__( 'Available', 'pepselect-coa-archive' ) : esc_html__( 'Missing', 'pepselect-coa-archive' ); }
	}

	/** Defines sortable columns. @param array $columns Existing. @return array */
	public function sortable_columns( $columns ) { foreach ( array( 'workflow_stage', 'expected_coa_date', 'test_date', 'batch_number', 'compound_id', 'testing_lab', 'purity_percentage', 'coa_status', 'is_current' ) as $key ) { $columns[ $key ] = $key; } return $columns; }

	/** Renders scoped list filters. @param string $post_type Current type. @return void */
	public function render_filters( $post_type ) {
		if ( Post_Types::COA_TEST !== $post_type || ! current_user_can( 'edit_ps_coas' ) ) { return; }
		$selected_compound = $this->request( 'ps_compound' );
		$this->choice_filter( 'ps_workflow_stage', __( 'All Workflow Stages', 'pepselect-coa-archive' ), $this->stage_choices() );
		$this->choice_filter( 'ps_coa_status', __( 'All COA Statuses', 'pepselect-coa-archive' ), COA_Test_Fields::statuses() );
		echo '<select name="ps_compound"><option value="">' . esc_html__( 'All Compounds', 'pepselect-coa-archive' ) . '</option>';
		foreach ( get_posts( array( 'post_type' => Post_Types::COMPOUND, 'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ), 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true ) ) as $compound ) { printf( '<option value="%1$d" %2$s>%3$s</option>', $compound->ID, selected( absint( $selected_compound ), $compound->ID, false ), esc_html( get_the_title( $compound ) ) ); }
		echo '</select>';
		$this->choice_filter( 'ps_lab', __( 'All Laboratories', 'pepselect-coa-archive' ), COA_Test_Fields::labs() );
		$this->choice_filter( 'ps_timing', __( 'All Timing Statuses', 'pepselect-coa-archive' ), array( 'due-soon' => __( 'Due Soon', 'pepselect-coa-archive' ), 'overdue' => __( 'Overdue', 'pepselect-coa-archive' ), 'no-date' => __( 'No Expected Date', 'pepselect-coa-archive' ) ) );
		$this->choice_filter( 'ps_current', __( 'All current states', 'pepselect-coa-archive' ), array( '1' => __( 'Current', 'pepselect-coa-archive' ), '0' => __( 'Not Current', 'pepselect-coa-archive' ) ) );
		$current_year = (int) wp_date( 'Y' ); $years = array(); for ( $year = $current_year; $year >= $current_year - 10; $year-- ) { $years[ (string) $year ] = (string) $year; }
		$this->choice_filter( 'ps_test_year', __( 'All test years', 'pepselect-coa-archive' ), $years );
	}

	/** Applies controls only to the main COA Test list query. @param \WP_Query $query Query. @return void */
	public function apply_query_controls( $query ) {
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) || ! $query->is_main_query() || Post_Types::COA_TEST !== $query->get( 'post_type' ) ) { return; }
		$orderby = sanitize_key( (string) $query->get( 'orderby' ) ); $numeric = array( 'compound_id', 'purity_percentage', 'is_current' );
		if ( in_array( $orderby, array( 'expected_coa_date', 'test_date' ), true ) ) { $query->set( 'ps_coa_date_sort', $orderby ); }
		elseif ( 'compound_id' === $orderby ) { $query->set( 'ps_coa_compound_sort', 1 ); }
		elseif ( in_array( $orderby, array( 'workflow_stage', 'testing_lab', 'batch_number', 'purity_percentage', 'coa_status', 'is_current' ), true ) ) { $query->set( 'meta_key', $orderby ); $query->set( 'orderby', in_array( $orderby, $numeric, true ) ? 'meta_value_num' : 'meta_value' ); }
		$meta = array( 'relation' => 'AND' );
		if ( '' === $orderby ) { $meta[] = array( 'relation' => 'OR', 'test_date_clause' => array( 'key' => 'test_date', 'compare' => 'EXISTS' ), 'no_test_date_clause' => array( 'key' => 'test_date', 'compare' => 'NOT EXISTS' ) ); $query->set( 'orderby', array( 'test_date_clause' => 'DESC', 'date' => 'DESC' ) ); }
		$filters = array( 'ps_compound' => 'compound_id', 'ps_coa_status' => 'coa_status', 'ps_current' => 'is_current', 'ps_lab' => 'testing_lab' );
		foreach ( $filters as $request => $key ) {
			$value = $this->request( $request );
			if ( '' === $value ) { continue; }
			if ( 'is_current' === $key && '0' === $value ) { $meta[] = array( 'relation' => 'OR', array( 'key' => 'is_current', 'value' => '0' ), array( 'key' => 'is_current', 'compare' => 'NOT EXISTS' ) ); }
			else { $meta[] = array( 'key' => $key, 'value' => 'compound_id' === $key ? absint( $value ) : $value ); }
		}
		$stage = $this->request( 'ps_workflow_stage' );
		if ( array_key_exists( $stage, COA_Workflow::stages() ) ) {
			$stage_query = array( 'relation' => 'OR', array( 'key' => 'workflow_stage', 'value' => $stage ) );
			if ( 'complete' === $stage ) { $stage_query[] = array( 'relation' => 'AND', array( 'key' => 'workflow_stage', 'compare' => 'NOT EXISTS' ), array( 'key' => 'coa_status', 'value' => array( 'approved', 'failed', 'archived', 'superseded' ), 'compare' => 'IN' ) ); }
			elseif ( 'vendor-vetting' === $stage ) { $stage_query[] = array( 'relation' => 'AND', array( 'key' => 'workflow_stage', 'compare' => 'NOT EXISTS' ), array( 'key' => 'coa_status', 'value' => array( 'pending', 'vendor-vetting' ), 'compare' => 'IN' ) ); }
			elseif ( 'in-testing' === $stage ) { $stage_query[] = array( 'relation' => 'AND', array( 'key' => 'workflow_stage', 'compare' => 'NOT EXISTS' ), array( 'key' => 'coa_status', 'value' => 'in-testing' ) ); }
			if ( 'submitted-to-lab' === $stage ) { $stage_query[] = array( 'key' => 'workflow_stage', 'value' => 'sample-received' ); }
			if ( 'in-testing' === $stage ) { $stage_query[] = array( 'key' => 'workflow_stage', 'value' => 'coa-pending' ); }
			$meta[] = $stage_query;
		}
		$year = $this->request( 'ps_test_year' ); if ( preg_match( '/^\d{4}$/', $year ) ) { $meta[] = array( 'key' => 'test_date', 'value' => array( $year . '-01-01', $year . '-12-31' ), 'compare' => 'BETWEEN', 'type' => 'DATE' ); }
		$timing = $this->request( 'ps_timing' );
		if ( 'no-date' === $timing ) { $meta[] = array( 'relation' => 'OR', array( 'key' => 'expected_coa_date', 'compare' => 'NOT EXISTS' ), array( 'key' => 'expected_coa_date', 'value' => '' ) ); }
		elseif ( in_array( $timing, array( 'due-soon', 'overdue' ), true ) ) {
			$matches = $this->timing_ids( $timing );
			$existing = array_filter( array_map( 'absint', (array) $query->get( 'post__in' ) ) );
			$combined = $existing ? array_values( array_intersect( $existing, $matches ) ) : $matches;
			$query->set( 'post__in', $combined ? $combined : array( 0 ) );
		}
		if ( count( $meta ) > 1 ) { $query->set( 'meta_query', $meta ); }
	}

	/** Applies chronological date and compound-name ordering without excluding empty values. @param array $clauses SQL clauses. @param \WP_Query $query Query. @return array */
	public function apply_sort_clauses( $clauses, $query ) {
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) || ! $query->is_main_query() || Post_Types::COA_TEST !== $query->get( 'post_type' ) ) { return $clauses; }
		global $wpdb;
		$direction = 'ASC' === strtoupper( (string) $query->get( 'order' ) ) ? 'ASC' : 'DESC';
		$date_key = sanitize_key( (string) $query->get( 'ps_coa_date_sort' ) );
		if ( in_array( $date_key, array( 'expected_coa_date', 'test_date' ), true ) ) {
			$alias = 'ps_coa_sort_date';
			$clauses['join'] .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} AS {$alias} ON ({$wpdb->posts}.ID = {$alias}.post_id AND {$alias}.meta_key = %s)", $date_key );
			$normalized = "REPLACE({$alias}.meta_value, '-', '')";
			$clauses['orderby'] = "CASE WHEN {$normalized} REGEXP '^[0-9]{8}$' THEN 0 ELSE 1 END ASC, {$normalized} {$direction}, {$wpdb->posts}.post_date DESC";
		}
		elseif ( $query->get( 'ps_coa_compound_sort' ) ) {
			$clauses['join'] .= $wpdb->prepare( " LEFT JOIN {$wpdb->postmeta} AS ps_coa_sort_compound_meta ON ({$wpdb->posts}.ID = ps_coa_sort_compound_meta.post_id AND ps_coa_sort_compound_meta.meta_key = %s) LEFT JOIN {$wpdb->posts} AS ps_coa_sort_compound ON (ps_coa_sort_compound.ID = CAST(ps_coa_sort_compound_meta.meta_value AS UNSIGNED))", 'compound_id' );
			$clauses['orderby'] = "CASE WHEN ps_coa_sort_compound.post_title IS NULL OR ps_coa_sort_compound.post_title = '' THEN 1 ELSE 0 END ASC, ps_coa_sort_compound.post_title {$direction}, {$wpdb->posts}.post_date DESC";
		}
		return $clauses;
	}

	/** Primes linked Compound objects once for the current COA Tests page. @param \WP_Post[] $posts Results. @param \WP_Query $query Query. @return \WP_Post[] */
	public function prime_compound_cache( $posts, $query ) {
		if ( ! is_admin() || ! current_user_can( 'edit_ps_coas' ) || ! $query->is_main_query() || Post_Types::COA_TEST !== $query->get( 'post_type' ) || ! $posts ) { return $posts; }
		$ids = array_values( array_unique( array_filter( array_map( static function ( $post ) { return absint( get_post_meta( $post->ID, 'compound_id', true ) ); }, $posts ) ) ) );
		if ( $ids ) { _prime_post_caches( $ids, false, false ); }
		return $posts;
	}

	/** Returns only active laboratory-stage IDs matching the shared timing classifier. @param string $status Timing status. @return int[] */
	private function timing_ids( $status ) {
		$ids = get_posts(
			array(
				'post_type' => Post_Types::COA_TEST,
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page' => -1,
				'fields' => 'ids',
				'no_found_rows' => true,
				'suppress_filters' => false,
				'meta_query' => array(
					'relation' => 'AND',
					array( 'key' => 'coa_status', 'value' => array( 'pending', 'in-testing', 'vendor-vetting' ), 'compare' => 'IN' ),
					array( 'relation' => 'OR', array( 'key' => 'workflow_stage', 'value' => array( 'submitted-to-lab', 'in-testing', 'sample-received', 'coa-pending' ), 'compare' => 'IN' ), array( 'key' => 'workflow_stage', 'compare' => 'NOT EXISTS' ) ),
					array( 'key' => 'expected_coa_date', 'value' => '', 'compare' => '!=' ),
				),
			)
		);
		$ids = array_map( 'absint', $ids );
		if ( $ids ) { update_meta_cache( 'post', $ids ); }
		$matches = array();
		foreach ( $ids as $id ) { if ( current_user_can( 'edit_post', $id ) && $status === COA_Admin_Workflow::timing( $id )['status'] ) { $matches[] = $id; } }
		return $matches ? $matches : array( 0 );
	}

	/** Returns normalized filter labels in operational order. @return array */
	private function stage_choices() {
		$choices = array();
		foreach ( array( 'vendor-vetting', 'waiting-on-vendor', 'submitted-to-lab', 'in-testing', 'complete' ) as $stage ) { $choices[ $stage ] = COA_Admin_Workflow::stage_label( $stage ); }
		return $choices;
	}

	/** Renders a select filter. @param string $name Name. @param string $all All label. @param array $choices Choices. @return void */
	private function choice_filter( $name, $all, $choices ) { $value = $this->request( $name ); printf( '<select name="%1$s"><option value="">%2$s</option>', esc_attr( $name ), esc_html( $all ) ); foreach ( $choices as $key => $label ) { printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $value, (string) $key, false ), esc_html( $label ) ); } echo '</select>'; }
	/** Returns the readable administrative status indicator. @param int $post_id Test ID. @return array */
	private function status_indicator( $post_id ) {
		$outcome = COA_Workflow::outcome( $post_id );
		$stage = COA_Workflow::stage( $post_id );
		if ( 'archived' === $outcome || 'superseded' === $outcome ) { return array( 'class' => $outcome, 'label' => ucfirst( $outcome ) ); }
		if ( 'failed' === $outcome ) { return array( 'class' => 'failed', 'label' => __( 'Failed', 'pepselect-coa-archive' ) ); }
		if ( 'approved' === $outcome && 'complete' === $stage ) { return array( 'class' => 'approved', 'label' => __( 'Approved', 'pepselect-coa-archive' ) ); }
		if ( 'vendor-vetting' === $stage ) { return array( 'class' => 'vendor', 'label' => __( 'Vendor Vetting', 'pepselect-coa-archive' ) ); }
		return array( 'class' => 'incoming', 'label' => __( 'Incoming', 'pepselect-coa-archive' ) );
	}
	/** Returns a sanitized filter request. @param string $key Key. @return string */
	private function request( $key ) { return isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : ''; } // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

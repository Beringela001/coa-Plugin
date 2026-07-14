<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Resolves COA routes into validated, normalized template contexts. */
final class Frontend_Router {
	private $query; private $compounds; private $tests; private $view_model;
	private $resolved = false; private $context = array(); private $not_found = false;

	public function __construct( Frontend_Query $query, Compound_Repository $compounds, COA_Test_Repository $tests, Frontend_View_Model $view_model ) {
		$this->query = $query; $this->compounds = $compounds; $this->tests = $tests; $this->view_model = $view_model;
	}

	/** Resolves the main query once WordPress has parsed it. @return void */
	public function resolve() {
		if ( $this->resolved || is_admin() || wp_doing_ajax() || wp_doing_cron() ) { return; }
		$this->resolved = true; $view = $this->query->view();
		if ( ! $view ) { return; }
		if ( 'archive' === $view ) { $this->context = $this->build_archive( $this->query->page(), $this->query->search() ); }
		elseif ( 'compound' === $view ) { $this->context = $this->build_compound( $this->query->compound_slug(), 0, $this->query->page() ); }
		elseif ( 'report' === $view ) { $this->context = $this->build_report( $this->query->compound_slug(), $this->query->batch_slug() ); }
		if ( ! $this->context ) { $this->mark_404(); return; }
		set_query_var( 'ps_coa_context', $this->context );
		$this->mark_200();
	}

	/** Blocks legacy core post-type URLs without redirecting or bypassing visibility. @return void */
	public function protect_legacy_routes() {
		if ( $this->is_route() || is_preview() || is_admin() ) { return; }
		if ( is_post_type_archive( Post_Types::COMPOUND ) || is_singular( array( Post_Types::COMPOUND, Post_Types::COA_TEST ) ) ) { $this->mark_404(); }
	}

	/** Builds the public archive context. @param int $page Page. @param string $search Search term. @return array */
	public function build_archive( $page = 1, $search = '' ) {
		$search = sanitize_text_field( (string) $search );
		$result = $this->compounds->archive_page( $this->tests->compound_ids_with_public_tests(), $page, 24, $search );
		$grouped = $this->tests->grouped_for_compounds( wp_list_pluck( $result['posts'], 'ID' ) );
		$items = array();
		foreach ( $result['posts'] as $compound ) { $items[] = $this->view_model->archive_compound( $compound, isset( $grouped[ $compound->ID ] ) ? $grouped[ $compound->ID ] : array() ); }
		$archive_url = $this->view_model->archive_url();
		$canonical_args = array_filter( array( 'coa_search' => $search, 'paged' => $result['page'] > 1 ? $result['page'] : null ) );
		$canonical = $canonical_args ? add_query_arg( $canonical_args, $archive_url ) : $archive_url;
		return array( 'view' => 'archive', 'template' => 'archive-testing.php', 'canonical' => $canonical, 'archive_url' => $archive_url, 'search' => $search, 'compounds' => $items, 'pagination' => array( 'page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total'] ) );
	}

	/** Builds a visible compound history by slug or ID. @param string $slug Slug. @param int $id ID. @param int $page Page. @return array */
	public function build_compound( $slug = '', $id = 0, $page = 1 ) {
		$compound = $id ? $this->compounds->find_public_by_id( $id ) : $this->compounds->find_public_by_slug( $slug );
		if ( ! $compound ) { return array(); }
		$tests = $this->tests->all_for_compound( $compound->ID );
		if ( ! $tests ) { return array(); }
		$approved = array_values( array_filter( $tests, function ( $test ) { return 'approved' === get_post_meta( $test->ID, 'coa_status', true ); } ) );
		$incoming = array_values( array_filter( $tests, function ( $test ) { return in_array( get_post_meta( $test->ID, 'coa_status', true ), array( 'pending', 'in-testing', 'vendor-vetting' ), true ); } ) );
		$latest = $approved ? array_shift( $approved ) : null;
		$previous_all = array_values( array_filter( $tests, function ( $test ) use ( $latest ) { $status = get_post_meta( $test->ID, 'coa_status', true ); return ( ! $latest || $test->ID !== $latest->ID ) && in_array( $status, array( 'approved', 'failed' ), true ); } ) );
		$total = count( $previous_all ); $pages = (int) ceil( $total / 20 ); $page = min( max( 1, absint( $page ) ), max( 1, $pages ) );
		$previous = array_slice( $previous_all, ( $page - 1 ) * 20, 20 );
		$compound_model = $this->view_model->compound( $compound );
		$compound_model['approved_test_count'] = count( $approved ) + ( $latest ? 1 : 0 );
		$compound_model['latest_test_date'] = $latest ? get_post_meta( $latest->ID, 'test_date', true ) : '';
		$compound_model['latest_batch_number'] = $latest ? get_post_meta( $latest->ID, 'batch_number', true ) : '';
		$compound_model['latest_purity'] = $latest ? get_post_meta( $latest->ID, 'purity_percentage', true ) : '';
		$compound_model['current_approved_test'] = $latest && absint( get_post_meta( $latest->ID, 'is_current', true ) ) ? $this->view_model->test_summary( $latest, $compound ) : null;
		$compound_model['previous_approved_tests'] = array_map( function ( $test ) use ( $compound ) { return $this->view_model->test_summary( $test, $compound ); }, $previous );
		$canonical = $page > 1 ? add_query_arg( 'paged', $page, $compound_model['url'] ) : $compound_model['url'];
		return array( 'view' => 'compound', 'template' => 'single-compound-history.php', 'canonical' => $canonical, 'archive_url' => $this->view_model->archive_url(), 'compound' => $compound_model, 'latest_report' => $latest ? $this->view_model->test_summary( $latest, $compound ) : null, 'incoming_reports' => array_map( function ( $test ) use ( $compound ) { return $this->view_model->test_summary( $test, $compound ); }, $incoming ), 'previous_reports' => array_map( function ( $test ) use ( $compound ) { return $this->view_model->test_summary( $test, $compound ); }, $previous ), 'pagination' => array( 'page' => $page, 'pages' => $pages, 'total' => $total ) );
	}

	/** Builds a visible report by route slugs. @param string $compound_slug Compound. @param string $batch_slug Batch. @return array */
	public function build_report( $compound_slug, $batch_slug ) {
		$compound = $this->compounds->find_public_by_slug( $compound_slug );
		if ( ! $compound ) { return array(); }
		$test = $this->tests->find_public_by_batch_slug( $compound->ID, $batch_slug );
		return $test ? $this->report_context( $compound, $test ) : array();
	}

	/** Builds a visible report by explicit IDs for shortcode use. @param int $compound_id Compound. @param int $test_id Test. @return array */
	public function build_report_by_ids( $compound_id, $test_id ) {
		$compound = $this->compounds->find_public_by_id( $compound_id );
		$test = $compound ? $this->tests->find_public_by_id( $test_id, $compound->ID ) : null;
		return $test ? $this->report_context( $compound, $test ) : array();
	}

	public function context() { return $this->context; }
	public function is_route() { return (bool) $this->query->view(); }
	public function is_404() { return $this->not_found; }
	public function canonical_url() { return isset( $this->context['canonical'] ) ? $this->context['canonical'] : ''; }

	private function report_context( $compound, $test ) {
		$adjacent = $this->tests->adjacent( $compound->ID, $test->ID );
		return array( 'view' => 'report', 'template' => 'single-coa-report.php', 'canonical' => $this->view_model->test_url( $compound, $test ), 'archive_url' => $this->view_model->archive_url(), 'compound' => $this->view_model->compound( $compound ), 'test' => $this->view_model->report( $test, $compound ), 'previous_report' => $adjacent['previous'] ? $this->view_model->test_summary( $adjacent['previous'], $compound ) : null, 'next_report' => $adjacent['next'] ? $this->view_model->test_summary( $adjacent['next'], $compound ) : null );
	}

	private function mark_404() {
		global $wp_query; $this->not_found = true;
		if ( $wp_query ) { $wp_query->set_404(); }
		status_header( 404 ); nocache_headers();
	}

	private function mark_200() {
		global $wp_query;
		if ( $wp_query ) { $wp_query->is_404 = false; }
		status_header( 200 );
	}
}

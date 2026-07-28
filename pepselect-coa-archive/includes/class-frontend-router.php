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
		$search = Frontend_Query::normalize_search( $search );
		$settings = Design_Settings::get();
		// Ops Spec §16.6: a compound whose only public record is a FAILURE must
		// still appear in the archive — a lone failure is exactly the case where
		// transparency matters most. The archive therefore always includes
		// failed-only compounds; the design toggle no longer suppresses them.
		$index = $this->tests->archive_index( $search, true );
		$result = $this->compounds->archive_page( $index['compound_ids'], $page, 24, $search, $index['batch_matches'], $index['sort_priorities'] );
		$grouped = $this->tests->grouped_for_compounds( wp_list_pluck( $result['posts'], 'ID' ) );
		$items = array();
		foreach ( $result['posts'] as $compound ) { $items[] = $this->view_model->archive_compound( $compound, isset( $grouped[ $compound->ID ] ) ? $grouped[ $compound->ID ] : array() ); }
		$archive_url = $this->view_model->archive_url();
		$canonical_args = array_filter( array( 'coa_search' => $search, 'paged' => $result['page'] > 1 ? $result['page'] : null ) );
		$canonical = $canonical_args ? add_query_arg( $canonical_args, $archive_url ) : $archive_url;
		return array( 'view' => 'archive', 'template' => 'archive-testing.php', 'canonical' => $canonical, 'archive_url' => $archive_url, 'search' => $search, 'compounds' => $items, 'pagination' => array( 'page' => $result['page'], 'pages' => $result['pages'], 'total' => $result['total'], 'available_total' => isset( $result['available_total'] ) ? $result['available_total'] : $result['total'], 'displayed' => count( $items ) ) );
	}

	/** Builds a visible compound history by slug or ID. @param string $slug Slug. @param int $id ID. @param int $page Page. @return array */
	public function build_compound( $slug = '', $id = 0, $page = 1 ) {
		unset( $page );
		$compound = $id ? $this->compounds->find_public_by_id( $id ) : $this->compounds->find_public_by_slug( $slug );
		if ( ! $compound ) { return array(); }
		$classified = $this->tests->classified_for_compound( $compound->ID );
		$tests = array_merge( $classified['approved'], $classified['incoming'], $classified['failed'] ); if ( ! $tests ) { return array(); }
		$approved = $classified['approved']; $incoming = $classified['incoming'];
		$latest = $approved ? array_shift( $approved ) : null;
		$previous_all = array_merge( $approved, $classified['failed'] );
		usort( $previous_all, static function ( $left, $right ) { $ld = preg_replace( '/\D/', '', (string) get_post_meta( $left->ID, 'test_date', true ) ); $rd = preg_replace( '/\D/', '', (string) get_post_meta( $right->ID, 'test_date', true ) ); $date = strcmp( $rd, $ld ); return 0 !== $date ? $date : strcmp( $right->post_date_gmt, $left->post_date_gmt ); } );
		$total = count( $previous_all );
		$previous = array_slice( $previous_all, 0, 10 );
		$compound_model = $this->view_model->compound( $compound );
		$compound_model['approved_test_count'] = count( $approved ) + ( $latest ? 1 : 0 );
		$compound_model['latest_test_date'] = $latest ? get_post_meta( $latest->ID, 'test_date', true ) : '';
		$compound_model['latest_batch_number'] = $latest ? get_post_meta( $latest->ID, 'batch_number', true ) : '';
		$compound_model['latest_purity'] = $latest ? get_post_meta( $latest->ID, 'purity_percentage', true ) : '';
		$latest_model = $latest ? $this->view_model->history_report( $latest, $compound ) : null;
		$current_model = $latest_model && $latest_model['is_current'] ? $latest_model : null;
		$compound_model['current_approved_test'] = $current_model;
		$compound_model['previous_approved_tests'] = array_map( function ( $test ) use ( $compound ) { return $this->view_model->history_report( $test, $compound ); }, $previous );
		// The compound header covers EVERY batch, so it must show the compound's own
		// stock image (compound_image_id -> Woo product image -> placeholder), never
		// a single lot's batch_vial_photo. The batch vial photo belongs only on the
		// individual COA/report page. (Fixes vial-photo leak into the compound header.)
		$hero_image = array( 'id' => $compound_model['compound_image_id'], 'url' => $compound_model['compound_image_url'] ?: plugins_url( 'assets/images/neutral-vial.svg', PEPSELECT_COA_ARCHIVE_FILE ), 'srcset' => $compound_model['compound_image_srcset'], 'sizes' => $compound_model['compound_image_sizes'], 'alt' => $compound_model['compound_image_alt'], 'source' => $compound_model['compound_image_url'] ? 'compound-image' : 'local-placeholder' );
		return array(
			'view' => 'compound', 'template' => 'single-compound-history.php', 'canonical' => $compound_model['url'], 'archive_url' => $this->view_model->archive_url(),
			'compound' => $compound_model, 'hero_image' => $hero_image, 'current_report' => $current_model, 'latest_report' => $latest_model,
			'incoming_reports' => array_map( function ( $test ) use ( $compound ) { return $this->view_model->test_summary( $test, $compound ); }, $incoming ),
			'previous_reports' => $compound_model['previous_approved_tests'], 'previous_report_total' => $total, 'previous_report_limit' => 10,
		);
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

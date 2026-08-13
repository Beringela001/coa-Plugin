<?php
/** SEO sitemap integration tests. */
class PepSelect_COA_Archive_SEO_Sitemaps_Test extends WP_UnitTestCase {
	private $visibility;
	private $tests;
	private $view_model;
	private $sitemaps;

	public function set_up() {
		parent::set_up();
		$this->visibility = new PepSelect\COAArchive\Frontend_Visibility();
		$this->tests      = new PepSelect\COAArchive\COA_Test_Repository( $this->visibility );
		$this->view_model = new PepSelect\COAArchive\Frontend_View_Model();
		$this->sitemaps   = new PepSelect\COAArchive\SEO_Sitemaps( $this->visibility, $this->tests, $this->view_model );
	}

	public function test_public_compound_and_report_use_real_testing_routes() {
		$compound = $this->compound();
		$test     = $this->test_record( $compound, 'PUBLIC-BATCH' );

		$this->assertSame( home_url( '/testing/retatrutide-30mg/' ), $this->sitemaps->public_url( $compound ) );
		$this->assertSame( home_url( '/testing/retatrutide-30mg/public-batch/' ), $this->sitemaps->public_url( $test ) );
		$this->assertSame( $this->sitemaps->public_url( $test ), $this->sitemaps->filter_post_url( get_permalink( $test ), get_post( $test ) ) );
	}

	public function test_private_invalid_and_ambiguous_records_are_excluded() {
		$compound = $this->compound();
		$public   = $this->test_record( $compound, 'DUPLICATE-BATCH' );
		$private  = $this->test_record( $compound, 'PRIVATE-BATCH' );
		update_post_meta( $private, '_ps_coa_private', 1 );
		$duplicate = $this->test_record( $compound, 'DUPLICATE-BATCH' );

		$excluded = $this->sitemaps->exclude_unroutable_posts( array( 999 ) );
		$this->assertContains( 999, $excluded );
		$this->assertContains( $private, $excluded );
		$this->assertContains( $public, $excluded );
		$this->assertContains( $duplicate, $excluded );
	}

	public function test_empty_compound_is_excluded_and_archive_is_added_once() {
		$empty = $this->compound( 'Empty Compound' );
		$this->assertContains( $empty, $this->sitemaps->exclude_unroutable_posts( array() ) );

		$links = $this->sitemaps->add_archive_url( array(), 'ps_compound' );
		$this->assertSame( home_url( '/testing/' ), $links[0]['loc'] );
		$this->assertCount( 1, $this->sitemaps->add_archive_url( $links, 'ps_compound' ) );
		$this->assertSame( array(), $this->sitemaps->add_archive_url( array(), 'product' ) );
	}

	private function compound( $title = 'Retatrutide 30mg' ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ps_compound',
				'post_status' => 'publish',
				'post_title'  => $title,
				'post_name'   => sanitize_title( $title ),
			)
		);
		update_post_meta( $id, 'display_name', $title );
		update_post_meta( $id, 'is_active', 1 );
		return $id;
	}

	private function test_record( $compound, $batch ) {
		$id = self::factory()->post->create(
			array(
				'post_type'   => 'ps_coa_test',
				'post_status' => 'publish',
				'post_title'  => $batch,
				'post_name'   => sanitize_title( $batch ),
			)
		);
		update_post_meta( $id, 'compound_id', $compound );
		update_post_meta( $id, 'coa_status', 'approved' );
		update_post_meta( $id, 'workflow_stage', 'complete' );
		update_post_meta( $id, 'batch_number', $batch );
		update_post_meta( $id, 'test_date', '20260813' );
		update_post_meta( $id, 'testing_lab', 'ils-labs' );
		update_post_meta( $id, 'vials_tested', 1 );
		return $id;
	}
}

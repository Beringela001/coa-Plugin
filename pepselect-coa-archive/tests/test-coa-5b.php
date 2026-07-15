<?php
/** COA-5B product source-of-truth and lightbox regression coverage. */
class PepSelect_COA_5B_Test extends WP_UnitTestCase {
	/** @var PepSelect\COAArchive\Product_Matching */ private $matching;

	public function set_up() {
		parent::set_up();
		if ( ! post_type_exists( 'product' ) ) { register_post_type( 'product', array( 'public' => true, 'supports' => array( 'title', 'thumbnail' ) ) ); }
		$this->matching = new PepSelect\COAArchive\Product_Matching( new PepSelect\COAArchive\Dependencies(), true );
	}

	private function product( $title, $sku, $include = true ) {
		$id = self::factory()->post->create( array( 'post_type' => 'product', 'post_status' => 'publish', 'post_title' => $title ) );
		update_post_meta( $id, '_sku', $sku );
		update_post_meta( $id, PepSelect\COAArchive\Product_Matching::INCLUDE_META, $include ? 'yes' : 'no' );
		return $id;
	}

	private function eligible_product( $title = 'GLP-3 R', $sku = 'GLP3R30' ) {
		$id = $this->product( $title, $sku );
		update_post_meta( $id, PepSelect\COAArchive\Product_Matching::DISPLAY_NAME_META, 'Retatrutide' );
		update_post_meta( $id, PepSelect\COAArchive\Product_Matching::STRENGTH_META, '30' );
		update_post_meta( $id, PepSelect\COAArchive\Product_Matching::STRENGTH_UNIT_META, 'mg' );
		return $id;
	}

	public function test_sku_first_search_supports_exact_partial_title_and_product_id() {
		$partial = $this->product( 'GLP-3 R Research', 'GLP3R20' );
		$exact = $this->product( 'GLP-3 R', 'GLP3R30' );
		$this->assertSame( $exact, $this->matching->search( 'GLP3R30' )[0]['id'] );
		$this->assertSame( $partial, $this->matching->search( 'GLP3R2' )[0]['id'] );
		$this->assertSame( $exact, $this->matching->search( (string) $exact )[0]['id'] );
		$this->assertContains( $exact, wp_list_pluck( $this->matching->search( 'GLP-3 R' ), 'id' ) );
		$result = $this->matching->search( 'GLP3R30' )[0];
		foreach ( array( 'title', 'sku', 'id', 'status', 'strength' ) as $key ) { $this->assertArrayHasKey( $key, $result ); }
	}

	public function test_create_and_connect_is_draft_idempotent_and_fabricates_no_tests() {
		$product_id = $this->eligible_product();
		$compound_id = $this->matching->create_and_connect( $product_id );
		$this->assertIsInt( $compound_id );
		$this->assertSame( 'draft', get_post_status( $compound_id ) );
		$this->assertSame( (string) $product_id, get_post_meta( $compound_id, 'woocommerce_product_id', true ) );
		$this->assertSame( 'GLP3R30', get_post_meta( $compound_id, 'related_woocommerce_sku', true ) );
		$this->assertSame( 'Retatrutide', get_post_meta( $compound_id, 'display_name', true ) );
		$this->assertSame( 'Retatrutide', get_post_meta( $compound_id, 'compound_name', true ) );
		$this->assertSame( $compound_id, $this->matching->create_and_connect( $product_id ) );
		$this->assertCount( 1, $this->matching->compounds_for_product( $product_id ) );
		$this->assertEmpty( get_posts( array( 'post_type' => 'ps_coa_test', 'post_status' => 'any', 'posts_per_page' => -1, 'meta_key' => 'compound_id', 'meta_value' => $compound_id ) ) );
	}

	public function test_create_blocks_missing_duplicate_and_ambiguous_identity() {
		$missing = $this->eligible_product( 'Missing', '' );
		$this->assertWPError( $this->matching->create_and_connect( $missing ) );
		$one = $this->eligible_product( 'One', 'DUP30' ); $this->eligible_product( 'Two', 'DUP30' );
		$this->assertSame( 'duplicate_sku', $this->matching->create_and_connect( $one )->get_error_code() );
		$ambiguous = $this->product( 'Unknown Peptide', 'UNKNOWN30' );
		$this->assertSame( 'strength_review', $this->matching->create_and_connect( $ambiguous )->get_error_code() );
		update_post_meta( $ambiguous, PepSelect\COAArchive\Product_Matching::STRENGTH_META, '30' ); update_post_meta( $ambiguous, PepSelect\COAArchive\Product_Matching::STRENGTH_UNIT_META, 'mg' );
		$this->assertSame( 'name_review', $this->matching->create_and_connect( $ambiguous )->get_error_code() );
	}

	public function test_connect_and_sync_preserve_public_identity_slug_and_all_coa_owned_data() {
		$product_id = $this->eligible_product();
		$compound_id = self::factory()->post->create( array( 'post_type' => 'ps_compound', 'post_status' => 'publish', 'post_title' => 'Retatrutide 30 mg', 'post_name' => 'retatrutide-30-mg' ) );
		$owned = array( 'display_name' => 'Retatrutide', 'compound_name' => 'Retatrutide', 'short_name' => 'Reta', 'archive_description' => 'Independent testing.', 'compound_category' => 'metabolic', 'display_order' => '7', 'vial_cap_color' => 'blue' );
		foreach ( $owned as $key => $value ) { update_post_meta( $compound_id, $key, $value ); }
		$test_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish', 'post_title' => 'RT30-0726-B' ) ); update_post_meta( $test_id, 'compound_id', $compound_id ); update_post_meta( $test_id, 'coa_pdf', 991 );
		$this->assertNotWPError( $this->matching->connect_existing( $product_id, $compound_id ) );
		foreach ( $owned as $key => $value ) { $this->assertSame( $value, get_post_meta( $compound_id, $key, true ) ); }
		$this->assertSame( 'retatrutide-30-mg', get_post( $compound_id )->post_name );
		$this->assertSame( '991', get_post_meta( $test_id, 'coa_pdf', true ) );
		$this->assertNotNull( get_post( $test_id ) );
	}

	public function test_product_change_warning_deletion_and_disconnect_preserve_history() {
		$product_id = $this->eligible_product(); $compound_id = $this->matching->create_and_connect( $product_id );
		$test_id = self::factory()->post->create( array( 'post_type' => 'ps_coa_test', 'post_status' => 'publish' ) ); update_post_meta( $test_id, 'compound_id', $compound_id ); update_post_meta( $test_id, 'batch_vial_photo', 123 );
		update_post_meta( $product_id, '_sku', 'GLP3R30-NEW' ); $this->matching->sync( $compound_id, false );
		$this->assertSame( 'sku-changed', $this->matching->product_status( $product_id )['key'] );
		$this->assertSame( 'GLP3R30', get_post_meta( $compound_id, 'related_woocommerce_sku', true ) );
		wp_delete_post( $product_id, true ); $result = $this->matching->sync( $compound_id );
		$this->assertWPError( $result ); $this->assertNotNull( get_post( $compound_id ) ); $this->assertNotNull( get_post( $test_id ) );
		$this->matching->disconnect( $compound_id );
		$this->assertSame( '', get_post_meta( $compound_id, 'woocommerce_product_id', true ) );
		$this->assertSame( '123', get_post_meta( $test_id, 'batch_vial_photo', true ) );
	}

	public function test_woocommerce_inactive_preserves_relationships() {
		$compound_id = self::factory()->post->create( array( 'post_type' => 'ps_compound' ) ); update_post_meta( $compound_id, 'woocommerce_product_id', 123 ); update_post_meta( $compound_id, 'related_woocommerce_sku', 'GLP3R30' );
		$inactive = new PepSelect\COAArchive\Product_Matching( new PepSelect\COAArchive\Dependencies(), false ); $this->assertWPError( $inactive->sync( $compound_id ) );
		$this->assertSame( '123', get_post_meta( $compound_id, 'woocommerce_product_id', true ) ); $this->assertSame( 'GLP3R30', get_post_meta( $compound_id, 'related_woocommerce_sku', true ) );
	}

	public function test_admin_security_scope_and_no_public_product_hooks() {
		$source = file_get_contents( dirname( __DIR__ ) . '/includes/class-product-matching-admin.php' );
		foreach ( array( 'current_user_can', 'check_ajax_referer', 'check_admin_referer', 'sanitize_text_field', 'wp_ajax_pepselect_coa_search_products' ) as $needle ) { $this->assertStringContainsString( $needle, $source ); }
		foreach ( array( 'woocommerce_after_single_product_summary', 'woocommerce_single_product_summary', 'woocommerce_before_single_product' ) as $hook ) { $this->assertStringNotContainsString( $hook, $source ); }
		foreach ( array( 'price', 'stock', 'order', 'checkout', 'shipping', 'qr' ) as $forbidden ) { $this->assertStringNotContainsString( "update_post_meta( \$product_id, '_{$forbidden}", strtolower( $source ) ); }
	}

	public function test_image_fallback_order_and_lightbox_portal_are_explicit() {
		$model = file_get_contents( dirname( __DIR__ ) . '/includes/class-frontend-view-model.php' );
		$fallback = substr( $model, strpos( $model, '$batch_image_id' ) );
		$this->assertLessThan( strpos( $fallback, "'woocommerce-product-image'" ), strpos( $fallback, "'featured-image'" ) );
		$this->assertLessThan( strpos( $fallback, "'compound-image'" ), strpos( $fallback, "'woocommerce-product-image'" ) );
		$script = file_get_contents( dirname( __DIR__ ) . '/assets/js/pepselect-coa-lightbox.js' ); $css = file_get_contents( dirname( __DIR__ ) . '/assets/css/pepselect-coa-frontend.css' );
		$this->assertStringContainsString( 'doc.body.appendChild(lightbox)', $script ); $this->assertStringContainsString( 'view.scrollTo(scrollState.x, scrollState.y)', $script );
		$this->assertStringContainsString( 'height: 100vh; height: 100dvh', $css ); $this->assertStringContainsString( 'z-index: 2147483000', $css );
	}
}

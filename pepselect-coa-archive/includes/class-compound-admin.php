<?php
namespace PepSelect\COAArchive;

if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Customizes only the compound administration list. */
final class Compound_Admin {
	/** Registers compound-list hooks. @return void */
	public function register_hooks() {
		add_filter( 'manage_' . Post_Types::COMPOUND . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . Post_Types::COMPOUND . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_filter( 'manage_edit-' . Post_Types::COMPOUND . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_action( 'restrict_manage_posts', array( $this, 'render_filters' ) );
		add_action( 'pre_get_posts', array( $this, 'apply_query_controls' ) );
	}

	/** Defines useful list columns. @param array $columns Existing columns. @return array */
	public function columns( $columns ) {
		$date = isset( $columns['date'] ) ? $columns['date'] : __( 'Date', 'pepselect-coa-archive' );
		return array( 'cb' => $columns['cb'], 'title' => __( 'Compound', 'pepselect-coa-archive' ), 'strength' => __( 'Strength', 'pepselect-coa-archive' ), 'compound_category' => __( 'Category', 'pepselect-coa-archive' ), 'related_product' => __( 'Related Product', 'pepselect-coa-archive' ), 'is_active' => __( 'Active', 'pepselect-coa-archive' ), 'is_featured' => __( 'Featured', 'pepselect-coa-archive' ), 'display_order' => __( 'Display Order', 'pepselect-coa-archive' ), 'date' => $date );
	}

	/** Renders a compound list column. @param string $column Column key. @param int $post_id Compound ID. @return void */
	public function render_column( $column, $post_id ) {
		if ( 'strength' === $column ) {
			$value = get_post_meta( $post_id, 'strength_value', true );
			$unit  = get_post_meta( $post_id, 'strength_unit', true );
			echo '' !== (string) $value && '' !== $unit ? esc_html( $value . $unit ) : '&mdash;';
		} elseif ( 'compound_category' === $column ) {
			$key = get_post_meta( $post_id, 'compound_category', true );
			$labels = Compound_Validation::categories();
			echo isset( $labels[ $key ] ) ? esc_html( $labels[ $key ] ) : '&mdash;';
		} elseif ( 'related_product' === $column ) {
			$this->render_product( absint( get_post_meta( $post_id, 'woocommerce_product_id', true ) ) );
		} elseif ( 'is_active' === $column ) {
			echo get_post_meta( $post_id, 'is_active', true ) ? '<span class="ps-coa-status ps-coa-status--active">' . esc_html__( 'Active', 'pepselect-coa-archive' ) . '</span>' : '<span class="ps-coa-status ps-coa-status--inactive">' . esc_html__( 'Inactive', 'pepselect-coa-archive' ) . '</span>';
		} elseif ( 'is_featured' === $column ) {
			echo get_post_meta( $post_id, 'is_featured', true ) ? esc_html__( 'Featured', 'pepselect-coa-archive' ) : '&mdash;';
		} elseif ( 'display_order' === $column ) {
			$value = get_post_meta( $post_id, 'display_order', true );
			echo esc_html( '' === (string) $value ? '0' : (string) absint( $value ) );
		}
	}

	/** Defines sortable compound columns. @param array $columns Existing sortable columns. @return array */
	public function sortable_columns( $columns ) {
		$columns['strength']      = 'strength';
		$columns['display_order'] = 'display_order';
		$columns['is_active']     = 'is_active';
		$columns['is_featured']   = 'is_featured';
		return $columns;
	}

	/** Renders category and boolean filters on the compound list only. @param string $post_type Current post type. @return void */
	public function render_filters( $post_type ) {
		if ( Post_Types::COMPOUND !== $post_type ) { return; }
		$active   = isset( $_GET['ps_active'] ) ? sanitize_key( wp_unslash( $_GET['ps_active'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$featured = isset( $_GET['ps_featured'] ) ? sanitize_key( wp_unslash( $_GET['ps_featured'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$category = isset( $_GET['ps_category'] ) ? sanitize_key( wp_unslash( $_GET['ps_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$this->boolean_filter( 'ps_active', __( 'All active statuses', 'pepselect-coa-archive' ), $active );
		$this->boolean_filter( 'ps_featured', __( 'All featured statuses', 'pepselect-coa-archive' ), $featured );
		echo '<label class="screen-reader-text" for="ps_category">' . esc_html__( 'Filter by compound category', 'pepselect-coa-archive' ) . '</label><select name="ps_category" id="ps_category"><option value="">' . esc_html__( 'All categories', 'pepselect-coa-archive' ) . '</option>';
		foreach ( Compound_Validation::categories() as $key => $label ) { printf( '<option value="%1$s" %2$s>%3$s</option>', esc_attr( $key ), selected( $category, $key, false ), esc_html( $label ) ); }
		echo '</select>';
	}

	/** Applies sorting and filters only to the main compound admin query. @param \WP_Query $query Query. @return void */
	public function apply_query_controls( $query ) {
		if ( ! is_admin() || ! $query->is_main_query() || Post_Types::COMPOUND !== $query->get( 'post_type' ) ) { return; }
		$orderby = sanitize_key( (string) $query->get( 'orderby' ) );
		$meta_sort = array( 'strength' => array( 'strength_value', 'meta_value_num' ), 'display_order' => array( 'display_order', 'meta_value_num' ), 'is_active' => array( 'is_active', 'meta_value_num' ), 'is_featured' => array( 'is_featured', 'meta_value_num' ) );
		if ( isset( $meta_sort[ $orderby ] ) ) { $query->set( 'meta_key', $meta_sort[ $orderby ][0] ); $query->set( 'orderby', $meta_sort[ $orderby ][1] ); }
		$meta_query = array( 'relation' => 'AND' );
		if ( '' === $orderby ) {
			$meta_query[] = array(
				'relation'                 => 'OR',
				'display_order_clause'     => array( 'key' => 'display_order', 'type' => 'NUMERIC', 'compare' => 'EXISTS' ),
				'no_display_order_clause'  => array( 'key' => 'display_order', 'compare' => 'NOT EXISTS' ),
			);
			$query->set( 'orderby', array( 'display_order_clause' => 'ASC', 'title' => 'ASC' ) );
		}
		$this->add_boolean_filter( $meta_query, 'ps_active', 'is_active' );
		$this->add_boolean_filter( $meta_query, 'ps_featured', 'is_featured' );
		$category = isset( $_GET['ps_category'] ) ? sanitize_key( wp_unslash( $_GET['ps_category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( array_key_exists( $category, Compound_Validation::categories() ) ) { $meta_query[] = array( 'key' => 'compound_category', 'value' => $category ); }
		if ( count( $meta_query ) > 1 ) { $query->set( 'meta_query', $meta_query ); }
	}

	/** Renders a product link or fallback. @param int $product_id Product ID. @return void */
	private function render_product( $product_id ) {
		$product = $product_id ? get_post( $product_id ) : null;
		if ( ! $product || 'product' !== $product->post_type ) { echo '&mdash;'; return; }
		if ( current_user_can( 'edit_post', $product_id ) ) { printf( '<a href="%1$s">%2$s</a>', esc_url( get_edit_post_link( $product_id ) ), esc_html( get_the_title( $product_id ) ) ); return; }
		echo esc_html( get_the_title( $product_id ) );
	}

	/** Renders a tri-state boolean filter. @param string $name Name. @param string $all_label All label. @param string $selected Selected value. @return void */
	private function boolean_filter( $name, $all_label, $selected ) {
		printf( '<label class="screen-reader-text" for="%1$s">%2$s</label><select name="%1$s" id="%1$s"><option value="">%2$s</option><option value="1" %3$s>%4$s</option><option value="0" %5$s>%6$s</option></select>', esc_attr( $name ), esc_html( $all_label ), selected( $selected, '1', false ), esc_html__( 'Yes', 'pepselect-coa-archive' ), selected( $selected, '0', false ), esc_html__( 'No', 'pepselect-coa-archive' ) );
	}

	/** Adds a sanitized boolean meta query from a request filter. @param array $meta_query Query clauses. @param string $request_key Request key. @param string $meta_key Meta key. @return void */
	private function add_boolean_filter( &$meta_query, $request_key, $meta_key ) {
		$value = isset( $_GET[ $request_key ] ) ? sanitize_key( wp_unslash( $_GET[ $request_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( in_array( $value, array( '0', '1' ), true ) ) { $meta_query[] = array( 'key' => $meta_key, 'value' => $value, 'compare' => '=' ); }
	}
}

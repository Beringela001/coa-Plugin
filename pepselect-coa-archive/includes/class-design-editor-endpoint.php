<?php
namespace PepSelect\COAArchive;
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Authenticated contract shared by WP admin and a future Ops client. */
final class Design_Editor_Endpoint {
	public function register() { add_action( 'rest_api_init', array( $this, 'routes' ) ); }
	public function permitted() { return current_user_can( 'manage_ps_coas' ); }
	public function routes() {
		$permission = array( $this, 'permitted' );
		register_rest_route( 'pepselect-coa/v1', '/design', array(
			array( 'methods' => 'GET', 'callback' => array( $this, 'read' ), 'permission_callback' => $permission ),
			array( 'methods' => 'PATCH', 'callback' => array( $this, 'save' ), 'permission_callback' => $permission ),
		) );
		register_rest_route( 'pepselect-coa/v1', '/design/preview', array( 'methods' => 'POST', 'callback' => array( $this, 'preview' ), 'permission_callback' => $permission ) );
	}
	private function response( $result ) {
		if ( is_wp_error( $result ) ) { return $result; }
		$response = rest_ensure_response( $result ); $response->header( 'Cache-Control', 'no-store, private' ); return $response;
	}
	public function read() { return $this->response( array_merge( Design_Editor::read(), array( 'choices' => Design_Editor::choices() ) ) ); }
	public function save( $request ) { return $this->response( Design_Editor::save( $request->get_param( 'settings' ), $request->get_param( 'revision' ) ) ); }
	public function preview( $request ) { return $this->response( Design_Editor::preview( $request->get_param( 'settings' ), $request->get_param( 'view' ), $request->get_param( 'id' ) ) ); }
}

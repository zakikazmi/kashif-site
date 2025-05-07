<?php

class Meow_MGCL_Rest
{
	private $core = null;
	private $admin = null;
	private $namespace = 'gallery-custom-links/v1';

	public function __construct( $core, $admin ) {
		if ( !current_user_can( 'administrator' ) ) {
			return;
		} 
		$this->core = $core;
		$this->admin = $admin;
		add_action( 'rest_api_init', array( $this, 'rest_api_init' ) );
	}

	function rest_api_init() {
		try {
			// SETTINGS
			register_rest_route( $this->namespace, '/update_option', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_update_option' )
			) );
			register_rest_route( $this->namespace, '/all_settings', array(
				'methods' => 'GET',
				'permission_callback' => array( $this->core, 'can_access_settings' ),
				'callback' => array( $this, 'rest_all_settings' ),
			) );

			register_rest_route( $this->namespace, '/update_meta', array(
				'methods' => 'POST',
				'permission_callback' => array( $this->core, 'can_access_features' ),
				'callback' => array( $this, 'rest_update_meta' )
			) );
		} 
		catch (Exception $e) {
			var_dump($e);
		}
	}

	function rest_all_settings() {
		return new WP_REST_Response( [
			'success' => true,
			'data' => $this->admin->get_all_options()
		], 200 );
	}

	function rest_update_option( $request ) {
		$params = $request->get_json_params();
		try {
			$name = $params['name'];
			$options = $this->admin->list_options();
			if ( !array_key_exists( $name, $options ) ) {
				return new WP_REST_Response([ 'success' => false, 'message' => 'This option does not exist.' ], 200 );
			}
			$value = is_bool( $params['value'] ) ? ( $params['value'] ? '1' : '' ) : $params['value'];
			$success = update_option( $name, $value );
			if ( $success ) {
				$res = $this->validate_updated_option( $name );
				$result = $res['result'];
				$message = $res['message'];
				return new WP_REST_Response([ 'success' => $result, 'message' => $message ], 200 );
			}
			return new WP_REST_Response([ 'success' => false, 'message' => "Could not update option." ], 200 );
		} 
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}

	function validate_updated_option( $option_name ) {
		$obmode = get_option( 'mgcl_obmode', false );
		$parsing_engine = get_option( 'mgcl_parsing_engine', 'HtmlDomParser' );
		$log = get_option( 'mgcl_log', false );
		$button_enabled = get_option( 'mgcl_button_enabled', false );
		$button_label = get_option( 'mgcl_button_label', "Click here" );
		if ( $obmode === '' )
			update_option( 'mgcl_obmode', false );
		if ( $parsing_engine === '' )
			update_option( 'mgcl_parsing_engine', 'HtmlDomParser' );
		if ( $log === '' )
			update_option( 'mgcl_log', false );
		if ( $button_enabled === '' )
			update_option( 'mgcl_button_enabled', false );
		if ( $button_label === '' )
			update_option( 'mgcl_button_label', "Click here" );
		return $this->createValidationResult();
	}

	function createValidationResult( $result = true, $message = null) {
		$message = $message ? $message : __( 'OK', 'gallery-custom-links' );
		return ['result' => $result, 'message' => $message];
	}

	function rest_update_meta( $request ) {
		$params = $request->get_json_params();
		if ( !$params['post_id'] ) {
			return new WP_REST_Response( [ 'success' => false, 'message' => 'Missing post id parameter.' ], 400 );
		}
		$postId = $params['post_id'];
		try {
			$options = $this->admin->meta_options();
			foreach ( $params as $key => $value ) {
				if ( $key === 'post_id' ) continue;
				if ( !array_key_exists( $key, $options ) ) {
					return new WP_REST_Response([ 'success' => false, 'message' => 'This option does not exist.' ], 200 );
				}
			}
			foreach ( $params as $key => $value ) {
				if ( $key === 'post_id' ) continue;
				update_post_meta( $postId, $key, $value );
			}
			return new WP_REST_Response([ 'success' => true ], 200 );
		}
		catch (Exception $e) {
			return new WP_REST_Response([
				'success' => false,
				'message' => $e->getMessage(),
			], 500 );
		}
	}
}

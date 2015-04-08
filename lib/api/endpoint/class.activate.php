<?php

/**
 * Endpoint for activating a license key.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
class ITELIC_API_Endpoint_Activate extends ITELIC_API_Endpoint implements ITELIC_API_Interface_Authenticatable {

	/**
	 * @var ITELIC_Key
	 */
	protected $key;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param ArrayAccess $get
	 * @param ArrayAccess $post
	 *
	 * @return ITELIC_API_Response
	 *
	 * @throws ITELIC_API_Exception|Exception
	 */
	public function serve( ArrayAccess $get, ArrayAccess $post ) {

		if ( ! isset( $post['location'] ) ) {
			throw new ITELIC_API_Exception( __( "Activation location is required.", ITELIC::SLUG ), self::CODE_NO_LOCATION );
		}

		$location = sanitize_text_field( $post['location'] );

		try {
			$activation = itelic_activate_license_key( $this->key, $location );
		}
		catch ( ITELIC_DB_Exception $e ) {
			if ( $e->getCode() == 1062 ) {
				$activation = itelic_get_activation_by_location( $location, $this->key );
				$activation->reactivate();
			} else {
				throw $e;
			}
		}
		catch ( LogicException $e ) {
			throw new ITELIC_API_Exception( $e->getMessage(), self::CODE_MAX_ACTIVATIONS, $e );
		}

		return new ITELIC_API_Response( array(
			'success' => true,
			'body'    => $activation
		) );
	}

	/**
	 * Retrieve the mode of authentication.
	 *
	 * @since 1.0
	 *
	 * @return string One of MODE_VALID, MODE_ACTIVE
	 */
	public function get_auth_mode() {
		return ITELIC_API_Interface_Authenticatable::MODE_ACTIVE;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "Your license key has expired.", ITELIC::SLUG );
	}

	/**
	 * Get the error code to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return int
	 */
	public function get_auth_error_code() {
		return self::CODE_INVALID_KEY;
	}

	/**
	 * Give a reference of the API key to this object.
	 *
	 * @since 1.0
	 *
	 * @param ITELIC_Key $key
	 */
	public function set_auth_license_key( ITELIC_Key $key ) {
		$this->key = $key;
	}
}
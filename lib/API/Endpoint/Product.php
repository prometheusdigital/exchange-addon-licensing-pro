<?php
/**
 * Product Endpoint API
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

namespace ITELIC\API\Endpoint;
use ITELIC\API\Endpoint;
use ITELIC\API\Contracts\Authenticatable;
use ITELIC\Key;
use ITELIC\API\Response;
use ITELIC\Plugin;

/**
 * Class Product
 * @package ITELIC\API\Endpoint
 */
class Product extends Endpoint implements Authenticatable {

	/**
	 * @var Key
	 */
	protected $key;

	/**
	 * Serve the request to this endpoint.
	 *
	 * @param \ArrayAccess $get
	 * @param \ArrayAccess $post
	 *
	 * @return Response
	 */
	public function serve( \ArrayAccess $get, \ArrayAccess $post ) {

		$readme = it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing-readme' );

		$contributors = array();

		if ( $readme['author'] ) {
			$usernames = explode( ',', $readme['author'] );

			foreach ( $usernames as $username ) {
				$contributors[ $username ] = "//profiles.wordpress.org/$username";
			}
		}

		$product = array(
			'id'              => $this->key->get_product()->ID,
			'name'            => $this->key->get_product()->post_title,
			'description'     => it_exchange_get_product_feature( $this->key->get_product()->ID, 'description' ),
			'version'         => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'version' ) ),
			'tested'          => $readme['tested'],
			'requires'        => $readme['requires'],
			'contributors'    => $contributors,
			'last_updated'    => empty( $readme['last_updated'] ) ? '' : $readme['last_updated']->format( \DateTime::ISO8601 ),
			'banner_low'      => $readme['banner_low'],
			'banner_high'     => $readme['banner_high'],
			'package_url'     => itelic_generate_download_link( $this->key, $this->key->get_product() ),
			'description_url' => get_permalink( $this->key->get_product()->ID ),
			'changelog'       => it_exchange_get_product_feature( $this->key->get_product()->ID, 'licensing', array( 'field' => 'changelog' ) ),
			'sections'        => array()
		);

		return new Response( array(
			'success' => true,
			'body'    => array(
				'list' => array(
					$this->key->get_product()->ID => $product
				)
			)
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
		return Authenticatable::MODE_EXISTS;
	}

	/**
	 * Get the error message to be displayed if authentication is not provided.
	 *
	 * @since 1.0
	 *
	 * @return string
	 */
	public function get_auth_error_message() {
		return __( "A license key is required.", Plugin::SLUG );
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
	 * @param Key $key
	 */
	public function set_auth_license_key( Key $key ) {
		$this->key = $key;
	}
}
<?php
/**
 * Keys WP CLI command.
 *
 * @author      Iron Bound Designs
 * @since       1.0
 * @copyright   2015 (c) Iron Bound Designs.
 * @license     GPLv2
 */

/**
 * Class ITELIC_Key_Command
 */
class ITELIC_Key_Command extends \WP_CLI\CommandWithDBObject {

	protected $obj_type = 'key';
	protected $obj_id_key = 'lkey';

	/**
	 * @var ITELIC_Fetcher
	 */
	protected $fetcher;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->fetcher = new ITELIC_Fetcher( '\ITELIC\Key' );
	}

	/**
	 * Get a license key's content by key.
	 *
	 * ## Options
	 *
	 * <key>
	 * : License key
	 *
	 * [--fields=<fields>]
	 * : Return designated object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param array $args
	 * @param array $assoc_args
	 */
	public function get( $args, $assoc_args ) {

		list( $key ) = $args;

		$object = $this->fetcher->get_check( $key );

		$fields = $this->get_fields_for_object( $object );

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array_keys( $fields );
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_item( $fields );
	}

	/**
	 * Get a list of keys
	 *
	 * ## Options
	 *
	 * [--<field>=<value>]
	 * : Include additional query args in keys query.
	 *
	 * [--fields=<fields>]
	 * : Return designated object fields.
	 *
	 * [--format=<format>]
	 * : Accepted values: table, json, csv. Default: table
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @subcommand list
	 */
	public function list_( $args, $assoc_args ) {

		$query_args = wp_parse_args( $assoc_args, array(
			'items_per_page' => 20,
			'page'           => 1
		) );

		$query_args['order'] = array(
			'transaction' => 'DESC'
		);

		$query = new \ITELIC_API\Query\Keys( $query_args );

		$results = $query->get_results();

		$items = array();

		foreach ( $results as $item ) {
			$items[] = $this->get_fields_for_object( $item );
		}

		if ( empty( $assoc_args['fields'] ) ) {
			$assoc_args['fields'] = array(
				'key',
				'status',
				'product',
				'customer'
			);
		}

		$formatter = $this->get_formatter( $assoc_args );
		$formatter->display_items( $items );
	}

	/**
	 * Extend a license key's expiration date.
	 *
	 * ## Options
	 *
	 * <key>
	 * : License key
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function extend( $args, $assoc_args ) {

		list( $key ) = $args;

		$key = $this->fetcher->get_check( $key );

		$result = $key->extend();

		if ( ! $result ) {
			WP_CLI::error( "This key does not have an expiry date." );
		}

		WP_CLI::success( sprintf( "New expiration date %s", $result->format( DateTime::ISO8601 ) ) );
	}

	/**
	 * Renew a license key.
	 *
	 * ## Options
	 *
	 * <key>
	 * : License key
	 *
	 * [<transaction>]
	 * : Optionally tie this renewal to a transaction
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function renew( $args, $assoc_args ) {

		list( $key, $transaction ) = array_pad( $args, 2, 0 );

		$key = $this->fetcher->get_check( $key );

		if ( ! empty( $transaction ) ) {
			$object = it_exchange_get_transaction( $transaction );

			if ( ! $object ) {
				WP_CLI::error( sprintf( "Invalid transaction with ID %d", $transaction ) );
			}

			$transaction = $object;
		} else {
			$transaction = null;
		}

		try {
			$result = $key->renew( $transaction );

			if ( $result ) {
				WP_CLI::success(
					sprintf( "Key has been renewed. New expiration date is %s",
						$key->get_expires()->format( DateTime::ISO8601 ) )
				);

				return;
			}
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::error( "An unknown error has occurred" );
	}

	/**
	 * Expire a license key.
	 *
	 * ## Options
	 *
	 * <key>
	 * : License key
	 *
	 * [<when>]
	 * : Specify when the license key expired. Accepts strtotime compatible value
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function expire( $args, $assoc_args ) {

		list( $key, $when ) = array_pad( $args, 2, 'now' );

		$key = $this->fetcher->get_check( $key );

		try {
			$when = new DateTime( $when );
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		$key->expire( $when );

		WP_CLI::success( "Key expired." );
	}

	/**
	 * Delete a license key.
	 *
	 * ## Options
	 *
	 * <key>
	 * : Key
	 *
	 * @param $args
	 * @param $assoc_args
	 */
	public function delete( $args, $assoc_args ) {

		list( $object ) = $args;

		$object = $this->fetcher->get_check( $object );

		try {
			$object->delete();
		}
		catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( "Key deleted." );
	}

	/**
	 * Get data to display for a single object.
	 *
	 * @param \ITELIC\Key $object
	 *
	 * @return array
	 */
	protected function get_fields_for_object( \ITELIC\Key $object ) {
		return array(
			'key'         => $object->get_key(),
			'status'      => $object->get_status( true ),
			'product'     => $object->get_product()->post_title,
			'transaction' => it_exchange_get_transaction_order_number( $object->get_transaction() ),
			'customer'    => $object->get_customer()->wp_user->display_name,
			'expires'     => $object->get_expires() ? $object->get_expires()->format( DateTime::ISO8601 ) : '-',
			'max'         => $object->get_max(),
			'activations' => $object->get_active_count()
		);
	}
}

WP_CLI::add_command( 'itelic key', 'ITELIC_Key_Command' );
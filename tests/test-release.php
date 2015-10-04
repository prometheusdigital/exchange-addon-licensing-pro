<?php
/**
 * Test release object.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */
use ITELIC\Release;
use ITELIC_API\Query\Updates;

/**
 * Class ITELIC_Test_Release
 */
class ITELIC_Test_Release extends ITELIC_UnitTestCase {

	public function test_invalid_status_is_rejected() {

		$mock_product = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file    = new WP_Post( new stdClass() );

		$version = '';
		$type    = Release::TYPE_MAJOR;
		$status  = 'garbage';

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type, $status );
	}

	public function test_invalid_type_is_rejected() {

		$mock_product = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file    = new WP_Post( new stdClass() );

		$version = '';
		$type    = 'garbage';

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

	public function test_invalid_post_type_for_file_is_rejected() {

		$mock_product         = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file            = new WP_Post( new stdClass() );
		$mock_file->post_type = 'not-attachment';

		$version = '';
		$type    = Release::TYPE_MAJOR;

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

	public function test_product_with_licensing_feature_disabled_is_rejected() {

		$mock_product         = $this->getMockBuilder( '\IT_Exchange_Product' )->disableOriginalConstructor()->getMock();
		$mock_file            = new WP_Post( new stdClass() );
		$mock_file->post_type = 'attachment';

		$version = '';
		$type    = Release::TYPE_MAJOR;

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $mock_product, $mock_file, $version, $type );
	}

	public function test_release_created() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		$r = Release::create( $product, get_post( $file ), '1.0', Release::TYPE_MAJOR );

		$this->assertInstanceOf( '\ITELIC\Release', $r );
	}

	public function test_default_status_is_draft() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR
		) );

		$this->assertEquals( Release::STATUS_DRAFT, $r->get_status() );
	}

	public function test_release_rejected_when_new_version_is_less_than_latest() {

		$product = $this->product_factory->create_and_get();

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR
		) );

		update_post_meta( $product->ID, '_itelic_first_release', $r1->get_pk() );

		$file2 = $this->factory->attachment->create_object( 'file2.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		Release::create( $product, get_post( $file2 ), '0.9', Release::TYPE_MAJOR );
	}

	public function test_product_version_updated_when_release_created() {

		$product = $this->product_factory->create_and_get();

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		$this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$saved_version = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'version' ) );

		$this->assertEquals( '1.1', $saved_version );
	}

	public function test_download_url_updated_when_release_created() {

		$product = $this->product_factory->create_and_get( array(
				'update-file' => wp_insert_post( array(
					'post_type' => 'it_exchange_download'
				) )
			)
		);

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		$this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$download_id   = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'update-file' ) );
		$download_data = get_post_meta( $download_id, '_it-exchange-download-info', true );

		$this->assertEquals( wp_get_attachment_url( $file1 ), $download_data['source'] );
	}

	public function test_activating_release_updates_version() {

		$product = $this->product_factory->create_and_get();

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->activate();

		$saved_version = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'version' ) );

		$this->assertEquals( '1.1', $saved_version );
	}

	public function test_activating_release_updates_url() {

		$product = $this->product_factory->create_and_get( array(
				'update-file' => wp_insert_post( array(
					'post_type' => 'it_exchange_download'
				) )
			)
		);

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->activate();

		$download_id   = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'update-file' ) );
		$download_data = get_post_meta( $download_id, '_it-exchange-download-info', true );

		$this->assertEquals( wp_get_attachment_url( $file1 ), $download_data['source'] );
	}

	public function test_activating_release_updates_status() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->activate();

		$this->assertEquals( Release::STATUS_ACTIVE, $r->get_status() );
	}

	public function test_activating_release_sets_start_date() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->activate();

		$this->assertEquals( \ITELIC\make_date_time()->getTimestamp(), $r->get_start_date()->getTimestamp(), '', 5 );
	}

	public function test_activating_release_sets_start_date_with_custom_date() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->activate( \ITELIC\make_date_time( 'yesterday' ) );

		$this->assertEquals( \ITELIC\make_date_time( 'yesterday' )->getTimestamp(), $r->get_start_date()->getTimestamp(), '', 5 );
	}

	public function test_activating_pre_release_does_not_update_version() {

		$product = $this->product_factory->create_and_get();

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_PRERELEASE
		) );

		$r->activate();

		$saved_version = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'version' ) );

		$this->assertEquals( '1.0', $saved_version );
	}

	public function test_activating_pre_release_does_not_update_url() {

		$product = $this->product_factory->create_and_get( array(
				'update-file' => wp_insert_post( array(
					'post_type' => 'it_exchange_download'
				) )
			)
		);

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.1',
			'type'    => Release::TYPE_PRERELEASE
		) );

		$r->activate();

		$download_id   = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'update-file' ) );
		$download_data = get_post_meta( $download_id, '_it-exchange-download-info', true );

		if ( ! is_array( $download_data ) ) {
			$download_data['source'] = '';
		}

		$this->assertNotEquals( wp_get_attachment_url( $file1 ), $download_data['source'] );
	}

	public function test_activating_release_clears_changelog_cache() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		wp_cache_set( $r->get_product()->ID, 'test', 'itelic-changelog' );

		$r->activate();

		$this->assertEmpty( wp_cache_get( $r->get_product()->ID, 'itelic-changelog' ) );
	}

	public function test_pausing_release_updates_status() {

		$product = $this->product_factory->create_and_get();

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		update_post_meta( $product->ID, '_itelic_first_release', $r1->get_pk() );

		$file2 = $this->factory->attachment->create_object( 'file-1.1.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file2,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$r2->pause();

		$saved_version = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'version' ) );

		$this->assertEquals( '1.0', $saved_version );
	}

	public function test_pausing_release_updates_url() {

		$product = $this->product_factory->create_and_get( array(
			'update-file' => wp_insert_post( array(
				'post_type' => 'it_exchange_download'
			) )
		) );

		$file1 = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r1 */
		$r1 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file1,
			'version' => '1.0',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		update_post_meta( $product->ID, '_itelic_first_release', $r1->get_pk() );

		$file2 = $this->factory->attachment->create_object( 'file - 1.1 . zip', $product->ID, array(
			'post_mime_type' => 'application / zip'
		) );

		/** @var Release $r2 */
		$r2 = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file2,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$r2->pause();

		$download_id   = it_exchange_get_product_feature( $product->ID, 'licensing', array( 'field' => 'update-file' ) );
		$download_data = get_post_meta( $download_id, '_it-exchange-download-info', true );

		$this->assertEquals( wp_get_attachment_url( $file1 ), $download_data['source'] );
	}

	public function test_pausing_release_clears_changelog_cache() {

		$product = $this->product_factory->create_and_get( array(
			'update-file' => wp_insert_post( array(
				'post_type' => 'it_exchange_download'
			) )
		) );

		$file = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		wp_cache_set( $r->get_product()->ID, 'test', 'itelic-changelog' );

		$r->pause();

		$this->assertEmpty( wp_cache_get( $r->get_product()->ID, 'itelic-changelog' ) );
	}

	public function test_archiving_release_updates_status() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->archive();

		$this->assertEquals( Release::STATUS_ARCHIVED, $r->get_status() );
	}

	public function test_archiving_release_saves_stats_to_meta() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1,
			'limit'    => 10
		) );

		$activations = $this->activation_factory->create_many( 5, array(
			'key' => $key
		) );

		foreach ( $activations as $activation ) {
			$this->update_factory->create( array(
				'activation'       => (int) $activation,
				'release'          => $r,
				'previous_version' => rand( 0, 1 ) ? '1.0' : '0.9'
			) );
		}

		$updated = $r->get_total_updated( true );
		$active  = $r->get_total_active_activations();
		$top5    = $r->get_top_5_previous_versions();
		$first14 = $r->get_first_14_days_of_upgrades();

		$r->archive();

		$this->assertEquals( $updated, $r->get_meta( 'updated', true ),
			"Total updated stats not saved." );
		$this->assertEquals( $active, $r->get_meta( 'activations', true ),
			"Total activations stats not saved." );
		$this->assertEquals( $top5, $r->get_meta( 'top5_prev_version', true ),
			"Top 5 prev versions stats not saved." );
		$this->assertEquals( $first14, $r->get_meta( 'first_14_days', true ),
			"First 14 days of activations stats not saved." );
	}

	public function test_archiving_release_deletes_update_records() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR,
			'status'  => Release::STATUS_ACTIVE
		) );

		$key = $this->key_factory->create_and_get( array(
			'product'  => $product->ID,
			'customer' => 1,
			'limit'    => 10
		) );

		$activations = $this->activation_factory->create_many( 5, array(
			'key' => $key
		) );

		foreach ( $activations as $activation ) {
			$this->update_factory->create( array(
				'activation'       => (int) $activation,
				'release'          => $r,
				'previous_version' => rand( 0, 1 ) ? '1.0' : '0.9'
			) );
		}

		$r->archive();

		$updates = new Updates( array(
			'release' => $r->get_ID()
		) );

		$this->assertEmpty( $updates->get_results() );
	}

	public function test_updating_download_with_invalid_post_type_is_rejected() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$post = $this->factory->post->create();
		$r->set_download( $post );
	}

	public function test_updating_version_with_version_lower_than_current_is_rejected() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$r->set_version( '1.0' );
	}

	public function test_updating_status_with_invalid_status_is_rejected() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$r->set_status( 'garbage' );
	}

	public function test_updating_type_with_invalid_type_is_rejected() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$this->setExpectedException( '\InvalidArgumentException' );

		$r->set_type( 'garbage' );
	}

	public function test_updating_changelog_clears_cache() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		wp_cache_set( $product->ID, 'test', 'itelic-changelog' );

		$r->set_changelog( 'content' );

		$this->assertEmpty( wp_cache_get( $product->ID, 'itelic-changelog' ) );
	}

	public function test_replacing_changelog() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product' => $product->ID,
			'file'    => $file,
			'version' => '1.1',
			'type'    => Release::TYPE_MAJOR
		) );

		$r->set_changelog( 'changes' );

		$this->assertEquals( 'changes', $r->get_changelog() );
	}

	public function test_appending_to_changelog() {

		$product = $this->product_factory->create_and_get();
		$file    = $this->factory->attachment->create_object( 'file.zip', $product->ID, array(
			'post_mime_type' => 'application/zip'
		) );

		/** @var Release $r */
		$r = $this->release_factory->create_and_get( array(
			'product'   => $product->ID,
			'file'      => $file,
			'version'   => '1.1',
			'type'      => Release::TYPE_MAJOR,
			'changelog' => 'first'
		) );

		$r->set_changelog( 'second', 'append' );

		$this->assertEquals( 'firstsecond', $r->get_changelog() );
	}
}
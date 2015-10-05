<?php
/**
 * Test internal functions.
 *
 * @author Iron Bound Designs
 * @since  1.0
 */

/**
 * Class ITELIC_Test_Functions
 */
class ITELIC_Test_Functions extends ITELIC_UnitTestCase {

	/**
	 * @dataProvider rp_date_interval_provider
	 */
	public function test_convert_rp_to_date_interval( $type, $count, $expected ) {
		$this->assertEquals( $expected, \ITELIC\convert_rp_to_date_interval( $type, $count ) );
	}

	public function rp_date_interval_provider() {
		return array(
			array( 'day', '1', new DateInterval( 'P1D' ) ),
			array( 'day', '3', new DateInterval( 'P3D' ) ),
			array( 'week', '1', new DateInterval( 'P1W' ) ),
			array( 'week', '4', new DateInterval( 'P4W' ) ),
			array( 'month', '1', new DateInterval( 'P1M' ) ),
			array( 'month', '12', new DateInterval( 'P12M' ) ),
			array( 'year', '1', new DateInterval( 'P1Y' ) ),
			array( 'year', '10', new DateInterval( 'P10Y' ) ),
		);
	}
}
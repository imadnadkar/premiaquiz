<?php
/**
 * Basic plugin test.
 *
 * @package PremiaQuiz
 */

/**
 * Class PremiaQuizTest
 */
class PremiaQuizTest extends \PHPUnit\Framework\TestCase {

	/**
	 * Test plugin constants are defined.
	 *
	 * @return void
	 */
	public function test_plugin_constants_defined() {
		$this->assertTrue( defined( 'PREMIAQUIZ_VERSION' ) );
		$this->assertTrue( defined( 'PREMIAQUIZ_PLUGIN_DIR' ) );
		$this->assertTrue( defined( 'PREMIAQUIZ_PLUGIN_URL' ) );
		$this->assertTrue( defined( 'PREMIAQUIZ_PLUGIN_BASENAME' ) );
	}

	/**
	 * Test plugin version matches package.json.
	 *
	 * @return void
	 */
	public function test_plugin_version() {
		$this->assertEquals( '0.1.0', PREMIAQUIZ_VERSION );
	}
}

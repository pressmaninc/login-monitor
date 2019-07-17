<?php

/**
 * Class Login_Monitor_SCRIPT_DEBUG_Test
 *
 * @package Login_Monitor
 */

class Login_Monitor_SCRIPT_DEBUG_Test extends WP_UnitTestCase {
	/** @var Login_Monitor */
	private static $lm;

	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		global $lm;
		self::$lm = $lm;
	}

	/**
	 * @covers Login_Monitor::enqueue
	 */
	public function test__enqueue() {
		global $wp_styles, $wp_scripts;

		add_filter( 'show_admin_bar', function ( $show_admin_bar ) {
			return true;
		} );
		add_filter( 'change_lm_capability', function ( $capability ) {
			return 'read';
		} );
		wp_set_current_user( $this->factory()->user->create() );
		self::$lm->enqueue();
		$this->assertTrue( wp_style_is( 'login-monitor' ) );
		$this->assertTrue( wp_script_is( 'login-monitor' ) );
		$this->assertEquals( plugin_dir_url( dirname( __DIR__ ) ) . 'css/login-monitor.css', $wp_styles->registered['login-monitor']->src );
		$this->assertEquals( plugin_dir_url( dirname( __DIR__ ) ) . 'js/login-monitor.js', $wp_scripts->registered['login-monitor']->src );
	}


}

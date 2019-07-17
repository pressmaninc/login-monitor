<?php
/**
 * Class Login_Monitor_Test
 *
 * @package Login_Monitor
 */

class Login_Monitor_Test extends WP_UnitTestCase {
	/** @var Login_Monitor */
	private static $lm;

	/**
	 * setup once
	 */
	public static function setUpBeforeClass() {
		parent::setUpBeforeClass();
		global $lm;
		self::$lm = $lm;
	}

	############################################################################

	/**
	 * @covers Login_Monitor::__construct
	 * @throws ReflectionException
	 */
	public function test____construct() {
		$ref  = new ReflectionClass( self::$lm );
		$prop = $ref->getProperty( 'version' );
		$prop->setAccessible( true );
		$expected = get_file_data( dirname( dirname( __DIR__ ) ) . '/login-monitor.php', [ 'v' => 'Version' ] )['v'];

		$this->assertEquals( $expected, $prop->getValue( self::$lm ) );
	}

	/**
	 * @covers Login_Monitor::run
	 */
	public function test__run() {
		$plugin = plugin_basename( plugin_dir_path( dirname( __DIR__ ) ) . 'login-monitor.php' );

		$this->assertNotFalse( has_action( "activate_{$plugin}", [ self::$lm, 'activate_plugin' ] ) );
		$this->assertNotFalse( has_action( "deactivate_{$plugin}", [ self::$lm, 'deactivate_plugin' ] ) );

		$this->assertNotFalse( has_action( 'plugins_loaded', [ self::$lm, 'load_text_domain' ] ) );
		$this->assertNotFalse( has_action( 'wp_enqueue_scripts', [ self::$lm, 'enqueue' ] ) );
		$this->assertEquals( 999, has_action( 'admin_bar_menu', [ self::$lm, 'add_lm_node' ] ) );
		$this->assertNotFalse( has_action( 'wp_ajax_login-monitor', [ self::$lm, 'admin_ajax' ] ) );
	}

	/**
	 * @covers Login_Monitor::activate_plugin
	 * @throws ReflectionException
	 */
	public function test__activate_plugin() {
		self::$lm->activate_plugin();
		$ref  = new ReflectionClass( self::$lm );
		$prop = $ref->getProperty( 'version' );
		$prop->setAccessible( true );
		$this->assertEquals( $prop->getValue( self::$lm ), get_option( 'login-monitor_version' ) );
	}

	/**
	 * @covers Login_Monitor::deactivate_plugin
	 */
	public function test__deactivate_plugin() {
		update_option( 'login-monitor_version', 'version' );
		self::$lm->deactivate_plugin();
		$this->assertFalse( get_option( 'login-monitor_version' ) );
	}

	/**
	 * @covers Login_Monitor::load_text_domain
	 */
	public function test__load_text_domain() {
		self::$lm->load_text_domain();
		global $l10n;
		$this->assertArrayHasKey( 'login-monitor', $l10n );
		$this->assertEquals( 'ログイン', __( 'Logged in', 'login-monitor' ) );
	}

	/**
	 * @covers Login_Monitor::enqueue
	 */
	public function test__enqueue() {
		global $wp_styles, $wp_scripts;

		add_filter( 'change_lm_capability', [ $this, 'change_lm_capability' ] );
		add_filter( 'change_lm_lifetime', [ $this, 'change_lm_lifetime' ] );
		self::$lm->enqueue();
		$this->assertFalse( wp_style_is( 'login-monitor' ) );
		$this->assertFalse( wp_script_is( 'login-monitor' ) );
		$this->assertNotFalse( has_filter( 'change_lm_capability', [ $this, 'change_lm_capability' ] ) );
		$this->assertNotFalse( has_filter( 'change_lm_lifetime', [ $this, 'change_lm_lifetime' ] ) );

		add_filter( 'show_admin_bar', [ $this, 'show_admin_bar' ] );
		self::$lm->enqueue();
		$this->assertTrue( wp_style_is( 'login-monitor' ) );
		$this->assertFalse( wp_script_is( 'login-monitor' ) );
		$this->assertEquals( plugin_dir_url( dirname( __DIR__ ) ) . 'css/login-monitor.min.css', $wp_styles->registered['login-monitor']->src );

		wp_set_current_user( $this->factory()->user->create() );
		self::$lm->enqueue();
		$this->assertTrue( wp_style_is( 'login-monitor' ) );
		$this->assertTrue( wp_script_is( 'login-monitor' ) );
		$this->assertEquals( plugin_dir_url( dirname( __DIR__ ) ) . 'js/login-monitor.min.js', $wp_scripts->registered['login-monitor']->src );
		$expected = 'var LOGIN_MONITOR_CONST = ' . json_encode( [
				'url'      => admin_url( 'admin-ajax.php' ),
				'action'   => 'login-monitor',
				'lifetime' => 1,
			] ) . ';';
		$this->assertEquals( $expected, $wp_scripts->registered['login-monitor']->extra['before'][1] );
	}

	public function test__add_lm_node() {
		require_once ABSPATH . 'wp-includes/class-wp-admin-bar.php';
		$wab = new WP_Admin_Bar();
		self::$lm->add_lm_node( $wab );
		$ref  = new ReflectionClass( $wab );
		$prop = $ref->getProperty( 'nodes' );
		$prop->setAccessible( true );
		$nodes = $prop->getValue( $wab );

		$this->assertEquals( 'login-monitor', $nodes['login-monitor']->id );
		$this->assertEquals( '<span class="ab-icon"></span><span class="ab-label"><span id="lm-cnt">--</span> ' . __( 'Logged in', 'login-monitor' ) . '</span>', $nodes['login-monitor']->title );
		$this->assertEquals( 'top-secondary', $nodes['login-monitor']->parent );
		$this->assertEquals( '#', $nodes['login-monitor']->href );
		$this->assertEquals( false, $nodes['login-monitor']->group );
		$this->assertEquals( [], $nodes['login-monitor']->meta );

		$this->assertEquals( 'login-monitor-detail', $nodes['login-monitor-detail']->id );
		$this->assertEquals( '<ul id="lm-list"></ul>', $nodes['login-monitor-detail']->title );
		$this->assertEquals( 'login-monitor', $nodes['login-monitor-detail']->parent );
		$this->assertEquals( '#', $nodes['login-monitor-detail']->href );
		$this->assertEquals( false, $nodes['login-monitor-detail']->group );
		$this->assertEquals( [], $nodes['login-monitor-detail']->meta );
	}

	############################################################################
	public function change_lm_capability( $capability ) {
		return 'read';
	}

	public function change_lm_lifetime( $lifetime ) {
		return 1;
	}

	public function show_admin_bar( $show_admin_bar ) {
		return true;
	}
}

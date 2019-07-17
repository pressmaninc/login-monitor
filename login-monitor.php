<?php
/**
 * Plugin Name: Login Monitor
 * Description: Displays current logged in users in administration screens in real time.
 * Version: 1.0.3
 * Author: PRESSMAN
 * Author URI: https://www.pressman.ne.jp
 * Text Domain: login-monitor
 * Domain Path: /languages
 *
 * @author    PRESSMAN
 * @link      https://www.pressman.ne.jp
 * @copyright Copyright (c) 2018, PRESSMAN
 * @license   http://www.gnu.org/licenses/old-licenses/gpl-2.0.html GNU General Public License, v2 or higher
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Login_Monitor {
	const UM_KEY = 'lm_session';
	const LIFETIME = 30; // 30 sec.

	private $version;

	public function __construct() {
		$this->version = get_file_data( __FILE__, [ 'v' => 'Version' ] )['v'];
	}

	public function run() {
		register_activation_hook( __FILE__, [ $this, 'activate_plugin' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin' ] );

		add_action( 'plugins_loaded', [ $this, 'load_text_domain' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_bar_menu', [ $this, 'add_lm_node' ], 999 );
		add_action( 'wp_ajax_login-monitor', [ $this, 'admin_ajax' ] );
	}

	public function activate_plugin() {
		update_option( 'login-monitor_version', $this->version );
	}

	public function deactivate_plugin() {
		delete_option( 'login-monitor_version' );
	}

	public function load_text_domain() {
		load_plugin_textdomain( 'login-monitor', false, basename( __DIR__ ) . '/languages' );
	}

	public function enqueue() {
		$ext = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

		if ( is_admin_bar_showing() ) {
			wp_enqueue_style( 'login-monitor', plugin_dir_url( __FILE__ ) . "css/login-monitor{$ext}.css", [], $this->version );
		}

		$capability = apply_filters( 'change_lm_capability', 'edit_posts' );

		if ( is_user_logged_in() && current_user_can( $capability ) ) {
			wp_enqueue_script( 'login-monitor', plugin_dir_url( __FILE__ ) . "js/login-monitor{$ext}.js", [], $this->version, true );
			$lifetime = apply_filters( 'change_lm_lifetime', self::LIFETIME );
			$script   = 'var LOGIN_MONITOR_CONST = ' . json_encode( [
					'url'      => admin_url( 'admin-ajax.php' ),
					'action'   => 'login-monitor',
					'lifetime' => $lifetime,
				] ) . ';';

			wp_add_inline_script( 'login-monitor', $script, 'before' );
		}
	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function add_lm_node( $wp_admin_bar ) {
		$wp_admin_bar->add_node( [
			'id'     => 'login-monitor',
			'parent' => 'top-secondary',
			'meta'   => [],
			'title'  => '<span class="ab-icon"></span><span class="ab-label"><span id="lm-cnt">--</span> ' . __( 'Logged in', 'login-monitor' ) . '</span>',
			'href'   => '#',
		] );

		$wp_admin_bar->add_node( [
			'id'     => 'login-monitor-detail',
			'parent' => 'login-monitor',
			'meta'   => [],
			'title'  => '<ul id="lm-list"></ul>',
			'href'   => '#',
		] );
	}

	public function admin_ajax() {
		$ary = [];
		$now = time();

		update_user_meta( get_current_user_id(), self::UM_KEY, $now );

		$lifetime = apply_filters( 'change_lm_lifetime', self::LIFETIME );
		$expire   = $now - $lifetime;

		$users = new WP_User_Query( [
			'meta_key'     => self::UM_KEY,
			'meta_value'   => $expire,
			'meta_compare' => '>',
		] );

		foreach ( $users->get_results() as $user ) {
			$ary[] = [
				'id'           => $user->ID,
				'nice_name'    => $user->user_nicename,
				'display_name' => $user->display_name,
				'color'        => substr( md5( $user->display_name ), 0, 6 ),
			];
		}

		wp_send_json( $ary );
	}
}

global $lm;
$lm = new Login_Monitor();
$lm->run();

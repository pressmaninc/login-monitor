<?php
/**
 * Plugin Name: Login Monitor
 * Description: Displays the currently logged in user (can access admin page) in real time.
 * Version: 0.9
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
	const VERSION = 0.9;
	const UM_KEY = 'lm_session';
	const LIFETIME = 30; // 30 sec.

	public function __construct() {
	}

	public function run() {
		register_activation_hook( __FILE__, [ $this, 'activate_plugin' ] );
		register_deactivation_hook( __FILE__, [ $this, 'deactivate_plugin' ] );

		add_action( 'admin_bar_menu', [ $this, 'add_lm_node' ], 999 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue' ] );

		add_action( 'wp_ajax_login-monitor', [ $this, 'admin_ajax' ] );
	}

	public function activate_plugin() {
		$ver = get_option( 'login-monitor_version' );

		if ( ! $ver ) {
			add_option( 'login-monitor_version', $this::VERSION );
		} elseif ( $ver !== $this::VERSION ) {
			update_option( 'login-monitor_version', $this::VERSION );
		}
	}

	public function deactivate_plugin() {
		delete_option( 'login-monitor_version' );
	}


	public function enqueue() {
		if ( is_admin_bar_showing() ) {
			wp_enqueue_style( 'login-monitor', plugin_dir_url( __FILE__ ) . 'css/login-monitor.min.css', [], $this::VERSION );
		}

		$capability = apply_filters( 'change_lm_capability', 'edit_posts' );

		if ( is_user_logged_in() && current_user_can( $capability ) ) {
			wp_enqueue_script( 'login-monitor', plugin_dir_url( __FILE__ ) . 'js/login-monitor.min.js', [], $this::VERSION, true );
			$lifetime = apply_filters( 'change_lm_lifetime', $this::LIFETIME );
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
			'title'  => '<span class="ab-icon"></span><span class="ab-label"><span id="lm-cnt">0</span> ' . __( 'Logged in', 'login-monitor' ) . '</span>',
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
		$uid = get_current_user_id();
		$now = time();

		if ( get_user_meta( $uid, $this::UM_KEY ) ) {
			update_user_meta( $uid, $this::UM_KEY, $now );
		} else {
			add_user_meta( $uid, $this::UM_KEY, $now );
		}

		$lifetime = apply_filters( 'change_lm_lifetime', $this::LIFETIME );
		$expire   = $now - $lifetime;

		$users = new WP_User_Query( [
			'meta_key'     => $this::UM_KEY,
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

		header( 'Content-Type: application/json' );
		echo json_encode( $ary );
		die();
	}
}

$lm = new Login_Monitor();
$lm->run();
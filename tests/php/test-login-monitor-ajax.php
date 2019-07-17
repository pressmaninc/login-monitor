<?php

/**
 * Class Login_Monitor_Ajax_Test
 *
 * @package Login_Monitor
 */

class Login_Monitor_Ajax_Test extends WP_Ajax_UnitTestCase {

	/**
	 * @covers Login_Monitor::admin_ajax
	 */
	public function test__admin_ajax() {
		/** @var WP_User $user */
		$user = $this->factory()->user->create_and_get();
		wp_set_current_user( $user->ID );

		try {
			$this->_handleAjax( 'login-monitor' );
		} catch ( WPAjaxDieContinueException $e ) {
		}

		$expended = [
			[
				'id'           => $user->ID,
				'nice_name'    => $user->user_nicename,
				'display_name' => $user->display_name,
				'color'        => substr( md5( $user->display_name ), 0, 6 )
			]
		];

		$this->assertEquals( json_encode( $expended ), $this->_last_response );
	}
}

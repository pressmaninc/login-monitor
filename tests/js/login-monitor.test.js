'use strict';

jest.useFakeTimers();
const consoleError = jest.spyOn( console, 'error' );
consoleError.mockImplementation();
const initBody = `
		<div id="wp-admin-bar-login-monitor">
			<span id="lm-cnt"></span>
			<ul id="lm-list"></ul>
		</div>
		`;

describe( 'loginMonitor', () => {
	beforeAll( () => {
		document.body.innerHTML = `
		<div id="wp-admin-bar-login-monitor">
			<span id="lm-cnt"></span>
			<ul id="lm-list"></ul>
		</div>
		`;
		global.LOGIN_MONITOR_CONST = {
			url: 'http://localhost',
			action: 'act',
			lifetime: 0.1
		};
		fetch.mockResponse(
			JSON.stringify( [{display_name: 'foo', color: 'ff0000'}] ),
			{
				status: 200,
				headers: {
					'content-type': 'application/json'
				}
			}
		);
		require( '../../js/login-monitor' );
		const event = document.createEvent( 'Event' );
		event.initEvent( 'DOMContentLoaded', true, true );
		window.document.dispatchEvent( event );
	} );

	beforeEach( () => {
		fetch.resetMocks();
		fetch.mockResponse(
			JSON.stringify( [{display_name: 'foo', color: 'ff0000'}] ),
			{
				status: 200,
				headers: {
					'content-type': 'application/json'
				}
			}
		);

	} );

	afterEach(() => {
		document.querySelector('#lm-cnt').innerHTML = '';
		document.querySelector('#lm-list').innerHTML = '';
	});

	it( "success", () => {
		jest.advanceTimersByTime( 100 );
		expect( fetch ).toBeCalled();
		expect( fetch.mock.calls[0][0] ).toBe( LOGIN_MONITOR_CONST.url );
		expect( fetch.mock.calls[0][1].method ).toBe( 'POST' );
		expect( fetch.mock.calls[0][1].headers ).toEqual( {'Content-Type': 'application/x-www-form-urlencoded'} );
		expect( fetch.mock.calls[0][1].body ).toBe( `action=${LOGIN_MONITOR_CONST.action}` );
		const li = document.querySelectorAll( '#lm-list > li' );
		expect( li.length ).toBe( 1 );
		expect( li[0].innerHTML ).toBe( '<span class="lm-badge" style="background: rgb(255, 0, 0);"><li class="lm-badge-str"></li></span>foo' );
	} );

	it( "throw 'INVALID TOKEN' error when response status 400", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'INVALID TOKEN' ) );
			done();
		} );
		fetch.once( '', {status: 400} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'UNAUTHORIZED' error when response status 401", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'UNAUTHORIZED' ) );
			done();
		} );
		fetch.once( '', {status: 401} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'INTERNAL SERVER ERROR' error when response status 500 ", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'INTERNAL SERVER ERROR' ) );
			done();
		} );
		fetch.once( '', {status: 500} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'BAD GATEWAY' error when response status 502 ", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'BAD GATEWAY' ) );
			done();
		} );
		fetch.once( '', {status: 502} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'NOT FOUND' error when response status 404 ", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'NOT FOUND' ) );
			done();
		} );
		fetch.once( '', {status: 404} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'UNHANDLED ERROR' error when response status is not defined ", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( Error( 'UNHANDLED ERROR' ) );
			done();
		} );
		fetch.once( '', {status: 418} );
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );

	it( "throw 'Not JSON' error when response body is not JSON", done => {
		consoleError.mockImplementationOnce( error => {
			expect( error ).toEqual( TypeError( 'Not JSON' ) );
			done();
		} );
		fetch.once(
			'<?xml version="1.0"?><user display_name="foo" color="ff0000" />',
			{
				status: 200,
				headers: {
					'content-type': 'application/xml'
				}
			}
		);
		jest.advanceTimersByTime( 100 );
		expect(document.body.innerHTML).toBe(initBody);
	} );
} );

describe('loginMonitor (not admin-bar)', () => {
	beforeAll( () => {
		document.body.innerHTML = '<div></div>';
		global.LOGIN_MONITOR_CONST = {
			url: 'http://localhost',
			action: 'act',
			lifetime: 0.1
		};
		fetch.mockResponse(
			JSON.stringify( [{display_name: 'foo', color: 'ff0000'}] ),
			{
				status: 200,
				headers: {
					'content-type': 'application/json'
				}
			}
		);
		require( '../../js/login-monitor' );
		const event = document.createEvent( 'Event' );
		event.initEvent( 'DOMContentLoaded', true, true );
		window.document.dispatchEvent( event );
	} );

	it( "success", () => {
		jest.advanceTimersByTime( 100 );
		expect( fetch ).toBeCalled();
		expect( fetch.mock.calls[0][0] ).toBe( LOGIN_MONITOR_CONST.url );
		expect( fetch.mock.calls[0][1].method ).toBe( 'POST' );
		expect( fetch.mock.calls[0][1].headers ).toEqual( {'Content-Type': 'application/x-www-form-urlencoded'} );
		expect( fetch.mock.calls[0][1].body ).toBe( `action=${LOGIN_MONITOR_CONST.action}` );
		expect(document.body.innerHTML).toBe('<div></div>');
	} );
});

document.addEventListener( 'DOMContentLoaded', () => {
	const wpAdminBarLoginMonitor = document.getElementById( 'wp-admin-bar-login-monitor' );

	const loginMonitorRefresh = () => {
		fetch(
			LOGIN_MONITOR_CONST.url,
			{
				method: 'POST',
				headers: {'Content-Type': 'application/x-www-form-urlencoded'},
				body: (
					new URLSearchParams( {action: LOGIN_MONITOR_CONST.action} )
				).toString()
			}
		).then( response => {
			if ( ! response.ok ) {
				switch ( response.status ) {
					case 400:
						throw Error( 'INVALID TOKEN' );
					case 401:
						throw Error( 'UNAUTHORIZED' );
					case 500:
						throw Error( 'INTERNAL SERVER ERROR' );
					case 502:
						throw Error( 'BAD GATEWAY' );
					case 404:
						throw Error( 'NOT FOUND' );
					default:
						throw Error( 'UNHANDLED ERROR' );
				}
			}

			const contentType = response.headers.get( 'content-type' );

			if ( ! contentType || ! contentType.includes( 'application/json' ) ) {
				throw new TypeError( 'Not JSON' );
			}

			return response.json();
		} ).then( json => {
			if ( wpAdminBarLoginMonitor ) {
				document.getElementById( 'lm-cnt' ).innerText = json.length;
				const ul = document.getElementById( 'lm-list' );
				ul.innerHTML = '';

				for ( let i in json ) {
					const li = document.createElement( 'li' ),
						badge = document.createElement( 'span' ),
						badgeStr = document.createElement( 'li' ),
						name = document.createTextNode( json[i].display_name );

					badge.classList.add( 'lm-badge' );
					badge.style.background = '#' + json[i].color;

					badgeStr.classList.add( 'lm-badge-str' );
					badgeStr.innerText = json[i].display_name.substr( 0, 1 ).toUpperCase();
					badge.appendChild( badgeStr );

					li.appendChild( badge );
					li.appendChild( name );

					ul.appendChild( li );
				}
			}
		} ).catch( error => console.error( error ) );
	};

	setInterval( loginMonitorRefresh, LOGIN_MONITOR_CONST.lifetime * 1000 );
	loginMonitorRefresh();
} );


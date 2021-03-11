// 404 page Tracking
( function () {
	var eventName = '404';

	if ( typeof window.plausible === 'undefined' || typeof mw.config === 'undefined' ) {
		return;
	}

	if ( mw.config.get( 'is404', false ) === true ) {
		window.plausible( eventName, {
			props: {
				path: document.location.pathname
			}
		} );
	}
}() );

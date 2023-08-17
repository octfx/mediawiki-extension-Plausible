// 404 page Tracking
( function () {
	var eventName = '404',
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\';

	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 || typeof mw.config === 'undefined' ) {
		return;
	}

	if ( mw.config.get( 'is404', false ) === true ) {
		window.plausible( eventName, {
			props: {
				title: document.location.pathname,
				isAnon,
			}
		} );
	}
}() );

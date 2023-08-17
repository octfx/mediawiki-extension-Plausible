// Menu Link click Tracking
( function () {
	var eventName = 'Citizen: Menu Link Click',
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\';

	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 ) {
		return;
	}

	document.querySelectorAll( '.citizen-drawer__menu nav a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			if ( typeof event.target.href !== 'undefined' && event.target.href !== null ) {
				event.preventDefault();
				event.stopPropagation();

				window.plausible( eventName, {
					props: {
						entry: event.target.innerText,
						title: event.target.href,
						isAnon,
					},
					callback: function () {
						window.location = event.target.href;
					}
				} );
			}
		} );
	} );
}() );

// Menu Link click Tracking
( function () {
	var eventName = 'CitizenMenuLinkClick';

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
						path: event.target.href
					},
					callback: function () {
						window.location = event.target.href;
					}
				} );
			}
		} );
	} );
}() );

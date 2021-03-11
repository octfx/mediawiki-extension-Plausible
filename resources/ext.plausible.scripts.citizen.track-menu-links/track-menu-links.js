// Menu Link click Tracking
( function () {
	var eventName = 'CitizenMenuLinkClick';

	if ( typeof window.plausible === 'undefined' ) {
		return;
	}

	document.querySelectorAll( '.mw-portal li a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			event.stopPropagation();
			window.plausible( eventName, {
				props: { href: event.target.href },
				callback: function () {
					window.location = event.target.href;
				}
			} );
		} );
	} );
}() );

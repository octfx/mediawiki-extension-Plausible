// Menu Link click Tracking
( function () {
	var eventName = 'CitizenMenuLinkClick';

	if ( typeof window.plausible === 'undefined' ) {
		return;
	}

	document.querySelectorAll( '#mw-drawer-menu .mw-portal li a' ).forEach( function ( link ) {
		link.addEventListener( 'click', function ( event ) {
			window.plausible( eventName, {
				props: { href: event.target.href },
			} );
		} );
	} );
}() );

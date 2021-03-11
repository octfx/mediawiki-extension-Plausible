// Edit Button Tracking
( function () {
	var eventName = 'EditButtonClick',
		btn = document.querySelector( '#ca-edit a' );

	if ( typeof window.plausible === 'undefined' || btn === null ) {
		return;
	}

	btn.addEventListener( 'click', function () {
		window.plausible( eventName, { props: { page: window.location.href } } );
	} );
}() );

// Search Input Tracking
( function () {
	var eventName = 'SearchInput',
		search = document.getElementById( 'searchInput' ),
		sendAfter = 100, // ms
		timeoutId;

	if ( typeof window.plausible === 'undefined' || search === null ) {
		return;
	}

	search.addEventListener( 'input', function ( event ) {
		if ( event.target.value === '' ) {
			return;
		}

		clearTimeout( timeoutId );

		timeoutId = setTimeout( function () {
			window.plausible( eventName, { props: { query: event.target.value } } );
		}, sendAfter );
	} );
}() );

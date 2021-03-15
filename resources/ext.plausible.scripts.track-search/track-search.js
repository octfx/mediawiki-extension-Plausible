// Search Input Tracking
( function () {
	var eventName = 'SearchInput',
		search = document.getElementById( 'searchInput' ),
		sendAfter = 1500, // ms
		minLength = 3,
		timeoutId;

	if ( typeof window.plausible === 'undefined' || search === null ) {
		return;
	}

	search.addEventListener( 'input', function ( event ) {
		clearTimeout( timeoutId );

		if ( event.target.value !== '' && event.target.value.length >= minLength ) {
			timeoutId = setTimeout( function () {
				window.plausible(
					eventName, {
						props: {
							query: event.target.value,
							path: document.location.pathname
						}
					}
				);
			}, sendAfter );
		}
	} );
}() );

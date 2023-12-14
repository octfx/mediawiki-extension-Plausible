// Search Input Tracking
( function () {
	var eventName = 'Search: Input',
		search = document.getElementById( 'searchInput' ),
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\',
		sendAfter = 1500, // ms
		minLength = 3,
		timeoutId;

	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 || search === null ) {
		return;
	}

	search.addEventListener( 'input', function ( event ) {
		clearTimeout( timeoutId );

		if ( event.target.value !== '' && event.target.value.length >= minLength ) {
			timeoutId = setTimeout( function () {
				window.plausible(
					eventName, {
						props: {
							term: event.target.value,
							title: document.location.pathname,
							isAnon,
						}
					}
				);
			}, sendAfter );
		}
	} );
}() );

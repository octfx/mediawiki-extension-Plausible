// Search Link click Tracking
( function () {
	var eventName = 'CitizenSearchLinkClick',
		suggestions = document.getElementById( 'searchform' ),
		callback = function ( event ) {
			window.plausible( eventName, {
				props: { href: event.target.parentNode.parentNode.href },
				callback: function () {
					window.location = event.target.parentNode.parentNode.href;
				}
			} );
		},
		observer,
		idxMutation,
		idxChild,
		dropdown;

	if ( typeof window.plausible === 'undefined' || suggestions === null ) {
		return;
	}

	observer = new MutationObserver( function ( mutationList ) {
		for ( idxMutation = 0; idxMutation < mutationList.length; idxMutation++ ) {
			if ( mutationList[ idxMutation ].addedNodes.length === 0 ) {
				continue;
			}

			dropdown = mutationList[ idxMutation ].addedNodes[ 0 ];
			if ( typeof dropdown.childNodes === 'undefined' ) {
				return;
			}

			for ( idxChild = 0; idxChild < dropdown.childNodes.length; idxChild++ ) {
				dropdown.childNodes[ idxChild ].addEventListener( 'click', callback );
			}
		}
	} );

	observer.observe( suggestions, { childList: true, subtree: true } );
}() );

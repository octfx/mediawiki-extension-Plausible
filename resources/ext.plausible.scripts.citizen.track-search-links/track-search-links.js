// Search Link click Tracking
( function () {
	var eventName = 'CitizenSearchLinkClick',
		suggestions = document.getElementById( 'searchform' ),
		callback = function ( event ) {
			var currentEl,
				href = null;

			currentEl = event.target.parentNode;

			while ( typeof currentEl !== 'undefined' ) {
				if ( currentEl.classList.contains( 'suggestion-link' ) ) {
					href = currentEl.href;

					break;
				}

				currentEl = currentEl.parentNode;
			}

			if ( href !== null ) {
				event.preventDefault();
				event.stopPropagation();
			}

			window.plausible( eventName, {
				props: { href: href },
				callback: function () {
					window.location = href;
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

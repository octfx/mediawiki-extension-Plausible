// Search Link click Tracking
( function () {
	var eventName = 'CitizenSearchLinkClick',
		suggestions = document.getElementById( 'searchform' ),
		search = document.getElementById( 'searchInput' ),
		callback = function ( event ) {
			var currentEl,
				href = null,
				url,
				searchValue;

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

			url = new URL( href );
			// Catch invalid urls, should not happen
			if ( url === false ) {
				url = {
					pathname: href
				};
			}

			searchValue = typeof search.value === 'undefined' ? null : search.value;

			window.plausible( eventName, {
				props: {
					query: searchValue,
					path: url.pathname
				},
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

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

			while ( typeof currentEl !== 'undefined' && currentEl !== null && typeof currentEl.parentNode !== 'undefined' ) {
				if ( currentEl instanceof HTMLAnchorElement && currentEl.getAttribute( 'href' ) !== null ) {
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
		idxChild;

	if ( typeof window.plausible === 'undefined' || suggestions === null ) {
		return;
	}

	observer = new MutationObserver( function ( mutationList ) {
		for ( idxMutation = 0; idxMutation < mutationList.length; idxMutation++ ) {
			if ( mutationList[ idxMutation ].addedNodes.length === 0 ) {
				continue;
			}

			const target = mutationList[ idxMutation ]?.target;

			if ( typeof target === 'undefined' || !( target instanceof HTMLOListElement ) ) {
				return;
			}

			for ( idxChild = 0; idxChild < mutationList[ idxMutation ].addedNodes.length; idxChild++ ) {
				let child = mutationList[ idxMutation ].addedNodes[ idxChild ];

				if ( !( child instanceof HTMLLIElement ) || typeof child.querySelector === 'undefined' ) {
					continue;
				}

				const a = child.querySelector('a');

				if ( a === null ) {
					continue;
				}

				a.addEventListener( 'click', callback );
			}
		}
	} );

	observer.observe( suggestions, { childList: true, subtree: true } );
}() );

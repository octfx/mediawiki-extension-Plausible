// Infobox Link Click Tracking
( function () {
	var eventName = 'Infobox: Click',
		infoboxes = [
			...Array.from(document.querySelectorAll( '.mw-capiunto-infobox' )),
			...Array.from(document.querySelectorAll( '.infobox' )),
		],
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\';


	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 || infoboxes.length === null ) {
		return;
	}

	infoboxes.forEach(infobox => {
		infobox.querySelectorAll('a:not(.new)').forEach(link => {
			const callback = function (event) {
				if (link.getAttribute('href') === null) {
					return;
				}

				if ( link.classList.contains('image') ) {
					window.plausible(
						eventName,
						{
							props: {
								title: 'Infobox Image',
								isAnon,
							}
						}
					);
				} else {
					event.preventDefault();

					window.plausible(
						eventName,
						{
							props: {
								title: link.innerText,
								isAnon,
							},
							callback: function () {
								window.location = link.getAttribute('href');
							}
						}
					);
				}
			};

			link.removeEventListener('click', callback);
			link.addEventListener('click', callback);
		});
	});
}() );

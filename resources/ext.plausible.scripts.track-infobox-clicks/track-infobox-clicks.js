// Infobox Link Click Tracking
( function () {
	var eventName = 'Infobox: Click',
		infoboxes = document.querySelectorAll( '.mw-capiunto-infobox' ),
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\';

	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 || infoboxes === null ) {
		return;
	}

	infoboxes.forEach(infobox => {
		infobox.querySelectorAll('a:not(.new)').forEach(link => {
			link.addEventListener('click', function (event) {
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
			});
		});
	});
}() );

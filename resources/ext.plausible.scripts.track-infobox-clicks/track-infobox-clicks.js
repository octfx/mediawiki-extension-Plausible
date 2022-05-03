// Infobox Link Click Tracking
( function () {
	var eventName = 'Infobox: Click',
		infoboxes = document.querySelectorAll( '.mw-capiunto-infobox' );

	if ( typeof window.plausible === 'undefined' || infoboxes === null ) {
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
								link: 'Infobox Image'
							}
						}
					);
				} else {
					event.preventDefault();

					window.plausible(
						eventName,
						{
							props: {
								link: link.textContent
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

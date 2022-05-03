// Navplate Link Click Tracking
( function () {
	var eventName = 'Navplate: Click',
		navplates = document.querySelectorAll( '.navplate' );

	if ( typeof window.plausible === 'undefined' || navplates === null ) {
		return;
	}

	navplates.forEach(navplate => {
		navplate.querySelectorAll('a:not(.new)').forEach(link => {
			link.addEventListener('click', function (event) {
				if (link.getAttribute('href') === null) {
					return;
				}

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
			});
		});
	});
}() );

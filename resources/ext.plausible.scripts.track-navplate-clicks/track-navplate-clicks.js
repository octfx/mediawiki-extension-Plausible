// Navplate Link Click Tracking
( function () {
	var eventName = 'Navplate: Click',
		navplates = document.querySelectorAll( '.navplate' ),
		isAnon = mw.user?.tokens?.values?.watchToken === null || mw.user?.tokens?.values?.watchToken === '+\\';

	if ( typeof window.plausible === 'undefined' || window.plausible.length === 0 || navplates === null ) {
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
							title: link.innerText,
							isAnon,
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

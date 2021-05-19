(function () {
	const optOut = () => {
		const optOutButtons = document.querySelectorAll('.plausible-opt-out'),
			plausibleKey = 'plausible_ignore';

		if (optOutButtons.length === 0) {
			return;
		}

		const setInclude = (button) => {
			window.localStorage.removeItem(plausibleKey);

			button.innerText = mw.message('ext-plausible-exclude-visits').text();
			button.classList.remove('plausible-opt-out__enabled');
			button.classList.add('plausible-opt-out__disabled');
		};

		const setExclude = (button) => {
			window.localStorage.setItem(plausibleKey, 'true');

			button.innerText = mw.message('ext-plausible-include-visits').text();
			button.classList.remove('plausible-opt-out__disabled');
			button.classList.add('plausible-opt-out__enabled');
		};

		const toggleState = (event) => {
			try {
				const state = window.localStorage.getItem(plausibleKey);

				if (state === 'true') {
					setInclude(event.target);
					mw.notify(mw.message('ext-plausible-notification-visits-included').text());
				} else {
					setExclude(event.target);
					mw.notify(mw.message('ext-plausible-notification-visits-excluded').text());
				}
			} catch (e) {
			}
		};

		const trackingState = window.localStorage.getItem(plausibleKey);

		optOutButtons.forEach(button => {
			button.addEventListener('click', toggleState);

			if (trackingState === 'true') {
				button.innerText = mw.message('ext-plausible-include-visits').text();
				button.classList.remove('plausible-opt-out__disabled');
				button.classList.add('plausible-opt-out__enabled');
			}
		});
	};

	mw.hook( 'wikipage.content' ).add( optOut );
})();

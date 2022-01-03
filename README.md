# Plausible Analytics

Plausible Analytics is a simple, lightweight (< 1 KB), open-source and privacy-friendly alternative to Google Analytics. It doesn’t use cookies and is fully compliant with GDPR, CCPA and PECR.

See https://github.com/plausible/analytics

## Configuration
| Key                                 | Description                                                                                                                                         | Example                         | Default |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|---------|
| $wgPlausibleDomain                  | Plausible Domain                                                                                                                                    | https://plausible.io            | null    |
| $wgPlausibleDomainKey               | Domain Key set on the plausible website                                                                                                             | plausible.io                    | null    |
| $wgPlausibleHonorDNT                | Honor the Do Not Track header and disable tracking                                                                                                  | false                           | true    |
| $wgPlausibleTrackOutboundLinks      | Enable Tracking of outbound link clicks                                                                                                             | true                            | false   |
| $wgPlausibleTrackLoggedIn           | Enable Tracking for logged in users                                                                                                                 | true                            | false   |
| $wgPlausibleEnableCustomEvents      | Enable to add the global window.plausible function needed for custom event tracking                                                                 | true                            | false   |
| $wgPlausibleIgnoredTitles           | List of page titles that should not be tracked. https://github.com/plausible/docs/blob/master/docs/excluding-pages.md#common-use-cases-and-examples | ['/Page1', '/Special:*', ]      | []      |
| $wgPlausibleEnableOptOutTag         | Enables or disables the `<plausible-opt-out />` tag that allows users to opt-out from being tracked                                                 | false                           | true    |

### Included tracking scripts
The following tracking modules can be activated by setting the provided configuration key in `LocalSettings.php` to true.
| Key                                 | Description                                                                                                                                         | EventName                       |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------|---------------------------------|
| $wgPlausibleTrack404                | Sends a '404' event for unknown titles                                                                                                              | `404`                           |
| $wgPlausibleTrackSearchInput        | Send inputs to `#searchInput` to plausible as a custom event named 'SearchInput'                                                                    | `SearchInput`                   |
| $wgPlausibleTrackEditButtonClicks   | Track clicks to `#ca-edit a` as a custom event named 'EditButtonClick'                                                                              | `EditButtonClick`               |
| $wgPlausibleTrackNavplateClicks     | Track clicks to links inside `.navplate` elements						                                                                            | `Navplate: Click`               |
| $wgPlausibleTrackInfoboxClicks      | Track clicks to links inside `.mw-capiunto-infobox` elements				                                                                        | `Infobox: Click`                |
| $wgPlausibleTrackCitizenSearchLinks | Only for skin Citizen. Track clicks to search result links found in `#typeahead-suggestions`. Event is named 'CitizenSearchLinkClick'               | `CitizenSearchLinkClick`        |
| $wgPlausibleTrackCitizenMenuLinks   | Only for skin Citizen. Track clicks to links in the sidebar menu. Event is named 'CitizenMenuLinkClick'                                             | `CitizenMenuLinkClick`          |


## Tracking Custom Events
https://github.com/plausible/docs/blob/master/docs/custom-event-goals.md

If you want to track custom event goals like button clicks or form completions, you have to trigger these custom events from your website using JavaScript.

Scripts need to be placed in `MediaWiki:<Your Skin>.js` e.g. `MediaWiki:Citizen.js`.

Example: Tracking edit button clicks on [SkinCitizen](https://github.com/StarCitizenTools/mediawiki-skins-Citizen).
```js
if (typeof window.plausible === 'undefined') {
    return;
}

document.querySelector('#ca-edit a').addEventListener('click', function (event) {
    plausible('Editbtn Clicked');
});
```

## Ignoring Pages
https://github.com/plausible/docs/blob/master/docs/excluding-pages.md#common-use-cases-and-examples

By default, Plausible Analytics tracks every page you install the snippet on. If you don't want Plausible to track specific pages, do not include the snippet on those pages.

## Common use cases and examples
| $wgPlausibleIgnoredTitles input | Prevents tracking on pages with a URL path of: |
| ------------- | ------------- |
| `/blog4` | `/blog4` and exactly `/blog4` with nothing before or after it, so not `/blog45` nor `/blog4/new` nor `/blog` |
| `/rule/*` | `/rule/<anything>`, with `<anything>` being any set of characters (length >=0), but not a forward slash - for example, both `/rule/1` as well as `/rule/general-rule-14`, but not `/rule/4/details` nor `/rules` |
| `/how-to-*` | `/how-to-<anything>` - for example, `/how-to-play` or `/how-to-succeed`, but not `how-to-/blog` |
| `/*/admin` | `/<anything>/admin` - for example, `/sites/admin`, but not `/sites/admin/page-2` nor `/sites/2/admin` nor `/admin` |
| `/*/priv/*` | `/<anything>/priv/<anything>` - for example, `/admin/priv/sites`, but not `/priv` nor `/priv/page` nor `/admin/priv` |
| `/rule/*/*` | `/rule/<anything>/<anything>` - for example, `/rule/4/new/` or `/rule/10/edit`, but not `/rule` nor `/rule/10/new/save` |
| `/wp/**` | `/wp<anything, even slashes>` - for example, `/wp/assets/subdirectory/another/image.png` or `/wp/admin`, and everything in between, but not `/page/wp`

# Plausible Analytics

Plausible Analytics is a simple, lightweight (< 1 KB), open-source and privacy-friendly alternative to Google Analytics. It doesn’t use cookies and is fully compliant with GDPR, CCPA and PECR.

See https://github.com/plausible/analytics

## Installation
* Download, extract and place the file(s) in a directory called Plausible in your extensions/ folder.
* Add the following code at the bottom of your LocalSettings.php file:
```php
wfLoadExtension( 'Plausible' );  
$wgPlausibleDomain = "https://plausible.io";  
$wgPlausibleDomainKey = "mywiki.example.com"; // change to your site address
$wgPlausibleApikey = ''; // Only necessary when using Extension:PageViewInfo
```
* Configure as required.
* Done – Navigate to Special:Version on your wiki to verify that the extension is successfully installed.

## Configuration
| Key                                     | Description                                                                                                                                                                        | Example                    | Default |
|-----------------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|----------------------------|---------|
| $wgPlausibleDomain                      | Plausible Domain. **Required**                                                                                                                                                     | https://plausible.io       | null    |
| $wgPlausibleDomainKey                   | Domain Key set on the plausible website. **Required**                                                                                                                              | plausible.io               | null    |
| $wgPlausibleApiKey                      | Auth Bearer key for integration with [Extension:PageViewInfo](https://www.mediawiki.org/wiki/Extension:PageViewInfo)                                                               |                            |         |
| $wgPlausibleHonorDNT                    | Honor the Do Not Track header and disable tracking.                                                                                                                                | false                      | true    |
| $wgPlausibleTrackOutboundLinks          | Enable Tracking of outbound link clicks.                                                                                                                                           | true                       | false   |
| $wgPlausibleTrackFileDownloads          | Enable Tracking of link clicks that lead to files, sending a `File Download` event. See [the official docs](https://plausible.io/docs/file-downloads-tracking).                    | true                       | false   |
| $wgPlausibleTrackFileDownloadExtensions | List of additional file extensions to track. See [the official docs](https://plausible.io/docs/file-downloads-tracking#which-file-types-are-tracked).                              | ['js', 'py']               | []      |
| $wgPlausibleTrackLoggedIn               | Enable Tracking for logged in users.                                                                                                                                               | true                       | false   |
| $wgPlausibleEnableTaggedEvents          | Enable click tracking via css classes. See [the official docs](https://plausible.io/docs/custom-event-goals#2-add-a-css-class-name-to-the-element-you-want-to-track-on-your-site). | true                       | false   |
| $wgPlausibleIgnoredTitles               | List of page titles that should not be tracked. [Examples](https://github.com/plausible/docs/blob/master/docs/excluding-pages.md#common-use-cases-and-examples).                   | ['/Page1', '/Special:*', ] | []      |
| $wgPlausibleEnableOptOutTag             | Enables or disables the `<plausible-opt-out />` tag that allows users to opt-out from being tracked.                                                                               | false                      | true    |


### Included tracking scripts
The following tracking modules can be activated by setting the provided configuration key in `LocalSettings.php` to true.

| Key                                 | Description                                                                                                                                                                                               | Event Name                   |
|-------------------------------------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|------------------------------|
| $wgPlausibleTrack404                | Sends a `404` event for unknown titles.                                                                                                                                                                   | `404`                        |
| $wgPlausibleTrackSearchInput        | Send inputs to `#searchInput` to plausible as a custom event named `Search: Input`.                                                                                                                       | `Search: Input`              |
| $wgPlausibleTrackEditButtonClicks   | Track clicks to `#ca-edit a` as a custom event named `Edit Button: Click`.                                                                                                                                | `Edit Button: Click`         |
| $wgPlausibleTrackNavplateClicks     | Track clicks to links inside `.navplate` elements.                                                                                                                                                        | `Navplate: Click`            |
| $wgPlausibleTrackInfoboxClicks      | Track clicks to links inside `.mw-capiunto-infobox` and `.infobox` elements.                                                                                                                              | `Infobox: Click`             |
| $wgPlausibleTrackCitizenSearchLinks | Only for [Skin:Citizen](https://github.com/StarCitizenTools/mediawiki-skins-Citizen). Track clicks to search result links found in `#typeahead-suggestions`. Event is named `Citizen: Search Link Click`. | `Citizen: Search Link Click` |
| $wgPlausibleTrackCitizenMenuLinks   | Only for [Skin:Citizen](https://github.com/StarCitizenTools/mediawiki-skins-Citizen). Track clicks to links in the sidebar menu. Event is named `Citizen: Menu Link Click`.                               | `Citizen: Menu Link Click`   |

### Server Side Tracking
Some events can be sent serverside without having to rely on the included plausible client script.

The following custom events can be activated: 
```php
$wgPlausibleServerSideTracking = [
    // Event Name: pageview
    'pageview' => false,
    // Event Name: 404 
    'page404' => false,
    // Event Name: Page: Edit 
    'pageedit' => true, // Page has been successfully edited
    // Event Name: Page: Delete
    'pagedelete' => true, // Page has been deleted
    // Event Name: Page: Undelete
    'pageundelete' => true, // Page has been undeleted
    // Event Name: Page: Move
    'pagemove' => true, // Page was moved
    // Event Name: User: Register
    'userregister' => false, // A new user registered
    // Event Name: User: Login
    'userlogin' => false, // A user logged in
    // Event Name: User: Logout
    'userlogout' =>  false, // A user logged out
    // Event Name: File: Upload
    'fileupload' => true, // A file was uploaded
    // Event Name: File: Delete
    'filedelete' => true, // A file was deleted
    // Event Name: File: Undelete
    'fileundelete' => true, // A file was undeleted
    // Event Name: Search: Not found
    'searchnotfound' => true, // A searched term was not found / has no title on the wiki
    // Event Name: Search: Found
    'searchfound' => true, // A searched term was found / has a corresponding title on the wiki
];
```

### Event / Goal Names
This extension chooses the following convention for naming events / goals: `Subject: Event/Action`.

## Tracking Custom Events
https://github.com/plausible/docs/blob/master/docs/custom-event-goals.md

If you want to track custom event goals like button clicks or form completions, you have to trigger these custom events from your website using JavaScript.

Scripts need to be placed in `MediaWiki:<Your Skin>.js` e.g. `MediaWiki:Citizen.js`.

Example: Tracking edit button clicks on [Skin:Citizen](https://github.com/StarCitizenTools/mediawiki-skins-Citizen).
```js
if (typeof window.plausible === 'undefined') {
    return;
}

document.querySelector('#ca-edit a').addEventListener('click', function (event) {
    plausible('Edit Button: Click');
});
```

### Via css classes
With setting `$wgPlausibleEnableTaggedEvents = true;` click to elements can be tracked by setting css classes.
From [the official docs](https://plausible.io/docs/custom-event-goals): 
> You can also add class names directly in HTML
> If you can edit the raw HTML code of the element you want to track, you can also add the classes directly in HTML. For example:
> 
> ```<!-- before -->```  
> ```<button>Click Me</button>```  
> ```<!-- after -->```  
> ```<button class="plausible-event-name=Button+Click">Click Me</button>```
> 
> Or if your element already has a class attribute, just separate the new ones with a space:
>
> ```<!-- before -->```  
> ```<button class="some-existing-class">Click Me</button>```
> 
> ```<!-- after -->```  
> ```<button class="some-existing-class plausible-event-name=Button+Click">Click Me</button>```

> When you send custom events to Plausible, they won't show up in your dashboard automatically. You'll have to configure the goal for the conversion numbers to show up.

## Ignoring Pages
https://github.com/plausible/docs/blob/master/docs/excluding-pages.md#common-use-cases-and-examples

By default, Plausible Analytics tracks every page you install the snippet on. If you don't want Plausible to track specific pages, do not include the snippet on those pages.

## Common use cases and examples
| $wgPlausibleIgnoredTitles input | Prevents tracking on pages with a URL path of:                                                                                                                                                                   |
|---------------------------------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `/blog4`                        | `/blog4` and exactly `/blog4` with nothing before or after it, so not `/blog45` nor `/blog4/new` nor `/blog`                                                                                                     |
| `/rule/*`                       | `/rule/<anything>`, with `<anything>` being any set of characters (length >=0), but not a forward slash - for example, both `/rule/1` as well as `/rule/general-rule-14`, but not `/rule/4/details` nor `/rules` |
| `/how-to-*`                     | `/how-to-<anything>` - for example, `/how-to-play` or `/how-to-succeed`, but not `how-to-/blog`                                                                                                                  |
| `/*/admin`                      | `/<anything>/admin` - for example, `/sites/admin`, but not `/sites/admin/page-2` nor `/sites/2/admin` nor `/admin`                                                                                               |
| `/*/priv/*`                     | `/<anything>/priv/<anything>` - for example, `/admin/priv/sites`, but not `/priv` nor `/priv/page` nor `/admin/priv`                                                                                             |
| `/rule/*/*`                     | `/rule/<anything>/<anything>` - for example, `/rule/4/new/` or `/rule/10/edit`, but not `/rule` nor `/rule/10/new/save`                                                                                          |
| `/wp/**`                        | `/wp<anything, even slashes>` - for example, `/wp/assets/subdirectory/another/image.png` or `/wp/admin`, and everything in between, but not `/page/wp`                                                           |

## Lua Integration
With [Extension:PageViewInfo](https://www.mediawiki.org/wiki/Extension:PageViewInfo) active, plausible exposes the following functions:

1. `mw.ext.plausible.topPages()`
Returns the top pages and the views for the last day. The table is ordered by the number of page views, and can be iterated by using `ipairs`.  
Example:
```lua
local result = mw.ext.plausible.topPages()
> {
  {
    page = "Foo",
    views = 100
  },
  {
    page = "Bar",
    views = 80
  },
  { [...] }
}
```

Alternatively this function can be called with the number of days to calculate the views over, e.g. `mw.ext.plausible.topPages( 30 )`.  
This is _expensive_  as no caching is employed.

2. `mw.ext.plausible.pageData( titles, days )`
Returns the page views for the given titles over the last N days.  
Example:
```lua
local result = mw.ext.plausible.pageData( { "Foo", "Bar" }, 5 )
> {
  ["Foo"] = {
    ["2023-08-04"] = 10,
    ["2023-08-05"] = 1,
    ["2023-08-06"] = 4,
    ["2023-08-07"] = 7,
    ["2023-08-08"] = 1,
    ["2023-08-09"] = 4,
  },
  ["Bar"] = {
    ["2023-08-04"] = 100,
    ["2023-08-05"] = 14,
    ["2023-08-06"] = 54,
    ["2023-08-07"] = 7,
    ["2023-08-08"] = 31,
    ["2023-08-09"] = 1,
  },
}
```

3. `mw.ext.plausible.siteData( days )`
Returns the site views for the given last N days.  
Example:
```lua
local result = mw.ext.plausible.siteData( 5 )
> {
  ["2023-08-04"] = 10,
  ["2023-08-05"] = 1,
  ["2023-08-06"] = 4,
  ["2023-08-07"] = 7,
  ["2023-08-08"] = 1,
  ["2023-08-09"] = 4,
}
```
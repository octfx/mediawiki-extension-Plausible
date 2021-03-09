<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 *
 * @file
 */

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Hooks;

use ConfigException;
use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\MediaWikiServices;
use OutputPage;
use Skin;

/**
 * Hooks to run relating the page
 */
class PageHooks implements BeforePageDisplayHook {

	/**
	 * Adds the tracking script to the apge
	 *
	 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforePageDisplay
	 *
	 * @param OutputPage $out
	 * @param Skin $skin
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		$url = $this->getConfigValue( 'PlausibleUrl' );
		$domainKey = $this->getConfigValue( 'PlausibleDomainKey' );

		if ($url === null || $domainKey === null) {
		    return;
        }

		$user = $out->getUser();
        $enableLoggedIn = $this->getConfigValue( 'PlausibleEnableLoggedIn' ) ?? false;

        if ($user->isAnon() || ($user->isLoggedIn() && $enableLoggedIn)) {
            $out->addScript(sprintf(
                '<script async defer data-domain="%s" src="%s/js/plausible.js"></script>',
                $domainKey,
                rtrim($url, '/')
            ));
        }
	}

    /**
     * Loads a config value for a given key from the main config
     * Returns null on if an ConfigException was thrown
     *
     * @param string $key The config key
     *
     * @return mixed|null
     */
    protected function getConfigValue( string $key ) {
        try {
            $value = MediaWikiServices::getInstance()->getMainConfig()->get( $key );
        } catch ( ConfigException $e ) {
            wfLogWarning(
                sprintf(
                    'Could not get config for "$wg%s". %s', $key,
                    $e->getMessage()
                )
            );
            $value = null;
        }

        return $value;
    }
}

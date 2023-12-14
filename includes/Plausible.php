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

namespace MediaWiki\Extension\Plausible;

use Config;
use ConfigException;
use OutputPage;

class Plausible {

	/**
	 * @var OutputPage
	 */
	private $out;

	/**
	 * The complete url to the plausible instance
	 *
	 * @var string|null
	 */
	private $plausibleDomain;

	/**
	 * The domain key set in plausible
	 *
	 * @var string|null
	 */
	private $domainKey;

	/**
	 * @var Config
	 */
	private $config;

	/**
	 * @param OutputPage $out
	 */
	public function __construct( OutputPage $out ) {
		$this->out = $out;
		$this->config = $out->getConfig();
		$this->plausibleDomain = $this->getConfigValue( 'PlausibleDomain' );
		$this->domainKey = $this->getConfigValue( 'PlausibleDomainKey' );
	}

	/**
	 * Add the script to the page
	 */
	public function addScript(): void {
		if ( !$this->canAdd() ) {
			return;
		}

		$script = $this->buildScript();
		$this->out->addHeadItem( 'plausible', $script );
	}

	/**
	 * Checks the config for each available tracking module and adds it if it is active
	 */
	public function addModules(): void {
		if ( !$this->canAdd() ) {
			return;
		}

		$availableModules = [
			'PlausibleTrack404' => 'ext.plausible.scripts.track-404',
			'PlausibleTrackSearchInput' => 'ext.plausible.scripts.track-search',
			'PlausibleTrackEditButtonClicks' => 'ext.plausible.scripts.track-edit-btn',
			'PlausibleTrackNavplateClicks' => 'ext.plausible.scripts.track-navplate-clicks',
			'PlausibleTrackInfoboxClicks' => 'ext.plausible.scripts.track-infobox-clicks',

			'PlausibleTrackCitizenSearchLinks' => 'ext.plausible.scripts.citizen.track-search-links',
			'PlausibleTrackCitizenMenuLinks' => 'ext.plausible.scripts.citizen.track-menu-links',
		];

		foreach ( $availableModules as $config => $module ) {
			if ( $this->getConfigValue( $config, false ) === false ) {
				continue;
			}

			if ( $config === 'PlausibleTrack404' ) {
				$exists = $this->out->getTitle() !== null && $this->out->getTitle()->isKnown();
				$this->out->addJsConfigVars( 'is404', !$exists );
			}

			$this->out->addModules( $module );
		}
	}

	/**
	 * Builds the complete script
	 *
	 * @return string
	 */
	private function buildScript(): string {
		$nonce = $this->out->getCSP()->getNonce();

		return sprintf(
			'<script async defer %s src="%s"%s></script>',
			$this->buildScriptAttribs(),
			$this->buildScriptPath(),
			$nonce !== false ? sprintf( ' nonce="%s"', $nonce ) : ''
		);
	}

	/**
	 * Builds the absolute link to the plausible js file
	 *
	 * @return string
	 */
	private function buildScriptPath(): string {
		$name = 'script';
		$name = sprintf( '%s.pageview-props', $name );

		if ( $this->getConfigValue( 'PlausibleTrackOutboundLinks', false ) === true ) {
			$name = sprintf( '%s.outbound-links', $name );
		}

		if ( !empty( $this->getConfigValue( 'PlausibleIgnoredTitles', [] ) ) ) {
			$name = sprintf( '%s.exclusions', $name );
		}

		if ( $this->getConfigValue( 'PlausibleTrackFileDownloads', false ) === true ) {
			$name = sprintf( '%s.file-downloads', $name );
		}

		if ( $this->getConfigValue( 'PlausibleEnableTaggedEvents', false ) === true ) {
			$name = sprintf( '%s.tagged-events', $name );
		}

		return sprintf(
			'%s/js/%s.js',
			rtrim( $this->plausibleDomain, '/' ),
			$name
		);
	}

	/**
	 * Builds the data attributes of the script tag
	 *
	 * @return string
	 */
	private function buildScriptAttribs(): string {
		$attributes = [
			'domain' => $this->domainKey,
		];

		$ignoredTitles = $this->getConfigValue( 'PlausibleIgnoredTitles', [] );

		if ( !empty( $ignoredTitles ) ) {
			$attributes['exclude'] = implode( ', ', $ignoredTitles );
		}

		$attributes = array_map( static function ( $key, $value ) {
			return sprintf( 'data-%s="%s"', $key, $value );
		}, array_keys( $attributes ), $attributes );

		$additionalExtensions = $this->getConfigValue( 'PlausibleTrackFileDownloadExtensions', [] );
		if ( $this->getConfigValue( 'PlausibleTrackFileDownloads' ) && !empty( $additionalExtensions ) ) {
			$attributes[] = sprintf( 'file-types="%s"', implode( ',', $additionalExtensions ) );
		}

		return implode( ' ', $attributes );
	}

	/**
	 * Determines if the script can be added to the page
	 *
	 * @return bool
	 */
	private function canAdd(): bool {
		if ( $this->plausibleDomain === null || $this->domainKey === null ) {
			wfLogWarning( '$wgPlausibleDomain or $wgPlausibleDomainKey is not set.' );

			return false;
		}

		$dnt = $this->out->getRequest()->getHeader( 'DNT' ) === '1';

		if ( $dnt === true && (bool)$this->getConfigValue( 'PlausibleHonorDNT', true ) === true ) {
			return false;
		}

		$user = $this->out->getUser();
		$enableLoggedIn = (bool)$this->getConfigValue( 'PlausibleTrackLoggedIn', false );

		return $user->isAnon() || ( $user->isRegistered() && $enableLoggedIn );
	}

	/**
	 * Loads a config value for a given key from the main config
	 * Returns null on if an ConfigException was thrown
	 *
	 * @param string $key The config key
	 * @param null $default
	 * @return mixed|null
	 */
	private function getConfigValue( string $key, $default = null ) {
		try {
			$value = $this->config->get( $key );
		} catch ( ConfigException $e ) {
			wfLogWarning(
				sprintf(
					'Could not get config for "$wg%s". %s', $key,
					$e->getMessage()
				)
			);

			return $default;
		}

		return $value;
	}
}

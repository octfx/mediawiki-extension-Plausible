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

use MediaWiki\Extension\PageViewInfo\CachedPageViewService;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Scribunto_LuaLibraryBase;

/**
 * Plausible Lua library, registered when the Scribunto extension is loaded
 */
class PlausibleLua extends Scribunto_LuaLibraryBase {

	/**
	 * @inheritDoc
	 */
	public function register() {
		$lib = [
			'getTopPages' => [ $this, 'getTopPages' ],
			'getTopPagesDays' => [ $this, 'getTopPages' ],
			'getPageData' => [ $this, 'getPageData' ],
			'getSiteData' => [ $this, 'getSiteData' ],
		];

		return $this->getEngine()->registerInterface( __DIR__ . '/' . 'mw.ext.plausible.lua', $lib, [] );
	}

	/**
	 * The top pages in the last day (or specified days) for this site
	 *
	 * @param int ...$args Optional number of days to calculate the top pages over
	 *
	 * @return array|array[]
	 */
	public function getTopPages( ...$args ): array {
		if ( !$args ) {
			/** @var CachedPageViewService $service */
			$service = MediaWikiServices::getInstance()->getService( 'PageViewService' );

			$status = $service->getTopPages();
		} else {
			$service = new PlausiblePageViewService();
			$status = $service->getTopPagesDays( $args[0] );
		}

		if ( $status->isOK() ) {
			$out = [];
			$i = 1;
			foreach ( $status->getValue() as $page => $views ) {
				$out[$i] = [
					'page' => $page,
					'views' => $views,
				];
				++$i;
			}

			uasort( $out, static function ( $a, $b ) {
				return $b['views'] <=> $a['views'];
			} );
			return [ $out ];
		}

		return [];
	}

	/**
	 * Number of views for whole site
	 *
	 * @param int ...$args Number of days for returning the views
	 *
	 * @return array
	 */
	public function getSiteData( ...$args ): array {
		/** @var CachedPageViewService $service */
		$service = MediaWikiServices::getInstance()->getService( 'PageViewService' );

		$status = $service->getSiteData( $args[0] );

		if ( $status->isOK() ) {
			return [ $status->getValue() ];
		}

		return [];
	}

	/**
	 * Number of views for the specified titles
	 *
	 * @param string|mixed ...$args Titles to work on, then the number of days
	 *
	 * @return array
	 */
	public function getPageData( ...$args ): array {
		/** @var CachedPageViewService $service */
		$service = MediaWikiServices::getInstance()->getService( 'PageViewService' );

		$titles = [];
		foreach ( $args[0] as $title ) {
			$titles[] = Title::newFromText( $title );
		}

		$status = $service->getPageData( $titles, $args[1] );

		if ( $status->isOK() ) {
			return [ $status->getValue() ];
		}

		return [];
	}
}

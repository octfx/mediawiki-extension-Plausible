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

use ExtensionRegistry;
use MediaWiki\Extension\PageViewInfo\CachedPageViewService;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use Scribunto_LuaLibraryBase;

class ScribuntoLua extends Scribunto_LuaLibraryBase {

	/**
	 * @inheritDoc
	 */
	public function register() {
		if ( ExtensionRegistry::getInstance()->isLoaded( 'PageViewInfo' ) ) {
			$lib = [
				'getTopPages' => [ $this, 'getTopPages' ],
				'getPageData' => [ $this, 'getPageData' ],
				'getSiteData' => [ $this, 'getSiteData' ],
			];

			$this->getEngine()->registerInterface( __DIR__ . '/' . 'mw.ext.plausible.lua', $lib, [] );
		}
	}

	public function getTopPages() {
		/** @var CachedPageViewService $service */
		$service = MediaWikiServices::getInstance()->getService( 'PageViewService' );

		$status = $service->getTopPages();

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

			uasort( $out, function ( $a, $b ) {
				return $a['views'] < $b['views'];
			} );
			return [ $out ];
		}

		return [];
	}

	public function getSiteData() {
		$args = func_get_args();

		/** @var CachedPageViewService $service */
		$service = MediaWikiServices::getInstance()->getService( 'PageViewService' );

		$status = $service->getSiteData( $args[0] );

		if ( $status->isOK() ) {
			return [ $status->getValue() ];
		}

		return [];
	}

	public function getPageData() {
		$args = func_get_args();

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

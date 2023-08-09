<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use ExtensionRegistry;
use MediaWiki\Extension\PageViewInfo\CachedPageViewService;
use MediaWiki\Extension\Plausible\PlausiblePageViewService;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Logger\LoggerFactory;
use ObjectCache;

class MediaWikiServices implements MediaWikiServicesHook {

	/**
	 * @inheritDoc
	 */
	public function onMediaWikiServices( $services ) {
		if ( !ExtensionRegistry::getInstance()->isLoaded( 'PageViewInfo' ) ) {
			return;
		}

		global $wgPageViewApiMaxDays;

		$cache = ObjectCache::getLocalClusterInstance();
		$logger = LoggerFactory::getInstance( 'Plausible' );
		$cachedDays = max( 30, $wgPageViewApiMaxDays );

		$services->redefineService(
			'PageViewService',
			static function () use (
				$cache,
				$logger,
				$cachedDays
			) {
				$service = new PlausiblePageViewService();

				$cachedService = new CachedPageViewService( $service, $cache );
				$cachedService->setCachedDays( $cachedDays );
				$cachedService->setLogger( $logger );
				return $cachedService;
			}
		);
	}
}

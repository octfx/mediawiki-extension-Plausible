<?php

namespace MediaWiki\Extension\Plausible\Hooks;


use ExtensionRegistry;
use MediaWiki\Extension\PageViewInfo\CachedPageViewService;
use MediaWiki\Hook\MediaWikiServicesHook;
use MediaWiki\Logger\LoggerFactory;
use ObjectCache;

class MediaWikiServices implements MediaWikiServicesHook {

    /**
     * @inheritDoc
     */
    public function onMediaWikiServices( $services ) {
        if(!ExtensionRegistry::getInstance()->isLoaded('PageViewInfo')) {
            return;
        }

        global $wgPageViewInfoGAProfileId,
               $wgPageViewInfoGACredentialsFile,
               $wgPageViewInfoGAWriteCustomMap,
               $wgPageViewInfoGAReadCustomDimensions,
               $wgPageViewApiMaxDays;

        $profileId = $wgPageViewInfoGAProfileId;
        if ( !$profileId ) {
            return;
        }
        $credentialsFile = $wgPageViewInfoGACredentialsFile;
        $customMap = $wgPageViewInfoGAWriteCustomMap;
        $readCustomDimensions = $wgPageViewInfoGAReadCustomDimensions;
        $cache = ObjectCache::getLocalClusterInstance();
        $logger = LoggerFactory::getInstance( 'PageViewInfoGA' );
        $cachedDays = max( 30, $wgPageViewApiMaxDays );

        $services->redefineService(
            'PageViewService',
            static function () use (
                $credentialsFile,
                $profileId,
                $customMap,
                $readCustomDimensions,
                $cache,
                $logger,
                $cachedDays
            ) {
                $service = new GoogleAnalyticsPageViewService( [
                    'credentialsFile' => $credentialsFile,
                    'profileId' => $profileId,
                    'customMap' => $customMap,
                    'readCustomDimensions' => $readCustomDimensions,
                ] );

                $cachedService = new CachedPageViewService( $service, $cache );
                $cachedService->setCachedDays( $cachedDays );
                $cachedService->setLogger( $logger );
                return $cachedService;
            }
        );
    }
}
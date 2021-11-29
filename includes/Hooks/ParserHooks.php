<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Hooks;

use MediaWiki\Hook\ParserFirstCallInitHook;
use MediaWiki\MediaWikiServices;
use MWException;

class ParserHooks implements ParserFirstCallInitHook {
	/**
	 * @inheritDoc
	 */
	public function onParserFirstCallInit( $parser ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'Plausible' );
		if ( $config->get( 'PlausibleEnableOptOutTag' ) === false ) {
			return;
		}

		try {
			$parser->setHook( 'plausible-opt-out', 'MediaWiki\Extension\Plausible\OptOut::fromTag' );
		} catch ( MWException $e ) {
			wfLogWarning( $e->getMessage() );
		}
	}
}

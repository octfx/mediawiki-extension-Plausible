<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use MediaWiki\Extension\Plausible\Hooks\ParserHooks;
use MediaWikiIntegrationTestCase;

/**
 * @group Plausible
 */
class ParserHooksTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\ParserHooks
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testConstructor() {
		$hooks = new ParserHooks();

		$this->assertInstanceOf( ParserHooks::class, $hooks );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\ParserHooks::onParserFirstCallInit
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testAddOptOut() {
		$this->overrideConfigValues( [
			'PlausibleEnableOptOutTag' => true,
		] );

		$hooks = new ParserHooks();
		$parser = $this->getServiceContainer()->getParser();

		$hooks->onParserFirstCallInit( $parser );

		$this->assertContains( 'plausible-opt-out', $parser->getTags() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\ParserHooks::onParserFirstCallInit
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testNotAddOptOut() {
		$this->overrideConfigValues( [
			'PlausibleEnableOptOutTag' => false,
		] );

		$hooks = new ParserHooks();
		$parser = $this->getServiceContainer()->getParser();

		$hooks->onParserFirstCallInit( $parser );

		$this->assertNotContains( 'plausible-opt-out', $parser->getTags() );
	}

}

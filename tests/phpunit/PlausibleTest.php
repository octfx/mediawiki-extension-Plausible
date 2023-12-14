<?php

namespace MediaWiki\Extension\Plausible\Tests;

use MediaWiki\Extension\Plausible\Plausible;
use MediaWikiIntegrationTestCase;
use OutputPage;
use RequestContext;

/**
 * @group Plausible
 */
class PlausibleTest extends MediaWikiIntegrationTestCase {

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'PlausibleTrackOutboundLinks' => false,
			'PlausibleTrackFileDownloads' => false,
			'PlausibleTrackLoggedIn' => false,
			'PlausibleEnableTaggedEvents' => false,
		] );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible
	 * @return void
	 */
	public function testConstructor() {
		$plausible = new Plausible( new OutputPage( RequestContext::getMain() ) );
		$this->assertInstanceOf( Plausible::class, $plausible );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptAttribs
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptPath
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addModules
	 * @covers \MediaWiki\Extension\Plausible\Plausible::canAdd
	 * @return void
	 */
	public function testScriptGeneration() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'localhost',
			'PlausibleDomainKey' => 'localwiki',
		] );

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		$this->assertArrayHasKey( 'plausible', $page->getHeadItemsArray() );
		$this->assertStringContainsString( 'script.pageview-props.js', $page->getHeadItemsArray()['plausible'] );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptAttribs
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptPath
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addModules
	 * @covers \MediaWiki\Extension\Plausible\Plausible::canAdd
	 * @return void
	 */
	public function testNoScriptGeneration() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => null,
			'PlausibleDomainKey' => null,
		] );

		$this->expectWarning();

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		$this->assertArrayNotHasKey( 'plausible', $page->getHeadItemsArray() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptAttribs
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptPath
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addModules
	 * @covers \MediaWiki\Extension\Plausible\Plausible::canAdd
	 * @return void
	 */
	public function testCustomScriptGeneration() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'localhost',
			'PlausibleDomainKey' => 'localwiki',
			'PlausibleTrackFileDownloads' => true,
		] );

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		$this->assertArrayHasKey( 'plausible', $page->getHeadItemsArray() );
		$script = $page->getHeadItemsArray()['plausible'];

		$this->assertStringContainsString( 'script.pageview-props', $script );
		$this->assertStringContainsString( 'file-downloads', $script );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptAttribs
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptPath
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addModules
	 * @covers \MediaWiki\Extension\Plausible\Plausible::canAdd
	 * @return void
	 */
	public function testIgnoredTitles() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'localhost',
			'PlausibleDomainKey' => 'localwiki',
			'PlausibleIgnoredTitles' => [ 'Main Page', 'Foo' ],
		] );

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		$this->assertArrayHasKey( 'plausible', $page->getHeadItemsArray() );
		$script = $page->getHeadItemsArray()['plausible'];

		$this->assertStringContainsString( 'exclusions', $script );
		$this->assertStringContainsString( 'data-exclude="Main Page, Foo"', $script );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScript
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptAttribs
	 * @covers \MediaWiki\Extension\Plausible\Plausible::buildScriptPath
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addModules
	 * @covers \MediaWiki\Extension\Plausible\Plausible::canAdd
	 * @return void
	 */
	public function testAddModule() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'localhost',
			'PlausibleDomainKey' => 'localwiki',
			'PlausibleTrack404' => true,
		] );

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		$this->assertArrayHasKey( 'plausible', $page->getHeadItemsArray() );

		$this->assertContainsEquals( 'ext.plausible.scripts.track-404', $page->getModules() );
	}
}

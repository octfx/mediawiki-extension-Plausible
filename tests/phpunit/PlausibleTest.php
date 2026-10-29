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

		$warnings = [];
		set_error_handler( static function ( $errno, $errstring ) use ( &$warnings ): bool {
			$warnings[] = $errstring;
			return true;
		} );

		$page = new OutputPage( RequestContext::getMain() );
		$plausible = new Plausible( $page );
		$plausible->addModules();
		$plausible->addScript();

		restore_error_handler();

		$this->assertStringContainsString(
			'$wgPlausibleDomain or $wgPlausibleDomainKey is not set.',
			implode( "\n", $warnings )
		);
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
	 * @dataProvider provideFileDownloadExtensions
	 */
	public function testFileDownloadExtensions( bool $enabled, array $extensions, ?string $expected ) {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'localhost',
			'PlausibleDomainKey' => 'localwiki',
			'PlausibleTrackFileDownloads' => $enabled,
			'PlausibleTrackFileDownloadExtensions' => $extensions,
		] );

		$page = new OutputPage( RequestContext::getMain() );
		( new Plausible( $page ) )->addScript();
		$script = $page->getHeadItemsArray()['plausible'];

		$this->assertStringNotContainsString( ' file-types=', $script );
		if ( $expected === null ) {
			$this->assertStringNotContainsString( ' add-file-types=', $script );
		} else {
			$this->assertStringContainsString( ' add-file-types="' . $expected . '"', $script );
		}
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Plausible::addScript
	 * @dataProvider provideSiteSpecificOptions
	 */
	public function testSiteSpecificOptions( bool $outbound, bool $downloads, array $extensions, string $expected ) {
		$this->overrideConfigValues( [
			'PlausibleTrackerMode' => 'site-specific',
			'PlausibleScriptUrl' => 'https://analytics.example/js/pa-test.js',
			'PlausibleDomain' => 'https://analytics.example',
			'PlausibleDomainKey' => 'localwiki',
			'PlausibleTrackOutboundLinks' => $outbound,
			'PlausibleTrackFileDownloads' => $downloads,
			'PlausibleTrackFileDownloadExtensions' => $extensions,
		] );

		$page = new OutputPage( RequestContext::getMain() );

		( new Plausible( $page ) )->addScript();

		$script = $page->getHeadItemsArray()['plausible'];
		$this->assertStringContainsString( 'https://analytics.example/js/pa-test.js', $script );
		$this->assertStringContainsString( $expected, $script );
		$this->assertStringContainsString( 'plausible.init(', $script );
		$this->assertStringNotContainsString( 'script.pageview-props', $script );
		$this->assertStringNotContainsString( 'transformRequest', $script );
	}

	/** @covers \MediaWiki\Extension\Plausible\Plausible::addScript */
	public function testSiteSpecificRequiresUrl() {
		$this->overrideConfigValues( [
			'PlausibleTrackerMode' => 'site-specific',
			'PlausibleScriptUrl' => null,
			'PlausibleDomain' => 'https://analytics.example',
			'PlausibleDomainKey' => 'localwiki',
		] );

		$warnings = [];

		set_error_handler( static function ( $errno, $message ) use ( &$warnings ): bool {
			$warnings[] = $message;
			return true;
		} );

		try {
			$page = new OutputPage( RequestContext::getMain() );
			( new Plausible( $page ) )->addScript();
		} finally {
			restore_error_handler();
		}

		$this->assertArrayNotHasKey( 'plausible', $page->getHeadItemsArray() );
		$this->assertStringContainsString( '$wgPlausibleScriptUrl', implode( ' ', $warnings ) );
	}

	public static function provideSiteSpecificOptions(): array {
		return [
			'disabled' => [ false, false, [ 'js' ], '"outboundLinks":false,"fileDownloads":false' ],
			'default file types' => [ true, true, [], '"outboundLinks":true,"fileDownloads":true' ],
			'replacement file types' => [ false, true, [ 'js', 'py' ],
				'"outboundLinks":false,"fileDownloads":{"fileExtensions":["js","py"]}' ],
		];
	}

	public static function provideFileDownloadExtensions(): array {
		return [
			'additional extensions' => [ true, [ 'js', 'py' ], 'js,py' ],
			'disabled' => [ false, [ 'js' ], null ],
			'empty list' => [ true, [], null ],
		];
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

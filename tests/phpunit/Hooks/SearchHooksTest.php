<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\Hooks\SearchHooks;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @group Plausible
 */
class SearchHooksTest extends MediaWikiIntegrationTestCase {
	private $mockQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'PlausibleTrackOutboundLinks' => false,
			'PlausibleTrackFileDownloads' => false,
			'PlausibleTrackLoggedIn' => false,
			'PlausibleEnableTaggedEvents' => false,
		] );

		$this->mockQueue = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'push' ] )
			->getMock();
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\SearchHooks
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testConstructor() {
		$hooks = new SearchHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getJobQueueGroup()
		);

		$this->assertInstanceOf( SearchHooks::class, $hooks );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\SearchHooks::onSpecialSearchGoResult
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testSearchHooksFound() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new SearchHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$empty = null;

		$hooks->onSpecialSearchGoResult( 'Foo', Title::newFromText( 'Foo' ), $empty );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\SearchHooks::onSpecialSearchGoResult
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testSearchHooksFoundDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'searchfound' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new SearchHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$empty = null;

		$hooks->onSpecialSearchGoResult( 'Foo', Title::newFromText( 'Foo' ), $empty );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\SearchHooks::onSpecialSearchNogomatch
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testSearchHooksNotFound() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new SearchHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$title = Title::newFromText( 'Foo' );

		$hooks->onSpecialSearchNogomatch( $title );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\SearchHooks::onSpecialSearchNogomatch
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testSearchHooksNotFoundDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'searchnotfound' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new SearchHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$title = Title::newFromText( 'Foo' );

		$hooks->onSpecialSearchNogomatch( $title );
	}
}

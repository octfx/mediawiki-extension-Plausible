<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\Hooks\PageHooks;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Storage\EditResult;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use OutputPage;
use RequestContext;
use User;

/**
 * @group Plausible
 * @group Database
 */
class PageHooksTest extends MediaWikiIntegrationTestCase {
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
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testConstructor() {
		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getJobQueueGroup()
		);

		$this->assertInstanceOf( PageHooks::class, $hooks );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onBeforePageDisplay
	 * @return void
	 * @throws Exception
	 */
	public function testOnBeforePageDisplay() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'foo',
			'PlausibleDomainKey' => 'foo',
			'PlausibleTrackLoggedIn' => true,
			'PlausibleHonorDNT' => false,
		] );

		$out = new OutputPage( RequestContext::getMain() );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onBeforePageDisplay( $out, null );

		$this->assertArrayHasKey( 'plausible', $out->getHeadItemsArray() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onBeforePageDisplay
	 * @return void
	 * @throws Exception
	 */
	public function testOnBeforePageDisplayAllModules() {
		$this->overrideConfigValues( [
			'PlausibleDomain' => 'foo',
			'PlausibleDomainKey' => 'foo',
			'PlausibleTrackLoggedIn' => true,
			'PlausibleHonorDNT' => false,
			'PlausibleTrack404' => true,
			'PlausibleTrackSearchInput' => true,
			'PlausibleTrackEditButtonClicks' => true,
			'PlausibleTrackNavplateClicks' => true,
			'PlausibleTrackInfoboxClicks' => true,
			'PlausibleTrackCitizenSearchLinks' => true,
			'PlausibleTrackCitizenMenuLinks' => true,
		] );

		$out = new OutputPage( RequestContext::getMain() );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onBeforePageDisplay( $out, null );

		$this->assertContains( 'ext.plausible.scripts.track-404', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.track-search', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.track-edit-btn', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.track-navplate-clicks', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.track-infobox-clicks', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.citizen.track-search-links', $out->getModules() );
		$this->assertContains( 'ext.plausible.scripts.citizen.track-menu-links', $out->getModules() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onArticleDeleteAfterSuccess
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testArticleDeleteAfterSuccess() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onArticleDeleteAfterSuccess(
			Title::newFromText( 'Foo' ),
			new OutputPage( RequestContext::getMain() )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onPageSaveComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testPageSaveComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onPageSaveComplete(
			$this->getServiceContainer()->getWikiPageFactory()->newFromTitle( Title::newFromText( 'Foo' ) ),
			User::createNew( 'PageSaveComplete' ),
			'',
			0,
			$this->getMockBuilder( RevisionRecord::class )->disableOriginalConstructor()->getMock(),
            // phpcs:ignore Generic.Files.LineLength.TooLong
			new EditResult( true, false, null, null, null, false, false, [] )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onArticleUndelete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testArticleUndelete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onArticleUndelete(
			Title::newFromText( 'ArticleUndelete' ),
			true,
			null,
			0,
			[],
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onPageMoveComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testPageMoveComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onPageMoveComplete(
			Title::newFromText( 'Foo' ),
			Title::newFromText( 'Bar' ),
			User::createNew( 'PageMoveComplete' ),
			0,
			0,
			'',
			$this->getMockBuilder( RevisionRecord::class )->disableOriginalConstructor()->getMock()
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onArticleDeleteAfterSuccess
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testArticleDeleteAfterSuccessDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'pageedit' => false,
				'pagedelete' => false,
				'pageundelete' => false,
				'pagemove' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onArticleDeleteAfterSuccess(
			Title::newFromText( 'Foo' ),
			new OutputPage( RequestContext::getMain() )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onPageSaveComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testPageSaveCompleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'pageedit' => false,
				'pagedelete' => false,
				'pageundelete' => false,
				'pagemove' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onPageSaveComplete(
			$this->getServiceContainer()->getWikiPageFactory()->newFromTitle( Title::newFromText( 'Foo' ) ),
			User::createNew( 'PageSaveCompleteDisabled' ),
			'',
			0,
			$this->getMockBuilder( RevisionRecord::class )->disableOriginalConstructor()->getMock(),
            // phpcs:ignore Generic.Files.LineLength.TooLong
			new EditResult( true, false, null, null, null, false, false, [] )
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onArticleUndelete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testArticleUndeleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'pageedit' => false,
				'pagedelete' => false,
				'pageundelete' => false,
				'pagemove' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onArticleUndelete(
			Title::newFromText( 'Foo' ),
			true,
			null,
			0,
			[],
		);
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\PageHooks::onPageMoveComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testPageMoveCompleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'pageedit' => false,
				'pagedelete' => false,
				'pageundelete' => false,
				'pagemove' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new PageHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onPageMoveComplete(
			Title::newFromText( 'Foo' ),
			Title::newFromText( 'Bar' ),
			User::createNew( 'PageMoveCompleteDisabled' ),
			0,
			0,
			'',
			$this->getMockBuilder( RevisionRecord::class )->disableOriginalConstructor()->getMock()
		);
	}
}

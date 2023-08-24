<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\Hooks\UserHooks;
use MediaWikiIntegrationTestCase;

/**
 * @group Plausible
 */
class UserHooksTest extends MediaWikiIntegrationTestCase {
	private $mockQueue;

	protected function setUp(): void {
		parent::setUp();

		$this->overrideConfigValues( [
			'PlausibleTrackOutboundLinks' => false,
			'PlausibleTrackFileDownloads' => false,
			'PlausibleTrackLoggedIn' => false,
			'PlausibleEnableTaggedEvents' => false,
			'PlausibleServerSideTracking' => [
				'userregister' => true,
				'userlogin' => true,
				'userlogout' => true,
			]
		] );

		$this->mockQueue = $this->getMockBuilder( JobQueueGroup::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'push' ] )
			->getMock();
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\UserHooks
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testConstructor() {
		$hooks = new UserHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->getServiceContainer()->getJobQueueGroup()
		);

		$this->assertInstanceOf( UserHooks::class, $hooks );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\UserHooks::onLocalUserCreated
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testOnLocalUserCreated() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new UserHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'OnLocalUserCreated' );

		$hooks->onLocalUserCreated( $user, false );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\UserHooks::onUserLoginComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testOnUserLoginComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new UserHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'OnUserLoginComplete' );

		$html = '';

		$hooks->onUserLoginComplete( $user, $html, true );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\UserHooks::onUserLoginComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testOnUserLoginCompleteNotDirect() {
		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new UserHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'OnUserLoginComplete' );

		$html = '';

		$hooks->onUserLoginComplete( $user, $html, false );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\UserHooks::onUserLogoutComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testOnUserLogoutComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new UserHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$user = $this->getServiceContainer()->getUserFactory()->newFromName( 'OnUserLogoutComplete' );

		$html = '';

		$hooks->onUserLogoutComplete( $user, $html, '' );
	}

}

<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use JobQueueGroup;
use LocalFile;
use MediaWiki\Extension\Plausible\Hooks\FileHooks;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use UploadFromFile;
use User;

/**
 * @group Plausible
 */
class FileHooksTest extends MediaWikiIntegrationTestCase {
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
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onUploadComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testUploadComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onUploadComplete( new UploadFromFile() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onFileDeleteComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testDeleteComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$file = new LocalFile(
			Title::newFromText( 'Foo.jpg', NS_FILE ),
			$this->getServiceContainer()->getRepoGroup()->getLocalRepo()
		);

		$hooks->onFileDeleteComplete( $file, null, null, User::createNew( 'Foo' ), '' );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onFileUndeleteComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testUndeleteComplete() {
		$this->mockQueue->expects( $this->once() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onFileUndeleteComplete( Title::newFromText( 'Foo.jpg', NS_FILE ), [], User::createNew( 'Foo2' ), '' );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onUploadComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testUploadCompleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'fileupload' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onUploadComplete( new UploadFromFile() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onFileDeleteComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testDeleteCompleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'filedelete' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$file = new LocalFile(
			Title::newFromText( 'Foo.jpg', NS_FILE ),
			$this->getServiceContainer()->getRepoGroup()->getLocalRepo()
		);

		$hooks->onFileDeleteComplete( $file, null, null, User::createNew( 'Foo' ), '' );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\Hooks\FileHooks::onFileUndeleteComplete
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 *
	 * @return void
	 * @throws Exception
	 */
	public function testUndeleteCompleteDisabled() {
		$this->overrideConfigValues( [
			'PlausibleServerSideTracking' => [
				'fileundelete' => false,
			],
		] );

		$this->mockQueue->expects( $this->never() )->method( 'push' );

		$hooks = new FileHooks(
			$this->getServiceContainer()->getMainConfig(),
			$this->mockQueue
		);

		$hooks->onFileUndeleteComplete( Title::newFromText( 'Foo.jpg', NS_FILE ), [], User::createNew( 'Foo2' ), '' );
	}
}

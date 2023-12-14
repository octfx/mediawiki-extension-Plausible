<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible\Tests;

use Exception;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Http\HttpRequestFactory;
use MediaWiki\Request\FauxRequest;
use MediaWiki\Session\SessionManager;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;
use MWException;
use MWHttpRequest;
use NullJob;
use Status;
use User;

class PlausibleEventJobTest extends MediaWikiIntegrationTestCase {

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob
	 * @return void
	 */
	public function testConstructor() {
		$job = new PlausibleEventJob( [] );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @return void
	 * @throws MWException
	 */
	public function testCreateFromRequest() {
		$session = SessionManager::singleton()->getEmptySession();
		$session->setUser( User::createNew( 'FromRequestUser' ) );

		$title = Title::newFromText( 'Foo' );

		$request = new FauxRequest( [], false, $session, 'https' );
		$request->setRequestURL( $title->getLinkURL() );

		$job = PlausibleEventJob::newFromRequest( $request );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
		$this->assertStringEndsWith( '/Foo', $job->params['url'] );
		$this->assertFalse( $job->params['isAnon'] );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @return void
	 */
	public function testCreateFromRequestNullJob() {
		$job = PlausibleEventJob::newFromRequest( new FauxRequest() );

		$this->assertInstanceOf( NullJob::class, $job );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @return void
	 * @throws MWException
	 */
	public function testNotRunMissingAgent() {
		$session = SessionManager::singleton()->getEmptySession();
		$session->setUser( User::createNew( 'NotRunMissingAgent' ) );

		$title = Title::newFromText( 'Foo' );

		$request = new FauxRequest( [], false, $session, 'https' );
		$request->setRequestURL( $title->getLinkURL() );

		$job = PlausibleEventJob::newFromRequest( $request );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
		$this->assertStringEndsWith( '/Foo', $job->params['url'] );
		$this->assertFalse( $job->params['isAnon'] );

		$this->assertFalse( $job->run() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::run
	 * @return void
	 * @throws MWException
	 * @throws Exception
	 */
	public function testRun() {
		$this->overrideConfigValues( [
			'PlausibleTrackLoggedIn' => true,
		] );

		$session = SessionManager::singleton()->getEmptySession();
		$session->setUser( User::createNew( 'Run' ) );

		$title = Title::newFromText( 'Foo' );

		$request = new FauxRequest( [], false, $session, 'https' );
		$request->setRequestURL( $title->getLinkURL() );
		$request->setHeader( 'User-Agent', 'Foo-Agent' );

		$fac = $this->getMockBuilder( HttpRequestFactory::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
			->getMock();

		$this->getServiceContainer()->redefineService( 'HttpRequestFactory', function () use ( $fac ) {
			return $fac;
		} );

		$req = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'setHeader', 'execute' ] )
			->getMock();

		$req->expects( $this->once() )->method( 'execute' )->willReturn( Status::newGood() );
		$fac->expects( $this->once() )->method( 'create' )->willReturn( $req );

		$job = PlausibleEventJob::newFromRequest( $request );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
		$this->assertStringEndsWith( '/Foo', $job->params['url'] );
		$this->assertFalse( $job->params['isAnon'] );
		$this->assertTrue( $job->run() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::run
	 * @return void
	 * @throws MWException
	 * @throws Exception
	 */
	public function testRunAnon() {
		$this->overrideConfigValues( [
			'PlausibleTrackLoggedIn' => false,
		] );

		$session = SessionManager::singleton()->getEmptySession();
		$session->setUser( User::newFromSession() );

		$title = Title::newFromText( 'Foo' );

		$request = new FauxRequest( [], false, $session, 'https' );
		$request->setRequestURL( $title->getLinkURL() );
		$request->setHeader( 'User-Agent', 'Foo-Agent' );

		$fac = $this->getMockBuilder( HttpRequestFactory::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
			->getMock();

		$this->getServiceContainer()->redefineService( 'HttpRequestFactory', function () use ( $fac ) {
			return $fac;
		} );

		$req = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'setHeader', 'execute' ] )
			->getMock();

		$req->expects( $this->once() )->method( 'execute' )->willReturn( Status::newGood() );
		$fac->expects( $this->once() )->method( 'create' )->willReturn( $req );

		$job = PlausibleEventJob::newFromRequest( $request );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
		$this->assertStringEndsWith( '/Foo', $job->params['url'] );
		$this->assertTrue( $job->params['isAnon'] );
		$this->assertTrue( $job->run() );
	}

	/**
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::newFromRequest
	 * @covers \MediaWiki\Extension\Plausible\PlausibleEventJob::run
	 * @return void
	 * @throws MWException
	 * @throws Exception
	 */
	public function testNotRunLoggedInUser() {
		$this->overrideConfigValues( [
			'PlausibleTrackLoggedIn' => false,
		] );

		$session = SessionManager::singleton()->getEmptySession();
		$session->setUser( User::createNew( 'NotRunLoggedInUser' ) );

		$title = Title::newFromText( 'Foo' );

		$request = new FauxRequest( [], false, $session, 'https' );
		$request->setRequestURL( $title->getLinkURL() );
		$request->setHeader( 'User-Agent', 'Foo-Agent' );

		$fac = $this->getMockBuilder( HttpRequestFactory::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'create' ] )
			->getMock();

		$this->getServiceContainer()->redefineService( 'HttpRequestFactory', function () use ( $fac ) {
			return $fac;
		} );

		$req = $this->getMockBuilder( MWHttpRequest::class )
			->disableOriginalConstructor()
			->onlyMethods( [ 'setHeader', 'execute' ] )
			->getMock();

		$req->expects( $this->never() )->method( 'execute' );
		$fac->expects( $this->never() )->method( 'create' );

		$job = PlausibleEventJob::newFromRequest( $request );

		$this->assertInstanceOf( PlausibleEventJob::class, $job );
		$this->assertStringEndsWith( '/Foo', $job->params['url'] );

		$this->assertFalse( $job->params['isAnon'] );
		$this->assertTrue( $job->run() );
	}
}

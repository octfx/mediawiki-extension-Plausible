<?php

namespace MediaWiki\Extension\Plausible;

use Exception;
use GenericParameterJob;
use Job;
use MediaWiki\MediaWikiServices;
use NullJob;
use WebRequest;

class PlausibleEventJob extends Job implements GenericParameterJob {
	protected $removeDuplicates = true;

	public function __construct( array $params ) {
		parent::__construct( 'PlausibleEvent', $params );
	}

	/**
	 * @inheritDoc
	 */
	public function run(): bool {
		if ( empty( $this->params['url'] ) || empty( $this->params['agent'] ) ) {
			return false;
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create(
			sprintf( '%s/api/event', $config->get( 'PlausibleDomain' ) ),
			[
				'method' => 'POST',
				'userAgent' => $this->params['agent'],
				'postData' => [
					'domain' => $config->get( 'PlausibleDomainKey' ),
					'name' => $this->params['event'],
					'url' => $this->params['url'],
					'props' => $this->params['props'] ?? [],
				],
			]
		);

		$request->setHeader( 'Content-Type', 'application/json' );
		$request->setHeader( 'X-Forwarded-For', $this->params['ip'] );

		$status = $request->execute();

		return $status->isOK();
	}

	/**
	 * Creates a job from a web request
	 *
	 * @param WebRequest $request
	 * @param string $event
	 * @param array $props
	 * @return Job
	 */
	public static function newFromRequest( WebRequest $request, string $event = 'pageview', array $props = [] ): Job {
		try {
			return new self( [
				'event' => $event,
				'ip' => $request->getIP(),
				'url' => $request->getFullRequestURL(),
				'agent' => $request->getHeader( 'User-Agent' ),
				'props' => $props,
			] );
		} catch ( Exception $e ) {
			return new NullJob( [ 'removeDuplicates' => true ] );
		}
	}
}

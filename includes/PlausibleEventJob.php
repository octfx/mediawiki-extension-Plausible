<?php

namespace MediaWiki\Extension\Plausible;

use GenericParameterJob;
use Job;
use MediaWiki\MediaWikiServices;
use MWException;
use WebRequest;

class PlausibleEventJob extends Job implements GenericParameterJob {

	/**
	 * @inheritDoc
	 */
	public function run(): bool {
		if ( !empty( $this->params['url'] ) || !empty( $this->params['agent'] ) ) {
			return false;
		}

		$config = MediaWikiServices::getInstance()->getMainConfig();

		$request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create(
			sprintf( '%s/api/event', $config->get( 'PlausibleDomain' ) ),
			[
				'userAgent' => $this->params['agent'],
				'postData' => [
					'domain' => $config->get( 'PlausibleDomainKey' ),
					'name' => 'pageview',
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
	 * @throws MWException
	 */
	public static function newFromRequest( WebRequest $request, string $event = 'pageview', array $props = [] ): Job {
		return MediaWikiServices::getInstance()->getJobFactory()->newJob(
			'PlausibleEvent',
			[
				'event' => $event,
				'ip' => $request->getIP(),
				'url' => $request->getRequestURL(),
				'agent' => $request->getHeader( 'User-Agent' ),
				'props' => $props,
			]
		);
	}
}

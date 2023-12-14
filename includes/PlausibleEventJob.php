<?php

declare( strict_types=1 );

namespace MediaWiki\Extension\Plausible;

use ConfigException;
use Exception;
use GenericParameterJob;
use Job;
use JsonException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use NullJob;
use WebRequest;

class PlausibleEventJob extends Job implements GenericParameterJob {
	protected $removeDuplicates = true;

	/**
	 * @param array $params
	 */
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

		try {
			if ( !$config->get( 'PlausibleTrackLoggedIn' ) && ( $this->params['isAnon'] ?? true ) === false ) {
				return true;
			}

			$this->params['props']['isAnon'] = $this->params['isAnon'];

			$request = MediaWikiServices::getInstance()->getHttpRequestFactory()->create(
				sprintf( '%s/api/event', $config->get( 'PlausibleDomain' ) ),
				[
					'method' => 'POST',
					'userAgent' => $this->params['agent'],
					'postData' => json_encode( [
						'domain' => $config->get( 'PlausibleDomainKey' ),
						'name' => $this->params['event'],
						'url' => $this->params['url'],
						'props' => $this->params['props'] ?? [],
					], JSON_THROW_ON_ERROR ),
				]
			);
		} catch ( JsonException | ConfigException $e ) {
			return false;
		}

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
			$url = $request->getFullRequestURL();
			if ( isset( $props['title'] ) ) {
				$url = ( Title::newFromText( $props['title'] ) )->getFullURL();
				unset( $props['title'] );
			}

			return new self( [
				'event' => $event,
				'ip' => $request->getIP(),
				'url' => $url,
				'agent' => $request->getHeader( 'User-Agent' ),
				'props' => $props,
				'isAnon' => $request->getSession()->getUser()->isAnon(),
			] );
		} catch ( Exception $e ) {
			return new NullJob( [ 'removeDuplicates' => true ] );
		}
	}
}

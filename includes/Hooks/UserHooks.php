<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\UserLoginCompleteHook;
use MediaWiki\Hook\UserLogoutCompleteHook;

class UserHooks implements LocalUserCreatedHook, UserLogoutCompleteHook, UserLoginCompleteHook {

	private array $config;
	private JobQueueGroup $jobs;

	/**
	 * @param Config $config
	 * @param JobQueueGroup $group
	 */
	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config->get( 'PlausibleServerSideTracking' );
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ): void {
		if ( !$this->config['userregister'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'User: Register',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
				'autocreated' => $autocreated,
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLoginComplete( $user, &$inject_html, $direct ): void {
		if ( !$this->config['userlogin'] || !$direct ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'User: Login',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLogoutComplete( $user, &$inject_html, $oldName ): void {
		if ( !$this->config['userlogout'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'User: Logout',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}
}

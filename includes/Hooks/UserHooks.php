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

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config->get( 'PlausibleServerSideTracking' );
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( !$this->config['userregister'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'userregister',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLoginComplete( $user, &$inject_html, $direct ) {
		if ( !$this->config['userlogin'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'userlogin',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUserLogoutComplete( $user, &$inject_html, $oldName ) {
		if ( !$this->config['userlogout'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest(
			$user->getRequest(),
			'userlogout',
			[
				'user' => $user->isRegistered() ? $user->getName() : null,
			]
		) );
	}
}

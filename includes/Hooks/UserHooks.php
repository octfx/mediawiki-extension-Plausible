<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Auth\Hook\LocalUserCreatedHook;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\UserLoginCompleteHook;
use MediaWiki\Hook\UserLogoutCompleteHook;

class UserHooks implements LocalUserCreatedHook, UserLogoutCompleteHook, UserLoginCompleteHook {

	private Config $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config;
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onLocalUserCreated( $user, $autocreated ) {
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['userregister'] ) {
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
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['userlogin'] ) {
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
		if ( !$this->config->get( 'PlausibleServerSideTracking' )['userlogout'] ) {
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

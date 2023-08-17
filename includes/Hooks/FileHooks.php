<?php

namespace MediaWiki\Extension\Plausible\Hooks;

use Config;
use JobQueueGroup;
use MediaWiki\Extension\Plausible\PlausibleEventJob;
use MediaWiki\Hook\FileDeleteCompleteHook;
use MediaWiki\Hook\FileUndeleteCompleteHook;
use MediaWiki\Hook\UploadCompleteHook;
use RequestContext;

class FileHooks implements UploadCompleteHook, FileDeleteCompleteHook, FileUndeleteCompleteHook {

	private array $config;
	private JobQueueGroup $jobs;

	public function __construct( Config $config, JobQueueGroup $group ) {
		$this->config = $config->get( 'PlausibleServerSideTracking' );
		$this->jobs = $group;
	}

	/**
	 * @inheritDoc
	 */
	public function onFileDeleteComplete( $file, $oldimage, $article, $user, $reason ): void {
		if ( !$this->config['filedelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $user->getRequest(), 'File: Delete' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onUploadComplete( $uploadBase ): void {
		if ( !$this->config['fileupload'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( RequestContext::getMain()->getRequest(), 'File: Upload' ) );
	}

	/**
	 * @inheritDoc
	 */
	public function onFileUndeleteComplete( $title, $fileVersions, $user, $reason ): void {
		if ( !$this->config['fileundelete'] ) {
			return;
		}

		$this->jobs->push( PlausibleEventJob::newFromRequest( $user->getRequest(), 'File: Undelete' ) );
	}
}

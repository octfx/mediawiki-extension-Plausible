<?php

namespace MediaWiki\Extension\Plausible;

use Message;
use Parser;
use PPFrame;

class OptOut {
	/**
	 * <plausible-opt-out />
	 *
	 * @param Parser $parser The active Parser instance
	 * @param PPFrame $frame Frame
	 * @param array $args Arguments
	 *
	 * @return string The button
	 */
	public static function fromTag( ?string $input, array $args, Parser $parser, PPFrame $frame ): string {
		$parser->getOutput()->addModules( [ 'ext.plausible.opt-out' ] );

		$message = new Message( 'ext-plausible-exclude-visits' );

		return sprintf(
			'<button type="submit" class="plausible-opt-out plausible-opt-out__disabled">%s</button>',
			$message->plain()
		);
	}
}

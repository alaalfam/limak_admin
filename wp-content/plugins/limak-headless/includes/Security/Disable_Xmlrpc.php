<?php

namespace Limak\Headless\Security;

use Limak\Headless\Support\Registrable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * XML-RPC is a separate legacy API surface with a history of brute-force
 * and amplification abuse, and this project uses REST exclusively. Turning
 * it off shrinks the attack surface without touching the REST API at all.
 */
final class Disable_Xmlrpc implements Registrable {

	public function register(): void {
		add_filter( 'xmlrpc_enabled', '__return_false' );
	}
}

<?php

namespace Limak\Headless\Security;

use Limak\Headless\Support\Registrable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defense-in-depth guard for the entire REST API (wp/v2, limak/v1, and any
 * route a future plugin registers): unauthenticated requests may only use
 * safe HTTP methods, and the users endpoint is hidden from the public
 * entirely (it can enumerate usernames). This backstops — it doesn't
 * replace — each controller's own capability checks.
 *
 * Authenticated requests (cookie auth in wp-admin, or application
 * passwords for future integrations) are unaffected: WordPress resolves
 * the current user before rest_pre_dispatch runs.
 */
final class Rest_Guard implements Registrable {

	private const SAFE_METHODS = [ WP_REST_Server::READABLE, 'GET', 'HEAD', 'OPTIONS' ];

	public function register(): void {
		add_filter( 'rest_pre_dispatch', [ $this, 'guard' ], 10, 3 );
	}

	/**
	 * @param mixed            $result
	 * @param WP_REST_Server   $server
	 * @param WP_REST_Request  $request
	 * @return mixed
	 */
	public function guard( $result, $server, WP_REST_Request $request ) {
		if ( is_user_logged_in() ) {
			return $result;
		}

		if ( ! in_array( $request->get_method(), self::SAFE_METHODS, true ) ) {
			return new WP_Error(
				'limak_rest_write_forbidden',
				__( 'Write access to the REST API requires authentication.', 'limak-headless' ),
				[ 'status' => 401 ]
			);
		}

		if ( 0 === strpos( $request->get_route(), '/wp/v2/users' ) ) {
			return new WP_Error(
				'limak_rest_users_forbidden',
				__( 'The users endpoint is not publicly accessible.', 'limak-headless' ),
				[ 'status' => 401 ]
			);
		}

		return $result;
	}
}

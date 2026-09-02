<?php
// nginx terminates TLS and proxies to this container over plain HTTP
// (same pattern as the Next.js frontend) — without this, WP thinks every
// request is HTTP and generates http:// URLs/redirect loops behind
// ArvanCloud's Full(Strict) HTTPS.
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ) {
	$_SERVER['HTTPS'] = 'on';
}

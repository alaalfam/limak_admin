<?php
/**
 * CI guardrail: fails if a REST route registered in code has no matching
 * documented path in docs/openapi.yaml. Pure static analysis (regex over
 * source files) — no WordPress boot needed, so this can run as a fast CI
 * step alongside the PHP syntax lint.
 *
 * This catches "forgot to document a new endpoint" (what actually
 * happened with product-categories). It does NOT catch response-shape
 * drift (a field renamed/added in a transformer but not reflected in the
 * YAML schema) — that would need a much heavier tool (e.g. booting
 * WordPress and diffing live responses against the schema). Out of scope
 * here; this is the cheap, high-value half of the problem.
 */

$root              = dirname( __DIR__ );
$controllers_dir    = $root . '/wp-content/plugins/limak-headless/includes/REST/Controllers';
$openapi_path       = $root . '/docs/openapi.yaml';

$registered_bases = [];

foreach ( glob( $controllers_dir . '/*.php' ) as $file ) {
	$contents = file_get_contents( $file );

	if ( preg_match( "/\\\$this->rest_base\s*=\s*'([^']+)'/", $contents, $matches ) ) {
		$registered_bases[ $matches[1] ] = basename( $file );
	}
}

$openapi = file_get_contents( $openapi_path );

// Path keys under `paths:` look like "  /products:" or "  /products/{slug}:".
preg_match_all( '/^\s{2}(\/[a-zA-Z0-9\-{}\/]+):/m', $openapi, $path_matches );
$documented_paths = $path_matches[1];

$missing = [];

foreach ( $registered_bases as $base => $source_file ) {
	$found = false;

	foreach ( $documented_paths as $path ) {
		// The base must appear as its own path segment, e.g. "products" matches
		// "/products" and "/products/{slug}" but not "/product-categories".
		if ( preg_match( '#^/' . preg_quote( $base, '#' ) . '(/|$)#', $path ) ) {
			$found = true;
			break;
		}
	}

	if ( ! $found ) {
		$missing[] = "'{$base}' (registered in {$source_file}) has no matching path in docs/openapi.yaml";
	}
}

if ( $missing ) {
	fwrite( STDERR, "OpenAPI coverage check failed:\n" );
	foreach ( $missing as $line ) {
		fwrite( STDERR, "  - {$line}\n" );
	}
	exit( 1 );
}

echo 'OpenAPI coverage check passed: all ' . count( $registered_bases ) . " registered REST bases are documented.\n";

<?php
/**
 * Self-check for the GitHub release update filter.
 * Run: php tests/update-check.php
 *
 * ponytail: hand-stubbed instead of a WP test suite. Enough to catch the two
 * ways this breaks in production: offering an older version, or handing
 * WordPress the source tarball instead of the built zip.
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'HOUR_IN_SECONDS', 3600 );

// Hook registration is all functions.php does at include time.
function add_action() {}
function add_filter() {}
function add_shortcode() {}
function load_child_theme_textdomain() {}

$GLOBALS['transient'] = false;
$GLOBALS['http']      = array();

function get_site_transient( $key ) {
	return $GLOBALS['transient'];
}
function set_site_transient( $key, $value, $ttl ) {
	$GLOBALS['transient'] = $value;
}
function delete_site_transient( $key ) {
	$GLOBALS['transient'] = false;
}
function wp_remote_get( $url, $args = array() ) {
	return $GLOBALS['http'];
}
function is_wp_error( $thing ) {
	return $thing instanceof WP_Error;
}
function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['code'] ) ? $response['code'] : 0;
}
function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}
class WP_Error {}

require __DIR__ . '/../theme/functions.php';

function release_body( $tag, $extra = array() ) {
	return wp_json_encode_stub(
		array_merge(
			array(
				'tag_name' => $tag,
				'html_url' => 'https://github.com/MrDemonWolf/mrdemonwolf-wp-theme/releases/tag/' . $tag,
				'assets'   => array(
					array(
						'name'                 => 'mrdemonwolf-wp-theme-source.zip',
						'browser_download_url' => 'https://example.invalid/source.zip',
					),
					array(
						'name'                 => 'mrdemonwolf.zip',
						'browser_download_url' => 'https://example.invalid/mrdemonwolf.zip',
					),
				),
			),
			$extra
		)
	);
}
function wp_json_encode_stub( $data ) {
	return json_encode( $data );
}

function check( $label, $condition ) {
	if ( ! $condition ) {
		fwrite( STDERR, "FAIL: $label\n" );
		exit( 1 );
	}
	echo "ok: $label\n";
}

function run( $http, $current_version ) {
	$GLOBALS['transient'] = false;
	$GLOBALS['http']      = $http;
	return mrdemonwolf_check_for_update( false, array( 'Version' => $current_version ), 'mrdemonwolf' );
}

// Newer release: offered, and always the built zip.
$update = run( array( 'code' => 200, 'body' => release_body( 'v2.0.0' ) ), '1.1.0' );
check( 'newer release is offered', is_array( $update ) && '2.0.0' === $update['version'] );
check( 'package is the built zip', 'https://example.invalid/mrdemonwolf.zip' === $update['package'] );
check( 'stylesheet is passed through', 'mrdemonwolf' === $update['theme'] );

// Same or older: nothing offered.
check( 'same version is not offered', false === run( array( 'code' => 200, 'body' => release_body( 'v1.1.0' ) ), '1.1.0' ) );
check( 'older version is not offered', false === run( array( 'code' => 200, 'body' => release_body( 'v1.0.0' ) ), '1.1.0' ) );

// Drafts and prereleases never ship.
check( 'prerelease ignored', false === run( array( 'code' => 200, 'body' => release_body( 'v2.0.0', array( 'prerelease' => true ) ) ), '1.1.0' ) );
check( 'draft ignored', false === run( array( 'code' => 200, 'body' => release_body( 'v2.0.0', array( 'draft' => true ) ) ), '1.1.0' ) );

// A release with no built zip attached is not an update.
check(
	'release without mrdemonwolf.zip ignored',
	false === run( array( 'code' => 200, 'body' => release_body( 'v2.0.0', array( 'assets' => array() ) ) ), '1.1.0' )
);

// GitHub having a bad minute must never break the update screen.
check( 'http error ignored', false === run( array( 'code' => 500, 'body' => '' ), '1.1.0' ) );
check( 'garbage body ignored', false === run( array( 'code' => 200, 'body' => 'not json' ), '1.1.0' ) );
check( 'wp_error ignored', false === run( new WP_Error(), '1.1.0' ) );

echo "all checks passed\n";

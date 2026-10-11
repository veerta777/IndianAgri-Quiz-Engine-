<?php
/**
 * PHP Built-in Server Router for IndianAgri Quiz Engine Local Preview.
 */

$uri = parse_url( $_SERVER['REQUEST_URI'], PHP_URL_PATH );
$ext = pathinfo( $uri, PATHINFO_EXTENSION );

// Serve static assets directly from plugin root.
$plugin_root = dirname( __DIR__ );

if ( in_array( $ext, array( 'css', 'js', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'woff', 'woff2', 'ttf' ), true ) ) {
	$file_path = $plugin_root . '/' . ltrim( $uri, '/' );
	if ( file_exists( $file_path ) ) {
		$mimes = array(
			'css'   => 'text/css',
			'js'    => 'application/javascript',
			'png'   => 'image/png',
			'jpg'   => 'image/jpeg',
			'jpeg'  => 'image/jpeg',
			'gif'   => 'image/gif',
			'svg'   => 'image/svg+xml',
			'woff'  => 'font/woff',
			'woff2' => 'font/woff2',
			'ttf'   => 'font/ttf',
		);
		header( 'Content-Type: ' . ( isset( $mimes[ $ext ] ) ? $mimes[ $ext ] : 'application/octet-stream' ) );
		readfile( $file_path );
		exit;
	}
}

// Route all requests to index.php.
require_once __DIR__ . '/index.php';

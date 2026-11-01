<?php
/**
 * Build script to package production-ready WordPress plugin ZIP with normalized forward slashes
 * and explicit directory entries for 100% compatibility across Linux/Windows hosting environments.
 */

$base_dir = __DIR__;
$dest_zip = $base_dir . '/indianagri-quiz-engine.zip';
$slug     = 'indianagri-quiz-engine';

if ( file_exists( $dest_zip ) ) {
	unlink( $dest_zip );
}

$zip = new ZipArchive();
if ( $zip->open( $dest_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
	fwrite( STDERR, "Error: Cannot create zip archive at {$dest_zip}\n" );
	exit( 1 );
}

// 1. Explicitly declare root directory entry
$zip->addEmptyDir( $slug );

// 2. Add top-level root files
$files = array(
	'indianagri-quiz-engine.php',
	'uninstall.php',
	'readme.txt',
);

foreach ( $files as $file ) {
	$zip->addFile( $base_dir . '/' . $file, $slug . '/' . $file );
}

// 3. Add subdirectories and nested files with forward slashes
$directories = array( 'includes', 'templates', 'assets' );

foreach ( $directories as $dir_name ) {
	$full_path = $base_dir . '/' . $dir_name;
	$zip->addEmptyDir( $slug . '/' . $dir_name );

	$iterator = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $full_path, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$sub_path = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $base_dir ) + 1 ) );
		if ( $item->isDir() ) {
			$zip->addEmptyDir( $slug . '/' . $sub_path );
		} else {
			$zip->addFile( $item->getPathname(), $slug . '/' . $sub_path );
		}
	}
}

$zip->close();
echo "Plugin successfully packaged into: " . basename( $dest_zip ) . " (Size: " . filesize( $dest_zip ) . " bytes)\n";



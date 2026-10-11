<?php
/**
 * Build script to package production-ready WordPress plugin ZIP with normalized forward slashes.
 */

$dest_zip = __DIR__ . '/indianagri-quiz-engine.zip';
if ( file_exists( $dest_zip ) ) {
	unlink( $dest_zip );
}

$zip = new ZipArchive();
if ( $zip->open( $dest_zip, ZipArchive::CREATE | ZipArchive::OVERWRITE ) !== true ) {
	fwrite( STDERR, "Error: Cannot create zip archive at {$dest_zip}\n" );
	exit( 1 );
}

$base_dir = __DIR__;
$files = array(
	'indianagri-quiz-engine.php',
	'uninstall.php',
	'readme.txt',
);

foreach ( $files as $file ) {
	$zip->addFile( $base_dir . '/' . $file, 'indianagri-quiz-engine/' . $file );
}

$directories = array( 'includes', 'templates', 'assets' );

foreach ( $directories as $dir_name ) {
	$full_path = $base_dir . '/' . $dir_name;
	$iterator  = new RecursiveIteratorIterator(
		new RecursiveDirectoryIterator( $full_path, RecursiveDirectoryIterator::SKIP_DOTS ),
		RecursiveIteratorIterator::SELF_FIRST
	);

	foreach ( $iterator as $item ) {
		$sub_path = str_replace( '\\', '/', substr( $item->getPathname(), strlen( $base_dir ) + 1 ) );
		if ( $item->isDir() ) {
			$zip->addEmptyDir( 'indianagri-quiz-engine/' . $sub_path );
		} else {
			$zip->addFile( $item->getPathname(), 'indianagri-quiz-engine/' . $sub_path );
		}
	}
}

$zip->close();
echo "Plugin successfully packaged into: " . $dest_zip . " (Size: " . filesize( $dest_zip ) . " bytes)\n";

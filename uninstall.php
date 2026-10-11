<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package IndianAgri_Quiz_Engine
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if user has explicitly enabled data removal upon uninstall.
$delete_data = get_option( 'iaq_delete_data_on_uninstall', 0 );

if ( $delete_data ) {
	global $wpdb;

	$tables = array(
		$wpdb->prefix . 'iaq_attempt_answers',
		$wpdb->prefix . 'iaq_quiz_attempts',
		$wpdb->prefix . 'iaq_quiz_questions',
		$wpdb->prefix . 'iaq_quizzes',
		$wpdb->prefix . 'iaq_questions',
		$wpdb->prefix . 'iaq_topics',
		$wpdb->prefix . 'iaq_subjects',
	);

	foreach ( $tables as $table ) {
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	// Delete options.
	delete_option( 'iaq_db_version' );
	delete_option( 'iaq_delete_data_on_uninstall' );
	delete_option( 'iaq_settings' );
}

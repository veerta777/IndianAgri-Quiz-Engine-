<?php
/**
 * Fired during plugin deactivation.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Deactivator
 */
class IAQ_Deactivator {

	/**
	 * Run deactivation tasks.
	 * Preserves all database tables and student attempts by default.
	 */
	public static function deactivate() {
		// Clear any transient caches or scheduled crons if set.
		delete_transient( 'iaq_admin_stats' );
	}
}

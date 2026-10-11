<?php
/**
 * Fired during plugin activation.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Activator
 */
class IAQ_Activator {

	/**
	 * Run activation tasks.
	 */
	public static function activate() {
		// Run database migration.
		IAQ_Database::create_tables();

		// Seed initial agriculture taxonomy.
		IAQ_Database::seed_default_data();

		// Add custom capability to administrator role.
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			$admin_role->add_cap( 'manage_iaq_quizzes' );
		}

		// Set default settings if not exists.
		if ( false === get_option( 'iaq_settings' ) ) {
			update_option(
				'iaq_settings',
				array(
					'brand_color'                  => '#117E3A',
					'default_duration'             => 60,
					'default_marks'                => 1.00,
					'default_negative_marks'       => 0.25,
					'delete_data_on_uninstall'     => 0,
					'enable_hindi_support'         => 1,
				)
			);
		}
	}
}

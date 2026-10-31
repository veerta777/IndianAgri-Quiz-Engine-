<?php
/**
 * Plugin Name:       IndianAgri Quiz Engine
 * Plugin URI:        https://indianagriexam.com
 * Description:       Secure, high-performance MCQ Question Bank, Quiz Builder, and Mock Test Engine tailored for agricultural competitive examinations.
 * Version:           3.2.1
 * Author:            IndianAgriExam.com
 * Author URI:        https://indianagriexam.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       indianagri-quiz-engine
 * Domain Path:       /languages
 * Requires at least: 5.8
 * Requires PHP:      7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
if ( ! defined( 'IAQ_VERSION' ) ) {
	define( 'IAQ_VERSION', '3.2.1' );
}

if ( ! defined( 'IAQ_DB_VERSION' ) ) {
	define( 'IAQ_DB_VERSION', '1.2.0' );
}

if ( ! defined( 'IAQ_PLUGIN_DIR' ) ) {
	define( 'IAQ_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'IAQ_PLUGIN_URL' ) ) {
	define( 'IAQ_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

if ( ! defined( 'IAQ_PLUGIN_BASENAME' ) ) {
	define( 'IAQ_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

// Require Core Plugin Classes.
require_once IAQ_PLUGIN_DIR . 'includes/class-database.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-activator.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-deactivator.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-question-bank.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-examinations.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-access-control.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-quiz-builder.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-practice-sets.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-mock-tests.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-quiz-attempt.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-bookmarks.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-wrong-notebook.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-custom-practice.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-leaderboard.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-study-streaks.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-import-export.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-frontend.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-admin.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-rest-api.php';
require_once IAQ_PLUGIN_DIR . 'includes/class-plugin.php';

/**
 * Register activation hook.
 */
function iaq_activate_plugin() {
	IAQ_Activator::activate();
}
register_activation_hook( __FILE__, 'iaq_activate_plugin' );

/**
 * Register deactivation hook.
 */
function iaq_deactivate_plugin() {
	IAQ_Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'iaq_deactivate_plugin' );

/**
 * Bootstrap and run the plugin.
 */
function iaq_run_plugin() {
	$plugin = IAQ_Plugin::get_instance();
	$plugin->run();
}
add_action( 'plugins_loaded', 'iaq_run_plugin' );

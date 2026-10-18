<?php
/**
 * Main plugin orchestrator and lifecycle coordinator.
 *
 * @package IndianAgri_Quiz_Engine
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class IAQ_Plugin
 */
class IAQ_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var IAQ_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Admin controller.
	 *
	 * @var IAQ_Admin|null
	 */
	public $admin = null;

	/**
	 * Frontend controller.
	 *
	 * @var IAQ_Frontend|null
	 */
	public $frontend = null;

	/**
	 * Quiz attempt controller.
	 *
	 * @var IAQ_Quiz_Attempt|null
	 */
	public $attempt = null;

	/**
	 * Get singleton instance.
	 *
	 * @return IAQ_Plugin
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Private constructor.
	 */
	private function __construct() {
		$this->admin    = new IAQ_Admin();
		$this->frontend = new IAQ_Frontend();
		$this->attempt  = new IAQ_Quiz_Attempt();
	}

	/**
	 * Run the plugin components and hook registrations.
	 */
	public function run() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this, 'maybe_update_db' ), 5 );

		// Always initialize frontend shortcodes, access control, and attempt handlers.
		IAQ_Access_Control::init();
		IAQ_Rest_Api::init();
		$this->frontend->init();
		$this->attempt->init();

		if ( is_admin() ) {
			$this->admin->init();
		}
	}

	/**
	 * Automatically create or upgrade database tables if version has changed.
	 */
	public function maybe_update_db() {
		$installed_ver = get_option( 'iaq_db_version', '0' );
		if ( version_compare( $installed_ver, IAQ_DB_VERSION, '<' ) ) {
			if ( class_exists( 'IAQ_Database' ) ) {
				IAQ_Database::create_tables();
				IAQ_Database::seed_default_data();
			}
			update_option( 'iaq_db_version', IAQ_DB_VERSION );
		}
	}

	/**
	 * Load plugin localization textdomain.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'indianagri-quiz-engine',
			false,
			dirname( IAQ_PLUGIN_BASENAME ) . '/languages'
		);
	}
}

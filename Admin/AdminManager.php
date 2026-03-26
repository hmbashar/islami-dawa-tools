<?php
/**
 * AdminManager.php
 *
 * Admin class for Islami Dawa Tools.
 *
 * @package IslamiDawaTools\Admin
 * @since 1.0.0
 */

namespace IslamiDawaTools\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use IslamiDawaTools\Admin\YouTubeSyncSettings;
use IslamiDawaTools\Admin\YouTubeSyncPage;

/**
 * Class AdminManager
 *
 * Manages all admin-related functionality.
 *
 * @package IslamiDawaTools\Admin
 * @since 1.0.0
 */
class AdminManager {

	/**
	 * YouTubeSyncSettings instance.
	 *
	 * @since 1.0.0
	 * @var YouTubeSyncSettings
	 */
	protected $youtube_sync_settings;

	/**
	 * YouTubeSyncPage instance.
	 *
	 * @since 1.0.0
	 * @var YouTubeSyncPage
	 */
	protected $youtube_sync_page;

	/**
	 * Constructor for AdminManager class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Initialize admin hooks and modules.
	 *
	 * @since 1.0.0
	 */
	private function init_hooks() {
		// Enqueue admin scripts and styles.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Initialize YouTube sync admin modules.
		$this->youtube_sync_settings = new YouTubeSyncSettings();
		$this->youtube_sync_page     = new YouTubeSyncPage();
	}

	/**
	 * Enqueue admin scripts and styles.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// Enqueue admin scripts and styles here.
	}
}

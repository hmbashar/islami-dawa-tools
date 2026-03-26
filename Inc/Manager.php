<?php
/**
 * Manager.php
 *
 * This file contains the Manager class, which is responsible for handling
 * the initialization of the required configurations and functionalities
 * for the Islami Dawa Tools plugin.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */

namespace IslamiDawaTools;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use IslamiDawaTools\Admin\AdminManager;
use IslamiDawaTools\Frontend\Frontend;
use IslamiDawaTools\Api\YouTube\YouTubeSyncManager;
use IslamiDawaTools\Cron\CronManager;

/**
 * The manager class for Islami Dawa Tools.
 *
 * This class handles the initialization of the required configurations and functionalities
 * for the Islami Dawa Tools plugin.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */
class Manager {

	/** @var AdminManager */
	protected $Admin_Manager;

	/** @var Frontend */
	protected $Frontend;

	/** @var YouTubeSyncManager */
	protected $youtube_sync_manager;

	/** @var CronManager */
	protected $cron_manager;

    /**
     * Constructor for the Manager class.
     *
     * This method initializes the IslamiDawaTools Manager by calling the init method.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initiate the IslamiDawaTools Manager
     *
     * This method initializes the Admin Manager and Frontend.
     *
     * @since 1.0.0
     */
	public function init() {
		$this->Admin_Manager         = new AdminManager();
		$this->Frontend              = new Frontend();
		$this->youtube_sync_manager  = new YouTubeSyncManager();
		$this->cron_manager          = new CronManager();
	}
}

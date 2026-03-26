<?php
/**
 * Deactivate.php
 *
 * This file handles the plugin deactivation.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */

namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use IslamiDawaTools\CronManager;

/**
 * Class Deactivate
 *
 * Handles plugin deactivation routines.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */
class Deactivate
{
    /**
     * Deactivate the plugin.
     *
     * @since 1.0.0
     */
	public static function deactivate() {
		// Clear the scheduled YouTube sync cron event.
		CronManager::clear_scheduled_event();

		// Flush rewrite rules.
		flush_rewrite_rules();

		/**
		 * Do action on plugin deactivation.
		 *
		 * @since 1.0.0
		 */
		do_action( 'islami_dawa_tools_deactivated' );
	}
}

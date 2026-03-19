<?php
/**
 * Activate.php
 *
 * This file handles the plugin activation.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */

namespace IslamiDawaTools;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class Activate
 *
 * Handles plugin activation routines.
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */
class Activate
{
    /**
     * Activate the plugin.
     *
     * @since 1.0.0
     */
    public static function activate()
    {
        // Flush rewrite rules.
        flush_rewrite_rules();

        // Set plugin version.
        update_option('islami_dawa_tools_version', ISLAMI_DAWA_TOOLS_VERSION);

        /**
         * Do action on plugin activation.
         *
         * @since 1.0.0
         */
        do_action('islami_dawa_tools_activated');
    }
}

<?php
/**
 * Plugin Name: Islami Dawa Tools
 * Plugin URI: https://github.com/PairDevs/islami-dawa-tools
 * Description: A comprehensive toolkit for Islamic Dawa (outreach) with Elementor integration, custom widgets, and enhanced form features including multi-currency support.
 * Version: 1.0.0
 * Author: PairDevs
 * Author URI: https://github.com/PairDevs
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: islami-dawa-tools
 * Domain Path: /languages
 * Namespace: IslamiDawaTools
 * Elementor tested up to: 3.32
 * Elementor Pro tested up to: 3.32
 *
 * @package IslamiDawaTools
 * @since 1.0.0
 */

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

final class IslamiDawaTools
{
    // Singleton instance.
    private static $instance = null;

    /**
     * Initializes the IslamiDawaTools class by defining constants, including necessary files, and initializing hooks.
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    /**
     * Retrieves the singleton instance of the plugin.
     *
     * @since 1.0.0
     *
     * @return IslamiDawaTools
     */
    public static function get_instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Define plugin constants.
     *
     * @since 1.0.0
     */
    private function define_constants()
    {
        define('ISLAMI_DAWA_TOOLS_VERSION', '1.0.0');
        define('ISLAMI_DAWA_TOOLS_FILE', __FILE__);
        define('ISLAMI_DAWA_TOOLS_DIR', plugin_dir_path(__FILE__));
        define('ISLAMI_DAWA_TOOLS_URL', plugin_dir_url(__FILE__));
        define('ISLAMI_DAWA_TOOLS_BASENAME', plugin_basename(__FILE__));
    }

    /**
     * Include required files.
     *
     * @since 1.0.0
     */
    private function include_files()
    {
        // Load Composer autoloader for PSR-4 namespaces.
        if (file_exists(ISLAMI_DAWA_TOOLS_DIR . 'vendor/autoload.php')) {
            require_once ISLAMI_DAWA_TOOLS_DIR . 'vendor/autoload.php';
        }
    }

    /**
     * Initialize plugin hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        // Load text domain.
        add_action('plugins_loaded', [$this, 'register_textdomain']);

        // Plugin activation and deactivation hooks.
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Initialize the plugin manager on plugins loaded.
        add_action('plugins_loaded', [$this, 'init']);
    }

    /**
     * Load plugin textdomain.
     *
     * @since 1.0.0
     */
    public function register_textdomain()
    {
        load_plugin_textdomain('islami-dawa-tools', false, dirname(ISLAMI_DAWA_TOOLS_BASENAME) . '/languages');
    }

    /**
     * Initialize the plugin.
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Create the manager instance.
        new \IslamiDawaTools\Manager();
    }

    /**
     * Plugin activation hook.
     *
     * @since 1.0.0
     */
    public function activate()
    {
        // Run activation hooks.
        \IslamiDawaTools\Activate::activate();
    }

    /**
     * Plugin deactivation hook.
     *
     * @since 1.0.0
     */
    public function deactivate()
    {
        // Run deactivation hooks.
        \IslamiDawaTools\Deactivate::deactivate();
    }

    /**
     * Get plugin version.
     *
     * @since 1.0.0
     *
     * @return string
     */
    public function get_version()
    {
        return ISLAMI_DAWA_TOOLS_VERSION;
    }
}

// Instantiate plugin.
IslamiDawaTools::get_instance();

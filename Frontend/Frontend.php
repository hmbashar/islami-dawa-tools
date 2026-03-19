<?php
/**
 * Frontend.php
 *
 * Frontend class for Islami Dawa Tools.
 *
 * @package IslamiDawaTools\Frontend
 * @since 1.0.0
 */

namespace IslamiDawaTools\Frontend;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use IslamiDawaTools\Frontend\GravityForms\GravityForms;

/**
 * Class Frontend
 *
 * Manages all frontend-related functionality.
 *
 * @package IslamiDawaTools\Frontend
 * @since 1.0.0
 */
class Frontend
{
    protected $gravity_forms;

    /**
     * Constructor for Frontend class.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init_modules();
        $this->init_hooks();
    }

    /**
     * Initialize frontend modules.
     *
     * @since 1.0.0
     */
    private function init_modules()
    {
        // Initialize Gravity Forms module.
        $this->gravity_forms = new GravityForms();
    }

    /**
     * Initialize frontend hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        // Add frontend hooks here.
        add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
    }

    /**
     * Enqueue frontend scripts and styles.
     *
     * @since 1.0.0
     */
    public function enqueue_scripts()
    {
        // Enqueue frontend scripts and styles here.
    }
}

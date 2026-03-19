<?php
/**
 * Elementor.php
 *
 * Manages Elementor integration for Islami Dawa Tools.
 *
 * @package IslamiDawaTools\Frontend\Elementor
 * @since 1.0.0
 */

namespace IslamiDawaTools\Frontend\Elementor;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use IslamiDawaTools\Frontend\Elementor\Widgets\ACF_Repeater_Widget;

/**
 * Class Elementor
 *
 * Registers and manages Elementor widgets and configuration.
 *
 * @package IslamiDawaTools\Frontend\Elementor
 * @since 1.0.0
 */
class Elementor
{
    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize Elementor hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        // Enqueue widget styles
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);

        // Check if Elementor is installed
        if (!did_action('elementor/loaded')) {
            add_action('elementor/loaded', [$this, 'register_elements']);
        } else {
            $this->register_elements();
        }
    }

    /**
     * Enqueue widget styles and scripts.
     *
     * @since 1.0.0
     */
    public function enqueue_styles()
    {
        // Enqueue CSS
        wp_enqueue_style(
            'islami-dawa-repeater-widget',
            ISLAMI_DAWA_TOOLS_URL . 'Frontend/Elementor/Assets/repeater-widget.css',
            [],
            ISLAMI_DAWA_TOOLS_VERSION
        );
    }

    /**
     * Register Elementor widgets and categories.
     *
     * @since 1.0.0
     */
    public function register_elements()
    {
        // Register custom widget category
        add_action('elementor/elements/categories_registered', [$this, 'register_category']);

        // Register widgets
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    /**
     * Register custom widget category.
     *
     * @since 1.0.0
     *
     * @param object $elements_manager Elementor elements manager.
     */
    public function register_category($elements_manager)
    {
        $elements_manager->add_category(
            'islami-dawa',
            [
                'title' => esc_html__('Islami Dawa Tools', 'islami-dawa-tools'),
                'icon'  => 'fa fa-leaf',
            ]
        );
    }

    /**
     * Register custom widgets.
     *
     * @since 1.0.0
     *
     * @param object $widgets_manager Elementor widgets manager.
     */
    public function register_widgets($widgets_manager)
    {
        // Register ACF Repeater Widget
        require_once ISLAMI_DAWA_TOOLS_DIR . 'Frontend/Elementor/Widgets/ACF_Repeater_Widget.php';
        $widgets_manager->register(new ACF_Repeater_Widget());
    }
}

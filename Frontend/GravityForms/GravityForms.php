<?php
/**
 * GravityForms.php
 *
 * Gravity Forms integration for Islami Dawa Tools.
 *
 * @package IslamiDawaTools\Frontend\GravityForms
 * @since 1.0.0
 */

namespace IslamiDawaTools\Frontend\GravityForms;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class GravityForms
 *
 * Manages Gravity Forms integrations and features.
 *
 * @package IslamiDawaTools\Frontend\GravityForms
 * @since 1.0.0
 */
class GravityForms
{
    /**
     * Constructor for GravityForms class.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Initialize Gravity Forms hooks.
     *
     * @since 1.0.0
     */
    private function init_hooks()
    {
        // Check if Gravity Forms is active.
        if (!class_exists('GFForms')) {
            return;
        }

        // Add BDT currency support.
        add_filter('gform_currencies', [$this, 'add_bdt_currency']);
    }

    /**
     * Add BDT (Bangladeshi Taka) currency to Gravity Forms.
     *
     * @since 1.0.0
     *
     * @param array $currencies Array of available currencies.
     *
     * @return array
     */
    public function add_bdt_currency($currencies)
    {
        // Check if BDT is already in the list.
        if (isset($currencies['BDT'])) {
            return $currencies;
        }

        // Add BDT currency.
        $currencies['BDT'] = [
            'code'               => 'BDT',
            'name'               => __('Bangladeshi Taka', 'islami-dawa-tools'),
            'symbol_left'        => '৳ ',
            'symbol_right'       => '',
            'symbol_padding'     => ' ',
            'thousand_separator' => ',',
            'decimal_separator'  => '.',
            'decimals'           => 2,
        ];

        return $currencies;
    }
}

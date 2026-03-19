<?php
/**
 * ACF_Repeater_Widget.php
 *
 * Elementor widget for displaying ACF repeater fields dynamically.
 *
 * @package IslamiDawaTools\Frontend\Elementor\Widgets
 * @since 1.0.0
 */

namespace IslamiDawaTools\Frontend\Elementor\Widgets;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * ACF Repeater Widget
 *
 * Displays ACF repeater field data with dynamic content support.
 *
 * @package IslamiDawaTools\Frontend\Elementor\Widgets
 * @since 1.0.0
 */
class ACF_Repeater_Widget extends Widget_Base
{
    /**
     * Get widget name.
     *
     * @since 1.0.0
     *
     * @return string Widget name.
     */
    public function get_name()
    {
        return 'islami-dawa-acf-repeater';
    }

    /**
     * Get widget title.
     *
     * @since 1.0.0
     *
     * @return string Widget title.
     */
    public function get_title()
    {
        return __('ACF Repeater Field', 'islami-dawa-tools');
    }

    /**
     * Get widget icon.
     *
     * @since 1.0.0
     *
     * @return string Widget icon.
     */
    public function get_icon()
    {
        return 'eicon-posts-grid';
    }

    /**
     * Get widget categories.
     *
     * @since 1.0.0
     *
     * @return array Widget categories.
     */
    public function get_categories()
    {
        return ['islami-dawa'];
    }

    /**
     * Get script dependencies.
     *
     * @since 1.0.0
     *
     * @return array Script dependencies.
     */
    public function get_script_depends()
    {
        return ['jquery'];
    }

    /**
     * Register widget controls.
     *
     * @since 1.0.0
     */
    protected function register_controls()
    {
        // Content Section
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'islami-dawa-tools'),
                'tab'   => Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'repeater_field_key',
            [
                'label'       => __('Repeater Field Key', 'islami-dawa-tools'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __('e.g., expenditure_sector', 'islami-dawa-tools'),
                'description' => __('Enter the ACF repeater field key/name', 'islami-dawa-tools'),
                'dynamic'     => ['active' => false],
            ]
        );

        $this->add_control(
            'sub_field_keys',
            [
                'label'       => __('Sub-field Keys (Optional)', 'islami-dawa-tools'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __('e.g., text, amount, date', 'islami-dawa-tools'),
                'description' => __('Comma-separated list of sub-field keys to display. Leave empty to show all fields. Example: text, amount, date', 'islami-dawa-tools'),
                'dynamic'     => ['active' => false],
            ]
        );

        $this->add_control(
            'display_layout',
            [
                'label'   => __('Display Layout', 'islami-dawa-tools'),
                'type'    => Controls_Manager::SELECT,
                'default' => 'table',
                'options' => [
                    'table'      => __('Table', 'islami-dawa-tools'),
                    'list'       => __('List', 'islami-dawa-tools'),
                    'cards'      => __('Cards', 'islami-dawa-tools'),
                    'icon_text'  => __('Icon + Text', 'islami-dawa-tools'),
                    'custom'     => __('Custom (Show All Fields)', 'islami-dawa-tools'),
                ],
            ]
        );

        $this->add_control(
            'widget_heading',
            [
                'label'       => __('Widget Heading', 'islami-dawa-tools'),
                'type'        => Controls_Manager::TEXT,
                'placeholder' => __('e.g., Our Services, Features, etc.', 'islami-dawa-tools'),
                'condition'   => [
                    'display_layout' => 'icon_text',
                ],
            ]
        );

        $this->add_control(
            'item_icon',
            [
                'label'            => __('List Item Icon', 'islami-dawa-tools'),
                'type'             => Controls_Manager::ICONS,
                'default'          => [
                    'value'   => 'fas fa-check',
                    'library' => 'fa-solid',
                ],
                'condition'        => [
                    'display_layout' => 'icon_text',
                ],
            ]
        );

        $this->add_control(
            'columns',
            [
                'label'     => __('Columns', 'islami-dawa-tools'),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 2,
                'min'       => 1,
                'max'       => 6,
                'condition' => [
                    'display_layout' => 'cards',
                ],
            ]
        );

        $this->end_controls_section();

        // Style Section
        $this->start_controls_section(
            'style_section',
            [
                'label' => __('Style', 'islami-dawa-tools'),
                'tab'   => Controls_Manager::TAB_STYLE,
            ]
        );

        // Container styling
        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'container_border',
                'label'    => __('Border', 'islami-dawa-tools'),
                'selector' => '{{WRAPPER}} .repeater-container',
            ]
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            [
                'name'     => 'container_shadow',
                'label'    => __('Box Shadow', 'islami-dawa-tools'),
                'selector' => '{{WRAPPER}} .repeater-container',
            ]
        );

        $this->add_control(
            'container_padding',
            [
                'label'      => __('Padding', 'islami-dawa-tools'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .repeater-container' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'container_background',
            [
                'label'     => __('Background Color', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .repeater-container' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        // Row/Item styling
        $this->add_control(
            'item_background',
            [
                'label'     => __('Item Background Color', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .repeater-item' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            [
                'name'     => 'item_border',
                'label'    => __('Item Border', 'islami-dawa-tools'),
                'selector' => '{{WRAPPER}} .repeater-item',
            ]
        );

        $this->add_control(
            'item_padding',
            [
                'label'      => __('Item Padding', 'islami-dawa-tools'),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', 'em', '%'],
                'selectors'  => [
                    '{{WRAPPER}} .repeater-item' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        // Text styling
        $this->add_group_control(
            Group_Control_Typography::get_type(),
            [
                'name'     => 'text_typography',
                'label'    => __('Text Typography', 'islami-dawa-tools'),
                'selector' => '{{WRAPPER}} .repeater-item',
            ]
        );

        $this->add_control(
            'text_color',
            [
                'label'     => __('Text Color', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'selectors' => [
                    '{{WRAPPER}} .repeater-item' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();

        // Table Styling Section
        $this->start_controls_section(
            'table_style_section',
            [
                'label'     => __('Table Style', 'islami-dawa-tools'),
                'tab'       => Controls_Manager::TAB_STYLE,
                'condition' => [
                    'display_layout' => 'table',
                ],
            ]
        );

        $this->add_control(
            'table_header_background',
            [
                'label'     => __('Header Background', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#f5f5f5',
                'selectors' => [
                    '{{WRAPPER}} .repeater-table thead' => 'background-color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_header_color',
            [
                'label'     => __('Header Text Color', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#333',
                'selectors' => [
                    '{{WRAPPER}} .repeater-table thead' => 'color: {{VALUE}};',
                ],
            ]
        );

        $this->add_control(
            'table_border_color',
            [
                'label'     => __('Border Color', 'islami-dawa-tools'),
                'type'      => Controls_Manager::COLOR,
                'default'   => '#ddd',
                'selectors' => [
                    '{{WRAPPER}} .repeater-table, {{WRAPPER}} .repeater-table th, {{WRAPPER}} .repeater-table td' => 'border-color: {{VALUE}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     *
     * @since 1.0.0
     */
    protected function render()
    {
        $settings = $this->get_settings_for_display();
        $field_key = sanitize_text_field($settings['repeater_field_key']);
        $layout = $settings['display_layout'];
        $sub_field_keys = $settings['sub_field_keys'];

        // Check if ACF is active
        if (!function_exists('get_field')) {
            echo '<div class="elementor-alert elementor-alert-warning">';
            echo esc_html__('ACF (Advanced Custom Fields) is not installed or activated.', 'islami-dawa-tools');
            echo '</div>';
            return;
        }

        // Get the repeater field data
        $repeater_data = get_field($field_key);

        // Check if field exists and has data
        if (empty($repeater_data)) {
            echo '<div class="elementor-alert elementor-alert-info">';
            echo sprintf(
                esc_html__('No data found for repeater field: %s', 'islami-dawa-tools'),
                esc_html($field_key)
            );
            echo '</div>';
            return;
        }

        // Filter fields if sub-field keys are specified
        if (!empty($sub_field_keys)) {
            $repeater_data = $this->filter_repeater_fields($repeater_data, $sub_field_keys);
        }

        // Render based on layout
        switch ($layout) {
            case 'table':
                $this->render_table_layout($repeater_data);
                break;
            case 'list':
                $this->render_list_layout($repeater_data);
                break;
            case 'cards':
                $this->render_cards_layout($repeater_data, $settings['columns']);
                break;
            case 'custom':
                $this->render_custom_layout($repeater_data);
                break;
            case 'icon_text':
                $this->render_icon_text_layout($repeater_data, $settings);
                break;
            default:
                $this->render_table_layout($repeater_data);
        }
    }

    /**
     * Filter repeater fields based on specified sub-field keys.
     *
     * @since 1.0.0
     *
     * @param array  $repeater_data The original repeater data.
     * @param string $sub_field_keys Comma-separated sub-field keys.
     *
     * @return array Filtered repeater data.
     */
    private function filter_repeater_fields($repeater_data, $sub_field_keys)
    {
        // Parse the comma-separated keys
        $keys = array_map('trim', explode(',', $sub_field_keys));
        $keys = array_filter($keys); // Remove empty values

        if (empty($keys)) {
            return $repeater_data;
        }

        // Filter each row to only include specified fields
        $filtered_data = [];
        foreach ($repeater_data as $row) {
            if (is_array($row)) {
                $filtered_row = [];
                foreach ($keys as $key) {
                    if (isset($row[$key])) {
                        $filtered_row[$key] = $row[$key];
                    }
                }
                if (!empty($filtered_row)) {
                    $filtered_data[] = $filtered_row;
                }
            } else {
                $filtered_data[] = $row;
            }
        }

        return $filtered_data;
    }

    /**
     * Render table layout.
     *
     * @since 1.0.0
     *
     * @param array $data Repeater data.
     */
    private function render_table_layout($data)
    {
        if (empty($data)) {
            return;
        }

        echo '<div class="repeater-container repeater-table-container">';
        echo '<table class="repeater-table">';

        // Table header
        $first_row = reset($data);
        if (is_array($first_row)) {
            echo '<thead><tr>';
            foreach (array_keys($first_row) as $key) {
                echo '<th>' . esc_html(ucwords(str_replace('_', ' ', $key))) . '</th>';
            }
            echo '</tr></thead>';
        }

        // Table body
        echo '<tbody>';
        foreach ($data as $row) {
            echo '<tr class="repeater-item">';
            if (is_array($row)) {
                foreach ($row as $value) {
                    echo '<td>';
                    if (is_array($value)) {
                        echo esc_html(implode(', ', $value));
                    } else {
                        echo wp_kses_post($value);
                    }
                    echo '</td>';
                }
            } else {
                echo '<td>' . wp_kses_post($row) . '</td>';
            }
            echo '</tr>';
        }
        echo '</tbody>';

        echo '</table>';
        echo '</div>';
    }

    /**
     * Render list layout.
     *
     * @since 1.0.0
     *
     * @param array $data Repeater data.
     */
    private function render_list_layout($data)
    {
        echo '<div class="repeater-container repeater-list-container">';
        echo '<ul class="repeater-list">';

        foreach ($data as $row) {
            echo '<li class="repeater-item">';
            if (is_array($row)) {
                echo '<ul>';
                foreach ($row as $key => $value) {
                    echo '<li>';
                    echo '<strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ':</strong> ';
                    if (is_array($value)) {
                        echo esc_html(implode(', ', $value));
                    } else {
                        echo wp_kses_post($value);
                    }
                    echo '</li>';
                }
                echo '</ul>';
            } else {
                echo wp_kses_post($row);
            }
            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
    }

    /**
     * Render cards layout.
     *
     * @since 1.0.0
     *
     * @param array $data    Repeater data.
     * @param int   $columns Number of columns.
     */
    private function render_cards_layout($data, $columns = 2)
    {
        echo '<div class="repeater-container repeater-cards-container" style="display: grid; grid-template-columns: repeat(' . absint($columns) . ', 1fr); gap: 20px;">';

        foreach ($data as $row) {
            echo '<div class="repeater-item repeater-card" style="padding: 20px; border: 1px solid #ddd; border-radius: 8px;">';
            if (is_array($row)) {
                foreach ($row as $key => $value) {
                    echo '<div class="card-field">';
                    echo '<strong>' . esc_html(ucwords(str_replace('_', ' ', $key))) . ':</strong> ';
                    if (is_array($value)) {
                        echo esc_html(implode(', ', $value));
                    } else {
                        echo wp_kses_post($value);
                    }
                    echo '</div>';
                }
            } else {
                echo wp_kses_post($row);
            }
            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Render custom layout showing all fields.
     *
     * @since 1.0.0
     *
     * @param array $data Repeater data.
     */
    private function render_custom_layout($data)
    {
        echo '<div class="repeater-container repeater-custom-container">';

        foreach ($data as $index => $row) {
            echo '<div class="repeater-item repeater-row" style="margin-bottom: 30px; padding: 20px; border: 1px solid #e0e0e0; border-radius: 4px;">';
            echo '<h4 style="margin-top: 0;">' . esc_html(sprintf(__('Item %d', 'islami-dawa-tools'), $index + 1)) . '</h4>';

            if (is_array($row)) {
                echo '<dl style="margin: 0;">';
                foreach ($row as $key => $value) {
                    echo '<dt style="font-weight: bold; margin-top: 10px;">' . esc_html(ucwords(str_replace('_', ' ', $key))) . '</dt>';
                    echo '<dd style="margin: 5px 0 0 20px;">';
                    if (is_array($value)) {
                        echo esc_html(implode(', ', $value));
                    } else {
                        echo wp_kses_post($value);
                    }
                    echo '</dd>';
                }
                echo '</dl>';
            } else {
                echo wp_kses_post($row);
            }

            echo '</div>';
        }

        echo '</div>';
    }

    /**
     * Render icon and text layout.
     *
     * @since 1.0.0
     *
     * @param array $data Repeater data.
     * @param array $settings Widget settings.
     */
    private function render_icon_text_layout($data, $settings)
    {
        if (empty($data)) {
            return;
        }

        echo '<div class="repeater-container repeater-icon-text-container">';

        // Render heading if provided
        if (!empty($settings['widget_heading'])) {
            echo '<h3 class="repeater-heading">' . esc_html($settings['widget_heading']) . '</h3>';
        }

        echo '<ul class="repeater-icon-text-list">';

        // Get the icon from settings
        $icon = !empty($settings['item_icon']) ? $settings['item_icon'] : 'fas fa-check';

        foreach ($data as $row) {
            // Get text from the first/main item
            $text = '';
            if (is_array($row)) {
                // If it's an array, try to get the text field or first value
                $text = !empty($row['text']) ? $row['text'] : (reset($row) ?: '');
            } else {
                $text = $row;
            }

            echo '<li class="repeater-icon-text-item">';

            // Render icon using Elementor's icon renderer
            if (!empty($icon)) {
                echo '<span class="repeater-item-icon">';
                \Elementor\Icons_Manager::render_icon($icon, ['aria-hidden' => 'true']);
                echo '</span>';
            }

            // Render text
            echo '<span class="repeater-item-text">' . wp_kses_post($text) . '</span>';

            echo '</li>';
        }

        echo '</ul>';
        echo '</div>';
    }

    /**
     * Render widget output in the editor.
     *
     * @since 1.0.0
     */
    protected function content_template()
    {
        ?>
        <div class="elementor-custom-element">
            <p><?php esc_html_e('ACF Repeater Field Widget', 'islami-dawa-tools'); ?></p>
            <p style="color: #999; font-size: 12px;">
                <?php esc_html_e('Enter a repeater field key and select a layout to display your ACF repeater data.', 'islami-dawa-tools'); ?>
            </p>
        </div>
        <?php
    }
}

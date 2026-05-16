<?php
namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BadriMembers {
    const POST_TYPE      = 'badri_member';
    const NONCE_ACTION   = 'islami_dawa_badri_member_submit';
    const NONCE_NAME     = 'islami_dawa_badri_member_nonce';
    const SETTINGS_GROUP = 'islami_dawa_badri_member_settings_group';
    const OPTION_NAME    = 'islami_dawa_badri_member_settings';
    const SETTINGS_SLUG  = 'islami-dawa-tools-badri-settings';
    const DASHBOARD_SLUG = 'islami-dawa-tools-badri-dashboard';

    private $meta_keys = array(
        'guardian_name',
        'mobile',
        'profession',
        'donation_frequency',
        'donation_amount',
        'donation_custom_amount',
        'donation_amount_text',
        'permanent_address',
        'permanent_district',
        'current_address',
        'current_district',
        'public_visibility',
        'photo_visibility',
    );

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_action( 'after_setup_theme', array( $this, 'enable_thumbnail_support' ) );
        add_shortcode( 'badri_member_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'badri_members_grid', array( $this, 'render_grid_shortcode' ) );

        add_action( 'admin_post_nopriv_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_post_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );
        add_action( 'wp_ajax_nopriv_islami_dawa_badri_member_submit_ajax', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_islami_dawa_badri_member_submit_ajax', array( $this, 'handle_ajax_submission' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );
        add_action( 'admin_menu', array( $this, 'register_admin_menu' ), 20 );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_member_meta_box' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_member_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );
        add_action( 'restrict_manage_posts', array( $this, 'render_admin_list_filters' ) );
        add_filter( 'parse_query', array( $this, 'filter_admin_member_query' ) );
        add_filter( 'views_edit-' . self::POST_TYPE, array( $this, 'style_admin_member_views' ) );

        add_filter( 'theme_page_templates', array( $this, 'register_page_templates' ) );
        add_filter( 'template_include', array( $this, 'load_page_template' ) );
    }

    public function enable_thumbnail_support() {
        add_theme_support( 'post-thumbnails', array( self::POST_TYPE ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'               => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'singular_name'      => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'menu_name'          => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'name_admin_bar'     => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'add_new'            => esc_html__( 'নতুন সদস্য', 'islami-dawa-tools' ),
            'add_new_item'       => esc_html__( 'নতুন বদরী সদস্য যোগ করুন', 'islami-dawa-tools' ),
            'edit_item'          => esc_html__( 'বদরী সদস্য সম্পাদনা করুন', 'islami-dawa-tools' ),
            'new_item'           => esc_html__( 'নতুন বদরী সদস্য', 'islami-dawa-tools' ),
            'view_item'          => esc_html__( 'বদরী সদস্য দেখুন', 'islami-dawa-tools' ),
            'search_items'       => esc_html__( 'বদরী সদস্য খুঁজুন', 'islami-dawa-tools' ),
            'not_found'          => esc_html__( 'কোনো সদস্য পাওয়া যায়নি', 'islami-dawa-tools' ),
            'not_found_in_trash' => esc_html__( 'ট্র্যাশে কোনো সদস্য পাওয়া যায়নি', 'islami-dawa-tools' ),
        );

        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => $labels,
                'public'              => true,
                'publicly_queryable'   => false,
                'show_ui'             => true,
                'show_in_menu'        => 'islami-dawa-tools',
                'show_in_rest'        => true,
                'has_archive'         => false,
                'exclude_from_search' => true,
                'menu_icon'           => 'dashicons-groups',
                'supports'            => array( 'title', 'thumbnail' ),
                'capability_type'     => 'post',
                'rewrite'             => false,
            )
        );
    }

    public function enqueue_frontend_assets() {
        wp_enqueue_style(
            'islami-dawa-badri-members',
            ISLAMI_DAWA_TOOLS_FRONTEND_ASSETS . 'badri-members.css',
            array(),
            ISLAMI_DAWA_TOOLS_VERSION
        );

        wp_enqueue_style(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css',
            array(),
            '11'
        );

        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js',
            array(),
            '11',
            true
        );

        wp_enqueue_script(
            'islami-dawa-badri-members',
            ISLAMI_DAWA_TOOLS_FRONTEND_ASSETS . 'badri-members.js',
            array( 'jquery', 'sweetalert2' ),
            ISLAMI_DAWA_TOOLS_VERSION,
            true
        );

        $settings = $this->get_settings();
        wp_localize_script(
            'islami-dawa-badri-members',
            'islamiDawaBadriMembers',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce'   => wp_create_nonce( self::NONCE_ACTION ),
                'i18n'    => array(
                    'processing'        => $settings['processing_message'],
                    'successTitle'      => $settings['success_title'],
                    'success'           => $settings['success_message'],
                    'error'             => $settings['error_message'],
                    'validationTitle'   => $settings['validation_title'],
                    'requiredMessage'   => $settings['required_field_message'],
                    'customAmountError' => $settings['custom_amount_error_message'],
                    'photoTypeError'    => $settings['photo_type_error_message'],
                    'photoSizeError'    => $settings['photo_size_error_message'],
                    'ok'                => esc_html__( 'ঠিক আছে', 'islami-dawa-tools' ),
                ),
            )
        );
    }

    public function enqueue_admin_assets( $hook ) {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

        $is_badri_settings  = false !== strpos( $hook, self::SETTINGS_SLUG );
        $is_badri_dashboard = false !== strpos( $hook, self::DASHBOARD_SLUG );
        $is_badri_post      = $screen && self::POST_TYPE === $screen->post_type;

        if ( ! $is_badri_settings && ! $is_badri_dashboard && ! $is_badri_post ) {
            return;
        }

        wp_enqueue_style(
            'islami-dawa-badri-admin',
            ISLAMI_DAWA_TOOLS_URL . 'Admin/assets/css/badri-members-admin.css',
            array(),
            ISLAMI_DAWA_TOOLS_VERSION
        );

        wp_enqueue_script(
            'islami-dawa-badri-admin',
            ISLAMI_DAWA_TOOLS_URL . 'Admin/assets/js/badri-members-admin.js',
            array( 'jquery' ),
            ISLAMI_DAWA_TOOLS_VERSION,
            true
        );
    }

    public function register_admin_menu() {
        add_submenu_page(
            'islami-dawa-tools',
            esc_html__( 'বদরী সদস্য সেটিংস', 'islami-dawa-tools' ),
            esc_html__( 'বদরী সদস্য সেটিংস', 'islami-dawa-tools' ),
            'manage_options',
            self::SETTINGS_SLUG,
            array( $this, 'render_settings_page' )
        );
    }

    public function get_default_settings() {
        return array(
            'form_title'               => esc_html__( 'আজীবন বদরী সদস্য/সদস্যা ফরম', 'islami-dawa-tools' ),
            'form_description'         => esc_html__( 'নিচের তথ্যগুলো পূরণ করে জমা দিন। অ্যাডমিন যাচাই করার পর সদস্য তালিকায় প্রকাশ করা হবে।', 'islami-dawa-tools' ),
            'submit_button_text'       => esc_html__( 'জমা দিন', 'islami-dawa-tools' ),
            'success_title'            => esc_html__( 'ধন্যবাদ!', 'islami-dawa-tools' ),
            'success_message'          => esc_html__( 'আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর আপনার সাথে যোগাযোগ করা হবে।', 'islami-dawa-tools' ),
            'error_message'            => esc_html__( 'দুঃখিত, তথ্য জমা দেওয়া যায়নি। অনুগ্রহ করে সব প্রয়োজনীয় তথ্য পূরণ করুন।', 'islami-dawa-tools' ),
            'captcha_error_message'    => esc_html__( 'CAPTCHA উত্তর সঠিক নয়। অনুগ্রহ করে আবার চেষ্টা করুন।', 'islami-dawa-tools' ),
            'processing_message'       => esc_html__( 'আপনার তথ্য জমা হচ্ছে...', 'islami-dawa-tools' ),
            'validation_title'         => esc_html__( 'প্রয়োজনীয় তথ্য দিন', 'islami-dawa-tools' ),
            'required_field_message'   => esc_html__( 'অনুগ্রহ করে “{field}” পূরণ করুন।', 'islami-dawa-tools' ),
            'custom_amount_error_message' => esc_html__( 'অনুগ্রহ করে কাস্টম অনুদানের পরিমাণ লিখুন।', 'islami-dawa-tools' ),
            'photo_type_error_message' => esc_html__( 'অনুগ্রহ করে JPG, PNG বা WEBP ছবি আপলোড করুন।', 'islami-dawa-tools' ),
            'photo_size_error_message' => esc_html__( 'ছবির সাইজ সর্বোচ্চ {size}MB হতে পারবে।', 'islami-dawa-tools' ),
            'grid_title'               => esc_html__( 'আজীবন বদরী সদস্য/সদস্যা তালিকা', 'islami-dawa-tools' ),
            'grid_description'         => esc_html__( 'অ্যাডমিন অনুমোদিত সদস্যদের তালিকা এখানে প্রদর্শিত হচ্ছে।', 'islami-dawa-tools' ),
            'empty_message'            => esc_html__( 'এখনো কোনো প্রকাশিত সদস্য পাওয়া যায়নি।', 'islami-dawa-tools' ),
            'photo_max_size_mb'        => '2',
            'admin_notification_email' => get_option( 'admin_email' ),
            'admin_email_subject'      => esc_html__( 'নতুন বদরী সদস্য আবেদন: {name}', 'islami-dawa-tools' ),
            'admin_email_body'         => esc_html__( 'একটি নতুন বদরী সদস্য আবেদন জমা হয়েছে। অনুগ্রহ করে অ্যাডমিন থেকে রিভিউ করুন।', 'islami-dawa-tools' ),
            'extra_section_kicker'     => esc_html__( 'অতিরিক্ত তথ্য', 'islami-dawa-tools' ),
            'extra_section_title'      => esc_html__( 'প্রয়োজনীয় অতিরিক্ত তথ্য', 'islami-dawa-tools' ),
            'captcha_enabled'          => '1',
            'captcha_min_number'       => '1',
            'captcha_max_number'       => '9',
            'captcha_operator'         => 'mixed',
            'captcha_label_prefix'     => esc_html__( 'CAPTCHA', 'islami-dawa-tools' ),
            'additional_fields'        => array(),
        );
    }

    public function get_settings() {
        $saved    = get_option( self::OPTION_NAME, array() );
        $settings = wp_parse_args( is_array( $saved ) ? $saved : array(), $this->get_default_settings() );

        $old_success_message = esc_html__( 'আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর প্রকাশ করা হবে।', 'islami-dawa-tools' );
        if ( isset( $settings['success_message'] ) && $old_success_message === $settings['success_message'] ) {
            $settings['success_message'] = esc_html__( 'আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর আপনার সাথে যোগাযোগ করা হবে।', 'islami-dawa-tools' );
        }

        return $settings;
    }

    public function register_settings() {
        register_setting(
            self::SETTINGS_GROUP,
            self::OPTION_NAME,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this, 'sanitize_settings' ),
                'default'           => $this->get_default_settings(),
            )
        );
    }

    public function sanitize_settings( $input ) {
        $defaults = $this->get_default_settings();
        $output   = array();
        $input    = is_array( $input ) ? $input : array();

        foreach ( $defaults as $key => $default ) {
            if ( 'additional_fields' === $key ) {
                continue;
            }

            $value = isset( $input[ $key ] ) ? wp_unslash( $input[ $key ] ) : $default;

            if ( in_array( $key, array( 'form_description', 'admin_email_body', 'grid_description', 'success_message', 'error_message', 'captcha_error_message' ), true ) ) {
                $output[ $key ] = sanitize_textarea_field( $value );
            } elseif ( 'admin_notification_email' === $key ) {
                $output[ $key ] = sanitize_email( $value );
            } elseif ( in_array( $key, array( 'photo_max_size_mb', 'captcha_min_number', 'captcha_max_number' ), true ) ) {
                $output[ $key ] = max( 1, absint( $value ) );
            } elseif ( 'captcha_enabled' === $key ) {
                $output[ $key ] = ! empty( $value ) ? '1' : '0';
            } elseif ( 'captcha_operator' === $key ) {
                $output[ $key ] = in_array( $value, array( 'add', 'subtract', 'mixed' ), true ) ? $value : 'mixed';
            } else {
                $output[ $key ] = sanitize_text_field( $value );
            }
        }

        $output['captcha_enabled'] = ! empty( $input['captcha_enabled'] ) ? '1' : '0';
        $output['additional_fields'] = $this->sanitize_additional_fields( isset( $input['additional_fields'] ) ? $input['additional_fields'] : array() );

        if ( isset( $output['captcha_min_number'], $output['captcha_max_number'] ) && $output['captcha_min_number'] > $output['captcha_max_number'] ) {
            $tmp = $output['captcha_min_number'];
            $output['captcha_min_number'] = $output['captcha_max_number'];
            $output['captcha_max_number'] = $tmp;
        }

        return $output;
    }

    private function sanitize_additional_fields( $fields ) {
        $clean = array();

        if ( ! is_array( $fields ) ) {
            return $clean;
        }

        foreach ( $fields as $field ) {
            if ( ! is_array( $field ) ) {
                continue;
            }

            $label = isset( $field['label'] ) ? sanitize_text_field( wp_unslash( $field['label'] ) ) : '';
            $key   = isset( $field['key'] ) ? sanitize_key( wp_unslash( $field['key'] ) ) : '';
            $type  = isset( $field['type'] ) ? sanitize_key( wp_unslash( $field['type'] ) ) : 'text';

            if ( '' === $label ) {
                continue;
            }

            if ( '' === $key ) {
                $key = sanitize_key( strtolower( remove_accents( $label ) ) );
            }

            if ( '' === $key ) {
                $key = 'field_' . count( $clean );
            }

            $key = preg_replace( '/^badri_extra_/', '', $key );

            if ( ! in_array( $type, array( 'text', 'number', 'email', 'textarea', 'select', 'date' ), true ) ) {
                $type = 'text';
            }

            $options_raw = isset( $field['options'] ) ? sanitize_textarea_field( wp_unslash( $field['options'] ) ) : '';

            $clean[] = array(
                'key'          => $key,
                'label'        => $label,
                'type'         => $type,
                'placeholder'  => isset( $field['placeholder'] ) ? sanitize_text_field( wp_unslash( $field['placeholder'] ) ) : '',
                'options'      => $options_raw,
                'required'     => ! empty( $field['required'] ) ? '1' : '0',
                'show_in_grid' => ! empty( $field['show_in_grid'] ) ? '1' : '0',
            );
        }

        return $clean;
    }

    private function get_additional_fields() {
        $settings = $this->get_settings();
        return isset( $settings['additional_fields'] ) && is_array( $settings['additional_fields'] ) ? $settings['additional_fields'] : array();
    }

    public function render_dashboard_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $counts    = wp_count_posts( self::POST_TYPE );
        $published = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        $pending   = isset( $counts->pending ) ? absint( $counts->pending ) : 0;
        $draft     = isset( $counts->draft ) ? absint( $counts->draft ) : 0;
        $total     = $published + $pending + $draft;

        $hidden_query = new \WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => array( 'publish', 'pending', 'draft' ),
                'posts_per_page' => 1,
                'meta_key'       => '_badri_public_visibility',
                'meta_value'     => 'hide',
                'fields'         => 'ids',
                'no_found_rows'  => false,
            )
        );
        $hidden_count = absint( $hidden_query->found_posts );
        wp_reset_postdata();

        $recent = new \WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => array( 'publish', 'pending', 'draft' ),
                'posts_per_page' => 6,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        $settings = $this->get_settings();
        ?>
        <div class="wrap idt-badri-settings-wrap idt-badri-dashboard-wrap">
            <div class="idt-badri-dashboard-hero">
                <div class="idt-badri-hero-icon"><span class="dashicons dashicons-groups"></span></div>
                <div class="idt-badri-dashboard-hero-text">
                    <h1><?php echo esc_html__( 'বদরী সদস্য ড্যাশবোর্ড', 'islami-dawa-tools' ); ?></h1>
                    <p><?php echo esc_html__( 'আজীবন বদরী সদস্য/সদস্যা আবেদন, প্রকাশনা ও সেটিংস এক জায়গা থেকে দেখুন।', 'islami-dawa-tools' ); ?></p>
                </div>
                <div class="idt-badri-dashboard-actions">
                    <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ); ?>"><?php echo esc_html__( '+ নতুন সদস্য', 'islami-dawa-tools' ); ?></a>
                    <a class="button" href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ); ?>"><?php echo esc_html__( 'সেটিংস', 'islami-dawa-tools' ); ?></a>
                </div>
            </div>

            <div class="idt-badri-dashboard-stats">
                <?php
                $stats = array(
                    array( 'label' => esc_html__( 'মোট সদস্য', 'islami-dawa-tools' ), 'value' => $total, 'icon' => 'dashicons-groups', 'accent' => 'green' ),
                    array( 'label' => esc_html__( 'প্রকাশিত', 'islami-dawa-tools' ), 'value' => $published, 'icon' => 'dashicons-yes-alt', 'accent' => 'blue' ),
                    array( 'label' => esc_html__( 'রিভিউ অপেক্ষমাণ', 'islami-dawa-tools' ), 'value' => $pending, 'icon' => 'dashicons-clock', 'accent' => 'orange' ),
                    array( 'label' => esc_html__( 'গোপন সদস্য', 'islami-dawa-tools' ), 'value' => $hidden_count, 'icon' => 'dashicons-hidden', 'accent' => 'gold' ),
                );
                foreach ( $stats as $stat ) :
                    ?>
                    <div class="idt-badri-stat-card is-<?php echo esc_attr( $stat['accent'] ); ?>">
                        <span class="dashicons <?php echo esc_attr( $stat['icon'] ); ?>"></span>
                        <strong><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></strong>
                        <p><?php echo esc_html( $stat['label'] ); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="idt-badri-dashboard-grid">
                <div class="idt-badri-dashboard-panel idt-badri-dashboard-panel-large">
                    <div class="idt-badri-dashboard-panel-head">
                        <h2><span class="dashicons dashicons-list-view"></span><?php echo esc_html__( 'সাম্প্রতিক আবেদন', 'islami-dawa-tools' ); ?></h2>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><?php echo esc_html__( 'সব দেখুন', 'islami-dawa-tools' ); ?></a>
                    </div>
                    <?php if ( $recent->have_posts() ) : ?>
                        <div class="idt-badri-recent-members">
                            <?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
                                <?php
                                $status = get_post_status();
                                $mobile = get_post_meta( get_the_ID(), '_badri_mobile', true );
                                ?>
                                <div class="idt-badri-recent-member">
                                    <div class="idt-badri-recent-avatar">
                                        <?php if ( has_post_thumbnail() ) : ?>
                                            <?php the_post_thumbnail( array( 52, 52 ) ); ?>
                                        <?php else : ?>
                                            <span><?php echo esc_html( $this->get_initial( get_the_title() ) ); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <strong><?php the_title(); ?></strong>
                                        <p><?php echo esc_html( $mobile ? $mobile : esc_html__( 'মোবাইল নম্বর নেই', 'islami-dawa-tools' ) ); ?></p>
                                    </div>
                                    <span class="idt-badri-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( get_post_status_object( $status )->label ); ?></span>
                                    <a class="button button-small" href="<?php echo esc_url( get_edit_post_link( get_the_ID() ) ); ?>"><?php echo esc_html__( 'Edit', 'islami-dawa-tools' ); ?></a>
                                </div>
                            <?php endwhile; ?>
                        </div>
                        <?php wp_reset_postdata(); ?>
                    <?php else : ?>
                        <p class="idt-badri-empty-admin"><?php echo esc_html__( 'এখনো কোনো আবেদন জমা হয়নি।', 'islami-dawa-tools' ); ?></p>
                    <?php endif; ?>
                </div>

                <div class="idt-badri-dashboard-side">
                    <div class="idt-badri-dashboard-panel">
                        <div class="idt-badri-dashboard-panel-head">
                            <h2><span class="dashicons dashicons-controls-forward"></span><?php echo esc_html__( 'Quick Actions', 'islami-dawa-tools' ); ?></h2>
                        </div>
                        <div class="idt-badri-quick-actions">
                            <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-plus"></span><?php echo esc_html__( 'সদস্য যোগ করুন', 'islami-dawa-tools' ); ?></a>
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=pending&post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-clock"></span><?php echo esc_html__( 'Pending Review', 'islami-dawa-tools' ); ?><em><?php echo esc_html( number_format_i18n( $pending ) ); ?></em></a>
                            <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-admin-users"></span><?php echo esc_html__( 'সব সদস্য', 'islami-dawa-tools' ); ?></a>
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . self::SETTINGS_SLUG ) ); ?>"><span class="dashicons dashicons-admin-settings"></span><?php echo esc_html__( 'সেটিংস', 'islami-dawa-tools' ); ?></a>
                        </div>
                    </div>

                    <div class="idt-badri-dashboard-panel">
                        <div class="idt-badri-dashboard-panel-head">
                            <h2><span class="dashicons dashicons-shortcode"></span><?php echo esc_html__( 'Shortcodes', 'islami-dawa-tools' ); ?></h2>
                        </div>
                        <div class="idt-badri-shortcode-list">
                            <code>[badri_member_form]</code><span><?php echo esc_html__( 'সদস্য ফরম', 'islami-dawa-tools' ); ?></span>
                            <code>[badri_members_grid]</code><span><?php echo esc_html__( 'সদস্য তালিকা', 'islami-dawa-tools' ); ?></span>
                        </div>
                    </div>

                    <div class="idt-badri-dashboard-panel">
                        <div class="idt-badri-dashboard-panel-head">
                            <h2><span class="dashicons dashicons-admin-generic"></span><?php echo esc_html__( 'Active Settings', 'islami-dawa-tools' ); ?></h2>
                        </div>
                        <div class="idt-badri-active-settings">
                            <div><span><?php echo esc_html__( 'CAPTCHA', 'islami-dawa-tools' ); ?></span><strong><?php echo '1' === $settings['captcha_enabled'] ? esc_html__( 'Enabled', 'islami-dawa-tools' ) : esc_html__( 'Disabled', 'islami-dawa-tools' ); ?></strong></div>
                            <div><span><?php echo esc_html__( 'Extra Fields', 'islami-dawa-tools' ); ?></span><strong><?php echo esc_html( number_format_i18n( count( $this->get_additional_fields() ) ) ); ?></strong></div>
                            <div><span><?php echo esc_html__( 'Photo Max Size', 'islami-dawa-tools' ); ?></span><strong><?php echo esc_html( $settings['photo_max_size_mb'] ); ?>MB</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    private function get_dashboard_counts() {
        $counts    = wp_count_posts( self::POST_TYPE );
        $published = isset( $counts->publish ) ? absint( $counts->publish ) : 0;
        $pending   = isset( $counts->pending ) ? absint( $counts->pending ) : 0;
        $draft     = isset( $counts->draft ) ? absint( $counts->draft ) : 0;
        $total     = $published + $pending + $draft;

        $hidden_query = new \WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => array( 'publish', 'pending', 'draft' ),
                'posts_per_page' => 1,
                'meta_key'       => '_badri_public_visibility',
                'meta_value'     => 'hide',
                'fields'         => 'ids',
                'no_found_rows'  => false,
            )
        );
        $hidden_count = absint( $hidden_query->found_posts );
        wp_reset_postdata();

        return array(
            'total'     => $total,
            'published' => $published,
            'pending'   => $pending,
            'draft'     => $draft,
            'hidden'    => $hidden_count,
        );
    }

    private function render_dashboard_panel_content() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $counts   = $this->get_dashboard_counts();
        $settings = $this->get_settings();
        $recent   = new \WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => array( 'publish', 'pending', 'draft' ),
                'posts_per_page' => 6,
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );
        ?>
        <div class="idt-badri-panel-heading idt-badri-dashboard-heading">
            <span><?php echo esc_html__( 'Overview', 'islami-dawa-tools' ); ?></span>
            <h2><?php echo esc_html__( 'বদরী সদস্য ড্যাশবোর্ড', 'islami-dawa-tools' ); ?></h2>
            <p><?php echo esc_html__( 'সদস্য আবেদন, অনুমোদন, শর্টকোড এবং সক্রিয় সেটিংস এক জায়গা থেকে দেখুন।', 'islami-dawa-tools' ); ?></p>
        </div>

        <div class="idt-badri-dashboard-stats is-inside-settings">
            <?php
            $stats = array(
                array( 'label' => esc_html__( 'মোট সদস্য', 'islami-dawa-tools' ), 'value' => $counts['total'], 'icon' => 'dashicons-groups', 'accent' => 'green', 'url' => admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ),
                array( 'label' => esc_html__( 'প্রকাশিত', 'islami-dawa-tools' ), 'value' => $counts['published'], 'icon' => 'dashicons-yes-alt', 'accent' => 'blue', 'url' => admin_url( 'edit.php?post_status=publish&post_type=' . self::POST_TYPE ) ),
                array( 'label' => esc_html__( 'রিভিউ অপেক্ষমাণ', 'islami-dawa-tools' ), 'value' => $counts['pending'], 'icon' => 'dashicons-clock', 'accent' => 'orange', 'url' => admin_url( 'edit.php?post_status=pending&post_type=' . self::POST_TYPE ) ),
                array( 'label' => esc_html__( 'গোপন সদস্য', 'islami-dawa-tools' ), 'value' => $counts['hidden'], 'icon' => 'dashicons-hidden', 'accent' => 'gold', 'url' => admin_url( 'edit.php?post_type=' . self::POST_TYPE . '&badri_filter_visibility=hide' ) ),
            );
            foreach ( $stats as $stat ) :
                ?>
                <a class="idt-badri-stat-card is-<?php echo esc_attr( $stat['accent'] ); ?>" href="<?php echo esc_url( $stat['url'] ); ?>">
                    <span class="dashicons <?php echo esc_attr( $stat['icon'] ); ?>"></span>
                    <strong><?php echo esc_html( number_format_i18n( $stat['value'] ) ); ?></strong>
                    <p><?php echo esc_html( $stat['label'] ); ?></p>
                </a>
            <?php endforeach; ?>
        </div>

        <div class="idt-badri-dashboard-grid is-inside-settings">
            <div class="idt-badri-dashboard-panel idt-badri-dashboard-panel-large">
                <div class="idt-badri-dashboard-panel-head">
                    <h2><span class="dashicons dashicons-list-view"></span><?php echo esc_html__( 'সাম্প্রতিক আবেদন', 'islami-dawa-tools' ); ?></h2>
                    <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><?php echo esc_html__( 'সব দেখুন', 'islami-dawa-tools' ); ?></a>
                </div>
                <?php if ( $recent->have_posts() ) : ?>
                    <div class="idt-badri-recent-members">
                        <?php while ( $recent->have_posts() ) : $recent->the_post(); ?>
                            <?php
                            $status = get_post_status();
                            $mobile = get_post_meta( get_the_ID(), '_badri_mobile', true );
                            $status_obj = get_post_status_object( $status );
                            ?>
                            <div class="idt-badri-recent-member">
                                <div class="idt-badri-recent-avatar">
                                    <?php if ( has_post_thumbnail() ) : ?>
                                        <?php the_post_thumbnail( array( 52, 52 ) ); ?>
                                    <?php else : ?>
                                        <span><?php echo esc_html( $this->get_initial( get_the_title() ) ); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <strong><?php the_title(); ?></strong>
                                    <p><?php echo esc_html( $mobile ? $mobile : esc_html__( 'মোবাইল নম্বর নেই', 'islami-dawa-tools' ) ); ?></p>
                                </div>
                                <span class="idt-badri-status is-<?php echo esc_attr( $status ); ?>"><?php echo esc_html( $status_obj ? $status_obj->label : $status ); ?></span>
                                <a class="button button-small" href="<?php echo esc_url( get_edit_post_link( get_the_ID() ) ); ?>"><?php echo esc_html__( 'Edit', 'islami-dawa-tools' ); ?></a>
                            </div>
                        <?php endwhile; ?>
                    </div>
                    <?php wp_reset_postdata(); ?>
                <?php else : ?>
                    <p class="idt-badri-empty-admin"><?php echo esc_html__( 'এখনো কোনো আবেদন জমা হয়নি।', 'islami-dawa-tools' ); ?></p>
                <?php endif; ?>
            </div>

            <div class="idt-badri-dashboard-side">
                <div class="idt-badri-dashboard-panel">
                    <div class="idt-badri-dashboard-panel-head">
                        <h2><span class="dashicons dashicons-controls-forward"></span><?php echo esc_html__( 'Quick Actions', 'islami-dawa-tools' ); ?></h2>
                    </div>
                    <div class="idt-badri-quick-actions">
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-plus"></span><?php echo esc_html__( 'সদস্য যোগ করুন', 'islami-dawa-tools' ); ?></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_status=pending&post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-clock"></span><?php echo esc_html__( 'Pending Review', 'islami-dawa-tools' ); ?><em><?php echo esc_html( number_format_i18n( $counts['pending'] ) ); ?></em></a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=' . self::POST_TYPE ) ); ?>"><span class="dashicons dashicons-admin-users"></span><?php echo esc_html__( 'সব সদস্য', 'islami-dawa-tools' ); ?></a>
                        <a href="#" data-badri-go-tab="builder"><span class="dashicons dashicons-admin-customizer"></span><?php echo esc_html__( 'ফরম বিল্ডার', 'islami-dawa-tools' ); ?></a>
                    </div>
                </div>

                <div class="idt-badri-dashboard-panel">
                    <div class="idt-badri-dashboard-panel-head">
                        <h2><span class="dashicons dashicons-shortcode"></span><?php echo esc_html__( 'Shortcodes', 'islami-dawa-tools' ); ?></h2>
                    </div>
                    <div class="idt-badri-shortcode-list">
                        <code>[badri_member_form]</code><span><?php echo esc_html__( 'সদস্য ফরম', 'islami-dawa-tools' ); ?></span>
                        <code>[badri_members_grid]</code><span><?php echo esc_html__( 'সদস্য তালিকা', 'islami-dawa-tools' ); ?></span>
                    </div>
                </div>

                <div class="idt-badri-dashboard-panel">
                    <div class="idt-badri-dashboard-panel-head">
                        <h2><span class="dashicons dashicons-admin-generic"></span><?php echo esc_html__( 'Active Settings', 'islami-dawa-tools' ); ?></h2>
                    </div>
                    <div class="idt-badri-active-settings">
                        <div><span><?php echo esc_html__( 'CAPTCHA', 'islami-dawa-tools' ); ?></span><strong><?php echo '1' === $settings['captcha_enabled'] ? esc_html__( 'Enabled', 'islami-dawa-tools' ) : esc_html__( 'Disabled', 'islami-dawa-tools' ); ?></strong></div>
                        <div><span><?php echo esc_html__( 'Extra Fields', 'islami-dawa-tools' ); ?></span><strong><?php echo esc_html( number_format_i18n( count( $this->get_additional_fields() ) ) ); ?></strong></div>
                        <div><span><?php echo esc_html__( 'Photo Max Size', 'islami-dawa-tools' ); ?></span><strong><?php echo esc_html( $settings['photo_max_size_mb'] ); ?>MB</strong></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $settings = $this->get_settings();
        ?>
        <div class="wrap idt-badri-settings-wrap">
            <div class="idt-badri-hero">
                <div class="idt-badri-hero-icon"><span class="dashicons dashicons-groups"></span></div>
                <div>
                    <h1><?php echo esc_html__( 'বদরী সদস্য সেটিংস', 'islami-dawa-tools' ); ?></h1>
                    <p><?php echo esc_html__( 'ড্যাশবোর্ড, ফরম, মেসেজ, গ্রিড, CAPTCHA, ইমেইল এবং অতিরিক্ত ফিল্ডগুলো এক জায়গা থেকে ম্যানেজ করুন।', 'islami-dawa-tools' ); ?></p>
                </div>
            </div>

            <form method="post" action="options.php" class="idt-badri-settings-form">
                <?php settings_fields( self::SETTINGS_GROUP ); ?>

                <div class="idt-badri-settings-shell" data-badri-tabs>
                    <nav class="idt-badri-settings-tabs" aria-label="<?php echo esc_attr__( 'Badri settings tabs', 'islami-dawa-tools' ); ?>">
                        <button type="button" class="is-active" data-badri-tab="dashboard"><span class="dashicons dashicons-dashboard"></span><?php echo esc_html__( 'ড্যাশবোর্ড', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="form"><span class="dashicons dashicons-feedback"></span><?php echo esc_html__( 'ফরম', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="messages"><span class="dashicons dashicons-format-chat"></span><?php echo esc_html__( 'মেসেজ', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="grid"><span class="dashicons dashicons-screenoptions"></span><?php echo esc_html__( 'গ্রিড', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="email"><span class="dashicons dashicons-email-alt"></span><?php echo esc_html__( 'ইমেইল', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="captcha"><span class="dashicons dashicons-shield"></span><?php echo esc_html__( 'CAPTCHA', 'islami-dawa-tools' ); ?></button>
                        <button type="button" data-badri-tab="builder"><span class="dashicons dashicons-admin-customizer"></span><?php echo esc_html__( 'ফরম বিল্ডার', 'islami-dawa-tools' ); ?></button>
                    </nav>

                    <div class="idt-badri-settings-panels">
                        <section class="idt-badri-settings-panel is-active" data-badri-panel="dashboard">
                            <?php $this->render_dashboard_panel_content(); ?>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="form" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'General Form', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'ফরম সেটিংস', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'ফ্রন্টএন্ড ফরমের টাইটেল, বিবরণ, বাটন এবং ছবির আপলোড সাইজ নির্ধারণ করুন।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-grid compact">
                                <div class="idt-badri-admin-card">
                                    <?php $this->render_settings_input( 'form_title', esc_html__( 'ফরম টাইটেল', 'islami-dawa-tools' ), $settings['form_title'] ); ?>
                                    <?php $this->render_settings_textarea( 'form_description', esc_html__( 'ফরম বিবরণ', 'islami-dawa-tools' ), $settings['form_description'] ); ?>
                                </div>
                                <div class="idt-badri-admin-card">
                                    <?php $this->render_settings_input( 'submit_button_text', esc_html__( 'সাবমিট বাটন টেক্সট', 'islami-dawa-tools' ), $settings['submit_button_text'] ); ?>
                                    <?php $this->render_settings_input( 'photo_max_size_mb', esc_html__( 'ছবির সর্বোচ্চ সাইজ (MB)', 'islami-dawa-tools' ), $settings['photo_max_size_mb'], 'number' ); ?>
                                    <?php $this->render_settings_input( 'extra_section_kicker', esc_html__( 'অতিরিক্ত ফিল্ড সেকশন ছোট টাইটেল', 'islami-dawa-tools' ), $settings['extra_section_kicker'] ); ?>
                                    <?php $this->render_settings_input( 'extra_section_title', esc_html__( 'অতিরিক্ত ফিল্ড সেকশন টাইটেল', 'islami-dawa-tools' ), $settings['extra_section_title'] ); ?>
                                </div>
                            </div>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="messages" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'SweetAlert2 Messages', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'মেসেজ সেটিংস', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'সাবমিশন, এরর ও ভ্যালিডেশন পপআপের মেসেজ কাস্টমাইজ করুন।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-grid compact">
                                <div class="idt-badri-admin-card">
                                    <?php $this->render_settings_input( 'success_title', esc_html__( 'সাকসেস পপআপ টাইটেল', 'islami-dawa-tools' ), $settings['success_title'] ); ?>
                                    <?php $this->render_settings_textarea( 'success_message', esc_html__( 'সাকসেস মেসেজ', 'islami-dawa-tools' ), $settings['success_message'] ); ?>
                                    <?php $this->render_settings_textarea( 'error_message', esc_html__( 'এরর মেসেজ', 'islami-dawa-tools' ), $settings['error_message'] ); ?>
                                </div>
                                <div class="idt-badri-admin-card">
                                    <?php $this->render_settings_textarea( 'captcha_error_message', esc_html__( 'CAPTCHA এরর মেসেজ', 'islami-dawa-tools' ), $settings['captcha_error_message'] ); ?>
                                    <?php $this->render_settings_input( 'processing_message', esc_html__( 'প্রসেসিং মেসেজ', 'islami-dawa-tools' ), $settings['processing_message'] ); ?>
                                    <?php $this->render_settings_input( 'validation_title', esc_html__( 'ভ্যালিডেশন পপআপ টাইটেল', 'islami-dawa-tools' ), $settings['validation_title'] ); ?>
                                </div>
                                <div class="idt-badri-admin-card idt-badri-admin-card-wide">
                                    <div class="idt-badri-inline-fields">
                                        <?php $this->render_settings_input( 'required_field_message', esc_html__( 'Required ফিল্ড মেসেজ', 'islami-dawa-tools' ), $settings['required_field_message'] ); ?>
                                        <?php $this->render_settings_input( 'custom_amount_error_message', esc_html__( 'কাস্টম এমাউন্ট এরর মেসেজ', 'islami-dawa-tools' ), $settings['custom_amount_error_message'] ); ?>
                                        <?php $this->render_settings_input( 'photo_type_error_message', esc_html__( 'ছবির টাইপ এরর মেসেজ', 'islami-dawa-tools' ), $settings['photo_type_error_message'] ); ?>
                                        <?php $this->render_settings_input( 'photo_size_error_message', esc_html__( 'ছবির সাইজ এরর মেসেজ', 'islami-dawa-tools' ), $settings['photo_size_error_message'] ); ?>
                                    </div>
                                    <p class="idt-badri-hint"><?php echo esc_html__( 'Required মেসেজে {field} এবং ছবির সাইজ মেসেজে {size} ব্যবহার করা যাবে।', 'islami-dawa-tools' ); ?></p>
                                </div>
                            </div>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="grid" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'Public Listing', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'গ্রিড সেটিংস', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'প্রকাশিত বদরী সদস্য তালিকার হেডিং ও খালি মেসেজ কাস্টমাইজ করুন।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-card">
                                <?php $this->render_settings_input( 'grid_title', esc_html__( 'গ্রিড টাইটেল', 'islami-dawa-tools' ), $settings['grid_title'] ); ?>
                                <?php $this->render_settings_textarea( 'grid_description', esc_html__( 'গ্রিড বিবরণ', 'islami-dawa-tools' ), $settings['grid_description'] ); ?>
                                <?php $this->render_settings_input( 'empty_message', esc_html__( 'খালি তালিকা মেসেজ', 'islami-dawa-tools' ), $settings['empty_message'] ); ?>
                            </div>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="email" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'Admin Notification', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'অ্যাডমিন নোটিফিকেশন', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'নতুন আবেদন জমা হলে কোন ইমেইলে নোটিফিকেশন যাবে তা ঠিক করুন।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-card">
                                <?php $this->render_settings_input( 'admin_notification_email', esc_html__( 'নোটিফিকেশন ইমেইল', 'islami-dawa-tools' ), $settings['admin_notification_email'], 'email' ); ?>
                                <?php $this->render_settings_input( 'admin_email_subject', esc_html__( 'ইমেইল সাবজেক্ট', 'islami-dawa-tools' ), $settings['admin_email_subject'] ); ?>
                                <?php $this->render_settings_textarea( 'admin_email_body', esc_html__( 'ইমেইল বডি', 'islami-dawa-tools' ), $settings['admin_email_body'] ); ?>
                                <p class="idt-badri-hint"><?php echo esc_html__( 'ইমেইল সাবজেক্টে {name} ব্যবহার করলে সদস্যের নাম বসবে।', 'islami-dawa-tools' ); ?></p>
                            </div>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="captcha" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'Bot Protection', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'র‍্যান্ডম CAPTCHA সেটিংস', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'প্রতিবার ফরম লোড হলে নতুন অংক দেখাবে, তাই বট একই উত্তর দিয়ে বারবার সাবমিট করতে পারবে না।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-card">
                                <div class="idt-badri-toggle-field">
                                    <label>
                                        <input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME . '[captcha_enabled]' ); ?>" value="1" <?php checked( $settings['captcha_enabled'], '1' ); ?> />
                                        <span><?php echo esc_html__( 'CAPTCHA চালু রাখুন', 'islami-dawa-tools' ); ?></span>
                                    </label>
                                </div>
                                <div class="idt-badri-inline-fields">
                                    <?php $this->render_settings_input( 'captcha_min_number', esc_html__( 'সর্বনিম্ন সংখ্যা', 'islami-dawa-tools' ), $settings['captcha_min_number'], 'number' ); ?>
                                    <?php $this->render_settings_input( 'captcha_max_number', esc_html__( 'সর্বোচ্চ সংখ্যা', 'islami-dawa-tools' ), $settings['captcha_max_number'], 'number' ); ?>
                                </div>
                                <?php $this->render_settings_select( 'captcha_operator', esc_html__( 'অপারেশন', 'islami-dawa-tools' ), $settings['captcha_operator'], array( 'add' => esc_html__( 'শুধু যোগ', 'islami-dawa-tools' ), 'subtract' => esc_html__( 'শুধু বিয়োগ', 'islami-dawa-tools' ), 'mixed' => esc_html__( 'মিক্সড', 'islami-dawa-tools' ) ) ); ?>
                                <?php $this->render_settings_input( 'captcha_label_prefix', esc_html__( 'CAPTCHA লেবেল', 'islami-dawa-tools' ), $settings['captcha_label_prefix'] ); ?>
                                <p class="idt-badri-hint"><?php echo esc_html__( 'উদাহরণ: CAPTCHA: ৬ + ৩ = ? অথবা CAPTCHA: ৮ - ২ = ?', 'islami-dawa-tools' ); ?></p>
                            </div>
                        </section>

                        <section class="idt-badri-settings-panel" data-badri-panel="builder" hidden>
                            <div class="idt-badri-panel-heading">
                                <span><?php echo esc_html__( 'Extra Fields', 'islami-dawa-tools' ); ?></span>
                                <h2><?php echo esc_html__( 'ফরম বিল্ডার: অতিরিক্ত ফিল্ড', 'islami-dawa-tools' ); ?></h2>
                                <p><?php echo esc_html__( 'প্রয়োজন অনুযায়ী অতিরিক্ত ফিল্ড যোগ করুন। এগুলো ফ্রন্টএন্ড ফরমে, সদস্য এডিট স্ক্রিনে এবং চাইলে গ্রিডে দেখানো যাবে।', 'islami-dawa-tools' ); ?></p>
                            </div>
                            <div class="idt-badri-admin-card idt-badri-admin-card-wide">
                                <?php $this->render_additional_fields_builder( $settings ); ?>
                            </div>
                        </section>
                    </div>
                </div>

                <div class="idt-badri-sticky-save">
                    <button type="submit" class="button button-primary idt-badri-save-btn"><?php echo esc_html__( 'সেটিংস সংরক্ষণ করুন', 'islami-dawa-tools' ); ?></button>
                </div>
            </form>
        </div>
        <?php
    }

    private function render_additional_fields_builder( $settings ) {
        $fields = isset( $settings['additional_fields'] ) && is_array( $settings['additional_fields'] ) ? $settings['additional_fields'] : array();
        ?>
        <div class="idt-badri-field-builder" data-badri-field-builder>
            <div class="idt-badri-field-builder-list" data-badri-field-builder-list>
                <?php foreach ( $fields as $index => $field ) : ?>
                    <?php $this->render_field_builder_row( $index, $field ); ?>
                <?php endforeach; ?>
            </div>

            <button type="button" class="button idt-badri-add-field" data-badri-add-field><?php echo esc_html__( 'নতুন ফিল্ড যোগ করুন', 'islami-dawa-tools' ); ?></button>

            <script type="text/html" id="tmpl-idt-badri-field-row">
                <?php
                $this->render_field_builder_row(
                    '__INDEX__',
                    array(
                        'key'          => '',
                        'label'        => '',
                        'type'         => 'text',
                        'placeholder'  => '',
                        'options'      => '',
                        'required'     => '0',
                        'show_in_grid' => '0',
                    )
                );
                ?>
            </script>
        </div>
        <?php
    }

    private function render_field_builder_row( $index, $field ) {
        $field = wp_parse_args(
            $field,
            array(
                'key'          => '',
                'label'        => '',
                'type'         => 'text',
                'placeholder'  => '',
                'options'      => '',
                'required'     => '0',
                'show_in_grid' => '0',
            )
        );
        $name = self::OPTION_NAME . '[additional_fields][' . $index . ']';
        ?>
        <div class="idt-badri-field-builder-row" data-badri-field-row>
            <div class="idt-badri-field-builder-row-head">
                <strong><?php echo esc_html__( 'কাস্টম ফিল্ড', 'islami-dawa-tools' ); ?></strong>
                <button type="button" class="button idt-badri-remove-field" data-badri-remove-field><?php echo esc_html__( 'মুছে ফেলুন', 'islami-dawa-tools' ); ?></button>
            </div>
            <div class="idt-badri-field-builder-grid">
                <label>
                    <span><?php echo esc_html__( 'ফিল্ড লেবেল', 'islami-dawa-tools' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $name . '[label]' ); ?>" value="<?php echo esc_attr( $field['label'] ); ?>" placeholder="<?php echo esc_attr__( 'যেমন: জন্ম তারিখ', 'islami-dawa-tools' ); ?>" />
                </label>
                <label>
                    <span><?php echo esc_html__( 'ফিল্ড কী', 'islami-dawa-tools' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $name . '[key]' ); ?>" value="<?php echo esc_attr( $field['key'] ); ?>" placeholder="<?php echo esc_attr__( 'যেমন: date_of_birth', 'islami-dawa-tools' ); ?>" />
                </label>
                <label>
                    <span><?php echo esc_html__( 'ফিল্ড টাইপ', 'islami-dawa-tools' ); ?></span>
                    <select name="<?php echo esc_attr( $name . '[type]' ); ?>" data-badri-builder-type>
                        <?php foreach ( array( 'text' => 'Text', 'number' => 'Number', 'email' => 'Email', 'textarea' => 'Textarea', 'select' => 'Select', 'date' => 'Date' ) as $type_value => $type_label ) : ?>
                            <option value="<?php echo esc_attr( $type_value ); ?>" <?php selected( $field['type'], $type_value ); ?>><?php echo esc_html( $type_label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span><?php echo esc_html__( 'Placeholder', 'islami-dawa-tools' ); ?></span>
                    <input type="text" name="<?php echo esc_attr( $name . '[placeholder]' ); ?>" value="<?php echo esc_attr( $field['placeholder'] ); ?>" />
                </label>
                <label class="idt-badri-builder-options">
                    <span><?php echo esc_html__( 'Select options', 'islami-dawa-tools' ); ?></span>
                    <textarea name="<?php echo esc_attr( $name . '[options]' ); ?>" rows="3" placeholder="<?php echo esc_attr__( 'প্রতি লাইনে একটি অপশন লিখুন', 'islami-dawa-tools' ); ?>"><?php echo esc_textarea( $field['options'] ); ?></textarea>
                </label>
                <div class="idt-badri-builder-checks">
                    <label><input type="checkbox" name="<?php echo esc_attr( $name . '[required]' ); ?>" value="1" <?php checked( $field['required'], '1' ); ?> /> <?php echo esc_html__( 'Required', 'islami-dawa-tools' ); ?></label>
                    <label><input type="checkbox" name="<?php echo esc_attr( $name . '[show_in_grid]' ); ?>" value="1" <?php checked( $field['show_in_grid'], '1' ); ?> /> <?php echo esc_html__( 'গ্রিডে দেখান', 'islami-dawa-tools' ); ?></label>
                </div>
            </div>
        </div>
        <?php
    }

    private function render_settings_input( $key, $label, $value, $type = 'text' ) {
        ?>
        <div class="idt-badri-admin-field">
            <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <input id="<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" />
        </div>
        <?php
    }

    private function render_settings_textarea( $key, $label, $value ) {
        ?>
        <div class="idt-badri-admin-field">
            <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <textarea id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>" rows="4"><?php echo esc_textarea( $value ); ?></textarea>
        </div>
        <?php
    }

    private function render_settings_select( $key, $label, $value, $options ) {
        ?>
        <div class="idt-badri-admin-field">
            <label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <select id="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( self::OPTION_NAME . '[' . $key . ']' ); ?>">
                <?php foreach ( $options as $option_value => $option_label ) : ?>
                    <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    public function get_amount_options() {
        return apply_filters(
            'islami_dawa_badri_member_amount_options',
            array(
                '500'   => esc_html__( '৳৫০০', 'islami-dawa-tools' ),
                '1000'  => esc_html__( '৳১,০০০', 'islami-dawa-tools' ),
                '2000'  => esc_html__( '৳২,০০০', 'islami-dawa-tools' ),
                '5000'  => esc_html__( '৳৫,০০০', 'islami-dawa-tools' ),
                '10000' => esc_html__( '৳১০,০০০', 'islami-dawa-tools' ),
                'other' => esc_html__( 'অন্যান্য', 'islami-dawa-tools' ),
            )
        );
    }

    public function render_form_shortcode() {
        $settings = $this->get_settings();
        ob_start();

        if ( isset( $_GET['badri_member_submitted'] ) && 'success' === sanitize_text_field( wp_unslash( $_GET['badri_member_submitted'] ) ) ) {
            echo '<div class="at-badri-alert at-badri-alert-success">' . esc_html( $settings['success_message'] ) . '</div>';
        }

        if ( isset( $_GET['badri_member_submitted'] ) && 'error' === sanitize_text_field( wp_unslash( $_GET['badri_member_submitted'] ) ) ) {
            echo '<div class="at-badri-alert at-badri-alert-error">' . esc_html( $settings['error_message'] ) . '</div>';
        }
        ?>
        <form class="at-badri-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-badri-ajax="1" novalidate>
            <input type="hidden" name="action" value="islami_dawa_badri_member_submit" />
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

            <div class="at-badri-form-header">
                <span><?php echo esc_html__( 'Badri Membership', 'islami-dawa-tools' ); ?></span>
                <h2><?php echo esc_html( $settings['form_title'] ); ?></h2>
                <p><?php echo esc_html( $settings['form_description'] ); ?></p>
            </div>

            <div class="at-badri-grid at-badri-grid-2">
                <?php $this->render_front_text_field( 'member_name', esc_html__( 'সদস্য/সদস্যার নাম', 'islami-dawa-tools' ), true, esc_html__( 'সদস্য/সদস্যার নাম', 'islami-dawa-tools' ) ); ?>
                <?php $this->render_front_text_field( 'guardian_name', esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ), true, esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ) ); ?>
                <?php $this->render_front_text_field( 'mobile', esc_html__( 'মোবাইল নং', 'islami-dawa-tools' ), true, esc_html__( 'মোবাইল নং', 'islami-dawa-tools' ), 'tel' ); ?>
                <?php $this->render_front_text_field( 'profession', esc_html__( 'পেশা', 'islami-dawa-tools' ), true, esc_html__( 'পেশা', 'islami-dawa-tools' ) ); ?>
            </div>

            <div class="at-badri-grid at-badri-grid-3">
                <div class="at-badri-field">
                    <label><?php echo esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                    <div class="at-badri-radio-list at-badri-option-list">
                        <label><input type="radio" name="donation_frequency" value="yearly" required data-badri-label="<?php echo esc_attr__( 'অনুদান ধরন', 'islami-dawa-tools' ); ?>" /> <?php echo esc_html__( 'বার্ষিক', 'islami-dawa-tools' ); ?></label>
                        <label><input type="radio" name="donation_frequency" value="monthly" required data-badri-label="<?php echo esc_attr__( 'অনুদান ধরন', 'islami-dawa-tools' ); ?>" /> <?php echo esc_html__( 'মাসিক', 'islami-dawa-tools' ); ?></label>
                    </div>
                </div>

                <div class="at-badri-field at-badri-amount-field">
                    <label for="badri_donation_amount"><?php echo esc_html__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                    <select id="badri_donation_amount" name="donation_amount" required data-badri-label="<?php echo esc_attr__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ); ?>" data-badri-amount-select>
                        <option value=""><?php echo esc_html__( 'নির্বাচন করুন', 'islami-dawa-tools' ); ?></option>
                        <?php foreach ( $this->get_amount_options() as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="at-badri-custom-amount" data-badri-custom-amount-wrap hidden>
                        <label for="badri_donation_custom_amount"><?php echo esc_html__( 'কাস্টম অনুদানের পরিমাণ', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                        <input id="badri_donation_custom_amount" type="number" name="donation_custom_amount" min="1" step="1" placeholder="<?php echo esc_attr__( 'যেমন: ১৫০০', 'islami-dawa-tools' ); ?>" data-badri-custom-amount data-badri-label="<?php echo esc_attr__( 'কাস্টম অনুদানের পরিমাণ', 'islami-dawa-tools' ); ?>" />
                    </div>
                </div>

                <?php $this->render_front_text_field( 'donation_amount_text', esc_html__( 'অনুদানের পরিমাণ: কথায়', 'islami-dawa-tools' ), true, esc_html__( 'অনুদানের পরিমাণ কথায় লিখুন', 'islami-dawa-tools' ) ); ?>
            </div>

            <div class="at-badri-grid at-badri-grid-2">
                <div class="at-badri-field at-badri-field-wide">
                    <?php $this->render_front_textarea_field( 'permanent_address', esc_html__( 'স্থায়ী ঠিকানা', 'islami-dawa-tools' ), true, esc_html__( 'স্থায়ী ঠিকানা', 'islami-dawa-tools' ) ); ?>
                </div>
                <?php $this->render_front_text_field( 'permanent_district', esc_html__( 'স্থায়ী জেলার নাম', 'islami-dawa-tools' ), false, esc_html__( 'জেলা', 'islami-dawa-tools' ) ); ?>

                <div class="at-badri-field at-badri-field-wide">
                    <?php $this->render_front_textarea_field( 'current_address', esc_html__( 'বর্তমান ঠিকানা', 'islami-dawa-tools' ), true, esc_html__( 'বর্তমান ঠিকানা', 'islami-dawa-tools' ) ); ?>
                </div>
                <?php $this->render_front_text_field( 'current_district', esc_html__( 'বর্তমান জেলার নাম', 'islami-dawa-tools' ), false, esc_html__( 'জেলা', 'islami-dawa-tools' ) ); ?>
            </div>

            <div class="at-badri-grid at-badri-grid-2">
                <div class="at-badri-field at-badri-photo-field">
                    <label for="badri_member_photo"><?php echo esc_html__( 'সদস্যের ছবি', 'islami-dawa-tools' ); ?></label>
                    <input id="badri_member_photo" type="file" name="member_photo" accept="image/jpeg,image/png,image/webp" data-badri-label="<?php echo esc_attr__( 'ছবি', 'islami-dawa-tools' ); ?>" data-badri-max-size-mb="<?php echo esc_attr( $settings['photo_max_size_mb'] ); ?>" />
                    <p><?php printf( esc_html__( 'JPG, PNG বা WEBP আপলোড করুন। সর্বোচ্চ %sMB।', 'islami-dawa-tools' ), esc_html( $settings['photo_max_size_mb'] ) ); ?></p>
                </div>

                <div class="at-badri-field">
                    <label><?php echo esc_html__( 'ছবি প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?></label>
                    <div class="at-badri-radio-list at-badri-option-list">
                        <label><input type="radio" name="photo_visibility" value="show" /> <?php echo esc_html__( 'আমার ছবি প্রকাশ করা যাবে', 'islami-dawa-tools' ); ?></label>
                        <label><input type="radio" name="photo_visibility" value="hide" checked /> <?php echo esc_html__( 'ছবি প্রকাশ না করুন', 'islami-dawa-tools' ); ?></label>
                    </div>
                </div>
            </div>

            <?php $additional_fields = $this->get_additional_fields(); ?>
            <?php if ( ! empty( $additional_fields ) ) : ?>
                <div class="at-badri-form-section">
                    <div class="at-badri-section-title">
                        <span><?php echo esc_html( $settings['extra_section_kicker'] ); ?></span>
                        <h3><?php echo esc_html( $settings['extra_section_title'] ); ?></h3>
                    </div>
                    <div class="at-badri-grid at-badri-grid-2">
                        <?php foreach ( $additional_fields as $extra_field ) : ?>
                            <?php $this->render_front_extra_field( $extra_field ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="at-badri-field at-badri-privacy-field">
                <label><?php echo esc_html__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                <div class="at-badri-radio-list at-badri-option-list">
                    <label><input type="radio" name="public_visibility" value="show" required data-badri-label="<?php echo esc_attr__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?>" /> <?php echo esc_html__( 'আমার তথ্য প্রকাশ করা যাবে', 'islami-dawa-tools' ); ?></label>
                    <label><input type="radio" name="public_visibility" value="hide" required data-badri-label="<?php echo esc_attr__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?>" /> <?php echo esc_html__( 'আমাকে পাবলিক তালিকায় গোপন রাখুন', 'islami-dawa-tools' ); ?></label>
                </div>
                <p><?php echo esc_html__( 'গোপন রাখলে তালিকায় শুধু আপনার নাম দেখা যাবে; ছবি ও বাকি তথ্য xxx হিসেবে দেখানো হবে।', 'islami-dawa-tools' ); ?></p>
            </div>

            <?php $captcha_data = $this->create_captcha_challenge( $settings ); ?>
            <?php if ( $captcha_data ) : ?>
                <div class="at-badri-field at-badri-captcha-field">
                    <label for="badri_captcha"><?php echo esc_html( $captcha_data['label'] ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                    <input type="hidden" name="badri_captcha_token" value="<?php echo esc_attr( $captcha_data['token'] ); ?>" />
                    <input id="badri_captcha" type="number" name="badri_captcha" required data-badri-label="<?php echo esc_attr__( 'CAPTCHA', 'islami-dawa-tools' ); ?>" autocomplete="off" />
                </div>
            <?php endif; ?>

            <button type="submit" class="at-badri-submit"><span><?php echo esc_html( $settings['submit_button_text'] ); ?></span></button>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_front_text_field( $name, $label, $required = false, $placeholder = '', $type = 'text' ) {
        ?>
        <div class="at-badri-field">
            <label for="badri_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
            <input id="badri_<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-badri-label="<?php echo esc_attr( $label ); ?>" <?php echo $required ? 'required' : ''; ?> />
        </div>
        <?php
    }

    private function render_front_textarea_field( $name, $label, $required = false, $placeholder = '' ) {
        ?>
        <label for="badri_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
        <textarea id="badri_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-badri-label="<?php echo esc_attr( $label ); ?>" <?php echo $required ? 'required' : ''; ?>></textarea>
        <?php
    }

    private function render_front_extra_field( $field ) {
        $key         = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
        $label       = isset( $field['label'] ) ? $field['label'] : '';
        $type        = isset( $field['type'] ) ? $field['type'] : 'text';
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
        $required    = ! empty( $field['required'] ) && '1' === $field['required'];

        if ( '' === $key || '' === $label ) {
            return;
        }

        $name = 'badri_extra_' . $key;
        ?>
        <div class="at-badri-field <?php echo 'textarea' === $type ? 'at-badri-field-wide' : ''; ?>">
            <label for="badri_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
            <?php if ( 'textarea' === $type ) : ?>
                <textarea id="badri_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-badri-label="<?php echo esc_attr( $label ); ?>" <?php echo $required ? 'required' : ''; ?>></textarea>
            <?php elseif ( 'select' === $type ) : ?>
                <select id="badri_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" data-badri-label="<?php echo esc_attr( $label ); ?>" <?php echo $required ? 'required' : ''; ?>>
                    <option value=""><?php echo esc_html__( 'নির্বাচন করুন', 'islami-dawa-tools' ); ?></option>
                    <?php foreach ( $this->parse_options( isset( $field['options'] ) ? $field['options'] : '' ) as $option ) : ?>
                        <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input id="badri_<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( in_array( $type, array( 'text', 'number', 'email', 'date' ), true ) ? $type : 'text' ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" data-badri-label="<?php echo esc_attr( $label ); ?>" <?php echo $required ? 'required' : ''; ?> />
            <?php endif; ?>
        </div>
        <?php
    }

    private function parse_options( $options ) {
        $lines = preg_split( '/\r\n|\r|\n/', (string) $options );
        $clean = array();
        foreach ( $lines as $line ) {
            $line = trim( $line );
            if ( '' !== $line ) {
                $clean[] = $line;
            }
        }
        return $clean;
    }

    public function handle_form_submission() {
        $result = $this->process_submission();

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_status( 'error' );
        }

        $this->redirect_with_status( 'success' );
    }

    public function handle_ajax_submission() {
        $result   = $this->process_submission();
        $settings = $this->get_settings();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message() ? $result->get_error_message() : $settings['error_message'],
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => $settings['success_message'],
                'post_id' => absint( $result ),
            )
        );
    }

    private function create_captcha_challenge( $settings ) {
        if ( empty( $settings['captcha_enabled'] ) || '1' !== $settings['captcha_enabled'] ) {
            return false;
        }

        $min = max( 1, absint( $settings['captcha_min_number'] ) );
        $max = max( $min, absint( $settings['captcha_max_number'] ) );

        $a = wp_rand( $min, $max );
        $b = wp_rand( $min, $max );
        $operator = isset( $settings['captcha_operator'] ) ? $settings['captcha_operator'] : 'mixed';

        if ( 'mixed' === $operator ) {
            $operator = wp_rand( 0, 1 ) ? 'add' : 'subtract';
        }

        if ( 'subtract' === $operator ) {
            if ( $b > $a ) {
                $tmp = $a;
                $a = $b;
                $b = $tmp;
            }
            $answer = $a - $b;
            $symbol = '-';
        } else {
            $answer = $a + $b;
            $symbol = '+';
        }

        $token = wp_generate_uuid4();
        set_transient( 'idt_badri_captcha_' . $token, (string) $answer, 30 * MINUTE_IN_SECONDS );

        $prefix = ! empty( $settings['captcha_label_prefix'] ) ? $settings['captcha_label_prefix'] : esc_html__( 'CAPTCHA', 'islami-dawa-tools' );

        return array(
            'token' => $token,
            'label' => sprintf(
                /* translators: 1: captcha label, 2: first number, 3: math operator, 4: second number. */
                esc_html__( '%1$s: %2$s %3$s %4$s = ?', 'islami-dawa-tools' ),
                $prefix,
                number_format_i18n( $a ),
                $symbol,
                number_format_i18n( $b )
            ),
        );
    }

    private function validate_captcha_answer( $settings ) {
        if ( empty( $settings['captcha_enabled'] ) || '1' !== $settings['captcha_enabled'] ) {
            return true;
        }

        $token  = isset( $_POST['badri_captcha_token'] ) ? sanitize_text_field( wp_unslash( $_POST['badri_captcha_token'] ) ) : '';
        $answer = isset( $_POST['badri_captcha'] ) ? sanitize_text_field( wp_unslash( $_POST['badri_captcha'] ) ) : '';

        if ( '' === $token || '' === $answer ) {
            return false;
        }

        $expected = get_transient( 'idt_badri_captcha_' . $token );
        delete_transient( 'idt_badri_captcha_' . $token );

        return false !== $expected && (string) intval( $answer ) === (string) intval( $expected );
    }

    private function process_submission() {
        $settings = $this->get_settings();

        if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
            return new \WP_Error( 'bad_nonce', $settings['error_message'] );
        }

        if ( '1' === $settings['captcha_enabled'] && ! $this->validate_captcha_answer( $settings ) ) {
            return new \WP_Error( 'bad_captcha', $settings['captcha_error_message'] );
        }

        $member_name = isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '';
        $required    = array(
            'member_name'          => esc_html__( 'সদস্য/সদস্যার নাম', 'islami-dawa-tools' ),
            'guardian_name'        => esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ),
            'mobile'               => esc_html__( 'মোবাইল নং', 'islami-dawa-tools' ),
            'profession'           => esc_html__( 'পেশা', 'islami-dawa-tools' ),
            'donation_frequency'   => esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ),
            'donation_amount'      => esc_html__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ),
            'donation_amount_text' => esc_html__( 'অনুদানের পরিমাণ: কথায়', 'islami-dawa-tools' ),
            'permanent_address'    => esc_html__( 'স্থায়ী ঠিকানা', 'islami-dawa-tools' ),
            'current_address'      => esc_html__( 'বর্তমান ঠিকানা', 'islami-dawa-tools' ),
            'public_visibility'    => esc_html__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ),
        );

        foreach ( $required as $field => $label ) {
            if ( 'member_name' === $field ) {
                $value = $member_name;
            } else {
                $value = isset( $_POST[ $field ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $field ] ) ) ) : '';
            }

            if ( '' === $value ) {
                return new \WP_Error( 'missing_' . $field, $this->format_required_message( $label ) );
            }
        }

        foreach ( $this->get_additional_fields() as $extra_field ) {
            if ( empty( $extra_field['required'] ) || '1' !== $extra_field['required'] ) {
                continue;
            }

            $extra_key   = isset( $extra_field['key'] ) ? sanitize_key( $extra_field['key'] ) : '';
            $extra_label = isset( $extra_field['label'] ) ? $extra_field['label'] : $extra_key;
            $extra_name  = 'badri_extra_' . $extra_key;
            $extra_value = isset( $_POST[ $extra_name ] ) ? trim( sanitize_text_field( wp_unslash( $_POST[ $extra_name ] ) ) ) : '';

            if ( '' === $extra_value ) {
                return new \WP_Error( 'missing_' . $extra_key, $this->format_required_message( $extra_label ) );
            }
        }

        $donation_amount = isset( $_POST['donation_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['donation_amount'] ) ) : '';
        if ( 'other' === $donation_amount && empty( $_POST['donation_custom_amount'] ) ) {
            return new \WP_Error( 'missing_donation_custom_amount', $settings['custom_amount_error_message'] );
        }

        $post_id = wp_insert_post(
            array(
                'post_type'   => self::POST_TYPE,
                'post_title'  => $member_name,
                'post_status' => 'pending',
            ),
            true
        );

        if ( is_wp_error( $post_id ) ) {
            return $post_id;
        }

        $this->save_meta_values_from_request( $post_id );

        $photo_result = $this->maybe_handle_photo_upload( $post_id );
        if ( is_wp_error( $photo_result ) ) {
            wp_delete_post( $post_id, true );
            return $photo_result;
        }

        $this->send_admin_notification( $member_name );

        return $post_id;
    }

    private function send_admin_notification( $member_name ) {
        $settings = $this->get_settings();
        $email    = ! empty( $settings['admin_notification_email'] ) ? $settings['admin_notification_email'] : get_option( 'admin_email' );

        if ( ! $email ) {
            return;
        }

        $subject = str_replace( '{name}', $member_name, $settings['admin_email_subject'] );
        $body    = str_replace( '{name}', $member_name, $settings['admin_email_body'] );

        wp_mail( $email, $subject, $body );
    }

    private function maybe_handle_photo_upload( $post_id ) {
        if ( empty( $_FILES['member_photo']['name'] ) ) {
            return true;
        }

        $settings = $this->get_settings();
        $max_size = max( 1, absint( $settings['photo_max_size_mb'] ) ) * 1024 * 1024;

        if ( ! empty( $_FILES['member_photo']['size'] ) && $_FILES['member_photo']['size'] > $max_size ) {
            return new \WP_Error( 'photo_too_large', str_replace( '{size}', absint( $settings['photo_max_size_mb'] ), $settings['photo_size_error_message'] ) );
        }

        $file_type = wp_check_filetype_and_ext( $_FILES['member_photo']['tmp_name'], $_FILES['member_photo']['name'] );
        $allowed   = array( 'jpg', 'jpeg', 'png', 'webp' );

        if ( empty( $file_type['ext'] ) || ! in_array( strtolower( $file_type['ext'] ), $allowed, true ) ) {
            return new \WP_Error( 'photo_type', $settings['photo_type_error_message'] );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'member_photo', $post_id );

        if ( is_wp_error( $attachment_id ) ) {
            return $attachment_id;
        }

        set_post_thumbnail( $post_id, $attachment_id );
        return true;
    }

    private function format_required_message( $field_label ) {
        $settings = $this->get_settings();
        return str_replace( '{field}', $field_label, $settings['required_field_message'] );
    }

    private function save_meta_values_from_request( $post_id ) {
        foreach ( $this->meta_keys as $key ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                continue;
            }

            $value = wp_unslash( $_POST[ $key ] );

            if ( false !== strpos( $key, 'address' ) ) {
                $value = sanitize_textarea_field( $value );
            } else {
                $value = sanitize_text_field( $value );
            }

            update_post_meta( $post_id, '_badri_' . $key, $value );
        }

        if ( ! get_post_meta( $post_id, '_badri_photo_visibility', true ) ) {
            update_post_meta( $post_id, '_badri_photo_visibility', 'hide' );
        }

        foreach ( $this->get_additional_fields() as $extra_field ) {
            $extra_key = isset( $extra_field['key'] ) ? sanitize_key( $extra_field['key'] ) : '';
            if ( '' === $extra_key ) {
                continue;
            }

            $request_key = 'badri_extra_' . $extra_key;
            $meta_key    = '_badri_extra_' . $extra_key;

            if ( isset( $_POST[ $request_key ] ) ) {
                $value = wp_unslash( $_POST[ $request_key ] );
                $value = 'textarea' === ( isset( $extra_field['type'] ) ? $extra_field['type'] : '' ) ? sanitize_textarea_field( $value ) : sanitize_text_field( $value );
                update_post_meta( $post_id, $meta_key, $value );
            }
        }
    }

    private function redirect_with_status( $status ) {
        $redirect = wp_get_referer() ? wp_get_referer() : home_url( '/' );
        $redirect = remove_query_arg( 'badri_member_submitted', $redirect );
        wp_safe_redirect( add_query_arg( 'badri_member_submitted', $status, $redirect ) );
        exit;
    }

    public function add_member_meta_box() {
        add_meta_box(
            'badri_member_details',
            esc_html__( 'বদরী সদস্য তথ্য', 'islami-dawa-tools' ),
            array( $this, 'render_member_meta_box' ),
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public function render_member_meta_box( $post ) {
        wp_nonce_field( 'badri_member_meta_save', 'badri_member_meta_nonce' );
        $current_amount = get_post_meta( $post->ID, '_badri_donation_amount', true );
        ?>
        <div class="idt-badri-member-metabox">
            <div class="idt-badri-member-metabox-hero">
                <div class="idt-badri-member-metabox-avatar">
                    <?php if ( has_post_thumbnail( $post->ID ) ) : ?>
                        <?php echo get_the_post_thumbnail( $post->ID, array( 72, 72 ) ); ?>
                    <?php else : ?>
                        <span><?php echo esc_html( $this->get_initial( get_the_title( $post->ID ) ) ); ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <h2><?php echo esc_html__( 'বদরী সদস্য তথ্য', 'islami-dawa-tools' ); ?></h2>
                    <p><?php echo esc_html__( 'সদস্য/সদস্যার আবেদন তথ্য রিভিউ ও সম্পাদনা করুন। প্রকাশ করলে সদস্য তালিকায় দেখা যাবে।', 'islami-dawa-tools' ); ?></p>
                </div>
            </div>

            <div class="idt-badri-member-admin-section">
                <h3><?php echo esc_html__( 'ব্যক্তিগত তথ্য', 'islami-dawa-tools' ); ?></h3>
                <div class="idt-badri-member-admin-grid two-cols">
                    <?php $this->render_admin_field( $post->ID, 'guardian_name', esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_field( $post->ID, 'mobile', esc_html__( 'মোবাইল নং', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_field( $post->ID, 'profession', esc_html__( 'পেশা', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_select( $post->ID, 'public_visibility', esc_html__( 'পাবলিক তথ্য প্রদর্শন', 'islami-dawa-tools' ), array( 'show' => esc_html__( 'প্রকাশ করা যাবে', 'islami-dawa-tools' ), 'hide' => esc_html__( 'গোপন রাখুন', 'islami-dawa-tools' ) ) ); ?>
                    <?php $this->render_admin_select( $post->ID, 'photo_visibility', esc_html__( 'ছবি প্রদর্শন', 'islami-dawa-tools' ), array( 'show' => esc_html__( 'ছবি প্রকাশ করা যাবে', 'islami-dawa-tools' ), 'hide' => esc_html__( 'ছবি গোপন রাখুন', 'islami-dawa-tools' ) ) ); ?>
                </div>
            </div>

            <div class="idt-badri-member-admin-section">
                <h3><?php echo esc_html__( 'অনুদান তথ্য', 'islami-dawa-tools' ); ?></h3>
                <div class="idt-badri-member-admin-grid three-cols">
                    <?php $this->render_admin_select( $post->ID, 'donation_frequency', esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ), array( 'yearly' => esc_html__( 'বার্ষিক', 'islami-dawa-tools' ), 'monthly' => esc_html__( 'মাসিক', 'islami-dawa-tools' ) ) ); ?>
                    <?php $this->render_admin_select( $post->ID, 'donation_amount', esc_html__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ), $this->get_amount_options(), array( 'data-badri-admin-amount-select' => '1' ) ); ?>
                    <div class="idt-badri-admin-custom-amount" data-badri-admin-custom-amount-wrap <?php echo 'other' === $current_amount ? '' : 'hidden'; ?>>
                        <?php $this->render_admin_field( $post->ID, 'donation_custom_amount', esc_html__( 'কাস্টম অনুদানের পরিমাণ', 'islami-dawa-tools' ), 'number' ); ?>
                    </div>
                    <?php $this->render_admin_field( $post->ID, 'donation_amount_text', esc_html__( 'অনুদানের পরিমাণ: কথায়', 'islami-dawa-tools' ) ); ?>
                </div>
            </div>

            <div class="idt-badri-member-admin-section">
                <h3><?php echo esc_html__( 'ঠিকানা', 'islami-dawa-tools' ); ?></h3>
                <div class="idt-badri-member-admin-grid two-cols">
                    <?php $this->render_admin_textarea( $post->ID, 'permanent_address', esc_html__( 'স্থায়ী ঠিকানা', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_field( $post->ID, 'permanent_district', esc_html__( 'স্থায়ী জেলার নাম', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_textarea( $post->ID, 'current_address', esc_html__( 'বর্তমান ঠিকানা', 'islami-dawa-tools' ) ); ?>
                    <?php $this->render_admin_field( $post->ID, 'current_district', esc_html__( 'বর্তমান জেলার নাম', 'islami-dawa-tools' ) ); ?>
                </div>
            </div>

            <?php $additional_fields = $this->get_additional_fields(); ?>
            <?php if ( ! empty( $additional_fields ) ) : ?>
                <div class="idt-badri-member-admin-section">
                    <h3><?php echo esc_html( $this->get_settings()['extra_section_title'] ); ?></h3>
                    <div class="idt-badri-member-admin-grid two-cols">
                        <?php foreach ( $additional_fields as $extra_field ) : ?>
                            <?php $this->render_admin_extra_field( $post->ID, $extra_field ); ?>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="idt-badri-member-admin-note">
                <?php echo esc_html__( 'সদস্যের ছবি Featured Image হিসেবে সংরক্ষিত হয়। ছবি পরিবর্তন করতে ডান পাশের Featured Image ব্যবহার করুন।', 'islami-dawa-tools' ); ?>
            </div>
        </div>
        <?php
    }

    private function render_admin_field( $post_id, $key, $label, $type = 'text' ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        ?>
        <div class="idt-badri-member-admin-field">
            <label for="badri_admin_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <input id="badri_admin_<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo 'number' === $type ? 'min="1" step="1"' : ''; ?> />
        </div>
        <?php
    }

    private function render_admin_textarea( $post_id, $key, $label ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        ?>
        <div class="idt-badri-member-admin-field">
            <label for="badri_admin_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <textarea id="badri_admin_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" rows="3"><?php echo esc_textarea( $value ); ?></textarea>
        </div>
        <?php
    }

    private function render_admin_select( $post_id, $key, $label, $options, $attrs = array() ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        ?>
        <div class="idt-badri-member-admin-field">
            <label for="badri_admin_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <select id="badri_admin_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $key ); ?>" <?php foreach ( $attrs as $attr_key => $attr_value ) : ?> <?php echo esc_attr( $attr_key ); ?>="<?php echo esc_attr( $attr_value ); ?>"<?php endforeach; ?>>
                <?php foreach ( $options as $option_value => $option_label ) : ?>
                    <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php
    }

    private function render_admin_extra_field( $post_id, $field ) {
        $key         = isset( $field['key'] ) ? sanitize_key( $field['key'] ) : '';
        $label       = isset( $field['label'] ) ? $field['label'] : '';
        $type        = isset( $field['type'] ) ? $field['type'] : 'text';
        $placeholder = isset( $field['placeholder'] ) ? $field['placeholder'] : '';

        if ( '' === $key || '' === $label ) {
            return;
        }

        $value = get_post_meta( $post_id, '_badri_extra_' . $key, true );
        $name  = 'badri_extra_' . $key;
        ?>
        <div class="idt-badri-member-admin-field <?php echo 'textarea' === $type ? 'idt-badri-member-admin-field-wide' : ''; ?>">
            <label for="badri_admin_extra_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label>
            <?php if ( 'textarea' === $type ) : ?>
                <textarea id="badri_admin_extra_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>" rows="3" placeholder="<?php echo esc_attr( $placeholder ); ?>"><?php echo esc_textarea( $value ); ?></textarea>
            <?php elseif ( 'select' === $type ) : ?>
                <select id="badri_admin_extra_<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name ); ?>">
                    <option value=""><?php echo esc_html__( 'নির্বাচন করুন', 'islami-dawa-tools' ); ?></option>
                    <?php foreach ( $this->parse_options( isset( $field['options'] ) ? $field['options'] : '' ) as $option ) : ?>
                        <option value="<?php echo esc_attr( $option ); ?>" <?php selected( $value, $option ); ?>><?php echo esc_html( $option ); ?></option>
                    <?php endforeach; ?>
                </select>
            <?php else : ?>
                <input id="badri_admin_extra_<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( in_array( $type, array( 'text', 'number', 'email', 'date' ), true ) ? $type : 'text' ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" />
            <?php endif; ?>
        </div>
        <?php
    }

    public function save_member_meta( $post_id, $post ) {
        if ( ! isset( $_POST['badri_member_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['badri_member_meta_nonce'] ) ), 'badri_member_meta_save' ) ) {
            return;
        }

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        $this->save_meta_values_from_request( $post_id );
    }

    public function render_grid_shortcode( $atts ) {
        $settings = $this->get_settings();
        $atts = shortcode_atts(
            array(
                'per_page' => 12,
                'columns'  => 3,
            ),
            $atts,
            'badri_members_grid'
        );

        $query = new \WP_Query(
            array(
                'post_type'      => self::POST_TYPE,
                'post_status'    => 'publish',
                'posts_per_page' => absint( $atts['per_page'] ),
                'paged'          => max( 1, get_query_var( 'paged' ) ),
                'orderby'        => 'date',
                'order'          => 'DESC',
            )
        );

        ob_start();
        ?>
        <div class="at-badri-grid-wrap" style="--at-badri-columns: <?php echo esc_attr( absint( $atts['columns'] ) ); ?>;">
            <div class="at-badri-list-header">
                <span><?php echo esc_html__( 'Badri Members', 'islami-dawa-tools' ); ?></span>
                <h2><?php echo esc_html( $settings['grid_title'] ); ?></h2>
                <p><?php echo esc_html( $settings['grid_description'] ); ?></p>
            </div>

            <?php if ( $query->have_posts() ) : ?>
                <div class="at-badri-members-grid">
                    <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                        <?php $this->render_member_card( get_the_ID() ); ?>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <div class="at-badri-empty-state"><?php echo esc_html( $settings['empty_message'] ); ?></div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_member_card( $post_id ) {
        $visibility       = get_post_meta( $post_id, '_badri_public_visibility', true );
        $photo_visibility = get_post_meta( $post_id, '_badri_photo_visibility', true );
        $hidden           = 'hide' === $visibility;
        $masked           = esc_html__( 'xxx', 'islami-dawa-tools' );
        $show_photo       = ! $hidden && 'show' === $photo_visibility && has_post_thumbnail( $post_id );

        $fields = array(
            esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_guardian_name', true ),
            esc_html__( 'মোবাইল', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_mobile', true ),
            esc_html__( 'পেশা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_profession', true ),
            esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ) => $this->format_frequency( get_post_meta( $post_id, '_badri_donation_frequency', true ) ),
            esc_html__( 'অনুদান', 'islami-dawa-tools' ) => $this->format_amount( get_post_meta( $post_id, '_badri_donation_amount', true ), $post_id ),
            esc_html__( 'স্থায়ী জেলা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_permanent_district', true ),
            esc_html__( 'বর্তমান জেলা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_current_district', true ),
        );

        foreach ( $this->get_additional_fields() as $extra_field ) {
            if ( empty( $extra_field['show_in_grid'] ) || '1' !== $extra_field['show_in_grid'] ) {
                continue;
            }
            $extra_key = isset( $extra_field['key'] ) ? sanitize_key( $extra_field['key'] ) : '';
            $label     = isset( $extra_field['label'] ) ? $extra_field['label'] : $extra_key;
            if ( '' !== $extra_key && '' !== $label ) {
                $fields[ $label ] = get_post_meta( $post_id, '_badri_extra_' . $extra_key, true );
            }
        }
        ?>
        <article class="at-badri-member-card <?php echo $hidden ? 'is-hidden-member' : 'is-visible-member'; ?>">
            <?php if ( $show_photo ) : ?>
                <div class="at-badri-member-photo"><?php echo get_the_post_thumbnail( $post_id, 'medium' ); ?></div>
            <?php else : ?>
                <div class="at-badri-member-avatar"><?php echo esc_html( $this->get_initial( get_the_title( $post_id ) ) ); ?></div>
            <?php endif; ?>
            <h3 title="<?php echo esc_attr( get_the_title( $post_id ) ); ?>"><?php echo esc_html( get_the_title( $post_id ) ); ?></h3>
            <div class="at-badri-member-info">
                <?php foreach ( $fields as $label => $value ) : ?>
                    <div>
                        <span><?php echo esc_html( $label ); ?></span>
                        <strong><?php echo esc_html( $hidden ? $masked : ( $value ? $value : $masked ) ); ?></strong>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
        <?php
    }

    private function format_frequency( $frequency ) {
        if ( 'yearly' === $frequency ) {
            return esc_html__( 'বার্ষিক', 'islami-dawa-tools' );
        }

        if ( 'monthly' === $frequency ) {
            return esc_html__( 'মাসিক', 'islami-dawa-tools' );
        }

        return '';
    }

    private function format_amount( $amount, $post_id = 0 ) {
        if ( 'other' === $amount && $post_id ) {
            $custom_amount = get_post_meta( $post_id, '_badri_donation_custom_amount', true );
            return $custom_amount ? sprintf( esc_html__( '৳%s', 'islami-dawa-tools' ), number_format_i18n( absint( $custom_amount ) ) ) : esc_html__( 'অন্যান্য', 'islami-dawa-tools' );
        }

        $options = $this->get_amount_options();
        return isset( $options[ $amount ] ) ? $options[ $amount ] : $amount;
    }

    private function get_initial( $text ) {
        $text = wp_strip_all_tags( $text );
        if ( function_exists( 'mb_substr' ) ) {
            return mb_substr( $text, 0, 1, 'UTF-8' );
        }
        return substr( $text, 0, 1 );
    }

    public function register_page_templates( $templates ) {
        $templates['islami-dawa-badri-member-form.php'] = esc_html__( 'Badri Member Form', 'islami-dawa-tools' );
        $templates['islami-dawa-badri-members-grid.php'] = esc_html__( 'Badri Members Grid', 'islami-dawa-tools' );
        return $templates;
    }

    public function load_page_template( $template ) {
        if ( ! is_singular( 'page' ) ) {
            return $template;
        }

        $selected_template = get_page_template_slug( get_queried_object_id() );

        if ( 'islami-dawa-badri-member-form.php' === $selected_template ) {
            $plugin_template = ISLAMI_DAWA_TOOLS_PATH . 'Templates/badri-member-form.php';
            return file_exists( $plugin_template ) ? $plugin_template : $template;
        }

        if ( 'islami-dawa-badri-members-grid.php' === $selected_template ) {
            $plugin_template = ISLAMI_DAWA_TOOLS_PATH . 'Templates/badri-members-grid.php';
            return file_exists( $plugin_template ) ? $plugin_template : $template;
        }

        return $template;
    }

    public function add_admin_columns( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $label ) {
            $new_columns[ $key ] = $label;
            if ( 'title' === $key ) {
                $new_columns['badri_photo']      = esc_html__( 'ছবি', 'islami-dawa-tools' );
                $new_columns['badri_mobile']     = esc_html__( 'মোবাইল', 'islami-dawa-tools' );
                $new_columns['badri_frequency']  = esc_html__( 'ধরন', 'islami-dawa-tools' );
                $new_columns['badri_donation']   = esc_html__( 'অনুদান', 'islami-dawa-tools' );
                $new_columns['badri_visibility'] = esc_html__( 'প্রকাশ', 'islami-dawa-tools' );
                $new_columns['badri_status']     = esc_html__( 'স্ট্যাটাস', 'islami-dawa-tools' );
            }
        }
        return $new_columns;
    }

    public function render_admin_columns( $column, $post_id ) {
        if ( 'badri_photo' === $column ) {
            if ( has_post_thumbnail( $post_id ) ) {
                echo '<span class="idt-badri-list-photo">' . get_the_post_thumbnail( $post_id, array( 46, 46 ) ) . '</span>';
            } else {
                echo '<span class="idt-badri-list-initial">' . esc_html( $this->get_initial( get_the_title( $post_id ) ) ) . '</span>';
            }
        }

        if ( 'badri_mobile' === $column ) {
            $mobile = get_post_meta( $post_id, '_badri_mobile', true );
            echo $mobile ? '<span class="idt-badri-list-mobile">' . esc_html( $mobile ) . '</span>' : '<span class="idt-badri-muted">—</span>';
        }

        if ( 'badri_frequency' === $column ) {
            $frequency = get_post_meta( $post_id, '_badri_donation_frequency', true );
            $label     = $this->format_frequency( $frequency );
            echo $label ? '<span class="idt-badri-list-badge is-' . esc_attr( $frequency ) . '">' . esc_html( $label ) . '</span>' : '<span class="idt-badri-muted">—</span>';
        }

        if ( 'badri_donation' === $column ) {
            echo '<span class="idt-badri-list-amount">' . esc_html( $this->format_amount( get_post_meta( $post_id, '_badri_donation_amount', true ), $post_id ) ) . '</span>';
        }

        if ( 'badri_visibility' === $column ) {
            $visibility = get_post_meta( $post_id, '_badri_public_visibility', true );
            $visibility = $visibility ? $visibility : 'show';
            $label      = 'hide' === $visibility ? esc_html__( 'গোপন', 'islami-dawa-tools' ) : esc_html__( 'পাবলিক', 'islami-dawa-tools' );
            echo '<span class="idt-badri-list-badge is-' . esc_attr( $visibility ) . '">' . esc_html( $label ) . '</span>';
        }

        if ( 'badri_status' === $column ) {
            $status     = get_post_status( $post_id );
            $status_obj = get_post_status_object( $status );
            $label      = $status_obj ? $status_obj->label : $status;
            echo '<span class="idt-badri-list-badge is-' . esc_attr( $status ) . '">' . esc_html( $label ) . '</span>';
        }
    }

    public function render_admin_list_filters( $post_type ) {
        if ( self::POST_TYPE !== $post_type ) {
            return;
        }

        $selected_status     = isset( $_GET['badri_filter_status'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_status'] ) ) : '';
        $selected_frequency  = isset( $_GET['badri_filter_frequency'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_frequency'] ) ) : '';
        $selected_visibility = isset( $_GET['badri_filter_visibility'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_visibility'] ) ) : '';
        $selected_year       = isset( $_GET['badri_filter_year'] ) ? absint( $_GET['badri_filter_year'] ) : 0;
        $selected_month      = isset( $_GET['badri_filter_month'] ) ? absint( $_GET['badri_filter_month'] ) : 0;

        ?>
        <select name="badri_filter_status" class="idt-badri-list-filter">
            <option value=""><?php echo esc_html__( 'সব স্ট্যাটাস', 'islami-dawa-tools' ); ?></option>
            <option value="publish" <?php selected( $selected_status, 'publish' ); ?>><?php echo esc_html__( 'প্রকাশিত', 'islami-dawa-tools' ); ?></option>
            <option value="pending" <?php selected( $selected_status, 'pending' ); ?>><?php echo esc_html__( 'রিভিউ অপেক্ষমাণ', 'islami-dawa-tools' ); ?></option>
            <option value="draft" <?php selected( $selected_status, 'draft' ); ?>><?php echo esc_html__( 'ড্রাফট', 'islami-dawa-tools' ); ?></option>
        </select>

        <select name="badri_filter_frequency" class="idt-badri-list-filter">
            <option value=""><?php echo esc_html__( 'সব অনুদান ধরন', 'islami-dawa-tools' ); ?></option>
            <option value="monthly" <?php selected( $selected_frequency, 'monthly' ); ?>><?php echo esc_html__( 'মাসিক', 'islami-dawa-tools' ); ?></option>
            <option value="yearly" <?php selected( $selected_frequency, 'yearly' ); ?>><?php echo esc_html__( 'বার্ষিক', 'islami-dawa-tools' ); ?></option>
        </select>

        <select name="badri_filter_visibility" class="idt-badri-list-filter">
            <option value=""><?php echo esc_html__( 'সব প্রকাশ অনুমতি', 'islami-dawa-tools' ); ?></option>
            <option value="show" <?php selected( $selected_visibility, 'show' ); ?>><?php echo esc_html__( 'পাবলিক', 'islami-dawa-tools' ); ?></option>
            <option value="hide" <?php selected( $selected_visibility, 'hide' ); ?>><?php echo esc_html__( 'গোপন', 'islami-dawa-tools' ); ?></option>
        </select>

        <select name="badri_filter_year" class="idt-badri-list-filter">
            <option value="0"><?php echo esc_html__( 'সব বছর', 'islami-dawa-tools' ); ?></option>
            <?php
            $current_year = absint( current_time( 'Y' ) );
            for ( $year = $current_year; $year >= $current_year - 5; $year-- ) :
                ?>
                <option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>><?php echo esc_html( number_format_i18n( $year, 0 ) ); ?></option>
            <?php endfor; ?>
        </select>

        <select name="badri_filter_month" class="idt-badri-list-filter">
            <option value="0"><?php echo esc_html__( 'সব মাস', 'islami-dawa-tools' ); ?></option>
            <?php for ( $month = 1; $month <= 12; $month++ ) : ?>
                <option value="<?php echo esc_attr( $month ); ?>" <?php selected( $selected_month, $month ); ?>><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $month, 1 ) ) ); ?></option>
            <?php endfor; ?>
        </select>
        <?php
    }

    public function filter_admin_member_query( $query ) {
        global $pagenow;

        if ( ! is_admin() || 'edit.php' !== $pagenow || ! $query->is_main_query() ) {
            return;
        }

        $post_type = $query->get( 'post_type' );
        if ( self::POST_TYPE !== $post_type ) {
            return;
        }

        $status = isset( $_GET['badri_filter_status'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_status'] ) ) : '';
        if ( in_array( $status, array( 'publish', 'pending', 'draft' ), true ) ) {
            $query->set( 'post_status', $status );
        }

        $meta_query = array();

        $frequency = isset( $_GET['badri_filter_frequency'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_frequency'] ) ) : '';
        if ( in_array( $frequency, array( 'monthly', 'yearly' ), true ) ) {
            $meta_query[] = array(
                'key'   => '_badri_donation_frequency',
                'value' => $frequency,
            );
        }

        $visibility = isset( $_GET['badri_filter_visibility'] ) ? sanitize_key( wp_unslash( $_GET['badri_filter_visibility'] ) ) : '';
        if ( in_array( $visibility, array( 'show', 'hide' ), true ) ) {
            $meta_query[] = array(
                'key'   => '_badri_public_visibility',
                'value' => $visibility,
            );
        }

        if ( ! empty( $meta_query ) ) {
            $query->set( 'meta_query', $meta_query );
        }

        $year  = isset( $_GET['badri_filter_year'] ) ? absint( $_GET['badri_filter_year'] ) : 0;
        $month = isset( $_GET['badri_filter_month'] ) ? absint( $_GET['badri_filter_month'] ) : 0;
        $date_query = array();

        if ( $year ) {
            $date_query['year'] = $year;
        }

        if ( $month >= 1 && $month <= 12 ) {
            $date_query['month'] = $month;
        }

        if ( ! empty( $date_query ) ) {
            $query->set( 'date_query', array( $date_query ) );
        }
    }

    public function style_admin_member_views( $views ) {
        foreach ( $views as $key => $view ) {
            $views[ $key ] = '<span class="idt-badri-view-pill">' . $view . '</span>';
        }

        return $views;
    }

}

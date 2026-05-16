<?php
namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BadriMembers {
    const POST_TYPE = 'badri_member';
    const NONCE_ACTION = 'islami_dawa_badri_member_submit';
    const NONCE_NAME = 'islami_dawa_badri_member_nonce';
    const SETTINGS_OPTION = 'islami_dawa_badri_settings';

    private $meta_keys = array(
        'guardian_name',
        'mobile',
        'profession',
        'donation_frequency',
        'donation_amount',
        'donation_amount_text',
        'permanent_address',
        'permanent_district',
        'current_address',
        'current_district',
        'public_visibility',
        'show_photo',
    );

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_shortcode( 'badri_member_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'badri_members_grid', array( $this, 'render_grid_shortcode' ) );

        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_frontend_assets' ) );

        add_action( 'admin_post_nopriv_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_post_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );
        add_action( 'wp_ajax_nopriv_islami_dawa_badri_member_submit_ajax', array( $this, 'handle_ajax_submission' ) );
        add_action( 'wp_ajax_islami_dawa_badri_member_submit_ajax', array( $this, 'handle_ajax_submission' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_member_meta_box' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_member_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );

        add_filter( 'theme_page_templates', array( $this, 'register_page_templates' ) );
        add_filter( 'template_include', array( $this, 'load_page_template' ) );

        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function register_post_type() {
        $labels = array(
            'name'                  => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'singular_name'         => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'menu_name'             => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'name_admin_bar'        => esc_html__( 'বদরী সদস্য', 'islami-dawa-tools' ),
            'add_new'               => esc_html__( 'নতুন সদস্য', 'islami-dawa-tools' ),
            'add_new_item'          => esc_html__( 'নতুন বদরী সদস্য যোগ করুন', 'islami-dawa-tools' ),
            'edit_item'             => esc_html__( 'বদরী সদস্য সম্পাদনা করুন', 'islami-dawa-tools' ),
            'new_item'              => esc_html__( 'নতুন বদরী সদস্য', 'islami-dawa-tools' ),
            'view_item'             => esc_html__( 'বদরী সদস্য দেখুন', 'islami-dawa-tools' ),
            'search_items'          => esc_html__( 'বদরী সদস্য খুঁজুন', 'islami-dawa-tools' ),
            'not_found'             => esc_html__( 'কোনো সদস্য পাওয়া যায়নি', 'islami-dawa-tools' ),
            'not_found_in_trash'    => esc_html__( 'ট্র্যাশে কোনো সদস্য পাওয়া যায়নি', 'islami-dawa-tools' ),
            'featured_image'        => esc_html__( 'সদস্যের ছবি', 'islami-dawa-tools' ),
            'set_featured_image'    => esc_html__( 'সদস্যের ছবি সেট করুন', 'islami-dawa-tools' ),
            'remove_featured_image' => esc_html__( 'সদস্যের ছবি সরান', 'islami-dawa-tools' ),
            'use_featured_image'    => esc_html__( 'সদস্যের ছবি হিসেবে ব্যবহার করুন', 'islami-dawa-tools' ),
        );

        register_post_type(
            self::POST_TYPE,
            array(
                'labels'              => $labels,
                'public'              => true,
                'publicly_queryable'  => false,
                'show_ui'             => true,
                'show_in_menu'        => true,
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

        wp_enqueue_script(
            'sweetalert2',
            'https://cdn.jsdelivr.net/npm/sweetalert2@11',
            array(),
            '11',
            true
        );

        wp_enqueue_script(
            'islami-dawa-badri-members',
            ISLAMI_DAWA_TOOLS_FRONTEND_ASSETS . 'badri-members.js',
            array( 'sweetalert2' ),
            ISLAMI_DAWA_TOOLS_VERSION,
            true
        );

        $settings = $this->get_settings();

        wp_localize_script(
            'islami-dawa-badri-members',
            'IslamiDawaBadriMembers',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'messages' => array(
                    'processing' => $settings['processing_message'],
                    'success'    => $settings['success_message'],
                    'error'      => $settings['error_message'],
                ),
            )
        );
    }

    public function get_default_settings() {
        return array(
            'form_title'          => esc_html__( 'আজীবন বদরী সদস্য/সদস্যা ফরম', 'islami-dawa-tools' ),
            'form_description'    => esc_html__( 'নিচের তথ্যগুলো পূরণ করে জমা দিন। অ্যাডমিন যাচাই করার পর সদস্য তালিকায় প্রকাশ করা হবে।', 'islami-dawa-tools' ),
            'grid_title'          => esc_html__( 'আজীবন বদরী সদস্য/সদস্যা তালিকা', 'islami-dawa-tools' ),
            'grid_description'    => esc_html__( 'অ্যাডমিন অনুমোদিত সদস্যদের তালিকা এখানে প্রদর্শিত হচ্ছে।', 'islami-dawa-tools' ),
            'success_message'     => esc_html__( 'আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর প্রকাশ করা হবে।', 'islami-dawa-tools' ),
            'error_message'       => esc_html__( 'দুঃখিত, তথ্য জমা দেওয়া যায়নি। অনুগ্রহ করে সব প্রয়োজনীয় তথ্য পূরণ করুন।', 'islami-dawa-tools' ),
            'captcha_message'     => esc_html__( 'CAPTCHA সঠিক নয়। অনুগ্রহ করে আবার চেষ্টা করুন।', 'islami-dawa-tools' ),
            'processing_message'  => esc_html__( 'তথ্য জমা হচ্ছে...', 'islami-dawa-tools' ),
            'admin_email'         => get_option( 'admin_email' ),
            'admin_email_subject' => esc_html__( 'নতুন বদরী সদস্য আবেদন: {name}', 'islami-dawa-tools' ),
            'admin_email_body'    => esc_html__( 'একটি নতুন বদরী সদস্য আবেদন জমা হয়েছে। অনুগ্রহ করে অ্যাডমিন থেকে রিভিউ করুন।', 'islami-dawa-tools' ),
            'photo_max_size'      => 2,
        );
    }

    public function get_settings() {
        $saved = get_option( self::SETTINGS_OPTION, array() );
        return wp_parse_args( is_array( $saved ) ? $saved : array(), $this->get_default_settings() );
    }

    public function register_settings() {
        register_setting(
            'islami_dawa_badri_settings_group',
            self::SETTINGS_OPTION,
            array( $this, 'sanitize_settings' )
        );
    }

    public function sanitize_settings( $input ) {
        $defaults = $this->get_default_settings();
        $output   = array();

        foreach ( $defaults as $key => $default ) {
            if ( 'photo_max_size' === $key ) {
                $output[ $key ] = isset( $input[ $key ] ) ? max( 1, absint( $input[ $key ] ) ) : $default;
                continue;
            }

            if ( 'admin_email' === $key ) {
                $output[ $key ] = isset( $input[ $key ] ) ? sanitize_email( $input[ $key ] ) : $default;
                continue;
            }

            $output[ $key ] = isset( $input[ $key ] ) ? sanitize_textarea_field( $input[ $key ] ) : $default;
        }

        return $output;
    }

    public function add_settings_page() {
        add_submenu_page(
            'edit.php?post_type=' . self::POST_TYPE,
            esc_html__( 'বদরী সদস্য সেটিংস', 'islami-dawa-tools' ),
            esc_html__( 'সেটিংস', 'islami-dawa-tools' ),
            'manage_options',
            'badri-member-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function render_settings_page() {
        $settings = $this->get_settings();
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__( 'বদরী সদস্য সেটিংস', 'islami-dawa-tools' ); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'islami_dawa_badri_settings_group' ); ?>

                <h2><?php echo esc_html__( 'ফরম সেটিংস', 'islami-dawa-tools' ); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->render_settings_text_field( 'form_title', esc_html__( 'ফরম শিরোনাম', 'islami-dawa-tools' ), $settings['form_title'] ); ?>
                    <?php $this->render_settings_textarea_field( 'form_description', esc_html__( 'ফরম বিবরণ', 'islami-dawa-tools' ), $settings['form_description'] ); ?>
                    <?php $this->render_settings_textarea_field( 'success_message', esc_html__( 'সফল সাবমিশন মেসেজ', 'islami-dawa-tools' ), $settings['success_message'] ); ?>
                    <?php $this->render_settings_textarea_field( 'error_message', esc_html__( 'এরর মেসেজ', 'islami-dawa-tools' ), $settings['error_message'] ); ?>
                    <?php $this->render_settings_textarea_field( 'captcha_message', esc_html__( 'CAPTCHA এরর মেসেজ', 'islami-dawa-tools' ), $settings['captcha_message'] ); ?>
                    <?php $this->render_settings_text_field( 'processing_message', esc_html__( 'প্রসেসিং মেসেজ', 'islami-dawa-tools' ), $settings['processing_message'] ); ?>
                    <?php $this->render_settings_number_field( 'photo_max_size', esc_html__( 'ছবির সর্বোচ্চ সাইজ (MB)', 'islami-dawa-tools' ), $settings['photo_max_size'] ); ?>
                </table>

                <h2><?php echo esc_html__( 'লিস্টিং সেটিংস', 'islami-dawa-tools' ); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->render_settings_text_field( 'grid_title', esc_html__( 'লিস্ট শিরোনাম', 'islami-dawa-tools' ), $settings['grid_title'] ); ?>
                    <?php $this->render_settings_textarea_field( 'grid_description', esc_html__( 'লিস্ট বিবরণ', 'islami-dawa-tools' ), $settings['grid_description'] ); ?>
                </table>

                <h2><?php echo esc_html__( 'অ্যাডমিন নোটিফিকেশন', 'islami-dawa-tools' ); ?></h2>
                <table class="form-table" role="presentation">
                    <?php $this->render_settings_text_field( 'admin_email', esc_html__( 'অ্যাডমিন ইমেইল', 'islami-dawa-tools' ), $settings['admin_email'], 'email' ); ?>
                    <?php $this->render_settings_text_field( 'admin_email_subject', esc_html__( 'ইমেইল সাবজেক্ট', 'islami-dawa-tools' ), $settings['admin_email_subject'] ); ?>
                    <?php $this->render_settings_textarea_field( 'admin_email_body', esc_html__( 'ইমেইল বডি', 'islami-dawa-tools' ), $settings['admin_email_body'] ); ?>
                </table>

                <?php submit_button( esc_html__( 'সেটিংস সংরক্ষণ করুন', 'islami-dawa-tools' ) ); ?>
            </form>
        </div>
        <?php
    }

    private function render_settings_text_field( $key, $label, $value, $type = 'text' ) {
        ?>
        <tr>
            <th scope="row"><label for="badri_settings_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input id="badri_settings_<?php echo esc_attr( $key ); ?>" type="<?php echo esc_attr( $type ); ?>" class="regular-text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>"></td>
        </tr>
        <?php
    }

    private function render_settings_number_field( $key, $label, $value ) {
        ?>
        <tr>
            <th scope="row"><label for="badri_settings_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><input id="badri_settings_<?php echo esc_attr( $key ); ?>" type="number" min="1" class="small-text" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $value ); ?>"></td>
        </tr>
        <?php
    }

    private function render_settings_textarea_field( $key, $label, $value ) {
        ?>
        <tr>
            <th scope="row"><label for="badri_settings_<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></label></th>
            <td><textarea id="badri_settings_<?php echo esc_attr( $key ); ?>" class="large-text" rows="3" name="<?php echo esc_attr( self::SETTINGS_OPTION ); ?>[<?php echo esc_attr( $key ); ?>]"><?php echo esc_textarea( $value ); ?></textarea></td>
        </tr>
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
        <form class="at-badri-form" method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-badri-ajax-form="1">
            <input type="hidden" name="action" value="islami_dawa_badri_member_submit" />
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

            <div class="at-badri-form-header">
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
                    <div class="at-badri-radio-list">
                        <label><input type="radio" name="donation_frequency" value="yearly" required /> <?php echo esc_html__( 'বার্ষিক', 'islami-dawa-tools' ); ?></label>
                        <label><input type="radio" name="donation_frequency" value="monthly" required /> <?php echo esc_html__( 'মাসিক', 'islami-dawa-tools' ); ?></label>
                    </div>
                </div>

                <div class="at-badri-field">
                    <label for="badri_donation_amount"><?php echo esc_html__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                    <select id="badri_donation_amount" name="donation_amount" required>
                        <option value=""><?php echo esc_html__( 'নির্বাচন করুন', 'islami-dawa-tools' ); ?></option>
                        <?php foreach ( $this->get_amount_options() as $value => $label ) : ?>
                            <option value="<?php echo esc_attr( $value ); ?>"><?php echo esc_html( $label ); ?></option>
                        <?php endforeach; ?>
                    </select>
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
                <div class="at-badri-field at-badri-privacy-field">
                    <label><?php echo esc_html__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                    <div class="at-badri-radio-list">
                        <label><input type="radio" name="public_visibility" value="show" required /> <?php echo esc_html__( 'আমার তথ্য প্রকাশ করা যাবে', 'islami-dawa-tools' ); ?></label>
                        <label><input type="radio" name="public_visibility" value="hide" required /> <?php echo esc_html__( 'আমাকে পাবলিক তালিকায় গোপন রাখুন', 'islami-dawa-tools' ); ?></label>
                    </div>
                    <p><?php echo esc_html__( 'গোপন রাখলে তালিকায় শুধু আপনার নাম দেখা যাবে; বাকি তথ্য xxx হিসেবে দেখানো হবে।', 'islami-dawa-tools' ); ?></p>
                </div>

                <div class="at-badri-field at-badri-photo-field">
                    <label><?php echo esc_html__( 'ছবি প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?></label>
                    <div class="at-badri-radio-list">
                        <label><input type="radio" name="show_photo" value="yes" /> <?php echo esc_html__( 'হ্যাঁ, ছবি দেখানো যাবে', 'islami-dawa-tools' ); ?></label>
                        <label><input type="radio" name="show_photo" value="no" checked /> <?php echo esc_html__( 'না, ছবির বদলে নামের প্রথম অক্ষর দেখান', 'islami-dawa-tools' ); ?></label>
                    </div>

                    <label for="badri_member_photo" class="at-badri-file-label"><?php echo esc_html__( 'সদস্যের ছবি', 'islami-dawa-tools' ); ?></label>
                    <input id="badri_member_photo" type="file" name="member_photo" accept="image/jpeg,image/png,image/webp" />
                    <p><?php printf( esc_html__( 'JPG, PNG অথবা WEBP ফাইল দিন। সর্বোচ্চ সাইজ %sMB।', 'islami-dawa-tools' ), esc_html( $settings['photo_max_size'] ) ); ?></p>
                </div>
            </div>

            <div class="at-badri-field at-badri-captcha-field">
                <label for="badri_captcha"><?php echo esc_html__( 'CAPTCHA: ৭ + ২ = ?', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                <input id="badri_captcha" type="number" name="badri_captcha" required />
            </div>

            <button type="submit" class="at-badri-submit"><?php echo esc_html__( 'জমা দিন', 'islami-dawa-tools' ); ?></button>
        </form>
        <?php
        return ob_get_clean();
    }

    private function render_front_text_field( $name, $label, $required = false, $placeholder = '', $type = 'text' ) {
        ?>
        <div class="at-badri-field">
            <label for="badri_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
            <input id="badri_<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo $required ? 'required' : ''; ?> />
        </div>
        <?php
    }

    private function render_front_textarea_field( $name, $label, $required = false, $placeholder = '' ) {
        ?>
        <label for="badri_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
        <textarea id="badri_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php echo $required ? 'required' : ''; ?>></textarea>
        <?php
    }

    public function handle_form_submission() {
        $result = $this->process_submission();

        if ( is_wp_error( $result ) ) {
            $this->redirect_with_status( 'error' );
        }

        $this->redirect_with_status( 'success' );
    }

    public function handle_ajax_submission() {
        $result = $this->process_submission();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(
                array(
                    'message' => $result->get_error_message(),
                )
            );
        }

        wp_send_json_success(
            array(
                'message' => $this->get_settings()['success_message'],
            )
        );
    }

    private function process_submission() {
        $settings = $this->get_settings();

        if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
            return new \WP_Error( 'bad_nonce', $settings['error_message'] );
        }

        $captcha = isset( $_POST['badri_captcha'] ) ? absint( $_POST['badri_captcha'] ) : 0;
        if ( 9 !== $captcha ) {
            return new \WP_Error( 'bad_captcha', $settings['captcha_message'] );
        }

        $member_name = isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '';
        $required = array( 'guardian_name', 'mobile', 'profession', 'donation_frequency', 'donation_amount', 'donation_amount_text', 'permanent_address', 'current_address', 'public_visibility' );

        if ( empty( $member_name ) ) {
            return new \WP_Error( 'missing_name', $settings['error_message'] );
        }

        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                return new \WP_Error( 'missing_field', $settings['error_message'] );
            }
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
            return new \WP_Error( 'insert_failed', $settings['error_message'] );
        }

        $this->save_meta_values_from_request( $post_id );

        $photo_result = $this->handle_photo_upload( $post_id );

        if ( is_wp_error( $photo_result ) ) {
            wp_delete_post( $post_id, true );
            return $photo_result;
        }

        $this->send_admin_notification( $member_name );

        return $post_id;
    }

    private function send_admin_notification( $member_name ) {
        $settings    = $this->get_settings();
        $admin_email = ! empty( $settings['admin_email'] ) ? $settings['admin_email'] : get_option( 'admin_email' );

        if ( ! $admin_email ) {
            return;
        }

        $subject = str_replace( '{name}', $member_name, $settings['admin_email_subject'] );
        $body    = str_replace( '{name}', $member_name, $settings['admin_email_body'] );

        wp_mail( $admin_email, $subject, $body );
    }

    private function handle_photo_upload( $post_id ) {
        if ( empty( $_FILES['member_photo']['name'] ) ) {
            return true;
        }

        $settings = $this->get_settings();
        $max_size = max( 1, absint( $settings['photo_max_size'] ) ) * 1024 * 1024;

        if ( ! empty( $_FILES['member_photo']['size'] ) && $_FILES['member_photo']['size'] > $max_size ) {
            return new \WP_Error( 'file_too_large', sprintf( esc_html__( 'ছবির সাইজ %sMB এর বেশি হতে পারবে না।', 'islami-dawa-tools' ), absint( $settings['photo_max_size'] ) ) );
        }

        $allowed_types = array( 'image/jpeg', 'image/png', 'image/webp' );

        if ( ! empty( $_FILES['member_photo']['type'] ) && ! in_array( $_FILES['member_photo']['type'], $allowed_types, true ) ) {
            return new \WP_Error( 'invalid_file_type', esc_html__( 'শুধুমাত্র JPG, PNG অথবা WEBP ছবি আপলোড করা যাবে।', 'islami-dawa-tools' ) );
        }

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $attachment_id = media_handle_upload( 'member_photo', $post_id );

        if ( is_wp_error( $attachment_id ) ) {
            return new \WP_Error( 'upload_failed', $attachment_id->get_error_message() );
        }

        update_post_meta( $post_id, '_badri_photo_id', absint( $attachment_id ) );
        set_post_thumbnail( $post_id, absint( $attachment_id ) );

        return true;
    }

    private function save_meta_values_from_request( $post_id ) {
        foreach ( $this->meta_keys as $key ) {
            if ( ! isset( $_POST[ $key ] ) ) {
                if ( 'show_photo' === $key ) {
                    update_post_meta( $post_id, '_badri_' . $key, 'no' );
                }
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
        $photo_id = get_post_thumbnail_id( $post->ID );
        ?>
        <div class="at-badri-admin-fields">
            <?php if ( $photo_id ) : ?>
                <p>
                    <strong><?php echo esc_html__( 'বর্তমান ছবি', 'islami-dawa-tools' ); ?></strong><br>
                    <?php echo wp_get_attachment_image( $photo_id, 'thumbnail' ); ?>
                </p>
            <?php endif; ?>
            <?php $this->render_admin_field( $post->ID, 'guardian_name', esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_field( $post->ID, 'mobile', esc_html__( 'মোবাইল নং', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_field( $post->ID, 'profession', esc_html__( 'পেশা', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_select( $post->ID, 'donation_frequency', esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ), array( 'yearly' => esc_html__( 'বার্ষিক', 'islami-dawa-tools' ), 'monthly' => esc_html__( 'মাসিক', 'islami-dawa-tools' ) ) ); ?>
            <?php $this->render_admin_select( $post->ID, 'donation_amount', esc_html__( 'অনুদানের পরিমাণ: অংকে', 'islami-dawa-tools' ), $this->get_amount_options() ); ?>
            <?php $this->render_admin_field( $post->ID, 'donation_amount_text', esc_html__( 'অনুদানের পরিমাণ: কথায়', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_textarea( $post->ID, 'permanent_address', esc_html__( 'স্থায়ী ঠিকানা', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_field( $post->ID, 'permanent_district', esc_html__( 'স্থায়ী জেলার নাম', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_textarea( $post->ID, 'current_address', esc_html__( 'বর্তমান ঠিকানা', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_field( $post->ID, 'current_district', esc_html__( 'বর্তমান জেলার নাম', 'islami-dawa-tools' ) ); ?>
            <?php $this->render_admin_select( $post->ID, 'public_visibility', esc_html__( 'পাবলিক তথ্য প্রদর্শন', 'islami-dawa-tools' ), array( 'show' => esc_html__( 'প্রকাশ করা যাবে', 'islami-dawa-tools' ), 'hide' => esc_html__( 'গোপন রাখুন', 'islami-dawa-tools' ) ) ); ?>
            <?php $this->render_admin_select( $post->ID, 'show_photo', esc_html__( 'ছবি প্রদর্শন', 'islami-dawa-tools' ), array( 'yes' => esc_html__( 'ছবি দেখানো যাবে', 'islami-dawa-tools' ), 'no' => esc_html__( 'প্রথম অক্ষর দেখান', 'islami-dawa-tools' ) ) ); ?>
        </div>
        <?php
    }

    private function render_admin_field( $post_id, $key, $label ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        ?>
        <p><label><strong><?php echo esc_html( $label ); ?></strong></label><br><input type="text" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" style="width:100%;max-width:700px;"></p>
        <?php
    }

    private function render_admin_textarea( $post_id, $key, $label ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        ?>
        <p><label><strong><?php echo esc_html( $label ); ?></strong></label><br><textarea name="<?php echo esc_attr( $key ); ?>" rows="3" style="width:100%;max-width:700px;"><?php echo esc_textarea( $value ); ?></textarea></p>
        <?php
    }

    private function render_admin_select( $post_id, $key, $label, $options ) {
        $value = get_post_meta( $post_id, '_badri_' . $key, true );
        if ( '' === $value && 'show_photo' === $key ) {
            $value = 'no';
        }
        ?>
        <p><label><strong><?php echo esc_html( $label ); ?></strong></label><br><select name="<?php echo esc_attr( $key ); ?>" style="width:100%;max-width:700px;">
            <?php foreach ( $options as $option_value => $option_label ) : ?>
                <option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( $value, $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
            <?php endforeach; ?>
        </select></p>
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

        $photo_id = get_post_thumbnail_id( $post_id );
        if ( $photo_id ) {
            update_post_meta( $post_id, '_badri_photo_id', absint( $photo_id ) );
        }
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
                <div class="at-badri-empty-state"><?php echo esc_html__( 'এখনো কোনো প্রকাশিত সদস্য পাওয়া যায়নি।', 'islami-dawa-tools' ); ?></div>
            <?php endif; ?>

            <?php wp_reset_postdata(); ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_member_card( $post_id ) {
        $visibility = get_post_meta( $post_id, '_badri_public_visibility', true );
        $hidden = 'hide' === $visibility;
        $masked = esc_html__( 'xxx', 'islami-dawa-tools' );
        $show_photo = get_post_meta( $post_id, '_badri_show_photo', true );
        $photo_id = get_post_thumbnail_id( $post_id );

        $fields = array(
            esc_html__( 'পিতা/স্বামীর নাম', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_guardian_name', true ),
            esc_html__( 'মোবাইল', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_mobile', true ),
            esc_html__( 'পেশা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_profession', true ),
            esc_html__( 'অনুদান ধরন', 'islami-dawa-tools' ) => $this->format_frequency( get_post_meta( $post_id, '_badri_donation_frequency', true ) ),
            esc_html__( 'অনুদান', 'islami-dawa-tools' ) => $this->format_amount( get_post_meta( $post_id, '_badri_donation_amount', true ) ),
            esc_html__( 'স্থায়ী জেলা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_permanent_district', true ),
            esc_html__( 'বর্তমান জেলা', 'islami-dawa-tools' ) => get_post_meta( $post_id, '_badri_current_district', true ),
        );
        ?>
        <article class="at-badri-member-card">
            <div class="at-badri-member-avatar <?php echo ( ! $hidden && 'yes' === $show_photo && $photo_id ) ? 'at-badri-member-avatar-image' : ''; ?>">
                <?php if ( ! $hidden && 'yes' === $show_photo && $photo_id ) : ?>
                    <?php echo wp_get_attachment_image( $photo_id, 'thumbnail', false, array( 'alt' => get_the_title( $post_id ) ) ); ?>
                <?php else : ?>
                    <?php echo esc_html( $this->get_initial( get_the_title( $post_id ) ) ); ?>
                <?php endif; ?>
            </div>
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

    private function format_amount( $amount ) {
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
                $new_columns['badri_photo'] = esc_html__( 'ছবি', 'islami-dawa-tools' );
                $new_columns['badri_mobile'] = esc_html__( 'মোবাইল', 'islami-dawa-tools' );
                $new_columns['badri_donation'] = esc_html__( 'অনুদান', 'islami-dawa-tools' );
                $new_columns['badri_visibility'] = esc_html__( 'প্রকাশ', 'islami-dawa-tools' );
            }
        }
        return $new_columns;
    }

    public function render_admin_columns( $column, $post_id ) {
        if ( 'badri_photo' === $column ) {
            $photo_id = get_post_thumbnail_id( $post_id );
            if ( $photo_id ) {
                echo wp_get_attachment_image( $photo_id, array( 48, 48 ) );
            } else {
                echo esc_html( $this->get_initial( get_the_title( $post_id ) ) );
            }
        }

        if ( 'badri_mobile' === $column ) {
            echo esc_html( get_post_meta( $post_id, '_badri_mobile', true ) );
        }

        if ( 'badri_donation' === $column ) {
            echo esc_html( $this->format_amount( get_post_meta( $post_id, '_badri_donation_amount', true ) ) );
        }

        if ( 'badri_visibility' === $column ) {
            $visibility = get_post_meta( $post_id, '_badri_public_visibility', true );
            echo 'hide' === $visibility ? esc_html__( 'গোপন', 'islami-dawa-tools' ) : esc_html__( 'প্রকাশিত', 'islami-dawa-tools' );
        }
    }
}

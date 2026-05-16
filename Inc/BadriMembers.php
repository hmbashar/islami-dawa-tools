<?php
namespace IslamiDawaTools;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class BadriMembers {
    const POST_TYPE = 'badri_member';
    const NONCE_ACTION = 'islami_dawa_badri_member_submit';
    const NONCE_NAME = 'islami_dawa_badri_member_nonce';

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
    );

    public function __construct() {
        add_action( 'init', array( $this, 'register_post_type' ) );
        add_shortcode( 'badri_member_form', array( $this, 'render_form_shortcode' ) );
        add_shortcode( 'badri_members_grid', array( $this, 'render_grid_shortcode' ) );

        add_action( 'admin_post_nopriv_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );
        add_action( 'admin_post_islami_dawa_badri_member_submit', array( $this, 'handle_form_submission' ) );

        add_action( 'add_meta_boxes', array( $this, 'add_member_meta_box' ) );
        add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_member_meta' ), 10, 2 );

        add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'add_admin_columns' ) );
        add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_admin_columns' ), 10, 2 );

        add_filter( 'theme_page_templates', array( $this, 'register_page_templates' ) );
        add_filter( 'template_include', array( $this, 'load_page_template' ) );
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
        );

        register_post_type(
            self::POST_TYPE,
            array(
                'labels'             => $labels,
                'public'             => true,
                'publicly_queryable'  => false,
                'show_ui'            => true,
                'show_in_menu'       => true,
                'show_in_rest'       => true,
                'has_archive'        => false,
                'exclude_from_search'=> true,
                'menu_icon'          => 'dashicons-groups',
                'supports'           => array( 'title' ),
                'capability_type'    => 'post',
                'rewrite'            => false,
            )
        );
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
        ob_start();

        if ( isset( $_GET['badri_member_submitted'] ) && 'success' === sanitize_text_field( wp_unslash( $_GET['badri_member_submitted'] ) ) ) {
            echo '<div class="at-badri-alert at-badri-alert-success">' . esc_html__( 'আপনার তথ্য সফলভাবে জমা হয়েছে। অ্যাডমিন যাচাই করার পর প্রকাশ করা হবে।', 'islami-dawa-tools' ) . '</div>';
        }

        if ( isset( $_GET['badri_member_submitted'] ) && 'error' === sanitize_text_field( wp_unslash( $_GET['badri_member_submitted'] ) ) ) {
            echo '<div class="at-badri-alert at-badri-alert-error">' . esc_html__( 'দুঃখিত, তথ্য জমা দেওয়া যায়নি। অনুগ্রহ করে সব প্রয়োজনীয় তথ্য পূরণ করুন।', 'islami-dawa-tools' ) . '</div>';
        }
        ?>
        <form class="at-badri-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="islami_dawa_badri_member_submit" />
            <?php wp_nonce_field( self::NONCE_ACTION, self::NONCE_NAME ); ?>

            <div class="at-badri-form-header">
                <h2><?php echo esc_html__( 'আজীবন বদরী সদস্য/সদস্যা ফরম', 'islami-dawa-tools' ); ?></h2>
                <p><?php echo esc_html__( 'নিচের তথ্যগুলো পূরণ করে জমা দিন। অ্যাডমিন যাচাই করার পর সদস্য তালিকায় প্রকাশ করা হবে।', 'islami-dawa-tools' ); ?></p>
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

            <div class="at-badri-field at-badri-privacy-field">
                <label><?php echo esc_html__( 'তথ্য প্রকাশের অনুমতি', 'islami-dawa-tools' ); ?> <span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span></label>
                <div class="at-badri-radio-list">
                    <label><input type="radio" name="public_visibility" value="show" required /> <?php echo esc_html__( 'আমার তথ্য প্রকাশ করা যাবে', 'islami-dawa-tools' ); ?></label>
                    <label><input type="radio" name="public_visibility" value="hide" required /> <?php echo esc_html__( 'আমাকে পাবলিক তালিকায় গোপন রাখুন', 'islami-dawa-tools' ); ?></label>
                </div>
                <p><?php echo esc_html__( 'গোপন রাখলে তালিকায় শুধু আপনার নাম দেখা যাবে; বাকি তথ্য xxx হিসেবে দেখানো হবে।', 'islami-dawa-tools' ); ?></p>
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
            <input id="badri_<?php echo esc_attr( $name ); ?>" type="<?php echo esc_attr( $type ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php required( $required ); ?> />
        </div>
        <?php
    }

    private function render_front_textarea_field( $name, $label, $required = false, $placeholder = '' ) {
        ?>
        <label for="badri_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $label ); ?> <?php if ( $required ) : ?><span><?php echo esc_html__( '(Required)', 'islami-dawa-tools' ); ?></span><?php endif; ?></label>
        <textarea id="badri_<?php echo esc_attr( $name ); ?>" name="<?php echo esc_attr( $name ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" <?php required( $required ); ?>></textarea>
        <?php
    }

    public function handle_form_submission() {
        if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::NONCE_ACTION ) ) {
            $this->redirect_with_status( 'error' );
        }

        $captcha = isset( $_POST['badri_captcha'] ) ? absint( $_POST['badri_captcha'] ) : 0;
        if ( 9 !== $captcha ) {
            $this->redirect_with_status( 'error' );
        }

        $member_name = isset( $_POST['member_name'] ) ? sanitize_text_field( wp_unslash( $_POST['member_name'] ) ) : '';
        $required = array( 'guardian_name', 'mobile', 'profession', 'donation_frequency', 'donation_amount', 'donation_amount_text', 'permanent_address', 'current_address', 'public_visibility' );

        if ( empty( $member_name ) ) {
            $this->redirect_with_status( 'error' );
        }

        foreach ( $required as $field ) {
            if ( empty( $_POST[ $field ] ) ) {
                $this->redirect_with_status( 'error' );
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
            $this->redirect_with_status( 'error' );
        }

        $this->save_meta_values_from_request( $post_id );

        $admin_email = get_option( 'admin_email' );
        if ( $admin_email ) {
            wp_mail(
                $admin_email,
                sprintf( esc_html__( 'নতুন বদরী সদস্য আবেদন: %s', 'islami-dawa-tools' ), $member_name ),
                esc_html__( 'একটি নতুন বদরী সদস্য আবেদন জমা হয়েছে। অনুগ্রহ করে অ্যাডমিন থেকে রিভিউ করুন।', 'islami-dawa-tools' )
            );
        }

        $this->redirect_with_status( 'success' );
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
        ?>
        <div class="at-badri-admin-fields">
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
    }

    public function render_grid_shortcode( $atts ) {
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
                <h2><?php echo esc_html__( 'আজীবন বদরী সদস্য/সদস্যা তালিকা', 'islami-dawa-tools' ); ?></h2>
                <p><?php echo esc_html__( 'অ্যাডমিন অনুমোদিত সদস্যদের তালিকা এখানে প্রদর্শিত হচ্ছে।', 'islami-dawa-tools' ); ?></p>
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
            <div class="at-badri-member-avatar"><?php echo esc_html( $this->get_initial( get_the_title( $post_id ) ) ); ?></div>
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
                $new_columns['badri_mobile'] = esc_html__( 'মোবাইল', 'islami-dawa-tools' );
                $new_columns['badri_donation'] = esc_html__( 'অনুদান', 'islami-dawa-tools' );
                $new_columns['badri_visibility'] = esc_html__( 'প্রকাশ', 'islami-dawa-tools' );
            }
        }
        return $new_columns;
    }

    public function render_admin_columns( $column, $post_id ) {
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

<?php
/**
 * Badri Members Grid Page Template
 * Provided by Islami Dawa Tools plugin.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();
?>

<main class="at-badri-template-page at-badri-grid-template-page">
    <?php while ( have_posts() ) : the_post(); ?>
        <section class="at-badri-template-hero">
            <div class="at-badri-template-container">
                <h1><?php the_title(); ?></h1>
                <?php if ( has_excerpt() ) : ?>
                    <p><?php echo esc_html( get_the_excerpt() ); ?></p>
                <?php endif; ?>
            </div>
        </section>

        <?php the_content(); ?>
        <?php echo do_shortcode( '[badri_members_grid]' ); ?>
    <?php endwhile; ?>
</main>

<?php get_footer(); ?>

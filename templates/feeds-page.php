<?php
/**
 * Template for the Feeds page
 *
 * @package AthenaAI
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Get category from query var if available
$category = get_query_var('athena_feed_category', '');
$category_term = !empty($category) ? get_term_by('slug', $category, 'athena-feed-category') : null;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="profile" href="http://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">
    <header id="masthead" class="site-header">
        <div class="site-branding">
            <?php
            if (function_exists('the_custom_logo')) {
                the_custom_logo();
            }
            ?>
            <h1 class="site-title"><a href="<?php echo esc_url(home_url('/')); ?>" rel="home"><?php bloginfo('name'); ?></a></h1>
            <p class="site-description"><?php bloginfo('description'); ?></p>
        </div><!-- .site-branding -->

        <nav id="site-navigation" class="main-navigation">
            <?php
            if (has_nav_menu('primary')) {
                wp_nav_menu(array(
                    'theme_location' => 'primary',
                    'menu_id'        => 'primary-menu',
                ));
            }
            ?>
        </nav><!-- #site-navigation -->
    </header><!-- #masthead -->

    <div id="content" class="site-content">
        <div id="primary" class="content-area">
            <main id="main" class="site-main" role="main">
                <div class="athena-feeds-page">
                    <header class="page-header">
                        <h1 class="page-title">
                            <?php 
                            if ($category_term) {
                                printf(
                                    /* translators: %s: category name */
                                    esc_html__('Feeds: %s', 'athena-ai'),
                                    esc_html($category_term->name)
                                );
                            } else {
                                esc_html_e('All Feeds', 'athena-ai');
                            }
                            ?>
                        </h1>
                    </header>

                    <div class="athena-feed-categories">
                        <h2><?php esc_html_e('Categories', 'athena-ai'); ?></h2>
                        <ul class="athena-feed-category-list">
                            <li class="<?php echo empty($category) ? 'active' : ''; ?>">
                                <a href="<?php echo esc_url(get_permalink(get_page_by_path('athena-feeds'))); ?>">
                                    <?php esc_html_e('All', 'athena-ai'); ?>
                                </a>
                            </li>
                            <?php
                            $categories = get_terms([
                                'taxonomy' => 'athena-feed-category',
                                'hide_empty' => true,
                            ]);
                            
                            foreach ($categories as $cat) {
                                $is_active = $category === $cat->slug;
                                printf(
                                    '<li class="%s"><a href="%s">%s</a></li>',
                                    $is_active ? 'active' : '',
                                    esc_url(home_url('/athena-feeds/category/' . $cat->slug . '/')),
                                    esc_html($cat->name)
                                );
                            }
                            ?>
                        </ul>
                    </div>

                    <div class="athena-feeds-content">
                        <?php echo do_shortcode('[athena_feeds category="' . esc_attr($category) . '" limit="10"]'); ?>
                    </div>
                </div>
            </main><!-- #main -->
        </div><!-- #primary -->

        <?php get_sidebar(); ?>
    </div><!-- #content -->

    <footer id="colophon" class="site-footer">
        <div class="site-info">
            <?php
            printf(
                /* translators: %s: WordPress */
                esc_html__('Proudly powered by %s', 'athena-ai'),
                '<a href="https://wordpress.org/">WordPress</a>'
            );
            ?>
            <span class="sep"> | </span>
            <?php
            printf(
                /* translators: %s: Theme name */
                esc_html__('Theme: %s', 'athena-ai'),
                '<a href="' . esc_url(home_url('/')) . '">' . esc_html(wp_get_theme()->get('Name')) . '</a>'
            );
            ?>
        </div><!-- .site-info -->
    </footer><!-- #colophon -->
</div><!-- #page -->

<?php wp_footer(); ?>
</body>
</html>

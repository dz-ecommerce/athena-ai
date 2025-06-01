<?php
/**
 * Admin dashboard template
 *
 * @package AthenaAI
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
?>
<div class="wrap athena-ai-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
    
    <div class="welcome-panel">
        <div class="welcome-panel-content">
            <h2><?php _e('Welcome to Athena AI!', 'athena-ai'); ?></h2>
            <p class="about-description"><?php _e('Your AI-powered assistant for WordPress content management.', 'athena-ai'); ?></p>
            
            <div class="welcome-panel-column-container">
                <div class="welcome-panel-column">
                    <h3><?php _e('Get Started', 'athena-ai'); ?></h3>
                    <ul>
                        <li>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=athena-ai-profile')); ?>" class="button button-primary">
                                <?php _e('Set Up Your Profile', 'athena-ai'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column">
                    <h3><?php _e('Features', 'athena-ai'); ?></h3>
                    <ul>
                        <li><?php _e('AI-powered content generation', 'athena-ai'); ?></li>
                        <li><?php _e('Product extraction from text', 'athena-ai'); ?></li>
                        <li><?php _e('Smart content recommendations', 'athena-ai'); ?></li>
                    </ul>
                </div>
                
                <div class="welcome-panel-column welcome-panel-last">
                    <h3><?php _e('Documentation', 'athena-ai'); ?></h3>
                    <ul>
                        <li>
                            <a href="#" target="_blank">
                                <?php _e('View Documentation', 'athena-ai'); ?>
                            </a>
                        </li>
                        <li>
                            <a href="#" target="_blank">
                                <?php _e('Get Support', 'athena-ai'); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card">
        <h2 class="title"><?php _e('Plugin Status', 'athena-ai'); ?></h2>
        <p>
            <?php 
            printf(
                __('Athena AI version %s is currently active.', 'athena-ai'),
                '<strong>' . esc_html(ATHENA_AI_VERSION) . '</strong>'
            );
            ?>
        </p>
    </div>
</div> 
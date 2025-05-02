<?php
namespace AthenaAI\Frontend;

/**
 * Feed Widget for displaying feeds in sidebars
 */
class FeedWidget extends \WP_Widget {
    /**
     * Initialize the widget
     */
    public function __construct() {
        parent::__construct('athena_feed_widget', __('Athena AI Feeds', 'athena-ai'), [
            'description' => __('Display RSS feeds from Athena AI', 'athena-ai'),
            'classname' => 'athena-feed-widget',
        ]);
    }

    /**
     * Widget frontend display
     *
     * @param array $args Widget arguments
     * @param array $instance Saved widget values
     */
    public function widget($args, $instance) {
        $title = !empty($instance['title'])
            ? apply_filters('widget_title', $instance['title'])
            : '';
        $category = !empty($instance['category']) ? sanitize_text_field($instance['category']) : '';
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;

        echo $args['before_widget'];

        if ($title) {
            echo $args['before_title'] . esc_html($title) . $args['after_title'];
        }

        // Display feeds using the shortcode
        echo do_shortcode(
            '[athena_feeds category="' . esc_attr($category) . '" limit="' . esc_attr($limit) . '"]'
        );

        echo $args['after_widget'];
    }

    /**
     * Widget backend form
     *
     * @param array $instance Previously saved values
     * @return void
     */
    public function form($instance) {
        $title = !empty($instance['title']) ? sanitize_text_field($instance['title']) : '';
        $category = !empty($instance['category']) ? sanitize_text_field($instance['category']) : '';
        $limit = !empty($instance['limit']) ? absint($instance['limit']) : 5;

        // Get all feed categories
        $categories = get_terms([
            'taxonomy' => 'athena-feed-category',
            'hide_empty' => false,
        ]);
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'athena-ai'); ?>
            </label>
            <input class="widefat" 
                   id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" 
                   value="<?php echo esc_attr($title); ?>">
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('category')); ?>">
                <?php esc_html_e('Category:', 'athena-ai'); ?>
            </label>
            <select class="widefat" 
                    id="<?php echo esc_attr($this->get_field_id('category')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('category')); ?>">
                <option value="" <?php selected($category, ''); ?>>
                    <?php esc_html_e('All Categories', 'athena-ai'); ?>
                </option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?php echo esc_attr($cat->slug); ?>" <?php selected(
    $category,
    $cat->slug
); ?>>
                        <?php echo esc_html($cat->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="<?php echo esc_attr($this->get_field_id('limit')); ?>">
                <?php esc_html_e('Number of feeds to show:', 'athena-ai'); ?>
            </label>
            <input class="tiny-text" 
                   id="<?php echo esc_attr($this->get_field_id('limit')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('limit')); ?>" 
                   type="number" 
                   step="1" 
                   min="1" 
                   max="20" 
                   value="<?php echo esc_attr($limit); ?>">
        </p>
        <?php
    }

    /**
     * Save widget options
     *
     * @param array $new_instance New widget values
     * @param array $old_instance Previous widget values
     * @return array Updated widget values
     */
    public function update($new_instance, $old_instance) {
        $instance = [];
        $instance['title'] = !empty($new_instance['title'])
            ? sanitize_text_field($new_instance['title'])
            : '';
        $instance['category'] = !empty($new_instance['category'])
            ? sanitize_text_field($new_instance['category'])
            : '';
        $instance['limit'] = !empty($new_instance['limit']) ? absint($new_instance['limit']) : 5;

        return $instance;
    }
}

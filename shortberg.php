<?php
/**
 * Plugin Name:       ShortBerg
 * Description:       Manage your Gutenberg templates and put any templates anywhere 
 * Version:           1.0.0
 * Author:            Dwindi Ramadhana
 * Author URI:        https://dwindi.com
 * Text Domain:       shortberg
 * Donate Link:       https://paypal.me/dwindown
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v3.0
 * 
 * Requires at least: 6.0.0
 * Tested up to: 6.6.2
 *
 * Copyright: © 2024 Dwindi Ramadhana.
 * License: GNU General Public License v2.0
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

define( 'SHORTBERG_NAME', 'ShortBerg' );
define( 'SHORTBERG_DOMAIN', 'shortberg' );
define( 'SHORTBERG_BASENAME', plugin_basename(__FILE__));
define( 'SHORTBERG_VERSION', '1.0.0' );
define( 'SHORTBERG_URL', plugin_dir_url( __FILE__ ) );
define( 'SHORTBERG_PATH', plugin_dir_path( __FILE__ ) );

if (!class_exists('ShortBerg')) {
    class ShortBerg {

        public function __construct() {
            add_action('init', [$this, 'cpt']);
            add_shortcode('berg', [$this, 'shortcode']);
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
            add_filter('manage_shortberg_posts_columns', [$this, 'set_custom_columns']);
            add_action('manage_shortberg_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
            add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
            add_action('wp_enqueue_scripts', [$this, 'wp_scripts']);
        }

        public function cpt() {
            $labels = array (
                "name"          => _x('Templates', 'shortberg'),
                "singular_name" => _x('Template', 'shortberg'),
                "menu_name"     => _x('ShortBerg', 'shortberg'),
                'add_new'       => __('Add New', 'shortberg'),
                'add_new_item'  => __('Add New Template', 'shortberg'),
                'new_item'      => __('New Template', 'shortberg'),
                'edit_item'     => __('Edit Template', 'shortberg'),
                'view_item'     => __('View Template', 'shortberg'),
                'all_items'     => __('All Templates', 'shortberg'),
                'search_items'  => __('Search Template', 'shortberg'),
                'not_found'     => __('No templates found.', 'shortberg'),
                'not_found_in_trash' => __('No templates found in Trash.', 'shortberg'),
            );

            $args = array (
                "label"         => esc_html__('Templates', 'shortberg'),
                "labels"        => $labels,
                "public"        => false,
                "publicly_queryable" => false,
                "show_ui"       => true,
                'rewrite'       => ['slug' => 'shortberg'],
                'show_in_rest'  => true,
                "has_archive"   => true,
                "show_in_menu"  => true,
                "show_in_nav_menus"     => true,
                "delete_with_user"      => false,
                "exclude_from_search"   => false,
                "capability_type"       => "post",
                "map_meta_cap"  => true,
                "hierarchical"  => false,
                "can_export"    => true,
                "menu_position" => 15,
                "menu_icon"     => 'dashicons-shortcode',
                "supports"      => ["title", "editor", "thumbnail"]
            );

            register_post_type("shortberg", $args);
        }

        public function shortcode($atts) {

            $atts = shortcode_atts(['id' => null], $atts, 'berg');
            $post_id = intval($atts['id']);
        
            ob_start();
        
            $content_post = get_post($post_id);
            
            if ($content_post) {
                $content = apply_filters('the_content', $content_post->post_content);
                echo wp_kses_post($content);
            } else {
                echo esc_html__('Template not found.', 'shortberg');
            }
        
            return ob_get_clean();
        }

        public function add_meta_boxes() {
            add_meta_box('berg_meta', esc_html__('Template Shortcode', 'shortberg'), [$this, 'render_meta_box'], 'shortberg', 'side', 'default');
        }

        public function render_meta_box($post) {
        
            // Retrieve the existing shortcode value
            $template_id = esc_html($post->ID);
            $full_shortcode = sprintf('[berg id="%d"]', intval($template_id));
        
            // Prepare the HTML output
            $output = sprintf(
                '<div class="components-input-control__container shortberg-box">
                    <input autocomplete="off" spellcheck="false" aria-describedby="inspector-input-control-%d__help" class="components-input-control__input" id="inspector-input-control-%d" type="text" value="%s" readonly>
                    <span class="components-input-control__suffix">
                        <button type="button" class="components-button has-icon copy-button" aria-label="%s" data-clipboard-text="%s">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"></path>
                            </svg>
                        </button>
                    </span>
                    <div aria-hidden="true" class="components-input-control__backdrop"></div>
                </div>',
                intval($template_id), // for id attribute
                intval($template_id), // for id attribute
                esc_attr($full_shortcode), // for input value
                esc_attr__('Copy', 'shortberg'), // for button aria-label
                esc_attr($full_shortcode) // for data-clipboard-text
            );
        
            // Output the final HTML
            echo $output;
        }

        public function admin_scripts() {
            wp_enqueue_style('shortberg-style', SHORTBERG_URL . 'assets/style.css', [], SHORTBERG_VERSION, 'all');
            wp_enqueue_script('shortberg-copy-interaction', SHORTBERG_URL . 'assets/copy.js', [], SHORTBERG_VERSION, true );
        }        

        public function wp_scripts() {
            global $post;

            // Check if we are in the main query and if the post content contains the shortcode
            if (is_main_query() && !is_admin() && has_shortcode($post->post_content, 'berg')) {
                // Get all IDs of shortberg posts
                preg_match_all('/\[berg id="(\d+)"\]/', $post->post_content, $matches);
                $shortberg_ids = array_unique($matches[1]);
        
                foreach ($shortberg_ids as $shortberg_id) {
                    if (class_exists('ABlocks')) {
                        wp_enqueue_style(
                            sprintf( 'ablocks-block-combine-shortberg-%d', esc_html($shortberg_id) ), 
                            site_url('/wp-content/uploads/ablocks_uploads/' . esc_html($shortberg_id) . '.min.css'), 
                            [], time(), 'all'
                        );
        
                        wp_enqueue_script(
                            sprintf('ablocks-block-combine-shortberg-%d', esc_html($shortberg_id)),
                            site_url('/wp-content/uploads/ablocks_uploads/' . esc_html($shortberg_id) . '.min.js'), 
                            [], time(), true
                        );
                    }
                }
            }
        }

        public function set_custom_columns($columns) {
            $columns['shortcode'] = esc_html__('Shortcode', 'shortberg');
            return $columns;
        }

        public function custom_column_content($column, $post_id) {
            if ($column == 'shortcode') {
                $template_id = esc_html($post_id);
                $full_shortcode = sprintf('[berg id="%s"]', $template_id);
                
                // Prepare the HTML output
                $output = sprintf(
                    '<div class="components-input-control__container shortberg-box">
                        <input autocomplete="off" spellcheck="false" aria-describedby="inspector-input-control-%d__help" class="components-input-control__input" id="inspector-input-control-%d" type="text" value="%s" readonly>
                        <span class="components-input-control__suffix">
                            <button type="button" class="components-button has-icon copy-button" aria-label="%s" data-clipboard-text="%s">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"></path>
                                </svg>
                            </button>
                        </span>
                        <div aria-hidden="true" class="components-input-control__backdrop"></div>
                    </div>',
                    esc_attr($template_id), // for id attribute
                    esc_attr($template_id), // for id attribute
                    esc_attr($full_shortcode), // for input value
                    esc_attr__('Copy', 'shortberg'), // for button aria-label
                    esc_attr($full_shortcode) // for data-clipboard-text
                );
        
                // Output the final HTML
                echo $output;
            }
        }

    }

    new ShortBerg();
}

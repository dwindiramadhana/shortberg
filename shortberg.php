<?php
/**
 * Plugin Name:       ShortBerg
 * Description:       Manage your Gutenberg templates and put any templates anywhere 
 * Version:           1.0.0
 * Author:            Dwindi Ramadhana
 * Author URI:        https://facebook.com/dwindi.ramadhana
 * Text Domain:       shortberg
 * Domain Path:       /languages
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 * 
 * Requires at least: 6.0.0
 * Tested up to: 6.6.2
 *
 * Copyright: © 2024 Dwindi Ramadhana.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
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
            add_action('save_post', [$this, 'save_meta_boxes']);
            add_filter('manage_shortberg_posts_columns', [$this, 'set_custom_columns']);
            add_action('manage_shortberg_posts_custom_column', [$this, 'custom_column_content'], 10, 2);
            add_action('template_redirect', [$this, 'template_redirect']);

            // Enqueue scripts for Gutenberg editor
            add_action('admin_enqueue_scripts', [$this, 'admin_scripts']);
            add_action('wp_enqueue_scripts', [$this, 'wp_scripts']);
        }

        public function cpt() {
            $labels = array (
                "name" => _x("Templates", 'shortberg'),
                "singular_name" => _x("Template", 'shortberg'),
                "menu_name" => _x("ShortBerg", 'shortberg'),
                'add_new' => __('Add New', 'shortberg'),
                'add_new_item' => __('Add New Template', 'shortberg'),
                'new_item' => __('New Template', 'shortberg'),
                'edit_item' => __('Edit Template', 'shortberg'),
                'view_item' => __('View Template', 'shortberg'),
                'all_items' => __('All Templates', 'shortberg'),
                'search_items' => __('Search Template', 'shortberg'),
                'not_found' => __('No templates found.', 'shortberg'),
                'not_found_in_trash' => __('No templates found in Trash.', 'shortberg'),
            );

            $args = array (
                "label" => esc_html__("Templates", 'shortberg'),
                "labels" => $labels,
                "description" => "",
                "public" => false,
                "publicly_queryable" => false,
                "show_ui" => true,
                'rewrite' => array('slug' => 'shortberg'),
                'show_in_rest' => true,
                "has_archive" => true,
                "show_in_menu" => true,
                "show_in_nav_menus" => true,
                "delete_with_user" => false,
                "exclude_from_search" => false,
                "capability_type" => "post",
                "map_meta_cap" => true,
                "hierarchical" => false,
                "can_export" => true,
                "menu_position" => 15,
                "menu_icon" => 'dashicons-shortcode',
                "supports" => ["title", "editor", "thumbnail"]
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
            add_meta_box('berg_meta', __('Template Details', 'shortberg'), [$this, 'render_meta_box'], 'shortberg', 'side', 'default');
        }

        public function render_meta_box($post) {
            wp_nonce_field('berg_meta_box', 'berg_meta_box_nonce');
        
            // Retrieve the existing shortcode value
            $template_id = esc_html($post->ID);
            $full_shortcode = '[berg id="' . intval($template_id) . '"]';
        
            echo '<div class="components-input-control__container shortberg-box">
                    <input autocomplete="off" spellcheck="false" aria-describedby="inspector-input-control-' . intval($template_id) . '__help" class="components-input-control__input" id="inspector-input-control-' . intval($template_id) . '" type="text" value="' . esc_attr($full_shortcode) . '" readonly>
                    <span class="components-input-control__suffix">
                        <button type="button" class="components-button has-icon copy-button" aria-label="Copy" data-clipboard-text="' . esc_attr($full_shortcode) . '">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                <path fill-rule="evenodd" clip-rule="evenodd" d="M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"></path>
                            </svg>
                        </button>
                    </span>
                    <div aria-hidden="true" class="components-input-control__backdrop"></div>
                  </div>';
        }

        public function admin_scripts() {
            wp_enqueue_style('shortberg-style', SHORTBERG_URL . 'assets/style.css', [], SHORTBERG_VERSION, 'all');
            wp_enqueue_script('copy-to-clipboard', 'https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.6/clipboard.min.js', [], '2.0.6', true);
            wp_enqueue_script('copy-interaction', SHORTBERG_URL . 'assets/copy.js', [], SHORTBERG_VERSION, true );
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
                            'ablocks-block-combine-shortberg-' . $shortberg_id, 
                            site_url('/wp-content/uploads/ablocks_uploads/' . $shortberg_id . '.min.css'), 
                            [], time(), 'all'
                        );
        
                        wp_enqueue_script(
                            'ablocks-block-combine-shortberg-' . $shortberg_id,
                            site_url('/wp-content/uploads/ablocks_uploads/' . $shortberg_id . '.min.js'), 
                            [], time(), true
                        );
                    }
                }
            }
        }
        

        public function save_meta_boxes($post_id) {
            // Check if the nonce is set
			if (!isset($_POST['berg_meta_box_nonce'])) {
				return; // Nonce not set, exit the function
			}

			// Verify the nonce
			if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['berg_meta_box_nonce'])), 'berg_meta_box')) {
				return; // Nonce verification failed, exit the function
			}
			
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return;
            }
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }

            // Save any additional fields here if needed
        }

        public function set_custom_columns($columns) {
            $columns['shortcode'] = __('Shortcode', 'shortberg');
            return $columns;
        }

        public function custom_column_content($column, $post_id) {
            if ($column == 'shortcode') {
                $template_id = esc_html($post_id);
                $full_shortcode = '[berg id="' . esc_html($template_id) . '"]';
            
                echo '<div class="components-input-control__container shortberg-box">
                        <input autocomplete="off" spellcheck="false" aria-describedby="inspector-input-control-' . intval($template_id) . '__help" class="components-input-control__input" id="inspector-input-control-' . intval($template_id) . '" type="text" value="' . esc_attr($full_shortcode) . '" readonly>
                        <span class="components-input-control__suffix">
                            <button type="button" class="components-button has-icon copy-button" aria-label="Copy" data-clipboard-text="' . esc_attr($full_shortcode) . '">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
                                    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.625 5.5h9.75c.069 0 .125.056.125.125v9.75a.125.125 0 0 1-.125.125h-9.75a.125.125 0 0 1-.125-.125v-9.75c0-.069.056-.125.125-.125ZM4 5.625C4 4.728 4.728 4 5.625 4h9.75C16.273 4 17 4.728 17 5.625v9.75c0 .898-.727 1.625-1.625 1.625h-9.75A1.625 1.625 0 0 1 4 15.375v-9.75Zm14.5 11.656v-9H20v9C20 18.8 18.77 20 17.251 20H6.25v-1.5h11.001c.69 0 1.249-.528 1.249-1.219Z"></path>
                                </svg>
                            </button>
                        </span>
                        <div aria-hidden="true" class="components-input-control__backdrop"></div>
                    </div>';
            }
        }

        public function template_redirect() {
            if (is_post_type_archive('shortberg') || is_singular('shortberg')) {
                // Load a custom template if needed
                load_template(plugin_dir_path(__FILE__) . 'template-archive.php');
                exit;
            }
        }
    }

    new ShortBerg();
}

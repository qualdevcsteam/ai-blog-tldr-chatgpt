<?php
/**
 * Plugin Name: AI Blog TL;DR Generator (ChatGPT)
 * Plugin URI: https://qualdev.com
 * Description: Auto-generates TL;DR summaries using ChatGPT API
 * Version: 2.0.0
 * Author: Qualdev.com
 * License: GPL v2 or later
 * Requires at least: 5.0
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('AI_BLOG_TLDR_VERSION', '1.0.0');
define('AI_BLOG_TLDR_DIR', plugin_dir_path(__FILE__));
define('AI_BLOG_TLDR_URL', plugin_dir_url(__FILE__));

require_once AI_BLOG_TLDR_DIR . 'includes/class-tldr.php';
require_once AI_BLOG_TLDR_DIR . 'includes/class-admin.php';

function ai_blog_tldr_init() {
    new AI_Blog_TLDR();
    new AI_Blog_TLDR_Admin();
}
add_action('plugins_loaded', 'ai_blog_tldr_init');

function ai_blog_tldr_activate() {
    $default_settings = array(
        'openai_api_key' => '',
        'enable_tldr' => 1,
        'enable_buttons' => 1,
        'cache_hours' => 24,
    );
    if (!get_option('ai_blog_tldr_settings')) {
        update_option('ai_blog_tldr_settings', $default_settings);
    }
}
register_activation_hook(__FILE__, 'ai_blog_tldr_activate');

function ai_blog_tldr_deactivate() {
    global $wpdb;
    $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '%ai_tldr_%'");
}
register_deactivation_hook(__FILE__, 'ai_blog_tldr_deactivate');

function display_ai_blog_tldr() {
    $tldr = new AI_Blog_TLDR();
    echo $tldr->display();
}

add_shortcode('ai_blog_tldr', function() {
    $tldr = new AI_Blog_TLDR();
    return $tldr->display();
});
?>

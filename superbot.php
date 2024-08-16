<?php
/*
 * Plugin Name:       Super Bot
 * Description:       This is a bot assistant, which also powered by Google Gemini API. It can reply against queries 24x7 to website visitors.
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Shimanta Das
 * Author URI:        https://microcodes.in/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Text Domain:       bot, super bot, chatbot 
 */

if (!defined('ABSPATH')) {
    die("You are restricted to access this page!");
}

require ('functions.php');

// enqueue css and js scripts
function superbot_enqueue_assets()
{
    // Enqueue Google Fonts
    wp_enqueue_style('superbot-material-icons-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0', [], null);
    wp_enqueue_style('superbot-material-icons-rounded', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@48,400,1,0', [], null);

    // // Enqueue plugin styles and scripts
    wp_enqueue_style('superbot-style', plugins_url('assets/css/style.css', __FILE__), [], '1.0.0');
    wp_enqueue_script('superbot-jquery', plugins_url('assets/js/jquery.js', __FILE__), ['jquery'], '1.0.0', true);
    wp_enqueue_script('superbot-script', plugins_url('assets/js/script.js', __FILE__), ['jquery'], '1.0.0', true);

    // Pass the AJAX URL to the script
    wp_localize_script(
        'superbot-script',
        'superbot_ajax',
        array(
            'ajax_url' => admin_url('admin-ajax.php')
        )
    );
}
add_action('wp_enqueue_scripts', 'superbot_enqueue_assets');

// activation hook
function superbot_activate()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'chats';

    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            question VARCHAR(250) NOT NULL,
            answer LONGTEXT NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    $table_name = $wpdb->prefix . 'chat_terms';
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) NOT NULL AUTO_INCREMENT,
            chatid INT NOT NULL,
            tag VARCHAR(50) NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Create the chat_history table if it doesn't exist
    $history_table_name = $wpdb->prefix . 'chat_history';
    if ($wpdb->get_var("SHOW TABLES LIKE '$history_table_name'") != $history_table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $history_table_name (
              id BIGINT(20) NOT NULL AUTO_INCREMENT,
              query VARCHAR(200) NOT NULL,
              gemini_reply LONGTEXT NOT NULL,
              ip_address VARCHAR(50) NULL,
              location VARCHAR(200) NULL,
              date VARCHAR(50) NOT NULL,
              PRIMARY KEY (id)
          ) $charset_collate;";

        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Create the chat_global_settings table if it doesn't exist
    $history_table_name = $wpdb->prefix . 'chat_global_settings';
    if ($wpdb->get_var("SHOW TABLES LIKE '$history_table_name'") != $history_table_name) {
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $history_table_name (
              id BIGINT(20) NOT NULL AUTO_INCREMENT,
              gemini_key VARCHAR(200) NULL,
              contact_us_link VARCHAR(200) NULL,
              business_name VARCHAR(200) NULL,
              business_description VARCHAR(250) NULL,
              restriction VARCHAR(200) NULL,
              PRIMARY KEY (id)
          ) $charset_collate;";

        require_once (ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

}
register_activation_hook(__FILE__, 'superbot_activate');

// add menu to admin panel page
function superbot_pages_register()
{
    $plugin_slug = "chat_admin";

    add_menu_page('Super Bot', 'Super Bot', 'edit', $plugin_slug, null, plugins_url('icon.png', __FILE__), '58', );

    add_submenu_page(
        $plugin_slug,
        'Dashboard',
        'Dashboard',
        'manage_options',
        'chatbot_dashboard',
        'dashboard_function'
    );

    add_submenu_page(
        $plugin_slug,
        'Add/Edit Replies',
        'Add/Edit Replies',
        'manage_options',
        'reply_edit_remove',
        'reply_function'
    );

    add_submenu_page(
        $plugin_slug,
        'Unreserved Queries',
        'Unreserved Queries',
        'manage_options',
        'unreserved_queries',
        'unreserved_query_function'
    );

    add_submenu_page(
        $plugin_slug,
        'Settings',
        'Settings',
        'manage_options',
        'chat_global_settings',
        'chat_global_settings_function'
    );
}
add_action('admin_menu', 'superbot_pages_register');

function dashboard_function()
{
    require (plugin_dir_path(__FILE__) . 'admin/dashboard.php');
}
function reply_function()
{
    require (plugin_dir_path(__FILE__) . 'admin/replies.php');
}

function unreserved_query_function()
{
    require (plugin_dir_path(__FILE__) . 'admin/unreserved.php');
}

function chat_global_settings_function()
{
    require (plugin_dir_path(__FILE__) . 'settings.php');
}


// enable superbot to right-footer area.
function superbot_chatbot_markup()
{
    ?>
    <!-- floating bot icon -->
    <button class="chatbot-toggler">
        <span class="material-symbols-rounded">mode_comment</span>
        <span class="material-symbols-outlined">close</span>
    </button>
    <div class="chatbot">
        <header>
            <h2> SuperBot </h2>
            <span class="close-btn material-symbols-outlined">close</span>
        </header>
        <ul class="chatbox">
            <li class="chat incoming">
                <span class="material-symbols-outlined">smart_toy</span>
                <p>Hi there 👋<br>How can I help you today?</p>
            </li>
        </ul>
        <div class="chat-input">
            <textarea placeholder="Enter a message..." spellcheck="false" required></textarea>
            <span id="send-btn" class="material-symbols-rounded">send</span>
        </div>
    </div>

    <?php
}
add_action('wp_footer', 'superbot_chatbot_markup');



?>
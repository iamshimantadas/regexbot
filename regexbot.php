<?php
/*
 * Plugin Name:       REGEX Bot
 * Description:       This is a bot assistant, which also powered by Google Gemini API. It can reply against queries 24x7 to website visitors.
 * Version:           1.1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Shimanta Das
 * Author URI:        https://microcodes.in/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html
 * Text Domain:       bot, regex bot, 
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
function register_options_page()
{
    $plugin_slug = "chat_admin";

    add_menu_page('REGEX Bot', 'REGEX Bot', 'edit', $plugin_slug, null, plugins_url('icon.png', __FILE__), '58', );

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
add_action('admin_menu', 'register_options_page');

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

// find important words
function extractImportantWords($sentence)
{
    // Define a list of common stop words
    $stopWords = array(
        'i',
        'me',
        'my',
        'myself',
        'we',
        'our',
        'ours',
        'ourselves',
        'you',
        'your',
        'yours',
        'yourself',
        'yourselves',
        'he',
        'him',
        'his',
        'himself',
        'she',
        'her',
        'hers',
        'herself',
        'it',
        'its',
        'itself',
        'they',
        'them',
        'their',
        'theirs',
        'themselves',
        'what',
        'which',
        'who',
        'whom',
        'this',
        'that',
        'these',
        'those',
        'am',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'being',
        'have',
        'has',
        'had',
        'having',
        'do',
        'does',
        'did',
        'doing',
        'a',
        'an',
        'the',
        'and',
        'but',
        'if',
        'or',
        'because',
        'as',
        'until',
        'while',
        'of',
        'at',
        'by',
        'for',
        'with',
        'about',
        'against',
        'between',
        'into',
        'through',
        'during',
        'before',
        'after',
        'above',
        'below',
        'to',
        'from',
        'up',
        'down',
        'in',
        'out',
        'on',
        'off',
        'over',
        'under',
        'again',
        'further',
        'then',
        'once',
        'here',
        'there',
        'when',
        'where',
        'why',
        'how',
        'all',
        'any',
        'both',
        'each',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'nor',
        'not',
        'only',
        'own',
        'same',
        'so',
        'than',
        'too',
        'very',
        's',
        't',
        'can',
        'will',
        'just',
        'don',
        'should',
        'now'
    );

    // Convert the sentence to lowercase and split into words
    $words = preg_split('/\s+/', strtolower($sentence));

    // Remove any non-word characters
    $words = array_map(function ($word) {
        return preg_replace('/[^\w]/', '', $word);
    }, $words);

    // Filter out stop words
    $importantWords = array_filter($words, function ($word) use ($stopWords) {
        return !in_array($word, $stopWords) && !empty($word);
    });

    // Return the important words
    return array_values($importantWords);
}

// ajax response handelling
function superbot_search_answer()
{
    global $wpdb;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {

        $userInput = sanitize_text_field($_POST['userInput']);
        
        // check string lengh first
        if(strlen($userInput) > 180){
            echo $response = "Your query length is very big! reduce it!";
            exit;
        }

        $response = "I'm sorry, I don't understand the question.";
        $table_name = $wpdb->prefix . 'chats';

        // Prepare the SQL query
        $sql = $wpdb->prepare(
            "SELECT answer FROM $table_name WHERE question REGEXP %s LIMIT 1",
            $userInput
        );

        // Execute the query
        $result = $wpdb->get_row($sql);

        if ($result) {
            $response = $result->answer;


            try {
                $ip = getenv('REMOTE_ADDR');

                // Check if the IP is localhost
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $location = "Localhost";
                } else {
                    $url = "https://freeipapi.com/api/json/$ip";
                    $data = file_get_contents($url);
                    $data = json_decode($data, true);

                    if ($data['countryName']) {
                        $countryName = $data['countryName'];
                        $cityName = $data['cityName'];
                        $regionName = $data['regionName'];
                        $zipCode = $data['zipCode'];

                        // Create the response string
                        $location = "Country: $countryName, City: $cityName, Region: $regionName, Zip Code: $zipCode";
                    } else {
                        $location = NULL;
                    }
                }
            } catch (Exception $e) {
                $location = NULL;
                $ip = NULL;
                error_log($e->getMessage());
            }

            $table = $wpdb->prefix . "chat_history";
            $wpdb->insert(
                $table,
                array(
                    "query" => $userInput,
                    "date" => date("Y/m/d"),
                    "ip_address" => $ip,
                    "location" => $location,
                )
            );

        } else {

            $table_name = $wpdb->prefix . 'chats';
            $importantWords = extractImportantWords($userInput);
            $i = 0;
            $count = 0;
            while ($i < sizeof($importantWords)) {
                $sql = $wpdb->prepare(
                    "SELECT answer FROM $table_name WHERE question = %s LIMIT 1",
                    $importantWords[$i]
                );
                $result = $wpdb->get_row($sql);
                if ($result) {
                    $count++;
                    $response = $result->answer;
                }
                $i++;
            }

            // if wp_chats table has no word related strings then goes to wp_terms
            if ($count == 0) {

                $table_name = $wpdb->prefix . 'chat_terms';
                // passing question to get the important words -> returns an array
                $importantWords = extractImportantWords($userInput);
                $i = 0;
                while ($i < sizeof($importantWords)) {
                    $sql = $wpdb->prepare(
                        "SELECT chatid FROM $table_name WHERE tag='%s'",
                        $importantWords[$i]
                    );
                    $result = $wpdb->get_row($sql);
                    if ($result) {
                        $chat_id = $result->chatid;
                    }
                    $i++;
                }

                // getting answer
                if ($chat_id) {
                    $count = 0;
                    $table_name = $wpdb->prefix . 'chats';
                    $result = $wpdb->get_row($wpdb->prepare("SELECT answer FROM $table_name WHERE id = %d", $chat_id));
                    if ($result) {
                        $response = $result->answer;
                    }


                    try {
                        $ip = getenv('REMOTE_ADDR');

                        // Check if the IP is localhost
                        if ($ip === '127.0.0.1' || $ip === '::1') {
                            $location = "Localhost";
                        } else {
                            $url = "https://freeipapi.com/api/json/$ip";
                            $data = file_get_contents($url);
                            $data = json_decode($data, true);

                            if ($data['countryName']) {
                                $countryName = $data['countryName'];
                                $cityName = $data['cityName'];
                                $regionName = $data['regionName'];
                                $zipCode = $data['zipCode'];

                                // Create the response string
                                $location = "Country: $countryName, City: $cityName, Region: $regionName, Zip Code: $zipCode";
                            } else {
                                $location = NULL;
                            }
                        }
                    } catch (Exception $e) {
                        $location = NULL;
                        $ip = NULL;
                        error_log($e->getMessage());
                    }

                    $table = $wpdb->prefix . "chat_history";
                    $wpdb->insert(
                        $table,
                        array(
                            "query" => $userInput,
                            "date" => date("Y/m/d"),
                            "ip_address" => $ip,
                            "location" => $location,
                        )
                    );

                } else {
                    $count = 0;

                    // if no terms found against user's query! then we will hit google's gemini api
                    try {
                        global $wpdb;
                        $table = $wpdb->prefix . "chat_global_settings";
                        $arr = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");

                        $apiKey = $arr[0]->gemini_key;
                        $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
                        $business_name = $arr[0]->business_name;
                        $business_description = $arr[0]->business_description;
                        $common = "you should reply as for $business_name, $business_description";
                        $restrictions = $arr[0]->restriction;
                        $contact_page = $arr[0]->contact_us_link;


                        $data = json_encode([
                            'contents' => [
                                [
                                    'parts' => [
                                        [
                                            'text' => "$userInput, $common, $restrictions"
                                        ]
                                    ]
                                ]
                            ]
                        ]);

                        $ch = curl_init($apiUrl . '?key=' . $apiKey);
                        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                        curl_setopt($ch, CURLOPT_HTTPHEADER, [
                            'Content-Type: application/json'
                        ]);
                        curl_setopt($ch, CURLOPT_POST, true);
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                        $response = curl_exec($ch);
                        $responseArray = json_decode($response, true);
                        // if response occur any error!
                        if ($responseArray['error']) {
                            if ($contact_page) {
                                $response = "Feel free to <b><a href='$contact_page' target='_blank'>contact us</a></b>! We will contact you shortly.";
                            } else {
                                $response = "We can't understand your query! Please correct your query!";
                            }
                        }

                        if (isset($responseArray['candidates'][0]['content']['parts'][0]['text'])) {
                            $response = $responseArray['candidates'][0]['content']['parts'][0]['text'];
                            $response = preg_replace('/\*\*|\*/', '', $response);
                            if ($contact_page) {
                                $response = $response . ". Feel free to <b><a href='$contact_page' target='_blank'>contact us</a></b>";
                            }


                            try {
                                $ip = getenv('REMOTE_ADDR');

                                // Check if the IP is localhost
                                if ($ip === '127.0.0.1' || $ip === '::1') {
                                    $location = "Localhost";
                                } else {
                                    $url = "https://freeipapi.com/api/json/$ip";
                                    $data = file_get_contents($url);
                                    $data = json_decode($data, true);

                                    if ($data['countryName']) {
                                        $countryName = $data['countryName'];
                                        $cityName = $data['cityName'];
                                        $regionName = $data['regionName'];
                                        $zipCode = $data['zipCode'];

                                        // Create the response string
                                        $location = "Country: $countryName, City: $cityName, Region: $regionName, Zip Code: $zipCode";
                                    } else {
                                        $location = NULL;
                                    }
                                }
                            } catch (Exception $e) {
                                $location = NULL;
                                $ip = NULL;
                                error_log($e->getMessage());
                            }



                            // store information into 
                            $table = $wpdb->prefix . "chat_history";
                            $wpdb->insert(
                                $table,
                                array(
                                    "query" => $userInput,
                                    "date" => date("Y/m/d"),
                                    "gemini_reply" => $response,
                                    "ip_address" => $ip,
                                    "location" => $location,
                                )
                            );


                        }

                        curl_close($ch);


                    } catch (Exception $e) {
                        echo $e->getMessage();
                    }

                }
            }

        }

        echo $response;


        wp_die();
    }
}
add_action('wp_ajax_search_answer', 'superbot_search_answer');
add_action('wp_ajax_nopriv_search_answer', 'superbot_search_answer');



?>
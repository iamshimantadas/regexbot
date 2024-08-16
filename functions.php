<?php


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

                    if (is_array($data) && isset($data['countryName'])) {
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

                            if (is_array($data) && isset($data['countryName'])) {
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
                        if (is_array($responseArray) && isset($responseArray['error'])) {
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

                                    if (is_array($data) && isset($data['countryName'])) {
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




/**
 * hook - save_query helps to save query, response and tags, inside page=reply_edit_remove
 */
add_action('wp_ajax_save_query', 'saveQuery');
add_action('wp_ajax_nopriv_save_query', 'saveQuery');
function saveQuery()
{


    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = $_POST['editor'];
    $html = str_replace('\\', '', $html);
    $tags = explode(',', sanitize_text_field($_POST['tags']));


    try {
        $table = $wpdb->prefix . "chats";
        $wpdb->insert(
            $table,
            array(
                "question" => $query,
                "answer" => $html,
            )
        );
        // getting inserted record last id
        $id = $wpdb->insert_id;

        if ($_POST['tags']) {
            $i = 0;
            while ($i < sizeof($tags)) {
                $tag = trim($tags[$i]);
                $tag = str_replace('\\', '', $tag);
                $table = $wpdb->prefix . "chat_terms";
                $wpdb->insert(
                    $table,
                    array(
                        "chatid" => $id,
                        "tag" => $tag,
                    )
                );
                $i++;
            }
        }

        $res = true;

    } catch (Exception $e) {
        $res = false;
        echo $e->getMessage();
    }

    if ($res) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}


/**
 * hook - get_reply helps to fetch summercode exitor data and query and tags data in update form, inside page=reply_edit_remove
 */
add_action('wp_ajax_get_reply', 'getQuery');
add_action('wp_ajax_nopriv_get_reply', 'getQuery');
function getQuery()
{
    global $wpdb;

    $id = sanitize_text_field($_POST['id']);
    $table = $wpdb->prefix . "chats";

    $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id));

    if ($result) {
        $tags_table = $wpdb->prefix . "chat_terms";
        $tags = $wpdb->get_results($wpdb->prepare("SELECT tag FROM $tags_table WHERE chatid = %d", $id), ARRAY_A);
        $tags = array_column($tags, 'tag');

        echo json_encode(
            array(
                'question' => $result->question,
                'answer' => $result->answer,
                'tags' => $tags
            )
        );
    } else {
        echo json_encode(array('error' => 'No record found'));
    }

    exit;
}


/**
 * hook - update_query helps to update query , inside page=reply_edit_remove
 */
add_action('wp_ajax_update_query', 'updateQuery');
add_action('wp_ajax_nopriv_update_query', 'updateQuery');
function updateQuery()
{
    // print_r($_POST);

    global $wpdb;

    $query = sanitize_text_field($_POST['query']);
    $html = $_POST['editor'];
    $html = str_replace('\\', '', $html);
    $chatid = sanitize_text_field($_POST['chatid']);
    $tags = explode(',', sanitize_text_field($_POST['tags']));


    try {
        $table = $wpdb->prefix . "chats";
        $wpdb->update(
            $table,
            array(
                "question" => $query,
                "answer" => $html,
            ),
            array('id' => $chatid),
        );
        // getting inserted record last id
        $id = $chatid;

        /**
         * chat id chn't be deleted, but chat terms should be deleted!
         * when deletion 3 conditions - update new tags, remove existing tags, insert new tags
         */

        if ($_POST['tags']) {
            // tags update 


            $table = $wpdb->prefix . "chat_terms";
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE chatid = %d",
                $chatid
            );
            $num_records = $wpdb->get_var($query);
            if ($num_records != 0) {
                // deleting old ids of chat terms -> tags
                $i = 0;
                while ($i < sizeof($tags)) {
                    $table = $wpdb->prefix . "chat_terms";
                    $wpdb->delete($table, array('chatid' => $id));
                    $i++;
                }
            }


            // inserting new ids of chat terms -> tags
            $i = 0;
            while ($i < sizeof($tags)) {
                $tag = trim($tags[$i]);
                $tag = str_replace('\\', '', $tag);
                $table = $wpdb->prefix . "chat_terms";
                $wpdb->insert(
                    $table,
                    array(
                        "chatid" => $id,
                        "tag" => $tag,
                    )
                );
                $i++;
            }
        } else {
            $table = $wpdb->prefix . "chat_terms";
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE chatid = %d",
                $chatid
            );

            // if records -> means no records and if atleast one then delete operation start!
            $num_records = $wpdb->get_var($query);

            if ($num_records != 0) {
                // deleting old ids of chat terms -> tags
                $i = 0;
                while ($i < sizeof($tags)) {
                    $table = $wpdb->prefix . "chat_terms";
                    $wpdb->delete($table, array('chatid' => $id));
                    $i++;
                }
            }
        }


        $res = true;

    } catch (Exception $e) {
        $res = false;
        echo $e->getMessage();
    }

    if ($res) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}


/**
 * hook - delete_query helps to delete query form , inside page=reply_edit_remove
 */
add_action('wp_ajax_delete_query', 'deleteQuery');
add_action('wp_ajax_nopriv_delete_query', 'deleteQuery');
function deleteQuery()
{
    global $wpdb;
    $id = sanitize_text_field($_POST['chatid']);

    try {
        $table = $wpdb->prefix . "chats";
        $wpdb->delete($table, array('id' => $id));


        $table = $wpdb->prefix . "chat_terms";
        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE chatid = %d",
            $id
        );
        $count = $wpdb->get_var($query);
        if ($count != 0) {
            $i = 0;
            while ($i < $count) {
                $table = $wpdb->prefix . "chat_terms";
                $wpdb->delete($table, array('chatid' => $id));
                $i++;
            }
        }

        echo "success";


    } catch (Exception $e) {
        echo $e->getMessage();
    }

    exit;
}



/**
 * hook - save_settings helps to save chat global settings into db.
 */
add_action('wp_ajax_save_settings', 'chatSettings');
add_action('wp_ajax_nopriv_save_settings', 'chatSettings');
function chatSettings()
{
    global $wpdb;

    try {
       
            $key = sanitize_text_field($_POST['key']);
            $contact = str_replace('\\', '', $_POST['contact']);
            $name = str_replace('\\', '', $_POST['business_name']);
            $description = str_replace('\\', '', $_POST['description']);
            $restriction = str_replace('\\', '', $_POST['restriction']);


            $table = $wpdb->prefix . "chat_global_settings";
            $wpdb->insert(
                $table,
                array(
                    "gemini_key" => $key,
                    "contact_us_link" => $contact,
                    "business_name" => $name,
                    "business_description" => $description,
                    "restriction" => $restriction,
                )
            );
            echo "success";
     

    } catch (Exception $e) {
        echo $e->getMessage();
    }

    exit;
}


// hook - view_settings helps to displat settings value inside settings form.
add_action('wp_ajax_view_settings', 'viewSettings');
add_action('wp_ajax_nopriv_view_settings', 'viewSettings');
function viewSettings()
{
    global $wpdb;
    $table = $wpdb->prefix."chat_global_settings";
    $arr = $wpdb->get_results("SELECT * FROM $table ORDER BY id DESC LIMIT 1");
    echo json_encode($arr, true);
    exit;
}



// hook - export_csv helps to export table data in csv format
add_action('wp_ajax_export_csv', 'exportCSV');
add_action('wp_ajax_nopriv_export_csv', 'exportCSV');
function exportCSV()
{
    global $wpdb;
    // Set headers to force download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=data.csv');
    $output = fopen('php://output', 'w');

    // Output column headers
    fputcsv($output, array('id', 'question', 'answer'));
    $table = $wpdb->prefix."chats";
    $rows = $wpdb->get_results("SELECT id, question, answer FROM $table", ARRAY_A);
    foreach ($rows as $row) {
        fputcsv($output, $row);
    }
    fclose($output);

    exit();
}

// hook - import_csv helps to import csv file data inside wp_chats table.
add_action('wp_ajax_import_csv', 'importCSV');
add_action('wp_ajax_nopriv_import_csv', 'importCSV');
function importCSV() {
    global $wpdb;
    $table = $wpdb->prefix."chats";

    // Check if a file was uploaded
    if (isset($_FILES['file'])) {
        $file = $_FILES['file']['tmp_name'];
        
        // Open the uploaded CSV file
        if (($handle = fopen($file, 'r')) !== false) {
            global $wpdb;

            // Skip the first line (header row)
            fgetcsv($handle);

            // Loop through the file and insert data into the wp_chats table
            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $wpdb->insert(
                    $table,
                    array(
                        'question' => $data[1], // Assuming 'question' is in the 2nd column
                        'answer' => $data[2],   // Assuming 'answer' is in the 3rd column
                    ),
                    array('%s', '%s')
                );
            }

            // Close the file handle
            fclose($handle);

            // Return success response
            wp_send_json_success('CSV imported successfully!');
        } else {
            wp_send_json_error('Failed to open the file.');
        }
    } else {
        wp_send_json_error('No file uploaded.');
    }

    exit();
}



?>
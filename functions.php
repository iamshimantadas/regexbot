<?php


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
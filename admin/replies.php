<?php
if (!defined('ABSPATH')) {
    exit;
}

$path = $_SERVER['DOCUMENT_ROOT'];
include_once $path . '/wp-config.php';

global $wpdb;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Replies</title>
    <link rel="stylesheet" href="<?php echo plugins_url('../assets/css/bootstrap.min.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('../assets/css/custom.css', __FILE__); ?>" />  
    <link rel="stylesheet" href="<?php echo plugins_url('../assets/summernote/summernote.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('../assets/font/bootstrap-icons.css', __FILE__); ?>">
    <link rel="stylesheet" href="<?php echo plugins_url('../assets/DataTables/datatables.min.css', __FILE__); ?>">
    <script src="<?php echo plugins_url('../assets/js/bootstrap.bundle.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../assets/js/jquery.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../assets/js/sweetalert.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../assets/DataTables/datatables.min.js', __FILE__); ?>"></script>
   
</head>

<body>

    <br>

    <div class="row">
        <div class="col-3">
            <!-- Button trigger modal -->
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                Add Query
            </button>
        </div>
        <div class="col-3"></div>
        <div class="col-3">
            <!-- <button type="button" class="btn btn-dark">
                Import Chats
            </button> -->
            <!-- Bootstrap Import CSV Button -->
            <button id="import-csv-btn" class="btn btn-primary">Import CSV</button>

            <!-- File Input (Hidden) -->
            <input type="file" id="csv-file-input" style="display: none;" accept=".csv" />

        </div>
        <div class="col-3">
            <button type="button" id="export-csv-btn" class="btn btn-warning">
                Export Chats
            </button>
        </div>
    </div>


    <!-- Modal of add query -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <br>
        <br>
        <br>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add New Bot's Query & Reply</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form>
                        <div class="mb-3">
                            <label for="query" class="form-label">Query</label>
                            <input type="text" class="form-control" name="query" id="query"
                                placeholder="enter bot's query" required>
                        </div>
                        <div class="mb-3">
                            <label for="query_tags" class="form-label">Tags</label>
                            <input type="text" class="form-control" name="query_tags" id="query_tags"
                                placeholder="enter tags related query and separate by comma">
                            <div id="tags-container"></div>
                        </div>
                        <div class="mb-3">
                            <label for="editor1" class="form-label">Enter bot's reply</label>
                            <textarea name="editor1" id="editor1" rows="10" cols="80" required>
                        </textarea>
                        </div>
                        <button type="button" id="save_query_btn" class="btn btn-primary">Submit</button>
                    </form>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>


    <br>
    <br>


    <!-- start of query/reply -->
    <div class="row">
        <div class="col-12">

            <table class="table" id="myTable">
                <thead>
                    <tr>
                    <th scope="col">Chat ID</th>
                        <th scope="col">Query</th>
                        <th scope="col">Reply</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $table_name = $wpdb->prefix . "chats";
                    $record = $wpdb->get_results("SELECT * FROM $table_name ORDER BY id DESC;");
                    foreach ($record as $data) {
                        ?>
                        <tr>
                        <td><?php echo $data->id; ?></td>
                            <td><?php echo $data->question; ?></td>
                            <td><?php echo sanitize_text_field(substr($data->answer, 0, 50)) . "..."; ?></td>
                            <td>
                                <i class="bi bi-pencil-square" onclick="updateForm(<?php echo $data->id; ?>)"></i>
                                <i class="bi bi-trash3-fill"
                                    onclick="deleteForm(<?php echo $data->id; ?>, '<?php echo $data->question; ?>')"></i>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>

        </div>
    </div>
    <!-- end of query/reply -->


    <!-- Update Chat Modal -->
    <div class="modal fade" id="updateChatModal" tabindex="-1" aria-labelledby="updateChatModalLabel"
        aria-hidden="true">
        <br>
        <br>
        <br>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateChatModalLabel">Update Chat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="updateChatForm">
                        <input type="hidden" id="chat-id">
                        <div class="mb-3">
                            <label for="chat-question" class="form-label">Query</label>
                            <input type="text" class="form-control" id="chat-question" required>
                        </div>
                        <div class="mb-3">
                            <label for="chat-answer" class="form-label">Bot's reply</label>
                            <textarea class="form-control" id="editor2" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="chat-tags" class="form-label">Tags (comma-separated)</label>
                            <input type="text" class="form-control" id="chat-tags">
                            <div id="update-tags-container" class="mt-2"></div>
                        </div>
                        <button type="button" id="update_query_btn" class="btn btn-primary">Save changes</button>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>



    <script src="<?php echo plugins_url('../assets/summernote/summernote.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('../assets/js/script.js', __FILE__); ?>"></script>

    <script>
        // this basically gets parameters from the page url and display inside add query modal - examplemodal query field
        jQuery(document).ready(function () {
            const urlParams = new URLSearchParams(window.location.search);
            const queryParam = urlParams.get('query');
            jQuery('#query').val(queryParam);
            if (urlParams.get('showModal') === 'true') {
                var myModal = new bootstrap.Modal(document.getElementById('exampleModal'));
                myModal.show();
            }
        });


        // import csv
        jQuery(document).ready(function () {
            jQuery('#import-csv-btn').click(function () {
                jQuery('#csv-file-input').click(); // Trigger the hidden file input click
            });

            jQuery('#csv-file-input').change(function (e) {
                let file = e.target.files[0];

                if (file) {
                    let formData = new FormData();
                    formData.append('file', file);
                    formData.append('action', 'import_csv'); // Action name for AJAX

                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: formData,
                        processData: false, // Prevent jQuery from processing the data
                        contentType: false, // Prevent jQuery from setting content type
                        success: function (res) {
                            alert('CSV imported successfully!');
                            window.location.reload();
                        },
                        error: function (res) {
                            console.error(res);
                            alert('Failed to import CSV.');
                        }
                    });
                }
            });
        });


        // export csv 
        jQuery(document).ready(function () {
            jQuery('#export-csv-btn').click(function () {
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: { action: 'export_csv' },
                    success: function (res) {
                        // Create a download link and trigger it automatically
                        let blob = new Blob([res], { type: 'text/csv;charset=utf-8;' });
                        let link = document.createElement("a");
                        let url = URL.createObjectURL(blob);
                        link.setAttribute("href", url);
                        link.setAttribute("download", "data.csv");
                        link.style.visibility = 'hidden';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    },
                    error: function (res) {
                        console.error(res);
                    }
                });
            });
        });


        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

        jQuery('#document').ready(function () {
            // loading summernote on mount
            $('#editor1').summernote();
            jQuery('#myTable').DataTable({
                order: [[0, 'desc']]
            });

            // tags containers active
            jQuery('#query_tags').on('keypress', function (event) {
                if (event.key === ',' || event.key === 'Enter') {
                    event.preventDefault();
                    let inputValue = $(this).val().trim();
                    if (inputValue) {
                        addTag(inputValue);
                        jQuery(this).val('');
                    }
                }
            });

            function addTag(tagText) {
                let tagHtml = `<span class="tag">${tagText}<span class="remove-tag">&times;</span></span>`;
                jQuery('#tags-container').append(tagHtml);
            }
            jQuery('#tags-container').on('click', '.remove-tag', function () {
                jQuery(this).parent('.tag').remove();
            });
        });


        jQuery('#save_query_btn').click(function () {
            let query = jQuery('#query').val();
            let editor = jQuery('#editor1').summernote('code');
            let tags = [];
            jQuery('#tags-container .tag').each(function () {
                tags.push(jQuery(this).clone().children().remove().end().text().trim());
            });
            let allTags = tags.join(', ');
            if (allTags.length == 0) {
                allTags = null;
            }


            if (query && editor) {
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: { action: 'save_query', query: query, editor: editor, tags: allTags },
                    success: function (res) {
                        // console.log(res);
                        if (res == "success") {

                            Swal.fire({
                                title: "Record Saved!",
                                icon: "success"
                            });

                            jQuery('#exampleModal').modal('hide');
                            jQuery('#query').val("");
                            window.location.reload();
                            // redirecting to the this page again! after successful data insert!
                            window.location.href = "<?php echo admin_url('admin.php?page=reply_edit_remove'); ?>";


                        } else if (res == "error") {
                            Swal.fire({
                                title: "Record Not Saved!",
                                icon: "error"
                            });
                        }
                    },
                    error: function (res) {
                        console.error(res);
                    }
                });
            } else {
                if (!jQuery('#query').val()) {
                    alert("Please enter query");
                }

                if (!jQuery('#editor1').summernote('code')) {
                    alert("Please enter response");
                }
            }


        });


        jQuery('#update_query_btn').click(function () {
            let query = jQuery('#chat-question').val();
            let editor = jQuery('#editor2').summernote('code');
            let tags = [];
            jQuery('#update-tags-container .tag').each(function () {
                tags.push(jQuery(this).clone().children().remove().end().text().trim());
            });
            let allTags = tags.join(', ');
            if (allTags.length == 0) {
                allTags = null;
            }
            let chatid = jQuery('#chat-id').val();

            // console.warn("update data: ", query, editor, allTags, chatid);

            if (query && editor) {
                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: { action: 'update_query', query: query, editor: editor, tags: allTags, chatid: chatid },
                    success: function (res) {
                        console.warn(res);
                        if (res == "success") {

                            Swal.fire({
                                title: "Record Updated!",
                                icon: "success"
                            });
                            window.location.reload();
                            jQuery('#updateChatModal').modal('hide');
                            jQuery('#query2').val("");


                        } else if (res == "error") {
                            Swal.fire({
                                title: "Record Not Updated!",
                                icon: "error"
                            });
                        }
                    },
                    error: function (res) {
                        console.error(res);
                    }
                });
            } else {
                if (!jQuery('#chat-question').val()) {
                    alert("Please enter query");
                }

                if (!jQuery('#editor2').summernote('code')) {
                    alert("Please enter response");
                }
            }
        });

    </script>

    <script>
        var ajaxurl = "<?php echo admin_url('admin-ajax.php'); ?>";

        function updateForm(id) {

            jQuery.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'get_reply',
                    id: id
                },
                success: function (response) {
                    // Parse the JSON response
                    const data = JSON.parse(response);
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }

                    // Populate the modal with the retrieved data
                    jQuery('#chat-id').val(id);
                    jQuery('#chat-question').val(data.question);
                    jQuery('#editor2').summernote('code', data.answer);

                    jQuery('#update-tags-container').empty();

                    // Populate tags
                    data.tags.forEach(tag => {
                        addUpdateTag(tag);
                    });

                    // Show the modal
                    jQuery('#updateChatModal').modal('show');
                },
                error: function (xhr, status, error) {
                    console.error('Error fetching chat details:', error);
                    alert('An error occurred while fetching chat details.');
                }
            });



            // Function to add a tag to the update modal
            function addUpdateTag(tagText) {
                let tagHtml = `<span class="tag">${tagText}<span class="remove-tag">&times;</span></span>`;
                jQuery('#update-tags-container').append(tagHtml);
            }

            // Handle tag input in the update modal
            jQuery('#chat-tags').on('keypress', function (event) {
                if (event.key === ',' || event.key === 'Enter') {
                    event.preventDefault();
                    let inputValue = jQuery(this).val().trim();
                    if (inputValue) {
                        addUpdateTag(inputValue);
                        jQuery(this).val('');
                    }
                }
            });

            // Remove tag when clicking on the remove icon
            jQuery('#update-tags-container').on('click', '.remove-tag', function () {
                jQuery(this).parent('.tag').remove();
            });
        }

        function deleteForm(id, chatquestion) {
            let chatid = id;


            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: "btn btn-success",
                    cancelButton: "btn btn-danger custom-cancel-button"
                },
                buttonsStyling: false
            });
            swalWithBootstrapButtons.fire({
                title: "Delete Query: " + chatquestion,
                text: "You won't be able to revert this!",
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: "Yes, delete it!",
                cancelButtonText: "No, cancel!",
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {

                    // ajax for delete record
                    jQuery.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        data: { action: 'delete_query', chatid: chatid },
                        success: function (res) {
                            console.warn(res);
                            if (res == "success") {
                                swalWithBootstrapButtons.fire({
                                    title: "Deleted!",
                                    icon: "success"
                                });

                            } else if (res == "error") {
                                Swal.fire({
                                    title: "Record Not Deleted!",
                                    icon: "error"
                                });
                            }
                            window.location.reload();
                        },
                        error: function (res) {
                            console.error(res);
                        }
                    });

                } else if (
                    /* Read more about handling dismissals below */
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    swalWithBootstrapButtons.fire({
                        title: "Record Not Deleted",
                        // text: "Your imaginary file is safe :)",
                        icon: "error"
                    });

                }
            });

        }
    </script>


</body>

</html>
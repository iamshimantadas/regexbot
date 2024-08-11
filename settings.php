<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
    <link rel="stylesheet" href="<?php echo plugins_url('./assets/css/bootstrap.min.css', __FILE__); ?>">
        <script src="<?php echo plugins_url('./assets/js/bootstrap.bundle.min.js', __FILE__); ?>"></script>
    <script src="<?php echo plugins_url('./assets/js/sweetalert.js', __FILE__); ?>"></script>

</head>

<body>

    <br>
    <br>

    <div class="row">
        <div class="col-2"></div>
        <div class="col-8">

            <form>
                <div class="mb-3">
                    <label for="gemini-key" class="form-label">Google Gemini API Key</label>
                    <input type="password" class="form-control" id="gemini-key" aria-describedby="enter genimi key"
                        placeholder="Enter Google Gemini API key">
                </div>
                <div class="mb-3">
                    <label for="contact-us-page" class="form-label">Contact Us Page Link</label>
                    <input type="text" class="form-control" id="contact-us-page"
                        aria-describedby="enter contact us page link" placeholder="Enter Contact Us Page Link">
                </div>
                <div class="mb-3">
                    <label for="business-name" class="form-label">Business Name</label>
                    <input type="text" class="form-control" id="business-name" placeholder="Enter Your Business Name">
                </div>
                <div class="mb-3">
                    <label for="business-description" class="form-label">Business Description</label>
                    <input type="text" class="form-control" id="business-description"
                        placeholder="Enter Your Business Description">
                </div>
                <div class="mb-3">
                    <label for="restrictions" class="form-label">Restrictions(Any restrictions which bot should
                        follow!)</label>
                    <textarea class="form-control" id="restrictions" rows="3"></textarea>
                </div>
                <button type="button" id="save-btn" class="btn btn-primary">Save</button>
            </form>

        </div>
        <div class="col-2"></div>
    </div>





    <script>
        jQuery(document).ready(function () {

            // on load fetch latest record if present.
            jQuery.ajax({
                url: ajaxurl,
                method: 'POST',
                data: { action: 'view_settings' },
                success: function (res) {
                    let obj = JSON.parse(res);
                    obj = obj[0];
                    jQuery('#gemini-key').val(obj.gemini_key);
                    jQuery('#contact-us-page').val(obj.contact_us_link);
                    jQuery('#business-name').val(obj.business_name);
                    jQuery('#business-description').val(obj.business_description);
                    jQuery('#restrictions').val(obj.restriction);
                },
                error: function (res) {
                    console.error("some error occured! call to system admin!");
                }
            });

            jQuery('#save-btn').click(function () {
                let key = jQuery('#gemini-key').val();
                let contact_link = jQuery('#contact-us-page').val();
                let business_name = jQuery('#business-name').val();
                let business_description = jQuery('#business-description').val();
                let restriction = jQuery('#restrictions').val();

                // console.warn(key, contact_link, business_name, business_description, restriction);

                jQuery.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: { action: 'save_settings', key: key, contact: contact_link, business_name: business_name, description: business_description, restriction: restriction },
                    success: function (res) {
                        // console.log(res);
                        if (res == "success") {
                            Swal.fire({
                                title: "New Record Saved!",
                                icon: "success"
                            });

                            jQuery('#gemini-key').val("");
                            window.location.reload();


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
            });

        });
    </script>


</body>

</html>
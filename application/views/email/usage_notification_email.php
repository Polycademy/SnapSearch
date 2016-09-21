<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>SnapSearch Usage Notification</title>
        <?php $this->load->view('email/email_styles.php'); ?>
    </head>
    <body>
        <!-- Wrapper/Container Table: Use a wrapper table to control the width and the background color consistently of your email. Use this approach instead of setting attributes on the body tag. -->
        <table cellpadding="0" cellspacing="0" border="0" id="backgroundTable">
            <tr>
                <td valign="top" style="padding: 16px;"> 

                    <h4>Hi <?= $username ?>,</h4>

                    <p>You have reached <?= $percentage ?>% usage on SnapSearch.</p>

                    <p>Your current usage count is <?= $usage ?> and the limit is <?= $limit ?>.</p>

                    <p>Once you have exhausted your usage count, new snapshots will no longer be created, however cached snapshots will still be accessible until their expiry date. Your usage count will reset starting your next billing cycle.</p>

                    <p>You can either increase your API limit, or you can extend the expiry time using the `cachetime` parameter on the API. If you do switch the `cachetime` parameter, the old snapshots on the old `cachetime` will automatically be invalidated. You can also use the `refresh` parameter along with a long `cachetime` if you want to manually refresh the cache.</p>

                    <p>Thank you for your business.</p>

                    <div style="color: #8f8f8f; margin-top: 20px; font-size: 12px;">
                        <p>
                            SnapSearch is an SEO interception service for advanced javascript applications provided by Polycademy. You can contact SnapSearch at <a href="mailto:enquiry@snapsearch.io">enquiry@snapsearch.io</a>
                        </p>
                    </div>

                </td>
            </tr>
        </table>  
    </body>
</html>
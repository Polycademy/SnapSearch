<!-- Grace Action - Failed Charge - Grace Retry -->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <title>SnapSearch Retry Billing Error for <?= $month ?> <?= $year ?></title>
        <?php $this->load->view('email/email_styles'); ?>
    </head>
    <body>
        <!-- Wrapper/Container Table: Use a wrapper table to control the width and the background color consistently of your email. Use this approach instead of setting attributes on the body tag. -->
        <table cellpadding="0" cellspacing="0" border="0" id="backgroundTable">
            <tr>
                <td valign="top" style="padding: 16px;"> 

                    <h4>Hi <?= $username ?>,</h4>

                    <p>SnapSearch has attempted to retry charging for your previous monthly invoice. However it met an error processing the payment. The error is:</p>

                    <blockquote><strong><?= $charge_error ?></strong></blockquote>

                    <p>We will retry again every <?= $grace_retry_period_human_readable ?> until <?= $grace_ending_date ?>. When this date is reached, your API limit will be downgraded to your free account limit.</p>

                    <p>Please review your credit card details at your <a href="https://snapsearch.io/control_panel/payments">billing control panel</a>.</p>

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
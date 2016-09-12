<!-- Charge/Grace Action - Invalid or Missing Billing Source-->
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd"> 
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
		<title>SnapSearch Invalid or Missing Card Error for <?= $month ?> <?= $year ?></title>
		<? $this->load->view('email/email_styles'); ?>
	</head>
	<body>
		<!-- Wrapper/Container Table: Use a wrapper table to control the width and the background color consistently of your email. Use this approach instead of setting attributes on the body tag. -->
		<table cellpadding="0" cellspacing="0" border="0" id="backgroundTable">
			<tr>
				<td valign="top" style="padding: 16px;"> 

					<h4>Hi <?= $username ?>,</h4>

					<p>SnapSearch has attempted to bill your credit card for your monthly invoice. However it met an error processing the payment. The error is:</p>

					<blockquote><strong><?= $charge_error ?></strong></blockquote>

					<p>Due to this error, we have downgraded your API limit to the free account limit.</p>

					<p>Please review your credit card details at your <a href="https://snapsearch.io/control_panel/payments">billing control panel</a>. You need to update or delete and recreate a new valid card. Once this is resolved, you must reinstate your previous API limit in the control panel. This month's charge will be added to the next month's charge.</p>

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
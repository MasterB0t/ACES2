<?php
$MAIL['header'] = null;
$MAIL['header'] .= "To: %{to} < %{to} > \r\n";
$MAIL['header'] .= "From: %{site_name} <no-replay@%{site_host}>\r\n";
$MAIL['header'] .= "Reply-To: NO-REPLAY <no-replay@%{site_host}>\r\n";
$MAIL['header'] .= 'X-Mailer: PHP/' . phpversion();
$MAIL['header'] .= "MIME-Version: 1.0\r\n";
$MAIL['header'] .= "Content-type: text/html; charset=iso-8859-1\r\n";
//$MAIL['header'] .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
$MAIL['header'] .= "Content-Transfer-Encoding: 8bit\r\n";

$MAIL['header'] .= "Return-Path: <no-replay@%{site_host}>\r\n";
$MAIL['header'] .= "X-Priority: 3\r\n";
$MAIL['header'] .= "X-MSMail-Priority: Normal\r\n";
//$MAIL['header'] .= "X-Mailer: Mail via PHP\r\n";




$MAIL['register']['header'] = $MAIL['header'];
$MAIL['register']['subject'] = "Activate your account.";
$MAIL['register']['message'] = 
"Dear, %{username},<br><br>
	  Thanks for registering with %{site_host}. Please Click <a href=\"%{site_url}/validate.php?validate=%{email}&key=%{key}\" > Here </a> to verify and activate your account. <br><br>

	  If the above link does not work, please copy and paste the following URL into any browser window and verify your account.<br>
	  %{site_url}/validate.php?validate=%{email}&key=%{key} <br><br>
	  
	  The %{site_name} Team <br><br>
	  
	  --------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br><br>
	  If you have not register in %{site_url} ignore this email.<br>
";

$MAIL['payment_accepted']['header'] = $MAIL['header'];
$MAIL['payment_accepted']['subject'] = "Your payment %{site_name}";
$MAIL['payment_accepted']['message'] = 
"
  We email to inform you that we just deposit your payment of $%{amount} in your account of %{processor}.<br><br>
  
  If you have don't receive the payment after 24-48 hours, please let us know at %{contact_email} or <a href=\"%site_url/index.php?page=contact.php\" > here </a> and please provide us the payment id which is %{item_id}.<br><br>
  
  Thanks, The %{site_name} Team.

";

$MAIL['payment_declined']['header'] = $MAIL['header'];
$MAIL['payment_declined']['subject'] = "Your payment %{site_name}";
$MAIL['payment_declined']['message'] =
"
  %{username}, We email to inform you that your payment request was declined.<br><br>

  %{reason} <br><br>

  If you have any question please let us know at %{contact_email} or <a href=\"%site_url\index.php?page=contact.php\" > here </a> and please provide us the payment id which is #%{item_id}.<br><br>

  Thanks, The %{site_name} Team.

";

$MAIL['admin_payment_requested']['header'] = $MAIL['header'];
$MAIL['admin_payment_requested']['subject'] = "An payment was requested %{site_name}.";
$MAIL['admin_payment_requested']['message'] =
"
  %{username} have request a payment of $%{amount} in %{processor} processor. <br><br>


";

$MAIL['admin_contact']['header'] = $MAIL['header'];
$MAIL['admin_contact']['subject'] = "Someone Have contact on %{site_name}";
$MAIL['admin_contact']['message'] =
"
  %{name} have send you a message:<br><br>

  %{message}<br><br>

  replay to this email %{email} <br>

";


$MAIL['reset_password']['header'] = $MAIL['header'];
$MAIL['reset_password']['subject'] = "Reset your password.";
$MAIL['reset_password']['message'] =
"Dear, %{username}, <br>
	  You have requested to change your %{site_name} account password. To finish this process, please visit the following link:  <br>
	  <a href=\"%{site_url}/forgot.php?recover_password&email=%{email}&key=%{key}\" > Here </a> to change your account password. <br><br>

	  If the above link does not work, please copy and paste the following URL into any browser window.<br>
	  %{site_url}/forgot.php?&recover_password&email=%{email}&key=%{key} <br><br>
	 
	  The %{site_name} Team<br><br>

	  --------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br>
	  If you didn't request any changes, just ignore this message.<br>
";



$MAIL['validate_email']['header'] = $MAIL['header'];
$MAIL['validate_email']['subject'] = "Activate your account.";
$MAIL['validate_email']['message'] =
"Dear, %{username},<br><br>
	  Thanks for registering with %{site_host} Please Click <a href=\"%{site_url}/validate.php?validate=%{email}&key=%{key}\" > Here </a> to verify and activate your account. <br><br>

	  If the above link does not work, please copy and paste the following URL into any browser window and verify your account.<br>
	  %{site_url}/validate.php?validate=%{email}&key=%{key} <br><br>
	 
	  The %{site_name} Team <br><br>
	 
	  --------------------------------------------------------------------------------------------------------------------------------------------------------------------------<br><br>
	  If you have not register in %{site_host} ignore this email.<br>
";



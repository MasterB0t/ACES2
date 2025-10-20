<?php

require '/home/aces/www/ACES/init.php';

$MAIL = new \ACES\MAIL\MAIN();

$r=$MAIL->query("SELECT * FROM mail__queues ");
while($row=$r->fetch_assoc()) { 
    
    $MAIL->sendmail($row['to_email'],$row['subject'],$row['body']);
	$MAIL->query("DELETE FROM mail__queues WHERE id = '{$row['id']}'");
    
}

<?php

$r=$DB->query("SELECT * FROM iptv_mag_event WHERE account_id = '$MAG_ACCOUNT_ID' ORDER BY id ASC LIMIT 1");
if($row=$r->fetch_assoc()) {


    $json = array(
        "msgs" => "1",
        'id' => (string)$row['id'],
        'event' => $row['event'],
        'need_confirm' =>"1",
        'msg' => $row['message'],
        'reboot_after_ok' => "0",
        'auto_hide_timeout' => "0",
        'send_time' => date("d-m-y H:i:s"),
        'additional_services_on' => "1",
        'updated' => array('anec' => "0", 'vclub' => "0")
        );

    //WHAT THIS FOR ???
    //AFTER THE EVENT HAVE BEEN SEND LET REMOVE IT IF IS NOT A MESSAGE.
    //OR IT WILL KEEP BE SENDING TO THE DEVICE.
    if($row['event'] != 'send_msg')
        $DB->query("DELETE FROM iptv_mag_event WHERE id = '{$row['id']}'");

    echo json_encode(array('js' => array('data' => $json )));


} else
    echo '{"js":[],"text":""}';


exit;
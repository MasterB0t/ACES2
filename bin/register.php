<?php

$serial = $argv[1];

$UUID = trim(shell_exec('findmnt -n -o UUID $(stat -c \'%m\' "/")'));

$info = array(
    'HID' => $UUID,
    'Timezone' => date_default_timezone_get(),
    'serial' => $serial,
);



$ch = curl_init("https://acescript.ddns.net/v2/register.php?info=".base64_encode(json_encode($info)));
curl_setopt($ch, CURLOPT_HEADER, false);    // we want headers
curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
curl_setopt($ch, CURLOPT_TIMEOUT ,10);
$output = trim(curl_exec($ch));
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents("/home/aces/machine-id", $output);

echo $httpcode;



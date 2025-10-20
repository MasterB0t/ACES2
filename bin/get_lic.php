
<?php

$mid = trim(file_get_contents('/home/aces/machine-id'));

$file = "/tmp/lic-".time().".zip";

if(!is_dir('/home/aces/lics/'))
    mkdir('/home/aces/lics/');

$SITE = "https://acescript.ddns.net/v2/get_lic.php?mid=$mid";
exec("wget -O $file $SITE ");
exec("unzip -o $file -d  /home/aces/lics/");
unlink($file);
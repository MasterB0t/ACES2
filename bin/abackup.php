<?php

//if(count(glob("/home/aces/backups/aBackup*")) > 14 )
//    exec("find /home/aces/backups/aBackup* -mtime +14 -exec rm -f {} \;");

//exec("su aces -c \" php /home/aces/bin/backup.php aBackup-".date("YFd_H-i-s")."tar.gz > /dev/null &  \" "  );

exec("php /home/aces/bin/backup.php aBackup-".date("YFd_H-i-s") );
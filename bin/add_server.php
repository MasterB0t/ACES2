#!/usr/bin/env php
<?php

echo "Welcome to ACESIPTV Server installer.";
echo "\n\n";

chdir("/home/aces/panel");
error_reporting(0);
include '/home/aces/panel/includes/init.php';

include '/home/aces/stream/config.php';
$DB = new mysqli($DBHOST,$DBUSER,$DBPASS,$DATABASE);
if($DB->connect_errno > 0) die('FAIL TO CONNECT TO DATABASE.');

$NEW_SRV_NAME='';
$API_TOKEN='';
$NEW_ID=0;

$url = "http://acescript.ddns.net/update.php";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$url");
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

if(!$json = json_decode(curl_exec($ch),1)) die("Could not connect with ACES Servers. Please try again later.\n");

//if(version_compare(ACES::VERSION, $json['version'], '<') ) { die("Please update panel before adding a new server.\n"); }


$SSH_CON=NULL;

while(!$SSH_CON) {
	echo "Enter the IP-ADDRESS of server to be added. : ";
	while(true) {
	    $handle = fopen ("php://stdin","r");
	    $line = trim(fgets($handle));
	    if( filter_var($line, FILTER_VALIDATE_IP)  ) { fclose($handle); $ip = $line; break; }
	    else { fclose($handle); echo "This '$ip' is not a valid ip-address. Please try again : "; }
	}

//	echo "\nEnter ssh username 'root': ";
//	while(true) {
//	    $handle = fopen ("php://stdin","r");
//	    $line = fgets($handle);
//	    if(trim($line)!= '') { fclose($handle); $username = trim($line); break; }
//	    fclose($handle);
//	}

        $username = 'root';

        echo "\nEnter the ssh port (22) : ";
	while(true) {
	    $handle = fopen ("php://stdin","r");
	    $line = trim(fgets($handle));
	    if($line != '') { fclose($handle); $port = $line; break; }
	    fclose($handle);
	}

	echo "\nEnter the ssh user password : ";
	while(true) {
	    $handle = fopen ("php://stdin","r");
	    $line = trim(fgets($handle));
	    if($line != '') { fclose($handle); $password = $line; break; }
	    fclose($handle);
	}

	$connection = ssh2_connect($ip, $port);
	if(@ssh2_auth_password($connection, $username, $password)) { break; }
	else { echo "\nCould not connect to server with those details. Please try again.\n\n" ;}

}



function run($comm,$out='stderr') {

    global $connection;

    if($out == 'stdout') $comm = $comm ." ; ";
    else $comm = "$comm >> ACES_INSTALLER.txt; echo $? ";

    $stream = ssh2_exec($connection, $comm);
    stream_set_blocking($stream, true);
    $stream_out=ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);
    $s=trim(stream_get_contents($stream_out));

    if($out=='stdout') return $s;

    if($s > 0 ){ return false; }
    else return true;
}

function lic() {

    global $connection;

    if(!run('ls /home/aces/lics/')) {
        if(!run('mkdir -p -m755 /home/aces/lics/ ')) die("Unable to create lics folder on server.");
    }

    if(!$json=json_decode(run("curl 'http://acescript.ddns.net/get_lic.php' ",'stdout'),true)) die("Unable to communicate with server. Please try again later.");
    if( isset($json['lics']["nodo"]) && $json['lics']["nodo"]['expire_in'] < 1 ) die('ERROR: Please renew the license for this server.');
    else if( isset($json['lics']["nodo"]) && $json['lics']["nodo"]['new'] = 1 || isset($json['lics']["nodo"]) && !is_file("/home/aces/lics/nodo.txt")) {

        //THERE ARE A LIC BUT ISN'T ON SERVER
        $tmp_lic = "/home/aces/tmp_lic_".microtime(true).".txt";

        if(!file_put_contents($tmp_lic,$json['lics']["nodo"]['key'])) die("Network error. Unable to download lic content.");
        if(!ssh2_scp_send($connection,$tmp_lic,'/home/aces/lics/nodo.txt')) die("Unable to write lic on server.");
        unlink($tmp_lic);

    } else {
        //NO LICENSE REGISTERED LET ASK FOR SERIAL.
	echo "\n\nPlease enter the serial to register this server : ";
	$serial=trim(fgets(STDIN));
	while(true) {
                $json=json_decode(run("curl 'http://acescript.ddns.net/register_lic.php?type=nodo&serial={$serial}' ",'stdout'),true);
		if(isset($json['status']) && $json['status'] == 'complete') { break; }
		if($json['error_code'] == 203 ) {
			//ALREADY REGISTERED. LET CONFIRM IS FROM THIS SERVER.
			$json=json_decode(run("curl 'http://acescript.ddns.net/get_lic.php' "),true);
			if(isset($json['lics']["nodo"]['key'])) { break; }
		}

		echo "\n\nThis serial do not exist. Please try again : ";
		$serial=trim(fgets(STDIN));
	}

        echo "\nServer been registered. Thanks.\n\n";

        if(!$json=json_decode(run("curl 'http://acescript.ddns.net/get_lic.php' ", 'stdout'),true)) die('Fail to download lic.');
        $tmp_lic = "/home/aces/tmp_lic_".microtime(true).".txt";
        if(!file_put_contents($tmp_lic,$json['lics']["nodo"]['key'])) die("Internet error. Unable to download lic content.");
        if(!ssh2_scp_send($connection,$tmp_lic,'/home/aces/lics/nodo.txt')) die("Unable to upload lic to server.");
        unlink($tmp_lic);

    }

}


function set_db() {

    global $DB, $connection, $SERVER_ID, $ip, $DBUSER, $DBPASS, $DATABASE, $NEW_SRV_NAME, $password;

    echo "\nSETTING DATABASE...\n\n";

    $API_TOKEN=md5(RAND().RAND());
    if(!$r_server=$DB->query("SELECT id FROM iptv_servers WHERE address = '$ip' ")) die("ERROR.");
    if(!$NEW_ID=mysqli_fetch_array($r_server)['id']) {

        if(!$DB->query("INSERT INTO iptv_servers (name,address,port,max_bandwidth_mbps,api_token,ssh_pass) VALUES('$NEW_SRV_NAME','$ip','9090',500,'$API_TOKEN','$password') "))
            die("Fail to add server to database.");

        $NEW_ID=$DB->insert_id;


    } else {
        $DB->query("UPDATE iptv_servers SET name = '$NEW_SRV_NAME', api_token = '$API_TOKEN' WHERE id = $NEW_ID ");
    }

    //GETTING MAIN SERVER IP
    $r_main=$DB->query("SELECT address FROM iptv_servers WHERE id = '{$SERVER_ID}' ");
    if(!$MAIN_IP=$r_main->fetch_assoc()['address']) die("Internal Error. Could not get main server ip.");

    $tfile="/home/aces/tmp/tmp_conf_file_.txt";
    if(!file_put_contents($tfile,"<?php\n\$DBHOST='$MAIN_IP';\n\$DBUSER='$DBUSER';\n\$DBPASS='$DBPASS';\n\$DATABASE='$DATABASE';\n\n\$API_TOKEN='$API_TOKEN';\n\n\$FOLDER='/home/aces/stream_tmp/';\n\n\$SERVER_ID=$NEW_ID;?>")) Die("Fail wrinting config file.");
    ssh2_scp_send($connection,$tfile,'/home/aces/stream/config.php');


}

function install_aces() {

    echo "\nINSTALLING ACES...\n\n";
    run("mkdir -p /home/aces/panel/ /home/aces/panel/class /home/aces/logs/ /home/aces/run /home/aces/lics/  /home/aces/stream  /home/aces/bin  /home/aces/tmp /home/aces/tmp/vod_downloads  /home/aces/stream_tmp /home/aces/vods /home/aces/etc /home/aces/fonts/ /home/aces/aces_vods/");
    run("mkdir /home/aces/rtmp_push/");
    run("mkdir -p /home/aces/loaders/");
    run("mkdir /home/aces/backups/");
    run("mkdir /home/aces/channel_files/");

    run("cp -r /root/ACES/HOME/bin/* /home/aces/bin/");
    run("cp -r /root/ACES/HOME/stream/* /home/aces/stream/");
    run("cp -r /root/ACES/HOME/panel/class/TMDB.php /home/aces/panel/class/");
    run("cp -f /root/ACES/FILES/php-fpm.conf /home/aces/etc/");
    run("cp -r /root/ACES/FILES/nginx /home/aces/etc");
    run("cp -r /root/ACES/HOME/fonts/* /home/aces/fonts/");
    run("cp -r HOME/loaders/* /home/aces/loaders/");


    run("cp /root/ACES/FILES/cacert.ca /home/aces/etc/");

    run("chmod o+x /home/aces/");
    run("chmod -R o+r /home/aces/stream");
    run("chmod 777 -R /home/aces/tmp /home/aces/stream_tmp");
    run("chmod +x /home/aces/bin/*");
    run("chmod 755 /home/aces/lics/");
    run("chmod 775 /home/aces/loaders -R");
    run("chown aces:aces /home/aces/ -R");


    run(" bash -c 'if [ -z \"$(mount --fake --verbose --all | grep '/home/aces/stream_tmp')\" ]; then echo \"tmpfs /home/aces/stream_tmp tmpfs defaults,noatime,nosuid,nodev,noexec,mode=1777,size=65% 0 0\" >> /etc/fstab ; mount /home/aces/stream_tmp ; fi  '");

    run(" bash -c 'if [ -z \"$(mount --fake --verbose --all | grep '/home/aces/run')\" ]; then echo \"tmpfs /home/aces/run tmpfs defaults,noatime,nosuid,nodev,noexec,mode=1777,size=100M 0 0\" >> /etc/fstab ; mount /home/aces/run ; fi  '");

    run("crontab -u aces -r ");
    run('(crontab -u aces -l ; echo "0 */12 * * * php /home/aces/bin/aces_get_lic.php")| crontab -u aces -');
    //run('(crontab -u aces -l ; echo "00 00 * * * php /home/aces/bin/abackup.php")| crontab -u aces -');
    run('(crontab -u aces -l ; echo "* * * * * php /home/aces/bin/aFolderWatch.php")| crontab -u aces -');
    run('(crontab -u aces -l ; echo "* * * * * php /home/aces/bin/aces_iptv_conns.php")| crontab -u aces -');


}

//CHECKING FOR DISTRO AND VERSION.
if(run('ls /etc/redhat-release')) {
    $DIST = trim(run(" cat /etc/redhat-release | awk '{print $1}'",'stdout' ));
    if($DIST != 'CentOS') DIE("Sorry this distro is not supported.");
    else {
        $VER = (int)run("rpm -q --queryformat '%{VERSION}' centos-release",'stdout');echo $VER;
        if($VER != 7 ) DIE("Sorry this version of CentOS is not supported.");
    }

} else if(run('ls /etc/lsb-release ')) {
    $DIST=trim(run(" . /etc/lsb-release && echo \$DISTRIB_ID ",'stdout'));
    $VER=(int)run(" . /etc/lsb-release && echo \${DISTRIB_RELEASE%%.*} ",'stdout');
    if($DIST != 'Ubuntu') DIE("This distro is not supported.");
    else if ($VER != 14 && $VER != 18 && $VER != 20 ) die("This version of ubuntu is not supported.");
} else die("Sorry this distro is not supported.");


echo "\nEnter a name for this new server : ";
while(true) {
    $handle = fopen ("php://stdin","r");
    $line = trim(fgets($handle));
    if(trim($line)!= '' && preg_match('/^[a-zA-Z0-9-]+$/',$line) ) { fclose($handle); $name = trim($line); break; }
    echo "Please use only letters and numbers. ";
    fclose($handle);
}
$NEW_SRV_NAME = $line;

run("echo '' > /root/ACES_INSTALLER.txt ");


if($DIST == 'CentOS' && $VER == 7 ) {

    if(run('getenforce','stdout') != 'Disabled') die("Please disabled selinux before installing the software.");

    echo "\nINSTALLING REQUIRED PACKAGES...\n\n";

    run("yum -y install 'http://li.nux.ro/download/nux/dextop/el7/x86_64/nux-dextop-release-0-5.el7.nux.noarch.rpm'");
    if(!run("yum -y install epel-release nginx  mariadb-server mariadb  php php-mysql php-fpm psmisc net-tools ffmpeg wget  php-gd  php-process php-pecl-ssh2  net-tools sudo")) die("Fail installing software packages2.");

    if(!run('getent passwd aces'))
        if(!run('useradd  -r -m -U aces'))
            die("Fail creating aces user.");
        run("usermod -aG sudo aces");

    lic();

    echo "\nDOWNLOADING ACES SOFTWARE...\n\n";
    run("rm -rf /root/ACES/ /root/ACES.tar.gz");

    run(" cd /root/; tar -xf ACES.tar.gz");

    run("cp -r /root/ACES/FILES/aces_limits.conf /etc/security/limits.d/50-aces_limits.conf");
    run("cp -r /root/ACES/FILES/centos-systemd/* /etc/systemd/system/");
    run("cp /root/ACES/FILES/aces_sudoers /etc/sudoers.d/");
    run("mkdir -p /etc/systemd/system/mariadb.service.d/");
    run("cp -R /root/ACES/FILES/mariadb_service_d/limits.conf /etc/systemd/system/mariadb.service.d/");

    echo "\nINSTALLING IONCUBE...\n\n";
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");

    if(!run("wget http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -O /root/ioncube_loaders_lin_x86-64.tar.gz")) die("Unable to download ioncube.");
    run("cd /root/; tar -xf ioncube_loaders_lin_x86-64.tar.gz");
    run("mkdir -p /home/aces/etc/ioncube/");
    if(!run("cp ioncube/ioncube_loader_lin_5.4.so /home/aces/etc/ioncube/")) die("Fail installing ioncube.");
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");

    $f="/home/aces/tmp/".microtime()."ioncube.ini";
    file_put_contents($f, "zend_extension = /home/aces/etc/ioncube/ioncube_loader_lin_5.4.so");
    if(!ssh2_scp_send($connection,$f,'/etc/php.d/ioncube.ini')) die('Fail installing ioncube.');
    unlink($f);


    install_aces();

    run("sysctl -p");

    set_db();

    echo "\nSTARTING SOFTWARE SERVICES..\n\n";
    run("systemctl daemon-reload");
    run("systemctl restart aces-nginx aces-php-fpm mariadb aces");
    run("systemctl enable aces-nginx aces-php-fpm mariadb ");
    run("systemctl enable aces");

    if(run('systemctl status firewalld ')) {

        run('firewall-cmd --permanent --add-port=9090/tcp');
        run('firewall-cmd --permanent --add-port=3306/tcp');
        run('firewall-cmd --reload');

    }



} if($DIST == 'Ubuntu' && $VER == 14 ) {


    echo "\nINSTALLING REQUIRED PACKAGES...\n\n";

   if(!run('apt -y update ') || !run('apt-get -y install software-properties-common') || !run('add-apt-repository -y ppa:mc3man/trusty-media') || !run('apt-get -y update') || !run('DEBIAN_FRONTEND=noninteractive apt-get -q -y install net-tools nginx php5-fpm php5-mysql curl libcurl3 libcurl3-dev php5-mcrypt php5-curl php5-cli libssh2-php mariadb-server daemon ffmpeg wget python cron') ) die("Failed installing requiered packages.");

    //if(!run('apt -y update ') || !run('apt-get -y install software-properties-common') || !run('add-apt-repository -y ppa:mc3man/trusty-media') || !run('apt-get -y update') || !run('DEBIAN_FRONTEND=noninteractive apt-get -q -y install nginx php5-fpm php5-mysql curl libcurl3 libcurl3-dev php5-mcrypt php5-curl php5-cli libssh2-php mariadb-server daemon ffmpeg wget') ) die("Failed installing requiered packages.");

    if(!run('getent passwd aces'))
        if(!run('useradd  -r -m -U aces')) die("Fail creating aces user.");
    run("usermod -aG sudo aces");

    lic();

    run("wget https://yt-dl.org/latest/youtube-dl -O /usr/local/bin/youtube-dl");
    run("chmod a+x /usr/local/bin/youtube-dl ");

    echo "\nDOWNLOADING ACES SOFTWARE...\n\n";
    run("rm -rf /root/ACES/ /root/ACES.tar.gz");
    if(!run("wget http://acescript.ddns.net/download.php -O /root/ACES.tar.gz")) die("Fail downloading ACES Software.");
    run(" cd /root/; tar -xf ACES.tar.gz");

    run('echo "session required pam_limits.so" >> /etc/pam.d/common-session ');

    run("cp -rf /root/ACES/FILES/aces_limits.conf  /etc/security/limits.d/50-aces_limits.conf");
    run("chmod +x /root/ACES/FILES/ubuntu14-initd/*");
    run("cp -rf /root/ACES/FILES/ubuntu14-initd/* /etc/init.d/");
    run("cp -f /root/ACES/FILES/ubuntu14-init/aces.conf /etc/init/");
    run("ln -s /etc/init/aces.conf /etc/init.d/aces");
    run("cp -f /root/ACES/FILES/aces_sudoers /etc/sudoers.d/");


    echo "\nINSTALLING IONCUBE...\n\n";
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");

    if(!run("wget http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -O /root/ioncube_loaders_lin_x86-64.tar.gz")) die("Unable to download ioncube.");
    run("cd /root/; tar -xf ioncube_loaders_lin_x86-64.tar.gz");
    run("mkdir -p /home/aces/etc/ioncube/");
    if(!run("cp ioncube/ioncube_loader_lin_5.5.so /home/aces/etc/ioncube/")) die("Fail installing ioncube.");
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");

    $f="/home/aces/tmp/".microtime()."01-ioncube.ini";
    file_put_contents($f, "zend_extension = /home/aces/etc/ioncube/ioncube_loader_lin_5.5.so");
    if(!ssh2_scp_send($connection,$f,'/etc/php5/fpm/conf.d/01-ioncube.ini')) die('Fail installing ioncube.');
    if(!ssh2_scp_send($connection,$f,'/etc/php5/cli/conf.d/01-ioncube.ini')) die('Fail installing ioncube.');
    unlink($f);

    install_aces();

    run('sysctl -p');
    run('initctl reload-configuration');

    set_db();

    echo "\nSTARTING SOFTWARE SERVICES..\n\n";
    run('/etc/init.d/aces-php5-fpm stop');
    run('/etc/init.d/aces-nginx stop');
    run('service aces stop ');

    if(!run('service aces-nginx start ')) die('Fail starting aces-nginx services.');
    if(!run('service aces-php5-fpm start')) die('Fail starting aces-php-fpm service.');
    if(!run('service aces start')) die('Fail starting aces service.');

    run("update-rc.d aces-nginx defaults 60");
    run("update-rc.d aces-php5-fpm  defaults ");
    run("update-rc.d aces defaults 90");

} if($DIST == 'Ubuntu' && $VER > 15 ) {

    echo "\nINSTALLING REQUIRED PACKAGES...\n\n";

    $libcurl='libcurl3';

    if($VER > 17 ) {
        $libcurl = 'libcurl4';

        if(run(" apt -qq list libcurl3 2>/dev/null  | grep -c installed ")) {
            echo "\n\nThis server have libcurl3 installed and libcurl4 is required. If you update it software or website you have in this server may stop working. Are you sure you want to update to libcurl4? y/n : ";
            while(true) {
                $a=trim(fgets(STDIN));
                if($a == 'y') { echo "\n UPDATING TO libcurl4...\n\n"; break; }
                else if($a == 'n' ) { die; }
                echo "\nPlease answer y/n : ";
            }
        }
    }

    if( !run('apt-get -y update ') ||  !run('apt-get -y install software-properties-common') )
        Die("Failed installing required packages.");

    if( $VER == '18' )
        if( !run('add-apt-repository -y ppa:jonathonf/ffmpeg-4') )
        Die("Failed installing required packages.");


    if( !run('apt-get -y update ') ||
        !run('apt-get -y install software-properties-common') ||
        !run('add-apt-repository -y ppa:ondrej/php') ||
        !run('add-apt-repository -y universe') ||
        !run('apt-get -y update') ||
        !run("DEBIAN_FRONTEND=noninteractive apt-get -y install nginx libnginx-mod-rtmp curl $libcurl php5.6 php5.6-fpm php5.6-mysql php5.6-mcrypt php5.6-curl php5.6-cli mariadb-server daemon ffmpeg wget python python3 python3-pip cron net-tools php5.6-ssh")) Die("Failed installing requiered packages.");

//    run("wget https://yt-dl.org/latest/youtube-dl -O /usr/local/bin/youtube-dl");
//    run("chmod a+x /usr/local/bin/youtube-dl ");

        run("python3 -m pip install --upgrade youtube-dlc");


    //if($VER > 17 && run("apt -qq list libcurl3 2>/dev/null  | grep -c installed") ) {
        run("update-alternatives --set php /usr/bin/php5.6");
    //}

    if(!run('getent passwd aces'))
        if(!run('useradd  -r -m -U aces')) die("Fail creating aces user.");
    run("usermod -aG sudo aces");

    lic();

    echo "\nDOWNLOADING ACES SOFTWARE...\n\n";
    run("rm -rf /root/ACES/ /root/ACES.tar.gz");
    if(!run("wget http://acescript.ddns.net/download.php -O /root/ACES.tar.gz")) die("Fail downloading ACES Software.");
    run(" cd /root/; tar -xf ACES.tar.gz");

    run("cp -r  /root/ACES/FILES/aces_limits.conf  /etc/security/limits.d/50-aces_limits.conf");
    run("cp -r  /root/ACES/FILES/ubuntu16-systemd/* /etc/systemd/system/");
    run("cp  /root/ACES/FILES/aces_sudoers /etc/sudoers.d/");

    run("mkdir -p /etc/systemd/system/mariadb.service.d/");
    run("cp -R  /root/ACES/FILES/mariadb_service_d/limits.conf /etc/systemd/system/mariadb.service.d/");


    echo "\nINSTALLING IONCUBE...\n\n";
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");

    if(!run("wget http://downloads3.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz -O /root/ioncube_loaders_lin_x86-64.tar.gz")) die("Unable to download ioncube.");
    run("cd /root/; tar -xf ioncube_loaders_lin_x86-64.tar.gz");
    run("mkdir -p /home/aces/etc/ioncube/");
    if(!run("cp ioncube/ioncube_loader_lin_5.6.so /home/aces/etc/ioncube/")) die("Fail installing ioncube.");
    run("rm -rf /root/ioncube_loaders_lin_x86-64.tar.gz /root/ioncube ");


    $f="/home/aces/tmp/".microtime()."01-ioncube.ini";
    file_put_contents($f, "zend_extension = /home/aces/etc/ioncube/ioncube_loader_lin_5.6.so");
    if(!ssh2_scp_send($connection,$f,'/etc/php/5.6/fpm/conf.d/01-ioncube.ini')) die('Fail installing ioncube.');
    if(!ssh2_scp_send($connection,$f,'/etc/php/5.6/cli/conf.d/01-ioncube.ini')) die('Fail installing ioncube.');
    unlink($f);

    install_aces();

    run("mkdir -p /home/aces/etc/nginx/modules/");
    run("cp FILES/nginx-modules/conf/rtmp.conf /home/aces/etc/nginx/conf/");
    run("ln -s /usr/share/nginx/modules-available/mod-rtmp.conf /home/aces/etc/nginx/modules/");


    run("sysctl -p");

    set_db();

    echo "\nSTARTING SOFTWARE SERVICES..\n\n";
    run("systemctl daemon-reload");
    run("systemctl enable aces-nginx aces-php-fpm mysql cron");
    run("systemctl restart aces-nginx aces-php-fpm mysql aces cron");
    run("systemctl enable aces");


}

echo "\n\nFINISHED...\n";


?>

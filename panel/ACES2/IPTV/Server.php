<?php

namespace ACES2\IPTV;

use \ACES2\DB;

class Server {
    const ACTION_REMOVE_VOD = 'REMOVE_VOD_FILE';
    const ACTION_PROCESS_VOD = 'PROCESS_VOD';
    const ACTION_GET_CONTENT = 'GET_CONTENT';
    const ACTION_VIDEO_DIR_IMPORT = 'VIDEO_DIR_IMPORT';
    const ACTION_SERIES_DIR_IMPORT = 'SERIES_DIR_IMPORT';
    const ACTION_REMOVE_CHANNEL_FILES = 'REMOVE_CHANNEL_FILES';
    const ACTION_MAINTENANCE = 'MAINTENANCE';
    const ACTION_CLEAR_SYSTEM_LOGS = 'CLEAR_SYSTEM_LOGS';
    const ACTION_RESTART_STREAM = 'RESTART_STREAM';
    const ACTION_CHECK_VODS = 'CHECK_VODS';
    const ACTION_GET_STREAM_LOGS = 'GET_STREAM_LOGS';

    const STATUS_INSTALLING = -1 ;
    const STATUS_ONLINE = 1;
    const STATUS_OFFLINE = 0;
    const STATUS_ERROR_INSTALLING = -2;

    const STATUS_UPDATING = -3;
    const STATUS_ERROR_UPDATING = -4;

    public $id;
    protected $api_token = '';
    public $name = '';
    public $address = '';
    public $ip_address = '';
    public $port = 0;
    public $sport = 0;
    public $aces_version = '';
    public $network_port = 500;
    public $ssh_password = '';
    public $ssh_port = 22;

    private $ssh_connection;

    public function __construct(int $server_id) {

        if(!$server_id)
            throw new \Exception("Unable to get server #$server_id from database");

        $db = new DB;
        $r=$db->query("SELECT * FROM iptv_servers WHERE id = '$server_id'");
        if(!$row = $r->fetch_assoc())
            throw new \Exception("Unable to get server #$server_id from database");

        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->api_token = $row['api_token'];
        $this->address = $row['address'];
        $this->port = (int)$row['port'];
        $this->sport = (int)$row['sport'];
        $this->network_port  = $row['max_bandwidth_mbps'];
        $this->ssh_password = $row['ssh_pass'];

        $this->ip_address = empty($row['ip_address']) ? $this->address : $row['ip_address'];

    }

    public function send_action($action,$data=array()) {

        set_time_limit(0);

        if(empty($action))
            throw new \Exception("Unable to send server action. No action been selected");

        $action = strtoupper($action);

        $data['api_token'] = $this->api_token;
        $data = json_encode($data);

        //SHALL WE ALWAYS USE HTTP HERE?
        $url = isset($_SERVER['HTTPS']) && $this->sport > 0
            ? "https://{$this->address}:{$this->sport}/stream/api2.php?action=$action&data=$data"
            : "http://{$this->ip_address}:{$this->port}/stream/api2.php?action=$action&data=$data";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        if(!$resp=curl_exec($ch)) {
            throw new \Exception("Unable to send Server action '$action' to server $this->name. Error ".curl_error($ch));
        }

        $decode = json_decode($resp,1);

        if($decode['status'] == 0 ) return false;
        return $decode['data'] ;
        //return $decode['data'] ? $decode['data'] : [] ;


    }

    public function reboot() {

        $connection = \ssh2_connect($this->ip_address, 22);
        if(!@\ssh2_auth_password($connection, 'root', $this->ssh_password ))  {
            AcesLogE("Fail Connecting to ssh on server $this->name");
            return false;
        }

        \ssh2_exec($connection, "reboot");

        $db = new \ACES2\DB;
        $db->query("UPDATE iptv_servers SET status = 0 WHERE id = '$this->id'");

        ssh2_disconnect($connection);

        return true;

    }

    public function restart_aces():bool{

        $connection = \ssh2_connect($this->ip_address, 22);
        if(!@\ssh2_auth_password($connection, 'root', $this->ssh_password ))  {
            AcesLogE("Fail Connecting to ssh on server");
            return false;
        }

        if(!\ssh2_exec($connection, "systemctl restart aces"))
            AcesLogE("Fail to exec command to LB $this->name");

        ssh2_disconnect($connection);

        return true;
    }

    public function restart_service():bool{

        $connection = \ssh2_connect($this->ip_address, 22);
        if(!@\ssh2_auth_password($connection, 'root', $this->ssh_password ))  {
            AcesLogE("Fail Connecting to ssh on server");
            return false;
        }

        if(!\ssh2_exec($connection, "systemctl stop aces aces2-php-fpm aces2-nginx; sleep 3; killall -9 php; systemctl start aces aces2-php-fpm aces2-nginx"))
            AcesLogE("Fail to exec command to LB $this->name");

        ssh2_disconnect($connection);

        return true;
    }


    public function updateSettings($POST) {

        $name = $this->escString($POST['name']);

        $bandwidth =(int)$POST['network_port'];
        $address = $this->escString($POST['address']);
        if(!$address)
            throw new \Exception("The address is required.");

        $port = (int)$POST['port'];
        if(!$port)
            throw new \Exception("Port is required.");

        $sport = (int)$POST['sport'];
        if(!empty($POST['ssh_pass'])) {
            $ssh_pass = $this->escString($POST['ssh_pass']);
            $ssh_pass = ", ssh_pass = '$ssh_pass' ";
        }

        $db = new DB;
        $db->query("UPDATE iptv_servers SET  name = '$name', max_bandwidth_mbps = '$bandwidth',
                         port = '$port', sport = '$sport', address = '$address' $ssh_pass
                        WHERE id = $this->id ");
        $this->__construct($this->id);

        return true;

    }

    public function remove():bool {

        while(true) {
            if(!$connection = \ssh2_connect($this->ip_address, $this->ssh_port))
                break;
            if(!@\ssh2_auth_password($connection, 'root', $this->ssh_password ))
                break;

            ssh2_exec($connection, "systemctl stop aces2-php-fpm aces ; sleep 3; killall -9 php ;");

            break;
        }

        $db = new DB;
        $db->query("DELETE FROM iptv_servers WHERE id = '$this->id'");

        if($connection)
            ssh2_disconnect($connection);

        return true;
    }

    public function update($address, $server_name, $bandwidth_port, $ssh_password = '') {

        $db = new \ACES2\DB;
        $server_name = $db->escString($server_name);

        $db->query("UPDATE iptv_servers SET address = '$address', name = '$server_name', 
                        max_bandwidth_mbps = '$bandwidth_port'
                    WHERE id = '$this->id'");

        if($ssh_password)
            $db->query("UPDATE iptv_servers SET ssh_pass = '$ssh_password' WHERE id = '$this->id'");

    }

    public function updateAces() {

        $ERR = '';
        while(true) {
            //if(!$connection = \ssh2_connect($this->address, $this->ssh_port, [ 'hostkey' => 'ssh-rsa'] ))
            if(!$connection = \ssh2_connect($this->ip_address, $this->ssh_port ))
                break;
            if(!@\ssh2_auth_password($connection, 'root', $this->ssh_password ))
                break;


            $db = new DB;
            $s = self::STATUS_UPDATING;
            $db->query("UPDATE iptv_servers SET status = '$s' WHERE id = '$this->id'");


            $stream=ssh2_exec($connection, "/home/aces/bin/update.sh");
            $errorStream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);

            stream_set_blocking($errorStream, true);
            stream_set_blocking($stream, true);

            $ERR = stream_get_contents($errorStream);

            break;
        }

        if($ERR) {
            $s = self::STATUS_ERROR_UPDATING;
            $db->query("UPDATE iptv_servers SET status = '$s' WHERE id = '$this->id'");
            return $ERR;
        }


        return true;

    }

    public function getStreamLogs($stream_id) {

        $data['stream_id'] = $stream_id;
        return $this->send_action(self::ACTION_GET_STREAM_LOGS, $data);

    }

    public static function add($ip_address, $password, $ssh_port, $server_name, $serial,  $bandwidth_port = 1000 ) {

        if(!$connection = ssh2_connect($ip_address, $ssh_port, [ 'hostkey' => 'ecdsa-sha2-nistp256,ssh-rsa'] ))
            throw new \Exception("Unable to connect to server");

        if(!@ssh2_auth_password($connection, "root", $password))
            throw new \Exception("Unable to login to server. Wrong password ?");

        $db = new \ACES2\DB;
        $r=$db->query("SELECT id FROM iptv_servers WHERE address = '$ip_address' AND id = 1 ");
        if($r->num_rows > 0)
            throw new \Exception("Cannot reinstall main server from here.");

        $GLOBALS['server_address'] = $ip_address;
        $GLOBALS['connection']= $connection;
        unlink(ACES_ROOT."/logs/SERVER_INSTALL_{$GLOBALS['server_address']}.log");

        function logfile($msg) {
            error_log($msg."\n", 3 ,
                ACES_ROOT."/logs/SERVER_INSTALL_{$GLOBALS['server_address']}.log");
        }

        function run($comm,$out='stderr') {

            $command = $comm;
            if($out == 'stdout') $comm = $comm ." ; ";
            else $comm = "$comm >> ACES_INSTALLER.txt; echo $? ";

            $stream = ssh2_exec($GLOBALS['connection'], $comm);
            stream_set_blocking($stream, true);
            $output = \ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
            $stream_out=\ssh2_fetch_stream($stream, SSH2_STREAM_STDIO);

            $status=trim(stream_get_contents($stream_out));
            logfile(stream_get_contents($output)."\n");

            if($out=='stdout') return $status;

            if($status > 0 ){ return false; }
            else return true;

        }

        function err($server_id,$err_msg = '') {
            $db = new \ACES2\DB;
            $db->query("UPDATE iptv_servers SET status = '-2', error_msg = '$err_msg' 
                    WHERE id = '$server_id'");
            logfile($err_msg,  3 ,
                ACES_ROOT."/logs/SERVER_INSTALL_{$GLOBALS['server_address']}.log");
            exit;
        }

        $hid = trim(run('findmnt -n -o UUID $(stat -c \'%m\' "/")', 'stdout'));
        $status = file_get_contents("https://acescript.ddns.net/v2/test_serial.php?type=IPTVLB&serial=$serial&hid=$hid");
        if($status == 402 )
            throw new \Exception("License expired.");
        else if($status != 200 )
            throw new \Exception("License not found.");


        run('ls /etc/lsb-release ');
        $VER=(int)run(" . /etc/lsb-release && echo \${DISTRIB_RELEASE%%.*} ", 'stdout');
        if($VER != 18 && $VER != 20 && $VER != 22 && $VER != 24 )
            throw new \Exception("Unsupported Ubuntu $VER version.");


        //SETTING DATABASE.

        $NEW_API_TOKEN=md5(RAND().RAND());
        $r_server = $db->query("SELECT id,status FROM iptv_servers WHERE address = '$ip_address'");
        if(!$server=$r_server->fetch_assoc()) {

            $db->query("INSERT INTO iptv_servers (status,name,address,ip_address,port,max_bandwidth_mbps,api_token,ssh_pass) 
                VALUES('-1','$server_name','$ip_address','$ip_address','9090','$bandwidth_port','$NEW_API_TOKEN','$password') ");
            $server_id = $db->insert_id;

        } else {
            $server_id = $server['id'];
            if($$server['status'] == self::STATUS_INSTALLING)
                throw new \Exception("Installing process is running.");
            logfile("REINSTALLING");
            run("systemctl stop aces aces2-nginx aces2-php-fpm; sleep 2 ; killall -9 php ;  ");
            sleep(2);
            $db->query("UPDATE iptv_servers SET name = '$server_name', max_bandwidth_mbps = '$bandwidth_port', 
                        ssh_pass = '$password', api_token = '$NEW_API_TOKEN', status = '-1' 
                    WHERE id = '$server_id' ");

        }

        session_write_close();
        set_time_limit(-1);
        echo json_encode(array('complete' => 1, 'status' => 1));
        fastcgi_finish_request();

        logfile("UBUNTU ".$VER);

        run("useradd  -r -m -U aces; usermod -aG sudo aces ");

        $r_main = $db->query("SELECT address FROM iptv_servers WHERE id = '1'");
        $main_ip = $r_main->fetch_assoc()['address'];

        //INSTALLING PACKAGES
        logfile("INSTALLING PACKAGES");
        if(run("export DEBIAN_FRONTEND=noninteractive && apt -y update && apt-get -y install software-properties-common && 
        add-apt-repository -y universe && 
        apt-get -y install net-tools nginx libnginx-mod-rtmp curl libcurl4 mariadb-server daemon ffmpeg wget python3 python3-pip cron libwebp-dev libxpm-dev libonig-dev libzip-dev libjpeg-dev && 
        add-apt-repository -y universe"))
            err($server_id, 'Error installing packages');

        if($VER == 18 ) {
           run(" apt -y install  libonig4 libwebp6 libjpeg8 libxpm4 libonig-dev libzip-dev libssl-dev libwebp-dev libjpeg-dev libonig-dev libzip5 libssh2-1  ");
        } else if($VER == 20 ) {
            run(" apt -y install libonig5 libssh2-1-dev");
        } else if ($VER == 22 ) {
            run("apt -y install libzip4t64 libavif16 libonig5 libssh2-1");
        } else if ($VER == 24 ) {
            run("apt -y install libzip4t64 libavif16 libonig5 libssh2-1t64");
        }

        run("python3 -m pip install --upgrade youtube-dlc");

        //INSTALLING PHP
        logfile("INSTALLING PHP");
        run("rm -r /root/PHP83.tar.gz /root/php83");
        if(!run("wget https://acescript.ddns.net/downloads/PHP83-U{$VER}.tar.gz -O /root/PHP83.tar.gz"))
            err("Unable to download PHP.");
        run("cd /root/; tar xf PHP83.tar.gz; cp -r php83/ /opt/; rm -r php83/ PHP83.tar.gz;");
        run("ln -s /opt/php83/bin/php /usr/local/bin/php");


        run("mkdir -p /home/aces/{bin,panel,logs,run,stream,stream_tmp,tmp,vods,fonts,rtmp_push,backups,imported_guides,opc,sessions} ");
        run("mkdir -p /home/aces/tmp/vod_downloads");
        //run ("touch /home/aces/no_armor");

        run("rm -rf /home/aces/stream_tmp/*");
	    run("rm -rf /home/aces/run/*");

        //DOWNLOADING ACES
        logfile("DOWNLOADING ACES.");
        run("rm -rf /root/ACES/ /root/ACES2.tar.gz");
        if(!run("wget https://acescript.ddns.net/downloads/ACES2.tar.gz -O /root/ACES2.tar.gz"))
            err($server_id,"Fail downloading ACES Software.");

        if(!run("cd /root/ && tar xf /root/ACES2.tar.gz"))
            err($server_id,"Fail extracting files.");

        logfile("INSTALLING ACES");
        run("cd /root/ACES/; 
        cp aces_limits.conf  /etc/security/limits.d/50-aces_limits.conf;
        cp aces_sudoers /etc/sudoers.d/;
        cp aces.service aces2-nginx.service aces2-php-fpm.service aces2-shutdown.service /etc/systemd/system/;
        cp nginx-aces.conf /etc/nginx/;
        cp php.ini /opt/php83/etc/;
        cp php-fpm-aces.conf /opt/php83/etc/php-fpm.d/;
        cp php-fpm.conf /opt/php83/etc/;
        cp blacklist.txt /opt/php83/etc/;
        mkdir /home/aces/nginx-mods;
        cp nginx-stream.conf /home/aces/nginx-mods/;
        touch /home/aces/nginx-mods/nginx-panel.conf
        cp update.sh /home/aces/bin/;
        ");

        run("sed -i \"/%{PORT}/c\listen 9090;\"  /etc/nginx/nginx-aces.conf");

        run("cd /root/ACES/; 
            cp -r HOME/panel/{class,functions,ACES2} /home/aces/panel/
            cp -r HOME/bin/* /home/aces/bin/
            cp -r HOME/stream/* /home/aces/stream/
            cp -r HOME/fonts/* /home/aces/fonts/
            cp -r HOME/opc/* /home/aces/opc/
        ");

        //PERMISSIONS
        logfile("SETTING PERMISSIONS");
        run(
            "chown aces:aces -R /home/aces/;
            chmod o+x /home/aces/;
            chmod 777 /home/aces/tmp;
            chmod 777 /home/aces/tmp/vod_downloads/;
            chmod 777 /home/aces/stream_tmp;
            find /home/aces/panel/ -type f -exec chmod 644 {} \;
 	        find /home/aces/panel/ -type d -exec chmod 755 {} \;
        ");

        run(" bash -c 'if [ -z \"$(mount --fake --verbose --all | grep '/home/aces/stream_tmp')\" ]; then echo \"tmpfs /home/aces/stream_tmp tmpfs defaults,noatime,nosuid,nodev,noexec,mode=1777,size=65% 0 0\" >> /etc/fstab ; mount /home/aces/stream_tmp ; fi  '");

        run(" bash -c 'if [ -z \"$(mount --fake --verbose --all | grep '/home/aces/run')\" ]; then echo \"tmpfs /home/aces/run tmpfs defaults,noatime,nosuid,nodev,noexec,mode=1777,size=100M 0 0\" >> /etc/fstab ; mount /home/aces/run ; fi  '");

        #run("crontab -u aces -r ");
        #run('(crontab -u aces -l ; echo "0 */12 * * * php /home/aces/bin/get_lic.php")| crontab -u aces -');
        #run('(crontab -u aces -l ; echo "* * * * * php /home/aces/bin/aFolderWatch.php")| crontab -u aces -');
        #run('(crontab -u aces -l ; echo "* * * * * php /home/aces/bin/aces_iptv_conns.php")| crontab -u aces -' );

        run('(crontab -u root -l ; echo "* * * * * su aces -c \'php /home/aces/bin/aFolderWatch.php\'")| crontab -u root -');
        run('(crontab -u root -l ; echo "* * * * * su aces -c \'php /home/aces/bin/aces_iptv_conns.php\'")| crontab -u root -' );

        run('(crontab -u root -l ; echo "* */1 * * * su aces -c \'php /home/aces/bin/cron.php h\'")| crontab -u root -' );
        run('(crontab -u root -l ; echo "0 0 * * * su aces -c \'php /home/aces/bin/cron.php d\'")| crontab -u root -' );



        //WRITING CONFIG
        require( "/home/aces/stream/config.php" );
        $tfile="/home/aces/tmp/tmp_conf_file_.txt";
        if(!file_put_contents($tfile,"<?php\n\$DBHOST='$main_ip';\n\$DBUSER='$DBUSER';\n\$DBPASS='$DBPASS';\n\$DATABASE='$DATABASE';\n\n\$API_TOKEN='$NEW_API_TOKEN';\n\n\$FOLDER='/home/aces/stream_tmp/';\n\n\$SERVER_ID=$server_id;")) err("Fail writing config file.");
        ssh2_scp_send($connection,$tfile,'/home/aces/stream/config.php');
        unlink("$tfile");

        logfile("STARTING SERVICES.");
        run("sysctl -p ; 
            systemctl stop nginx;
            systemctl disable nginx;
            systemctl restart aces2-nginx aces2-php-fpm aces2-shutdown mysql aces cron;
            systemctl enable aces2-nginx aces2-php-fpm aces2-shutdown mysql aces cron
         ");

        $db->query("UPDATE iptv_servers SET error_msg = '', status = 0 
                    WHERE id = '$server_id'");

        logfile("FINISHED");

    }

}
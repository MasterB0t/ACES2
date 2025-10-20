<?php

namespace ACES2\IPTV;


use ACES\IPTV\StreamProfile;
use ACES2\DB;
use ACES2\File;

class Stream
{
    CONST STATUS_STOPPED = 0;
    CONST STATUS_CONNECTING = -1;
    CONST STATUS_STREAMING =  1;
    CONST STATUS_STANDBY =  2;
    CONST STATUS_SHUTTING_DOWN =  3;
    CONST STATUS_MOVING = 4;
    const STATUS_FILE_UNBUILD = 0;
    const STATUS_FILE_BUILDED = 2;
    const STATUS_FILE_BUILDING = 1;
    const STATUS_FILE_MOVING = 3;
    const SOURCE_TYPE_RTMP = 1;
    const SOURCE_TYPE_HTTP = 0;
    CONST CHANNEL_TYPE_STREAM = 0;
    CONST CHANNEL_TYPE_247 = 1;
    CONST CHANNEL_TYPE_EVENT = 2;

    public $id = 0;
    public $name = '';
    public $server_id = 0;
    public $tvg_id = '';
    public $category_id = 0;
    public $logo = '';
    public $stream = 0;
    public $ondemand = 0;
    public $type = 0;

    public $event_id = 0;

    public $stream_profile_id = 0;
    public $load_balances = [];
    public $enabled = 0;
    public $catchup = 0;
    public $catchup_server = 0;
    public $catchup_exp_days = 0;
    public $auto_update_name = 0;
    public $status = 0;
    public $source_type = 0;

    public function __construct(int $stream_id = null) {

        if(is_null($stream_id))
            return;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_channels WHERE id = '$stream_id'");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Unable to get stream from database.");

        $this->id = $row['id'];
        $this->name = $row['name'];
        $this->server_id = $row['stream_server'];
        $this->tvg_id = $row['tvg_id'];
        $this->category_id = $row['category_id'];
        $this->logo = $row['logo'];
        $this->stream = $row['stream'];
        $this->enabled = $row['enable'];
        $this->catchup = $row['catchup'];
        $this->catchup_server = $row['catchup_server'];
        $this->auto_update_name = $row['auto_update'];
        $this->type = $row['type'];
        $this->stream_profile_id  = $row['stream_profile'];
        $this->ondemand = $row['ondemand'];
        $this->catchup_exp_days = $row['catchup_expire_days'];
        $this->source_type = $row['source_type'];

        $r=$db->query("SELECT source_server as source , destination_server as destination 
                            FROM iptv_channels_in_lb WHERE channel_id = $this->id ");

        while($row=$r->fetch_assoc())
            $this->load_balances[] = $row;

        $this->getStatus();

    }

    public function getStatus() {
        $db = new \ACES2\DB;
        $r_streaming = $db->query("SELECT id,status FROM iptv_streaming WHERE chan_id = $this->id ");
        if(!$this->status=$r_streaming->fetch_assoc()['status'])
            $this->status = 0;

        return $this->status ;
    }

    public function getStreamSources() {

        if(!$this->id || $this->type == $this::CHANNEL_TYPE_247 )
            return [];

        $db = new DB;

        $sources = [];
        $r=$db->query("SELECT id,is_backup,enable as is_enabled,source as name,url  FROM iptv_channels_sources 
                                               WHERE chan_id = $this->id ORDER BY priority ASC ");
        while($row=$r->fetch_assoc()) $sources[] = $row;

        return $sources;

    }
    public function getBouquets() {

        if(!$this->id)
            return [];

        $db = new \ACES2\DB();

        $bouquets = [];
        $r = $db->query("SELECT bouquet_id FROM iptv_channels_in_bouquet WHERE chan_id = $this->id ");
        while($id=$r->fetch_assoc()['bouquet_id'])
            $bouquets[] = $id;

        return $bouquets;

    }

    public function setName(string $name) {
        $this->name = $name;
    }
    public function setEvent(int $event_id = 0) {
        $this->event_id = $event_id;
        $this->type = $event_id ? self::CHANNEL_TYPE_EVENT : self::CHANNEL_TYPE_STREAM;
    }
    public function setTvgId(string $tvg_id) : void {
        $this->tvg_id = $tvg_id;
    }
    public function setCategoryId(int $category_id) : void {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT id FROM iptv_stream_categories WHERE id = '$category_id' ");
        if($r->num_rows < 1)
            throw new \Exception("Category #$category_id do not exist.");
        $this->category_id = $category_id;
    }
    public function setServerID(int $server_id, bool $restart_stream = true ) : void {

        if($this->server_id == $server_id)
            return;

        $db = new \ACES2\DB();
        $r=$db->query("SELECT id FROM iptv_servers WHERE id = '$server_id' ");
        if($r->num_rows < 1)
            throw new \Exception("Server #$server_id do not exist.");
        $this->server_id = $server_id;

        $db->query("UPDATE iptv_channels SET stream_server = '$server_id' WHERE id = '$this->id' ");

        foreach($this->load_balances as $load_balance ) {
            $this->removeLoadBalance($load_balance['destination']);
        }

        $this->getStatus();

        if($restart_stream && $this->status != self::STATUS_STOPPED)
            $this->restart();


    }
    public function setStreamProfile(int $stream_profile_id) : void {
        $this->stream_profile_id = $stream_profile_id;
    }
    public function setOnDemand(bool $OnDemand) : void {
        $this->ondemand = $OnDemand ? 1 : 0;
    }
    public function setToStream(bool $stream) : void {
        $this->stream = $stream ? 1 : 0;
    }
    public function setLogo(string $logo_url): void {

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if (false === $logo_ext = array_search(
                $finfo->buffer(file_get_contents( $logo_url)), array(
                'jpg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif'
            ), true
            )) {
                throw new \Exception("No valid logo image.");
        }

        $db = new \ACES2\DB;

        $LogoFilename = "s$this->id-".time().".$logo_ext";
        if(copy($logo_url, "/home/aces/panel/logos/".$LogoFilename)) {
            if($this->logo)
                unlink($this->logo);

            $db->query("UPDATE iptv_channels SET logo = '$LogoFilename' WHERE id = '$this->id' ");
        }

    }
    public function setBouquets(array $bouquets = []) : void {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_channels_in_bouquet WHERE chan_id = $this->id ");
        foreach($bouquets as $bouquet) {
            if($b = (int)$bouquet)
                $db->query("INSERT INTO iptv_channels_in_bouquet (chan_id, bouquet_id) 
                    VALUES ('$this->id', '$b')");
        }
    }

    public function restart()  {

        session_write_close();

        $db = new \ACES2\DB;

        $db->query("DELETE FROM iptv_streaming WHERE chan_id = $this->id ");
        $db->query("DELETE FROM iptv_access WHERE chan_id = $this->id ");

        if (!$this->stream) //IF NOT SET TO STREAM NOTHING MORE TO DO...
            return;

        $no_client = (count($this->load_balances) > 0) ? 1 : 0 ;

        $time = time();

        if($this->ondemand) {
            $db->query("INSERT INTO iptv_streaming (chan_id,status,action,no_client,server_id,last_access,start_time) 
                                VALUES ('$this->id',2,0,'$no_client','$this->server_id',NOW(),$time) ");
            $StreamStats = new StreamStats($this->stream, $this->server_id);
            $StreamStats->setStandBy();

        } else {
            $db->query("INSERT INTO iptv_streaming (chan_id,action,no_client,server_id,last_access,start_time) 
                                VALUES ('$this->id',1,'$no_client','$this->server_id',NOW(),$time) ");
        }

        if( count($this->load_balances) > 0 ) {

            foreach($this->load_balances as $lb  ) {
                $d = $lb['destination'];
                $s = $lb['source'];

                //ONLY PUT THE STREAM ON LB AS ONDEMAND WHEN IT CONNECTING DIRECT TO SOURCE OTHERWISE START THE STREAM.
                if($s == 0 && $this->ondemand) {
                    $StreamStats = new StreamStats($this->stream, $d);
                    $StreamStats->setStandBy();
                    $db->query("INSERT INTO iptv_streaming (chan_id,status,action,server_id,source_server_id,last_access,start_time)
                                        VALUES ('$this->id',2,0,'$d','$s',NOW(),$time) ");
                }
                else
                    $db->query("INSERT INTO iptv_streaming (chan_id,action,server_id,source_server_id,last_access,start_time) 
                                        VALUES ('$this->id',1,'$d','$s',NOW(),$time) ");

            }

        }

        if($this->catchup) {
            if($PID = file_get_contents("/home/aces/run/catchup-{$this->id}.pid")) {
                exec("kill -9 $PID ");
                unlink("/home/aces/run/catchup-{$this->id}.pid");
            }
            exec("nohup php /home/aces/bin/catchup.php -{$this->id}- > /dev/null & " );
        }

    }

    public function stop() {

        session_write_close();

        $db = new \ACES2\DB;

        $r = $db->query("SELECT id,server_id FROM iptv_streaming WHERE chan_id = $this->id ");
        while($row=$r->fetch_assoc()) {
            $StreamStats = new StreamStats($this->id, $row['server_id']);
            $StreamStats->setShutoff();
        }

        $db->query("UPDATE iptv_streaming SET action = 2, status = 3 where chan_id = {$this->id} ");
        $db->query("DELETE FROM iptv_access WHERE chan_id = $this->id ");

        exec("kill -9  $(ps -eAf  | grep /home/aces/bin/catchup.php | grep '\-{$this->id}\-'  | grep -v 'ps -eAf' | awk '{print $2 }' )");

        return true;

    }

    protected function removeLogo(){
        if(is_file("/home/aces/panel/logos/$this->logo"))
            unlink("/home/aces/panel/logos/$this->logo");
    }

    public function remove() {

        $db = new \ACES2\DB;

        $this->stop();

        $db->query("DELETE FROM iptv_channels_sources WHERE chan_id = $this->id ");
        $db->query("DELETE FROM iptv_channels WHERE id = $this->id ");
        $db->query("DELETE FROM iptv_channels_in_bouquet where chan_id = $this->id");
        $db->query("DELETE FROM iptv_stream_options WHERE only_chan_id = $this->id ");
        $db->query("DELETE FROM iptv_streaming WHERE chan_id = $this->id ");


        if($this->type == $this::CHANNEL_TYPE_247 ) {

            $Server = new \ACES2\IPTV\SERVER($this->server_id);

            $r=$db->query("SELECT id FROM iptv_channel_files WHERE channel_id = '$this->id' ");
            while($file=$r->fetch_assoc()['id']) $files[] = $file;
            $Server->send_action($Server::ACTION_REMOVE_CHANNEL_FILES, array('channel_files'=>$files));

            $db->query("DELETE FROM iptv_channel_files WHERE channel_id = $this->id ");
        }


        $this->removeLogo();

        return true;
    }

    static function addStream($POST,$FILES) {

        $db = new \ACES2\DB;

        if(empty($POST['name']))
            throw new \Exception(ERRORS::IPTV_STREAM_NAME_REQUIRED);
        $name = $db->escString($POST['name']);

        $category_id = (int)$POST['category_id'];
        if(!$category_id)
            throw new \Exception(ERRORS::IPTV_STREAM_CATEGORY_REQUIRED);

        $stream_server = (int)$POST['server_id'];
        if(!$stream_server)
            throw new \Exception(ERRORS::IPTV_STREAM_SERVER_REQUIRED);

        $enable = $POST['enable'] ? 1 : 0;
        $auto_update_name = $POST['auto_update'] ? 1 : 0;
        $stream_it = $POST['stream'] ? 1 : 0;
        $ondemand = $POST['ondemand'] ? 1 : 0;
        $build_codecs = $POST['build_codecs'];
        $channel_type = $POST['channel_type'] ? 1 : 0;
        $stream_profile_id = (int)$POST['stream_profile_id'];
        $source_type = (int)$POST['source_type'];
        $tvg_id = $db->escString($POST['tvg_id']);

        //UPLOAD LOGO
        if (!empty($FILES['tmp_name'])) {
            $Logo = new \ACES2\File($FILES, \ACES2\File::getMimeImages() );
        }
        //WEB LOGO
        if (!empty($POST['logo'])) {

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if (false === $logo_ext = array_search(
                    $finfo->buffer(file_get_contents($POST['logo'])), array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif'
                ), true
                )) {
                throw new \Exception(ERRORS::IPTV_STREAM_LOGO_NOT_SUPPORTED);
            }
        }


        $ro=$db->query("SELECT ordering FROM iptv_channels ORDER BY ordering DESC LIMIT 1");
        $ordering = ((int)$ro->fetch_assoc()['ordering']) + 1;

        $rn=$db->query("SELECT number FROM iptv_channels ORDER BY number DESC LIMIT 1");
        $number = ((int)$rn->fetch_assoc()['number']) + 1 ;

        if(!$stream_profile_id)
            $STREAM_PROFILE= \ACES2\IPTV\StreamProfile::create($POST['stream_profile']);

        $db->query("INSERT INTO iptv_channels (type,`name`,tvg_id,category_id,`enable`,stream,ondemand,stream_profile,
                           stream_server,ordering,number,auto_update,build_codecs,source_type) 
                VALUES ('$channel_type','$name','$tvg_id','$category_id','$enable','$stream_it','$ondemand',
                        '$stream_profile_id','$stream_server',$ordering,$number,$auto_update_name,'$build_codecs','$source_type')  ");
        $stream_id = $db->insert_id;

        //ADDING STREAM PROFILE.
        if(!$stream_profile_id) {

            $db->query("UPDATE iptv_stream_options SET only_chan_id = $stream_id WHERE id = $STREAM_PROFILE->id ");
            $db->query("UPDATE iptv_channels SET stream_profile = '$STREAM_PROFILE->id' WHERE id = $stream_id ");

        }

        $LogoFilename = "";
        if(!empty($FILES['tmp_name'])) {
            $Logo->upload("s{$stream_id}-".time(), DOC_ROOT.'/logos/' );
            $LogoFilename = $Logo->filename;


        } else if (!empty($POST['logo'])) {
            $LogoFilename = "s$stream_id-".time().".$logo_ext";
            copy($POST['logo'], "/home/aces/panel/logos/".$LogoFilename);

        }

        if($LogoFilename)
            $db->query("UPDATE iptv_channels SET logo = '$LogoFilename' WHERE id = $stream_id ");

        foreach($POST['source_url'] as $i => $v ) {

            $s_name = $db->escString($POST['source_name'][$i]);
            $s_url = $POST['source_url'][$i];
            $s_enable = $POST['source_enable'][$i] ? 1 : 0;
            $s_backup = $POST['source_backup'][$i] ? 1 : 0;
            $s_prio = $i;

            $db->query("INSERT INTO iptv_channels_sources (chan_id, priority, is_backup, enable, source,  url) 
                                VALUES($stream_id, '$s_prio', '$s_backup', '$s_enable', '$s_name', '$s_url' )");

        }

        foreach ($POST['bouquets'] as $pkg) {

            if((int)$pkg)
                $db->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id,chan_id) VALUES ($pkg,$stream_id) ");

        }

        $Stream = new self($stream_id);

        if($POST['start'])
            $Stream->restart();

        return $Stream;

    }

    static public function addStream2(
            string $name, int $server_id, int $category_id, int $stream_profile_id, bool $stream_it = true,
            bool $OnDemand = false, string  $tvg_id = '' ) {

        $db = new \ACES2\DB;

        $stream = new self(null);
        $stream->setName($name);
        $stream->setServerID($server_id);
        $stream->setCategoryID($category_id);
        $stream->setOndemand($OnDemand);
        $stream->setTvgID($tvg_id);
        $stream->setToStream($stream_it);
        $stream->setStreamProfile($stream_profile_id);

        $ro=$db->query("SELECT ordering FROM iptv_channels ORDER BY ordering DESC LIMIT 1");
        $ordering = ((int)$ro->fetch_assoc()['ordering']) + 1;

        $rn=$db->query("SELECT number FROM iptv_channels ORDER BY number DESC LIMIT 1");
        $number = ((int)$rn->fetch_assoc()['number']) + 1 ;

        $name = $db->escString($stream->name);
        $tvg_id = $db->escString($stream->tvg_id);
        $type = self::CHANNEL_TYPE_STREAM;

        $db->query("INSERT INTO iptv_channels ( type, name, tvg_id, category_id, 
                        stream, ondemand, stream_server, stream_profile, enable, ordering, number)
            VALUES('$type', '$name', '$tvg_id', '$stream->category_id', '$stream->stream', '$stream->ondemand', 
                   '$stream->server_id', '$stream->stream_profile_id', 1, $ordering, $number);
        ");

        return new self($db->insert_id);
    }

    public function save() {

        $db = new \ACES2\DB;
        $name = $db->escString($this->name);
        $tvg_id = $db->escString($this->tvg_id);

        $db->query("UPDATE iptv_channels SET name = '$this->name', stream_server = '$this->server_id', 
                             stream_profile = '$this->stream_profile_id', category_id = '$this->category_id', tvg_id = '$tvg_id',
                             ondemand = '$this->ondemand', stream = '$this->stream', enable = '$this->enabled', event_id = '$this->event_id',
                             type = '$this->type'
                WHERE id = '$this->id'
        ");
    }

    public function updateStream($POST,$FILE) {

        $db = new \ACES2\DB;

        if(empty($POST['name']))
            throw new \Exception(ERRORS::IPTV_STREAM_NAME_REQUIRED);
        $name = $db->escString($POST['name']);

        $tvg_id = $this->tvg_id;
        if($POST['tvg_id']) {
            $tvg_id = $db->escString($POST['tvg_id']);
            $tvg_id = str_replace('"', '', $tvg_id);
            $tvg_id = str_replace("'", '', $tvg_id);
        }

        $category_id = (int)$POST['category_id'];
        if(!$category_id)
            throw new \Exception(ERRORS::IPTV_STREAM_CATEGORY_REQUIRED);

        $stream_server = (int)$POST['server_id'];
        if(!$stream_server)
            throw new \Exception(ERRORS::IPTV_STREAM_SERVER_REQUIRED);


        $enable = $POST['enable'] ? 1 : 0;
        $auto_update_name = $POST['auto_update'] ? 1 : 0;
        $stream_it = $POST['stream'] ? 1 : 0;
        $ondemand = $POST['ondemand'] ? 1 : 0;
        $build_codecs = $POST['build_codecs'];
        $channel_type = $POST['channel_type'] ? 1 : 0;
        $stream_profile_id = (int)$POST['stream_profile_id'];
        $source_type = (int)$POST['source_type'];

        $stream_profile_id = ( (int)$POST['stream_profile_id'] ) ? $POST['stream_profile_id'] : $this->stream_profile_id;

        //UPLOAD LOGO
        if (!empty($FILE['tmp_name'])) {
            $Logo = new \ACES2\File($FILE, \ACES2\File::getMimeImages() );
        }
        //WEB LOGO
        if (!empty($POST['logo'])) {

            $finfo = new \finfo(FILEINFO_MIME_TYPE);
            if (false === $logo_ext = array_search(
                    $finfo->buffer(file_get_contents($POST['logo'])), array(
                    'jpg' => 'image/jpeg',
                    'png' => 'image/png',
                    'gif' => 'image/gif'
                ), true
                )) {
                throw new \Exception(ERRORS::IPTV_STREAM_LOGO_NOT_SUPPORTED);
            }
        }

        $db->query("UPDATE iptv_channels SET name = '$name', category_id = $category_id, tvg_id = '$tvg_id', ondemand = '$ondemand', 
                         auto_update = '$auto_update_name', stream = '$stream_it', `enable` = '$enable',  build_codecs = '$build_codecs', 
                         stream_server = '$stream_server', stream_profile = $stream_profile_id, source_type = '$source_type'
                     WHERE id = $this->id  ");

        //SETTING STREAM SERVER WITH FUNCTION TO REMOVES LBS IF IT HAVE
        $this->setServerID($stream_server, false);

        $LogoFilename = "";
        if(!empty($FILE['tmp_name'])) {
            $Logo->upload("s{$this->id}-".time(), DOC_ROOT.'/logos/' );
            $LogoFilename = $Logo->filename;


        } else if (!empty($POST['logo'])) {
            $LogoFilename = "s$this->id-".time().".$logo_ext";
            copy($POST['logo'], "/home/aces/panel/logos/".$LogoFilename);
        }

        if($LogoFilename) {
            $this->removeLogo();
            $db->query("UPDATE iptv_channels SET logo = '$LogoFilename' WHERE id = $this->id ");
            $this->logo = $LogoFilename;
        }


        $db->query("DELETE FROM iptv_channels_in_bouquet WHERE chan_id = $this->id ");
        foreach($POST['bouquets'] as $bouquet ) {
            $bouquet = (int)$bouquet;
            if($bouquet)
                $db->query("INSERT INTO iptv_channels_in_bouquet 
                    (bouquet_id, chan_id) VALUE ($bouquet,$this->id)");
        }


        //UPDATING SOURCES
        foreach($POST['source_url'] as $i => $v ) {

            $s_id = (int)$POST['source_id'][$i];
            $s_name = $db->escString($POST['source_name'][$i]);
            $s_url = $POST['source_url'][$i];
            $s_enable = $POST['source_enable'][$i] ? 1 : 0;
            $s_backup = $POST['source_backup'][$i] ? 1 : 0;
            $s_prio = $i;

            if($s_id)
                $db->query("UPDATE iptv_channels_sources SET source = '$s_name', url = '$s_url', is_backup = '$s_backup',
                                 enable = '$s_enable', priority = '$s_prio' WHERE id = $s_id ");
            else
                $db->query("INSERT INTO iptv_channels_sources (chan_id, priority, is_backup, enable, source,  url) 
                    VALUES($this->id, '$s_prio', '$s_backup', '$s_enable', '$s_name', '$s_url' )");


        }

        //REMOVING SOURCES FOR BOTH CHANNELS AND STREAMS.
        if(is_array($POST['remove_source']))
        foreach($POST['remove_source'] as $s_id ) {
            $remove_id = (int)$s_id;
            $db->query("DELETE FROM iptv_channels_sources WHERE id = $remove_id ");
        }

        $restart = $POST['start'];
        if(!$restart) {
            if($this->server_id != $stream_server
                || !$this->stream && $stream_it
                || $stream_it && $this->ondemand != $ondemand
                || $stream_profile_id != $this->stream_profile_id
                || $source_type != $this->source_type
            ) $restart = 1;
        }

        if($restart)
            $this->restart();

    }

    public function addLoadBalance( int $source, int $destination_server) {

        $db = new DB();

        if(!$destination_server)
            throw new \Exception("Select a destination server.");

        if($source == $destination_server )
            throw new \Exception("Source and destination server can not be the same");

        $r=$db->query("SELECT channel_id FROM iptv_channels_in_lb 
                  WHERE channel_id = $this->id AND destination_server = $destination_server ");
        if($r->fetch_assoc())
            throw new \Exception("This server #$destination_server is already set as destination.");

        $db->query("INSERT INTO iptv_channels_in_lb (channel_id, source_server, destination_server) 
            VALUE ('$this->id', $source, $destination_server)");

        $this->__construct($this->id);

        $r= $db->query("SELECT id FROM iptv_streaming  WHERE chan_id = $this->id AND server_id = $this->server_id ");
        if($r->fetch_assoc()) {

            $db->query("UPDATE iptv_streaming SET no_client = 1 WHERE chan_id = $this->id AND server_id = $this->server_id ");

            //STARTING THE STREAM ON LB
            $db->query("DELETE FROM iptv_streaming WHERE chan_id = $this->id AND server_id = $destination_server ");
            $db->query("INSERT INTO iptv_streaming (chan_id,action,server_id,source_server_id,last_access,start_time) VALUES ('$this->id',1,'$destination_server','$source',NOW(),UNIX_TIMESTAMP()) ");

        }

        return true;
    }

    public function removeLoadBalance( int $destination_server) {

        $db = new DB();

        if(!$destination_server)
            return false;

        $db->query("DELETE FROM iptv_channels_in_lb WHERE channel_id = $this->id AND destination_server = $destination_server ");

        $db->query("DELETE FROM iptv_streaming WHERE chan_id = $this->id AND server_id = $destination_server ");

        $this->__construct($this->id);

        if(count($this->load_balances) == 0 )
            $db->query("UPDATE iptv_streaming SET no_client = 0 WHERE chan_id = $this->id AND server_id = $this->server_id ");

        return true;

    }

}?>

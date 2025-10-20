<?php

namespace ACES2\IPTV;

class Channel extends \ACES2\IPTV\Stream {


    public function __construct(int $stream_id) {
        parent::__construct($stream_id);
    }

    public static function addChannel($POST, $FILE):self  {

        $db = new \ACES2\DB;

        if(empty($POST['name']))
            throw new \ACES\Exception(ERRORS::IPTV_STREAM_NAME_REQUIRED);
        $name = $db->escString($POST['name']);

        $category_id = (int)$POST['category_id'];
        if(!$category_id)
            throw new \Exception(ERRORS::IPTV_STREAM_CATEGORY_REQUIRED);

        $stream_server = (int)$POST['server_id'];
        if(!$stream_server)
            throw new \Exception(ERRORS::IPTV_STREAM_SERVER_REQUIRED);

        $enable = $POST['enable'] ? 1 : 0;
        $stream_it = 1;
        $build_codecs = $POST['build_codecs'] ?: 'h264:acc';
        $channel_type = 1;
        $source_type = (int)$POST['source_type'];

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

        $ro=$db->query("SELECT ordering FROM iptv_channels ORDER BY ordering DESC LIMIT 1");
        $ordering = ((int)$ro->fetch_assoc()['ordering']) + 1;

        $rn=$db->query("SELECT number FROM iptv_channels ORDER BY number DESC LIMIT 1");
        $number = ((int)$rn->fetch_assoc()['number']) + 1 ;

        $db->query("INSERT INTO iptv_channels (type,`name`,category_id,`enable`,stream,ondemand,stream_profile,
                           stream_server,ordering,number,auto_update,build_codecs,source_type) 
                VALUES ('$channel_type','$name','$category_id','$enable','$stream_it','0',
                        '0','$stream_server',$ordering,$number,0,'$build_codecs','$source_type')  ");
        $stream_id = $db->insert_id;

        //EPG FOR CHANNEL
        $db->query(" UPDATE iptv_channels SET tvg_id = 'aces-channel-$stream_id' WHERE id = '$stream_id'");

        foreach($POST['bouquets'] as $bouquet ) {
            $bouquet = (int)$bouquet;
            if($bouquet)
                $db->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id, chan_id) VALUE ($bouquet,$stream_id)");
        }

        //UPLOADING LOGO..
        $LogoFilename = "";
        if(!empty($FILES['tmp_name'])) {
            $Logo->upload("s{$stream_id}-".time(), DOC_ROOT.'/logos/' );
            $LogoFilename = $Logo->filename;


        } else if (!empty($POST['logo'])) {
            $LogoFilename = "s$stream_id-".time()."$logo_ext";
            copy($POST['logo'], "/home/aces/panel/logos/".$LogoFilename);

        }

        if($LogoFilename)
            $db->query("UPDATE iptv_channels SET logo = '$LogoFilename' WHERE id = $stream_id ");


        //SOURCES
        $file_order = 0;
        foreach ($POST['files'] as $i => $file_id) {
            $can_start = 1;
            $type=(int)$POST['files_type'][$i];

            if($type > 0 ) {
                $file_id = $db->escString($file_id);
                $db->query("INSERT INTO iptv_channel_files (type,channel_id,val,ordering) 
                                    VALUES($type,'$stream_id','$file_id',$file_order) ");
            } else {

                if(!is_numeric($file_id)) {
                    foreach(explode(',',$file_id) as $f ) { $file_order++;
                        $db->query("INSERT INTO iptv_channel_files (channel_id,file_id,ordering)
                                            VALUES('$stream_id','$f',$file_order) ");
                    }
                } else {
                    $file_order++;
                    $db->query("INSERT INTO iptv_channel_files (channel_id,file_id,ordering) 
                                        VALUES('$stream_id','$file_id',$file_order) ");
                }
            }



        }

        $Channel = new \ACES2\IPTV\Channel($stream_id);

        if($POST['start'])
            $Channel->restart();

        return $Channel;

    }

    public function updateChannel($POST, $FILE) {

        $db = new \ACES2\DB;

        if(empty($POST['name']))
            throw new \ACES\Exception(ERRORS::IPTV_STREAM_NAME_REQUIRED);
        $name = $db->escString($POST['name']);

        $category_id = (int)$POST['category_id'];
        if(!$category_id)
            throw new \Exception(ERRORS::IPTV_STREAM_CATEGORY_REQUIRED);

        $stream_server = (int)$POST['server_id'];
        if(!$stream_server)
            throw new \Exception(ERRORS::IPTV_STREAM_SERVER_REQUIRED);

        $enable = $POST['enable'] ? 1 : 0;

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

        $db->query("UPDATE iptv_channels SET name = '$name', category_id = '$category_id', enable = '$enable',
                        stream_server = '$stream_server'
                     WHERE id = $this->id ");


        //BOUQUETS
        $db->query("DELETE FROM iptv_channels_in_bouquet WHERE chan_id = $this->id ");
        foreach($POST['bouquets'] as $bouquet ) {
            $bouquet = (int)$bouquet;
            if($bouquet)
                $db->query("INSERT INTO iptv_channels_in_bouquet (bouquet_id, chan_id) VALUE ($bouquet,$this->id)");
        }


        //LOGO
        $LogoFilename = "";
        if(!empty($FILE['tmp_name'])) {
            $Logo->upload("s{$this->id}-".time(), DOC_ROOT.'/logos/' );
            $LogoFilename = $Logo->filename;


        } else if (!empty($POST['logo'])) {
            $LogoFilename = "s$this->id-".time()."$logo_ext";
            copy($POST['logo'], "/home/aces/panel/logos/".$LogoFilename);
        }

        if($LogoFilename) {
            $this->removeLogo();
            $db->query("UPDATE iptv_channels SET logo = '$LogoFilename' WHERE id = $this->id ");
            $this->logo = $LogoFilename;
        }


        //SOURCES
        if(!is_array($POST['files']))
            $POST['files'] = [];

        $remove_files = array();
        $rf=$db->query("SELECT * FROM iptv_channel_files WHERE channel_id = $this->id ");
        while($frow=$rf->fetch_assoc()) { $files[] = $frow['id'];
            if(in_array($frow['file_id'],$POST['files']) || in_array($frow['val'],$POST['files'])) {
                if($frow['type'] > 0 )
                    $o=array_search($frow['val'],$POST['files']);

                else
                    $o=array_search($frow['file_id'],$POST['files']);

                unset($POST['files'][$o]);
                $db->query("UPDATE iptv_channel_files set ordering = '$o' WHERE id = '{$frow['id']}' ");
            } else {
                $remove_files[] = $frow['id'];
                $db->query("DELETE FROM iptv_channel_files WHERE id = {$frow['id']}");
            }

        }

        $Server = new \ACES2\IPTV\Server($this->server_id);

        //REMOVING FILES
        if(count($remove_files)>0)
            $Server->send_action($Server::ACTION_REMOVE_CHANNEL_FILES, array('channel_files'=>$remove_files));

        foreach($POST['files'] as $o => $file_id ) {

            $type=(int)$POST['files_type'][$o];
            $_file = $db->escString($POST['files'][$o]);

            if( $type > 0  ) {
                $db->query("INSERT INTO iptv_channel_files (type,channel_id,val,ordering) VALUES($type,'$this->id','$_file',$o) ");


            } else if(!is_numeric($file_id)) {
                foreach(explode(',',$file_id) as $f ) {
                    $o++;
                    $db->query("INSERT INTO iptv_channel_files (channel_id,file_id,ordering) VALUES('$this->id','$f',$o) ");
                }
            } else {
                $o++;
                $db->query("INSERT INTO iptv_channel_files (channel_id,file_id,ordering) VALUES('$this->id','$file_id',$o) ");
            }
        }


        //SERVER HAVE CHANGE REBUILD
        if( $this->server_id != $stream_server ) {
            $this->query("UPDATE iptv_channel_files SET status = 0 WHERE channel_id = $this->id ");
            $Server->send_action(SERVER::ACTION_REMOVE_CHANNEL_FILES, array('channel_files'=>$files));
        }

        $db->query("DELETE FROM iptv_epg WHERE chan_id = '$this->server_id' ");

    }



}
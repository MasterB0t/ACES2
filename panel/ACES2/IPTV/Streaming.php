<?php

namespace ACES2\IPTV;

class Streaming {
    const SPEED_EXCELENT = 1;
    const SPEED_FAST = 2;
    const SPEED_SLOW = 3;
    const SPEED_VERY_SLOW = 4;
    const SPEED_NOTHING = 5;

    const STATUS_CONNECTING = 0;
    const STATUS_STREAMING = 1;
    const STATUS_STANDBY = 2;
    const STATUS_ONDEMAND = 2;
    const STATUS_SHUTINGDOWN = 3;

    public $id = 0;
    public $stream_id = 0;
    public $streaming_url = '';
    public $server_id = 0;
    public $status = 0;
    private $db_connection = null;

    public function __construct( int $streaming_id = 0 ) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM iptv_streaming WHERE id = '$streaming_id'");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Streaming could not found in database #$streaming_id");

        $this->id = $streaming_id;
        $this->stream_id = (int)$row['chan_id'];
        $this->streaming_url = $row['streaming'];
        $this->server_id = (int)$row['server_id'];
        $this->status = (int)$row['status'];
        $this->db_connection = $db;

    }

    private function getDatabaseConnection() {

        $db = $this->db_connection;

        if(!$this->db_connection->ping()) {
            while(true) {
                $this->db_connection = new \ACES2\DB;
                if($this->db_connection->connect_errno > 0) { sleep(5); } //WE CANNOT EXIST IF THERE IS AN ERROR THIS CAN BE RUN FROM iptv_stream.php
                else break;
            }
        }

        return $this->db_connection;
    }

    public function updateStreamingInfo():void {

        clearstatcache();

        $chunk = exec("tail -1 /home/aces/stream_tmp/$this->stream_id-.m3u8 ");
        if(!$chunk) {

            return;
        }


        //$stream_v_info = json_decode(shell_exec(" ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk ",true));
        $stream_v_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams v -print_format json -show_entries stream=width,height,codec_name,r_frame_rate /home/aces/stream_tmp/$chunk " ),true);
        $stream_a_info = json_decode(shell_exec( " ffprobe -v quiet -select_streams a -print_format json -show_entries stream=codec_name /home/aces/stream_tmp/$chunk " ),true);
        $bitrate = (int) shell_exec( "ffprobe -v quiet  -print_format json -show_entries format=bit_rate -of default=noprint_wrappers=1:nokey=1  /home/aces/stream_tmp/$chunk  " );


        $s_info = array();
        $video_codec = $stream_v_info['streams'][0]['codec_name'];
        $audio_codec = $stream_a_info['streams'][0]['codec_name'];
        $resolution = "{$stream_v_info['streams'][0]['width']}x{$stream_v_info['streams'][0]['height']}";
        $fps = round( (int) explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[0] /
            explode( '/', $stream_v_info['streams'][0]['r_frame_rate'] )[1],0)  ;


        $db = $this->getDatabaseConnection();
        $db->query("UPDATE iptv_streaming SET video_codec = '$video_codec', audio_codec = '$audio_codec', fps = '$fps',
                          bitrate = '$bitrate'
                      WHERE id = '$this->id' ");


    }

}
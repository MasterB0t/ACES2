<?php

namespace ACES2\IPTV;

class StreamStats {

    CONST TYPE_SHUTOFF = 0;
    CONST TYPE_STANDBY = 1;
    CONST TYPE_CONNECTING = 2;
    CONST TYPE_STREAMING = 3;
    CONST TYPE_DISCONNECT = 4;

    private $stream_id = 0;
    private $server_id = 0;
    private $status =0;
    private $streaming_url = '';

    public function __construct(int $stream_id, int $server_id) {
        $this->stream_id = $stream_id;
        $this->server_id = $server_id;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT type,source_url FROM iptv_stream_stats 
                       WHERE stream_id=" . $this->stream_id . " AND server_id=" . $this->server_id);
        if($row=$r->fetch_assoc()) {
            $this->status = $row['type'];
            $this->streaming_url = $row['source_url'];
        }

        $db->close();
    }

    private function addLog(int $type, string $source_url = ''):void {
        $db = new \ACES2\DB;
        $source_url = $db->escString($source_url);

        //DO NOT REPEAT LOGS.
        if($type === $this->status && $source_url == $this->streaming_url)
            return;

        $db->query("UPDATE iptv_stream_stats SET last_time = unix_timestamp() - log_time
             WHERE stream_id = $this->stream_id AND server_id = $this->server_id
             ORDER BY log_time DESC LIMIT 1");

        $db->query("INSERT INTO iptv_stream_stats (stream_id, server_id, type, log_time, source_url) 
            VALUES ('$this->stream_id', '$this->server_id', '$type', unix_timestamp(), '$source_url')");

        $this->status = $type;
        $this->streaming_url = $source_url;

    }

    function setConnecting():void {
        $this->addLog(self::TYPE_CONNECTING);
    }

    function setStreaming(string $source_url = ''):void {
        $this->addLog( self::TYPE_STREAMING, $source_url);
    }

    function setShutoff():void {
        $this->addLog( self::TYPE_SHUTOFF);
    }

    function setStandBy():void {
        $this->addLog(self::TYPE_STANDBY);
    }


    function clear():void {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM iptv_stream_stats WHERE stream_id = '$this->stream_id' AND server_id = '$this->server_id'");
    }

    static function getType(int $type):string {

        return match($type) {
            self::TYPE_STANDBY => 'Stand By',
            self::TYPE_DISCONNECT => 'Disconnected',
            self::TYPE_CONNECTING => 'Connecting',
            self::TYPE_STREAMING => 'Streaming',
            default => 'Shutoff'
        };

    }

}
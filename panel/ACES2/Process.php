<?php

namespace ACES2;

class Process {

    public $id = 0;
    public $pid = 0;
    public $type = '';
    public $description = '';
    public $server_id = '';
    public $args = '';

    private $intervalUpdateProgress = 0;
    private $intervalUpdateDescription = 0;
    private $intervalAlive= 0;
    private $progress = 0;

    public function __construct(int $process_id) {

        $db = new \ACES2\DB;
        $r=$db->query("SELECT id,pid,type,description,server_id,args FROM process WHERE id= '$process_id' ");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("Process #$process_id not found");

        $this->id = $process_id;
        $this->pid = (int)$row['pid'];
        $this->type = $row['type'];
        $this->description = $row['description'];
        $this->server_id = (int)$row['server_id'];
        $this->args = $row['args'];
        $this->progress = (int)$row['progress'];

        $this->intervalUpdateProgress = time();
        $this->intervalUpdateDescription = time();
        $this->intervalAlive = time();

    }

    public function getProgress():int {
        $db = new \ACES2\DB;
        $r=$db->query("SELECT progress FROM process WHERE id= '$this->id' ");
        $this->progress = (int)$r->fetch_assoc()['progress'];
        return $this->progress;
    }

    public function setProgress(int $progress):void {

        if($this->intervalUpdateProgress > time())
            return;

        $this->intervalUpdateProgress = time() + 10;
        if($this->progress == $progress)
            return;

        $db = new \ACES2\DB;
        $db->query("UPDATE process SET progress = '$progress' 
                     WHERE id= '$this->id' ");
    }

    public function setDescription(string $description):void {
        if($this->intervalUpdateDescription > time())
            return;

        $this->intervalUpdateDescription = time() + 10;

        $db = new \ACES2\DB;
        $desc = $db->escString($description);
        $db->query("UPDATE process SET description = '$desc'");
    }

    public function calculateProgress(int $current , int $total ):void {
        $current_percent = round(($current / $total) * 100);
        $this->setProgress($current_percent);
    }

    public function isAlive():bool {

        if($this->intervalAlive > time())
            return true;

        $this->intervalAlive = time() + 5;

        $db = new \ACES2\DB;
        $r = $db->query("SELECT id FROM process WHERE id= '$this->id' ");
        if($r->num_rows < 1)
            return false;
        return true;
    }

    public function remove(bool $kill = true):void {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM process WHERE id= '$this->id' ");
        //THIS PROCESS SHOULD ONLY BE KILL IF ITS RUN BY THE SAME SERVER ITS BEEN STARTED
        //posix_kill($this->pid, 15);
    }

    static public function getProcessByType(string $type):array {
        $db = new \ACES2\DB;
        $type = $db->escString(strtolower($type));
        $r=$db->query("SELECT id FROM process WHERE type= '$type' ");
        return $r->fetch_all(MYSQLI_ASSOC);
    }

    static public function add (
            string $type, int $server_id, int $sid = 0, string $description = '', string $args = '',  int $pid = 0 ):self {

        $db = new \ACES2\DB;

        $pid = $pid == 0 ? getmypid() : $pid;

        $type = $db->escString(strtolower($type));
        $description = $db->escString($description);
        $args = $db->escString($args);

        $db->query("INSERT INTO process (pid, type, description,server_id, sid, args, progress) 
            VALUES ('$pid','$type', '$description', '$server_id', '$sid', '$args', 1)");

        return new self($db->insert_id );

    }

}
<?php

namespace ACES2;

use ACES2\IPTV\Server;


class Firewall {

    const RULE_ACCEPT = 1;
    const RULE_REJECT = -1;
    const RULE_DROP = 0;

    public $id = 0;
    public $chain = '';
    public $ip_address = '';
    public $dport = '';
    public $sport = '';
    public $protocol = '';
    public $rule = '';
    public $options = '';
    public $comments = '';

    public function __construct(int $id = null) {

        if(is_null($id))
            return ;

        $db = new \ACES2\DB;
        $r=$db->query("SELECT * FROM firewall WHERE id= $id ");
        if(!$row=$r->fetch_assoc())
            throw new \Exception("No firewall ID $id found");

        $this->id = $id;
        $this->chain = strtoupper($row['chain']);
        $this->ip_address = $row['ip_address'];
        $this->dport = (int)$row['dport'];
        $this->sport = (int)$row['sport'];
        $this->protocol = $row['protocol'];

        $this->rule = strtoupper($row['rule']);

        $this->options = $row['options'];
        $this->comments = $row['comments'];
    }

    public function setIpAddress(string $ip_address) {
        if(!filter_var($ip_address, FILTER_VALIDATE_IP))
            setAjaxError("Invalid IP address");
        $this->ip_address = $ip_address;
    }

    public function setChain(string $chain) {
        $chain = strtoupper($chain);
        if($chain != 'INPUT' && $chain != 'OUTPUT')
            setAjaxError("Invalid chain");
        $this->chain = $chain;
    }

    public function setRule(string $rule) {
        $rules = array('ACCEPT', 'REJECT', 'DROP');
        if(!in_array($rule, $rules) )
            setAjaxError("Invalid rule");
        $this->rule = $rule;
    }
    private function buildCommand():string {

        $cmd = '';

        if($this->ip_address)
            $cmd .= " -s " . strtoupper($this->ip_address);

        if($this->dport || $this->sport ) {
            if(!$this->protocol)
                $this->protocol = 'tcp';
        }

        if($this->protocol)
            $cmd .= " -p ".strtolower($this->protocol);

        $cmd .= $this->dport ? ' --dport=' . $this->dport : '';
        $cmd .= $this->sport ? ' --sport=' . $this->sport : '';

        $cmd .= " -j ".$this->rule;

        return $cmd;
    }

    /**
     * @return bool Append rule to firewall.
     */
    public function appendRule(int $server_id):bool {
        $cmd = "iptables -A " . strtoupper($this->chain). " " .$this->buildCommand();
        return $this->applyRule($cmd, $server_id);
    }

    /**
     * @return bool Remove rule from firewall
     */
    public function dropRule(int $server_id):bool {
        $cmd = "iptables -D " . strtoupper($this->chain). " " .$this->buildCommand();
        return $this->applyRule($cmd, $server_id);
    }

    /**
     * @return bool Check if rule is applied
     */
    public function checkRule(int $server_id):bool {
        $cmd = "iptables -C " . strtoupper($this->chain). " " .$this->buildCommand();
        return $this->applyRule($cmd,$server_id);
    }

    private function applyRule($cmd, $server_id):bool {

        $Server = new Server($server_id);

        $connection = \ssh2_connect($Server->address, 22);
        if(!@\ssh2_auth_password($connection, 'root', $Server->ssh_password ))  {
            LogE("Fail Connecting to ssh on server");
            return false;
        }

        $stream =  \ssh2_exec($connection, $cmd );

        $status_stream = ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
        stream_set_blocking($status_stream, true);

        $status = stream_get_contents($status_stream);

        if($status != ""){
            logE("Fail to apply firewall rule $cmd");
            return false;
        }

        return true;

    }


    public function save() {
        $db = new \ACES2\DB;
        $options = $db->escString($this->options);
        $comments = $db->escString($this->comments);

        $db->query("UPDATE firewall SET ip_address = '$this->ip_address', chain = '$this->chain', 
                    rule = '$this->rule', options = '$options', comments = '$comments' 
            WHERE id = $this->id ");

    }

    static public function add(
        String $chain, String $rule, String $ip_address, $dport = 0, $sport = 0, $options = '', $comments = '' ) {

        $db = new \ACES2\DB;

        $Rule = new self();
        $Rule->setChain($chain);
        $Rule->setRule($rule);
        $Rule->setIpAddress($ip_address);

        $options = $db->escString( str_replace(";",'', $options) );
        $comments = $db->escString($comments);

        $db->query("INSERT INTO firewall (chain, ip_address, dport, sport, rule, options, comments) 
                VALUES ('$Rule->chain', '$Rule->ip_address', '$dport', '$sport', '$Rule->rule', '$options', '$comments') ");

        return new self($db->insert_id);

    }


    public function remove() {
        $db = new \ACES2\DB;
        $db->query("DELETE FROM firewall WHERE id= $this->id");
    }
}
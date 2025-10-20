<?php

class Armor {
    
    public $DB;
    
    /**
     * The time in seconds where the action will be available.
     * @var int 
     */
    public $action_exp_time;
    
    /**
     * Time in seconds for how long client will be block after reach the max attempts. 
     * @var int 
     */
    public $action_block_time;
    
    /**
     * The amount of attempts client can ask for action before get block.
     * @var int 
     */
    public $action_max_attempts;
    
    /**
     * The amount of time to sleep when client ask for action and have reach $this->action_sleep_in 
     * @var int 
     */
    public $action_sleep;
    
    /** 
     * The amount of time client can attempt an action without get to sleep.
     * @var int 
     */
    public $action_sleep_in;
    
    /**
     * The time in second when the $sleep_in will be reset.
     * @var int 
     */
    public $action_sleep_reset_time;
    
    /**
     * If is true client ip-address will be lock on iptables.
     * @var bool 
     */
    public $action_firewall = 0;
    
    public $max_simultaneous = 0;
    
    
    public $options;
    
    public $user_id=0;
    public $admin_id=0;
    
    public function __construct($options=null) {
        
        $this->action_exp_time = 60 * 60;
        $this->action_block_time = 60 * 60 * 24;
        $this->action_max_attempts = 35;
        $this->action_sleep = 0;
        $this->action_sleep_in = 10;
        $this->action_sleep_reset_time = 60;
        
        if(!$this->DB) { 
                        
            $this->DB = new mysqli(ACES_DB_HOST,ACES_DB_USER,ACES_DB_PASS,ACES_DB_NAME);
            if($this->DB->connect_errno > 0){ error_log("Could not connect to database."); die(" COULD NO CONNECT TO DATABASE."); }
            
            $this->options = $options;
        }
        
    }
    
    public function addAction($type) { 
        
        if(is_file('/home/aces/no_armor'))
            return true;
        
        $sql = "WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' AND UNIX_TIMESTAMP() < exp_time AND type = '$type'  ";
        if( $this->user_id != 0 ) $sql .= " AND user_id = '$this->user_id' ";
        else if( $this->admin_id != 0 ) $sql .= " AND admin_id = '$this->admin_id' ";
        
        $r=$this->DB->query("SELECT id,token,max_attempts,sleep,sleep_in,sleep_reset FROM armor $sql ");
        if(!$row=mysqli_fetch_array($r)) { 
            
            $this->clearAction($type);
            
            $rw=$this->DB->query("SELECT id FROM firewall WHERE rule = 1 AND value = '{$_SERVER['REMOTE_ADDR']}' ");
            if(mysqli_fetch_array($rw)) { $this->action_max_attempts = null; $this->action_exp_time = 60 * 60 * 24 * 365; } // IF WHITELIST?
            else {

                //IF CLIENT DOESN'T SET OPTIONS LET GET FROM FILE.
                if($this->options === NULL  && is_file("/home/aces/panel/includes/actions/$type.php") ) { 
                    
                    include_once "/home/aces/panel/includes/actions/$type.php";
                    if(isset($ACTION['exp_time'])) $this->action_exp_time = $ACTION['exp_time'];
                    
                    if(isset($ACTION['max_attempts']) && $ACTION['max_attempts'] === -1 ) { $this->action_max_attempts = null;  }
                    else if(isset($ACTION['max_attempts']) && $ACTION['max_attempts'] === 0 ) { $this->action_max_attempts = 0; } 
                    else if(isset($ACTION['max_attempts'])) $this->action_max_attempts = $ACTION['max_attempts'];
                          
                    if(isset($ACTION['sleep'])) $this->action_sleep = $ACTION['sleep'];
                    if(isset($ACTION['sleep_reset_time'])) $this->action_sleep_reset_time = $ACTION['sleep_reset_time'];
                    if(!empty($ACTION['firewall'])) $this->action_firewall = 1;
                    
                    if(!empty($ACTION['max_simultaneous']) && $ACTION['max_simultaneous']>0 ) $this->max_simultaneous  = $ACTION['max_simultaneous'];
                    
                    if(!empty($ACTION['block_time']) && $ACTION['block_time']>0 ) $this->action_block_time = $ACTION['block_time'];

                }
            }
                        
            $token = md5(rand().time().time().rand());
            if($this->action_max_attempts === null )
                $this->DB->query("INSERT INTO armor (type,token,user_id,admin_id,ip_address,max_attempts,max_simultaneous,firewall,sleep,sleep_in,sleep_reset,exp_time) VALUES('$type','$token','$this->user_id','$this->admin_id','{$_SERVER['REMOTE_ADDR']}',NULL,$this->max_simultaneous,'$this->action_firewall','$this->action_sleep','$this->action_sleep_in',NOW(), UNIX_TIMESTAMP() + $this->action_exp_time  ");
            else 
                $this->DB->query("INSERT INTO armor (type,token,user_id,admin_id,ip_address,max_attempts,max_simultaneous,firewall,sleep,sleep_in,sleep_reset,exp_time) VALUES('$type','$token','$this->user_id','$this->admin_id','{$_SERVER['REMOTE_ADDR']}',$this->action_max_attempts,$this->max_simultaneous,'$this->action_firewall','$this->action_sleep','$this->action_sleep_in',NOW(),UNIX_TIMESTAMP() + $this->action_exp_time  ) ");
            return $token;
                        
        } else { 
            
            $token = md5(rand().time().time().rand());
            $this->DB->query("UPDATE armor SET token = '$token' WHERE id = {$row['id']} ");
            return $token;
            
        }
        
    }

    public function action($type,$token=1) { 
        
        ignore_user_abort(true);
        
        if(is_file('/home/aces/no_armor'))
            return true;
        
        $sql = "WHERE ip_address = '{$_SERVER['REMOTE_ADDR']}' AND UNIX_TIMESTAMP() < exp_time ";
        if( $this->user_id != 0 ) $sql .= " AND user_id = '$this->user_id' ";
        else if( $this->admin_id != 0 ) $sql .= " AND admin_id = '$this->admin_id' ";
        else if($type) $sql .= " AND type = '$type' ";
                
        
        $r=$this->DB->query("SELECT id,token,max_attempts,sleep,sleep_in,sleep_reset,firewall,max_simultaneous,active FROM armor $sql ");
        if(!$row=$r->fetch_assoc()) {
            //CREATING AN ACTION. 

            //LET CHECK IF THIS CLIENT IS BLOCKED.
            //$rb=$this->DB->query("SELECT end_time FROM baned WHERE ban = '{$_SERVER['REMOTE_ADDR']}' AND ban_from = '$type' AND NOW() < end_time ");
            
            $rb=$this->DB->query("SELECT exp_date FROM firewall WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND bann = '$type' AND UNIX_TIMESTAMP() < exp_date "); 
            if($row_ban=mysqli_fetch_array($rb)) { 
                //A LITTLE HACK THERE TO LOAD BLOCK FASTER.

                $this->clearAction($type);
                $this->DB->query("INSERT INTO armor (type,user_id,admin_id,ip_address,max_attempts,active,exp_time) VALUES('$type','$this->user_id','$this->admin_id','{$_SERVER['REMOTE_ADDR']}','0',0,'{$row_ban['exp_time']}' ) ");
                return false;
            }
            
            $t=$this->addAction($type);
            return $t;
            
        } else { 
            
            //WHITELIST.
            if( $row['max_attempts'] == null ) { return true; }
            
            //BLOCK NOTHING TO DO....
            if($row['max_attempts'] == 0 ) return false;
            
            if($row['max_simultaneous'] > 0 ) 
                $this->DB->query("UPDATE armor SET active = active + 1 WHERE id = {$row['id']} ");
            
            //MAX ATTEMPTS EXCEDED. LET BLOCK IT.
            if($row['max_attempts'] < 2 || $row['max_simultaneous'] > 0 && $row['max_simultaneous'] < $row['active'] ) { 
                
                $bann = str_replace("_",' ',$type);
                $bann = str_replace('-', ' ', $bann);
 
                //$this->DB->query("INSERT INTO baned (ban,ban_from,start_time,end_time) VALUES('{$_SERVER['REMOTE_ADDR']}','$type',NOW(),NOW() + INTERVAL $this->action_exp_time SECOND) ");

                if($row['firewall']) { 

                    $this->DB->query("INSERT INTO firewall (type,value,comments) VALUES(0,'{$_SERVER['REMOTE_ADDR']}','auto banned from $bann') ");
                    exec("sudo /sbin/iptables -A INPUT -s {$_SERVER['REMOTE_ADDR']} -j DROP ");
                    
                } else {
                    $this->DB->query("INSERT INTO firewall (type,value,bann,exp_time,comments) VALUES(-1,'{$_SERVER['REMOTE_ADDR']}','$type', UNIX_TIMESTAMP() + $this->action_block_time ,'auto banned from $bann') ");
                }
                    
                $this->DB->query("UPDATE armor SET max_attempts = 0, active=0,  exp_time = UNIX_TIMESTAMP() + $this->action_block_time  WHERE id = {$row['id']} ");
                
                return false;
            }
            
            if($row['token'] !== $token ) { 
                //BAD TOKEN..
                
                $sql = '';
                if($row['max_simultaneous'] > 0 ) $sql = ", active = active -1 ";
                
                if($row['sleep']>0) {
                    

                    if($row['sleep_in'] < 1 && strtotime($row['sleep_reset']) > time() ) { sleep($row['sleep']);  $sql .= ", sleep_reset = NOW() + INTERVAL $this->action_sleep_reset_time SECOND ";  }//EXECEDE THE SLEEP ATTEMPTS LET SLEEP.
                    //else if($row['sleep_in'] < 1 ) { $sql .= ", sleep_reset = NOW() + INTERVAL = $this->action_sleep_reset_time SECOND "; error_log("SLEEP BLOCK"); }
                    else if( strtotime($row['sleep_reset']) < time() ) { $row['sleep_in']  = $this->action_sleep_in; $sql .= ", sleep_in = {$row['sleep_in']}, sleep_reset = NOW() + INTERVAL $this->action_sleep_reset_time SECOND ";  } //SLEEP RESET SLEEP.
                    else if($row['sleep_in'] > 0 )  {  
                        $row['sleep_in']--;
                        $sql .= ", sleep_in = {$row['sleep_in']} "; 
                        if($row['sleep_in'] < 1 ) {  $sql .= ", sleep_reset = NOW() + INTERVAL $this->action_sleep_reset_time SECOND "; } 
                    }

                }
                
                $this->DB->query("UPDATE armor SET max_attempts = max_attempts - 1  $sql WHERE id = '{$row['id']}' ");
                $t=$this->addAction($type);
                return $t;
                

            } if( $row['token'] === $token ) { 
                
                if($row['max_simultaneous'] > 0 ) 
                    $this->DB->query("UPDATE armor SET active = active - 1 WHERE id = {$row['id']} ");
                
                
                //$this->DB->query("DELETE FROM armor where id = '{$row['id']}' ");
                
                return true;
            }
        }
        
        return true;
        
    }
    
    
    public function isBlock($type) { 
        
        if(is_file('/home/aces/no_armor')) return false;
        
        $r=$this->DB->query("SELECT id FROM armor WHERE max_attempts = 0 AND type = '$type' AND ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
        if(mysqli_fetch_assoc($r)) {  return true;  }
                
        //$rb=$this->DB->query("SELECT exp_date FROM firewall  WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND bann = '$type' AND NOW() < exp_date ");
        $rb=$this->DB->query("SELECT exp_date FROM firewall  WHERE value = '{$_SERVER['REMOTE_ADDR']}' AND bann = '$type' ");
        if($row_ban=mysqli_fetch_assoc($rb)) {
            //A LITTLE HACK THERE TO LOAD BLOCK FASTER.
            $this->clearAction($type);
            $this->DB->query("INSERT INTO armor (type,user_id,admin_id,ip_address,max_attempts,exp_time) VALUES('$type','$this->user_id','$this->admin_id','{$_SERVER['REMOTE_ADDR']}','0',UNIX_TIMESTAMP() ) ");
            return true;
        }

        return false;
        
    }
    
    public function clearAction($type) {
        
        $this->DB->query("DELETE FROM armor WHERE type = '$type' and ip_address = '{$_SERVER['REMOTE_ADDR']}' ");
        return true;
        
    }
    
}


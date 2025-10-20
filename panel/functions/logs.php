<?php


function AcesLog($level,$msg , $is_backtrace=false ) {

    if(is_array($msg)) $msg = print_r($msg,1);

    if($level == 1) $type='ACES ERROR';
    else if($level == 2) $type='ACES NOTICE';
    else if($level == 3) $type='ACES DEBUG';
    
    $backtrace = '';
    $trace = debug_backtrace();
    
    if( $is_backtrace ) {
        
        $backtrace = "\nBACKTRACE:\n";

        foreach ( $trace as $i=>$t) {
            
            $backtrace .= "\t{$t['file']}({$t['line']}) -> {$t['function']}";

            //IF ITS AN ARRAY MEAN HAVE ARRGUMENTS
            if(is_array($t['args'])) {
                $backtrace .= "(";
                foreach($t['args'] as $i => $a ) {
                    if(is_array($a)) { 
                        foreach($a as $i2 => $a2 ) {
                             if(is_array($a2)) foreach($a2 as $i3 => $a3) $backtrace .= " $i3=> $a3 "; 
                             else $backtrace .= " $i2=>$a2, "; 
                        }


                    } else $backtrace .= " $i=>$a, ";


                }
                $backtrace .= ")\n";
            }

        }
    } else {  
        
        $msg .= "   in {$trace[1]['file']} on line {$trace[1]['line']} ";
        
    }
    
    $m="$type: $msg$backtrace";
    error_log("$m");

    
}

function logD($message, $backtrace = false ) {
    if(!DEFINED('DEBUG') )
        return ;
    AcesLog(3,$message, $backtrace);
}

function logE($message,  $backtrace = false) { AcesLog(1, $message , $backtrace); }


function AcesLogE($message) { return AcesLog(1,$message); } 
function AcesLogD($message) { return AcesLog(13,$message); }

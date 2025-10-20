<?php

if(!$AdminID=adminIsLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$ADMIN = new \ACES2\Admin($AdminID);

if(!$ADMIN->hasPermission("")) {
    setAjaxError(\ACES2\Errors::NO_PERMISSIONS);
}


$db = new \ACES2\DB;

try {
    switch(strtoupper($_REQUEST['action'])){

        case 'ADD_RULE':
            $Rule = ACES2\Firewall::add(
                $_REQUEST['chain'],
                $_REQUEST['rule'],
                $_REQUEST['ip_address'],
                $_REQUEST['dport'],
                $_REQUEST['sport'],
                $_REQUEST['options'],
                $_REQUEST['comments']
            );

            $Rule->appendRule(1);
            break;

        case 'REMOVE_RULE':
            $db = new \ACES2\DB;
            $ids = !is_array($_REQUEST['ids']) ? [$_REQUEST['ids']] : $_REQUEST['ids'];
            foreach($ids as $id){
                $Rule = new ACES2\Firewall($id);
                $Rule->dropRule(1);
                $Rule->remove();
                $db->query("DELETE FROM armor__bans WHERE ip = '$Rule->ip_address'");
                $db->query("DELETE FROM armor__log_ban WHERE ip = '$Rule->ip_address'");
            }

            break;

        case 'UPDATE_RULE':
            $OldRule = new ACES2\Firewall($_REQUEST['rule_id']);
            $Rule = new ACES2\Firewall($_REQUEST['rule_id']);
            $Rule->setChain($_REQUEST['chain']);
            $Rule->setIpAddress($_REQUEST['ip_address']);
            $Rule->dport = (int)$_REQUEST['dport'];
            $Rule->sport = (int)$_REQUEST['sport'];
            $Rule->comments = $_REQUEST['comments'];
            $Rule->options = $_REQUEST['options'];
            $Rule->save();
            $OldRule->dropRule();
            $Rule->AppendRule();

            break;



    }
} catch(\Exception $e) {
    setAjaxError($e);
}

setAjaxComplete();
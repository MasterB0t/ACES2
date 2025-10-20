<?php

namespace ACES2\IPTV;
use ACES2\DB;

class CreditLog  {

    CONST TYPE_ADMIN_TO_RESELLER = 0;
    CONST TYPE_ADMIN_CREATE_RESELLER = 1;
    CONST TYPE_ADMIN_REMOVE_RESELLER = 2;
    CONST TYPE_ADMIN_CREATE_ACCOUNT = 8;
    CONST TYPE_RESELLER_TO_RESELLER = 3;
    CONST TYPE_RESELLER_CREATE_RESELLER = 4;
    CONST TYPE_RESELLER_REMOVE_RESELLER = 5;
    CONST TYPE_RESELLER_ADD_ACCOUNT = 6;
    CONST TYPE_RESELLER_UPDATE_ACCOUNT = 7;
    CONST TYPE_USER_ACCOUNT_REFUNDED = 9;

    public function __construct(){

    }

    public function admin_create_reseller($from_admin,$to_reseller,$credits_amount) {

        $from_admin = (int)$from_admin;
        $to_reseller = (int)$to_reseller;
        $credits_amount = (int)$credits_amount;

        if($credits_amount == 0 )
            return false;

        if(empty($from_admin) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = $this::TYPE_ADMIN_CREATE_RESELLER;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_admin_id, to_user_id, credits, time, date )  
                    VALUES( '$type', '$from_admin', '$to_reseller', '$credits_amount', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    static public function admin_to_reseller(
            int $from_admin,
            int $to_reseller,
            int $credits_amount,
            ) {

        if($credits_amount == 0 )
            return false;

        if(empty($from_admin) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_ADMIN_TO_RESELLER;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_admin_id, to_user_id, credits , time, date )  
                    VALUES( '$type', '$from_admin', '$to_reseller', '$credits_amount', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    public function admin_remove_reseller($from_admin,$to_reseller,$credits_amount) {

        $from_admin = (int)$from_admin;
        $to_reseller = (int)$to_reseller;
        $credits_amount = (int)$credits_amount;

        //INVERT CREDITS TO NEGATIVE FOR CHARTS
        $credits_amount = $credits_amount - ($credits_amount* 2);

        if($credits_amount == 0 )
            return false;

        if(empty($from_admin) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = $this::TYPE_ADMIN_CREATE_RESELLER;
        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_admin_id, to_user_id, credits, time, date ) 
                    VALUES( '$type', '$from_admin', '$to_reseller', '$credits_amount', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    public function admin_add_account($package_id=0) {

        $package_id = (int)$package_id;

        $type = $this::TYPE_ADMIN_CREATE_ACCOUNT;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, credits, package_id, time, date ) 
                    VALUES( '$type', '0', '$package_id', UNIX_TIMESTAMP(), NOW() )");

    }

    static public function reseller_add_reseller(
        int $from_reseller,
        int $to_reseller,
        int $credits_amount,
        int $remaining_credits) {

        if($credits_amount == 0 )
            return false;

        if(empty($from_reseller) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_RESELLER_CREATE_RESELLER;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_user_id, to_user_id, credits, remaining_credits, time, date ) 
                    VALUES( '$type', '$from_reseller', '$to_reseller', '$credits_amount', $remaining_credits, UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    static public function reseller_to_reseller(
        int $from_reseller,
        int $to_reseller,
        int $credits_amount,
        int $remaining_credits ) {

        if( $credits_amount == 0 )
            return false;

        if(empty($from_reseller) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_RESELLER_TO_RESELLER;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_user_id, to_user_id, credits, remaining_credits, time, date )  
                    VALUES( '$type', '$from_reseller', '$to_reseller', '$credits_amount', '$remaining_credits', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }


    static public function reseller_remove_reseller(
        int $from_reseller,
        int $to_reseller,
        int $credits_amount,
        int $remaining_credits) {

        //INVERT CREDITS TO NEGATIVE FOR CHARTS
        $credits_amount = $credits_amount - ($credits_amount* 2);

        if(empty($from_reseller) || empty($to_reseller))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_RESELLER_REMOVE_RESELLER;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_user_id, to_user_id, credits, remaining_credits, time, date )  
                    VALUES( '$type', '$from_reseller', '$to_reseller', '$credits_amount', $remaining_credits, UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    static public function reseller_add_account(
            int $from_reseller,
            int $to_account,$package_id,
            bool $is_trail,
            int $credits_amount,
            int $remaining_credits) {

        $from_reseller = (int)$from_reseller;
        $package_id = (int)$package_id;
        $credits_amount = (int)$credits_amount;
        $to_account = (int)$to_account;
        $is_trail = $is_trail ? 1 : 0 ;

        if(empty($from_reseller) || empty($package_id))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_RESELLER_ADD_ACCOUNT;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_user_id, to_account_id, package_id, is_trial, credits, remaining_credits, time, date )
                    VALUES( '$type', '$from_reseller', '$to_account', '$package_id', '$is_trail', '$credits_amount', '$remaining_credits', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    static public function reseller_update_account(
        int $from_reseller,
        int $to_account,
        int $package_id,
        int $credits_amount,
        int $remaining_credits) {


        if(empty($from_reseller) || empty($package_id) || empty($to_account))
            throw new \Exception("Unable to log credit. Missing arguments");

        $type = self::TYPE_RESELLER_UPDATE_ACCOUNT;

        $db = new DB();
        $db->query("INSERT INTO iptv_credit_logs ( type, from_user_id, to_account_id, package_id, credits, remaining_credits, time, date )  
                    VALUES( '$type', '$from_reseller', '$to_account', '$package_id', '$credits_amount', '$remaining_credits', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }

    static public function userRefundAccount(
        int $from_user,
        int $to_account,
        int $package_id,
        int $credits_amount,
        int $remaining_credits) {

        $type = self::TYPE_USER_ACCOUNT_REFUNDED;

        //INVERT CREDITS FOR STATS
        $credits_amount = $credits_amount * -1;

        $db = new \ACES2\DB;
        $db->query("INSERT INTO iptv_credit_logs (type, from_user_id, to_account_id, package_id , credits, remaining_credits, time, date)
            VALUES ('$type', '$from_user', '$to_account', '$package_id', '$credits_amount', '$remaining_credits', UNIX_TIMESTAMP(), NOW() )");

        return true;

    }


}
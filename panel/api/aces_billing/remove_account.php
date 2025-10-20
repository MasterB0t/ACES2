<?php

use ACES2\IPTV\Settings;

$Reseller = new ACES2\IPTV\Reseller2($USER_ID);
$resellers = $Reseller->getResellers();
$resellers[] = $Reseller->id;
$reseller_sql = implode(",",$resellers);

$account_id = (int)$_REQUEST['account_id'];
try {
    $Account = new ACES2\IPTV\Account($account_id);
} catch (Exception $e) {
    setApiError("Account not found.");
}

if(!$Reseller->canManageAccount($account_id))
    setApiError("Account not found.");

if($Account->status == ACES2\IPTV\Account::STATUS_BLOCKED)
    setApiError("This account cannot be removed.");

$hours = (int)Settings::get(Settings::RESELLER_REFUND_HOURS);
if($hours > 0) {
    $exp_time = time() - ($hours * 3600);

    $db = new \ACES2\DB;
    $r_rc = $db->query("SELECT credits ,from_user_id,package_id  FROM iptv_credit_logs 
                                  WHERE to_account_id = '$Account->id' AND time > $exp_time AND from_user_id in ($reseller_sql) ");

    while($row = $r_rc->fetch_assoc()) {
        //LET CHECK IF THE USER WHO CREATE THE ACCOUNT EXIST OTHERWISE LET REFUND CREDITS TO MAIN RESELLER.
        try {
            $Owner = new \ACES2\IPTV\Reseller2((int)$row['from_user_id']);
            $Owner->setCredits($Owner->getCredits() + $row['credits']);
            \ACES2\IPTV\CreditLog::userRefundAccount(
                $Owner->id,
                $Account->id,
                (int)$row['package_id'],
                $row['credits'],
                $Owner->getCredits()
            );

        } catch (Exception $exp) {
            //RESELLER DO NOT EXIST MAIN RESELLER GET THE CREDITS.
            $Reseller->setCredits($Reseller->getCredits() + $row['credits']);
            \ACES2\IPTV\CreditLog::userRefundAccount(
                $Reseller->id,
                $Account->id,
                (int)$row['package_id'],
                $row['credits'],
                $Reseller->getCredits()
            );
        }
    }

}

$Account->remove();


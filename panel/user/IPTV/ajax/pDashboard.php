<?php

if(!$UserID=userIsLogged())
    Redirect("/user/login.php");
$USER = new \ACES2\IPTV\Reseller2($UserID);
$db = new \ACES2\DB;

use ACES2\IPTV\CreditLog;

$CacheExpTime = 60 * 60 ;


$resellers = $USER->getResellers();
$resellers[] = $USER->id;
$sql = implode("," , $resellers);

$intervalDays = match((int)$_REQUEST['interval']) {
    15 => 15,
    30 => 30,
    default => 7
};


$now = new DateTime( "$intervalDays days ago" );
$interval = new DateInterval( 'P1D'); // 1 Day interval
$Days = new DatePeriod( $now, $interval, $intervalDays); // 7 Days


$type_reseller_add = CreditLog::TYPE_RESELLER_CREATE_RESELLER;
$type_reseller_update = CreditLog::TYPE_RESELLER_TO_RESELLER;
$type_add = CreditLog::TYPE_RESELLER_ADD_ACCOUNT;
$type_update = CreditLog::TYPE_RESELLER_UPDATE_ACCOUNT;
$type_refunded = CreditLog::TYPE_USER_ACCOUNT_REFUNDED;

$DATA = [];

$CacheChart = new Cache("U{$UserID}-charts-intrv-{$intervalDays}", $CacheExpTime, true);
if(!$cache = json_decode($CacheChart->get(),1)) {

    $account_credits = [];
    foreach ($Days as $day) {
        $date = $day->format('Y-m-d');
        $r = $db->query("SELECT sum(credits) as credits FROM iptv_credit_logs 
                   WHERE type = $type_add AND date = '$date' AND from_user_id IN ($sql)
                      OR type =  $type_update AND date = '$date' AND from_user_id IN ($sql) ");

        $r_refunded = $db->query("SELECT sum(credits) as credits FROM iptv_credit_logs 
            WHERE type = '$type_refunded' AND date = '$date' AND from_user_id IN ($sql) ");

        $credits = (int)$r->fetch_assoc()['credits'];
        $refunded = (int)$r_refunded->fetch_assoc()['credits'];

        $account_credits[] = array(
            'Credits' => $credits + $refunded,
            'date' => $date,
        );
    }

    $new_accounts = [];
    foreach ($Days as $day) {
        $date = $day->format('Y-m-d');
        $r = $db->query("SELECT count(to_account_id) as accounts FROM iptv_credit_logs 
            WHERE type = $type_add AND date = '$date' AND from_user_id IN ($sql) ");
        $new_accounts[] = array(
            'Accounts' => (int)$r->fetch_assoc()['accounts'],
            'date' => $date,
        );

    }


    $resellers_sales = [];
    foreach ($Days as $day) {
        $date = $day->format('Y-m-d');
        $r = $db->query("SELECT sum(credits) as credits FROM iptv_credit_logs 
             WHERE type = $type_reseller_add AND date = '$date' AND from_user_id IN ($sql) 
             OR type =  $type_reseller_update AND date = '$date' AND from_user_id IN ($sql) ");
        $resellers_sales[] = array(
            'Credits' => (int)$r->fetch_assoc()['credits'],
            'date' => $date,
        );
    }

    $DATA = array(
        'chart_account_credits' => $account_credits,
        'chart_new_accounts' => $new_accounts,
        'chart_sales_resellers' => $resellers_sales);


    $CacheChart->saveit( json_encode($DATA) );


} else {
    $DATA = $cache;
}



$CacheMonthChart = new Cache("U{$UserID}-chart-months", $CacheExpTime, true);
if(!$cacheData = json_decode($CacheMonthChart->get(),1)) {
    $sales_months_labels = [];
    $sales_months_vals = [];
    for($i=7;$i>0;$i--) {

        $y = $i + 1;
        $r = $db->query(" SELECT  sum(credits) as total, MONTHNAME( LAST_DAY( curdate() - INTERVAL $y month) + INTERVAL $i DAY  ) as month
                                        FROM iptv_credit_logs 
                                        WHERE type IN ('$type_add','$type_update','$type_refunded')
                                        AND date >= LAST_DAY( curdate() - INTERVAL $y month) + INTERVAL 1 DAY 
                                        AND date <= LAST_DAY(curdate() - INTERVAL $i month)
                                        AND from_user_id  in ($sql)
                                        
            ");

        $row = $r->fetch_assoc();
        $sales_months_vals[] = $row['total'];
        $sales_months_labels[] = $row['month'];

    }

    $CacheMonthChart->saveit( json_encode(array(
        'chart_sales_months_labels' => $sales_months_labels,
        'chart_sales_months_vals' => $sales_months_vals
    )));

    $DATA['chart_sales_months_labels'] = $sales_months_labels;
    $DATA['chart_sales_months_vals'] = $sales_months_vals;

} else {
    $DATA['chart_sales_months_labels'] = $cacheData['chart_sales_months_labels'];
    $DATA['chart_sales_months_vals'] = $cacheData['chart_sales_months_vals'];
}






echo json_encode($DATA );
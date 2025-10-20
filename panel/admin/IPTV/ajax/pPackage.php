<?php

$ADMIN = new \ACES2\Admin();
if(!$ADMIN->isLogged()){
    setAjaxError(\ACES2\ERRORS::NOT_LOGGED, 401);
    exit;
}

$DB = new \ACES2\DB();

try {
    switch($_REQUEST['action']) {

        case 'get_package':

            $package_id = (int)$_REQUEST['package_id'];

            $r=$DB->query(" SELECT id,name,bouquets,max_connections,official_duration,official_duration_in,trial_duration,
                        trial_duration_in,auto_ip_lock, "
                . " CASE `official_duration_in` "
                . " WHEN 'HOUR' THEN  DATE_FORMAT(NOW() + INTERVAL official_duration HOUR, '%Y/%m/%d' )  "
                . " WHEN 'DAY' THEN DATE_FORMAT(NOW() + INTERVAL official_duration DAY, '%Y/%m/%d' )  "
                . " WHEN 'MONTH' THEN DATE_FORMAT(NOW() + INTERVAL official_duration MONTH, '%Y/%m/%d' ) "
                . " WHEN 'YEAR' THEN  DATE_FORMAT(NOW() + INTERVAL official_duration YEAR, '%Y/%m/%d' )  "
                . " END as expire_in "
                . "  FROM iptv_bouquet_packages WHERE id = $package_id ");

            $json= [];

            if($row=$r->fetch_assoc()) {

                $b=null;
                if(!$bouquets = join(",", unserialize($row['bouquets']) ) )$bouquets="''";
                $r2=$DB->query("SELECT id,name,IF ( id in ($bouquets), 1 , 0 ) as enabled FROM iptv_bouquets  ");
                while($row_b=$r2->fetch_assoc()) { $b[$row_b['id']] = $row_b; }
                $row['bouquets'] = $b;
                $json = $row;

            }

            setAjaxComplete($json);
            exit;

        default:
            setAjaxError(\ACES2\ERRORS::SYSTEM_ERROR);
            break;

    }
} catch (\Exception $e) {
    setAjaxError($e->getMessage());
}

setAjaxComplete();
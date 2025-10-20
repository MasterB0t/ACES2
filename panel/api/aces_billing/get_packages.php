<?php


$reseller2 = new ACES2\IPTV\Reseller2($USER_ID);

$packages = $reseller2->getBouquetPackages();

$json = [];
foreach($packages as $package) {

        $json[] = array(
            'id' => $package->id,
            'name' => $package->name,
            'official_credits' => $package->official_credits,
            'official_duration' => $package->official_duration,
            'official_duration_in' => $package->official_duration_in,
            'trial_credits' => $package->trial_credits,
            'trial_duration' => $package->trial_duration,
            'trial_duration_in' => $package->trial_duration_in,
            'max_connections' =>$package->max_connections,
            'allow_xc_apps' => $package->allow_xc_apps,

        );
}

echo json_encode($json);
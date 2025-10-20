<?php

namespace ACES2\IPTV;

class AdminPermissions extends \ACES2\AdminPermissions {

    const IPTV_VIEW_STREAMS = 'iptv.streams';
    const IPTV_RESTART_STOP_STREAMS = 'iptv.stream_start_stop';
    const IPTV_FULL_STREAMS = 'iptv.streams_full';
    const IPTV_MANAGE_BOUQUETS = 'iptv.bouquets';
    const IPTV_ACCOUNT = 'iptv.accounts';
    const IPTV_MANAGE_ACCOUNTS = 'iptv.accounts_full_manage';
    const IPTV_VOD = 'iptv.ondemand';
    const IPTV_VOD_FULL = 'iptv.ondemand_full';
    const IPTV_CATEGORIES = 'iptv.categories';
    const IPTV_CATEGORIES_FULL = 'iptv.categories_full';
    const IPTV_RESELLERS = 'iptv.resellers';
    const IPTV_RESELLERS_FULL = 'iptv.reseller_full';

}
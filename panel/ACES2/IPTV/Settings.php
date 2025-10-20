<?php

namespace ACES2\IPTV;

class Settings extends \ACES2\Settings {

    const STREAMS_AT_BOOT = 'iptv.streamsstartup';
    const STREAMS_LOGS = 'iptv.stream_logs';

    const EPG_LENGTH = 'iptv.epglength';
    const EPG_AUTO_BUILD = 'iptv.epgautobuild';
    const EPG_BUILD_XML = 'iptv.epgbuildxml';
    const EPG_BUILD_GZIP = 'iptv.epgbuildgz';
    const EPG_BUILD_ZIP = 'iptv.epgbuildzip';
    const EPG_BUILD_FOR_VODS = 'iptv.epgbuildvideosepg';
    const EPG_BUILD_247_CHANNEL = 'iptv.epgchannelbuilded';

    const RTMP_AUTH_KEY = 'iptv.rtmp_auth_key';

    const DUPLICATE_EPISODE = 'iptv.vod_duplicate_episode';
    const VOD_DONT_DOWNLOAD_LOGOS = 'iptv.do_not_download_logos';

    const TMDB_API_KEY = 'iptv.videos.tmdb_api_v3';
    const TMDB_LANGUAGE = 'iptv.videos.tmdb_lang';

    const STB_THEME = 'iptv.stb_theme';
    const STB_FORCE_THEME = 'iptv.stb_force_theme';
    const STB_LOCALE = 'iptv.stb_locale';
    const STB_PLAY_BY_OK = 'iptv.stb_play_by_ok';

    const RESELLER_CAN_SET_ACCOUNT_PASSWORD = 'iptv.user_set_account_password';
    const RESELLER_CAN_SET_ACCOUNT_USERNAME = 'iptv.user_set_account_username';
    const RESELLER_REFUND_HOURS = 'iptv.user_refund_hours';


}
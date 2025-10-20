<?php

namespace ACES2\IPTV\XCAPI;

use ACES2\IPTV\Account;

class Stream {

    public $num = 0;
    public $name = '';
    public $stream_type = '';
    public $stream_id ='';
    public $category_id = 0;
    public $stream_icon = '';
    public $epg_channel_id = '';
    public $added = 0;
    public $is_adult = false;
    public $custom_sid = '';
    public $tv_archive = 0;
    public $direct_source = '';
    public $tv_archive_duration = '';
    private $account = null;

    public function __construct(array $stream, int $account_id = null) {
        $this->num = (int)$stream['num'];
        $this->name = $stream['name'];
        $this->stream_type = $stream['stream_type'];
        $this->stream_id = (int)$stream['stream_id'];
        $this->category_id = (int)$stream['category_id'];
        $this->stream_icon = $stream['stream_icon'];
        $this->epg_channel_id = $stream['epg_channel_id'];
        $this->added = (int)$stream['added'];
        $this->is_adult = (bool)$stream['is_adult'];
        $this->custom_sid = $stream['custom_sid'];
        $this->tv_archive = (int)$stream['tv_archive'];
        $this->direct_source = $stream['direct_source'];
        $this->tv_archive_duration = $stream['tv_archive_duration'];

        if(!is_null($account_id))
            $this->account = new XCAccount($account_id);

    }

    public function setAccount(XCAccount $account) {
        $this->account = $account;
    }

    public function getStreamUrl($format = 'ts'):string {
        if(is_null($this->account))
            throw new \Exception("Cannot get stream url without account been set.");

        $format = $format == 'hls' ? $format = 'm3u8' : 'ts';

        return  "{$this->account->url}/live/{$this->account->username}/{$this->account->password}/{$this->stream_id}.$format";

    }



}
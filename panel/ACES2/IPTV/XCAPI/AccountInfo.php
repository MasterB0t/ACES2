<?php

namespace ACES2\IPTV\XCAPI;

class AccountInfo {

    public string $username = '';
    public string $password = '';
    public string $message = '';
    public int $auth = 0;
    public string $status = '';
    public int $exp_date = 0;
    public bool $is_trial = false;
    public int $active_cons = 0;
    public int $created_at = 0;
    public int $max_connections =0;
    public array $allowed_output_formats = [];

    public function __construct(array $account) {
        $this->username = $account['username'];
        $this->password = $account['password'];
        $this->message = $account['message'];
        $this->auth = (bool)$account['auth'];
        $this->status = $account['status'];
        $this->exp_date = (int)$account['exp_date'];
        $this->is_trial = (bool)$account['is_trial'];
        $this->active_cons = (int)$account['active_cons'];
        $this->created_at = (int)$account['created_at'];
        $this->max_connections = (int)$account['max_connections'];
        $this->allowed_output_formats = $account['allowed_output_formats'];
    }



}
<?php

namespace ACES2;

class Curl {

    private $url = null;
    private $curl = null;
    private $is_curl_exec = false;

    private $body = null;
    private $headers = null;
    private $request_headers = [];

    public function __construct(String $url = null) {

        $this->url = $url;
        $this->curl = curl_init();
        if($url != null)
            $this->setUrl($url);

        $this->setReturnHeader(true);
        $this->setReturnTransfer();

    }

    public function setUrl(String $url ) {
        curl_setopt($this->curl, CURLOPT_URL, $url);
    }

    public function setNoBody(bool $associative = true) {
        curl_setopt($this->curl, CURLOPT_NOBODY  , $associative );
    }

    /**
     * If this true header will be retrieved in get().
     */
    public function setReturnHeader(bool $associative = true) {
        curl_setopt($this->curl, CURLOPT_HEADER, $associative );
    }

    public function setUserAgent(String $user_agent) {
        curl_setopt($this->curl, CURLOPT_USERAGENT,$user_agent);
    }

    public function setReferred(String $referred_url) {
        curl_setopt($this->curl, CURLOPT_REFERER ,$referred_url);
    }

    public function setAutoReferred(bool $associative = true) {
        curl_setopt($this->curl, CURLOPT_AUTOREFERER, $associative );
    }

    public function setTimeOut(int $timeout = null) {
        curl_setopt($this->curl, CURLOPT_TIMEOUT, $timeout);
    }

    public function setReturnTransfer(bool $associative = true) {
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, $associative );
    }

    public function setHeader(String $headers) {
        $this->request_headers[] = $headers;
    }

    public function setRequestType(String $type) {
        curl_setopt( $this->curl, CURLOPT_CUSTOMREQUEST, strtoupper($type) );
    }

    public function getStatusCode(){
        if(!$this->is_curl_exec)
            throw new \Exception("Cannot get Status code before get().");
        return curl_getinfo($this->curl, CURLINFO_HTTP_CODE);
    }

    public function get() {

        $this->is_curl_exec = true;

        if(count($this->request_headers)> 0 )
            curl_setopt($this->curl, CURLOPT_HTTPHEADER , $this->request_headers);

        $resp =  curl_exec($this->curl);

        $header_size = curl_getinfo($this->curl, CURLINFO_HEADER_SIZE);

        $this->headers = substr($resp, 0, $header_size);
        $this->body = substr($resp, $header_size);

        //logD(print_r($this->headers,1));

    }

    public function getBody():string {
        if(!$this->is_curl_exec)
            $this->get();
        return $this->body;
    }

    public function getResponseHeaders():string {
        return $this->headers;
    }

    public function getResponseHeadersInArray():array {
        $headers_array = array();

        foreach( preg_split("/((\r?\n)|(\r\n?))/", $this->headers) as $line ) {
            $pos = strpos($line, ":" );
            $key = trim(substr( $line, 0, $pos ));
            $value = trim(substr( $line, $pos + 1 ));
            $headers_array[$key] = $value;
        }

        return $headers_array;

    }

}
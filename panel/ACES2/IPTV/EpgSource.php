<?php

namespace ACES2\IPTV;

use ACES2\DB;

class EpgSource {
    public $id = 0;
    public $name = "";
    public $url = "";
    public $enabled = false;
    private $tmp_file = '';
    public $xml_file = '';

    public function __construct(int $id = null) {

        if(!is_null($id)){
            $db = new DB();
            $r=$db->query("SELECT * FROM iptv_epg_sources WHERE id = '$id' ");
            if(!$row=$r->fetch_assoc())
                throw new \Exception("Unable to get epg source #$id from database");

            $this->id = $id;
            $this->name = $row['name'];
            $this->url = $row['url'];
            $this->enabled = (bool)$row['status'];

        }

    }

    public function __destruct() {

        if(is_file($this->tmp_file))
            unlink($this->tmp_file);

//        if(is_file($this->xml_file))
//            unlink($this->xml_file);

        if(is_dir('/home/aces/tmp/epg_unzip/'))
            exec("rm -rf /home/aces/tmp/epg_unzip/");

    }

    private function set(string $name, string $url, bool $enabled=true) {

        $db = new DB();

        $this->name = $db->escString($name);

        if (!filter_var($url, FILTER_VALIDATE_URL))
            throw new \Exception("Invalid Epg URL");

        $this->url = $url;
        $this->enabled = $enabled;

    }

    private function downloadFile() {

        $this->tmp_file = "/home/aces/tmp/epg_temp_file" . time();
        if (!file_put_contents($this->tmp_file, file_get_contents($this->url)))
            throw new \Exception("Unable to download epg file");

        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        switch(finfo_file($finfo, $this->tmp_file)) {

            case 'application/x-gzip':
            case 'application/x-tar':
            case 'application/gzip':
                if (!$gzfile = gzopen($this->tmp_file, 'rb'))
                    throw new \Exception('Could not open gzip file. Maybe is not a real gzip file?');

                $this->xml_file = '/home/aces/tmp/unzip_guide_file.xml';
                if (!$fp_unzip_file = fopen($this->xml_file, 'wb'))
                    throw new \Exception('Could not open file to write the extraction file.');

                while (!gzeof($gzfile)) {
                    fwrite($fp_unzip_file, gzread($gzfile, 4096));
                }

                fclose($fp_unzip_file);
                gzclose($gzfile);

                break;

            case 'application/zip':

                $zip = new ZipArchive;
                $res = $zip->open($this->tmp_file);
                if($res === FALSE) {
                    removeTmpFiles();
                    throw new \Exception("Unable to open zip file. Is this a valid zip file?");
                }

                //CREATE A FOLDER TO EXTRACT FILE ON IT
                if (!file_exists('/home/aces/tmp/epg_unzip'))
                    mkdir('/home/aces/tmp/epg_unzip', 0777, true);

                //GET NAME OF XMLTV FILE

                $this->xml_file = "/home/aces/tmp/epg_unzip/".$zip->getNameIndex(0);

                $zip->extractTo('/home/aces/tmp/epg_unzip/');
                $zip->close();

                $extract_file_type = finfo_file($finfo, $this->xml_file);

                break;


            case 'application/xml':
            case 'text/xml':
                $this->xml_file = $this->tmp_file;
                break;
        }

        $new_name = "/home/aces/imported_guides/$this->name.xml";
        if(rename($this->xml_file, $new_name))
            $this->xml_file = $new_name;

    }

    public function analyze():bool {

        if(!$this->xml_file)
            $this->downloadFile();

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $extract_file_type = finfo_file($finfo, $this->xml_file);
        if(!$extract_file_type != 'application/xml' && $extract_file_type != 'text/xml' ) {
            throw new \Exception("Url do not content a valid XML file");
        }

        return true;
    }

    public function updateTvgIds() {

        if(!$this->xml_file)
            $this->downloadFile();

        $DB = new \ACES2\DB;
        $DB->query("DELETE FROM iptv_imported_epg_names WHERE epg_id = '$this->id'");

        //FILE HAVE BEEN DOWNLOADED IMPORTING TVG_IDS;
        $chunkSize  = 1024 * 10;
        $handler = fopen($this->xml_file,'r');
        $len=0;$ini=0;
        while (!feof($handler)) {
            $buffer = fread($handler, $chunkSize);
            while(true) {

                if(!$ini = strpos($buffer, '<channel',($len+$ini))) break;
                //if(!$ini = strpos($buffer, '<channel' )) break;

                //IF COULD NOT FOUND THE ENDING TAG HERE MEAN IS IN ANOTHER CHUNK.
                WHILE(  (!$len = (strpos($buffer, '</channel>', $ini))) && !feof($handler) ) {
                    $buffer = substr($buffer, $ini).fread($handler, $chunkSize); $ini=0; }

                //COUND NOT FOUND ANYTHING??
                if(!$len) { break; }
                else { $len = ($len - $ini) + strlen('</channel>');  }

                //$xml = simplexml_load_string( str_replace('&','&amp;',substr($buffer, $ini, $len)) );
                $xml = simplexml_load_string( str_replace('&','&amp;',substr($buffer, $ini, $len)) );
                $json = json_encode($xml);
                $array = json_decode($xml,TRUE);

                $display_name = $DB->escString($array['display-name']);
                $tvg_id = $DB->escString($array['@attributes']['id']);
                if(is_array($display_name))
                    $debug = 0;

                $r=$DB->query("SELECT epg_id FROM iptv_imported_epg_names 
                WHERE epg_id = '{$this->id}' AND tvg_id = '{$tvg_id}' ");

                if($r->num_rows == 0)
                    $DB->query("INSERT INTO iptv_imported_epg_names (epg_id, tvg_id, channel_name) 
                    VALUES('{$this->id}', '$tvg_id', '$display_name') ");



            }

        }
        fclose($handler);


    }

    public function updateTvgID2() {
        if(!$this->xml_file)
            $this->downloadFile();

        $DB = new \ACES2\DB;
        $DB->query("DELETE FROM iptv_imported_epg_names WHERE epg_id = '$this->id'");

        $string_xml = '';
        $handler = fopen($this->xml_file,'r');
        while (($line = fgets($handler)) !== false) {

            //FINDING <channel
            if(str_contains($line, '<channel')) {
                $string_xml = $line;

                //LOOKING FOR CLOSING TAG </channel>
                while (($line = fgets($handler)) ) {
                    if($line == false)
                        break; //END OF FILE SHOULD EXIST HERE.

                    $string_xml .=  $line;

                    if(str_contains($line, '</channel>'))
                        break;

                }

                if( $xml = simplexml_load_string( str_replace('&','&amp;', $string_xml )) ) {

                    $json = json_encode($xml);
                    $array = json_decode($json,TRUE);

                    $display_name = $DB->escString($array['display-name']);
                    $tvg_id = $DB->escString($array['@attributes']['id']);

                    $r=$DB->query("SELECT epg_id FROM iptv_imported_epg_names 
                        WHERE epg_id = '{$this->id}' AND tvg_id = '{$tvg_id}' ");

                    if($r->num_rows == 0)
                        $DB->query("INSERT INTO iptv_imported_epg_names (epg_id, tvg_id, channel_name) 
                            VALUES('{$this->id}', '$tvg_id', '$display_name') ");

                }



            }


        }


    }

    public function update(string $name, string $url, bool $enabled=true) {
        $this->set($name, $url, $enabled);
        $db = new DB();

        $db->query("UPDATE iptv_epg_sources SET name = '$this->name', url = '$this->url', status = '$this->enabled' 
                        WHERE id = '$this->id' ");
    }

    public function remove():bool {
        $db = new DB();
        $db->query("DELETE FROM iptv_epg_sources WHERE id = '$this->id'");
        $db->query("DELETE FROM iptv_imported_epg_names WHERE epg_id = '$this->id'");
        return true;
    }

    public static function add(string $name, string $url, bool $enabled=true):self {

        $epg = new EpgSource(null);
        $epg->set($name, $url, $enabled);
        $enable = $epg->enabled ? 1 : 0;

        $epg->analyze();

        $db = new DB();
        $db->query("INSERT INTO iptv_epg_sources (name, status, url,  add_date ) 
            VALUES ('$epg->name', '$enable', '$url', NOW() )");

        $Epg = new self($db->insert_id);

        
        return $Epg;

    }

}
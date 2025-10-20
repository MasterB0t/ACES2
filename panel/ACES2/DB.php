<?php
namespace ACES2;
class DB extends \mysqli {

    public $errors_count = 0;

    public function __construct($host=null,$username=null,$passwd=null,$dbname=null) {

        include "/home/aces/stream/config.php";
        $host = is_null($host) ? $DBHOST : $host;
        $username = is_null($username) ? $DBUSER : $username;
        $passwd = is_null($passwd) ? $DBPASS : $passwd;
        $dbname = is_null($dbname) ? $DATABASE : $dbname;
        return parent::__construct($host, $username, $passwd, $dbname);
    }

    public function query($query,  $resultmode = MYSQLI_STORE_RESULT) {

        try {

            if (($r = parent::query($query, $resultmode)) == null) {
                $b = debug_backtrace();
                $trace = "\nSTACK TRACE:\n";
                foreach ($b as $i => $t) {
                    $trace .= "#$i {$t['file']}({$t['line']}): {$t['class']}->{$t['function']}\n";
                }
                //error_log(" MYSQL ERROR " . $this->errno . ": '" . $this->error . "' When executing: '$query' {$b[0]['file']} #{$b[0]['line']}\n$trace");
                $this->errors_count++;

            }


        } catch (\mysqli_sql_exception $exception) {
            //error_log($exception->getMessage());
            error_log("FULL QUERY: $query");
            throw $exception;
        }

        return $r;

    }

    public function escString($str) {
        return parent::escape_string(trim($str));
    }

    public static function arrayToSql($array) {

        if(!is_array($array) || count($array) < 1 ) return '';

        foreach ($array as $field => $data)
            $update_sqls[] = "$field = '" . $data . "' ";
        return implode(', ', $update_sqls);

    }

    public function cquery($query,$expiration_time=0) {

        if( strpos($query, "SELECT") === false ) {

            //RETURN NORMAL QUERY
            $r = $this->query($query);
            return json_encode($r->fetch_all());

        } else {

            $filename = ACES_ROOT.'/cache/'. md5(base64_encode($query));
            if(!is_file($filename)) {
                $r=$this->query($query);
                $d_decode = $r->fetch_all();
                $data = json_encode($d_decode);
                $data = gzdeflate($data);
                if(!file_put_contents($filename,$data))
                    logD("UNABLE TO WRITE FILE CACHE...");

                return $d_decode;
            } else {
                $data = gzinflate(file_get_contents($filename));
                $data = json_decode($data);
                return $data;
            }

        }

    }

}
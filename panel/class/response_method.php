<?php 

//CLASS FOR RUNTINE FUNCTIONS

class MR {
	const INCOMPLETE = 0;
	const FAIL = 0;
	const ERRORS = 1;
	const RUNTIME_ERROR = 2;                            
	const COMPLETE = 4;                             //METHOD COMPLETE AND DONE ITS ACTION
	const SUCCESS = 3;                              //METHOD COMPLETE 
	
	public $verbosity = ACES_VERBOSITY;		//ALLOW TO PRINT THE MESSAGE
	
	public $L_ERRORS = 1;	// LOG FO
	public $L_NOTICE = 3; 
	public $L_DEBUG = 5;
	
	public $ERRORVARS ;

	public $errors = 0;
	public $is_errors = 0;
	public $runtime_errors ;			//ARRAY: CONTANING RUNTIME ERRORS
	public $is_runtime_errors = 0;                  //BOOL: TRUE IF THERE IS RUNTIME ERRORS
	public $response = 0;  				//RESPONSE CODE FROM FUNCTION
	public $return = false; 			//VARABLE CONTAINING THE DATA RETURN FROM FUNCTION 
	


	public function __destruct() { unset($this->ERRORVARS); }
	
	public function __construct() { 

            foreach (glob(ACES_ROOT."/includes/errors/*.php") as $filename)  include $filename;
            $this->ERRVARS =  $ERRORS;
		
	}
        
        
        public function set_runtime_error($error_code,$name='error')  { 
            
            if(empty($this->ERRVARS[$error_code])) {  $this->runtime_errors[$name]  = $this->ERRVARS['unknown']; }
            else $this->runtime_errors[$name] = $this->ERRVARS[$error_code];

            $this->response = $this::RUNTIME_ERROR;
            $this->is_runtime_errors = 1;
            
        }


	public function setRnError($error_code,$name='error')  { AcesLogD('DEPRECRATED!!!');
		
            if(empty($this->ERRVARS[$error_code])) {  $this->runtime_errors[$name]  = $this->ERRVARS['unknown']; }
            else $this->runtime_errors[$name] = $this->ERRVARS[$error_code];

            $this->response = $this::RUNTIME_ERROR;
            $this->is_runtime_errors = 1;
	
	}
	
	public function setRnErrorStr($str,$name = 'error') { AcesLogD('DEPRECRATED!!!');

            if(empty($str)) $this->runtime_errors[$name] = $this->ERRVARS['unknown'];
            else $this->runtime_errors[$name] = $str;

            $this->response = $this::RUNTIME_ERROR;
            $this->is_runtime_errors = 1;
		
	}
	
	public function getStrRnErrors($separator="\n") {  AcesLogD('DEPRECRATED!!!');
		
		foreach($this->runtime_errors as $e ) $errors = $e.$separator;
		
		return $errors;
		
	}
	
	public function setDebug($msg,$type=5) { $this->log($type,$msg); } 
	
	public function setError($msg,$type=1) { $this->log($type,$msg); }

	public function log($code,$message) { AcesLogD("DEPRECRATED!!!!");
		
		//FUNCTION TO LOG ERRORS FROM RUNTIME FUNCTIONS..
		$this->is_errors = 1;
		if($code == $L_ERRORS) {
				//THERE IS ERROR ON RUNTINE FUNCTION LET SET RESPONSE CODE
				$this->response = $this->ERRORS;
		}
		
		if(ACES_LOG == 0 ) return 0;
		
		if($code == $this->L_ERRORS ) $type = "ERROR : ";
		else if($code == $this->L_NOTICE) $type = "NOTICE : ";
		else if($code == $this->L_DEBUG) $type = "DEBUG : ";
		else return 0;
		
		$backtrace = "BACKTRACE : ";
		$trace = array_reverse(debug_backtrace());
		foreach ($trace as $i=>$t) $backtrace .=  $t['file'].' '.$t['line'].' -> '.$t['function']. "   ";
		
                //DISABLE THIS WILL BE USING A NEW WAY TO GET BACKTRACE.
		//$log = "$type [".date("Y-m-d H:i:s")."] ".IP." $message\n$backtrace\n\n";
                
                $e = new Exception();
                $log = "[".date("Y-m-d H:i:s")."] $type $message\n".$e->getTraceAsString()."\n\n";
		
		if($this->verbosity) echo $log;
		
		if(ACES_LOG_FILE)
			error_log($log,3,ACES_LOG_FILE);
		else error_log($log);
		
		return 1;

	}

	public function logD($message) { return $this->log($this->L_DEBUG,$message); }
	public function logE($message) { return $this->log($this->L_ERROR,$message); }
	public function logN($message) { return $this->log($this->L_NOTICE,$message); }

	
}

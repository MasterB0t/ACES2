<?php 


class Runtime {
    
    const INCOMPLETE = 0;
    const FAIL = 0;
    const ERRORS = 1;
    const RUNTIME_ERROR = 2;  
    const SUCCESS = 3;                      //METHOD COMPLETE AND DONE ITS ACTION
    const COMPLETE = 4;                     //METHOD COMPLETE 
    
    public $ERRORVARS = null;               //CACHE VARIABLE CONTAINING 
    public $errors ;                        
    public $runtime_errors;                 //ARRAY CONTAINING ERRORS.
    public $response = 0;                   //RESPONSE CODE FROM FUNCTION
    public $status = 0;
    public $return = false;                 //VARABLE CONTAINING THE DATA TO BE RETURN FROM FUNCTION 
    
    
    public function set_runtime_error($error_code,$error_input='error') { 
        
        //GETTING ERRORS VARIABLES FROM ERRORS FILES.
        if(empty($this->ERRORVARS)) {
                foreach (glob(ACES_DOC_ROOT."/includes/errors/*.php") as $filename)  include $filename;
		$this->ERRVARS =  $ERRORS;
        }
        
        if(empty($this->ERRVARS[$error_code])) {  $this->runtime_errors[$error_input]  = $this->ERRVARS['unknown']; }
        else $this->runtime_errors[$error_input] = $this->ERRVARS[$error_code];


        $this->response = $this::RUNTIME_ERROR;
        $this->status = $this::RUNTIME_ERROR;
        
    }
    
    public function set_runtime_error_string($error_string,$error_input='error') {
        
        $this->runtime_errors[$error_input] = $error_string;
        $this->response = $this::RUNTIME_ERROR;
        $this->status = $this::RUNTIME_ERROR;
         
    } 
    
    public function is_errors() { 
        if($this->status == $this::RUNTIME_ERROR ) return true;
        else return false;
    }
    
    public function is_complete() { 
        if($this->status == $this::COMPLETE) return true;
        else return false;
    }
    
    public function set_complete() { 
        $this->response = $this::COMPLETE;
        $this->status = $this::COMPLETE;
    }
    
    public function get_runtime_errors_in_string($s = "\n") { 
        $errors = "";
        foreach($this->runtime_errors as $e ) $errors .= $e.$s;
        return $errors;
    }
}
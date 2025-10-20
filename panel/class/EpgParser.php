<?php

class EgpParser { 
	
	private $handler;
	private $xml_file;
	
	private $chunkSize;
	
	private $client_timezone;
	private $server_timezone;
	
	
	public $max_programme = 0;
	
	public function __construct($epg_xml_file) { 
		
		if(!is_file($epg_xml_file)) throw new Exception('Could not found xml file.');
		$this->xml_file  = $epg_xml_file;
		
		$this->chunkSize = 1024 * 2024;
		
		
	}
	
	public function setClientTimezone($timezone) { 
		
		$this->server_timezone = date_default_timezone_get();
		
		if(!date_default_timezone_set($timezone)) throw new Exception('Could not set timezone.');
		else $this->client_timezone = $timezone;
		
	}
	
	public function getProgramme($channel_id,$from_time=null,$to_time=null) { 

		if($from_time != null) {
                    if(!$client_time=date('YmdHis',$from_time)) error_log("Fail to build from date. Incorrect time parameter?"); 
                } else $client_time=(date('YmdHis'));
                
                if($to_time != null) {
                    if(!$client_end_time=date('YmdHis',$to_time)) error_log("Fail to build end date. Incorrect time parameter?");
                } else $client_end_time = null;

		$client_time_offset = date('Z');

		if(!$this->handler = fopen($this->xml_file, 'r')) throw new Exception('Could not load xml file.');
		
		$ini=0;$len=0;
		$programme;
		
		while (!feof($this->handler)) { 
			$buffer = fread($this->handler, $this->chunkSize);
			while( true ) { 
			
				if(!$ini = strpos($buffer, '<programme',($len+$ini))) { break; }
			
                                
				while(  (!$len = (strpos($buffer, '>', $ini))) && !feof($this->handler) ) { $buffer = substr($buffer, $ini).fread($this->handler, $this->chunkSize); $ini=0;  }
					
			
				//COUND NOT FOUND ANYTHING??
                                
				if(!$len) { return false; }
				 
				$len = ($len - $ini) + strlen('>');
				$tag = substr($buffer, $ini, $len);
                                
				if(@strpos($tag,$channel_id)) { 
			
					while(  (!$len = (strpos($buffer, '</programme>', $ini))) && !feof($this->handler) ) { $buffer = substr($buffer, $ini).fread($this->handler, $this->chunkSize); $ini=0; }

					//COUND NOT FOUND ANYTHING??
					if(!$len) { return false; }

			
					$len = ($len - $ini) + strlen('</programme>');

					$xml = simplexml_load_string(  str_replace('&','&amp;', utf8_encode(substr($buffer, $ini, $len)))   );
					$json = json_encode($xml);
					$array = json_decode($json,TRUE);
					
					
					
					//SETTING PROGRAMME TIME TO CLIENT TIME
													
					//$prog_start = explode(' ',$array['@attributes']['start'])[0]  ; 
					//$prog_end = explode(' ',$array['@attributes']['stop'])[0] ;
					
					$guide_offset = (int)explode(' ',$array['@attributes']['start'])[1];
					$guide_offset = (($guide_offset/100)*60)*60;// CONVERTING TO SECONDS
					
                                        //SETTING OFFSET IF GUIDE AND SERVER HAVE DIFFERENT TIMEZONES.
                                        //NOTE! WHATS THE DIFFERENCES???
					if(date('Z') < 0 && $guide_offset < 0 ) $offset = date('Z') - $guide_offset;
					else { $offset = date('Z') - $guide_offset; } 


					$prog_start = date('YmdHis', strtotime( explode(' ',$array['@attributes']['start'])[0] ) + $offset  );
					$prog_end = date('YmdHis', strtotime( explode(' ',$array['@attributes']['stop'])[0] ) + $offset  );
					
					
					$prog_start_hours = date('H:i',strtotime($prog_start));
					$prog_end_hours = date('H:i',strtotime($prog_end));
					

					
					if( $client_time < $prog_end ) {
						
						//if($prog_start == '19691231160000') var_dump($array);
						
						$array['start'] = $prog_start;
						$array['end'] = $prog_end;
						$array['start_hours'] = $prog_start_hours;
						$array['end_hours'] =$prog_end_hours;
						
						unset($array['@attributes']);
						$programme[] = $array;
						
						//echo " {$array['title']} - $prog_start <br> $prog_start <br> $prog_end  <br><br> ";
						if($this->max_programme) {
							if(count($programme) >= $this->max_programme ){
                                                            ob_flush();flush();fclose($this->handler); return $programme; 
                                                            
                                                        }
                                                } else if( $client_end_time > $prog_end ) { 
                                                    ob_flush();flush();fclose($this->handler); return $programme; 
                                                }
						
						
					}

					
					//ob_flush();
					flush();
					
				}

				
			}
		}

		fclose($this->handler);
		return $programme;
	}

	
	
	//THIS FUNCTION WILL FIND CHANNEL NAME AND RETURN THE CHANNEL ID.
	public function findChannelByName($channel_name) { 
		
		if(!$this->handler = fopen($this->xml_file, 'r'))
            throw new Exception('Could not load xml file.');
		
		$channel_id = NULL;
		
		$len=0;$ini=0;
		while (!feof($this->handler)) {
			$buffer = fread($this->handler, $this->chunkSize);

			while(true) {
				if(!$ini = strpos($buffer, '<channel',($len+$ini))) break;
				
				//IF COULD NOT FOUND THE ENDING TAG HERE MEAN IS IN ANOTHER CHUNK.
				WHILE(  (!$len = (strpos($buffer, '</channel>', $ini))) && !feof($this->handler) ) { 
					$buffer = substr($buffer, $ini).fread($this->handler, $chunkSize); $ini=0; }
				
				//COUND NOT FOUND ANYTHING??
				if(!$len) {  break; } 
				else { $len = ($len - $ini) + strlen('</channel>');  }
				
				
				$xml = simplexml_load_string(  str_replace('&','&amp;',substr($buffer, $ini, $len))   );
				$json = json_encode($xml);
				$array = json_decode($json,TRUE);
				//print_r($array);die;
			
                                if(is_array($array['display-name'])) {
                    
                                    if(in_array($channel_name,$array['display-name'])) {
                                        $channel_id = $array['@attributes']['id']; 
                                        
                                    }
                                    
                                } else if($channel_name ==  $array['display-name'] ) {
                                    
                                    $channel_id = $array['@attributes']['id']; 
                                    
                                }
 
			}

			
			//ob_flush();
			flush();

		
		}
		fclose($this->handler);
		return $channel_id;
		
		
	}
	
        
	public function searchChannels($channel_name) { 
            
        $channel_name= strtoupper($channel_name);
        
		if(!$this->handler = fopen($this->xml_file, 'r'))
            throw new Exception('Could not load xml file.');
                
        if(empty($channel_name)) return false;

        $return = NULL;
		
		$len=0;$ini=0;
		while (!feof($this->handler)) {
			$buffer = fread($this->handler, $this->chunkSize);

			while(true) {
				if(!$ini = strpos($buffer, '<channel',($len+$ini))) break;
				
				//IF COULD NOT FOUND THE ENDING TAG HERE MEAN IS IN ANOTHER CHUNK.
				WHILE(  (!$len = (strpos($buffer, '</channel>', $ini))) && !feof($this->handler) ) { 
					$buffer = substr($buffer, $ini).fread($this->handler, $chunkSize); $ini=0; }
				
				//COUND NOT FOUND ANYTHING??
				if(!$len) { break; } 
				else { $len = ($len - $ini) + strlen('</channel>');  }
				
				
				//$xml = simplexml_load_string(  str_replace('&','&amp;',substr($buffer, $ini, $len))   );
                $xml = simplexml_load_string(  mb_convert_encoding(substr($buffer, $ini, $len), 'UTF-8'  ) );

                //$b = mb_convert_encoding(substr($buffer, $ini, $len), 'ASCII'  );
                //$b = iconv( mb_detect_encoding($b),  'UTF-8',  $b);
                //$xml = simplexml_load_string( $b );
                                
                                
				$json = json_encode($xml);
				$array = json_decode($json,TRUE);
 
                if(! is_array($array['display-name'])) {
                    $array['display-name'] = array(strtoupper($array['display-name'])); }

                if(is_array($array['display-name'])) {

                    //SEARCHING IF THERE ARE A SIMILAR CHANNEL
                    //$result = preg_grep('~' . $channel_name . '~', $array['display-name'] );

                    $result = preg_grep("/$channel_name/",$array['display-name']);
                    if(count($result) > 0 ) {
                        foreach($result as $v ) {
                            //AcesLogD("$v ID {$array['@attributes']['id']}");
                            //$return[]['name'] = $v;
                            //$return[]['id'] = $array['@attributes']['id'];
                            //$return[]['id'] = $array['@attributes']['id'];

                            $return[$array['@attributes']['id']] = $v;
                        }
                    }
//
                }
 
			}
			
			//ob_flush();
			flush();

		
		}
		fclose($this->handler);
		
		return $return;
		
		
	}


}
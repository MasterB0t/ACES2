<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of FILE_UPLOAD
 *
 * @author chris
 */
class FILE_UPLOAD {
    
    public $upload_dir = './uploads/';
    public $dir_perm = 0775;

    public $input_name = 'upfile';
    public $size_limit = 2;
    
    public $create_thumb = 0;
    public $thumb_size=300;
    public $thumb_dir='./uploads/thumbs';
    public $thumb_prefix='thumb_';
    
    public $filename='';
    public $allowed_type;
    
    public function __construct() {
        $this->allowed_type = array();
    }
    
    
    public function set_image_mime_type() { 
        
        $this->allowed_type = array_merge($this->allowed_type, array( 
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ));
        
    }
    
    public function set_text_mime_type() { 
        
        $this->allowed_type = array_merge($this->allowed_type, array( 
            'txt' => 'text/plain',
            'pdf' => 'application/pdf',
        ));
        
    }
    
    public function set_zip_mime_type() { 
        
        $this->allowed_type = array_merge($this->allowed_type, array( 
            'zip' => 'application/zip'
        ));
    
    }
    
    public function upload($FILES) {  
        $this->upload_dir = preg_replace('#/+#','/',$this->upload_dir);
                		
        if(!is_dir($this->upload_dir)) {
            if(!mkdir($this->upload_dir, $this->dir_perm, true)) 
                throw new Exception('Unable to create upload directory.');
            
            if(!chmod($this->upload_dir,$this->dir_perm)) throw new Exception('Unable to set permissions to upload directory.');
        }
                        
        // Undefined | Multiple Files | $_FILES Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (!isset($FILES[$this->input_name]['error']) || is_array($FILES[$this->input_name]['error'])) {
            throw new Exception('Invalid parameters.');
        }

        switch ($FILES[$this->input_name]['error']) {
          case UPLOAD_ERR_OK:
              break;
          case UPLOAD_ERR_NO_FILE:
              throw new Exception('Please select a file to upload.');
          case UPLOAD_ERR_INI_SIZE:
          case UPLOAD_ERR_FORM_SIZE:
              throw new Exception('This file exceeded filesize limit.');
          default:
              throw new Exception('Unknown error.');
        }
        
        // You should also check filesize here. 
       
        if ($FILES[$this->input_name]['size'] > $this->size_limit * 1000000) {
            throw new Exception('This file exceeded filesize limit.');
        }
        
        // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
        // Check MIME Type by yourself.
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
            $finfo->file($FILES[$this->input_name]['tmp_name']),
                $this->allowed_type,
            true
        )) {
            
            throw new Exception('This format is not supported.');
        }
        
        
        
      // You should name it uniquely.
      // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
      // On this example, obtain safe unique name from its binary data.
		      
      if($this->filename == '') 
	    $this->uploaded_file=sprintf('%s.%s',
                sha1_file($FILES[$this->input_name]['tmp_name']),
                $ext
	    );
			    
      else $this->uploaded_file=sprintf('%s.%s', $this->filename,  $ext );

		     
      $this->uploaded_size = filesize($FILES[$this->input_name]['tmp_name']);
      if (!move_uploaded_file(
            $FILES[$this->input_name]['tmp_name'],
            ($this->uploaded_to=sprintf($this->upload_dir.'/%s',
                $this->uploaded_file
            ))
      )) {
            throw new Exception('Failed to move uploaded file.');
      }
      $this->uploaded_to = preg_replace('#/+#','/',$this->uploaded_to);
      
      return true;
        
    }
    
    
}

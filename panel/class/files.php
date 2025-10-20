<?php

class files { 

    public $upload_dir = './uploads/';
    public $dir_perm = 0700;
    
    public $exception=''; 
    public $exception_msg='';
    
    public $create_thumb = 0;
    public $thumb_size=300;
    public $thumb_dir='./uploads/thumbs';
    public $thumb_prefix='thumb_';
    
    public $filename='';
    
    const ERR_NO_FILE = UPLOAD_ERR_NO_FILE;
    const ERR_SIZE_LIMIT = UPLOAD_ERR_INI_SIZE;
    const ERR_UNKNOWN = 10;
    const ERR_FILE_NOT_SUPPORTED = 11;
    const ERR_SYSTEM = 12;

    
    const SUCCESS = 20;
    const COMPLETE = 21;
    const ERRORS = 22;
    
    
    
    public function upload($FILES) {   

		$this->exception='';
		$this->exception_msg='';
		$this->exception_code=0;
	    
		$this->uploaded_to='';
		$this->uploaded_file='';
		$this->uploaded_size=0;
		$this->thumb_file='';
		$this->thumb_to='';
	
	
		
		if(!is_dir($this->upload_dir)) 
			  	if(!mkdir($this->upload_dir, $this->dir_perm, true)) throw new RuntimeException('System Error', ERR_SYSTEM);
		
		      // Undefined | Multiple Files | $_FILES Corruption Attack
		      // If this request falls under any of them, treat it invalid.
		if (!isset($FILES['upfile']['error']) || is_array($FILES['upfile']['error'])) {
            throw new RuntimeException('Invalid parameters.',UPLOAD_ERR_NO_FILE);
		}

		      // Check $_FILES['upfile']['error'] value.
		switch ($FILES['upfile']['error']) {
		  case UPLOAD_ERR_OK:
		      break;
		  case UPLOAD_ERR_NO_FILE:
		      throw new RuntimeException('Please select a file to upload.',UPLOAD_ERR_NO_FILE);
		  case UPLOAD_ERR_INI_SIZE:
		  case UPLOAD_ERR_FORM_SIZE:
		      throw new RuntimeException('This file exceeded filesize limit.',ERR_SIZE_LIMIT);
		  default:
		      throw new RuntimeException('Unknown error.', self::ERR_UNKNOWN);
		}

      // You should also check filesize here.
      if ($FILES['upfile']['size'] > 10000000000) {
	 	 throw new RuntimeException('This file exceeded filesize limit.', self::ERR_SIZE_LIMIT);
      }
	
      // DO NOT TRUST $_FILES['upfile']['mime'] VALUE !!
      // Check MIME Type by yourself.
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      if (false === $ext = array_search(
		  $finfo->file($FILES['upfile']['tmp_name']),
		  array(
		      'jpg' => 'image/jpeg',
		      'png' => 'image/png',
		      'gif' => 'image/gif'
		  ),
	  true
      )) {
	  	throw new RuntimeException('This format is not supported.',self::ERR_FILE_NOT_SUPPORTED);
      }
      


      // You should name it uniquely.
      // DO NOT USE $_FILES['upfile']['name'] WITHOUT ANY VALIDATION !!
      // On this example, obtain safe unique name from its binary data.
      
		      
      if($this->filename == '') 
	    $this->uploaded_file=sprintf('%s.%s',
		    sha1_file($FILES['upfile']['tmp_name']),
		    $ext
	    );
			    
      else 
      	$this->uploaded_file=sprintf('%s.%s', $this->filename,  $ext );

		     
      $this->uploaded_size = filesize($FILES['upfile']['tmp_name']);
      if (!move_uploaded_file(
		  $FILES['upfile']['tmp_name'],
		  ($this->uploaded_to=sprintf($this->upload_dir.'/%s',
		      $this->uploaded_file
		  ))
      )) {
	  		throw new RuntimeException('Failed to move uploaded file.',self::ERR_SYSTEM);
      }
		      
		      
	      
      //CREATING thumb_
      if($this->create_thumb ) { 
	      if(!is_dir($this->thumb_dir)) 
		  if(!mkdir($this->thumb_dir, $this->dir_perm, true)) throw new RuntimeException('System Error', self::ERR_SYSTEM);
	      
	      // load image and get image size
	      if($FILES['upfile']['type']=="image/gif")
		    $img = imagecreatefromgif( $this->uploaded_to );
	      else if($FILES['upfile']['type']=="image/jpeg")
		    $img = imagecreatefromjpeg( $this->uploaded_to );
	      else if($FILES['upfile']['type']=="image/png")  
		    $img = imagecreatefrompng( $this->uploaded_to );
		    
		    
	      $width = imagesx( $img );
	      $height = imagesy( $img );

	      // calculate thumbnail size
	      if(!is_numeric($this->thumb_size)|| $this->thumb_size < 1) { $this->thumb_size = 100;  } 
	      $thumbWidth = $this->thumb_size;
	      $new_width = $thumbWidth;
	      $new_height = floor( $height * ( $thumbWidth / $width ) );

	      // create a new temporary image
	      $tmp_img = imagecreatetruecolor( $new_width, $new_height );

	      // copy and resize old image into new image
	      imagecopyresized( $tmp_img, $img, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

	      if($this->thumb_prefix == '' && $this->thumb_dir == $this->upload_dir ) $this->thumb_prefix = 'thumb_';
	      
	      // save thumbnail into a file
	      if($FILES['upfile']['type']=="image/gif")
		  imagegif( $tmp_img, $this->thumb_dir.'/'.$this->thumb_prefix.$this->uploaded_file );
	      else if($FILES['upfile']['type']=="image/jpeg")
		  imagejpeg( $tmp_img, $this->thumb_dir.'/'.$this->thumb_prefix.$this->uploaded_file );
	      else if($FILES['upfile']['type']=="image/png") 
		  imagepng( $tmp_img, $this->thumb_dir.'/'.$this->thumb_prefix.$this->uploaded_file );
	      
	      $this->thumb_to =  $this->thumb_dir.'/'.$this->thumb_prefix.$this->uploaded_file;
	      $this->thumb_file =  $this->thumb_prefix.$this->uploaded_file;
	      //$this->thumb_size = filesize($this->thumb_to);
      }
      return $this::SUCCESS;
    }


}


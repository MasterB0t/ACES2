<?php

namespace ACES2;

class File {

    const DEFAULT_UPLOAD_DIR = DOC_ROOT."/uploads/";

    public $filename = '';
    public $ext = '';
    /**
     * @var int filesize in KB.
     */
    public $size = 0;
    public $path = '';

    private $tmp_file = array();

    private $tmp_filename = '';

    public function __construct($FILE, array $mime_allowed, int $max_upload_size_kb = 1000) {

        // Undefined | Multiple Files | $_FILES Corruption Attack
        // If this request falls under any of them, treat it invalid.
        if (!isset($FILE['error']) || is_array($FILE['error'])) {
            throw new \Exception('Invalid parameters.',UPLOAD_ERR_NO_FILE);
        }

        switch ($FILE['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_NO_FILE:
                throw new \Exception('Please select a file to upload.',UPLOAD_ERR_NO_FILE);
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                throw new \Exception('This file exceeded filesize limit.',ERR_SIZE_LIMIT);
            default:
                throw new \Exception('Unknown error.' );
        }


        if ($FILE['size'] > ($max_upload_size_kb * 1000 ) ) {
            throw new \Exception('This file exceeded filesize limit.',  UPLOAD_ERR_INI_SIZE );
        }

        // DO NOT TRUST $_FILE['mime'] VALUE !!
        // Check MIME Type by yourself.
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        if (false === $ext = array_search(
                $finfo->file($FILE['tmp_name']),
                $mime_allowed,
                true
            )) {
            throw new \Exception('This format is not supported.' );
        }

        $this->ext = $ext;
        $this->tmp_file = $FILE;

    }

    public function upload($filename, $upload_dir = '') {

        $this->path = $upload_dir == '' ?  self::DEFAULT_UPLOAD_DIR : $upload_dir;
        $this->filename = $filename . "." . $this->ext;

        if( !is_dir($this->path) )
            if(!mkdir($this->path, 0754, true))
                throw new \Exception("Unable to make directory '$this->path' ");

        if (!move_uploaded_file(
            $this->tmp_file['tmp_name'],
            $this->path . $this->filename
        )) {
            throw new \Exception('Failed to move uploaded file.');
        }

    }


    static public function getMimePdf() { return array('pdf' => 'application/pdf'); }
    static public function getMimeDocx() { return array('docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'); }
    static public function getMimePng() { return array('png' => 'image/png'); }
    static public function getMimeSvg() { return array('svg' => 'image/svg+xml'); }
    static function getMimeJpg() { return array('jpg' => 'image/jpeg'); }
    static function getMimeGif() { return array('gif' => 'image/gif'); }
    static function getMimeImages() { return array('jpg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif'); }

}
<?php

class SkutumErrorLogger
{
    private $_handle, $_dateFormat;
    private $_filename = 'error.log';
    private $_filenamefull = '';
    private $_logfilestokeep = 7;
    private $_foldername = '/logs/';
    public function __construct() {
        $this->_filenamefull = SKUTUM_PLUGIN_DIR .$this->_foldername.$this->_filename;
        $this->_dateFormat = "d/M/Y H:i:s";
        $this->checkLogRotation();
        $this->_handle = fopen($this->_filenamefull, 'a');
    }
    public function checkLogRotation(){
        if (file_exists($this->_filenamefull)) {
            if (date ("Y-m-d", filemtime($this->_filenamefull)) !== date('Y-m-d')) {
                if (file_exists($this->_filenamefull . "." . $this->_logfilestokeep)) {
                    unlink($this->_filenamefull . "." . $this->_logfilestokeep);
                }
                for ($i = $this->_logfilestokeep; $i > 0; $i--) {
                    if (file_exists($this->_filenamefull . "." . $i)) {
                        $next = $i+1;
                        rename($this->_filenamefull . "." . $i, $this->_filenamefull . "." . $next);
                    }
                }
                rename($this->_filenamefull, $this->_filenamefull . ".1");
            }
        }
    }
    public function log($entries) {
        if(is_string($entries)) {
            fwrite($this->_handle, "Error: [" . date($this->_dateFormat) . "] " . $entries . "\n");
        } else {
            foreach($entries as $value) {
                fwrite($this->_handle, "Error: [" . date($this->_dateFormat) . "] " . $value . "\n");
            }
        }
    }
}
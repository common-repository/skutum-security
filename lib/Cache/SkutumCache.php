<?php

class SkutumCache {
    private $_salt = "skutum_wp_plugin";
    private $_name;
    private $_dir;
    private $_extension;
    private $_path;
    private $_autoSave = false;
    private $_cache;
    public function __construct( $name = "default") {
        $this->_name = $name;
        $this->_dir = SKUTUM_PLUGIN_DIR . '/cache/';
        $this->_extension = '.cache';
        $this->_path = $this->getCachePath();
        $this->checkCacheDir();
        $this->loadCache();
    }
    public function setAutoSave( $state) {
        $this->_autoSave = $state;
    }
    public function get( $key, &$out)  {
        if ($this->_cache === null) return false;
        if (!array_key_exists($key, $this->_cache)) return false;
        $data = $this->_cache[$key];
        if ($this->isExpired($data)) {

            unset($this->_cache[$key]);
            if ($this->_autoSave) {
                $this->saveCache();
            }
            return false;
        }
        $out = unserialize($data["v"]);
        return true;
    }
    public function set( $key, $value,  $ttl = -1)  {
        $data = [
            "t" => time(),
            "e" => $ttl,
            "v" => serialize($value),
        ];
        if ($this->_cache === null) {
            $this->_cache = [
                $key => $data,
            ];
        }
        else {
            $this->_cache[$key] = $data;
        }
        if ($this->_autoSave) {
            $this->saveCache();
        }
    }
    public function delete( $key)  {
        if ($this->_cache === null) return false;
        if (!array_key_exists($key, $this->_cache)) return false;
        unset($this->_cache[$key]);
        if ($this->_autoSave) {
            $this->saveCache();
        }
        return true;
    }
    public function deleteExpired()  {
        if ($this->_cache === null) return false;
        foreach ($this->_cache as $key => $value) {
            if($this->isExpired($value)) {
                unset($this->_cache[$key]);
            }
        }
        if ($this->_autoSave) {
            $this->saveCache();
        }
        return true;
    }
    private function isExpired($data) {
        if ($data["e"] == -1) return false;
        $expiresOn = $data["t"] + $data["e"];
        return $expiresOn < time();
    }
    public function saveCache() {
        if ($this->_cache === null) return false;
        $content = json_encode($this->_cache);
        file_put_contents($this->_path, $content);
        return true;
    }
    public function loadCache() {
        if (!file_exists($this->_path)) return false;
        $content = file_get_contents($this->_path);
        $this->_cache = json_decode($content, true);

        return true;
    }
    private function getCachePath() {
        return $this->_dir . md5($this->_name . $this->_salt) . $this->_extension;
    }
    private function checkCacheDir()  {
        $logger = new SkutumErrorLogger();
        if (!is_dir($this->_dir) && !mkdir($this->_dir, 0775, true)) {
            $logger->log('Unable to create cache directory' .($this->_dir));
        }
        if (!is_writable($this->_dir) || !is_readable($this->_dir)) {
                if (!chmod($this->_dir, 0775)) {
                    $logger->log('Cache directory must be readable and writable ' .($this->_dir));
            }
        }
        return true;
    }
    private function startsWith( $haystack,  $needle)  {
        $length = strlen($needle);
        return (substr($haystack, 0, $length) === $needle);
    }
    private function endsWith( $haystack,  $needle) {
        $length = strlen($needle);
        return $length === 0 || (substr($haystack, -$length) === $needle);
    }
}

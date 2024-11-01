<?php

class SkutumDbWorker{
    public $wpdb;
    public $record_livetime = 2419200;
    public $tableName = 'skutum_actions';
    public $queueTableBaseName = 'skutum_queue';

    public function __construct()
    {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->checkTable();
    }

    public function makeRequest($request){
        return $this->wpdb->get_results($request);
    }

    public function checkTable(){
        $results = $this->makeRequest( "CREATE TABLE IF NOT EXISTS `" . $this->wpdb->prefix . $this->tableName . "` (
          `key` varchar(32) NOT NULL,
          `action` tinyint(4) NOT NULL,
          `setTime` varchar(32),
           PRIMARY KEY (`key`),
           UNIQUE KEY `key` (`key`)
        );");
    }

    public function removeTables(){
        $results = $this->makeRequest( "DROP TABLE  `" . $this->wpdb->prefix . $this->tableName . "`;");

    }
    public function saveAction($key, $action){
        if($this->getAction($key)!== false){
            $result = $this->makeRequest( "UPDATE `".$this->wpdb->prefix . $this->tableName . "` SET `action` = '".$action."', `setTime` = ".time()."  WHERE `".$this->wpdb->prefix . $this->tableName . "`.`key` = '".$key."'");

        } else {
            $result = $this->makeRequest( "INSERT INTO `".$this->wpdb->prefix . $this->tableName . "`(`key`, `action`,`setTime`) VALUES ('".$key."','".$action."','".time()."')");
        }
    }
    public function getAction($key){
        $result = $this->makeRequest( "SELECT `action` FROM `".$this->wpdb->prefix . $this->tableName . "` WHERE `key` = '".$key."'");
        if (is_array($result) && isset($result[0]) && isset($result[0]->action)){
            return $result[0]->action;
        }
        return false;
    }

    public function clearOldRecords(){
        $time_to_delete = time() - $this->record_livetime;
        $result = $this->makeRequest( " DELETE FROM `".$this->wpdb->prefix . $this->tableName . "` WHERE `setTime` <  '".$time_to_delete."'");
    }

}
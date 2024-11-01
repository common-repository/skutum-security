<?php

class SkutumSqlConnector
{

    public $connecor;
    public $prefix;
    public $record_livetime = 2419200;
    public $tableName = 'skutum_actions';
    public $queueTableBaseName = 'skutum_queue';

    public function __construct()
    {
        try  {
            $this->connector = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD,DB_NAME);
        }
        catch (Exception $e){
            $logger = new SkutumErrorLogger();
            $logger->log($e->getMessage());
        }

        if(defined('SKUTUM_TABLE_PREFIX')){
            $this->prefix = SKUTUM_TABLE_PREFIX;
        } else {
            $this->prefix = '';
        }
        $this->checkTable();

    }

    public function checkTable(){
        $results = $this->makeRequest( "CREATE TABLE IF NOT EXISTS `" . $this->prefix . $this->tableName . "` (
          `key` varchar(32) NOT NULL,
          `action` tinyint(4) NOT NULL,
          `setTime`  varchar(32) ,
           PRIMARY KEY (`key`),
           UNIQUE KEY `key` (`key`)
        );");

    }

    public function makeRequest($request){
        $result = array();
        try  {
            $result = mysqli_query($this->connector,$request);
        }
        catch (Exception $e){
            $logger = new SkutumErrorLogger();
            $logger->log($e->getMessage());
        }

        return $result;
    }

    public function getAction($key){
        $result = $this->makeRequest( "SELECT `action` FROM `".$this->prefix . $this->tableName . "` WHERE `key` = '".$key."'");
        if($result){
            $result = $result->fetch_assoc();
            if (is_array($result) && isset($result['action'])){
                return $result['action'];
            }
        }
        return false;
    }



}
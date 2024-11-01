<?php

class SkutumFileDbWorker{
    public $dataDir;
    public $tableName = 'skutum_actions';
    public $settingsTableName = 'skutum_settings';
    public $queueTableBaseName = 'skutum_queue';
    public $storeConfig = [
        'auto_cache' => false,
        'timeout' => 120
    ];

    public function __construct()
    {
        $this->dataDir = SKUTUM_PLUGIN_DIR ."/data/";
    }

    public function getQueueCount($type){
        $queueStore = \SleekDB\SleekDB::store($this->queueTableBaseName.'_'.$type, $this->dataDir, $this->storeConfig);
        return count($queueStore->fetch());
    }

    public function addQueue($data, $type){

        $queueStore = \SleekDB\SleekDB::store($this->queueTableBaseName.'_'.$type, $this->dataDir, $this->storeConfig);
        $queueInsertable = [
            "request" => $data,
        ];
        $queueStore->insert( $queueInsertable );
    }

    public function addSetting($data, $name){

        $queueStore = \SleekDB\SleekDB::store($this->settingsTableName, $this->dataDir, $this->storeConfig);
        $queueInsertable = [
            "value" => $data,
            "name" => $name,
        ];
        if($this->getSetting($name) !== false){
            $updateable = [
                "value" => $data,
            ];
            $queueStore->where( 'name', '=', $name )->update( $updateable );

        } else {
            $queueStore->insert( $queueInsertable );
        }

    }

    public function getSetting($name){
        $queueStore = \SleekDB\SleekDB::store($this->settingsTableName, $this->dataDir, $this->storeConfig);
        $setting = $queueStore
            ->where( 'name', '=', $name )
            ->fetch();
        return (isset($setting[0])&&isset($setting[0]['value']))?$setting[0]['value']:false;
    }

    public function getQueue($limit, $type){

        $queueStore = \SleekDB\SleekDB::store($this->queueTableBaseName.'_'.$type, $this->dataDir, $this->storeConfig);
        return  $queueStore->skip( 0 )->limit( $limit )->fetch();
    }


    public function processQueue($limit,$type){
        $queueStore = \SleekDB\SleekDB::store($this->queueTableBaseName.'_'.$type, $this->dataDir, $this->storeConfig);
        $queueStore->skip( 0 )->limit( $limit )->delete();
    }


}
<?php

class SkutumProcessor{


    public $apiClient;
    public $dbWorker;
    public $dbFileWorker;

    public function __construct()
    {
        $this->apiClient = new SkutumApiClient();
        $this->dbWorker = new SkutumDbWorker();
        $this->dbFileWorker = new SkutumFileDbWorker();
    }

    public function getSessionId(){
        return SkutumMain::getSessionId();
    }

    public function processSingleAction($key, $value){

            if($value == 'drop'){
                $value = '0';
            } else{
                $value = '1';
            }
            $this->dbWorker->saveAction($key,$value);
    }

    public function processPagesResult($result){
       $arrResult = json_decode($result,true);
       $logger = new SkutumErrorLogger();
       if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
            if (isset($arrResult['data'])){
                if(isset($arrResult['data']['siteStatus'])){
                    parse_str($arrResult['data']['siteStatus']);
                    SkutumMain::setSiteStatus($arrResult['data']['siteStatus']);
                } else {
                    $logger->log('Invalid page response (no siteStatus found) : ' . $result );
                }
                if(isset($arrResult['data']['actions']) && is_array($arrResult['data']['actions'])){
                    foreach ($arrResult['data']['actions'] as $key=>$action){
                        $this->processSingleAction($key, $action);
                    }
                    return true;
                } else {
                    $logger->log('Invalid page response (no actions found) : ' . $result );
                }
            } else if(isset($arrResult['errmessage'])){
                $logger->log('Invalid page response (errormessage) : ' . $arrResult['errmessage'] );
            } else {
                $logger->log('Invalid page response (no data or errormessage found) : ' . $result );
            }
       } else {
           SkutumMain::setSiteStatus(1);
           $logger->log('Invalid page response : ' . $result );
       }
        return false;
    }
    public function processSessionsResult($result){
       $arrResult = json_decode($result,true);
       if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
           return true;
       } else {
           $logger = new SkutumErrorLogger();
           $logger->log('Invalid sessions response : ' . $result );
           return false;
       }
    }
    public function processActionsResult($result){
       $arrResult = json_decode($result,true);
       if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
           return true;
       } else {
           SkutumMain::setSiteStatus(1);
           $logger = new SkutumErrorLogger();
           $logger->log('Invalid actions response : ' . $result );
           return false;
       }
    }
    public function processSitekeyResult($result){
       $arrResult = json_decode($result,true);
       if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
           if(isset($arrResult['data']['siteKey'])){
               update_option('skutum_site_key', sanitize_text_field( $arrResult['data']['siteKey'] ) );
           } else {
               $logger = new SkutumErrorLogger();
               $logger->log('Invalid sites response (no siteKey found) : ' . $result );
           }

       } else {
           $logger = new SkutumErrorLogger();
           $logger->log('Invalid sitekey response : ' . $result );
           return false;
       }
    }

    public function pages($requestBody){
        $apiResult = $this->apiClient->pageRequest($requestBody);
        $this->processPagesResult($apiResult);
        return $apiResult;
    }

    public function session($requestBody){
        $apiResult = $this->apiClient->sessionRequest($requestBody);
        $this->processSessionsResult($apiResult);
        return $apiResult;
    }

    public function actions($requestBody){
        $apiResult = $this->apiClient->actionsRequest($requestBody);
        $this->processActionsResult($apiResult);
        return $apiResult;
    }

    public function sitekey($requestBody){
        $apiResult = $this->apiClient->siteKeyRequest($requestBody);
        $this->processSitekeyResult($apiResult);
        return $apiResult;
    }

}
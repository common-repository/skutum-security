<?php

class SkutumCaptchaProcessor{


    public $apiClient;
    public $logger;
    public $dbWorker;
    public $ipAdress;
    public $sessionId;

    public function __construct()
    {
        $this->apiClient = new SkutumApiClient();
        $this->dbWorker = new SkutumDbWorker();
        $this->logger = new SkutumErrorLogger();
    }

    public function requestAnalyzer(){
        $this->sessionId = isset($_COOKIE['xid'])? SkutumHelper::validateXid($_COOKIE['xid']): SkutumHelper::validateXid( $_COOKIE['xidRes'] );
        $this->ipAdress = SkutumHelper::getIPv4();
        if (isset($_POST['g-recaptcha-response'])){
            $response = sanitize_text_field($_POST['g-recaptcha-response']);
            $this->responseProcessor($response);
        } else if(isset($_POST['bd-response'])){
            $this->preloadProcessor();
        } else {
            $this->renderCaptchaPage();
        }
    }

    public function responseProcessor($response){
        if(!isset($_COOKIE['uxag'])){
            $this->logger->log('Invalid Captcha Check (missing uxag cookie).  Cookies:'. json_encode($_COOKIE));
            $this->logInvalidCaptcha('No_uxag_cookie');
            return;
        }
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.get_option('skutum_gr_secretkey').'&remoteip=' . $this->ipAdress . '&response='.$response;
        $remote_get = wp_remote_get( $url, array(
            'timeout'     => 5,
            'redirection' => 5,
            'httpversion' => '1.0',
            'user-agent'  => 'WordPress',
            'blocking'    => true,
            'headers'     => array(),
            'cookies'     => array(),
            'body'        => null,
            'compress'    => false,
            'decompress'  => true,
            'sslverify'   => true,
            'stream'      => false,
            'filename'    => null
        ) );
        if ( isset($remote_get['body'])){
            $body = json_decode($remote_get['body'],true);
            if(isset($body['success']) && $body['success'] == true){
                $this->requestCleanSession();
            }
        }  else {
            $this->logger->log('Invalid Google Captcha Check response :'. json_encode($remote_get));
            $this->logInvalidCaptcha('Google_validation_fail');
            return;
        }

    }

    public function requestCleanSession(){
        $xid = isset($this->sessionId) ? $this->sessionId : "nil";
        $uxid = isset($_COOKIE['uxid']) ? $_COOKIE['uxid'] : "nil";
        $uxatc = isset($_COOKIE['uxatc']) ? $_COOKIE['uxatc'] : "nil";
        $request = array(
            'xid' => SkutumHelper::validateXid( $xid ),
            'ipAddress' => $this->ipAdress,
            'uxid' => SkutumHelper::validateXid( $uxid ),
            'uxatc' => SkutumHelper::validateXat( $uxatc ),
        );
        $apiResult = $this->apiClient->sessionCleanRequest(json_encode($request));
        $this->sessionProcessAnalyzer($apiResult);
    }

    public function logInvalidCaptcha($reason){
        $request = array(
            'xid' => isset($this->sessionId) ? $this->sessionId : "nil",
            'ipAddress' => $this->ipAdress,
            'reason' => $reason,
        );
        $apiResult = $this->apiClient->captchaLogRequest(json_encode($request));
        $this->logProcessAnalyzer($apiResult);
        $this->renderCaptchaPage();
    }

    public function preloadProcessor(){
        $bdresponse = isset($_POST['bd-response'])?$_POST['bd-response']: false;
        $request = array(
            'xid' => isset($this->sessionId) ? $this->sessionId : "nil",
            'ipAddress' => $this->ipAdress,
            'bd-response' => sanitize_text_field( $bdresponse ),
        );
        $apiResult = $this->apiClient->captchaPreloadRequest(json_encode($request));
        $this->preloadProcessAnalyzer($apiResult);

    }

    public function logProcessAnalyzer($apiResult){
        $apiResult = json_decode($apiResult,true);
        if ( isset($apiResult['status']) && $apiResult['status'] == 'ok'){
           return;
        }else{
            $this->logger->log('Invalid Captcha Log Response :'. json_encode($apiResult));
        }
    }

    public function preloadProcessAnalyzer($apiResult){
        $apiResult = json_decode($apiResult,true);
        if ( isset($apiResult['status']) && $apiResult['status'] == 'ok'){
            if(isset($apiResult['data']) && isset($apiResult['data']['action'])){
                if ($apiResult['data']['action'] == 'permit'){
                    $this->dbWorker->saveAction($this->sessionId,'1');
                    $this->dbWorker->saveAction($this->ipAdress,'1');
                    status_header(202);
                } else {
                    status_header(201);
                }
            }
        } else {
            status_header(201);
            $this->logger->log('Invalid Captcha Preload Response :'. json_encode($apiResult));
        }
        die();
    }

    public function sessionProcessAnalyzer($apiResult){
        $apiResult = json_decode($apiResult,true);
        if ( isset($apiResult['status']) && $apiResult['status'] == 'ok'){
            if(isset($apiResult['data']) && isset($apiResult['data']['action'])){
                if ($apiResult['data']['action'] == 'permit'){
                    $this->dbWorker->saveAction($this->sessionId,'1');
                    $this->dbWorker->saveAction($this->ipAdress,'1');
                } else {
                    $this->renderCaptchaPage();
                }
            }
        } else {
            $this->logger->log('Invalid Captcha Sessions Response :'. json_encode($apiResult));
        }
    }

    public function renderCaptchaPage(){
        $captchaSiteKey = get_option('skutum_gr_sitekey');
        $templateHtml = file_get_contents(SKUTUM_PLUGIN_DIR . '/templates/captcha.tpl.html');
        $templateHtml = str_replace('{%SITE_KEY%}', sanitize_text_field( $captchaSiteKey ), $templateHtml);
        echo $templateHtml;
        die();
    }


}
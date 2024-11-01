<?php

class  SkutumApiClient{
    public $apiKey;
    public $apiUrl = 'https://api.skutum.io/api/v1/';

    public function __construct($apikey = false)
    {
        if ($apikey){
            $this->setApiKey($apikey);
        }
        if (get_option('skutum_site_key') && get_option('skutum_site_key') !== ''){
            $this->setApiKey(get_option('skutum_site_key'));
        }

    }

    public function setApiKey($key){
        $this->apiKey = $key;
    }

    public function getApiKey(){
        return $this->apiKey;
    }

    public function setApiUrl($url){
        $this->apiUrl = $url;
    }

    public function getApiUrl(){
        return $this->apiUrl;
    }

    public function sendTestReqest(){

        $path = get_bloginfo('url');
        $path = str_replace('http://','', $path);
        $path = str_replace('https://','', $path);
        if (substr($path, -1) == '/'){
            $path = substr($path, 0, -1);
        }
        $path =  str_replace('/','-', $path);

        $url = $this->apiUrl . 'sites/' . $path ;

        $plugin_version = 0;
        if (function_exists('get_file_data')){
            $plugin_version = get_file_data(SKUTUM_PLUGIN_DIR. '/plugin.php' , array('Version'), 'plugin')[0];
        }

        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Skutum-Client-Version' => 'WordPress/'.$plugin_version,
        ];

        $response = wp_remote_post( $url, array(
                'method' => 'TEST',
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => $headers,
                'body' => '',
                'cookies' => array()
            )
        );


        if(SkutumConfig::getConfigParam('globalDebug',false)){
            $logger = new SkutumErrorLogger();
            $logger->log('*********');
            $logger->log('Plugin Vesion : ' . json_encode($plugin_version) );
            $logger->log('Api request path : ' . $url );
            $logger->log('Api response : ' . json_encode($response) );
            $logger->log('*********');
        }

        if ( is_wp_error( $response ) ) {
            $errorResponse = $response->get_error_message();
            $logger = new SkutumErrorLogger();
            $logger->log('Invalid Api request  Curl Error : ' . $errorResponse );
            $logger->log('Invalid Api request  : ' . json_encode($response) );
            SkutumMain::setSiteStatus(1);
            return false;
        } else {
            return $response['body'];
        }
    }

    public function sendModeSwitchRequest($mode){

        $request = array('mode'=>intval($mode));
        $body = json_encode($request);

        $path = get_bloginfo('url');
        $path = str_replace('http://','', $path);
        $path = str_replace('https://','', $path);
        if (substr($path, -1) == '/'){
            $path = substr($path, 0, -1);
        }
        $path =  str_replace('/','-', $path);

        $url = $this->apiUrl . 'sites/' . $path ;

        $plugin_version = 0;
        if (function_exists('get_file_data')){
            $plugin_version = get_file_data(SKUTUM_PLUGIN_DIR. '/plugin.php' , array('Version'), 'plugin')[0];
        }

        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Skutum-Client-Version' => 'WordPress/'.$plugin_version,
        ];

        $response = wp_remote_post( $url, array(
                'method' => 'PUT',
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => $headers,
                'body' => $body,
                'cookies' => array()
            )
        );


        if(SkutumConfig::getConfigParam('globalDebug',false)){
            $logger = new SkutumErrorLogger();
            $logger->log('*********');
            $logger->log('Plugin Vesion : ' . json_encode($plugin_version) );
            $logger->log('Api request type : ' . 'PUT' );
            $logger->log('Api request path : ' . $path );
            $logger->log('Api request headers : ' . json_encode($headers) );
            $logger->log('Api request body : ' . $body );
            $logger->log('Api response : ' . json_encode($response) );
            $logger->log('*********');
        }

        if ( is_wp_error( $response ) ) {
            $errorResponse = $response->get_error_message();
            $logger = new SkutumErrorLogger();
            $logger->log('Invalid Api request  Curl Error : ' . $errorResponse );
            $logger->log('Invalid Api request  : ' . json_encode($response) );
            SkutumMain::setSiteStatus(1);
            return false;
        } else {
            return $response['body'];
        }
    }

    public function sendStatusReqest(){
        $path = get_bloginfo('url');
        $path = str_replace('http://','', $path);
        $path = str_replace('https://','', $path);
        if (substr($path, -1) == '/'){
            $path = substr($path, 0, -1);
        }
        $path =  str_replace('/','-', $path);
        $url = $this->apiUrl . 'sites/' . $path ;
        $plugin_version = 0;
        if (function_exists('get_file_data')){
            $plugin_version = get_file_data(SKUTUM_PLUGIN_DIR. '/plugin.php' , array('Version'), 'plugin')[0];
        }

        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Skutum-Client-Version' => 'WordPress/'.$plugin_version,
        ];
        $response = wp_remote_post( $url, array(
                'method' => 'GET',
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => $headers,
                'body' => '',
                'cookies' => array()
            )
        );
        if(SkutumConfig::getConfigParam('globalDebug',false)){
            $logger = new SkutumErrorLogger();
            $logger->log('*********');
            $logger->log('Plugin Vesion : ' . json_encode($plugin_version) );
            $logger->log('Api request path : ' . $url );
            $logger->log('Api response : ' . json_encode($response) );
            $logger->log('*********');
        }

        if ( is_wp_error( $response ) ) {
            $errorResponse = $response->get_error_message();
            $logger = new SkutumErrorLogger();
            $logger->log('Invalid Api request  Curl Error : ' . $errorResponse );
            $logger->log('Invalid Api request  : ' . json_encode($response) );
            SkutumMain::setSiteStatus(1);
            return false;
        } else {
            return $response['body'];
        }
    }

    public function sendRequest($request, $path = 'pages/'){
        $url = $this->apiUrl . $path;
        $plugin_version = 0;
        if (function_exists('get_file_data')){
            $plugin_version = get_file_data(SKUTUM_PLUGIN_DIR. '/plugin.php' , array('Version'), 'plugin')[0];
        }

        $headers = [
            'X-API-KEY' => $this->apiKey,
            'Skutum-Client-Version' => 'WordPress/'.$plugin_version,
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'application/json',
        ];

        /* for testing mode */

        $request_arr = json_decode($request,true);
        foreach ($request_arr as $arr_item){
            if(isset($arr_item['xapiskutumtest']) && $arr_item['xapiskutumtest'] !== 'nil'){
                $headers['xapiskutumtest'] = $arr_item['xapiskutumtest'];
                break;
            }
        }

        $response = wp_remote_post( $url, array(
                'method' => 'POST',
                'timeout' => 5,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => $headers,
                'body' => $request,
                'cookies' => array()
            )
        );


        if(SkutumConfig::getConfigParam('globalDebug',false)){
            $logger = new SkutumErrorLogger();
            $logger->log('*********');
            $logger->log('Plugin Vesion : ' . json_encode($plugin_version) );
            $logger->log('Api request type : ' . 'POST' );
            $logger->log('Api request path : ' . $path );
            $logger->log('Api request headers : ' . json_encode($headers) );
            $logger->log('Api request body : ' . $request );
            $logger->log('Api response : ' . json_encode($response) );
            $logger->log('*********');
        }

        if ( is_wp_error( $response ) ) {
            $errorResponse = $response->get_error_message();
            $logger = new SkutumErrorLogger();
            $logger->log('Invalid Api request  Curl Error : ' . $errorResponse );
            $logger->log('Invalid Api request  : ' . json_encode($response) );
            SkutumMain::setSiteStatus(1);
            return false;
        } else {
            return $response['body'];
        }
    }

    public function pageRequest($request){
        return  $this->sendRequest($request, 'pages/');
    }
    public function sessionRequest($request){
        return  $this->sendRequest($request, 'sessions/');
    }
    public function actionsRequest($request){
        return $this->sendRequest($request, 'activities/js/');
    }
    public function captchaPreloadRequest($request){
        return $this->sendRequest($request, 'captcha/preload/ ');
    }
    public function captchaLogRequest($request){
        return $this->sendRequest($request, 'captcha/log/ ');
    }
    public function sessionCleanRequest($request){
        return  $this->sendRequest($request, 'captcha/pass/');
    }
    public function siteKeyRequest($request){
        return  $this->sendRequest($request, 'sites/');
    }

    /**Methods for statistics displayment*/

    public function statsPiechart($path ='parser-seo-human', $period = 'month'){
        return $this->sendRequest('', 'stats/piechart/'.$path.'/'.$period);
    }

    public function statsLinechart($path ='alltraffic', $period = 'month'){
        return $this->sendRequest('', 'stats/linechart/'.$path.'/'.$period);
    }

    public function statsListParserTopIps($period = 'month'){
        return $this->sendRequest('', 'stats/list/parser-top-ips/'.$period);
    }

}
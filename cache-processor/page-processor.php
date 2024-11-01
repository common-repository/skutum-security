<?php

if(!defined('SKUTUM_PLUGIN_DIR')){
    define('SKUTUM_PLUGIN_DIR',dirname(__FILE__).'/../');
}
require_once( SKUTUM_PLUGIN_DIR.'/vendor/rakibtg/sleekdb/src/SleekDB.php' );
require_once( SKUTUM_PLUGIN_DIR.'/lib/Database/SkutumFileDbWorker.php' );
require_once( SKUTUM_PLUGIN_DIR.'/lib/Helpers/SkutumHelper.php' );
require_once( SKUTUM_PLUGIN_DIR.'/lib/Database/SkutumSqlConnector.php' );
require_once( SKUTUM_PLUGIN_DIR.'/lib/Logs/SkutumErrorLogger.php' );
require_once( SKUTUM_PLUGIN_DIR.'/lib/Request/SkutumSocketGetter.php' );


class SkutumPageProcessor{

    public $workerUrl;
    public $dbFileWorker;
    public $dbLiteWorker;
    public $urlPartsToSkip = array('wp-cron.php','wp-admin','logo.svg','get_static_logo','wp-json');
    public static  $_statusOptionName = 'skutumSiteStatus';

    public function __construct(){
        $this->dbFileWorker = new SkutumFileDbWorker();
        $this->workerUrl = $this->dbFileWorker->getSetting('queue_worker');
        $this->dbLiteWorker = new SkutumSqlConnector();
        $this->requestListener();
    }


    public function checkUrl($url){
        foreach ($this->urlPartsToSkip as $item){
            if(strpos($url,$item)>-1){
                return false;
            }
        }
        return true;
    }
    public function checkAction($key){
        $action = $this->dbLiteWorker->getAction($key);
        if($action !== false){
            return !($action == '0');
        }
        return true;
    }

    public function getSiteStatus(){
        return  $this->dbFileWorker->getSetting('site_status');
    }
    public function setSiteStatus($status){
        $this->dbFileWorker->addSetting($status,'site_status');
    }

    public static function getSessionId(){
        if (isset($_COOKIE['xid'])){
            return  SkutumHelper::validateXid( $_COOKIE['xid'] );
        } elseif (isset($_COOKIE['xidRes'])){
            return  SkutumHelper::validateXid( $_COOKIE['xidRes'] );
        } else {
            return false;
        }
    }

    public static function setSessionId($sessionId){
        $curtime     = time();
        $expiry_time = $curtime + 2419200;
        setcookie('xid', $sessionId, 0, "/");
        $_COOKIE['xid'] = $sessionId;
        setcookie('xidRes', $sessionId, $expiry_time, "/");
        $_COOKIE['xidRes'] = $sessionId;
    }

    public function requestPage(){
        $page_request = array(
            'uxid' => isset($_COOKIE['uxid']) ? SkutumHelper::validateXid( $_COOKIE['uxid'] ) : "nil",
            'ip' => SkutumHelper::getIPv4(),
            'uri' => urlencode($_SERVER['REQUEST_URI']),
            'xid' => isset($_COOKIE['xid']) ? SkutumHelper::validateXid( $_COOKIE['xid'] ) : "nil",
            'referer' => isset($_SERVER['HTTP_REFERER']) ? urlencode($_SERVER['HTTP_REFERER']) : "nil",
            'uxid2' => isset($_COOKIE['uxid2']) ? SkutumHelper::validateXid( $_COOKIE['uxid2'] ) : "nil",
            'uagent' => isset($_SERVER['HTTP_USER_AGENT']) ? urlencode($_SERVER['HTTP_USER_AGENT']) : "nil",
            'ajaxRequest' => isset($_SERVER['HTTP_X_REQUESTED_WITH']) ? $_SERVER['HTTP_X_REQUESTED_WITH'] : "nil",
            'hostName' => isset($_SERVER['HTTP_HOST']) ? urlencode($_SERVER['HTTP_HOST'] ) : "nil",
            'uxid3' => isset($_COOKIE['uxid3']) ? SkutumHelper::validateXid( $_COOKIE['uxid3'] ) : "nil",
            'uxat' =>isset($_COOKIE['uxat']) ? SkutumHelper::validateXat( $_COOKIE['uxat'] ) : "nil",
            'xidRes' => isset($_COOKIE['xidRes']) ? SkutumHelper::validateXid( $_COOKIE['xidRes'] ) : "nil",

        );

        $params['skutum'] = true;
        $params['type'] = 'page';
        $params['action'] = 'skutum_queue_worker_async';
        $params['salt'] = 'AF81D3B6ABF0F19DFA288870EF1080DF';
        $params['rand'] = time();

        if (isset($_COOKIE['xapiskutumtest'])){
            $page_request['xapiskutumtest'] =  SkutumHelper::validateXid( $_COOKIE['xapiskutumtest'] );
            $this->dbFileWorker->addQueue(addslashes(json_encode($page_request)),'test-page');
            $params['type'] = 'test-page';
        } else {
            $this->dbFileWorker->addQueue(addslashes(json_encode($page_request)),'page');
        }

        SkutumSocketGetter::post_async($this->workerUrl.md5(rand(0,999)).'/',$params);
        return $page_request;
    }

    public function requestSession($sessionId){
        $request = array(
            'newSessionID' => $sessionId,
        );
        
        $params['skutum'] = true;
        $params['type'] = 'session';
        $params['action'] = 'skutum_queue_worker_async';
        $params['salt'] = 'AF81D3B6ABF0F19DFA288870EF1080DF';
        $params['rand'] = time();

        if (isset($_COOKIE['xapiskutumtest'])){
            $request['xapiskutumtest'] = SkutumHelper::validateXid( $_COOKIE['xapiskutumtest'] );
            $this->dbFileWorker->addQueue(addslashes(json_encode($request)),'test-session');
            $params['type'] = 'test-session';
        } else {
            $this->dbFileWorker->addQueue(addslashes(json_encode($request)),'session');
        }
        SkutumSocketGetter::post_async($this->workerUrl.md5(rand(0,999)).'/',$params);
        return $request;
    }

    public function requestListener(){
        if(!is_admin() && !defined('SKIPSKUTUM') && !isset($_REQUEST['skutum']) && $this->checkUrl($_SERVER['REQUEST_URI'])){
            $this->requestPage();
            define('SKUTUM_PAGE_PROCESSED',true);
            $siteStatus = $this->getSiteStatus();
            $sessionId = self::getSessionId();
            $userIp = SkutumHelper::getIPv4();
            $ipPermit = $this->checkAction($userIp);
            if ($ipPermit && !$sessionId){
                $host =  isset($_SERVER['HTTP_HOST']) ? urlencode($_SERVER['HTTP_HOST']) : "host not set";
                $newsessionId = md5(rand(0, 999999) . $host . time());
                $this->requestSession($newsessionId);
                self::setSessionId($newsessionId);
            }
            if ( !($siteStatus == 'false') && ($siteStatus == '0')){
                if ($sessionId){
                    $sessionPermit = $this->checkAction($sessionId);
                    $sessionPermit = ($sessionPermit == '1');
                    if(!$sessionPermit || isset($_POST['g-recaptcha-response'])){
                        define('SKUTUM_NEED_CAPTCHA_CHECK',true);
                    }
                } else {
                    if( !$ipPermit || isset($_POST['g-recaptcha-response'])){

                        define('SKUTUM_NEED_CAPTCHA_CHECK',true);
                    }
                }

            }


        }
    }

}
$skutum_page_processor = new SkutumPageProcessor();
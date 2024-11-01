<?php

class SkutumMain{

    public $workerUrl;
    public $dbWorker;
    public $dbFileWorker;
    public $captchaProcessor;
    public $urlPartsToSkip = array('wp-cron.php','logo.svg','get_static_logo','wp-json');
    public static  $_statusOptionName = 'skutumSiteStatus';
    public  $cacheCode;

    public function __construct(){
        global $wpdb;
        $this->cacheCode =
            " /****SKUTUM_PART_START***/
                if (!defined('SKUTUM_TABLE_PREFIX')){
                    define('SKUTUM_TABLE_PREFIX','" . $wpdb->prefix . "');
                } else {
                    if (!defined('SKUTUM_TABLE_PREFIX')){
                        define('SKUTUM_TABLE_PREFIX','');
                    }
                }
                   if(file_exists(dirname(__FILE__) . '/plugins/skutum-wp/cache-processor/page-processor.php')){
                    include_once dirname(__FILE__) . '/plugins/skutum-wp/cache-processor/page-processor.php';
                }
                if(defined ('SKUTUM_NEED_CAPTCHA_CHECK') &&  SKUTUM_NEED_CAPTCHA_CHECK){
                    return;
                }
                /*****SKUTUM_PART_END*****/";
        $this->workerUrl = plugins_url( 'skutum-wp/webhook/').time().'/';
        $this->dbWorker = new SkutumDbWorker();
        $this->dbFileWorker = new SkutumFileDbWorker();
        $this->dbFileWorker->addSetting($this->workerUrl,'queue_worker');
        $this->dbFileWorker->addSetting(get_option( self::$_statusOptionName, 'false' ),'site_status');
        $this->addHooks();
        $this->captchaProcessor = new SkutumCaptchaProcessor();
        $this->requestListener();
        $this->writeAdvancedCache();
        $admin = new SkutumAdmin();

    }

    public function addCustomJs(){
        wp_enqueue_script( 'skutum-js', 'https://api.skutum.io/static/wp/js/d.min.js', false );
    }
    public function addCustomAjaxData(){
        wp_localize_script('skutum-js', 'static_logo_request_object', array(
            'main_request_url' => get_site_url()
        ));
    }

    public function addHooks(){
        add_action( 'plugins_loaded ', array($this,'requestListener'));
        add_filter( 'cron_schedules', array($this,'customCronShedule') );
        add_action( 'skutum_cron_hook', array($this,'runDbCleaner'));
        register_activation_hook( SKUTUM_PLUGIN_DIR . '/plugin.php', array($this,'pluginActionActivate'));
        add_action( 'wp_enqueue_scripts',  array($this,'addCustomJs') );
        add_action( 'wp_enqueue_scripts', array($this,'addCustomAjaxData'), 99 );
    }

    public function getAdvancedCacheFilename() {
        return untrailingslashit(WP_CONTENT_DIR) . '/advanced-cache.php';
    }

    private function writeAdvancedCache() {
        if (defined("WP_CACHE") && WP_CACHE){
            $filename = $this->getAdvancedCacheFilename();
            if(file_exists($filename) && is_writeable($filename)){
                $filecontent = file_get_contents($filename);
                $additional_content = "<?php
                $this->cacheCode";
                if ( strpos($filecontent,$additional_content) === false){
                    $filecontent = str_replace('<?php', $additional_content, $filecontent);
                    file_put_contents($filename,$filecontent);
                }
            }

        }
    }

    public function pluginActionActivate() {
        if ( ! wp_next_scheduled( 'skutum_cron_hook' ) ) {
            wp_schedule_event( time(), 'one_time_per_day', 'skutum_cron_hook' );
        }

        if (!get_option('skutum_site_key') || get_option('skutum_site_key') == ''){
            add_option( 'skutum_site_key', '', '', 'yes' );
            $siteUrl = get_bloginfo('url');
            $request = array(
                'newSite' => $this->processSiteUrl($siteUrl),
            );
            $processor = new SkutumProcessor();
            $processor->sitekey(json_encode($request));
        } else {
            $apiClient = new SkutumApiClient();
            $apiClient->sendTestReqest();
        }
        add_option( self::$_statusOptionName, 'false', '', 'yes' );
        add_option( 'skutum_total_requests', 0, '', 'yes' );
        add_option( 'skutum_available_requests', 10000, '', 'yes' );
        add_option( 'skutum_gr_sitekey', '', '', 'yes' );
        update_option('skutum_gr_sitekey', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI' );
        add_option( 'skutum_gr_secretkey', '', '', 'yes' );
        update_option('skutum_gr_secretkey', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe' );
        add_option( 'skutum_gr_is_valid', 0, '', 'yes' );
        update_option('skutum_gr_is_valid', 0 );


    }

    public function processSiteUrl($url){
        $url = str_replace('http://','', $url);
        $url = str_replace('https://','', $url);
        if (substr($url, -1) == '/'){
            $url = substr($url, 0, -1);
        }
        return $url;
        //return str_replace('/','-', $url);
    }

    public function pluginActionUninstall() {
        delete_option( self::$_statusOptionName );
        //removeTables
        $this->dbWorker->removeTables();
        //removing entry from advanced_cache.php
        $filename = $this->getAdvancedCacheFilename();
        if(file_exists($filename) && is_writeable($filename)){
            $filecontent = file_get_contents($filename);
            $cachecode = $this->cacheCode;
            if ( strpos($filecontent,$cachecode) !== false){
                $filecontent = str_replace($this->cacheCode, '', $filecontent);
                file_put_contents($filename,$filecontent);
            }
        }

    }

    public function runDbCleaner(){
        $this->dbWorker->clearOldRecords();
    }

    function customCronShedule( $schedules ) {
        $schedules['one_time_per_day'] = array(
            'interval' => 86400,
            'display'  => __( 'One time per day' ),
        );
        return $schedules;
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
        $action = $this->dbWorker->getAction($key);
        if($action !== false){
            return !($action == '0');
        }
        return true;
    }

    public static function getSiteStatus(){
        return get_option( self::$_statusOptionName, 'false' );
    }

    public static function setSiteStatus($status){
        update_option(  self::$_statusOptionName, $status );
    }

    public static function setRequestCount($count){
        update_option( 'skutum_total_requests', $count );
    }

    public static function setRequestLimit($count){
        update_option( 'skutum_available_requests', $count );
    }

    public static function getSessionId(){
        if (isset($_COOKIE['xid'])){
            return  SkutumHelper::validateXid($_COOKIE['xid']);
        } elseif (isset($_COOKIE['xidRes'])){
            return  SkutumHelper::validateXid($_COOKIE['xidRes']);
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
            'hostName' => isset($_SERVER['HTTP_HOST']) ? urlencode($_SERVER['HTTP_HOST']) : "nil",
            'uxid3' => isset($_COOKIE['uxid3']) ? SkutumHelper::validateXid( $_COOKIE['uxid3'] ) : "nil",
            'uxat' =>isset($_COOKIE['uxat']) ? SkutumHelper::validateXat( $_COOKIE['uxat'] ) : "nil",
            'xidRes' => isset($_COOKIE['xidRes']) ? SkutumHelper::validateXid( $_COOKIE['xidRes'] ) : "nil",

        );

        $params['skutum'] = true;
        $params['action'] = 'skutum_queue_worker_async';
        $params['type'] = 'page';
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
        $params['action'] = 'skutum_queue_worker_async';
        $params['salt'] = 'AF81D3B6ABF0F19DFA288870EF1080DF';
        $params['rand'] = time();
        $params['type'] = 'session';
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

    public function staticRequestListener(){
        if((isset($_COOKIE['xid'])||isset($_COOKIE['xidRes'])) && isset($_COOKIE['uxid'])){
            $processor = new SkutumProcessor();
            $xidRes = isset($_COOKIE['xidRes'])? SkutumHelper::validateXid( $_COOKIE['xidRes'] ) : "nil";
            $xid = isset($_COOKIE['xid'])? SkutumHelper::validateXid( $_COOKIE['xid'] ) : $xidRes;
            $uxid = isset($_COOKIE['uxid']) ? SkutumHelper::validateXid( $_COOKIE['uxid'] ) : "nil";
            $uxid2 = isset($_COOKIE['uxid2']) ? SkutumHelper::validateXid( $_COOKIE['uxid2'] ) : "nil";

            $xid = isset($_COOKIE['xid'])? SkutumHelper::validateXid( $_COOKIE['xid'] ) : SkutumHelper::validateXid( $_COOKIE['xidRes'] );
            $requestBody = array(
                'uxid' => $uxid,
                'ipAddress' => SkutumHelper::getIPv4(),
                'xid' => $xid,
                'uxid2' => $uxid2,
            );
            $params['skutum'] = true;
            $params['type'] = 'actions';
            $params['action'] = 'skutum_queue_worker_async';
            $params['salt'] = 'AF81D3B6ABF0F19DFA288870EF1080DF';
            $params['rand'] = time();
            if (isset($_COOKIE['xapiskutumtest'])){
                $requestBody['xapiskutumtest'] =  SkutumHelper::validateXid( $_COOKIE['xapiskutumtest'] );
                $processor->dbFileWorker->addQueue(addslashes(json_encode($requestBody)),'test-actions');
                $params['type'] = 'test-actions';
            } else {
                $processor->dbFileWorker->addQueue(addslashes(json_encode($requestBody)),'actions');
            }

            SkutumSocketGetter::post_async($this->workerUrl.md5(rand(0,999)).'/',$params);
        }
        exit;
    }

    public function queueWorkerProcess(){
        $allowed_types = array('session','page','actions','test-session','test-page','test-actions');
        $worker_files= array(
            'session' => dirname(__FILE__).'/../data/worker_sessions.lock',
            'page' => dirname(__FILE__).'/../data/worker_pages.lock',
            'actions' => dirname(__FILE__).'/../data/worker_actions.lock',
            'test-session' => dirname(__FILE__).'/../data/worker_test-sessions.lock',
            'test-page' => dirname(__FILE__).'/../data/worker_test-pages.lock',
            'test-actions' => dirname(__FILE__).'/../data/worker_test-actions.lock'
        );

        if (!isset($_REQUEST['type'])){
            exit;
        }
        $request_type = sanitize_text_field($_REQUEST['type']);
        if (!in_array($request_type, $allowed_types)){
            exit;
        }
        $worker_file = $worker_files[$request_type];
        if (file_exists($worker_file) && filemtime($worker_file) > (time() - 60*15 )){
            exit;
        }
        file_put_contents($worker_file,'1');
        $start_time = time();
        $maxtime = 30;
        $limit = 50;
        if (ini_get('max_execution_time')){
            $maxtime = intval(ini_get('max_execution_time'));
        }
        $maxtime = $maxtime - 3;
        $processor = new SkutumProcessor();
        SkutumHelper::recursiveChecker($maxtime,$start_time, $processor, $worker_file, $limit, $request_type);
        exit;
    }

    public function requestListener(){

        // check for 'queue worker actions' request
        if (isset($_REQUEST['action'])
            && ($_REQUEST['action'] == 'skutum_queue_worker_async')
            && isset($_REQUEST['salt'])
            && ($_REQUEST['salt'] == 'AF81D3B6ABF0F19DFA288870EF1080DF'))
        {
            $this->queueWorkerProcess();
        } else

        // check for 'js actions' request
        if (isset($_GET['action'])
            && ($_GET['action'] == 'get_static_logo')
            && isset($_GET['filename'])
            && ($_GET['filename'] == 'logo.svg')
            && isset($_GET['salt'])
            && ($_GET['salt'] == 'AF81D3B6ABF0F19DFA288870EF1080DF'))
            {
             $this->staticRequestListener();
            } else


        if(!is_admin() && !defined('SKIPSKUTUM')&& !defined('SKUTUM_PAGE_PROCESSED') && !isset($_REQUEST['skutum']) && $this->checkUrl($_SERVER['REQUEST_URI'])){
            $this->requestPage();
            $siteStatus = $this->getSiteStatus();
            $sessionId = self::getSessionId();
            $sessionId =  ($sessionId == 'nil')? false : $sessionId;
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
                        $this->captchaProcessor->requestAnalyzer();
                    }
                } else {
                    if( !$ipPermit || isset($_POST['g-recaptcha-response'])){
                        $this->captchaProcessor->requestAnalyzer();
                    }
                }

            }


        }
        if (defined('SKUTUM_NEED_CAPTCHA_CHECK')){
            $siteStatus = $this->getSiteStatus();
            $sessionId = self::getSessionId();
            $userIp = SkutumHelper::getIPv4();
            $ipPermit = $this->checkAction($userIp);
            if ( !($siteStatus == 'false') && ($siteStatus == '0')){
                if ($sessionId){
                    $sessionPermit = $this->checkAction($sessionId);
                    $sessionPermit = ($sessionPermit == '1');
                    if(!$sessionPermit || isset($_POST['g-recaptcha-response'])){
                        $this->captchaProcessor->requestAnalyzer();
                    }
                } else {
                    if( !$ipPermit || isset($_POST['g-recaptcha-response'])){
                        $this->captchaProcessor->requestAnalyzer();
                    }
                }

            }
        }
    }

}
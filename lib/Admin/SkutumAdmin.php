<?php

class SkutumAdmin
{


    public function __construct()
    {
        $this->registerMenu();

        add_action('admin_print_footer_scripts', array($this,'addCustomJs'), 99);
        add_action( 'wp_ajax_skutum_statistics', array($this,'ajaxStatsListener') );
        add_action( 'wp_ajax_skutum_statistics_pie', array($this,'ajaxStatsListenerPie') );
        add_action( 'wp_ajax_skutum_statistics_line', array($this,'ajaxStatsListenerLine') );
        add_action( 'wp_ajax_skutum_start_testing', array($this,'startCompatibilityTesting') );
        add_action( 'wp_ajax_skutum_mode_switch', array($this,'sendSwitchRequest') );
        add_action( 'wp_ajax_skutum_update_status', array($this,'udpateSiteStatus') );
        add_action('admin_enqueue_scripts', array($this,'addCustomJs')  );
    }

    public function addCustomJs($page){
        $plugin_version = 0;
        if (function_exists('get_file_data')){
            $plugin_version = get_file_data(SKUTUM_PLUGIN_DIR. '/plugin.php' , array('Version'), 'plugin')[0];
        }
        if ( 'toplevel_page_skutum' != $page  && 'skutum_page_skutum-settings' != $page && 'skutum_page_skutum-captcha-settings' != $page) {
            return;
        }
        wp_enqueue_style('skutum_admin_css_font', 'https://fonts.googleapis.com/css?family=Nunito+Sans:300,400,600,700,800,900&display=swap');
        wp_enqueue_style('skutum_admin_css', plugins_url('css/skutum-admin.css?v='.$plugin_version, SKUTUM_PLUGIN_DIR. '/plugin.php' ));
        wp_enqueue_script('skutum_moments_js', plugins_url('js/custom-moments.min.js', SKUTUM_PLUGIN_DIR. '/plugin.php' ));
        wp_enqueue_script('skutum_chart_js', plugins_url('js/chart.min.js', SKUTUM_PLUGIN_DIR. '/plugin.php' ));
        wp_enqueue_script('skutum_admin_js', plugins_url('js/skutum-admin.js?v='.$plugin_version, SKUTUM_PLUGIN_DIR. '/plugin.php' ));
    }

    public function registerMenu()
    {
        add_action('admin_menu', array($this, 'createMenu'));

    }

    public function createMenu()
    {

        add_action('admin_notices', array( $this, 'createNotification'));


        add_menu_page(
            'Skutum',
            'Skutum',
            'administrator',
            'skutum',
            array($this, 'displayDashboard')
        );
        add_submenu_page('skutum', 'Settings', 'Dashboard', 'administrator', 'skutum', array($this, 'displayDashboard'));
        add_submenu_page('skutum', 'Settings', 'General Settings', 'administrator', 'skutum-settings', array($this, 'displaySettingsPage'));
        add_submenu_page('skutum', 'Captcha Settings', 'Captcha Settings', 'administrator', 'skutum-captcha-settings', array($this, 'displayCaptchaSettingsPage'));

        add_action('admin_init', array($this, 'registerSettings'));
    }

    public function createNotification(){
        $page = get_current_screen();
        if ( 'toplevel_page_skutum' != $page->id   && 'skutum_page_skutum-settings' != $page->id) {
            return;
        }
        if (get_option( SkutumMain::$_statusOptionName, 'false' ) == '4') {
            echo '<div class="notice notice-warning is-dismissible">
             <p>Skutum Plugin works in testing mode.  Our AI system checks compatibility with your website.</p>
         </div>';
        }
    }



    public function registerSettings()
    {
        register_setting('skutum-settings-group', 'skutum_site_key');
        register_setting('skutum-captcha-settings-group', 'skutum_gr_sitekey');
        register_setting('skutum-captcha-settings-group', 'skutum_gr_secretkey');
        register_setting('skutum-captcha-settings-group', 'skutum_gr_sitekey_valid');
        register_setting('skutum-captcha-settings-group', 'skutum_gr_secretkey_valid');
        register_setting('skutum-captcha-settings-group', 'skutum_gr_is_valid');
    }

    public function displayCaptchaSettingsPage()
    {
        $this->udpateSiteStatus(true);
        if ( get_option('skutum_gr_sitekey_valid') !== get_option('skutum_gr_sitekey') || get_option('skutum_gr_secretkey_valid') !== get_option('skutum_gr_secretkey') ){
            update_option('skutum_gr_is_valid',0);
        }
        if (isset($_POST['scutum-action']) && $_POST['scutum-action']=='admin-capthca-validation' && isset($_POST['g-recaptcha-response'])){
            $response = sanitize_text_field($_POST['g-recaptcha-response']);
            $this->checkCaptchaKeys($response);
        }

        if (get_option( SkutumMain::$_statusOptionName, 'false' ) !== '4'){
            require_once SKUTUM_PLUGIN_DIR . '/templates/admin/captchasettings.tpl.php';
        }
    }
    public function checkCaptchaKeys($response){

        $ip = SkutumHelper::getIPv4();
        $url = 'https://www.google.com/recaptcha/api/siteverify?secret='.get_option('skutum_gr_secretkey').'&remoteip=' . $ip . '&response='.$response;
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
                update_option('skutum_gr_is_valid',1);
                update_option('skutum_gr_sitekey_valid',get_option('skutum_gr_sitekey'));
                update_option('skutum_gr_secretkey_valid',get_option('skutum_gr_secretkey'));

            }
        }  else {
            update_option('skutum_gr_is_valid',0);

        }
    }

    public function displaySettingsPage()
    {
        $this->udpateSiteStatus(true);
        if (get_option( SkutumMain::$_statusOptionName, 'false' ) !== '4'){
            require_once SKUTUM_PLUGIN_DIR . '/templates/admin/settings.tpl.php';
        }
    }
    public function displayDashboard()
    {
        $this->udpateSiteStatus(true);
        require_once SKUTUM_PLUGIN_DIR . '/templates/admin/dashboard.tpl.php';

    }

    public function ajaxStatsListener() {

        if (isset($_POST['action']) && $_POST['action'] == 'skutum_statistics') {

            $processor = new SkutumStatsProcessor();
            $data = $processor->processStatsRequest($_POST);
            echo $data;
        }
        wp_die();
    }

    public function ajaxStatsListenerPie() {

        if (isset($_POST['action']) && $_POST['action'] == 'skutum_statistics_pie') {

            $processor = new SkutumStatsProcessor();
            $data = $processor->processStatsRequestPie($_POST);
            echo $data;
        }
        wp_die();
    }
    public function ajaxStatsListenerLine() {

        if (isset($_POST['action']) && $_POST['action'] == 'skutum_statistics_line') {

            $processor = new SkutumStatsProcessor();
            $data = $processor->processStatsRequestLine($_POST);
            echo $data;
        }
        wp_die();
    }
    public function startCompatibilityTesting() {

        if (isset($_POST['action']) && $_POST['action'] == 'skutum_start_testing') {
            $apiClient = new SkutumApiClient();
            $apiClient->sendTestReqest();
            SkutumMain::setSiteStatus(4);
        }
        wp_die();
    }

    public function sendSwitchRequest() {

        $allowed = array(0,3);
        if (isset($_POST['action']) && $_POST['action'] == 'skutum_mode_switch' && in_array($_POST['mode'], $allowed)) {
            $apiClient = new SkutumApiClient();
            $apiClient->sendModeSwitchRequest($_POST['mode']);
        }
        wp_die();
    }

    public function udpateSiteStatus($force = false) {

        if ((isset($_POST['action']) && $_POST['action'] == 'skutum_update_status') || $force) {
            $apiClient = new SkutumApiClient();
            $statusData = $apiClient->sendStatusReqest();
            $arrResult = json_decode($statusData,true);
            $logger = new SkutumErrorLogger();
            if(isset($arrResult['status'])&& $arrResult['status']=='ok'){
                if (isset($arrResult['data'])){
                    if(isset($arrResult['data']['mode'])){
                        parse_str($arrResult['data']['mode']);
                        SkutumMain::setSiteStatus($arrResult['data']['mode']);
                    } else {
                        $logger->log('Invalid Status response (no mode found) : ' . $statusData );
                    }
                    if(isset($arrResult['data']['requestCount'])){
                        parse_str($arrResult['data']['requestCount']);
                        SkutumMain::setRequestCount($arrResult['data']['requestCount']);
                    } else {
                        $logger->log('Invalid Status response (no requestCount found) : ' . $statusData );
                    }
                    if(isset($arrResult['data']['requestLimit'])){
                        parse_str($arrResult['data']['requestLimit']);
                        SkutumMain::setRequestLimit($arrResult['data']['requestLimit']);
                    } else {
                        $logger->log('Invalid Status response (no requestAvailableCount found) : ' . $statusData );
                    }
                } else if(isset($arrResult['errmessage'])){
                    $logger->log('Invalid Status response (errormessage) : ' . $arrResult['errmessage'] );
                } else {
                    $logger->log('Invalid Status response (no data or errormessage found) : ' . $statusData );
                }
            } else {
                SkutumMain::setSiteStatus(1);
                $logger->log('Invalid page response : ' . $statusData );
            }
            return false;

        }
        wp_die();
    }
}
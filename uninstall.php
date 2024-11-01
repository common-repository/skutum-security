<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
if (!defined('SKUTUM_PLUGIN_DIR')){
    define('SKUTUM_PLUGIN_DIR',dirname(__FILE__));
}
include_once SKUTUM_PLUGIN_DIR.'/vendor/autoload.php';
$Skutum = new SkutumMain();
$Skutum->pluginActionUninstall();
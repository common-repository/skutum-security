<?php
/*
    Plugin Name: Skutum Security
    Description: The official Skutum WordPress plugin.
    Version: 1.2.0
    Author: Skutum
    Author URI:
    License: GPL-3.0+
    License URI: http://www.gnu.org/licenses/gpl-3.0.txt
    Text Domain: skutum
    Plugin URI: https://www.skutum.io/
*/

if (!defined('SKUTUM_PLUGIN_DIR')){
    define('SKUTUM_PLUGIN_DIR',dirname(__FILE__));
}
include_once SKUTUM_PLUGIN_DIR.'/vendor/autoload.php';
$Skutum = new SkutumMain();
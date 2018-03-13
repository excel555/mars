<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');

$config['auth_default_url'] =  $env_config['auth_default_url'];
$config['auth_fruitday_url'] =  $env_config['auth_fruitday_url'];
$config['auth_fruitday_app_url'] =  $env_config['auth_fruitday_app_url'];
$config['auth_gat_url'] =  $env_config['auth_gat_url'];
$config['auth_gat_redirect_url'] =  $env_config['auth_gat_redirect_url'];
$config['auth_redirect_url'] =  $env_config['auth_redirect_url'];
$config['auth_fruitday_index_url'] =  $env_config['auth_fruitday_index_url'];
$config['auth_wechat_redirect_url'] =  $env_config['auth_wechat_redirect_url'];
$config['auth_wfz_redirect_url'] =  $env_config['auth_wfz_redirect_url'];
$config['auth_wfz_default_url'] =  $env_config['base_url'].'/public/p.mars?d=DEVICEID';
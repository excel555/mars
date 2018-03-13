<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');

$config['platform_host'] =  $env_config['platform_host'];
$config['platform_update_cache_url'] =  $config['platform_host']."/api/commercial/update_cache";
$config['platform_secret'] =  $env_config['platform_secret'];
$config['platform_redis_key_ex'] =  'comercial_';
$config['platform_redis_new_key_ex'] =  'cb_config_';
$config['platform_update_new_cache_url'] =  $config['platform_host']."/api/commercial/refresh_cache";
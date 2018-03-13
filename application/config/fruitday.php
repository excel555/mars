<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 * 天天果园
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 5/15/17
 * Time: 18:57
 */

$config['secret'] = $env_config['fruitday_secret'];
$config['fruitday_host'] = $env_config['fruitday_host'];
$config['recharge_return_url'] = $env_config['fruitday_recharge_return_url'];

<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 * 关爱通配置文件
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 5/15/17
 * Time: 18:57
 */

$config['app_id'] = $env_config['guanaitong_app_id'];
$config['secret'] = $env_config['guanaitong_secret'];
$config['gateway'] = 'https://openapi.guanaitong.com';//'https://openapi.guanaitong.com';//关爱通后端路径
$config['gateway_web_test'] = 'https://c.guanaitong.test';//关爱通前端路径  测试
$config['gateway_web_com']  = 'https://c.guanaitong.com';//关爱通前端路径    正式  经关爱通要求  写两个路径，  原因是他们经常要迭代

$config['notify_url'] = $env_config['base_url'] . "/index.php/api/order/notify_gat";;

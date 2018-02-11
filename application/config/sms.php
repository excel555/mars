<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 5/15/17
 * Time: 14:55
 */
$config['sms_host'] = "http://notify.fruitday.com";
$config['versoin'] = "v1";
$config['sms_source'] = "citybox";
$config['sms_secret'] = "3410w312ecf4a3j814y50b6abff6f6b97e16";
$config['sms_url'] = $config['sms_host'].'/'.$config['versoin'].'/sms/send?source='.$config['sms_source'];
$config['email_url'] = $config['sms_host'].'/'.$config['versoin'].'/email/group?source='.$config['sms_source'];//批量发送邮件
$config['sms_bind_content'] = $env_config['sms_bind_content'];

$config['sms_warn_stock_enable'] = $env_config['sms_warn_stock_enable'];
$config['sms_warn_stock_content'] = $env_config['sms_warn_stock_content'];
$config['sms_warn_stock_moblie'] = $env_config['sms_warn_stock_moblie'];


//短信通知类
$config['sms_notify_accounts'] = $env_config['sms_notify_accounts'];
$config['select_notify_pipe'] = $env_config['select_notify_pipe'];//选择通道

//市场营销短信
$config['sms_market_accounts'] = $env_config['sms_market_accounts'];

$config['select_market_pipe'] = $env_config['select_market_pipe'];//选择通道
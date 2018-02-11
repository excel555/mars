<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 *    配置账号信息
 */

$config['wechat'] = $env_config['wechat'];

$config['pay_succ_tpl_id'] = $env_config['wechat_pay_succ_tpl_id'];
$config['pay_fail_tpl_id'] = $env_config['wechat_pay_fail_tpl_id'];
$config['refund_tpl_id'] = $env_config['wechat_refund_tpl_id'];
$config['notify_tpl_id'] = $env_config['wechat_notify_tpl_id'];
$config['exception_tpl_id'] = $env_config['wechat_exception_tpl_id'];
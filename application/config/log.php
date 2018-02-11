<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 * 日志相关配置
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 5/15/17
 * Time: 18:57
 */

$config['custom_log_path'] = dirname(dirname(__FILE__)).'/logs/'; //Use a full server path with trailing slash.
$config['crit_log_email_rec'] = $env_config['crit_log_email_rec']; //发送告警日志错误,多个邮箱逗号分隔
$config['crit_log_email_subject'] = "city box 程序发生错误！请及时处理"; //发送告警日志邮件标题
$config['crit_log_email_content'] = "错误内容："; //发送告警日志邮件内容

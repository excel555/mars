<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
$config = array (
    //应用ID,您的APPID。
    'app_id' => $env_config['alipay_app_id'],

    //商户私钥，您的原始格式RSA私钥,一行字符串
    'merchant_private_key' => "MIICXAIBAAKBgQCxH2skFUet0s34gLhYn7uJAK0XIy3kDEpP5FxYPQq4wFiMMf4c9I13XR4fjLavqfT9bCJrE1ASqPSgmroSqQYvyd0sm5HLMxWTHvhInkSWTN3ueJfIRax7LzTQexqFUy/JThO6hCblzTS0wN5yrA6LeAysX3N9UfdSab4fz00RdQIDAQABAoGBAJjq/DBB4wmSV1s1nnJ9LYbBu66fI66gYcQJ7yQLR2dsQMaBHtfW1w/3p9srPEn63NWydyCkotwJXHIQQ5d6sChAVgMSu2efa6In6LP5Cx2C+/eIvpOY6yXs0gxrJaWVZtBHjfYrXTrJtSOciMmGkGjDCCKaij7s0EZPVPKnR6whAkEA1xxqyohdk7QidZjTRDr3PrJuphjDV3RsZJSJP4zHN6BTovLrmMBPolCLCqf54Tcdb4R29b5thlMKReHydiAsvQJBANLKcTIky5QwvrThGVxpnD2cjOGYCJoo4PsxKjGPDSjGTWmV2VVbnlkdHslfRyXpIx6RGKzCxyCepxxgGinaLxkCQBrOcMRyf+7TKOQsuk8rZfpLNBzAwz8XxBY4qG3h9kWJVkLdMNzlQkdA8ELQsgQN4T4vbL+tDmsJ2CLjSFrOIaUCQHqZ8Li/mgD5URKXkk6jxpI3SeG0sdwoRqMTd30XvQmoPUJaO+xfu3wNaeiqGBG+xgRzVCy3pWYdoQjqBI2vL5ECQHbERmix55rnapCBNb91IU3lsp/H9mHiDQDzpchVj5GIuyal8kFJep6J6yJJFZHvb1ldirlEjBcYro/IhIyDiYg=",


    //商户公钥，您的原始格式RSA私钥,一行字符串
    'merchant_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCxH2skFUet0s34gLhYn7uJAK0XIy3kDEpP5FxYPQq4wFiMMf4c9I13XR4fjLavqfT9bCJrE1ASqPSgmroSqQYvyd0sm5HLMxWTHvhInkSWTN3ueJfIRax7LzTQexqFUy/JThO6hCblzTS0wN5yrA6LeAysX3N9UfdSab4fz00RdQIDAQAB",


    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' => $env_config['alipay_public_key'],

    //编码格式只支持GBK。
    'charset' => "GBK",

    //支付宝网关
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do", //https://openapi.alipay.com/gateway.do

    //签名方式，第一次激活暂时只能使用RSA
    'sign_type'=>"RSA",
    'notify_url'=>$env_config['base_url'].'/index.php/api/order/notify_alipay_wap',
    'retrun_url'=>$env_config['base_url'].'/public/order.html?orderId=',

    'pay_sell_id'=>'',
    //模板消息id
    'pay_succ_tpl_id' => $env_config['pay_succ_tpl_id'],
    'pay_fail_tpl_id' => $env_config['pay_fail_tpl_id'],
    'refund_tpl_id' => $env_config['refund_tpl_id'],
    'notify_tpl_id' => $env_config['notify_tpl_id'],
);

if(isset($env_config['alipay_sanbox']) && $env_config['alipay_sanbox']){
    $config['gatewayUrl'] = 'https://openapi.alipaydev.com/gateway.do';
}
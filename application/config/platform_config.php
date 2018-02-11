<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
//-------alipay config----------
$config['app_id'] = $env_config['alipay_app_id'];
$config['merchant_private_key'] = 'MIICXAIBAAKBgQCxH2skFUet0s34gLhYn7uJAK0XIy3kDEpP5FxYPQq4wFiMMf4c9I13XR4fjLavqfT9bCJrE1ASqPSgmroSqQYvyd0sm5HLMxWTHvhInkSWTN3ueJfIRax7LzTQexqFUy/JThO6hCblzTS0wN5yrA6LeAysX3N9UfdSab4fz00RdQIDAQABAoGBAJjq/DBB4wmSV1s1nnJ9LYbBu66fI66gYcQJ7yQLR2dsQMaBHtfW1w/3p9srPEn63NWydyCkotwJXHIQQ5d6sChAVgMSu2efa6In6LP5Cx2C+/eIvpOY6yXs0gxrJaWVZtBHjfYrXTrJtSOciMmGkGjDCCKaij7s0EZPVPKnR6whAkEA1xxqyohdk7QidZjTRDr3PrJuphjDV3RsZJSJP4zHN6BTovLrmMBPolCLCqf54Tcdb4R29b5thlMKReHydiAsvQJBANLKcTIky5QwvrThGVxpnD2cjOGYCJoo4PsxKjGPDSjGTWmV2VVbnlkdHslfRyXpIx6RGKzCxyCepxxgGinaLxkCQBrOcMRyf+7TKOQsuk8rZfpLNBzAwz8XxBY4qG3h9kWJVkLdMNzlQkdA8ELQsgQN4T4vbL+tDmsJ2CLjSFrOIaUCQHqZ8Li/mgD5URKXkk6jxpI3SeG0sdwoRqMTd30XvQmoPUJaO+xfu3wNaeiqGBG+xgRzVCy3pWYdoQjqBI2vL5ECQHbERmix55rnapCBNb91IU3lsp/H9mHiDQDzpchVj5GIuyal8kFJep6J6yJJFZHvb1ldirlEjBcYro/IhIyDiYg=';
$config['merchant_public_key'] = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCxH2skFUet0s34gLhYn7uJAK0XIy3kDEpP5FxYPQq4wFiMMf4c9I13XR4fjLavqfT9bCJrE1ASqPSgmroSqQYvyd0sm5HLMxWTHvhInkSWTN3ueJfIRax7LzTQexqFUy/JThO6hCblzTS0wN5yrA6LeAysX3N9UfdSab4fz00RdQIDAQAB';//
$config['alipay_public_key'] = $env_config['alipay_public_key'];//支付宝公钥
$config['charset'] = 'GBK';
$config['gatewayUrl'] = "https://openapi.alipay.com/gateway.do";
$config['sign_type'] = 'RSA';
$config['notify_url'] = $env_config['base_url'].'/index.php/api/order/notify_alipay_wap';
$config['retrun_url'] = $env_config['base_url'].'/public/order.html?orderId=';
$config['recharge_retrun_url'] = $env_config['base_url'].'/public/recharge.html';
$config['pay_sell_id'] = '';
$config['pay_succ_tpl_id'] = $env_config['pay_succ_tpl_id'];
$config['pay_fail_tpl_id'] = $env_config['pay_fail_tpl_id'];
$config['refund_tpl_id'] = $env_config['refund_tpl_id'];
$config['notify_tpl_id'] = $env_config['notify_tpl_id'];

if(isset($env_config['alipay_sanbox']) && $env_config['alipay_sanbox']){
    $config['gatewayUrl'] = 'https://openapi.alipaydev.com/gateway.do';
}

//-------wechat config----------
$config['wechat_appid'] = $env_config['wechat']['APPID'];
$config['wechat_secret'] = $env_config['wechat']['APPSECRET'];
$config['wechat_mchid'] = $env_config['wechat']['MCHID'];
$config['wechat_key'] = $env_config['wechat']['KEY'];
$config['wechat_planid'] = $env_config['wechat']['PLANID'];

//微信小程序
$config['wechat_program_appid'] = $env_config['wechat']['program_appid'];
$config['wechat_program_secret'] = $env_config['wechat']['program_secret'];


$config['CURL_TIMEOUT'] = 30;
$config['SSLCERT_PATH'] = dirname(dirname(__FILE__)).'/libraries/wechat/cert/apiclient_cert-'.$env_config['wechat']['APPID'].'.pem';
$config['SSLKEY_PATH'] = dirname(dirname(__FILE__)).'/libraries/wechat/cert/apiclient_key-'.$env_config['wechat']['APPID'].'.pem';
$config['entrustweb_notify_url'] = $env_config['base_url'].'/api/account/wecht_notify_entrust';//开通免密协议的回调
$config['p_entrustweb_notify_url'] = $env_config['base_url'].'/api/account/program_notify_entrust';//小程序开通免密协议的回调
$config['pay_notify_url'] = $env_config['base_url'].'/api/order/wecht_notify_pay';//支付的回调

$config['wechat_pay_succ_tpl_id'] = $env_config['wechat_pay_succ_tpl_id'];
$config['wechat_pay_fail_tpl_id'] = $env_config['wechat_pay_fail_tpl_id'];
$config['wechat_refund_tpl_id'] = $env_config['wechat_refund_tpl_id'];
$config['wechat_notify_tpl_id'] = $env_config['wechat_notify_tpl_id'];
$config['wechat_exception_tpl_id'] = $env_config['wechat_exception_tpl_id'];

//----------支付宝免密-----------------
$config['mapi_partner'] = $env_config['partner'];

$config['mapi_seller_id'] = $env_config['seller_id'];

//安全码
$config['mapi_safe_key'] = $env_config['safe_key'];


//支付宝网关
$config['mapi_gatewayUrl'] = "https://mapi.alipay.com/gateway.do";

$config['mapi_sign_type'] = "MD5";

$config['mapi_box_host'] = $env_config['mapi_box_host'];

$config['mapi_qr_code_go_url'] = $config['mapi_box_host']."/public/auth.html"; //二维码跳转页面

$config['mapi_agreement_return_url'] = $env_config['mapi_box_host']."/public/open.html";
$config['mapi_isv_agreement_return_url'] = $env_config['mapi_box_host']."/public/p.html";

//免密签约异步提醒
$config['mapi_agreement_notify_url'] = $config['mapi_box_host'] . "/index.php/api/account/notify_agree";
$config['mapi_isv_agreement_notify_url'] = $config['mapi_box_host'] . "/index.php/api/koubei/notify_agree";//isv

//支付异步提醒
$config['mapi_notify_url'] = $config['mapi_box_host'] . "/index.php/api/order/notify";

$config['mapi_isv_notify_url'] = $config['mapi_box_host'] . "/index.php/api/koubei/notify";//isv

$config['mapi_return_url'] = $config['mapi_box_host'] . "/index.php/api/order/notify";
//签约跳转页面
$config['mapi_sign_url'] = $config['mapi_box_host'] . "/public/sign.html";

$config['mapi_agreement_product_code'] = "GENERAL_WITHHOLDING_P"; //签约product_code
$config['mapi_agreement_scene'] = "INDUSTRY|SUPERMARKET"; //签约scene

$config['mapi_pay_product_code'] = "GENERAL_WITHHOLDING"; //支付product_code
$config['mapi_isv_pay_product_code'] = "FACE_TO_FACE_PAYMENT"; //isv支付product_code
$config['mapi_pay_scene'] = "deduct_pay"; //支付scene
$config['mapi_pay_subject'] = "魔盒CITYBOX购买商品"; //订单标题

$config['ajax_pay'] = $env_config['ajax_pay'];//是否需要异步支付1.异步、0.同步
$config['ajax_pay_key'] = 'city_box_pay_mq';

//-------------zmxy芝麻信用------------------
//应用ID,您的APPID。
$config['zmxy_app_id'] = $env_config['zmxy_app_id'];
$config['zmxy_merchant_id'] = $env_config['zmxy_merchant_id'];
$config['zmxy_type_id'] = $env_config['zmxy_type_id'];

//商户私钥，您的原始格式RSA私钥,一行字符串 *** 去掉换行
$config['zmxy_merchant_private_key'] = "MIICXQIBAAKBgQDNYhLB5gvYHFJqIiVxn8sMieUE+jHB5dZJ2lmU7d2q2N2MCA+rW3XAv2M/eQCtZ+WKGtgdUuOu1EN/6g2tdd8mxgW1I4Bm7S60cWuGKtkc2myyD8N3Hssk9KDhA5gloc3q5IBgBf9wWqCcXAT7clitlK5M083roI1PJQHxQS5gxQIDAQABAoGBALTjkPO34mynnSqfAm2NuG9FsDDvDw3gmRiYuFeEHLzRnmcr3mkk95QYvJf1wdP4cuFs/TTugVvE1eJ+SSeibjOLFM57no2RPI2lDGTGpulYvgYbQiVvrrrlaIxpw8oINoLygIC6d4lunj1QrWdnIwLqhZRIMqURVu47UL1YR2ThAkEA7ChhmgB4idhj7ZBbE8/XpDmonA4HCNb9HeU9CKdhNjpt301KLosfk/5OvQI1GE3jDrea3lqso716H6HWbYNqnQJBAN6jvzqe7pIgkfYpmZLARKZlx6az5VDY2JGz4mGPQMW2l3iqhbRcyaHofUkrkZo0C+LGfrvrA/cGJYa+Z+5ywkkCQGLWn8rZqZlfxKr4APZwxbsJGsV9pXoQqM1rVTka/Le6iqOr8IE8XxIMnJ3En741UvOk6p9nadv6AHPewyUAnI0CQBU3+fO2TfpzTDXvxQktdd19+cczgflwkUNhp4OwyXWOb2U6qz+DUFwz8izVEC1oJHHahR2XymrylQUAhJs/KLECQQC0BiTAt6qhGP1/i+5IhfOVys3fmlLq6lEOYkky/pVBJQ4CfQBrgLq0lx1g9kU4xvnKhL5QjdW/aUd8Dq0QSb9c";


//商户公钥，您的原始格式RSA私钥,一行字符串 *** 去掉换行
$config['zmxy_merchant_public_key'] = "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDNYhLB5gvYHFJqIiVxn8sMieUE+jHB5dZJ2lmU7d2q2N2MCA+rW3XAv2M/eQCtZ+WKGtgdUuOu1EN/6g2tdd8mxgW1I4Bm7S60cWuGKtkc2myyD8N3Hssk9KDhA5gloc3q5IBgBf9wWqCcXAT7clitlK5M083roI1PJQHxQS5gxQIDAQAB";


//芝麻信用公钥,查看地址：https://b.zmxy.com.cn/technology/myApps.htm 对应APPID下的芝麻信用宝公钥。 *** 去掉换行
$config['zmxy_zm_public_key'] = $env_config['zm_public_key'];
$config['zmxy_charset'] = "UTF-8";

//支付宝网关
$config['zmxy_gatewayUrl'] = "https://zmopenapi.zmxy.com.cn/openapi.do";
$config['zmxy_sign_type'] ="RSA";

//-------------other 配置------------------
$config['refers'] = array("alipay","wechat","fruitday-app","gat","cmb","sodexo","sdy");
$config['error_msg'] = "请使用微信、支付宝扫码";
$config['error_url'] = "";
$config['use_yue'] = 1;
$config['common_pr'] = $env_config['base_url'].'/public/p.html?d=DEVICEID';//二维码是前缀

//-------------支付宝设备入驻配置------------------
$config['product_user_id'] = '2088221926206318';//厂商支付宝id
$config['merchant_user_id'] = '2088221926206318';//设备使用商户支付宝id

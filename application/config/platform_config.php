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
$config['retrun_url'] = $env_config['base_url'].'/mars/order.html?orderId=';
$config['recharge_retrun_url'] = $env_config['base_url'].'/mars/recharge.mars';
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

$config['mapi_qr_code_go_url'] = $config['mapi_box_host']."/mars/index.html"; //二维码跳转页面

$config['mapi_agreement_return_url'] = $env_config['mapi_box_host']."/public/p.html";
$config['mapi_isv_agreement_return_url'] = $env_config['mapi_box_host']."/public/p.html";

//免密签约异步提醒
$config['mapi_agreement_notify_url'] = $config['mapi_box_host'] . "/index.php/api/account/notify_agree";
$config['mapi_isv_agreement_notify_url'] = $config['mapi_box_host'] . "/index.php/api/koubei/notify_agree";//isv

//支付异步提醒
$config['mapi_notify_url'] = $config['mapi_box_host'] . "/api/dalang/notify";

$config['mapi_isv_notify_url'] = $config['mapi_box_host'] . "/api/koubei/notify";//isv

$config['mapi_return_url'] = $config['mapi_box_host'] . "/api/dalang/notify";
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

$config['merchant_private_key'] ='MIIEogIBAAKCAQEA1bPhH/goUmhtlgGBY8oU88aFpEm0IaPp6vSEYW4yicRDgJpxi8VhcEE2fcaaQemVw4zEZlpmPFjAo4DQP5l8EUv4l7pU6M6Q87xkzSGjuYrplXgk3AcchbPORtotnZeC5fMWTH5PYJZ71i20n2Sx75hrHLtqLIve1MZ1cPU7x9R1UyPReg6ry4G/NdoSgkXsq+OVpzXZQK/Hm3UxpkxM23+1StzJNxFRqHIaeK73HZOGcnOHYTPAAIWzbN4QVFNtTNrb7U7MHX6xjQNGeLAGTCGLtSngf6PpwKyOvAZ13C/EQxNUcMdUh2JQpgIfPsWrCVhlKrAtveQtWpR8bZDYpwIDAQABAoIBAGEOum9voLiUzziy5FYzIML65hWQl+wzavkYJsutZeymI9ZTzsAhXDjElYAYZFUNRsSyuTyXUBmWYZ+g0HQiPHQKohQfP5Mgxjq81LdJ0Pdi/OWy1GLOJAkhec06KD+L4ZqYhgcl0t1WW1YSGhfOfvYHrpY1FGq49/KBVDOCXa1KnxL9/cYgTclEAIjGOy25gkOxw9q3H3NfQkNNOyIdmVr4NOKhoFhAK8azUytT7PcCqC/0fAxVvP2+rYnih++2LANkr+uSBKzrQb4ZjEMtW9v/vhRmKG/yKl5Y5XnVBKsv76hz9dKitpR/z7f2//0IyUKyLt+VkXwAwtAkW42G/FECgYEA/CO1XKFjU1Z5e69pSNSC09uzaJV96ReZycoVWsGqekbEHi17Ghvb/n+YlJ83NffOyHXzNguGK1emaJY4rUAzqB6plKzvR0kjQfMvOXfnSkNnj897vp7z88as1vN3DWoap0sUKMZ25vWtS4GErG7UkRGYbScWJloxuE0ZO/NwJ3kCgYEA2PmDWXyL5vCthy4jn2gbkFpKGjrC4qlm/v3Iv0FgNlyKHsrT3f+WRZJRvGyHytLpnslLo2vepRmc9hUPjkCHUzmOfXWhFHY7TEq71jFxCpk0Xw/mJm9m+yzye6HnhJXbMFEld5ZST0jFDvvR2P0FIUP/+p0NOeNLiL/FycebWR8CgYBWZgIKiL7ZWsNsD5J7Q17FK/6RMtCfY9ft3pJss/ovxjoDwT/ylWNQFPb6zogDtTOlW02I0nAaQAGkyv0G2P4aeM9RQ+UGP1iWi7c98QlPWEOPcuCLVDpx6T1mtqyv6xDRDJgO1Nr/j2XffrUwxRWP1ECv/nnHkJaC+eBQu71NuQKBgBclLrTKC2Z8QBaOfnBPu7j/WK5JnGZvabRDwDlesPO7lWRJgKZK0G3leOCftzCUpSUyFeZ96Ec2Xz8E6h7jlUv0dNW/SYqUPikaQ8VKiuN1ilelq4hoE9Uxa0By+e5zyejyjwudtnQMsDCz/iOmgfiVd2X1gOau72zMAD5RE6pDAoGAGdwtfBUArsaehNZO0ttNjgtfH/diZ2ta6UicywVHFusmpRyupyXo0hUlFRcWVCbV+ocMMEtYcN6nvFIXD36zpzTosCReiuGqy6+SqC14DdzaVCg4uw6Z9C1dlw8WnYGcCeYxs7iCqox89PxlSrRVbP8/KRd/okOG7eSNte3pR0Y=';
$config['merchant_public_key'] ='MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA1bPhH/goUmhtlgGBY8oU88aFpEm0IaPp6vSEYW4yicRDgJpxi8VhcEE2fcaaQemVw4zEZlpmPFjAo4DQP5l8EUv4l7pU6M6Q87xkzSGjuYrplXgk3AcchbPORtotnZeC5fMWTH5PYJZ71i20n2Sx75hrHLtqLIve1MZ1cPU7x9R1UyPReg6ry4G/NdoSgkXsq+OVpzXZQK/Hm3UxpkxM23+1StzJNxFRqHIaeK73HZOGcnOHYTPAAIWzbN4QVFNtTNrb7U7MHX6xjQNGeLAGTCGLtSngf6PpwKyOvAZ13C/EQxNUcMdUh2JQpgIfPsWrCVhlKrAtveQtWpR8bZDYpwIDAQAB';

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
$config['product_user_id'] = '';//厂商支付宝id
$config['merchant_user_id'] = '';//设备使用商户支付宝id

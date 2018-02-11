<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/*
|--------------------------------------------------------------------------
| HTTP protocol
|--------------------------------------------------------------------------
|
| Set to force the use of HTTPS for REST API calls
|
*/
$config['force_https'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Output Format
|--------------------------------------------------------------------------
|
| The default format of the response
|
| 'array':      Array data structure
| 'csv':        Comma separated file
| 'json':       Uses json_encode(). Note: If a GET query string
|               called 'callback' is passed, then jsonp will be returned
| 'html'        HTML using the table library in CodeIgniter
| 'php':        Uses var_export()
| 'serialized':  Uses serialize()
| 'xml':        Uses simplexml_load_string()
|
*/
$config['rest_default_format'] = 'json';

/*
|--------------------------------------------------------------------------
| REST Supported Output Formats
|--------------------------------------------------------------------------
|
| The following setting contains a list of the supported/allowed formats.
| You may remove those formats that you don't want to use.
| If the default format $config['rest_default_format'] is missing within
| $config['rest_supported_formats'], it will be added silently during
| REST_Controller initialization.
|
*/
$config['rest_supported_formats'] = [
    'json',
    'array',
    'csv',
    'html',
    'jsonp',
    'php',
    'serialized',
    'xml',
];

/*
|--------------------------------------------------------------------------
| REST Status Field Name
|--------------------------------------------------------------------------
|
| The field name for the status inside the response
|
*/
$config['rest_status_field_name'] = 'status';

/*
|--------------------------------------------------------------------------
| REST Message Field Name
|--------------------------------------------------------------------------
|
| The field name for the message inside the response
|
*/
$config['rest_message_field_name'] = 'message';

/*
|--------------------------------------------------------------------------
| Enable Emulate Request
|--------------------------------------------------------------------------
|
| Should we enable emulation of the request (e.g. used in Mootools request)
|
*/
$config['enable_emulate_request'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST Realm
|--------------------------------------------------------------------------
|
| Name of the password protected REST API displayed on login dialogs
|
| e.g: My Secret REST API
|
*/
$config['rest_realm'] = 'REST API';

/*
|--------------------------------------------------------------------------
| REST Login
|--------------------------------------------------------------------------
|
| Set to specify the REST API requires to be logged in
|
| FALSE     No login required
| 'basic'   Unsecured login
| 'digest'  More secured login
| 'session' Check for a PHP session variable. See 'auth_source' to set the
|           authorization key
| 'custom' 用户自定义授权规则
|
*/
$config['rest_auth'] = $env_config['rest_auth'];

/**
 * 运行请求的host
 */
$config['allow_host'] = $env_config['allow_host'];
//API加密串
$config['api_secret']= $env_config['api_secret'];
//不验证授权uri
$config['no_auth_uri'] = [
//    'api/account/test_login',// 测试环境登录调试
    'api/account/get_vcode',//获取短信验证码
    'api/account/auth_alipay_login',//支付宝授权
    'api/account/auth_wechat_login',//微信授权
    'api/account/goto_sign', //生成支付宝签约url
    'api/order/notify', //支付宝支付-退款notify
    'api/account/zmxy_return', //芝麻信用回调
    'api/account/auth_platform_login', //天天果园授权登录
    'api/account/notify_agree',// 支付宝签约notify
    'api/order/notify_alipay_wap',// 支付宝wap pay notify
    'api/account/get_config_by_device_id',// 获取配置信息
    'api/account/log_platform_access',// 记录第三方访问日志
    'api/account/platform_login',// 第三方平台登录
    'api/account/wecht_notify_entrust',// 微信开通免密协议的回调
    'api/account/program_notify_entrust',// 微信小程序签约回调
    'api/account/wechat_deletecontract',// 微信免密协议解约
    'api/account/delete_sign',// 支付宝免密协议解约
    'api/account/getjsapisign',// 微信签名
    'api/order/wecht_notify_pay',// 微信支付的回调
    'api/order/notify_gat',// 关爱通支付的回调
    'api/cart/scan_status',// 扫码
    'api/public_tool/del_wx_token',// 删除微信token扫码
    'api/public_tool/wx_token',// 微信token扫码
    'api/public_tool/test_wx_msg',// 微信token扫码
    'api/gat/open_door', //关爱通扫码开门
    'api/account/wx_jscode2session', //微信小程序根据code获取用户信息
    'api/account/program_entrust', //微信小程序签约
    'api/account/gat_only_login',//关爱通只登录
    'api/account/update_wechat_unioid',//微信根据open_id获取unionid
    'api/account/wx_program_user_info', //微信小程序用户信息补全
    'api/account/program_jscode2session', //微信小程序用户信息补全
    'api/account/ge_user_unionid_by_open_id', //微信小程序用户open_id
    'api/account/program_update_agreement', //更新用户协议号
    'api/account/login_user_program', //更新用户协议号
    'api/koubei/notify_agree', //口碑签约
    'api/koubei/delete_sign', //口碑解约
    'api/koubei/notify', //口碑免密支付回调
    'api/koubei/notify_alipay_wap', //口碑手动支付回调
    'api/koubei/auth_koubei_login', //口碑第三方APP授权
    'api/koubei/gateway', //口碑推送
    'api/open/sodexo_open_door', //索迪斯开门
    'api/open/open_door',//沙丁鱼开门
    'api/open/equipment_list',//沙丁鱼获取设备列表
    'api/order/notify_sdy',//沙丁鱼支付回调
    'api/cmb/dotest',//招商银行app相关api
    'api/cmb/cmb_open',//招商银行app相关api
    'api/cmb/cmb_balance',//招商银行app相关api
    'api/cmb/notifyhandler',//招商银行app相关api
    'api/cmb/returnhandler',//招商银行app相关api
    'api/account/cmb_login',//招商银行app相关api
    'api/device/barcode',//生成条码
];

$config['wap_auth_uri'] = [
    'api/account/get_info',
    'api/account/bind',
    'api/account/zmxy_auth',
    'api/account/coupon_list',//优惠券列表
    'api/account/thirdpartycoupon_list',
    'api/account/rec_admin',
    'api/order/list_order',
    'api/order/get_detail',
    'api/order/refund',
    'api/order/list_deliver',
    'api/order/pay_by_manual',//前台手动发起支付
    'api/device/open_door',
    'api/device/index_ad',
    'api/device/order_and_ad',
    'api/fruitday/get_info',
    'api/fruitday/recharge',
    'api/fruitday/open_door',
    'api/fruitday/box_status',
    'api/fruitday/get_order_and_amount',
    'api/cart/car_goods_pay',
    'api/cart/car_goods',
    'api/cart/del_car_goods',
    'api/cart/add_product_cart',
    'api/cart/car_goods_pay_wx',
    'api/deliver/deliver_open',//补货员开门
    'api/deliver/fix_product',//商品增减
    'api/deliver/init_goods',//补货员开门
    'api/deliver/confirm_stock',//手动盘点提交
    'api/deliver/init_eq_goods',//手动盘点初始化
    'api/deliver/open_scan',//补货员开门
    'api/deliver/confirm_deliver',//补货员确认商品
    'api/device/buy_box_address', //获取最近开过门的盒子地址
    'api/device/box_info', //获取盒子中的商品信息
    'api/device/qr_card', //扫码领券
    'api/device/new_index', //首页
    'api/device/exist_thirdparty', //是否有第三方优惠券
    'api/device/thirdparty_card', //第三方领券接口
    'api/device/thirdparty_detail', //第三方券点击详情接口
    'api/order/program_wap_pay', //微信小程序发起支付
    'api/account/amount_info', //获取余额信息
    'api/account/recharge_money', //充值金额
    'api/account/recharge_card', //卡券充值
    'api/account/amount_detail', //账号明细
    'api/account/warn_user_info', //账号绑定
    'api/device/index_card', //优惠券
    'api/device/order_card', //结算页优惠券
    'api/koubei/koubei_shop_discount_query', //口碑请求券
    'api/koubei/koubei_benefit_send', //领取优惠券
];

$config['admin_auth_uri'] = [
    'api/device/list_device',
    'api/device/stock',//发起盘点
    'api/device/get_info',//发起获取设备信息请求
    'api/public_tool/create_qr_code',
    'api/public_tool/create_short_url',
    'api/public_tool/general_qr_code',
    'api/order/refund_approval',
    'api/permission/add_shipping_permission',
    'api/permission/update_shipping_permission',
    'api/permission/update_shipping_order_car_info',
    'api/shipping/confirm_status',
    'api/shipping/confirm_num',
    'api/order/update_pay_order',
    'api/order/pay_by_manual',//后台手动发起支付
    'api/order/refund_against',//申请驳回
    'api/fruitday/get_detail',
    'api/message_tpl/send_wechat_warn_tpl',
    'api/account/update_user_black',
    'api/order/check_order'
];

$config['device_auth_uri'] = [
    'api/device/receive_msg',
    'api/device/receive_heart_msg',
    'api/device/receive_power_msg',
    'api/device/receive_door_msg',
    'api/device/receive_stock_msg',
];
$config['bjdevice_auth_uri'] = [
    /*以下是北京新设备*/
    'api/device/query_device_info',
    'api/device/query_sim_info',
    'api/device/receive_new_msg',
    'api/device/receive_heart_new_msg',
    'api/device/receive_power_new_msg',
    'api/device/receive_door_new_msg',
    'api/device/receive_stock_new_msg',
    'api/device/receive_exception_new_msg',
    'api/cart/add_product_use_code',
    'api/cart/banner',
];
//一体机
$config['client_auth_uri'] = [
    'api/device/label_product_table',
    'api/device/bind_lable_product',
    'api/device/ajax_search_proudct_by_name',
    'api/device/bind_user_check',//检测用户是否有权限
    'api/device/ajax_warehouse',//仓库

    'api/client/label_product_table',
    'api/client/bind_lable_product',
    'api/client/ajax_search_proudct_by_name',
    'api/client/bind_user_check',//检测用户是否有权限
    'api/client/ajax_warehouse',//仓库
];

/*
|--------------------------------------------------------------------------
| REST Login Source
|--------------------------------------------------------------------------
|
| Is login required and if so, the user store to use
|
| ''        Use config based users or wildcard testing
| 'ldap'    Use LDAP authentication
| 'library' Use a authentication library
|
| Note: If 'rest_auth' is set to 'session' then change 'auth_source' to the name of the session variable
|
*/
$config['auth_source'] = '';

/*
|--------------------------------------------------------------------------
| Allow Authentication and API Keys
|--------------------------------------------------------------------------
|
| Where you wish to have Basic, Digest or Session login, but also want to use API Keys (for limiting
| requests etc), set to TRUE;
|
*/
$config['allow_auth_and_keys'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Login Class and Function
|--------------------------------------------------------------------------
|
| If library authentication is used define the class and function name
|
| The function should accept two parameters: class->function($username, $password)
| In other cases override the function _perform_library_auth in your controller
|
| For digest authentication the library function should return already a stored
| md5(username:restrealm:password) for that username
|
| e.g: md5('admin:REST API:1234') = '1e957ebc35631ab22d5bd6526bd14ea2'
|
*/
$config['auth_library_class'] = '';
$config['auth_library_function'] = '';

/*
|--------------------------------------------------------------------------
| Override auth types for specific class/method
|--------------------------------------------------------------------------
|
| Set specific authentication types for methods within a class (controller)
|
| Set as many config entries as needed.  Any methods not set will use the default 'rest_auth' config value.
|
| e.g:
|
|           $config['auth_override_class_method']['deals']['view'] = 'none';
|           $config['auth_override_class_method']['deals']['insert'] = 'digest';
|           $config['auth_override_class_method']['accounts']['user'] = 'basic';
|           $config['auth_override_class_method']['dashboard']['*'] = 'none|digest|basic';
|
| Here 'deals', 'accounts' and 'dashboard' are controller names, 'view', 'insert' and 'user' are methods within. An asterisk may also be used to specify an authentication method for an entire classes methods. Ex: $config['auth_override_class_method']['dashboard']['*'] = 'basic'; (NOTE: leave off the '_get' or '_post' from the end of the method name)
| Acceptable values are; 'none', 'digest' and 'basic'.
|
*/
// $config['auth_override_class_method']['deals']['view'] = 'none';
// $config['auth_override_class_method']['deals']['insert'] = 'digest';
// $config['auth_override_class_method']['accounts']['user'] = 'basic';
// $config['auth_override_class_method']['dashboard']['*'] = 'basic';


// ---Uncomment list line for the wildard unit test
// $config['auth_override_class_method']['wildcard_test_cases']['*'] = 'basic';

/*
|--------------------------------------------------------------------------
| Override auth types for specific 'class/method/HTTP method'
|--------------------------------------------------------------------------
|
| example:
|
|            $config['auth_override_class_method_http']['deals']['view']['get'] = 'none';
|            $config['auth_override_class_method_http']['deals']['insert']['post'] = 'none';
|            $config['auth_override_class_method_http']['deals']['*']['options'] = 'none';
*/

// ---Uncomment list line for the wildard unit test
// $config['auth_override_class_method_http']['wildcard_test_cases']['*']['options'] = 'basic';

/*
|--------------------------------------------------------------------------
| REST Login Usernames
|--------------------------------------------------------------------------
|
| Array of usernames and passwords for login, if ldap is configured this is ignored
|
*/
$config['rest_valid_logins'] = ['admin' => '1234'];

/*
|--------------------------------------------------------------------------
| Global IP White-listing
|--------------------------------------------------------------------------
|
| Limit connections to your REST server to White-listed IP addresses
|
| Usage:
| 1. Set to TRUE and select an auth option for extreme security (client's IP
|    address must be in white-list and they must also log in)
| 2. Set to TRUE with auth set to FALSE to allow White-listed IPs access with no login
| 3. Set to FALSE but set 'auth_override_class_method' to 'white-list' to
|    restrict certain methods to IPs in your white-list
|
*/
$config['rest_ip_whitelist_enabled'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Handle Exceptions
|--------------------------------------------------------------------------
|
| Handle exceptions caused by the controller
|
*/
$config['rest_handle_exceptions'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST IP White-list
|--------------------------------------------------------------------------
|
| Limit connections to your REST server with a comma separated
| list of IP addresses
|
| e.g: '123.456.789.0, 987.654.32.1'
|
| 127.0.0.1 and 0.0.0.0 are allowed by default
|
*/
$config['rest_ip_whitelist'] = '';

/*
|--------------------------------------------------------------------------
| Global IP Blacklisting
|--------------------------------------------------------------------------
|
| Prevent connections to the REST server from blacklisted IP addresses
|
| Usage:
| 1. Set to TRUE and add any IP address to 'rest_ip_blacklist'
|
*/
$config['rest_ip_blacklist_enabled'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST IP Blacklist
|--------------------------------------------------------------------------
|
| Prevent connections from the following IP addresses
|
| e.g: '123.456.789.0, 987.654.32.1'
|
*/
$config['rest_ip_blacklist'] = '';

/*
|--------------------------------------------------------------------------
| REST Database Group
|--------------------------------------------------------------------------
|
| Connect to a database group for keys, logging, etc. It will only connect
| if you have any of these features enabled
|
*/
$config['rest_database_group'] = 'default_master';

/*
|--------------------------------------------------------------------------
| REST API Keys Table Name
|--------------------------------------------------------------------------
|
| The table name in your database that stores API keys
|
*/
$config['rest_keys_table'] = 'keys';

/*
|--------------------------------------------------------------------------
| REST Enable Keys
|--------------------------------------------------------------------------
|
| When set to TRUE, the REST API will look for a column name called 'key'.
| If no key is provided, the request will result in an error. To override the
| column name see 'rest_key_column'
|
| Default table schema:
|   CREATE TABLE `keys` (
|       `id` INT(11) NOT NULL AUTO_INCREMENT,
|       `user_id` INT(11) NOT NULL,
|       `key` VARCHAR(40) NOT NULL,
|       `level` INT(2) NOT NULL,
|       `ignore_limits` TINYINT(1) NOT NULL DEFAULT '0',
|       `is_private_key` TINYINT(1)  NOT NULL DEFAULT '0',
|       `ip_addresses` TEXT NULL DEFAULT NULL,
|       `date_created` INT(11) NOT NULL,
|       PRIMARY KEY (`id`)
|   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_keys'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Table Key Column Name
|--------------------------------------------------------------------------
|
| If not using the default table schema in 'rest_enable_keys', specify the
| column name to match e.g. my_key
|
*/
$config['rest_key_column'] = 'key';

/*
|--------------------------------------------------------------------------
| REST API Limits method
|--------------------------------------------------------------------------
|
| Specify the method used to limit the API calls
|
| Available methods are :
| $config['rest_limits_method'] = 'IP_ADDRESS'; // Put a limit per ip address
| $config['rest_limits_method'] = 'API_KEY'; // Put a limit per api key
| $config['rest_limits_method'] = 'METHOD_NAME'; // Put a limit on method calls
| $config['rest_limits_method'] = 'ROUTED_URL';  // Put a limit on the routed URL
|
*/
$config['rest_limits_method'] = 'ROUTED_URL';

/*
|--------------------------------------------------------------------------
| REST Key Length
|--------------------------------------------------------------------------
|
| Length of the created keys. Check your default database schema on the
| maximum length allowed
|
| Note: The maximum length is 40
|
*/
$config['rest_key_length'] = 40;

/*
|--------------------------------------------------------------------------
| REST API Key Variable
|--------------------------------------------------------------------------
|
| Custom header to specify the API key

| Note: Custom headers with the X- prefix are deprecated as of
| 2012/06/12. See RFC 6648 specification for more details
|
*/
$config['rest_key_name'] = 'X-API-KEY';

/*
|--------------------------------------------------------------------------
| REST Enable Logging
|--------------------------------------------------------------------------
|
| When set to TRUE, the REST API will log actions based on the column names 'key', 'date',
| 'time' and 'ip_address'. This is a general rule that can be overridden in the
| $this->method array for each controller
|
| Default table schema:
|   CREATE TABLE `logs` (
|       `id` INT(11) NOT NULL AUTO_INCREMENT,
|       `uri` VARCHAR(255) NOT NULL,
|       `method` VARCHAR(6) NOT NULL,
|       `params` TEXT DEFAULT NULL,
|       `api_key` VARCHAR(40) NOT NULL,
|       `ip_address` VARCHAR(45) NOT NULL,
|       `time` INT(11) NOT NULL,
|       `rtime` FLOAT DEFAULT NULL,
|       `authorized` VARCHAR(1) NOT NULL,
|       `response_code` smallint(3) DEFAULT '0',
|       PRIMARY KEY (`id`)
|   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_logging'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST API Logs Table Name
|--------------------------------------------------------------------------
|
| If not using the default table schema in 'rest_enable_logging', specify the
| table name to match e.g. my_logs
|
*/
$config['rest_logs_table'] = 'logs';

/*
|--------------------------------------------------------------------------
| REST Method Access Control
|--------------------------------------------------------------------------
| When set to TRUE, the REST API will check the access table to see if
| the API key can access that controller. 'rest_enable_keys' must be enabled
| to use this
|
| Default table schema:
|   CREATE TABLE `access` (
|       `id` INT(11) unsigned NOT NULL AUTO_INCREMENT,
|       `key` VARCHAR(40) NOT NULL DEFAULT '',
|       `all_access` TINYINT(1) NOT NULL DEFAULT '0',
|       `controller` VARCHAR(50) NOT NULL DEFAULT '',
|       `date_created` DATETIME DEFAULT NULL,
|       `date_modified` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
|       PRIMARY KEY (`id`)
|    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
*/
$config['rest_enable_access'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST API Access Table Name
|--------------------------------------------------------------------------
|
| If not using the default table schema in 'rest_enable_access', specify the
| table name to match e.g. my_access
|
*/
$config['rest_access_table'] = 'access';

/*
|--------------------------------------------------------------------------
| REST API Param Log Format
|--------------------------------------------------------------------------
|
| When set to TRUE, the REST API log parameters will be stored in the database as JSON
| Set to FALSE to log as serialized PHP
|
*/
$config['rest_logs_json_params'] = TRUE;

/*
|--------------------------------------------------------------------------
| REST Enable Limits
|--------------------------------------------------------------------------
|
| When set to TRUE, the REST API will count the number of uses of each method
| by an API key each hour. This is a general rule that can be overridden in the
| $this->method array in each controller
|
| Default table schema:
|   CREATE TABLE `limits` (
|       `id` INT(11) NOT NULL AUTO_INCREMENT,
|       `uri` VARCHAR(255) NOT NULL,
|       `count` INT(10) NOT NULL,
|       `hour_started` INT(11) NOT NULL,
|       `api_key` VARCHAR(40) NOT NULL,
|       PRIMARY KEY (`id`)
|   ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
|
| To specify the limits within the controller's __construct() method, add per-method
| limits with:
|
|       $this->method['METHOD_NAME']['limit'] = [NUM_REQUESTS_PER_HOUR];
|
| See application/controllers/api/example.php for examples
*/
$config['rest_enable_limits'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST API Limits Table Name
|--------------------------------------------------------------------------
|
| If not using the default table schema in 'rest_enable_limits', specify the
| table name to match e.g. my_limits
|
*/
$config['rest_limits_table'] = 'limits';

/*
|--------------------------------------------------------------------------
| REST Ignore HTTP Accept
|--------------------------------------------------------------------------
|
| Set to TRUE to ignore the HTTP Accept and speed up each request a little.
| Only do this if you are using the $this->rest_format or /format/xml in URLs
|
*/
$config['rest_ignore_http_accept'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST AJAX Only
|--------------------------------------------------------------------------
|
| Set to TRUE to allow AJAX requests only. Set to FALSE to accept HTTP requests
|
| Note: If set to TRUE and the request is not AJAX, a 505 response with the
| error message 'Only AJAX requests are accepted.' will be returned.
|
| Hint: This is good for production environments
|
*/
$config['rest_ajax_only'] = FALSE;

/*
|--------------------------------------------------------------------------
| REST Language File
|--------------------------------------------------------------------------
|
| Language file to load from the language directory
|
*/
$config['rest_language'] = 'english';

/*
|--------------------------------------------------------------------------
| CORS Check
|--------------------------------------------------------------------------
|
| Set to TRUE to enable Cross-Origin Resource Sharing (CORS). Useful if you
| are hosting your API on a different domain from the application that
| will access it through a browser
|
*/
$config['check_cors'] = FALSE;

/*
|--------------------------------------------------------------------------
| CORS Allowable Headers
|--------------------------------------------------------------------------
|
| If using CORS checks, set the allowable headers here
|
*/
$config['allowed_cors_headers'] = [
  'Origin',
  'X-Requested-With',
  'Content-Type',
  'Accept',
  'Access-Control-Request-Method'
];

/*
|--------------------------------------------------------------------------
| CORS Allowable Methods
|--------------------------------------------------------------------------
|
| If using CORS checks, you can set the methods you want to be allowed
|
*/
$config['allowed_cors_methods'] = [
  'GET',
  'POST',
  'OPTIONS',
  'PUT',
  'PATCH',
  'DELETE'
];

/*
|--------------------------------------------------------------------------
| CORS Allow Any Domain
|--------------------------------------------------------------------------
|
| Set to TRUE to enable Cross-Origin Resource Sharing (CORS) from any
| source domain
|
*/
$config['allow_any_cors_domain'] = FALSE;

/*
|--------------------------------------------------------------------------
| CORS Allowable Domains
|--------------------------------------------------------------------------
|
| Used if $config['check_cors'] is set to TRUE and $config['allow_any_cors_domain']
| is set to FALSE. Set all the allowable domains within the array
|
| e.g. $config['allowed_origins'] = ['http://www.example.com', 'https://spa.example.com']
|
*/
$config['allowed_cors_origins'] = [];

<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

/**
 * 使用SDK执行接口请求
 * @param unknown $request
 * @param string $token
 * @return Ambigous <boolean, mixed>
 */


function get_auth_app_token_for_isv($config)
{
    $ci =& get_instance();
    $auth_app_id = $config['auth_app_id'];
    $key = APP_AUTH_TOKEN_KEY . $auth_app_id;
    $app_auth_token = $ci->cache->redis->get($key);
    if (!$app_auth_token) {
        $key_refresh = APP_REFRESH_AUTH_TOKEN_KEY . $auth_app_id;
        $refresh_token = $ci->cache->redis->get($key_refresh);
        $rs = koubei_auth_token_request($refresh_token, $config, 1);
        $key = APP_AUTH_TOKEN_KEY . $rs['auth_app_id'];
        $ci->cache->redis->save($key, $rs['app_auth_token'], $rs['expires_in']);
        $ci->cache->redis->save($key_refresh, $rs['app_refresh_token'], $rs['re_expires_in']);
        return $rs['app_auth_token'];
    }
    return $app_auth_token;
}

function koubei_request_execute($request, $token = NULL, $config, $app_auth_token = NULL)
{

    $ci =& get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient ();
    $aop->gatewayUrl = $config ['gatewayUrl'];
    $aop->appId = $config ['app_id'];
    $aop->rsaPrivateKey = $config['merchant_private_key'];
    $aop->alipayrsaPublicKey = $config['alipay_public_key'];
    $aop->signType = $config['sign_type'];
//    $aop->postCharset = $config['charset'];
    $aop->apiVersion = "1.0";
    $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
    $result = $aop->execute($request, $token, $app_auth_token);
    write_log("koubei response: " . var_export($result, true));
    return $result->$responseNode ? (array)$result->$responseNode : $result;
}

function koubei_auth_token_request($code, $config, $is_refresh = 0)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayOpenAuthTokenAppRequest");
    $alipay = new AlipayOpenAuthTokenAppRequest();
    if ($is_refresh) {
        $arr = array(
            'refresh_token' => $code,//上一次的token
            'grant_type' => 'refresh_token'
        );
    } else {
        $arr = array(
            'code' => $code,
            'grant_type' => 'authorization_code'
        );
    }
    $alipay->setBizContent(json_encode($arr));
    return koubei_request_execute($alipay, null, $config);
}

function koubei_query_token_request($app_auth_token, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayOpenAuthTokenAppQueryRequest");
    $alipay = new AlipayOpenAuthTokenAppQueryRequest();
    $arr = array(
        'app_auth_token' => $app_auth_token,
    );
    $alipay->setBizContent(json_encode($arr));
    return koubei_request_execute($alipay, null, $config);
}


function koubei_shop_discount_query_request($shop_id, $user_id, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayOfflineMarketShopDiscountQueryRequest");
    $alipay = new AlipayOfflineMarketShopDiscountQueryRequest();
    $arr = array(
        'shop_id' => $shop_id,
        'query_type' => 'MERCHANT',
        'user_id' => $user_id,
    );
    $alipay->setBizContent(json_encode($arr));
    $app_auth_token = get_auth_app_token_for_isv($config);
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}

function koubei_marketing_campaign_benefit_send($data, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/KoubeiMarketingToolPrizesendAuthRequest");
    $alipay = new KoubeiMarketingToolPrizesendAuthRequest();
    $alipay->setBizContent(json_encode($data));
    $app_auth_token = get_auth_app_token_for_isv($config);
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}


function koubei_createandpay_request($order, $config, $shop_id = null)
{
    if (empty($order['agreement_no'])) {
        write_log("pay 协议信息不存在 :" . var_export($order, 1), 'info');
        $rs = array(
            "code" => "-1",
            "msg" => "协议信息不存在",
            "sub_code" => "AGREEMENT_NOT_EXIST",
            "sub_msg" => "协议信息不存在"
        );
        return $rs;
    } else {
        require_once FCPATH . 'application/libraries/aop/request/AlipayTradePayRequest.php';
        $alipay = new AlipayTradePayRequest ();
        $biz_content = array(
            'out_trade_no' => $order['pay_no'],
            'scene' => $config['mapi_pay_scene'],
            'auth_code' => $order['agreement_no'],//签约时返回的协议号
            'product_code' => $config['mapi_isv_pay_product_code'],
            'subject' => $config['mapi_pay_subject'],
            'total_amount' => $order['money'],
//            'body' => $config['mapi_pay_subject'],
//            'GoodsDetail' => $order['goods'],
            'terminal_id' => $order['device_id'],
        );
        foreach ($order['goods'] as $k => $v) {
            $order['goods'][$k]['goods_id'] = $v['goodsId'];
            unset($order['goods'][$k]['goodsId']);
            $order['goods'][$k]['goods_name'] = $v['goodsName'];
            unset($order['goods'][$k]['goodsName']);
            $order['goods'][$k]['goods_category'] = $v['goodsCategory'];
            unset($order['goods'][$k]['goodsCategory']);
        }
        $biz_content['goods_detail'] = $order['goods'];
        if ($shop_id) {
            $biz_content['alipay_store_id'] = $shop_id;
        }
        //系统商编号 该参数作为系统商返佣数据提取的依据，请填写系统商签约协议的PID
        if($config['sys_service_provider_id']){
            $biz_content['extend_params'] = array('sys_service_provider_id'=>$config['sys_service_provider_id']);
        }
        if ($config['pay_sell_id']) {
            $biz_content['seller_id'] = $config['pay_sell_id'];
        }
        $alipay->setNotifyUrl($config['mapi_isv_notify_url'] . '?device_id=' . $order['device_id']);
        $alipay->setReturnUrl($config['mapi_isv_notify_url'] . '?device_id=' . $order['device_id']);


        $alipay->setBizContent(json_encode($biz_content));
        $app_auth_token = get_auth_app_token_for_isv($config);
        write_log("koubei pay req:" . var_export($alipay, 1), 'info');
        $result = koubei_request_execute($alipay, null, $config, $app_auth_token);
        write_log("koubei pay result:" . var_export($result, 1), 'info');
        return $result;
    }
}

function koubei_request_agreement_url($config, $device_id = 0)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayUserAgreementPageSignRequest");
    $alipay = new AlipayUserAgreementPageSignRequest();
    $arr = array(
        'personal_product_code' => $config['mapi_agreement_product_code'],//ALIPAY_SIGN_WITHHOLDING_P
        'sign_scene' => $config['mapi_agreement_scene'],//INDUSTRY|CATERING
        'access_params' => array('channel' => 'ALIPAYAPP'),
    );
    $alipay->setNotifyUrl($config['mapi_isv_agreement_notify_url'] . '?device_id=' . $device_id);
    $alipay->setReturnUrl($config['mapi_isv_agreement_return_url'] . '?d=' . $device_id);
    $alipay->setBizContent(json_encode($arr));

    $ci =& get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient ();
    $aop->gatewayUrl = $config ['gatewayUrl'];
    $aop->appId = $config ['app_id'];
    $aop->rsaPrivateKey = $config['merchant_private_key'];
    $aop->alipayrsaPublicKey = $config['alipay_public_key'];
    $aop->signType = $config['sign_type'];
//    $aop->postCharset = $config['charset'];
    $aop->apiVersion = "1.0";
    $app_auth_token = get_auth_app_token_for_isv($config);
    $result = $aop->pageExecute($alipay, "GET", $app_auth_token);
    write_log("koubei qianyue response: " . var_export($result, true));
    return $result;
}

function koubei_agreemt_query($alipay_user_id, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayUserAgreementQueryRequest");
    $alipay = new AlipayUserAgreementQueryRequest();
    $arr = array(
        'personal_product_code' => $config['mapi_agreement_product_code'],//ALIPAY_SIGN_WITHHOLDING_P
        'sign_scene' => $config['mapi_agreement_scene'],
        'alipay_user_id' => $alipay_user_id,
    );
    $app_auth_token = get_auth_app_token_for_isv($config);
    $alipay->setBizContent(json_encode($arr));
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}


function koubei_check($arr, $config)
{
    unset($arr['device_id']);
    $ci =& get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient();

    $aop->alipayrsaPublicKey = $config['alipay_public_key'];
    $result = $aop->rsaCheckV1($arr, $config['alipay_public_key'], $config['sign_type']);
    return $result;
}

function koubei_request_execute_for_obj($request, $token = NULL, $config, $app_auth_token = NULL)
{

    $ci =& get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient ();
    $aop->gatewayUrl = $config ['gatewayUrl'];
    $aop->appId = $config ['app_id'];
    $aop->rsaPrivateKey = $config['merchant_private_key'];
    $aop->alipayrsaPublicKey = $config['alipay_public_key'];
    $aop->signType = $config['sign_type'];
//    $aop->postCharset = $config['charset'];
    $aop->apiVersion = "1.0";
    $result = $aop->execute($request, $token, $app_auth_token);
    write_log("koubei response: " . var_export($result, true));
    return $result;
}

/**
 * @param $data
 * @param $config
 * @return array|bool|mixed|SimpleXMLElement
 * $data = array(
 * 'out_trade_no'=>'171030286699511993',
 * 'refund_amount'=>'20.01',
 * 'refund_reason'=>'退款理由',
 * 'operator_id'=>'操作人',
 * 'out_request_no'=>'tuikuanpici'
 * );
 */
function koubei_refund_request($data, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayTradeRefundRequest");
    $alipay = new AlipayTradeRefundRequest();

    $arr = array(
        'out_trade_no' => $data['out_trade_no'],
        'refund_amount' => $data['refund_amount'],
        'refund_reason' => $data['refund_reason'],
        'operator_id' => $data['operator_id'],
        'out_request_no' => $data['pay_no'] . rand(100, 999)//如需部分退款，则此参数必传。
    );
    $alipay->setBizContent(json_encode($arr));
    $app_auth_token = get_auth_app_token_for_isv($config);
    $alipay->setBizContent(json_encode($arr));
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}

/**
 * @param $data
 * @param $config
 * @param $device_id
 * @param null $shop_id
 * @return array|bool|mixed|SimpleXMLElement
 * 网页支付
 */
function koubei_wap_pay_request($data, $config, $device_id, $shop_id = NULL)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AlipayTradeCreateRequest");
    $alipay = new AlipayTradeCreateRequest();
    $biz = array(
        'body' => $config['mapi_pay_subject'],
        'subject' => $config['mapi_pay_subject'],
        'out_trade_no' => $data['pay_no'],
        'total_amount' => $data['money'],
        'buyer_id' => $data['open_id']
//        'GoodsDetail' => $data['goods'],
    );
    foreach ($data['goods'] as $k => $v) {
        $data['goods'][$k]['goods_id'] = $v['goodsId'];
        unset($data['goods'][$k]['goodsId']);
        $data['goods'][$k]['goods_name'] = $v['goodsName'];
        unset($data['goods'][$k]['goodsName']);
        $data['goods'][$k]['goods_category'] = $v['goodsCategory'];
        unset($data['goods'][$k]['goodsCategory']);
    }
    $biz['goods_detail'] = $data['goods'];
    if ($shop_id) {
        $biz['alipay_store_id'] = $shop_id;
    }
    $alipay->setBizContent(json_encode($biz));
    $alipay->setNotifyUrl($config['mapi_isv_notify_url'] . '?device_id=' . $device_id);
    $alipay->setReturnUrl($config['mapi_isv_notify_url'] . '?device_id=' . $device_id);
    $app_auth_token = get_auth_app_token_for_isv($config);
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}

/**
 * @param $data
 * @param $config
 * 设备入驻蚂蚁盒子 返回支付宝的终端id -> alipay_terminal_id
 */
function ant_merchant_upload($data, $config)
{
    $ci =& get_instance();
    $ci->load->library("aop/request/AntMerchantExpandAutomatApplyUploadRequest");
    $alipay = new AntMerchantExpandAutomatApplyUploadRequest();
    $alipay->setBizContent(json_encode($data));
    return koubei_request_execute($alipay, null, $config, null);
}
function koubei_query_order($pay_no,$config){
    $ci =&get_instance();
    $ci->load->library("aop/request/AlipayTradeQueryRequest");
    $alipay = new AlipayTradeQueryRequest();
    $arr = array(
        'out_trade_no'=>$pay_no,
    );
    $alipay->setBizContent(json_encode($arr));
    $app_auth_token = get_auth_app_token_for_isv($config);
    return koubei_request_execute($alipay, null, $config, $app_auth_token);
}
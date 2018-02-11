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


require FCPATH . 'application/libraries/wechat/Lib_wx.php';

function get_wechat_token_execute($config) {

    if(!$config){
        $ci =&get_instance();
        $ci->config->load('platform_config', TRUE);
        $config = $ci->config->item('platform_config');
    }
    $jsApi_helper = new JsApi_helper ($config);
    $jsApi_helper->getToken();
    write_log('wechat_token'.var_export($jsApi_helper->access_token,1),'info');
    return $jsApi_helper->access_token;
}

function get_program_token_execute($config) {

    //替换成小程序的APPID
    $config['wechat_appid'] =  $config['wechat_program_appid'];
    $config['wechat_secret'] = $config['wechat_program_secret'];

    if(!$config){
        $ci =&get_instance();
        $ci->config->load('platform_config', TRUE);
        $config = $ci->config->item('platform_config');
    }
    $jsApi_helper = new JsApi_helper ($config);
    $jsApi_helper->getToken('program_token');
    write_log('program_token'.var_export($jsApi_helper->access_token,1),'info');
    return $jsApi_helper->access_token;
}

function send_program_tpl_msg_execute($data,$config) {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/wxopen/template/send?access_token='.get_program_token_execute($config);

    $result = HttpRequest::curl($url, $data, 1);
    write_log('send_program_tpl_msg_execute:'.var_export($result,1));
    return $result;
}

function send_wechat_tpl_msg_execute($data,$config) {
    $url = 'https://api.weixin.qq.com/cgi-bin/message/template/send?access_token='.get_wechat_token_execute($config);
    $result = HttpRequest::curl($url, $data, 1);
    write_log('send_wechat_tpl_msg_execute:'.var_export($result,1));
    return $result;
}
function get_user_by_openid_msg_execute($config,$openid) {
    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.get_wechat_token_execute($config).'&openid='.$openid.'&lang=zh_CN ';
    $result = HttpRequest::curl_get($url, null);
    write_log('get_user_by_openid_msg_execute:'.var_export($result,1));
    return $result;
}

function get_program_user_by_openid_msg_execute($config,$openid) {
    $url = 'https://api.weixin.qq.com/cgi-bin/user/info?access_token='.get_program_token_execute($config).'&openid='.$openid.'&lang=zh_CN ';
    $result = HttpRequest::curl_get($url, null);
    write_log('get_user_by_openid_msg_execute:'.var_export($result,1));
    return $result;
}

function getTokenByCode($auth_code,$config){
    $jsApi_helper = new JsApi_helper ($config);
    return $jsApi_helper->getTokenByCode($auth_code);
}

function getUserInfoByTokenAndOpenid($access_token, $openid,$config){
    $jsApi_helper = new JsApi_helper ($config);
    return $jsApi_helper->getUserInfoByTokenAndOpenid($access_token, $openid);
}

function get_open_id($config){
    $jsApi_helper = new JsApi_pub ($config);
    return $jsApi_helper->createOauthUrlForOpenid();
//    return $jsApi_helper;
}
function entrustweb($data,$config){
    $papay_entrustweb = new Papay_entrustweb ($config);
    $papay_entrustweb->setContract_code($data['contract_code']);
    $papay_entrustweb->setContract_display_account($data['contract_display_account']);
    $papay_entrustweb->setRequest_serial($data['request_serial']);
    return $papay_entrustweb->entrustweb();
}

function querycontract($contact_id,$config){
    $querycontract = new Papay_querycontract ($config);
    $querycontract->setContract_id($contact_id);
    return $querycontract->querycontract();
}


function get_querycontract_by_openid($openid,$config){
    $querycontract = new Papay_querycontract ($config);
    $querycontract->setOpen_id($openid);
    return $querycontract->querycontract();
}


function pay_apply($data,$config,$device_id){
    if(empty($data['contact_id'])){
        write_log("pay req 协议不存在:".var_export($data,1),'info');
        $ret = array(
            'return_code'=>'SUCCESS',
            'result_code'=>'FAIL',
            'err_code_des'=>'签约协议号不存在',
            'err_code'=>'CONTRACT_NOT_EXIST',
            );
        return $ret;
    }else{
        $papay_applyt = new Papay_apply ($config);
        $papay_applyt->setContract_id($data['contact_id']);
        $papay_applyt->setBody('魔盒CITYBOX购买商品');
        $papay_applyt->setTotal_fee($data['total_fee'] * 100);//分是单位
        $papay_applyt->setOut_trade_no($data['out_trade_no']);
        $papay_applyt->setAttach($device_id);
        write_log("pay req:".var_export($papay_applyt,1),'info');
        $result = $papay_applyt->pay_apply();
        write_log("pay result:".var_export($result,1),'info');
        return $result;
    }
}
function refund_wechat($data,$config){
    $refund = new Refund_pub ($config);
    $refund->setParameter('out_trade_no',$data['out_trade_no']);
    $refund->setParameter('out_refund_no',$data['out_refund_no']);
    $refund->setParameter('total_fee',$data['total_fee']*100);
    $refund->setParameter('refund_fee',$data['refund_fee']*100);
    $refund->setParameter('op_user_id',$data['op_user_id']);
    write_log("refund req:".var_export($refund,1),'info');
    $result = $refund->getResult();
    write_log("refund result:".var_export($result,1),'info');
    return $result;
}

function wechat_notify($config){
    $ret = false;
    //使用通用通知接口
    $notify = new Notify_pub($config);
    //存储微信的回调
    $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
    $notify->saveData($xml);
    $isSuccSign = $notify->checkSign();
    if($isSuccSign == FALSE){
        $notify->setReturnParameter("return_code","FAIL");//返回状态码
        $notify->setReturnParameter("return_msg","签名失败");//返回信息
    }else{
        $notify->setReturnParameter("return_code","SUCCESS");//设置返回码
        $ret = $notify;
    }
    $returnXml = $notify->returnXml();
    echo $returnXml;
    return $ret;
}

function pay_wap($data,$config){
    $unifiedOrder = new UnifiedOrder_pub($config);
    $unifiedOrder->setParameter("openid", $data['open_id']); //openId
    $unifiedOrder->setParameter("body", '魔盒CITYBOX购买商品'); //商品描述
    $unifiedOrder->setParameter("detail", '魔盒CITYBOX购买商品'); //商品描述
    $unifiedOrder->setParameter("out_trade_no", $data['pay_no']); //商户订单号
    $unifiedOrder->setParameter("total_fee", $data['money'] * 100); //总金额，单位分
    $unifiedOrder->setParameter("notify_url", $config['pay_notify_url']); //通知地址
    $unifiedOrder->setParameter("trade_type", "JSAPI"); //交易类型
    //非必填参数，商户可根据实际情况选填
    if(isset($data['attach']) && !empty($data['attach'])){
        $unifiedOrder->setParameter("attach", $data['attach']); //附加数据
    }
    $prepay_id = $unifiedOrder->getPrepayId();
    write_log('wechat pay'.var_export($unifiedOrder,1),'info');
    //=========步骤3：使用jsapi调起支付============
    $jsApi = new JsApi_pub($config);
    $jsApi->setPrepayId($prepay_id);
    $jsApiParameters = $jsApi->getParameters();
    return $jsApiParameters;
}

/**
 * 创建微信场景二维码，永久
 * @param $data = {"action_name": "QR_LIMIT_STR_SCENE", "action_info": {"scene": {"scene_str": "test"}}}
 * @return mixed
 */
function create_scene_qr($data){
    $url = 'https://api.weixin.qq.com/cgi-bin/qrcode/create?access_token='.get_wechat_token_execute();
    $result = HttpRequest::curl($url, $data, 1);
    write_log('create_scene_qr:'.var_export($result,1));
    $result = json_decode($result,true);
    $ret['qr_img'] = 'https://mp.weixin.qq.com/cgi-bin/showqrcode?ticket='.$result['ticket'];
    return $result;
}


function get_wx_api_sign($config,$noncestr, $timestamp, $url){

    $jsApi_helper = new JsApi_helper ($config);

    return $jsApi_helper->signature($noncestr, $timestamp, $url);
}

function get_wx_jscode2session($code,$config){
    $jscode2session = new Program_jscode2session ($config);
    $result = $jscode2session->jscode2session($code);
    write_log("jscode2session result:".var_export($result,1));
    if($result){
        return json_decode($result,true);
    }
    return false;
}

function get_program_entrust($data,$config){
    //替换APPID未小程序的APPID
    $config['wechat_appid'] =  $config['wechat_program_appid'];
    $config['wechat_secret'] = $config['wechat_program_secret'];

    $papay_entrustweb = new Papay_entrustweb ($config);
    $papay_entrustweb->setContract_code($data['contract_code']);
    $papay_entrustweb->setContract_display_account($data['contract_display_account']);
    $papay_entrustweb->setRequest_serial($data['request_serial']);
    return $papay_entrustweb->entrustweb_program();
}

function query_wx_order($out_trade_no,$config){
    $qurey = new OrderQuery_pub($config);
    $qurey->setParameter('out_trade_no',$out_trade_no);
    write_log("qurey order req:".var_export($qurey,1),'info');
    $result = $qurey->getResult();
    write_log("qurey order result:".var_export($result,1),'info');
    return $result;
}

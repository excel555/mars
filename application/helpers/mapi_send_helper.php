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


require FCPATH.'application/libraries/aop/MapiClient.php';

function mapiClient_request_execute($request, $token = NULL,$config,$device_id = 0) {
    $aop = new MapiClient ();
    $aop->gatewayUrl = $config ['mapi_gatewayUrl'];
    $aop->partner = $config ['mapi_partner'];
    $aop->sign_type=$config['mapi_sign_type'];
    $aop->safe_key=$config['mapi_safe_key'];
    $aop->notify_url = $config['mapi_notify_url']."?device_id={$device_id}";
    $aop->return_url = $config['mapi_return_url']."?device_id={$device_id}";
    $result = $aop->execute ( $request, $token );
    write_log("response: ".var_export($result,true));
    return $result;
}

function mapiClient_request_get_agreement_url($config,$device_id=0)
{
    $aop = new MapiClient ();
    $aop->gatewayUrl = $config ['mapi_gatewayUrl'];
    $aop->partner = $config ['mapi_partner'];
    $aop->sign_type = $config['mapi_sign_type'];
    $aop->safe_key = $config['mapi_safe_key'];
    $aop->return_url = $config['mapi_agreement_return_url'].'?device_id='.$device_id;
    $aop->notify_url = $config['mapi_agreement_notify_url'].'?device_id='.$device_id;
    require_once FCPATH . 'application/libraries/aop/request/AlipayDutCustomerAgreementPageSignRequest.php';
    $request = new AlipayDutCustomerAgreementPageSignRequest();
    $request->setProductCode($config['mapi_agreement_product_code']);
    $request->setScene($config['mapi_agreement_scene']);
    $request->setAccessInfo(json_encode(array("channel" => "ALIPAYAPP")));
//    require APPPATH.'config/zmxy.php';
    if(isset($config['zmxy_merchant_id']) && !empty($config['zmxy_merchant_id']) && isset($config['zmxy_app_id']) && !empty($config['zmxy_app_id'])){
        $ZmAuthInfo = json_encode(array(
            'buckleAppId'=>$config['zmxy_app_id'],
            'buckleMerchantId'=>$config['zmxy_merchant_id']
        ));
        $request->setZmAuthInfo($ZmAuthInfo);
    }
    $result = $aop->agreement_page_sign($request);
//    write_log("response url: " . var_export($result, true));
    return $result;

}
function mapi_agreement_query_request($alipay_user_id,$config){
    require_once FCPATH . 'application/libraries/aop/request/AlipayDutCustomerAgreementQueryRequest.php';
    $AlipayDutCustomerAgreementQueryRequest = new AlipayDutCustomerAgreementQueryRequest ();
    $AlipayDutCustomerAgreementQueryRequest->setProductCode($config['mapi_agreement_product_code']);
    $AlipayDutCustomerAgreementQueryRequest->setAlipayUserId($alipay_user_id);
    $AlipayDutCustomerAgreementQueryRequest->setScene($config['mapi_agreement_scene']);
    return mapiClient_request_execute($AlipayDutCustomerAgreementQueryRequest,null,$config);
}

function send_alipay_createandpay_request($order,$config){

    if(empty($order['agreement_no'])){
        write_log("pay 协议信息不存在 :".var_export($order,1),'info');
        $rs = array (
            'is_success' => 'T',
            'request' =>'',
            'response' =>
                array (
                    'alipay' =>
                        array (
                            'detail_error_code' => 'AGREEMENT_NOT_EXIST',
                            'detail_error_des' => '协议信息不存在',
                            'display_message' => '协议信息不存在',
                            'out_trade_no' => $order['pay_no'],
                            'result_code' => 'ORDER_FAIL',
                        ),
                ),
            'sign' => 'xxx',
            'sign_type' => 'MD5',
        );
        return $rs;
    }else{
        require_once FCPATH . 'application/libraries/aop/request/AlipayAcquireCreateandpayRequest.php';
        $alipay = new AlipayAcquireCreateandpayRequest ();
        $alipay->setOutTradeNo($order['pay_no']);
        $alipay->setSubject($config['mapi_pay_subject']);
        $alipay->setProductCode($config['mapi_pay_product_code']);
        $alipay->setTotalFee($order['money']);
//        $alipay->setExtendParams(json_encode(array('device_id'=>$order['device_id'])));//额外参数
        if($config['pay_sell_id']){
            $alipay->setSellerId($config['seller_id']);
        }
        //$alipay->setRoyaltyType('ROYALTY');
//        $royalty_params = array(
//            array(
//                'serialNo'=>'1',
//                'transOut'=>$config['mapi_partner'],
//                'transIn'=>$config['mapi_seller_id'],
//                'amount'=>$order['money'],
//                'desc'=>'商户分账,金额:'.$order['money']
//            )
//        );
        //$alipay->setRoyaltyParameters(json_encode($royalty_params));
        $alipay->setAgreementInfo(json_encode(array("agreement_no"=>$order['agreement_no'])));
        $alipay->setGoodsDetail(json_encode($order['goods']));
        write_log("pay req:".var_export($alipay,1),'info');
        $result = mapiClient_request_execute($alipay,null,$config,$order['device_id']);
        write_log("pay result:".var_export($result,1),'info');
        return $result;
    }

}



function verifyNotify($config)
{
    if (empty($_POST)) {//判断POST来的数组是否为空
        return false;
    } else {
        //生成签名结果
        $isSign = getSignVeryfy($_POST, $_POST["sign"],$config);
        write_log(var_export($_POST,1)." getSignVeryfy: ".$isSign,'info');
        //获取支付宝远程服务器ATN结果（验证是否是支付宝发来的消息）
        $responseTxt = 'false';
        if (!empty($_POST["notify_id"])) {
            $responseTxt = getResponse($_POST["notify_id"],$config);
        }
        write_log(" notify SignVeryfy response: ".$responseTxt,'info');
        //验证
        //$responsetTxt的结果不是true，与服务器设置问题、合作身份者ID、notify_id一分钟失效有关
        //isSign的结果不是true，与安全校验码、请求时的参数格式（如：带自定义参数等）、编码格式有关
        if (preg_match("/true$/i", $responseTxt) && $isSign) {
            return true;
        } else {
            return false;
        }
    }
}


function getResponse($notify_id,$config)
{
    $partner = trim($config ['mapi_partner']);
    $veryfy_url = 'https://mapi.alipay.com/gateway.do?service=notify_verify&';
    $veryfy_url = $veryfy_url . "partner=" . $partner . "&notify_id=" . $notify_id;
    $responseTxt = getHttpResponseGET($veryfy_url);
    write_log(" notify SignVeryfy response url : ".$veryfy_url,'info');
    return $responseTxt;
}

function getHttpResponseGET($url) {
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, 0 ); // 过滤HTTP头
    curl_setopt($curl,CURLOPT_RETURNTRANSFER, 1);// 显示输出结果
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);//SSL证书认证
    $responseText = curl_exec($curl);
    curl_close($curl);
    return $responseText;
}

/**
 * 获取返回时的签名验证结果
 * @param $para_temp 通知返回来的参数数组
 * @param $sign 返回的签名结果
 * @return 签名验证结果
 */
function getSignVeryfy($para_temp, $sign,$config)
{
    unset($para_temp['device_id']);
    //除去待签名参数数组中的空值和签名参数
    $para_filter = paraFilter($para_temp);
    //对待签名参数数组排序
    $para_sort = argSort($para_filter);
    //把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
    $prestr = createLinkstring($para_sort);
    $isSgin = false;
    $isSgin = md5Verify($prestr, $sign, $config['mapi_safe_key']);
    return $isSgin;

}


/**
 * 签名字符串
 * @param $prestr 需要签名的字符串
 * @param $key 私钥
 * return 签名结果
 */
function md5Sign($prestr, $key) {
    $prestr = $prestr . $key;
    return md5($prestr);
}

/**
 * 验证签名
 * @param $prestr 需要签名的字符串
 * @param $sign 签名结果
 * @param $key 私钥
 * return 签名结果
 */
function md5Verify($prestr, $sign, $key) {
    $prestr = $prestr . $key;
    $mysgin = md5($prestr);
    if($mysgin == $sign) {
        return true;
    }
    else {
        return false;
    }
}

/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilter($para) {
    $para_filter = array();
    while (list ($key, $val) = each ($para)) {
        if($key == "sign" || $key == "sign_type" || $val == "")continue;
        else	$para_filter[$key] = $para[$key];
    }
    return $para_filter;
}
/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSort($para) {
    ksort($para);
    reset($para);
    return $para;
}

function createLinkstring($para) {
    $arg  = "";
    while (list ($key, $val) = each ($para)) {
        $arg.=$key."=".$val."&";
    }
    //去掉最后一个&字符
    $arg = substr($arg,0,count($arg)-2);

    //如果存在转义字符，那么去掉转义
    if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}

    return $arg;
}
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



function aopclient_request_execute($request, $token = NULL,$config) {

    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient ();
    $aop->gatewayUrl = $config ['gatewayUrl'];
    $aop->appId = $config ['app_id'];
    $aop->rsaPrivateKey=$config['merchant_private_key'];
    $aop->alipayrsaPublicKey=$config['alipay_public_key'];
    $aop->signType=$config['sign_type'];
//    $aop->postCharset = $config['charset'];
    $aop->apiVersion = "1.0";
    $result = $aop->execute ( $request, $token );
    write_log("response: ".var_export($result,true));
    return $result;
}

function pay_wap_alipay_request($order,$config,$device_id){

    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $ci->load->library("aop/request/AlipayTradeWapPayRequest");
    $aop = new AopClient ();
    $aop->gatewayUrl = $config ['gatewayUrl'];
    $aop->appId = $config ['app_id'];
    $aop->rsaPrivateKey=$config['merchant_private_key'];
    $aop->alipayrsaPublicKey=$config['alipay_public_key'];
    $aop->signType=$config['sign_type'];
    $aop->apiVersion = '1.0';
    $aop->postCharset='UTF-8';
    $aop->format='json';
    $request = new AlipayTradeWapPayRequest ();
    $biz = array(
        'body'=>"大朗科技购买商品",
        'subject'=>"大朗科技购买商品",
        'out_trade_no'=>$order['pay_no'],
        'timeout_express'=>'90m',
        'total_amount'=>$order['money'],
        'product_code'=>'QUICK_WAP_PAY',
    );
    $request->setBizContent(json_encode($biz));
    $request->setNotifyUrl($config['notify_url']."?device_id=".$device_id);
    if(substr($order['pay_no'],0,1) == "M"){
        $request->setReturnUrl($config['recharge_retrun_url']);
    }else{
        $request->setReturnUrl($config['retrun_url'].$order['order_name']);
    }
    $result = $aop->pageExecute ( $request);
    return $result;
}

function aopclient_send_ack_msg($toUserId,$config)
{
    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $as = new AopClient();
    $response_xml = "<XML><ToUserId><![CDATA[" . $toUserId . "]]></ToUserId><AppId><![CDATA[" . $config ['app_id'] . "]]></AppId><CreateTime>" . time () . "</CreateTime><MsgType><![CDATA[ack]]></MsgType></XML>";
    $mysign=$as->alonersaSign($response_xml,$config['merchant_private_key'],$config['sign_type']);
    $return_xml = "<?xml version=\"1.0\" encoding=\"".$config['charset']."\"?><alipay><response>".$response_xml."</response><sign>".$mysign."</sign><sign_type>".$config['sign_type']."</sign_type></alipay>";
    write_log ( "send response_xml: " . $return_xml );
    return $return_xml;
}

function verifygw($is_sign_success,$biz_content,$config)
{
    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $xml = simplexml_load_string($biz_content);
    // print_r($xml);
    $EventType = ( string )$xml->EventType;
    // echo $EventType;
    if ($EventType == "verifygw") {
        $as = new AopClient();
        $as->rsaPrivateKey = $config['merchant_private_key'];
        if ($is_sign_success) {
            $response_xml = "<success>true</success><biz_content>" . $config ['merchant_public_key'] . "</biz_content>";
        } else {
            $response_xml = "<success>false</success><error_code>VERIFY_FAILED</error_code><biz_content>" . $config ['merchant_public_key_file'] . "</biz_content>";
        }

        $mysign = $as->alonersaSign($response_xml, $config['merchant_private_key'], $config['sign_type']);
        $return_xml = "<?xml version=\"1.0\" encoding=\"" . $config['charset'] . "\"?><alipay><response>" . $response_xml . "</response><sign>" . $mysign . "</sign><sign_type>" . $config['sign_type'] . "</sign_type></alipay>";
        write_log("response_xml: " . $return_xml);
        echo $return_xml;
        exit ();
    }
}
function rsaCheck($req,$alipay_public_key,$sign_type){
    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $as = new AopClient();
    $as->alipayrsaPublicKey = $alipay_public_key;
   return $as->rsaCheckV2($req, $alipay_public_key, $sign_type);
}


function alipay_check($arr,$config){
    $ci =&get_instance();
    $ci->load->library("aop/AopClient");
    $aop = new AopClient();

    $aop->alipayrsaPublicKey = $config['alipay_public_key'];
    $result = $aop->rsaCheckV1($arr, $config['alipay_public_key'], $config['sign_type']);
    return $result;
}

function alipay_query_wap_order($pay_no,$config){
    $ci =&get_instance();
    $ci->load->library("aop/request/AlipayTradeQueryRequest");
    $alipay = new AlipayTradeQueryRequest();
    $arr = array(
        'out_trade_no'=>$pay_no,
    );
    $alipay->setBizContent(json_encode($arr));
    return aopclient_request_execute($alipay,null,$config);
}
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


require FCPATH . 'application/libraries/zmxy/ZmopClient.php';

function zmopClient_request_execute($request, $token = NULL) {

    $client = return_zm_obj($config);
    $result = $client->execute ( $request, $token );

    write_log("response: ".var_export($result,true));
    return $result;
}

function generate_page_redirect_invoke_url($request){

    $client = return_zm_obj($config);
    return $client->generatePageRedirectInvokeUrl($request);
}

function return_zm_obj($config){
    $client = new ZmopClient();
    $client->gatewayUrl = $config['zmxy_gatewayUrl'];//$ci->config->item('gatewayUrl','zmxy');
    $client->appId = $config['zmxy_app_id'];//$ci->config->item('app_id','zmxy');
    $client->privateKey= $config['zmxy_merchant_private_key'];//$ci->config->item('merchant_private_key','zmxy');
    $client->zhiMaPublicKey= $config['zmxy_zm_public_key'];//$ci->config->item('zm_public_key','zmxy');
    $client->apiVersion = "1.0";
    $client -> charset = $config['zmxy_charset'];//$ci->config->item('charset','zmxy');
    return $client;
}

function decrypt_and_verify_sign($params,$sign){
    $client = return_zm_obj($config);
    return $client->decryptAndVerifySign ( $params, $sign );
}

/**
 * 批量反馈
 * $data =array('records'=>2,'file'=>'a.json')
 */
function send_batch_feedback($data,$config){
    $ci =&get_instance();
    $ci->load->library("zmxy/request/ZhimaDataBatchFeedbackRequest");
    $ci->load->config('zmxy',true);
    $type_id =  $ci->config->item('type_id','zmxy');
    $request = new ZhimaDataBatchFeedbackRequest();
    $request->setChannel("apppc");
    $request->setPlatform("zmop");
    $request->setFileType("json_data");// 必要参数
    $request->setFileCharset("UTF-8");// 必要参数
    $request->setRecords($data['records']);// 必要参数
    $request->setColumns("biz_date,user_credentials_type,user_credentials_no,user_name,order_no,order_start_date,order_status,merchant_name,bill_type,bill_desc,bill_amt,bill_last_date,bill_payoff_date,memo");// 必要参数
    $request->setPrimaryKeyColumns("order_no");// 必要参数
    $request->setFileDescription("芝麻信用批量反馈");//
    $request->setTypeId($type_id);// 必要参数
    $request->setBizExtParams("{\"extparam1\":\"value1\"}");//
    $request->setFile($data['file']);// 必要参数
    $client = return_zm_obj($config);
    $result = $client->execute ( $request );
    write_log("response: ".var_export($result,true));
    return $result;
}

/**
 * @param $data
 * @return bool|mixed
 * 单条记录的反馈
 *
 *
$data = array(
    'biz_date'=>date("Y-m-d"),//产生本条数据的实际日期。用于提醒用户数据的时效 不可为空 格式：YYYY-MM-DD;"
    'user_credentials_type'=>'Y',//芝麻信用open_id类型
    'user_credentials_no'=>'268803417658126154860050642',//芝麻信用open_id
    'user_name'=>'', //芝麻信用OpenID"时,可为空；
    'order_no'=>'17060742682107',//1）格式：YYYY-MM-DD hh:mm:ss;
    'order_start_date'=>'2017-05-10 15:02:00',
    'order_status'=>'0',//0-待付；//1-到期未付；2-结清；
    'merchant_name'=>'魔盒CITYBOX-上海鲜动信息技术',//填写真实提供服务的商户名称。展示给用户看的名称
    'bill_type'=>'100',//100-服务费
    'bill_desc'=>'魔盒订单',//不会展示给用户
    'bill_amt'=>'0.01',
    'bill_last_date'=>'2017-05-23',//格式：YYYY-MM-DD;
    'bill_payoff_date'=>'',//未支付情况下为空
    'memo'=>'for test',
    );
 */
function send_single_feedback($data,$config){
    if($data && $config['zmxy_type_id'] && $config['zmxy_app_id'] && $config['zmxy_merchant_id']){
        $ci =&get_instance();
        $ci->load->library("zmxy/request/ZhimaDataSingleFeedbackRequest");
        $request = new ZhimaDataSingleFeedbackRequest();

        $type_id =$config['zmxy_type_id'];//$ci->config->item('type_id','zmxy');
        $request->setBizExtParams('{"extparam1":"value1"}');
        $request->setData(json_encode($data));
        $request->setIdentity('order_no');
        $request->setTypeId($type_id);
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $client = return_zm_obj($config);
        $result = $client->execute ( $request );
        write_log("zmxy response: ".var_export($result,true)."zmxy req".var_export($request,1));
        write_log(json_encode($data),'feedback');
        return $result;
    }else{
        return '反馈数据为空';
    }
}

/**
 * 生成芝麻信用反馈的数据格式
 * @param $order_name 订单号
 * @param $feedback_status 反馈的订单状态
 * @param string $pay_date 支付成功结清时间
 * @return array
 */
function general_zmxy_order_data($order_name,$feedback_status,$pay_date =''){
    $ci =&get_instance();
    $ci->load->model('order_model');
    $order = $ci->order_model->get_order_by_name($order_name);
    if(!$order || $order['refer'] !='alipay' || !$order['uid']){
        return FALSE;
    }
    $ci->load->model('user_model');
    $user = $ci->user_model->get_user_info_by_id($order['uid']);
    if(!isset($user['open_id']))
    {
        return FALSE;
    }
    $zm_open_id = $user['open_id'];//modify 芝麻信用ID修改为支付宝的userID
    if($feedback_status == 0){
        $pay_date = '';//未支付情况下为空
    }
    //最后支付时间为创建订单的13天
    $last_date = date('Y-m-d',strtotime("+13day",strtotime($order['order_time'])));
    if($feedback_status == 0 &&  $last_date < date('Y-m-d')){
        $feedback_status = 1;//到期未付
    }
    $data = array(
        'biz_date'=>date("Y-m-d"),//产生本条数据的实际日期。用于提醒用户数据的时效 不可为空 格式：YYYY-MM-DD;"
        'user_credentials_type'=>'W',//Y 芝麻信用open_id类型 W 支付宝ID
        'user_credentials_no'=>$zm_open_id,//芝麻信用open_id
        'user_name'=>'', //芝麻信用OpenID"时,可为空；
        'order_no'=>$order['order_name'],//1）格式：YYYY-MM-DD hh:mm:ss;
        'order_start_date'=>$order['order_time'],
        'order_status'=>$feedback_status,//0-待付；//1-到期未付；2-结清；
        'merchant_name'=>'魔盒CITYBOX-上海鲜动信息技术',//填写真实提供服务的商户名称。展示给用户看的名称
        'bill_type'=>'100',//100-服务费
        'bill_desc'=>'魔盒订单',//不会展示给用户
        'bill_amt'=>$order['money'],
        'bill_last_date'=>$last_date,//格式：YYYY-MM-DD;
        'bill_payoff_date'=>$pay_date,//未支付情况下为空 1）格式：YYYY-MM-DD hh:mm:ss;
        'memo'=>'魔盒CITYBOX',
    );
    return $data;
}


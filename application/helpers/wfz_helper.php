<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */


/**
 * 新订单创建
 * @param $msg_body
 * @param $type
 * @return mixed|string
 */
function wfz_new_order_execute($msg_body,$oid)
{
    return send_wfz_request($msg_body,1,$oid);
}

/**
 * 订单支付成功
 * @param $msg_body
 * @param $type
 * @return mixed|string
 */
function wfz_order_pay_execute($msg_body,$oid)
{
    return send_wfz_request($msg_body,2,$oid);
}
/**
 * 订单申请退款
 * @param $msg_body
 * @param $type
 * @return mixed|string
 */
function wfz_order_refund_execute($msg_body,$oid)
{
    return send_wfz_request($msg_body,7,$oid);
}

/**
 * 订单退款同意
 * @param $msg_body
 * @param $type
 * @return mixed|string
 */
function wfz_order_refund_pass_execute($msg_body,$oid)
{
    return send_wfz_request($msg_body,8,$oid);
}

/**
 * 订单退款拒绝
 * @param $msg_body
 * @param $type
 * @return mixed|string
 */
function wfz_order_refund_against_execute($msg_body,$oid)
{
    return send_wfz_request($msg_body,9,$oid);
}

/**
 *
 * @param $mssage_body
 * @param $type
 * @return mixed|string
 */
function send_wfz_request($mssage_body, $type,$oid)
{
    $ci = get_instance();
    $ci->load->helper('http_request');
    $app_id = '801743';
    $sid = WFZ_SHOP_ID;
    $url = "http://waimai.wufangzhai.com/waimai/order/push/common";
    $url = "http://waimai.wufangzhai.com/waimai/order/push/test";
    $req = array(
        'type'=> $type,
        'appId'=>$app_id,
        'sid'=>$sid, //商户编号
        'oid'=>$oid,//订单号
        'message'=>$mssage_body,
        'timestamp'=>time()
    );
    $ci->load->model("request_msg_log_model");
    $data = array(
        'box_no'=>$oid,//订单号
        'req_time'=>date("Y-m-d H:i:s"),
        'req_body'=>json_encode($req),
        'req_type'=>'wfz_order_pos',
        'response'=>'',
    );
    $insert_id = $ci->request_msg_log_model->insert_log($data);
    $result = HttpRequest::curl($url, json_encode($req), 1);
    $ci->request_msg_log_model->update_log($result,$insert_id);
    return $result;
}

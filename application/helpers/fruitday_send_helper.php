<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

/**
 * 获取用户信息
 * @param $req_data
 * @return mixed
 *  $request_data = array('source'=>'wap/app','uid'=>'')
 */

function get_fruit_user_request_execute($request_data)
{
    $rs = send_fruitday_request("user.getUserInfo", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}

/**
 * @param $request_data
 * @return mixed|null
 */
function get_fruit_user_by_code_request_execute($request_data)
{
    $rs = send_fruitday_request("user.getUidByToken", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}


function recharge_request_execute($request_data)
{
    $rs = send_fruitday_request("user.addTrade", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}

function cut_money_request_execute($request_data)
{
    $rs = send_fruitday_request("user.cutMoney", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}

/**
 * @param $request_data = array('source'=>'wap','money'=>'','order_name'=>'','uid'=>)
 * @return mixed|null
 */
function refund_money_request_execute($request_data)
{
    $rs = send_fruitday_request("user.addMoney", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}


function get_fruitday_pay_url($url){
    $rs = HttpRequest::curl($url);
    if($rs){
        return json_decode($rs, TRUE);
    }
    return NULL;
}

function send_fruitday_request($type, $req_data)
{
    $ci = get_instance();
    $ci->load->helper('http_request');
    $ci->load->config('fruitday', TRUE);
    $secret = $ci->config->item('secret', 'fruitday');
    $url = $ci->config->item('fruitday_host', 'fruitday');

    $req_data['service'] = $type;
    $req_data['version'] = '1.0';
    $req_data['timestamp'] = time();
    $req_data['sign'] = fruitday_sign($req_data,$secret);

    write_log("fruitday req: " . var_export($req_data, true));
    $result = HttpRequest::curl($url, $req_data);
    if(!$result){
        write_log("请求天天果园的接口异常，".var_export($req_data,1),'crit');
    }
    write_log("fruitday response: " . var_export($result, true));
    return $result;
}

function fruitday_sign($params,$secret) {
    ksort($params);
    $query = '';
    foreach($params as $k=>$v) {
        $query .= $k . '=' . $v . '&';
    }
    $sign = md5(substr(md5($query.$secret), 0,-1).'C');
    return $sign;
}
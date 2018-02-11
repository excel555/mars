<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

/**
 * @param $data = array('xxx'=>'xx');
 * api 参数必填
 * @return bool
 */

function get_gat_access_token(){
    $ci = get_instance();
    $ci->load->driver('cache');

    $data = $ci->cache->redis->get('citybox_gat_access_token');
    if(!$data){
        $ci->load->config('guanaitong', TRUE);
        $appid = $ci->config->item('app_id', 'guanaitong');
        $secret = $ci->config->item('secret', 'guanaitong');
        $gateway_url = $ci->config->item('gateway', 'guanaitong');

        $ci->load->library('guanaitong/request/CommonRequest');
        $request = new CommonRequest();
        $request->setParameter('grant_type', 'client_credential');
        $request->setApi('token/create');

        $ci->load->library("guanaitong/GuanaitongClient");
        $client = new GuanaitongClient();
        $client->appId = $appid;
        $client->appsecret = $secret;
        $client->gatewayUrl = $gateway_url;
        $result = $client->execute($request);
        if ($result && $result['code'] == 0 && !empty($result['data']['access_token'])) {
            $data = $result['data']['access_token'];
            $ci->cache->redis->save('citybox_gat_access_token', $data, 55*60);
        }
    }
    return $data;
}


function send_gat_common_request($data,$api)
{
    $ci = get_instance();
    $ci->load->config('guanaitong', TRUE);
    $appid = $ci->config->item('app_id', 'guanaitong');
    $secret = $ci->config->item('secret', 'guanaitong');
    $gateway_url = $ci->config->item('gateway', 'guanaitong');

    $ci->load->library('guanaitong/request/CommonRequest');
    $request = new CommonRequest();
    $access_token = get_gat_access_token();
    $request->setParameter('access_token',$access_token);
    foreach ($data as $k => $v){
        $request->setParameter($k,$v);
    }

    $request->setApi($api);

    $ci->load->library("guanaitong/GuanaitongClient");
    $client = new GuanaitongClient();
    $client->appId = $appid;
    $client->appsecret = $secret;
    $client->gatewayUrl = $gateway_url;
    write_log("guanaitong request: ".var_export($request,true));
    $result = $client->execute($request);
    write_log("guanaitong response: ".var_export($result,true));
    return $result;
}

//发起支付
function send_gat_pay_request($pay_info, $order){
    $ci = get_instance();
    $ci->load->config('guanaitong', TRUE);
    $notify_url = $ci->config->item('notify_url', 'guanaitong');
    $trade_info = array();
    foreach($order['gat_data'] as $k=>$v){
        $product_name = str_replace('+', '加', $v['product_name']);
        $product_name = str_replace('%','',$product_name);
        $trade_info[$k]['goods_id']       = $v['product_id'];
        $trade_info[$k]['goods_name']     = $product_name;
        $trade_info[$k]['goods_category'] = str_replace('+', '加', $v['class_name']);
        $trade_info[$k]['quantity']       = $v['qty'];
        $trade_info[$k]['price']          = $v['price'];
    }

    $data = array(
        'outer_trade_no'=>$pay_info['pay_no'],
        'pay_code'=>$pay_info['pay_code'],//付款码
        //'notify_url'=>'',
        'total_amount'=>$pay_info['money'],//sprintf("%.2f", $order['good_money']),//这个填商品金额
        'pay_amount'=>$pay_info['money'],//支付金额
        'subject'=>$pay_info['order_name'],
        'product_category'=>$pay_info['pay_no'],
        'trade_info'=> json_encode($trade_info, JSON_UNESCAPED_UNICODE),
    );
    return send_gat_common_request($data,'pay/doPay');
}

//发起退款
function send_gat_refund_request($refund){
    $data = array(
        'outer_trade_no' => $refund['outer_trade_no'],
        'reason'         => '魔盒退款',
        'amount'         => $refund['amount'],
        'refund_trade_no'=> $refund['refund_trade_no'],
    );
    return send_gat_common_request($data,'pay/refund');
}






/**
 * @param $auth_code
 * 根据code获取用户信息
 */
function send_gat_user_request($auth_code){
    $access_token = get_gat_access_token();
    $ci = get_instance();
    $ci->load->config('guanaitong', TRUE);
    $appid       = $ci->config->item('app_id', 'guanaitong');
    $secret      = $ci->config->item('secret', 'guanaitong');
    $gateway_url = $ci->config->item('gateway', 'guanaitong');

    $ci->load->library('guanaitong/request/CommonRequest');
    $request = new CommonRequest();
    $request->setParameter('access_token', $access_token);
    $request->setParameter('auth_code', $auth_code);
    $request->setApi('getBizInfo');

    $ci->load->library("guanaitong/GuanaitongClient");
    $client = new GuanaitongClient();
    $client->appId = $appid;
    $client->appsecret = $secret;
    $client->gatewayUrl = $gateway_url;
    $result = $client->execute($request);
    write_log("gat发送数据: ".var_export($request,true));//返回错误信息
    write_log("gat返回数据: ".var_export($result,true));//返回错误信息
    if($result && $result['code'] == 0 ){
        if(is_string($result['data'])){
            $data = json_decode($result['data'], true);
            return $data['open_id']?$data['open_id']:0;
        }elseif(is_array($result['data'])){
            return $result['data']['open_id']?$result['data']['open_id']:0;
        }
        return 0;
    }
    return 0;
}

/*
 * @desc 发送关爱通消息
 * @param $biz_type 业务处理结果类型，取值为1或2，1：成功；2：失败
 * @param $msg_type 消息类型，取值有：开门1 关门2 支付3 退款4
 * */
function send_gat_notify_request($data){
    $message = array(
        'msg_title'=>$data['keyword2'],
        'msg_time' =>$data['keyword1'],
        'content'  =>$data['first'],
        'msg_tail' =>$data['remark']
    );
    $mess_data = array(
        'user_openid'=>$data['buyer_id'],
        'msg_type'=>$data['msg_type'],
        'biz_type'=>$data['biz_type'],
        'message'=>json_encode($message, JSON_UNESCAPED_UNICODE)
    );
    if($data['attach']){
        $mess_data['attach'] = json_encode($data['attach'], JSON_UNESCAPED_UNICODE);
    }
    return send_gat_common_request($mess_data,'notify');
}


/*
 * @desc 查询关爱通订单支付情况
 *
 * */

function check_order_pay($pay_no){

    $data = array(
        'outer_trade_no'=>$pay_no
    );
    return send_gat_common_request($data,'pay/status/getorder');
}
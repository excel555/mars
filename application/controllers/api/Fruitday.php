<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Fruitday Controller
 * 天天果园相关
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */
class Fruitday extends REST_Controller
{
    const MIN_BLANCE = 10;
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("user_model");
        $this->load->helper("utils");
        $this->load->helper('fruitday_send');
    }


    /**
     * 获取用户的信息
     */
    public function get_info_get(){
        $account = $this->get_curr_user();
        $info = get_fruit_user_request_execute(array('uid'=>$account['open_id'],'source'=>'wap'));
        if($info['code']==200){
            $account['fruitday_money'] = $info['data']['money'];
            $this->update_curr_user($account);
        }else {
            $this->send_error_response("没有查到用户数据");
        }
        $this->send_ok_response($account);
    }

    /**
     * 充值
     */
    public function recharge_post(){
        $type = $this->post('type');
        $agent = $this->post('agent');
        $money = $this->post('money');
        $this->check_null_and_send_error($type, "缺少支付类型参数");
        $this->check_null_and_send_error($money, "缺少充值金额参数");
        $account = $this->get_curr_user();

        $this->config->load('fruitday', TRUE);
        $return_url = $this->config->item("recharge_return_url","fruitday");

        $req = array(
            'source'=>'wap',
            'uid'=>$account['open_id'],
            'money'=>$money,
            'payment_id'=>$type,
            'return_url'=>$return_url,
        );
        $recharge = recharge_request_execute($req);
        if($recharge && $recharge['code'] == 200){
            $redict_url =  $recharge['redirect_url'];
            if($agent == "fruitday-web" && $type == 1){
                $r = get_fruitday_pay_url($recharge['redirect_url']);
                if($r['code'] == 200)
                    $redict_url = $r['msg'];
            }
            $ret = array('order_name'=>$recharge['data']['trade_number'],'return_url'=>$redict_url);
            $this->send_ok_response($ret);
        }else{
            $this->send_error_response($recharge['msg'].",充值失败，请重试");
        }
    }

    public function open_door_post(){
        $device_id = $this->post('device_id');
        $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
        $user = $this->get_curr_user();
        if($user['fruitday_money'] < self::MIN_BLANCE){
            $this->send_error_response("余额不足".self::MIN_BLANCE."元");
        }
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $rs = $device->request_open_door($device_id,$user,'fruitday');
        if($rs == 'succ'){
            $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...'));
        }else{
            $this->send_error_response($rs."，开门失败，请稍后重试");
        }
    }

    /**
     * 获取盒子状态
     */
    public function box_status_get(){
        $device_id = $this->get('device_id');
        $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
        $user = $this->get_curr_user();
        $this->load->model('box_status_model');
        $rs = $this->box_status_model->get_info_by_box_id($device_id);
        
        if(!$rs){
            $this->send_error_response("参数错误，请重新扫码");
        }else{
            if($rs['user_id'] != $user['id'] || strtotime($rs['last_update']) < time() - 15*60 ){
                $this->send_ok_response(array('status'=>'free','message'=>'跳转余额页'));
            }else if($rs['user_id'] == $user['id']  && $rs['status'] == 'stock'){
                $this->send_ok_response(array('status'=>'stock','message'=>'结算中...'));
            } else if($rs['user_id'] == $user['id'] && $rs['status'] == 'free'){
                $this->load->model('log_open_model');
                $log = $this->log_open_model->get_open_log($rs['open_log_id']);
                $order_name = "";
                if($log && $log['order_name']){
                    $order_name = $log['order_name'];
                }
//                $key = "scan_login_".$device_id;
//                $status = $this->cache->get($key);
                $status = '';
                if($status=="login_succ")//todo 演示专用
                {
                    $this->send_ok_response(array('status'=>'stock','message'=>'结算中...'));
                }else{
                    
                    if($rs['refer'] == 'cmb'){
                        $this->load->model("order_model");
                        $this->load->model("order_pay_model");
                        $order_info = $this->order_model->get_order_by_name($order_name);
                        $pay_info = $this->order_pay_model->get_pay_order_by_order_name($order_name);
                        
                        if( $pay_info[0]['pay_status'] == '0' ){
                            $this->send_ok_response(array('status'=>'wait_pay','message'=>'等待支付','order_name'=>$order_name));
                        }
                    }
                    $this->send_ok_response(array('status'=>'pay_succ','message'=>'支付成功','order_name'=>$order_name));
                }

            }
        }
        $this->send_error_response("购物中");
    }

    /**
     * 获取订单详情
     */
    public function get_detail_get()
    {
        $order_name = $this->get("order_name");
        $this->check_null_and_send_error($order_name,"订单号不能为空");
        $this->load->model("order_model");
        $rs = $this->order_model->get_order_by_name($order_name);
        if(!$rs){
            $this->send_error_response("订单不存在");
        }
        $this->send_ok_response($rs);
    }

    public function get_order_and_amount_get()
    {
        $user = $this->get_curr_user();
//        sleep(3);//延时3秒，以防余额未更新
//        $info = get_fruit_user_request_execute(array('uid'=>$user['open_id'],'source'=>'wap'));
//        if($info['code']==200){
//            $user['fruitday_money'] = $info['data']['money'];
//            $this->update_curr_user($user);
//        }else {
//            $this->send_error_response("没有查到用户数据");
//        }

        $device_id = $this->get("device_id");
        $order_name = $this->get("order_name");
        $rs['foot_img'] = array('img_url'=>DEFALUT_BANNER_IMG,'start_time'=>date('Y.m.d'),'end_time'=>date('Y.m.d'),'remarks'=>'CITYBOX魔盒','click_type'=>0,'click_url'=>'');
        if(empty($order_name)){
            $rs['money'] = 0;
            $rs['fruitday_money'] = $user['fruitday_money'];
            $this->send_ok_response($rs);
        }
        $this->load->model("order_model");
        $rs = $this->order_model->get_order_by_name($order_name);
        if(!$rs){
            $rs['money'] = 0;
        }
        $this->load->model("order_discount_log_model");
        $rs["discount"] = $this->order_discount_log_model->list_order_discount($order_name);//优惠信息
        if(!$rs["discount"]){
            $rs["discount"] = array();
        }
        if(!empty($rs['use_card']) && $rs['card_money'] >0){
            $this->load->model('card_model');
            $card_info = $this->card_model->get_card_by_number($rs['use_card']);
            if($card_info){
                $card = array(
                    'text'=>$card_info['card_name'],
                    'discount_money'=>$rs['card_money']
                );
                $rs["discount"][] = $card;
            }
        }

        $rs['fruitday_money'] = $user['fruitday_money'] - $rs['money'];
        $rs['discounted_money'] = $rs['discounted_money'];
        //结算页的banner
        $foot_img = '';
        //获取用户等级
        $user_rank = 1;
        $acount_id = $user['acount_id'];
        $sql = "select user_rank from cb_user_acount where id=".$acount_id;
        $user_rs = $this->db->query($sql)->row_array();
        if ($user_rs){
            $user_rank = $user_rs['user_rank'];
        }
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 3 and user_rank like '%{$user_rank}%' order by id desc limit 0,10";
        $foot = $this->db->query($sql)->row_array();
        $j = 0;

        $is_show = 1;
        $is_refer = 0;

        //增加判断来源
        if ($foot['refer'] == 'all'){
            $is_refer = 1;
        } else {
            if ($foot['refer'] == 'fruitday'){
                $is_refer = 1;
            }
        }
        if ($is_show == 1 && $is_refer == 1){
                if ($foot['click_type'] == 1){
                    $click_url = $foot['click_url'];
                } elseif ($foot['click_type'] == 2){
                    $click_url = IMG_HOST.'/'.$foot['click_url'];
                }
            $return_result['foot_img'] = array('img_url'=>IMG_HOST.'/'.$foot['img_banner'],'start_time'=>date('Y.m.d',strtotime($foot['start_time'])),'end_time'=>date('Y.m.d',strtotime($foot['end_time'])),'remarks'=>$foot['remarks'],'click_type'=>$foot['click_type'],'click_url'=>$click_url);
        }
        $this->send_ok_response($rs);
    }
}

<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Cart Controller
 * 购物车
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */
class Cart extends REST_Controller {

    const CACHE_TIME = 30*60;
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("product_model");
        $this->load->model("user_model");
        $this->load->model("equipment_model");
        $this->load->model("box_status_model");
        $this->load->model("order_model");
    }

    //扫码设备添加商品
    public function add_product_use_code_post(){
        $device_id = $this->raw_data("device_id");
        $serial_code = $this->raw_data("code");
        $type = $this->raw_data("type");
        $this->check_null_and_send_error($device_id,"device_id参数不能为空");
        $this->check_null_and_send_error($serial_code,"code参数不能为空");
        $this->check_null_and_send_error($type,"type参数不能为空");
        if(!in_array($type,array('add','del'))){
            $this->send_error_response('type应为add或del');
        }

        $device = $this->equipment_model->get_info_by_equipment_id($device_id);
        if(!$device || !isset($device['platform_id'])){
            $this->send_error_response("设备不存在");
        }else if(substr($device['type'],0,4) != 'scan'){
            $this->send_error_response("非扫码设备");
        }

        $box_status = $this->box_status_model->get_info_by_box_id($device_id);
        if($box_status['status'] != 'busy' || !$box_status['user_id'] ){
            $this->send_error_response("设备状态不是开门中，请关门重新操作");
        }

        $platform_id = $device['platform_id'];
        $product = $this->product_model->get_info_by_serial_code($serial_code,$platform_id);


        $tip = "";
        if($product){
            $product['img_url'] = IMG_HOST.'/'.$product['img_url'];
            $product['qty'] = "1";
            $product['code'] = $serial_code;
        }
        $cart = $this->cal_cart_for_code_list($device_id,$box_status['user_id'],$type,$product,$tip);

        $response = array(
            'tips'=>$tip,
            'last_update'=>date('Y-m-d H:i:s'),
            'box_no'=>$device_id,
            'money'=>'0',
            'good_money'=>'0',
            'qty'=>'0',
            'refer'=>$box_status['refer'],
            'card_name'=>'',
            'card_date'=>'',
            'card_money'=>'0',
            'level_money'=>'0',
            'modou'=>'0',
            'discounted_money'=>'0',
            'discount'=>array(),
            'goods'=>array(),
//            'codes'=>array(),
        );
        if($cart)
            $response['goods'] = $cart;
        $products = array();
        $product_code = array();
        foreach ($cart as $v){
            $product_code[$v['id']] = $v['code'];
            if(isset($products[$v['id']])){
                $products[$v['id']]['qty'] =  $products[$v['id']]['qty']+1;
            }else{
                $products[$v['id']]['product_id'] =  $v['id'];
                $products[$v['id']]['qty'] =  1;
            }
        }
        $products = array_values($products);
        $vm_order = array();
        if($products)
            $vm_order = $this->order_model->create_cart($products, $box_status['user_id'], $device_id, $box_status['refer']);
        if($vm_order){
            //全部格式为字符串，省得安卓出错
            foreach ($response as $key=>$value){
                if(isset($vm_order[$key]) && $vm_order[$key]){
                    if(!is_array($value)){
                        $response[$key] = (string)$vm_order[$key];
                    }
                }
            }
            if($vm_order['use_card']){
                $this->load->model('card_model');
                $card_info = $this->card_model->get_card_by_number($vm_order['use_card']);
                if($card_info){
                    $vm_order['card_name'] = $card_info['card_name'];
                    $vm_order['card_date'] = $card_info['begin_date'].'-'.$card_info['to_date'];
                }
            }
        }
        $this->send_ok_response($response);
    }


    private function cal_cart_for_code_list($device_id,$user_id,$type,$product,&$tips){
        $cache_key = 'cart_'.$device_id."_".$user_id;
        $cart = $this->cache->get($cache_key);
        if(!$product){
            $tips = '条形码不存在';
            return $cart;
        }
        if($type == 'add'){
            $cart[] = $product;
            $tips = '添加成功';
        }else{
            foreach ($cart as $k=>$v){
                if($v['code'] == $product['code']){
                    $tips = '删除成功';
                    unset($cart[$k]);
                    break;
                }
            }
        }
        $cart = array_values($cart);
        $this->cache->save($cache_key,$cart,self::CACHE_TIME);//缓存15分钟
        return $cart;
    }

    private function cal_cart_for_code($device_id,$user_id,$type,$product,&$tips){
        $cache_key = 'cart_'.$device_id."_".$user_id;
        $cart = $this->cache->get($cache_key);
        if(!$product){
            $tips = '条形码不存在';
            return $cart;
        }
        $exist_goods =  array();
        if($cart && is_array($cart) && count($cart) > 0){
            foreach ($cart  as $k =>$v){
                $exist_goods[] = $v['id'];
                if($v['id'] ==  $product['id']){
                    if($type == 'del'){
                        $cart[$k]['qty'] -= $product['qty'];
                        if($cart[$k]['qty']<=0) {
                            unset($cart[$k]);
                        }
                        $tips = '删除成功';
                    }elseif ($type == 'add' ){
                        $cart[$k]['qty'] += $product['qty'];
                        $tips = '添加成功';
                    }
                }
            }
        }

        if(! in_array($product['id'],$exist_goods) &&  $type == 'add'){
            $cart[] = $product;
        }

        $cart = array_values($cart);
        $this->cache->save($cache_key,$cart,self::CACHE_TIME);//缓存15分钟
        return $cart;
    }

    private function codelist_update($cache_key_code,$code,$type,$product){
        $cart_code = $this->cache->get($cache_key_code);
        if(!$cart_code){
            $cart_code = array();
        }
        if(!$product){
            return $cart_code;
        }
        if($type == 'add'){
            $cart_code[] = $code;
        }else if($type == 'del'){
            foreach ($cart_code as $k=>$v){
                if($v == $code){
                    unset($cart_code[$k]);
                    break;
                }
            }
        }
        $cart_code = array_values($cart_code);
        $this->cache->save($cache_key_code,$cart_code,self::CACHE_TIME);//缓存15分钟
        return $cart_code;
    }


    /**
     * 根据69码查询商品信息
     */
    public function add_product_cart_get()
    {
        $serial_code     = $this->get("serial_code");
        $device_id     = $this->get("device_id");
        $num     = $this->get("num");
        $scan     = $this->get("scan");
        if(! isset($num))
            $num = 1;
        $this->check_null_and_send_error($serial_code,"条形码不能为空");
        $this->check_null_and_send_error($device_id,"设备不能为空");


        $serial_code = ltrim($serial_code,'EAN_13,');//去掉微信返回的barcode 前缀
        $device = $this->equipment_model->get_info_by_equipment_id($device_id);
        if(!$device || !isset($device['platform_id'])){
            $this->send_error_response("设备ID不存在");
        }
        $platform = $device['platform_id'];
        $rs = $this->product_model->get_info_by_serial_code($serial_code,$platform);
        if(!$rs){
            $this->send_error_response('条形码不存在');
        }
        $rs['img_url'] = IMG_HOST.'/'.$rs['img_url'];
        $rs['qty'] =$num;
        $cart = $this->add_cart($rs,1,$scan);

//        $key = "scan_login_".$device_id;
//        $this->cache->save($key,'login_succ',self::CACHE_TIME);

        $this->send_ok_response($cart);
    }


    /**
     * @param $product
     * @param int $type 1 添加， -1 删除
     * @return mixed
     */
    private function add_cart($product,$type = 1,$scan = 0){
        $user = $this->get_curr_user();
        $ret =  array();
        if($user['goods'] && is_array($user['goods']) && count($user['goods']) > 0){
            foreach ($user['goods']  as $k =>$v){
                $ret[] = $v['id'];
                if($v['id'] ==  $product['id']){
                    if($type == -1 || $product['qty'] <= 0){
                        unset($user['goods'][$k]);
                    }elseif ($type == 1 ){
                        if($scan){
                            $user['goods'][$k]['qty'] += $product['qty'];
                        }else{
                            $user['goods'][$k]['qty'] = $product['qty'];
                        }
                    }
                }
            }
        }

        if(! in_array($product['id'],$ret) &&  $type == 1){
            $user['goods'][] = $product;
        }
        $user['goods'] = array_values($user['goods']);
        $this->update_curr_user($user);
        $ret['goods'] = $user['goods'];
        $ret['pay'] = $this->cal_cart($ret['goods']);
        return $ret;
    }

    function del_car_goods_get(){
        $product_id     = $this->get("product_id");
        $device_id     = $this->get("device_id");

        $this->check_null_and_send_error($product_id,"条形码不能为空");
        $this->check_null_and_send_error($device_id,"设备不能为空");

        $device = $this->equipment_model->get_info_by_equipment_id($device_id);
        if(!$device || !isset($device['platform_id'])){
            $this->send_error_response("设备ID不存在");
        }
        $rs['id'] = $product_id;
        $cart = $this->add_cart($rs,-1);

//        $key = "scan_login_".$device_id;
//        $this->cache->save($key,'login_succ',self::CACHE_TIME);
        $this->send_ok_response($cart);
    }


    public function  car_goods_get(){
        $user = $this->get_curr_user();

        $device_id     = $this->get("device_id");

        $this->check_null_and_send_error($device_id,"设备不能为空");
        $ret['goods'] = $user['goods'];
        if(!$ret['goods'])
            $ret['goods'] = array();
        $ret['pay'] = $this->cal_cart($ret['goods']);
//        $key = "scan_login_".$device_id;
//        $this->cache->save($key,'login_succ',self::CACHE_TIME);
        $this->send_ok_response($ret);
    }

    private function cal_cart($goods){
        $ret['total'] =0;
        $ret['qty'] =0;
        foreach ($goods as $v){
            $ret['qty'] +=$v['qty'];
            $ret['total'] +=$v['price']*$v['qty'];
        }
        return $ret;
    }


    public function car_goods_pay_post(){
        $device_id     = $this->post("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $user = $this->get_curr_user();
        $order = $this->create_order($user,$device_id,'alipay');
        if($order){
            //更新最后一次open_log
            $this->load->model('box_status_model');
            $rs = $this->box_status_model->get_info_by_box_id($device_id);
            $log_id = $rs['open_log_id'];
            $order_name = $order['order_name'];
            $this->load->model('log_open_model');
            $this->log_open_model->update_log($log_id,$order_name);

            $user['goods'] = array();
            $this->update_curr_user($user);//清空购物车
            $this->load->library("Device_lib");
            $device = new Device_lib();

//            $order['money'] = '0.01';//demo 支付金额
            $rs_pay = $device->pay($order, $user['id'], 'alipay','alipay');
            $this->order_model->update_order_last_update($order['order_name']);

            $key = "scan_login_".$device_id;
            $this->cache->save($key,'',self::CACHE_TIME);

            if($rs_pay['code'] == 200){
                $rs_pay['order_name'] = $order['order_name'];
                $this->send_ok_response($rs_pay);
            } else{
                $this->send_error_response($rs_pay['message'] ? : "支付失败");
            }
        }else{
            $this->send_error_response("创建订单失败");
        }
    }

    public function scan_status_post(){
        $type     = $this->post("type");
        $device_id     = $this->post("device_id");
        $key = "scan_login_".$device_id;

        $this->load->model('box_status_model');
        $rs = $this->box_status_model->get_info_by_box_id($device_id);
        $status = $rs['status'];
        if($status == 'scan'){
            $ret = array("status"=>'scan_succ');
            $this->cache->save($key,'scan_succ',self::CACHE_TIME);
        }elseif ($status == 'busy'){
            $this->cache->save($key,'login_succ',self::CACHE_TIME);
            $user = $this->user_model->get_user_info_by_id($rs['user_id']);
            $this->update_curr_user($user);
            $ret = array("status"=>'login_succ','name'=>$user['user_name'],'id'=>$user['id']);
        }elseif ($status == 'free'){
            $this->cache->save($key,'',self::CACHE_TIME);
        }
        $this->load->model('equipment_model');
        $info = $this->equipment_model->get_info_by_equipment_id($device_id);
        $ret['device'] = $info;
        $this->send_ok_response($ret);
    }

    private function create_order($user,$device_id,$type){

        $products = array();
        foreach ($user['goods'] as $v){
            $products[] = array('product_id'=>$v['id'],'qty'=>$v['qty']);
        }
        $this->load->model("order_model");
        return $this->order_model->create_order($products, $user['id'], $device_id,$type);//创建支付单
    }
    public function car_goods_pay_wx_post(){
        $user = $this->get_curr_user();
        $device_id     = $this->post("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $order = $this->create_order($user,$device_id,'wechat');
        if($order){
            $user['goods'] = array();
            $this->update_curr_user($user);//清空购物车
            $this->send_ok_response($order);
        }else{
            $this->send_error_response("创建订单失败");
        }
    }
    public function banner_post(){
       $ret =  array(
            "http://fdaycdn.fruitday.com/images/2018-01-08/1515401996_2.jpg",
            "http://fdaycdn.fruitday.com/images/2018-01-03/1514947449_2.jpg"
        );
       $this->send_ok_response($ret);
    }
}

<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Deliver Controller
 * 补货管理
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/22/17
 * Time: 12:56
 */
class Deliver extends REST_Controller {


    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('shipping_order_model');
        $this->load->model('shipping_order_product_model');
        $this->load->model('product_model');
        $this->load->model("equipment_model");
        $this->load->model("deliver_model");
        $this->load->model("equipment_stock_model");
    }

    function deliver_open_post(){
        $device_id = $this->post('device_id');
        $deliver_no = $this->post('deliver_no');
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $this->check_null_and_send_error($deliver_no,'配送单号不能为空');



        $user = $this->get_curr_user();
        $key = "role_". $user['open_id'];
        $role = $this->cache->get($key);
        if($role != 'admin'){
            $this->send_error_response('你没有权限操作上下货');
        }
        //验证配送单号


        $deliver_info =$this->shipping_order_model->get_info_by_ship_no($deliver_no);
        if(!$deliver_info){
            $this->send_error_response('配送单号不存在');
        }else if($deliver_info['equipment_id'] != $device_id){
            $this->send_error_response('配送单号与当前开门设备不符');
        }

        $key_no = "order_id_". $device_id;
        $this->cache->save($key_no, $deliver_info['id'], REST_Controller::USER_LIVE_SECOND);

        //查询设备类型

        $device = $this->equipment_model->get_info_by_equipment_id($device_id);
        if(!$device){
            $this->send_error_response('设备不存在');
        }
        if($device['type'] == RFID_1){
            //rfid 设备直接开门
            $this->load->library("Device_lib");
            $device1 = new Device_lib();
            $status = $device1->request_open_door($device_id,$user,'alipay');
            if($status == 'succ'){
                $data = array('type'=>$device['type'],'status'=>'succ','message'=>'请求开门中...','device_id'=>$device_id);
                $this->send_ok_response($data);
            }else{
                $this->send_error_response($status."，开门失败，请稍后重试");
            }
        }else{
            //需要手动确认补货商品
            $this->send_ok_response(array('type'=>$device['type'],'status'=>'redirect','message'=>'手动确认补货商品','device_id'=>$device_id,'deliver_id'=>$deliver_info['id']));
        }
    }

    /**
     *
     */
    function init_goods_get(){
        $user = $this->get_curr_user();
        $device_id     = $this->get("device_id");
        $deliver_no     = $this->get("deliver_no");
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $this->check_null_and_send_error($deliver_no,'配送单号不能为空');

        $key = $device_id.'_deliver_goods';
        if( isset($user[$key]) ){
            $ret['goods'] = $user[$key];
        }

        if(!$ret['goods']){
            $ret['goods'] = array();
            $products = $this->shipping_order_product_model->list_order_products($deliver_no);
            foreach ($products as $product){
                if($product['product_id']){
                    $rs = $this->product_model->get_info_by_id($product['product_id']);
                    $rs['img_url'] = IMG_HOST.'/'.$rs['img_url'];
                    $rs['qty'] = $product['add_qty'];
                    $ret['goods'][] = $rs;
                }
            }
            $user[$key] = array_values($ret['goods']);
            $this->update_curr_user($user);
        }

        $ret['pay'] = $this->cal_cart($ret['goods']);
        $this->send_ok_response($ret);
    }

    public function fix_product_get()
    {
        $product_id     = $this->get("product_id");
        $device_id     = $this->get("device_id");
        $serial_code     = $this->get("serial_code");
        $k     = $this->get("k");
        $num     = $this->get("num");

        $scan     = $this->get("scan");
        if(! isset($num))
            $num = 1;
//        $this->check_null_and_send_error($product_id,"product_id不能为空");
        $this->check_null_and_send_error($device_id,"设备不能为空");

        if($k == 'stock'){
            $key = 'eq_stock_goods_'.$device_id;
        }else{
            $key = $device_id.'_deliver_goods';
        }


        if($product_id){
            $rs = $this->product_model->get_info_by_id($product_id);
        } elseif($serial_code)
        {
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
        }


        $rs['img_url'] = IMG_HOST.'/'.$rs['img_url'];
        $rs['qty'] =$num;
        $cart = $this->add_cart($rs,1,$device_id,$scan,$key);

        $this->send_ok_response($cart);
    }


    private function add_cart($product,$type = 1,$device_id,$scan = 0 ,$key){

        $user = $this->get_curr_user();
        $ret_ids = $ret =  array();
        if($user[$key] && is_array($user[$key]) && count($user[$key]) > 0){
            foreach ($user[$key]  as $k =>$v){
                $ret_ids[] = $v['id'];
                if($v['id'] ==  $product['id']){
                    if($type == -1 || $product['qty'] <= 0){
                        if($key == 'eq_stock_goods_'.$device_id){
                            $user[$key][$k]['qty'] = 0;
                        }else{
                            unset($user[$key][$k]);
                        }

                    }elseif ($type == 1 ){
                        if($scan){
                            $user[$key][$k]['qty'] += $product['qty'];
                        }else{
                            $user[$key][$k]['qty'] = $product['qty'];
                        }
                    }
                }
            }
        }

        if(! in_array($product['id'],$ret_ids) &&  $type == 1){
            $user[$key][] = $product;
        }

        $user[$key] = array_values($user[$key]);

        $this->update_curr_user($user);
        $ret['goods'] = $user[$key];
//        var_dump($user[$key]);die;
        $ret['pay'] = $this->cal_cart($ret['goods']);
        return $ret;
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

    /**
     *提交商品上下架商品
     */
    public function confirm_deliver_post(){
        $user = $this->get_curr_user();
        $device_id     = $this->post("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $order = $this->create_deliver_order($user,$device_id,'alipay');
        if($order){
            $key = $device_id.'_deliver_goods';
            $user[$key] = array();
            $this->update_curr_user($user);//清空购物车

            //请求开门


            $this->send_ok_response($order);
        }else{
            $this->send_error_response("上下货创建配送工单失败");
        }
    }

    public function open_scan_post(){
        $user = $this->get_curr_user();
        $device_id     = $this->post("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');

        $key = "role_". $user['open_id'];
        $role = $this->cache->get($key);
        if($role != 'admin'){
            $this->send_error_response('你没有权限操作上下货');
        }

        //请求开门
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $status = $device->request_open_door($device_id,$user,'alipay');
        if($status == 'succ'){
            $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...','device_id'=>$device_id));
        }else{
            $this->send_error_response($status."，开门失败，请稍后重试");
        }
    }

    private function create_deliver_order($user,$box_id,$type){

        $rs_add = $user[$box_id.'_deliver_goods'];
        $deliver_data = array(
            'equipment_id' => $box_id,
            'originator' => $user['id'],   //user表  s_admin_id  后台账号id
            'description' => '扫码上下货-非RFID机型',
            'begin_time' => date("Y-m-d H:i:s"),//开门的时间
            'end_time' => date("Y-m-d H:i:s"),//关门的时间
            'result' => '',
            'time' => date('Y-m-d H:i:s')
        );
        $deliver_products_datas = array();
        //管理员
        $result = "";
        $up_num = $down_num = 0;
            //上架商品
        $result = "上架：";

        if($rs_add) {
            foreach ($rs_add as $ra) {
                $deliver_products_data['product_id'] = $ra['id'];
                $up_num +=$ra['qty'];
                $deliver_products_data['qty'] = $ra['qty'];
                $deliver_products_data['type'] = '1';
                $deliver_products_data['real_qty'] = '';
                $rs_product = $this->product_model->get_info_by_id($ra['id']);
                $deliver_products_data['product_name'] = $rs_product ? $rs_product['product_name'] : "";
                $deliver_products_datas[] = $deliver_products_data;
                $result .= $deliver_products_data['product_name'] . "x" . $deliver_products_data['qty'] . " ";
            }
        }
        $result .= ";";
        $deliver_data['result'] = $result;
        if ($deliver_products_datas) {
            $rs_deliver_no = $this->deliver_model->insert_deliver($deliver_data, $deliver_products_datas);
            if($rs_deliver_no){
                $key_no = "deliver_no_". $box_id;
                $this->cache->save($key_no, $rs_deliver_no, REST_Controller::USER_LIVE_SECOND);
                return $rs_deliver_no;
            }
        }
        return false;
    }

    function init_eq_goods_get(){
        $user = $this->get_curr_user();
        $device_id     = $this->get("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');

        $device = $this->equipment_model->get_info_by_equipment_id($device_id);
        if(!$device){
            $this->send_error_response('设备不存在');
        }
        if($device['type'] == RFID_1){
            $this->send_error_response('该设备不支持手动盘点操作');
        }


        $key = 'eq_stock_goods_'.$device_id;
        if( isset($user[$key]) ){
            $ret['goods'] = $user[$key];
        }

        if(!$ret['goods']){
            $ret['goods'] = array();
            $products = $this->equipment_stock_model->get_eq_products($device_id);
            foreach ($products as $product){
                if($product['product_id']){
                    $rs = $this->product_model->get_info_by_id($product['product_id']);
                    $rs['img_url'] = IMG_HOST.'/'.$rs['img_url'];
                    $rs['qty'] = $product['stock'];
                    $ret['goods'][] = $rs;
                }
            }
            $user[$key] = array_values($ret['goods']);
            $this->update_curr_user($user);
        }
        $ret['pay'] = $this->cal_cart($ret['goods']);
        $this->send_ok_response($ret);
    }
    //确认库存
    function confirm_stock_post(){
        $user = $this->get_curr_user();
        $device_id     = $this->post("device_id");
        $this->check_null_and_send_error($device_id,'设备id不能为空');
        $key = 'eq_stock_goods_'.$device_id;
        $goods = $user[$key] ;
        $num = $this->equipment_stock_model->update_stock_by_handle($device_id,$goods);
        $user[$key] = array();
        $this->update_curr_user($user);//清空购物车
        $this->send_ok_response($num);
    }

}

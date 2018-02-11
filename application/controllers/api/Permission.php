<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Permission Controller
 * 配送开门全线接口
 * Created by PhpStorm.
 * User: syt
 * Date: 5/2/17
 * Time: 12:56
 */
class Permission extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("shipping_permission_model");
        $this->load->model("shipping_order_model");
    }

    /**
     * 增加配送单权限
     *
     */
    public function add_shipping_permission_post()
    {
        $mobile = $this->post("mobile");
        $member = $this->post("member");
        $shipping_no = $this->post("shipping_no");

        $this->check_null_and_send_error($mobile,"mobile参数不能为空");
        $this->check_null_and_send_error($shipping_no,"shipping_no参数不能为空");

        //get equitment_id

        $mobile = explode(',',$mobile);
        $member = explode(',',$member);
        $shipping_no = explode(',',$shipping_no);


        $this->db->trans_begin();

        foreach($mobile as $k=>$v){
            $shipping_order = $this->db->select('equipment_id,platform_id')->from('shipping_order')->where(array(
                'shipping_no'=>$shipping_no[$k]
            ))->get()->row_array();


            $equipment_id = $shipping_order['equipment_id'];
            if(empty($equipment_id)){
                $this->db->trans_rollback();
                $ret = array(
                    "status"=>false,
                    "message"=>"查询不到设备id！".$equipment_id
                );
                $this->send_ok_response($ret);
            }

            $user_data = $this->user_model->get_user_info_by_mobile($mobile[$k],"id");
            $uid = $user_data['id'];
            $permission_data = array(
                'uid' => $uid,
                'add_time' => date('Y-m-d H:i:s'),
                'end_time'=> date('Y-m-d H:i:s', strtotime('+24 hours')),
                'status' => 1,
                'equipment_id' => $equipment_id,
                'mobile' => $mobile[$k],
                'shipping_no' => $shipping_no[$k],
                'platform_id' => $shipping_order['platform_id']   //默认是1
            );
            $rs = $this->shipping_permission_model->addPermission($permission_data);

            //更改订单状态
            $order_data = array(
                'operation_id' => 2,
                'shipping_no' => $shipping_no[$k],
                'dm_mobile' => $mobile[$k],
                'dm_name' => $member[$k]
            );
            $this->shipping_order_model->update_shipping_order($order_data);
        }





//        $is_added = $this->shipping_permission_model->getPermission(array(
//            'shipping_no' => $shipping_no
//        ));
//        if($is_added){
//            $data = array(
//                'mobile'=>$mobile,
//                'uid'=>$uid
//            );
//            $where =array(
//                'shipping_no'=>$shipping_no,
//                'mobile'=>$mobile
//            );
//            $rs = $this->shipping_permission_model->updatePermission($data,$where);
//
//            //更改订单状态
//            $order_data = array(
////                'operation_id' => 2,
//                'shipping_no' => $shipping_no,
//                'dm_mobile' => $mobile,
//                'dm_name' => $member
//            );
//            $this->shipping_order_model->update_shipping_order($order_data);
//        }else{

//        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $rs =  false;
        } else {
            $this->db->trans_commit();
            $rs = true;
        }
        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"接收成功"
            );
            $this->send_ok_response($ret);
        }else{
            $ret = array(
                "status"=>false,
                "message"=>"插入失败"
            );
            $this->send_ok_response($ret);
        }


    }

    /**
     * 修改配送单 和 权限完成
     */
    public  function update_shipping_permission_post(){
        $mobile = $this->post("mobile");
        $shipping_no = $this->post("shipping_no");

//        $this->check_null_and_send_error($mobile,"mobile参数不能为空");
        $this->check_null_and_send_error($shipping_no,"shipping_no参数不能为空");

        $data = array(
            'status'=>0
        );
        $where =array(
            'shipping_no'=>$shipping_no,
            'mobile'=>$mobile
        );

        $this->db->trans_begin();
//        $rs = $this->shipping_permission_model->updatePermission($data,$where);
        //更改订单状态
        $order_data = array(
            'operation_id' => 3,
            'shipping_no' => $shipping_no
        );
        $this->shipping_order_model->update_shipping_order($order_data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $rs =  false;
        } else {
            $this->db->trans_commit();
            $rs = true;
        }

        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"变更成功"
            );
            $this->send_ok_response($ret);
        }else{
            $ret = array(
                "status"=>false,
                "message"=>"数据有误"
            );
            $this->send_ok_response($ret);
        }
    }

    /**
     * 修改配送单的配送车辆信息
     *[{"car_no":"001","shipping_nos":["cbp170831469639","cbp170831428155"]},{"car_no":"002","shipping_nos":["cbp170831004413","cbp170824399820"]}]
     * array(
            array('car_no'=>'001','shipping_nos'=>array('cbp170831469639','cbp170831428155')),
            array('car_no'=>'002','shipping_nos'=>array('cbp170831004413','cbp170824399820')),
     * )
     */
    public  function update_shipping_order_car_info_post(){
        $car_info = $this->post("car_info");

//        $car_info = json_encode(array(
//                array('car_no'=>'001','shipping_nos'=>array('cbp170831469639','cbp170831428155')),
//                array('car_no'=>'002','shipping_nos'=>array('cbp170831004413','cbp170824399820')),
//        ));

        $this->check_null_and_send_error($car_info,"修改车辆信息不能为空");

//        var_dump($car_info);


        $car_info = json_decode($car_info,1);

//        var_dump($car_info);

//        exit;

        $this->db->trans_begin();
        //更改订单状态

        if(!empty($car_info)){
            foreach($car_info as $v){
                $data = array(
                    'dm_car_no' => $v['car_no']
                );
                $where_in = array(
                    'key'=>'shipping_no',
                    'val'=>$v['shipping_nos']
                );
                $this->shipping_order_model->update_shipping_order_simple($data,false,$where_in);

//                echo $this->db->last_query();
            }
        }

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $rs =  false;
        } else {
            $this->db->trans_commit();
            $rs = true;
        }

        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"变更成功"
            );
            $this->send_ok_response($ret);
        }else{
            $ret = array(
                "status"=>false,
                "message"=>"数据有误"
            );
            $this->send_ok_response($ret);
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: Excel
 * Date: 2018/3/12
 * Time: 10:10
 */

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Account Controller
 * 用户管理
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */
class Dalang extends REST_Controller
{

    const PAGE_SIZE = 10;
    const REFUND_STATUS = 3;   //退款中
    const ORDER_STATUS_REJECT = 5;//驳回申请
    const ORDER_STATUS_REFUND_APPLY = 3;//退款申请
    const ORDER_STATUS_REFUND = 4;//退款完成

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("order_model");
        $this->load->model("order_product_model");
        $this->load->model("order_refund_model");
        $this->load->model("order_discount_log_model");
        $this->load->helper("utils");
        $this->load->helper("aop_send");
        $this->load->helper("mapi_send");
        $this->load->helper("message");
        $this->load->model('order_pay_model');
    }

    /**
     * 订单列表
     * status 0:未支付1支付
     */
    public function list_order_get()
    {
        $page = (int)$this->get("page");
        $status = (int)$this->get("status");
        $auth_code = $this->get("auth_code");
        if ($page <= 0)
            $page = 1;
        $page_size = $this->get("page_size");
        if (!$page_size)
            $page_size = PAGE_SIZE;
        $user = $this->get_curr_user();
        $rs = $this->order_model->list_orders($user["id"], $status, $page, $page_size);
        $this->send_ok_response($rs);
    }

    public function box_status_get()
    {
        $device_id = $this->get('device_id');
        $this->check_null_and_send_error($device_id, "缺少参数，请重新扫码");
        $user = $this->get_curr_user();
        $this->load->model('box_status_model');
        $rs = $this->box_status_model->get_info_by_box_id($device_id);

        if (!$rs) {
            $this->send_error_response("参数错误，请重新扫码");
        } else {
            if ($rs['user_id'] != $user['id'] || strtotime($rs['last_update']) < time() - 15 * 60) {
                $this->send_ok_response(array('status' => 'free', 'message' => '跳转余额页'));
            } else if ($rs['user_id'] == $user['id'] && $rs['status'] == 'stock') {
                $this->send_ok_response(array('status' => 'stock', 'message' => '结算中...'));
            } else if ($rs['user_id'] == $user['id'] && $rs['status'] == 'free') {
                $this->load->model('log_open_model');
                $log = $this->log_open_model->get_open_log($rs['open_log_id']);
                $order_name = "";
                if ($log && $log['order_name']) {
                    $order_name = $log['order_name'];
                }
                $this->send_ok_response(array('status' => 'pay_succ', 'message' => '支付成功', 'order_name' => $order_name));
            }
            $this->send_error_response("购物中");
        }
    }

    public function order_ad_get(){
        $order_name = $this->get('order_name');
        $device_id = $this->post('device_id');


        if ($order_name){
            $this->load->model("order_model");
            $this->load->model("order_product_model");

            $order = $this->order_model->get_order_by_name($order_name);
            $this->load->model("order_discount_log_model");
            $order["discount"] = $this->order_discount_log_model->list_order_discount($order_name);//优惠信息
            if(!$order["discount"]){
                $order["discount"] = array();
            }
            if(!empty($order['use_card']) && $order['card_money'] >0){
                $this->load->model('card_model');
                $card_info = $this->card_model->get_card_by_number($order['use_card']);
                if($card_info){
                    $card = array(
                        'text'=>$card_info['card_name'],
                        'discount_money'=>$order['card_money']
                    );
                    $order["discount"][] = $card;
                }
            }
            $return_result["order"] = $order;
            $return_result["order_goods"] = $this->order_product_model->list_order_products($order_name);

        } else {
            $return_result["order"] = array();
            $return_result["order_goods"] = array();
        }
        $this->send_ok_response($return_result);
    }

}
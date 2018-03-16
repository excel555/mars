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
            } else if($rs['user_id'] == $user['id'] && $rs['status'] == 'scan'){
                $this->send_error_response("购物中");
            }else if ($rs['user_id'] == $user['id'] && $rs['status'] == 'stock') {
                $this->send_ok_response(array('status' => 'stock', 'message' => '结算中...'));
            }else if ($rs['user_id'] == $user['id'] && $rs['status'] == 'free') {
                $this->load->model('log_open_model');
                $log = $this->log_open_model->get_open_log($rs['open_log_id']);
                $order_name = time();// "";
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

    /**
     * 支付宝支付异步通知
     */
    public function notify_post()
    {
        $this->load->model("order_pay_model");
        $this->load->model("order_model");
        $this->load->model("receive_alipay_log_model");
        $this->load->library("Device_lib");
        $msg_data = array(
            'msg_type'=>'alipay-notify',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        write_log(" notify para:" . var_export($_REQUEST, 1),'info');
        $config = get_platform_config_by_device_id($_REQUEST['device_id']);
        unset($_REQUEST['device_id']);
        write_log('config=>'.var_export($config,1),'info');
        if (verifyNotify($config)) {
            //验证通过
            write_log(" notify verify:验证通过",'info');
            if($_POST['trade_status'] === 'TRADE_SUCCESS' && $_POST['refund_status'] === 'REFUND_SUCCESS') {
                $pay_info['pay_no'] = $_POST['out_biz_no'];
                $notify_data = array('pay_status'=>5,'trade_number'=>$_POST['trade_no'],'pay_money'=>$_POST['refund_fee']);
                $lib = new Device_lib();
                $lib->update_order_and_pay($pay_info,$notify_data,'wechat');

            }else if ($_POST['trade_status'] === 'TRADE_SUCCESS') {
                $pay = $this->order_pay_model->get_pay_info_by_pay_no($_POST['out_trade_no']);
                $pay_info = array('subject'=>'魔盒CITYBOX购买商品','pay_no'=> $_POST['out_trade_no']);
                if($pay && $pay['pay_status'] != "1"){
                    $comment = '支付成功';
                    $pay_status = 1;
                    $open_id = $_POST['buyer_id'];
                    $pay_money = $_POST['total_fee'];
                    $uid = $pay['uid'];
                    $pay_user = $_POST['buyer_email'];
                    $trade_number = $_POST['trade_no'];
                    $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);
                    $lib = new Device_lib();
                    $lib->update_order_and_pay($pay_info,$notify_data,'alipay');
                }else{
                    if(!$pay){
                        write_log("[notify_post]支付单找不到,支付单号".$_POST['out_trade_no'],'crit');
                    }else{
                        write_log("[notify_post]支付单已经更改状态".$_POST['out_trade_no'],'info');
                    }
                }
            }
            echo "success";
        } else {
            write_log("[notify_post]验证不通过".var_export($_REQUEST,1),'info');
            echo "fail";
        }
    }
}
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Cmb Controller
 * 招商银行手机银行
 */
class Cmb extends REST_Controller
{
    function __construct()
    {
        parent::__construct();
    }
    
    
    public function dotest_get()
    {
        $this->load->library('CmbChina');
        
        $cmbchina = new CmbChina();
        
        $retval = $cmbchina->pullPublicKey();
        
        if($retval['code'] == 200){
            $this->load->driver('cache');
            $cache_val = $this->cache->redis->save('cmb_public_key', $retval['data']);
        }


        // write_log
        $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------cache_val\r\n";
        $log .= var_export( $cache_val, true ) . "\r\n";
        mutil_write_log($log, 'cmb');
    
        // $retval = $cmbchina->doQuery([
            // 'order_name' => '171227033682351',
            // 'order_date' => '20171228'
        // ]);
        
        // $retval = $cmbchina->doRefund([
            // 'order_name' => '171227033682351',
            // 'refund_id' => 'R01171227033682351',
            // 'order_date' => '20171228',
            // 'refund_fee' => '4.50'
        // ]);
        
        // $retval = $cmbchina->doRefundQuery([
            // 'order_name' => '171227033682351',
            // 'refund_date' => '20171228',
            // 'refund_id' => 'R01171227033682351'
        // ]);
        
        // $retval = $cmbchina->doAccountList([
            // 'query_date' => '20171228'
        // ]);
        
        var_dump($retval);
    }
    
    
    
    /**
     *@desc 招商银行手机银行开门
     */
    public function cmb_open_post()
    {
        $user       = $this->get_curr_user();
        $device_id  = $this->post('device_id');
        $open_passwd = $this->post('open_passwd');
        
        if(!$device_id || !$open_passwd){
            
            $this->send_error_response("缺少参数，请重新扫码");
        }
        
        if(!$user['open_id']){
            $this->send_error_response("用户未登录，请先扫码登录");
        }
    
        $this->load->driver('cache');
        $this->load->library("Device_lib");
        
        $open_passwd_key = 'open_passwd:'.$device_id.'_'.$user['open_id'];
        $open_passwd_val = $this->cache->redis->get($open_passwd_key);
        
        if(!$open_passwd_val || $open_passwd_val != $open_passwd){
            $this->send_error_response("开门口令失效，请重新扫码登录");
        }else{
            $this->cache->redis->delete($open_passwd_key);
        }
        
        $device = new Device_lib();
        $open_reval = $device->request_open_door($device_id, $user, 'cmb');
        
        if($open_reval == 'succ'){
            $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...'));
        }else{
            $this->send_error_response($open_reval."，开门失败，请稍后重试");
        }
    }
    
    
    /**
     *@desc 招商银行手机银行扫码支付
     */
    public function cmb_balance_get()
    {
        $device_id = $this->get('deviceId');
        $order_name = $this->get('order_name');
        
        $this->load->helper('url');
        
        if(!$order_name){
            redirect($base_url . '/mars/cmb_error.mars?error_code=1');
        }

        $this->load->library('CmbChina');
        $this->load->model("order_model");
        $this->load->model("order_pay_model");
        
        //$order_info = $this->order_model->get_order_by_name($order_name);
        $pay_rows = $this->order_pay_model->get_pay_order_by_order_name($order_name);
        
        if(isset($pay_rows[0])){
            $pay_info = $pay_rows[0];
        }else{
            redirect($base_url . '/mars/cmb_error.mars?error_code=2');
        }
        
        if( $pay_info['pay_status'] == '0' ){

            $base_url = $this->config->item("base_url");
            // $notify_url = 'http://54.223.75.149/index.php/api/cmb/notifyHandler';
            $notify_url = $base_url . '/index.php/api/cmb/notifyHandler';
            $return_url = $base_url . '/index.php/api/cmb/returnHandler?order_name=' . $order_name . '&deviceId=' . $device_id;
            
            $cmbchina = new CmbChina();
            
            $cmbchina->doQRPay([
                'order_name' => $pay_info['pay_no'],
                'amount' => $pay_info['money'],
                'notify_url' => $notify_url,
                'return_url' => $return_url
            ]);
        }else{
            redirect($base_url . '/mars/cmb_error.mars?error_code=3');
        }
    }
    
    
    /**
     *@desc 招商银行支付异步通知
     */
    public function notifyHandler_post()
    {
        $requestJson = $this->post('jsonRequestData');
        
        $requestData = json_decode($requestJson, true);
        $noticeData = $requestData['noticeData'];
        
        $this->load->helper('utils');
        $this->load->driver('cache');
        $this->load->library('CmbChina');
        $this->load->library("Device_lib");
        $this->load->model("order_model");
        $this->load->model("order_pay_model");
        $this->load->model('user_model');
        

        $cmbchina = new CmbChina();
        
        $public_key = $this->cache->redis->get('cmb_public_key');
        
        if($public_key){
            $cmbchina->set_public_key($public_key);
            $verifyStatus = $cmbchina->rsa_verify($noticeData, $requestData['sign']);
        }else{
            $verifyStatus = 0;
        }
        
        $pay_info = $this->order_pay_model->get_pay_info_by_pay_no($noticeData['orderNo']);
        
        if($verifyStatus && $pay_info && $pay_info['pay_status'] != "1"){
            
            $open_id = $this->user_model->get_user_open_id($pay_info['uid']);
            
            $pay_data = [
                'subject' => '魔盒CITYBOX购买商品',
                'pay_no' => $noticeData['orderNo']
            ];
            
            $notify_data = [
                'pay_status' => 1,
                'comment' => '支付成功',
                'uid' => $pay_info['uid'],
                'open_id' => $open_id,
                'trade_number' => $noticeData['bankSerialNo']
            ];
            
            if($noticeData['discountFlag'] == 'Y'){
                $notify_data['pay_money'] = bcsub($noticeData['amount'], $noticeData['discountAmount'], 2);
            }else{
                $notify_data['pay_money'] = $noticeData['amount'];
            }
            
            $lib = new Device_lib();
            $lib->update_order_and_pay($pay_info, $notify_data, 'cmb');
        }
        
    
        // write_log
        $log = "-----------------------". date("Y-m-d H:i:s") ."-----------------------notifyHandler\r\n";
        $log .= var_export( $requestJson, true ) . "\r\n";
        $log .= var_export( $noticeData, true ) . "\r\n";
        $log .= var_export( $verifyStatus, true ) . "\r\n";
        mutil_write_log($log, 'cmb');
        
        exit("Success");
    }
    
    
    public function returnHandler_get()
    {
        $device_id = $this->get('deviceId');
        $showtopbar = $this->get('showtopbar');
        $order_name = $this->get('order_name');
        
        $this->load->helper('url');
        $this->load->model("order_model");
        
        $base_url = $this->config->item("base_url");
        $order_info = $this->order_model->get_order_by_name($order_name);
        
        if($device_id && $showtopbar == 'true'){
            $redirect_url = $base_url . '/mars/buy_succ.mars?order_name=' . $order_name . '&deviceId=' . $device_id;
        }else{
            $redirect_url = $base_url . '/mars/order.mars?orderId=' . $order_name;
        }
        
        redirect($redirect_url);
    }
}

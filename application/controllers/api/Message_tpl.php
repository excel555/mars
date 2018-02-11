<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Created by PhpStorm.
 * User: Excel
 * Date: 31/05/2017
 * Time: 18:12
 */
class Message_tpl extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper("message");
    }

    function send_refund_post()
    {
        $type = $this->post('type');
        $open_id = $this->post('open_id');
        $url = $this->post('url');
        $keyword2 = $this->post('keyword2');
        $type = $type ? $type : 'alipay';
        $this->config->load("tips", TRUE);
        $msg_data = $this->config->item("refund_succ_msg", "tips");
        $msg_data['buyer_id'] = $open_id;
        $msg_data['url'] = $url;
        $msg_data['keyword2'] = $keyword2;
        Message::send_refund_msg($msg_data, $type);
    }

    /**
     * 发送微信模板消息
     */
    function send_wechat_warn_tpl_post()
    {
        $open_id = $this->post('open_id');
        $url = $this->post('url');
        $first = $this->post('first');
        $keyword1 = $this->post('keyword1');
        $keyword2 = $this->post('keyword2');
        $keyword3 = $this->post('keyword3');
        $keyword4 = $this->post('keyword4');
        $remark = $this->post('remark');
        $deviceId = $this->post('device_id');
        $data = array(
            'buyer_id' => $open_id,//'oCUeIwo4R1esx5bJZxu1R2y9vbK0',
            'url' => $url,
            'first' => $first,
            'keyword1' => $keyword1,
            'keyword2' => $keyword2,
            'keyword3' => $keyword3,
            'keyword4' => $keyword4,
            'remark' => $remark,
        );
        $this->config->load("tips", TRUE);
        $test_devices = $this->config->item("test_devices", "tips");
        if(in_array($deviceId,$test_devices)){
            $rs = array('status'=>'ok','message'=>"该设备不发送告警");
        }else{
            $rs = Message::send_warn_exception_msg($data, 'wechat', $deviceId);
        }
        $this->send_ok_response($rs);
    }
}
<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * AGT Controller
 * 关爱通相关
 * Created by PhpStorm.
 */
class Gat extends REST_Controller
{
    const MIN_BLANCE = 10;
    function __construct()
    {
        // Construct the parent class
        parent::__construct();

    }

    public function open_door_post(){
        $user      = $this->get_curr_user();
        $pay_code  = $this->post('pay_code');
        $device_id = $this->post('device_id');
        if(!$device_id || !$pay_code || !$user['open_id'] || $user['source'] !='gat' ){
            $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
        }
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $rs = $device->request_open_door($device_id, $user, 'gat');
        if($rs == 'succ'){
            $this->load->driver('cache');
            $this->cache->redis->save('pay_code:'.$device_id, $pay_code, 3600);
            $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...'));
        }else{
            $this->send_error_response($rs."，开门失败，请稍后重试");
        }
    }


}

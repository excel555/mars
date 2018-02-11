<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Koubei Controller
 * 口碑
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */
class Koubei extends REST_Controller
{

    const APP_AUTH_TOKEN_KEY = "koubei_app_token_";
    const APP_REFRESH_AUTH_TOKEN_KEY = "koubei_app_refresh_token_";
    const KOUBEI_SHOP_ID = "2017122500077000000047108312"; //2015070700077000000001343615,  2017122500077000000047108312

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model('user_agreement_model');
        $this->load->helper('koubei_send');
    }

    public function auth_koubei_login_post()
    {
        $auth_code = $this->post('auth_code');
        $platform_id = 14;//默认是14-五芳斋
        $config = get_platform_config($platform_id);
        $rs = koubei_auth_token_request($auth_code, $config);
        $key = APP_AUTH_TOKEN_KEY.$rs['auth_app_id'];
        $key_refresh = APP_REFRESH_AUTH_TOKEN_KEY.$rs['auth_app_id'];
        $this->cache->redis->save($key,$rs['app_auth_token'],$rs['expires_in']);
        $this->cache->redis->save($key_refresh,$rs['app_refresh_token'],$rs['re_expires_in']);
        $this->send_ok_response($rs);
    }
    private function refresh_token($auth_app_id,$platform_id = 14){
        $key_refresh = APP_REFRESH_AUTH_TOKEN_KEY.$auth_app_id;
        $refresh_token = $this->cache->redis->get($key_refresh);
        $config = get_platform_config($platform_id);
        $rs = koubei_auth_token_request($refresh_token, $config,1);
        $key = APP_AUTH_TOKEN_KEY.$rs['auth_app_id'];
        $this->cache->redis->save($key,$rs['app_auth_token'],$rs['expires_in']);
        $this->cache->redis->save($key_refresh,$rs['app_refresh_token'],$rs['re_expires_in']);
        return $rs['app_auth_token'];
    }

    public function koubei_shop_discount_query_get(){
        $device_id = $this->get("device_id");
        $this->check_null_and_send_error($device_id,'缺少参数device_id');
        $config = get_platform_config_by_device_id($device_id);
        $shop_id = KOUBEI_SHOP_ID;
        $user = $this->get_curr_user();
        $user_id = $user['open_id'];

        $rs = koubei_shop_discount_query_request($shop_id,$user_id,$config);
        if($rs['camp_num']>0){
            foreach ($rs['camp_list'] as &$camp){
                $camp->rule = json_decode($camp->camp_guide,1);
                if(in_array('crowd',$camp->rule_flag_list)){
                    unset($camp);
                }
            }
            $rs['camp_num'] = count($rs['camp_list']);
        }
        $this->send_ok_response($rs);
    }

    public function koubei_benefit_send_post(){

        $item_id = $this->post('item_id');
        $discount_type = $this->post('discount_type');
        $device_id = $this->post("device_id");
        $this->check_null_and_send_error($item_id,'缺少参数item_id');
        $this->check_null_and_send_error($discount_type,'缺少参数discount_type');
        $this->check_null_and_send_error($device_id,'缺少参数device_id');
        $user = $this->get_curr_user();
        $user_id = $user['open_id'];//2088502956254279
        $shop_id = KOUBEI_SHOP_ID;

        $config = get_platform_config_by_device_id($device_id);
        $data = array(
            'shop_id'=>$shop_id,
            'out_biz_no'=>uuid_32(),
            'item_id'=>$item_id,
            'user_id'=>$user_id,
            'discount_type'=>$discount_type,
            'channel'=>'weibo',
        );
        $rs = koubei_marketing_campaign_benefit_send($data,$config);
        $this->send_ok_response($rs);
    }

    public function sign_get(){

        $platform_id = KOUBEI_PLATFORM_ID;
        $config = get_platform_config($platform_id);
        $key = APP_AUTH_TOKEN_KEY.$platform_id;
        $auth_token = $this->cache->redis->get($key);
        $device_id = 200;
        $rs = koubei_request_agreement_url($config,$auth_token,$device_id);
        $this->send_ok_response($rs);
    }



    function query_get(){
        $platform_id = KOUBEI_PLATFORM_ID;
        $config = get_platform_config($platform_id);
        $alipay_user_id = '2088502956254279';//2088411801301863 2088502956254279
        $rs = koubei_agreemt_query($alipay_user_id,$config);
        $this->send_ok_response($rs);
    }

    /**
     * koubei支付宝-签免密协议异步通知
     */
    public function notify_agree_post()
    {
        $config = get_platform_config(KOUBEI_PLATFORM_ID);
        $sign = koubei_check($_REQUEST,$config);
        write_log("agreement notify:" .','. var_export($_REQUEST, 1).',config=>'.var_export($config,1).',rs='.var_export($sign,1));
        if ($sign && isset($_REQUEST['agreement_no'])) {
            $this->load->model('user_agreement_model');
            $data = array(
                'scene'=>$_REQUEST['sign_scene'],
                'agreement_no'=>$_REQUEST['agreement_no'],
                'sign_time'=>$_REQUEST['sign_time'],
            );
            $partner_id = $_REQUEST['auth_app_id'];//auth_app_id
            $ret = $this->user_agreement_model->update_agreement_sign($_REQUEST['alipay_user_id'],$data,'alipay',$partner_id);
            if($ret){
                $this->update_user_cache($ret['user_id'],$ret);
            }
            echo "success";
        }else{
            echo "fail";
        }
    }

    public function delete_sign_post(){
        //todo 口碑 解约
        write_log(" delete_sign  :" . var_export($_REQUEST, 1),'info');

        $this->load->model("receive_alipay_log_model");
        $msg_data = array(
            'msg_type'=>'koubei-del-sign',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        $config = get_platform_config(KOUBEI_PLATFORM_ID);

        $sign = koubei_check($_REQUEST,$config);
        write_log('koubei delete_sign_post=>'.var_export($_REQUEST,1).",$sign=".var_export($sign,1));
        if ($sign && isset($_REQUEST['agreement_no'])) {
            $ret = $this->user_agreement_model->delete_agreement_sign($_REQUEST['alipay_user_id'],'alipay',$_REQUEST["agreement_no"]);
            if($ret){
                $this->update_user_cache($ret['user_id'],$ret);
            }
            echo "success";
        }else{
            echo "fail";
        }
    }




    public function test_get(){
        $auth_code = $this->post('auth_code');
        $platform_id = 14;//默认是14-五芳斋
        $config = get_platform_config($platform_id);
        $rs = json_decode('{
  "code": "10000",
  "msg": "Success",
  "app_auth_token": "201801BB06d082fb9f5e41949afa648243801C86",
  "app_refresh_token": "201801BBaadf606feba44467a779b327093f5B86",
  "auth_app_id": "2014060900006368",
  "expires_in": 31536000,
  "re_expires_in": 32140800,
  "tokens": [
    {
      "app_auth_token": "201801BB06d082fb9f5e41949afa648243801C86",
      "app_refresh_token": "201801BBaadf606feba44467a779b327093f5B86",
      "auth_app_id": "2014060900006368",
      "expires_in": 31536000,
      "re_expires_in": 32140800,
      "user_id": "2088411801301863"
    }
  ],
  "user_id": "2088411801301863"
}',1);
        $key = APP_AUTH_TOKEN_KEY.$rs['auth_app_id'];
        $key_refresh = APP_REFRESH_AUTH_TOKEN_KEY.$rs['auth_app_id'];
        $this->cache->redis->save($key,$rs['app_auth_token'],$rs['expires_in']);
        $this->cache->redis->save($key_refresh,$rs['app_refresh_token'],$rs['re_expires_in']);
        $this->send_ok_response($rs);
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
        if (koubei_check($_REQUEST,$config)) {
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
                    $pay_money = $_POST['total_amount'];
                    $uid = $pay['uid'];
                    $pay_user = $_POST['buyer_logon_id'];
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
                //---------start zmxy feedback-----//
                $this->load->helper('zmxy_send');
                $feed_data = general_zmxy_order_data($pay['order_name'],2,date('Y-m-d H:i:s'));//未支付
                send_single_feedback($feed_data,$config);
                //---------end zmxy feedback-----//
            }
            echo "success";
        } else {
            write_log("[notify_post]验证不通过".var_export($_REQUEST,1),'info');
            echo "fail";
        }
    }

    public function pay_get(){
        $device_id = $this->get('device_id');
        $order = array();
        $platform_config = get_platform_config_by_device_id($device_id);
        $this->load->model('order_pay_model');
        $this->load->model('order_model');
        $this->load->model('user_model');
        $order = $this->order_model->list_not_pay_order_for_cron(10,356);

        if ($order) {
            $order =  $order[0];
            $data = array(
                'money' => $order['money'],
                'order_name' => $order['order_name'],
                'detail' => $order['goods']
            );

        }
        $pay_info = $this->order_pay_model->create_pay($data, $order['uid'], $order['refer']);//创建支付单
        $pay_info['device_id'] = $order['box_no'];
        $pay_info['open_id'] = $this->user_model->get_user_open_id($order['uid']);
        $user = $this->user_model->get_user_info_by_id( $order['uid']);
        $partner_id = $platform_config['mapi_partner'];
        $this->load->model('user_agreement_model');
        $exsit1 = $this->user_agreement_model->get_user_agreement_3rd($user['id'],$partner_id);
        if($exsit1){
            $pay_info['agreement_no'] = $exsit1[0]['agreement_no'];
        }else{
            $pay_info['agreement_no'] = '';
        }
        print_r($pay_info);
        $rs =  koubei_createandpay_request($pay_info, $platform_config,KOUBEI_SHOP_ID);
        $this->send_ok_response($rs);
    }

    public function refund_get(){
        $data = array(
            'out_trade_no'=>'171030286699513495',
            'refund_amount'=>'4.0',
            'refund_reason'=>'退款理由',
            'operator_id'=>'操作人',
            'out_request_no'=>'tuikuanpici'
        );
        $config  = get_platform_config(KOUBEI_PLATFORM_ID);
        $rs = koubei_refund_request($data,$config);
        $this->send_ok_response($rs);
    }

    public function gateway_post(){
        $this->load->model("receive_alipay_log_model");
        $msg_data = array(
            'msg_type'=>'koubei-gateway',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        $config = get_platform_config(KOUBEI_PLATFORM_ID);
        $ckeck = koubei_check($_REQUEST,$config);
        write_log(" gateway verify:验证通过".$ckeck);
        if($ckeck){
            echo "success";
        }else{
            echo "fail";
        }
    }

    public function gateway_get(){
        $this->load->model("receive_alipay_log_model");
        $msg_data = array(
            'msg_type'=>'koubei-gateway-get',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        $config = get_platform_config(KOUBEI_PLATFORM_ID);
        $ckeck = koubei_check($_REQUEST,$config);
        write_log(" gateway verify:验证通过".$ckeck);
        if($ckeck){
            echo "success";
        }else{
            echo "fail";
        }
    }
}

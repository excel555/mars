<?php

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
class Account extends REST_Controller
{

    const VCODE_LIVE_SECOND = 60;
    public $platforms = array();

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('user_fin_model');
        $this->load->model('user_model');
    }

    public function get_info_get()
    {
        $rs = $this->get_curr_user();
//
        $this->send_ok_response($rs);
    }


    private function login_user($user_id)
    {
        $user = $this->user_model->get_user_info_by_id($user_id);
        if ($user) {
            $fin = $this->user_fin_model->get_fin_by_id($user_id);
            $user['fin'] = $fin;
            $session_id = $this->session_id_2_user($user['id']);
            $user_cache_key = 'user_'.$session_id;
            $this->cache->save($user_cache_key,$user,604800);//记录用户保存7天
            $key1 = "role_". $user['open_id'];
            $this->cache->save($key1, $user['role'], REST_Controller::USER_LIVE_SECOND);
            write_log('login_user_succ=>'.var_export($user,1));
            $this->send_ok_response(['token' => $session_id, 'user' => $user]);
        } else {
            $this->send_error_response("cache错误");
        }
    }





    /**
     * 记录通过第三方平台的访问记录
     */
    public function log_platform_access_get(){
        $device_id = $this->get('device_id');
        $platform =  $this->get('platform');
        $auth_code = $this->get('auth_code');

        $this->load->model('log_platform_access_model');
        $data = array(
            'token'=>$this->session->session_id,
            'device_id'=>$device_id,
            'add_time'=>date('Y-m-d H:i:s'),
            'platform'=>$platform
        );
        $this->log_platform_access_model->add_log($data);
        $url = "";
        if($device_id){
            $is_open_refer = check_device_open_refer($device_id,$platform);
            if($is_open_refer === true){
                //允许当前platform开门
            }else{
                $this->send_error_response($is_open_refer);
            }
            $this->load->model("equipment_model");
            $device_info = $this->equipment_model->get_info_by_equipment_id($device_id);
            if(!$device_info){
                $this->send_error_response('二维码错误');
            }else if ($device_info['status'] == 0){
                $this->send_error_response('设备已被停用');
            }
            $this->load->config('auth_url',true);
            $redirect = $this->config->item('auth_redirect_url','auth_url');
            $redirect = str_replace('DEVICEID',$device_id,$redirect);
            $redirect = urlencode(str_replace('PLATFORM',$platform,$redirect));
            switch ($platform){
                case 'alipay':
//                    $start = strpos($device_info['qr'],'code=');
//                    $end = strpos($device_info['qr'],'&picSize');
//                    $code = substr($device_info['qr'],$start+5,($end-$start)-5);
//                    $url = 'https://qr.alipay.com/'.$code;
//                    break;
                    //todo 口碑 五芳斋设备
                    $is_wfz_device = get_isv_platform($device_id);
                    if($is_wfz_device == KOUBEI_PLATFORM_ID){
                        $url = $this->config->item('auth_wfz_redirect_url','auth_url');
                        $url = str_replace('DEVICEID',$device_id,$url);
                        $url = str_replace('PLATFORM',$platform,$url);
                        break;
                    }
                case 'wechat':
                    $url = $this->config->item('auth_wechat_redirect_url','auth_url');
                    $url = str_replace('DEVICEID',$device_id,$url);
                    $url = str_replace('PLATFORM',$platform,$url);
                    break;
                case 'fruitday-web':
                    $url = $this->config->item('auth_fruitday_url','auth_url');
                    $url = str_replace('REDIRECT_URI',$redirect,$url);
                    break;
                case 'fruitday-app':
                    $connect_id = $this->get('connect_id');
                    $url = $this->config->item('auth_fruitday_app_url','auth_url');
                    $url = str_replace('DEVICEID',$device_id,$url);
                    $url = str_replace('PLATFORM',$platform,$url);
                    $url = str_replace('CONNECT',$connect_id,$url);
                    break;
                case 'cmb':
                    $this->load->library('CmbChina');
                    $this->load->helper('utils');

                    $cmbchina = new CmbChina();
                    $base_url = $this->config->item("base_url");
                    // $cmb_login_url = str_replace('http', 'https', $base_url) . '/index.php/api/account/cmb_login?device_id=' . $device_id;
                    $cmb_login_url = $base_url . '/index.php/api/account/cmb_login?device_id=' . $device_id;
                    $url = $cmbchina->tplogin_url($cmb_login_url);
                    break;
                case 'gat':
//                    $url = $this->config->item('auth_gat_url','auth_url');
//                    $url = str_replace('REDIRECT_URI',$redirect,$url);
                    $this->gat_user_login( $auth_code, $device_id, $this->get('redirect_uri') );

                    break;
                case 'sodexo':
                    $url = $this->sodexo_login($device_id);
                    break;
                default :
                    $url = $this->config->item('auth_default_url','auth_url');
//                    $url = str_replace('REDIRECT_URI',$redirect,$url);
                    break;
            }
        }
        $this->send_ok_response(urlencode($url));
    }

    /**
     * 第三方授权登录
     */
    public function platform_login_post()
    {
        $connect_id = $this->post('code');
        $device_id = $this->post('device_id');
        $this->check_null_and_send_error($connect_id, "缺少参数，请重新扫码");
        $this->check_null_and_send_error($device_id, "缺少参数，请重新扫码");
        $platform = $this->post('platform');
        $this->check_null_and_send_error($platform, "缺少参数，请重新扫码");
        $type = "";
        $money = 0;
        $this->load->config('auth_url',true);
        if ($platform === "fruitday-app") {
            $type = "fruitday";
            $uid = $connect_id;
            $source = "app";
            $this->load->helper("fruitday_send");
            $rs = get_fruit_user_request_execute(array('uid' => $uid, 'source' => $source));
            if ($rs && $rs['code'] == 200) {
                $user = $rs['data'];
            } else {
                $this->send_error_response($rs['msg'] . "，用户授权错误");
            }
            $open_id = $user['id'];
            $money = $user['money'];
            $redirect_url = $this->config->item('auth_fruitday_index_url','auth_url');
        }else if ($platform === "fruitday-web") {
            $type = "fruitday";
            $this->load->helper("fruitday_send");
            $rs = get_fruit_user_by_code_request_execute(array('token' => $connect_id,'source' => 'wap'));
            if ($rs && $rs['code'] == 200) {
                $user = $rs['data'];
            } else {
                $this->send_error_response($rs['msg'] . "，用户授权错误");
            }
            $open_id = $user['id'];
            $money = $user['money'];
            $redirect_url = $this->config->item('auth_fruitday_index_url','auth_url');
        }
        else if ($platform === "gat") {
            $type = $platform;
            //todo 根据关爱通传过来的code获取用户信息，并且把支付码记录下来，发起支付的时候用到
            $this->load->helper("guanaitong_send");
            $rs = send_gat_user_request($connect_id);

            if ($rs && $rs['code'] == 200) {
                $user = $rs['data'];
            } else {
                $this->send_error_response($rs['msg'] . "，用户授权错误");
            }
            $open_id = $user['id'];
            $money = $user['money'];
            $redirect_url = $this->config->item('auth_gat_redirect_url','auth_url');//这个页面是授权完成跳转的页面，比如果园跳转余额页
        }
        if (!$open_id) {
            $this->send_error_response("用户授权错误,请重试");
        }
        $this->register_and_login($open_id,$type,$device_id,$money,$redirect_url);
    }

    private function register_and_login($open_id,$type,$device_id,$money=0,$redirect_url='', $user=array()){
        $user_info = $this->user_model->get_user_info_by_open_id($open_id, $type);
        if(!$user_info){
            $user_data = array(
                "user_name" => isset($user['username']) ? $user['username'] : "",
                "avatar" => isset($user['avatar']) ? $user['avatar'] : "",
                "city" => isset($user['city']) ? $user['city'] : "",
                "province" => isset($user['province']) ? $user['province'] : "",
                "gender" => isset($user['gender']) ? $user['gender'] : "",
                "source" => $type,
                "reg_time" => date("Y-m-d H:i:s"),
                "open_id" => $open_id,
                "is_black" => 0,
                "equipment_id" => 0,
                "mobile" => $user['mobile'],
            );
            $rs = $this->user_model->adUser($user_data,$device_id);
            if ($rs) {
                $user_info = $this->user_model->get_user_info_by_id($rs);
                $this->login_user($user_info,$money,$redirect_url,$device_id);
            } else {
                $this->send_error_response("用户信息注册异常");
            }
        }else{
            $this->login_user($user_info,$money,$redirect_url,$device_id);
        }
    }

    /**
     * 微信免密协议回调
     */
    public function wecht_notify_entrust_post(){
        if($_GET['platform']){
            $platform = $_GET['platform'];
            unset($_GET['platform']);
        }
        $config =  get_platform_config($platform);
        write_log("wechat agreement notify:" . var_export($GLOBALS['HTTP_RAW_POST_DATA'], 1),'debug');
        $this->load->helper('wechat_send');
        $notify = wechat_notify($config);
        if($notify){
            if ($notify->data["result_code"] == "FAIL") {

            }else if($notify->data["result_code"] == "SUCCESS") {

                $data["agreement_no"] = $notify->data["contract_id"];
                $data["sign_time"] = $notify->data["operate_time"];
                $data["partner_id"] = $notify->data["mch_id"];
                $data['sign_detail'] = $notify->data;

                $ret = $this->user_agreement_model->update_agreement_sign($notify->data["openid"],$data,'wechat',$notify->data["mch_id"]);
                if($ret){
                    $this->update_user_cache($ret['user_id'],$ret);
                }
            }
        }
    }



    /**
     * 微信免密协议-解约
     */
    public function wechat_deletecontract_post(){
        write_log(" wechat_deletecontract :" . var_export($GLOBALS['HTTP_RAW_POST_DATA'], 1),'debug');
        $this->load->helper('wechat_send');
        if($_GET['platform']){
            $platform = $_GET['platform'];
            unset($_GET['platform']);
        }
        $config =  get_platform_config($platform);
        $notify = wechat_notify($config);
        if($notify){
            if ($notify->data["result_code"] == "FAIL") {

            }else if($notify->data["result_code"] == "SUCCESS") {
                //查询open_id是否是公众号
                $exist = $this->user_model->get_user_info_by_open_id($notify->data["openid"],'wechat');
                if($exist){
                    $is_program = 0;
                } else{
                    $user = $this->user_model->get_user_info_by_program_id($notify->data["openid"], "wechat");
                    if($user){
                        $is_program = 1;
                    }else{
                        return;
                    }
                }

                $ret = $this->user_agreement_model->delete_agreement_sign($notify->data["openid"],'wechat',$notify->data["contract_id"],$is_program);
                if($ret){
                    $this->update_user_cache($ret['user_id'],$ret);
                }
            }
        }
    }

    /**
     * 支付宝解约-推送
     */
    public function delete_sign_post(){
        write_log(" delete_sign  :" . var_export($_REQUEST, 1),'info');

        $this->load->model("receive_alipay_log_model");
        $msg_data = array(
            'msg_type'=>'alipay-del-sign',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);

        $sign = true;//getSignVeryfy($_REQUEST,$_REQUEST['sign']);
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


    /**
     * 获取微信JSAPI签名信息
     */
    public function getjsapisign_get()
    {
        $noncestr = $this->get("noncestr", "string");

        $timestamp = $this->get("timestamp");
        $url = $this->get("url");

        $config =  get_platform_config(1);//TODO 记住修改配置

        $this->load->helper('wechat_send');
        $result = get_wx_api_sign($config,$noncestr,$timestamp,$url);
        $result['timestamp'] = $timestamp;
        $result['noncestr'] = $noncestr;
        $result['url'] = $url;

        $result['result'] = 'ok';
        $this->send_ok_response($result);
    }


    /**
     * 小程序 code 获取用户信息
     */
    public function wx_jscode2session_post(){
        $js_code = $this->post('wxLoginCode');
        $userInfo = $this->post('rawData');
        $encrypted_data = $this->post('encryptedData');
        $iv = $this->post('iv');


        $this->check_null_and_send_error($js_code,'js_code 不能为空');
        $this->load->helper('wechat_send');
        $config  = get_platform_config_by_device_id(0);
        $data= get_wx_jscode2session($js_code,$config);
        write_log(var_export($data,1));
        if(!$data){
            $this->send_error_response("系统异常");
        } else if(isset($data['errcode'])){
            $this->send_error_response($data['errmsg']);
        }else{
            $userInfo = json_decode($userInfo,1);
            $exist  = $this->user_model->is_registered($data['openid'],'wechat-program');
            if($exist){
                //登录
                $user_id = $exist['user_id'];
            }else{
                $user_data = array(
                    'user_name'=>$userInfo['nickName'],
                    'gender'=>$userInfo['gender'],
                    'city'=>$userInfo['city'],
                    'province'=>$userInfo['province'],
                    'avatar'=>$userInfo['avatarUrl'],
                    'reg_time'=>date('Y-m-d H:i:s')
                );
                $user_id = $this->user_model->adUser($user_data,$data['openid'],'wechat-program');
            }
            $this->login_user($user_id);
        }
    }
}

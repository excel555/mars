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
        $this->load->model("user_model");
        $this->load->model('user_agreement_model');
        $this->load->helper("utils");
        $this->load->helper("aop_send");
        $this->load->helper("mapi_send");
        $this->load->helper('koubei_send');
        $this->load->helper("message");
        $this->load->helper("http_request");
        $this->load->library("aop/request/AlipaySystemOauthTokenRequest");
        $this->load->library("aop/request/AlipayUserUserinfoShareRequest");
    }

    public function get_info_get()
    {
        $rs = $this->get_curr_user();

//        $rs = $this->user_model->get_user_info_by_id($user['id']);
        if ($rs) {
            $this->load->model("shipping_permission_model");
            $old = $this->shipping_permission_model->old_deliver_permission($rs['mobile']);
            if($old){
                $rs['view_old_deliver'] = 1;
            }else{
                $rs['view_old_deliver'] = 0;
            }
            $rs['s_admin_id'] = $this->get_deliver_permission($rs['id']) ? 1 : 0;
            $rs['platforms'] = $this->platforms;
            $this->send_ok_response($rs);
        } else {
            $this->send_error_response("没有找到该用户信息");
        }
    }
    public function auth_alipay_login_post()
    {
        $auth_code = $this->post('auth_code');
        $device_id = $this->post('device_id');
        $mianmi = $this->post('mianmi');//是否需要开通免密
        if (!empty ($auth_code)) {
            $token = $this->requestToken($auth_code,$device_id);
            write_log(var_export($token, 1));
            if (isset ($token->alipay_system_oauth_token_response)) {
                $user_id = $token->alipay_system_oauth_token_response->user_id;
                $user = $this->user_model->get_user_info_by_open_id($user_id, "alipay");
                if (!$user || empty($user['mobile'])) {
                    $token_str = $token->alipay_system_oauth_token_response->access_token;
                    write_log("token_str:" . var_export($token_str, 1));
                    $user_info = $this->requestUserInfo($token_str,$device_id);
                    write_log("UserInfo:" . var_export($user_info, 1));
                    if (isset ($user_info->alipay_user_info_share_response)) {
                        $user_info_resp = $user_info->alipay_user_info_share_response;
                        $user_data = array(
                            "user_name" => isset($user_info_resp->nick_name) ? $user_info_resp->nick_name : "",
                            "avatar" => isset($user_info_resp->avatar) ? $user_info_resp->avatar : "",
                            "city" => isset($user_info_resp->city) ? $user_info_resp->city : "",
                            "province" => isset($user_info_resp->province) ? $user_info_resp->province : "",
                            "gender" => isset($user_info_resp->gender) ? $user_info_resp->gender : "",
                            "source" => 'alipay',
                            "reg_time" => date("Y-m-d H:i:s"),
                            "open_id" => $user_info_resp->user_id,
                            "is_black" => 0,
                            "equipment_id" => 0,
                            "mobile"=> isset($user_info_resp->mobile) ? $user_info_resp->mobile : ""
                        );
                        write_log(var_export($user_data, 1));

                        if($user && empty($user['mobile']) && !empty($user_data['mobile'])){
                            //更新手机号
                            $this->user_model->update_mobile($user['id'],$user_data['mobile']);
                        }else if(! $user){
                            $rs = $this->user_model->adUser($user_data,$device_id);
                            write_log("rs:" . var_export($rs, 1));
                            if ($rs) {
                                $user = $this->user_model->get_user_info_by_id($rs);

                            } else {
                                $this->send_error_response("用户信息注册异常");
                            }
                        }
                        $user['mianmi'] = $mianmi;
                        $this->login_user($user['id'],$device_id);
                    } else {
                        write_log("获取不到用户信息:" . var_export($user_info, 1));
                        $this->send_error_response($user_info->error_response->sub_msg);
                    }

                } else {
                    $user['mianmi'] = $mianmi;
                    $this->login_user($user['id'],$device_id);
                }

            } elseif (isset ($token->error_response)) {
                // 记录错误返回信息
                write_log($token->error_response->sub_msg);
                $this->send_error_response($token->error_response->sub_msg);
            }
        } else {
            $this->send_error_response("缺少auth_code参数");
        }
    }

    private function requestToken($auth_code,$device_id)
    {
        $AlipaySystemOauthTokenRequest = new AlipaySystemOauthTokenRequest ();
        $AlipaySystemOauthTokenRequest->setCode($auth_code);
        $AlipaySystemOauthTokenRequest->setGrantType("authorization_code");
        $platform_config = get_platform_config_by_device_id($device_id);
//        write_log('config:'.var_export($platform_config,1));
        $is_isv = get_isv_platform($device_id);
        if($is_isv){
            $result = koubei_request_execute_for_obj($AlipaySystemOauthTokenRequest,null,$platform_config);
        }else{
            $result = aopclient_request_execute($AlipaySystemOauthTokenRequest,null,$platform_config);
        }
        return $result;
    }


    /**
     * debug in broswer
     * use login for url
     */
    public function test_login_get()
    {
        $id = $this->get("id");
        $this->login_user($id,0);
    }


    private function login_user($user_id,$device_id)
    {
        $user = $this->user_model->get_user_info_by_id($user_id);
        if($user['source'] === "alipay"  || $user['source'] === "wechat") {
            $partner_id = get_3rd_partner_id_by_device_id($device_id, $user['source']);
            $agreements = $this->user_agreement_model->get_user_agreement_3rd($user['id']);
            write_log('$agreements=>'.var_export($agreements,1));
            if ($agreements) {
                foreach ($agreements as $agreement) {
                    $thirdpart_id = $agreement['thirdpart_id'];
                    $user[$thirdpart_id . '_agreement_no'] = $agreement['agreement_no'];
                }
            }
            write_log($partner_id.'_agreement_no=>'.var_export($user[$partner_id.'_agreement_no'],1));
        }
        write_log("login_user".var_export($user,1).",device_id=".$device_id);
        if ($user) {
            $session_id = session_id_2_user($user['id']);
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



    private function requestUserInfo($token,$device_id)
    {
        $AlipayUserUserinfoShareRequest = new AlipayUserUserinfoShareRequest ();
        $platform_config = get_platform_config_by_device_id($device_id);
        $is_isv = get_isv_platform($device_id);
        //todo 口碑获取用户信息
        if($is_isv){
            $result = koubei_request_execute_for_obj($AlipayUserUserinfoShareRequest,$token,$platform_config);
        }else{
            $result = aopclient_request_execute($AlipayUserUserinfoShareRequest, $token,$platform_config);
        }
        return $result;
    }


    /**
     * 支付宝签约查询
     */
    private function get_user_agreement_query($alipay_user_id,$device_id)
    {
        $config = get_platform_config_by_device_id($device_id);
        //todo 口碑 签约查询
        $exist_koubei = get_isv_platform($device_id);
        if($exist_koubei){
            $result =  koubei_agreemt_query($alipay_user_id,$config);
            write_log('koubei_agreemt_query=>'.var_export($result,1));
            if (!$result) {
                $this->send_error_response("查询用户是否签约失败，请联系管理员");
            }else if($result['code']== 10000 && $result['agreement_no']){
                $data = array(
                    'principal_id'=>$result['principal_id'],//openid
                    'thirdpart_id'=>$config['auth_app_id'],//协议id
                    'agreement_no'=>$result['agreement_no'],
                    'scene'=>$result['sign_scene'],
                    'sign_time'=>$result['sign_time'],
                );
                return $data;
            }else{
                return false;
            }
        }else{
            $result = mapi_agreement_query_request($alipay_user_id,$config);
            if (!$result) {
                $this->send_error_response("查询用户是否签约失败，请联系管理员");
            } else if ($result['is_success'] != 'T' || !isset($result['response']['userAgreementInfo']['agreement_no'])) {
                return false;
            } else {
                return $result['response']['userAgreementInfo'];
            }
        }
    }


    /**
     * wechat签约查询
     */
    private function get_wechat_user_agreement_query($wechat_user_id,$device_id,$is_program)
    {
        $this->load->helper('wechat_send');
        $platform_config = get_platform_config_by_device_id($device_id);
        if($is_program == 'program'){
            $platform_config['wechat_appid'] =  $platform_config['wechat_program_appid'];
            $platform_config['wechat_secret'] = $platform_config['wechat_program_secret'];
        }
        $rs = get_querycontract_by_openid($wechat_user_id,$platform_config);
        return $rs;
    }




    public function goto_sign_get(){
        $refer = $this->get('refer');
        $device_id = $this->get('device_id');
        $this->send_ok_response($this->get_agreement_sign_url($refer,$device_id));

    }

    private function get_agreement_sign_url($refer = 'alipay',$device_id)
    {
        $platform_config = get_platform_config_by_device_id($device_id);
        if(empty($refer) || $refer === 'alipay'){
            //todo 口碑获取签约URL
            $exist_koubei = get_isv_platform($device_id);
            if($exist_koubei){
                return koubei_request_agreement_url($platform_config,$device_id);
            }else{
                return mapiClient_request_get_agreement_url($platform_config,$device_id);
            }
        }elseif ($refer === 'wechat'){

            $this->load->helper('wechat_send');
            $data= array(
                'contract_code'=>uuid_32(),
                'contract_display_account'=>'魔盒CITYBOX微信免密支付',
                'request_serial'=>uuid_32(),
            );
            return entrustweb($data,$platform_config);
        }
    }

    /**
     * 支付宝-签免密协议异步通知
     */
    public function notify_agree_post()
    {
        $config = get_platform_config_by_device_id($_REQUEST['device_id']);
        $sign = getSignVeryfy($_REQUEST,$_REQUEST['sign'],$config);
        write_log("agreement notify:" .','. var_export($_REQUEST, 1).',config=>'.var_export($config,1).',rs='.var_export($sign,1));
        if ($sign && isset($_REQUEST['agreement_no'])) {
            $ret = $this->user_agreement_model->update_agreement_sign($_REQUEST['alipay_user_id'],$_REQUEST,'alipay',$_REQUEST['partner_id']);
            if($ret){
                update_user_cache($ret['user_id'],$ret);
            }
            echo "success";
        }else{
            echo "fail";
        }
    }

    public function return_get()
    {
        write_log(" return req:" . var_export($_REQUEST, 1));
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
                update_user_cache($ret['user_id'],$ret);
            }
            echo "success";
        }else{
            echo "fail";
        }
    }

    /**
     * 修改用户为黑名单用户
     */
    function update_user_black_get(){
        $uid = $this->get('uid');
        $this->check_null_and_send_error($uid,"uid不能为空");
        $update_data = array('is_black'=>1);
        $this->update_user_cache($uid,$update_data);
        $this->send_ok_response("succ");
    }

}

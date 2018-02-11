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

        $this->platforms = array(
            array("id"=>'1' ,"name"=> '上海鲜动'),
            array("id"=>'2' , "name"=> '北京波之鸿'),
            array("id"=>'3' , "name"=> '技术测试商户'),
            array("id"=>'4' , "name"=> '广州天天果园'),
            array("id"=>'6' , "name"=> '杭州鲜动'),
            array("id"=>'8' , "name"=> '武汉鲜动'),
            array("id"=>'7' , "name"=> '北京鲜动'),
            array("id"=>'9' , "name"=> '白领易生活科技（北京）有限公司'),
            array("id"=>'10' , "name"=> '上海天天果园'),
            array("id"=>'11' , "name"=> 'WinBox'),
            array("id"=>'12' , "name"=> '广州市钱大妈农产品有限公司'),
            array("id"=>'13' , "name"=> '广州食安菜妈信息科技有限公司'),
            array("id"=>'14' , "name"=> '浙江五芳斋'),
            array("id"=>'15' , "name"=> '潍坊元佩商贸有限公司'),
            array("id"=>'16' , "name"=> '叶氏兄弟'),
            );
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

    function warn_user_info_post(){
        $user = $this->get_curr_user();
        $mobile = $this->post('mobile');
        $email = $this->post('email');
        $wechat_id = $this->post('wechat_id');
        $name = $this->post('name');
        $platform_id = $this->post('platform_id');
        $data =array(
            'name'=>$name,
            'email'=>$email,
            'wechat_id'=>$wechat_id,
            'mobile'=>$mobile,
            'platform_id'=>$platform_id,
            'create_time'=>date("Y-m-d H:i:s")
            );
        $this->load->model('warn_user_model');
        $rs = $this->warn_user_model->insert_user($data);
        if($rs){
            $this->send_ok_response('succ');
        }else{
            $this->send_error_response("用户已存在，不能重复提交");
        }
    }
    /**
     * 获取用户余额
     */
    function amount_info_get(){

        $this->load->model('user_acount_model');
        $this->load->model('recharge_online_model');
        $user = $this->get_curr_user();
        $user_db = $this->user_model->get_user_info_by_id($user['id']);
        $user['acount_id'] = $user_db['acount_id'];
        $this->update_curr_user($user);
        $uid = $user['id'];
        $user_account = $this->user_acount_model->get_user_info_by_id($uid);
        $money_list = $this->recharge_online_model->get_online_config();
        $ret = array(
            'money'=>$user_account['yue'],
            'config'=>array_values($money_list)
        );
        $this->send_ok_response($ret);
    }

    /**
     * 充值金额
     */
    function recharge_money_post(){
        $user = $this->get_curr_user();
        $money = $this->post('money');
        $refer = $this->post('refer');
        $this->load->model('recharge_online_model');
        $acount_id = $user['acount_id'];
        $rs = $this->recharge_online_model->create_recharge($acount_id, $money, $refer,$user['id']);
        $this->send_ok_response($rs);
    }
    /**
     * 卡券充值
     */
    function recharge_card_post(){
        $user = $this->get_curr_user();
        $card_num = $this->post('card_num');
        $this->check_null_and_send_error($card_num,"充值码不能为空");
        $this->load->model('recharge_cards_model');
        $acount_id = $user['acount_id'];
        $rs = $this->recharge_cards_model->recharge_card($card_num, $acount_id,$user['id']);
        $this->send_ok_response($rs);
    }


    /**
     * 余额明细
     */
    function amount_detail_get(){
        $user = $this->get_curr_user();
        $this->load->model('user_acount_yue_model');
        $account_id =$user['acount_id'];
        $rs = $this->user_acount_yue_model->get_list($account_id);
        $this->send_ok_response($rs);
    }
    function coupon_list_get(){
        $status = $this->get('status');
        $page = (int)$this->get("page");
        $device_id = $this->get('deviceId');
        $platform_id = 0;
        if ($device_id){
            $equipment_sql = "select platform_id,type from cb_equipment where equipment_id = '".$device_id."'";
            $equipment = $this->db->query($equipment_sql)->row_array();
            if ($equipment){
                $platform_id = $equipment['platform_id'];
            }
        }
        if($page<=0)
            $page = 1;
        $page_size = $this->get("page_size");
        if(!$page_size)
            $page_size = PAGE_SIZE;
        $user = $this->get_curr_user();
        $this->load->model('card_model');
        $today = date('Y-m-d');
        $where_in_coupon = array(0,$platform_id);
        $rs = $this->card_model->list_card_by_status($user["id"],$status,$page,$page_size,$where_in_coupon);
        foreach ($rs as &$v){
            if($v['is_used'] == 1){
                $v['status'] = 1;
            }else if($v['to_date'] < date('Y-m-d')){
                $v['status'] = 0;
            }else{
                $v['status'] = 2;
            }
            //营销限制 1.无限制 2.不与运营活动重复
            //$v['use_with_sales'] = $v['use_with_sales'];
            //商品限制  0.无限制 1.指定商品 2.指定品类
            //$v['product_limit_type'] = $v['product_limit_type'];
            if ($v['product_limit_type'] == 1){
                $product_limit_value = $v['product_id'];
                $where_value = '('.$product_limit_value.')';
                $v_sql = "select * from cb_product where id in ".$where_value."";
                $products = $this->db->query($v_sql)->result_array();
                $limit_products = '';
                foreach($products as $eachProduct){
                    $limit_products = $limit_products.$eachProduct['product_name'].',';
                }
                $limit_products = substr($limit_products,0,strlen($limit_products)-1);
                $v['product_limit_value'] = $limit_products;
            } elseif($v['product_limit_type'] == 2){
                $product_limit_value = $v['class_id'];
                $where_value = '('.$product_limit_value.')';
                $v_sql = "select * from cb_product_class where id in ".$where_value."";
                $product_classes = $this->db->query($v_sql)->result_array();
                $limit_product_classes = '';
                foreach($product_classes as $eachProductclass){
                    $limit_product_classes = $limit_product_classes.$eachProductclass['name'].',';
                }
                $limit_product_classes = substr($limit_product_classes,0,strlen($limit_product_classes)-1);
                $v['product_limit_value'] = $limit_product_classes;
            }
        }
        $this->send_ok_response($rs);
    }
    
    //第三方优惠券列表
    function thirdpartycoupon_list_get(){
        $status = $this->get('status');
        $page = (int)$this->get("page");
        $device_id = $this->get('deviceId');
        $platform_id = 0;
        if ($device_id){
            $equipment_sql = "select platform_id,type from cb_equipment where equipment_id = '".$device_id."'";
            $equipment = $this->db->query($equipment_sql)->row_array();
            if ($equipment){
                $platform_id = $equipment['platform_id'];
            }
        }
        if($page<=0)
            $page = 1;
        $page_size = $this->get("page_size");
        if(!$page_size)
            $page_size = PAGE_SIZE;
        $user = $this->get_curr_user();
        $this->load->model('card_model');
        $where_in_coupon = array(0,$platform_id);
        $rs = $this->card_model->list_thirdpartycard_by_status($user["id"],$status,$page,$page_size,$where_in_coupon);
        foreach ($rs as &$v){
            if($v['is_used'] == 1){
                $v['status'] = 1;
            }else if($v['end_date'] < date('Y-m-d H:i:s')){
                $v['status'] = 0;
            }else{
                $v['status'] = 2;
            }
            $v['begin_date'] = date('Y.m.d',strtotime($v['begin_date']));
            $v['to_date'] = date('Y.m.d',strtotime($v['end_date']));
        }
        $this->send_ok_response($rs);
    }

    /**
     * 根据设备ID获取配置
     */
    function get_config_by_device_id_get(){
        $device_id = $this->get("device_id");
        $config = get_platform_config_by_device_id($device_id);
        $ret = array(
            'app_id'=>$config['app_id'],
            'wechat_id'=>$config['wechat_appid']
        );
        $this->send_ok_response($ret);
    }
    /**
     * 获取用户验证码
     */
    public function get_vcode_get()
    {
        $mobile = $this->get("mobile");
        $type = $this->get("type");
        $this->check_null_and_send_error($mobile, "手机号不能为空");
        $this->check_null_and_send_error($type, "验证码类型不能为空");
        $this->check_mobile_and_send_error($mobile, "手机号格式不正确");

        $key = 'phone' . $mobile;
        $store_code = $this->cache->get($key);
        if ($store_code) {
            $this->send_error_response(Account::VCODE_LIVE_SECOND . "s内只允许发送一次验证码");
        }

        $vcode = rand(100000, 999999);
        $this->load->helper('sms_send');
        $this->config->load('sms', TRUE);
        $content = $this->config->item("sms_bind_content","sms");
        $msg = array(
            'mobile'=>$mobile,
            'message'=>$content.$vcode,
            );
        $rs_sms = send_msg_execute(json_encode($msg));
        if($rs_sms){
            write_log("验证码" . $key . ":" . $vcode);
            $rs = $this->cache->save($key, $vcode, Account::VCODE_LIVE_SECOND);
            if ($rs) {
                $this->send_ok_response(['status' => true, "message" => "验证码发送成功"]); // OK (200) being the HTTP response code
            } else {
                write_log("验证码" . $key . ":" . $vcode."，短信发送失败！",'crit');
                $this->send_error_response("系统异常,稍后重试");
            }
        }else{
            $this->send_error_response("短信发送失败");
        }
    }

    /**
     * 绑定手机号
     */
    public function bind_post(){
        $mobile = $this->post('mobile');
        $vcode = $this->post('vcode');
        $this->check_null_and_send_error($mobile, "手机号不能为空");
        $this->check_null_and_send_error($vcode, "验证码不能为空");

        if(!is_mobile($mobile)){
            $this->send_error_response('手机号格式不正确');
        }
        $key = 'phone' . $mobile;
        $store_code = $this->cache->get($key);
        if ($store_code != $vcode) {
            $this->send_error_response("验证码错误");
        }
        $this->cache->delete($key);
        $user = $this->get_curr_user();
        $rs = $this->user_model->update_mobile($user['id'],$mobile);
        $user['mobile'] = $mobile;
        $this->update_curr_user($user);
        $this->send_ok_response($rs);
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
                        $this->login_user($user,0,'',$device_id);
                    } else {
                        write_log("获取不到用户信息:" . var_export($user_info, 1));
                        $this->send_error_response($user_info->error_response->sub_msg);
                    }

                } else {
                    $user['mianmi'] = $mianmi;
                    $this->login_user($user,0,'',$device_id);
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

    public function auth_wechat_login_post()
    {
        $auth_code = $this->post('auth_code');
        $device_id = $this->post('device_id');
        $mianmi = $this->post('mianmi');//是否需要开通免密
        if (!empty ($auth_code)) {
            $this->load->helper('wechat_send');
            $platform_config = get_platform_config_by_device_id($device_id);
            write_log("获取用户基本信息失败" . var_export($platform_config, 1),'debug');
            $data = getTokenByCode($auth_code,$platform_config);

            write_log('token=>'.var_export($data, 1));
            if (isset ($data ['errcode']) && $data ['errcode'] != 0) {
                write_log("获取token失败" . var_export($data, 1));
                $this->send_error_response("获取token失败");
            }
            $access_token = $data ['access_token'];
            $openid = $data ['openid'];
            $expires = $data ['expires_in'];
            $unionid = $data ['unionid'];

            $data = getUserInfoByTokenAndOpenid($access_token, $openid,$platform_config);
            write_log(var_export($data, 1));
            if (isset ($data ['errcode']) && $data ['errcode'] != 0) {
                write_log("获取用户基本信息失败" . var_export($data, 1));
                $this->send_error_response("获取用户基本信息失败" . $data ['errmsg']);
            }

            $user = $this->user_model->get_user_info_by_union_id($unionid);
            if (!$user) {
                $user = $this->user_model->get_user_info_by_open_id($openid,'wechat');
                if($user){
                    if(!$user['unionid'] || !$user['avatar'] || !$user['user_name']){
                        $this->user_model->update_user_info($user['id'],array("unionid" => $unionid,'user_name'=>$data ['nickname'],"avatar" => $data ['headimgurl']));
                        $user['mianmi'] = $mianmi;
                        $this->login_user($user,0,'',$device_id);
                    }
                }else{
                    $user_data = array(
                        "user_name" => $data ['nickname'],
                        "avatar" => $data ['headimgurl'],
                        "city" => $data['city'],
                        "province" => $data['province'],
                        "gender" => $data['sex'],
                        "source" => 'wechat',
                        "reg_time" => date("Y-m-d H:i:s"),
                        "open_id" => $data['openid'],
                        "is_black" => 0,
                        "equipment_id" => 0,
                        "unionid" => $unionid,
                    );
                    write_log('userInfo:'.var_export($user_data, 1));
                    $rs = $this->user_model->adUser($user_data,$device_id);
                    write_log(" 返回结果：" . var_export($rs, 1));
                    if ($rs) {
                        $user = $this->user_model->get_user_info_by_id($rs);
                        $user['mianmi'] = $mianmi;
                        $this->login_user($user,0,'',$device_id);
                    } else {
                        $this->send_error_response("用户信息注册异常");
                    }
                }
            } else {
                if(!$user['open_id'] || !$user['avatar'] || !$user['user_name']){
                    $this->user_model->update_user_info($user['id'],array("open_id" => $openid,'user_name'=>$data ['nickname'],"avatar" => $data ['headimgurl']));
                }
                $user['mianmi'] = $mianmi;
                $this->login_user($user,0,'',$device_id);
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
        $user = $this->user_model->get_user_info_by_id($id);
        $this->login_user($user);
    }

    private function login_user($user,$fruitday_moeny = 0,$redirect_url = '',$device_id='')
    {
        write_log("login_user".var_export($user,1).",device_id=".$device_id);
        if (!$user) {
            //用户未注册
            $this->response([
                'status' => FALSE,
                'message' => "用户未注册"
            ], REST_Controller::HTTP_NOT_FOUND);
        }
        if ($user["is_black"]) {
            //用户黑名单
            $this->response([
                'status' => FALSE,
                'message' => "你已进入黑名单"
            ], REST_Controller::HTTP_BAD_REQUEST);
        }
        if($user['source'] === "alipay"  || $user['source'] === "wechat") {
            //todo 口碑 mapi_parnert 因为没有 mapi_parnert_id 所以在配置上面使用auth_app_id代替
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

        if(isset($user['mianmi']) &&  $user['mianmi'] == 1){
            //登录时不检查免密
        } else if($user['source'] === "alipay" ){
            if(empty($user[$partner_id.'_agreement_no'])){
                $sign = $this->get_user_agreement_query($user['open_id'],$device_id);
                if ($sign) {
                    $ret = $this->user_agreement_model->update_agreement_sign($sign['principal_id'],$sign,'alipay',$sign['thirdpart_id']);
                    if($ret){
                        foreach ($ret as $kr=>$r){
                            $user[$kr] = $r;
                        }
                    }
                } else {
                    $this->send_error_response($this->get_agreement_sign_url($user['source'],$device_id));
                }
            }
        } else if($user['source'] === "wechat" ){
            if(empty($user[$partner_id.'_agreement_no'])){
                $sign = $this->get_wechat_user_agreement_query($user['open_id'],$device_id,$user['wechat_type']);//检查用户是否签约
                write_log('微信签约查询 '.var_export($sign,1));
                if ($sign['return_code']=='SUCCESS' && $sign['result_code']=='SUCCESS' && $sign['contract_state']=='0') {
                    //更新用户的签约号
                    $data["agreement_no"] = $sign["contract_id"];
                    $data["sign_time"] = $sign["contract_signed_time"];
                    $data["partner_id"] = $sign["mch_id"];
                    $data['sign_detail'] = $sign;
                    $ret = $this->user_agreement_model->update_agreement_sign($user['open_id'],$data,'wechat',$data['partner_id'],$user['wechat_type'] == 'program' ? 1 : 0);
                    if($ret){
                        foreach ($ret as $kr=>$r){
                            $user[$kr] = $r;
                        }
                    }
                } else {
                    if($user['wechat_type'] == 'program'){
                        //小程序签约
                        $this->load->helper('wechat_send');
                        $config  = get_platform_config_by_device_id($device_id);
                        $qianyue_data = array(
                            'contract_code'=>uuid_32(),
                            'contract_display_account'=>'魔盒CITYBOX微信免密支付',
                            'request_serial'=>uuid_32(),
                        );
                        $ret_qianyue = get_program_entrust($qianyue_data,$config);
                        $ret_qianyue['user'] = $user;
                        $this->send_error_response("qianyue",$ret_qianyue);
                    }else {
                        $this->send_error_response($this->get_agreement_sign_url($user['source'], $device_id));
                    }

                }
            }
        }

        $user['fruitday_money'] = $fruitday_moeny;//设置天天果园的余额
        $user['redirect_url'] = $redirect_url;//设置跳转页面
        $user['role'] = 'user';
        $session_id = $this->session_id_2_user($user['id']);
        $user_cache_key = 'user_'.$session_id;
        $this->cache->save($user_cache_key,$user,604800);//记录用户保存7天

        if ($user) {
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
                $this->update_user_cache($ret['user_id'],$ret);
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
     * 记录用户通过管理员角色登陆
     */
    public function rec_admin_post(){
        $type = $this->post("admin");
        $is_new = $this->post("is_new");
        $qrcode = $this->post("qrcode");//https://qr.alipay.com/pvx08418jib52xm9wzkvl0d
        $this->check_null_and_send_error($qrcode,"缺少参数，请重新扫码");
        $user = $this->get_curr_user();
        if($this->get_deliver_permission($user['id']) && $type == "1"){
            $role ="admin";
        }else{
            $role ="user";
        }
        $key = "role_". $user['open_id'];
        $this->cache->save($key, $role, REST_Controller::USER_LIVE_SECOND);
        $device_id = "";
        $type = "";
        $this->load->model("equipment_model");
        $tmp_arr = explode("/",$qrcode);
        if(check_str_exist($qrcode,'alipay')){
            $type = $user['source'];
            //扫码属于支付宝
            if(count($tmp_arr) == 4){
                $qrcode = "code=".$tmp_arr['3'];
            }
            $rs_device = $this->equipment_model->get_box_info_by_qr($qrcode,$type);
            if($rs_device){
                $device_id = $rs_device['equipment_id'];
            }else{
                $this->send_error_response("参数错误，请重新扫码");
            }
        }else if(check_str_exist($qrcode,'p.html?d=')){
            //通用二维码肯定带有p.html?d=
            $type = "alipay";
            $tmp_arr = explode("d=",$qrcode);
            if(count($tmp_arr) == 2){
                $device_id = $tmp_arr[1];
            }
//            $this->send_ok_response(array('status'=>'redirect_p','message'=>'请求开门中...','redirect_url'=>$qrcode,'device_id'=>$device_id));
//            $this->send_error_response("开门失败，请扫描支付宝专用二维码");
        }else if(check_str_exist($qrcode,'fruitday')){
            //果园开门补货
            $type = "alipay";
            $tmp_arr = explode("?deviceId=",$qrcode);
            if(count($tmp_arr) == 2){
                $tmp_arr =  explode('&scan',$tmp_arr[1]);
                if(count($tmp_arr) == 2){
                    $device_id = $tmp_arr[0];
                }
            }
        } else{
            $this->send_ok_response(array('status'=>'redirect_p','message'=>'请求开门中...','redirect_url'=>$qrcode,'device_id'=>$device_id));
        }

        if(empty($device_id)){
            $this->send_error_response("参数错误，请重新扫码");
        }else{
            if($role == 'admin' && $is_new == '1'){
                //新版补货流程
                $this->send_ok_response(array('status'=>'redirect','message'=>'跳转管理员页面','device_id'=>$device_id));
            }else{
                //todo 口碑 五芳斋开门
                $is_wfz_device = get_isv_platform($device_id);
                if($is_wfz_device == KOUBEI_PLATFORM_ID && $role == "user"){
                    $this->load->config('auth_url',true);
                    $url = $this->config->item('auth_wfz_default_url','auth_url');
                    $url = str_replace('DEVICEID',$device_id,$url);
                    $this->send_ok_response(array('status'=>'error','message'=>'五芳斋设备开门', 'url'=>$url));
                }
                $this->load->library("Device_lib");
                $device = new Device_lib();
                $status = $device->request_open_door($device_id,$user,$type);
                if($status == 'succ'){
                    $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...','device_id'=>$device_id));
                }elseif($status['status']=='qianyue'){
                    $this->send_ok_response(array('status'=>'error','message'=>'未签约...', 'url'=>$status['url']));
                }elseif ($status['status']=='limit_open_refer'){
                    //开门方式被限制了
                    $this->send_ok_response(array('status'=>'limit_open_refer','message'=>$status['error']['msg'],'redirect_url'=>$status['error']['redirect_url']));
                }else{
                    $this->send_error_response($status."，请稍后重试");
                }
            }

        }
    }
    private function get_deliver_permission($uid){
        $this->load->model("shipping_permission_model");
        return $this->shipping_permission_model->canOpen($uid);
    }

    /**
     * 芝麻信用授权
     */
    public function zmxy_auth_get()
    {
        $this->load->helper("zmxy_send");
        $this->load->library("zmxy/request/ZhimaAuthInfoAuthorizeRequest");
        $request = new ZhimaAuthInfoAuthorizeRequest();
        $request->setChannel("apppc");
        $request->setPlatform("zmop");
        $request->setIdentityType("1");// 必要参数 1:按照手机号进行授权2:按照身份证+姓名进行授权
        $identityParam = array('mobileNo'=>'15601686678');
        $request->setIdentityParam(json_encode($identityParam));// 必要参数
        $biz_param = array(
           'auth_code'=> 'M_H5',
            'channelType'=>'apppc',
            'state'=>'ttgy'// state是商户自定义的数据，页面授权接口会原样把这个数据返回个商户
        );
        $request->setBizParams(json_encode($biz_param));//
        $url = generate_page_redirect_invoke_url($request);
        $this->send_ok_response($url);
    }
    //芝麻信用授权回调
    public function zmxy_return_get(){
        $this->load->helper("zmxy_send");
        $params = $_REQUEST['params'];
        $sign = $_REQUEST['sign'];
        $rs = decrypt_and_verify_sign($params,$sign);
        $rs = explode("&",$rs);
        $this->send_ok_response($rs);
    }

    /**
     * 第三方授权
     */
    public function auth_platform_login_post()
    {
        $connect_id = $this->post('connect_id');
        $device_id = $this->post('device_id');
        $this->check_null_and_send_error($connect_id,"缺少参数，请重新扫码");
//        $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");

        $platform = $this->post('platform');
        $type = "";
        if(!$platform)
            $platform = "fruitday-web";

        if($platform === "fruitday-web"){
            $type = "fruitday";
            $uid = $connect_id;
            $source = "wap";
        }else if($platform === "fruitday-app"){
            $type = "fruitday";

            $uid = $connect_id;
            $source = "app";
        }

        $this->load->helper("fruitday_send");
        $rs = get_fruit_user_request_execute(array('uid'=>$uid,'source'=>$source));
        if($rs && $rs['code'] == 200){
            $user = $rs['data'];
        }else{
            $this->send_error_response($rs['msg']."，请重新扫码");
        }
        $user_info = $this->user_model->get_user_info_by_open_id($user['id'], "fruitday");
        if(!$user_info){
            $user_data = array(
                "user_name" => isset($user['username']) ? $user['username'] : "",
                "avatar" => isset($user['avatar']) ? $user['avatar'] : "",
                "city" => isset($user['city']) ? $user['city'] : "",
                "province" => isset($user['province']) ? $user['province'] : "",
                "gender" => isset($user['gender']) ? $user['gender'] : "",
                "source" => $type,
                "reg_time" => date("Y-m-d H:i:s"),
                "open_id" => $user['id'],
                "is_black" => 0,
                "equipment_id" => 0,
                "mobile" => $user['mobile'],
            );
            $rs = $this->user_model->adUser($user_data,$device_id);
            if ($rs) {
                $user_info = $this->user_model->get_user_info_by_id($rs);
                $this->login_user($user_info,$user['money'],'',$device_id);
            } else {
                $this->send_error_response("用户信息注册异常");
            }
        }else{
            $this->login_user($user_info,$user['money'],'',$device_id);
        }
    }
    
    
    /**
     * 招商银行一网通登录地址
     */
    public function cmb_login_post()
    {
		$this->load->helper('url');
        $this->load->helper('utils');
        $this->load->library('CmbChina');
        
        if($_POST){
            
            $params = [];
            $response = [];
            
            $resp_xml = $_REQUEST['sResponseXml'];
            $params['device_id'] = $_REQUEST['device_id'];
            
            $cmbchina = new CmbChina();
            
            $response = xml2array($resp_xml);
            $retval = $cmbchina->des_decrypt($response['Body']);
            
            $result = xml2array($retval);
            
            //验签
            if(preg_match("/<Body>(.*?)<\/Body>/", $retval, $matches)){
                $body_xml = $matches[1];
                $verifyString = $body_xml . "&signature=" . $result['Tail']['Verify'];
                $verifyState = $cmbchina->verifySign($verifyString);
            }else{
                $verifyState = false;
            }
            
            if($result && $verifyState){
                
                $open_id = $result['Body']['UniqueUserID'];
                $this->cmb_user_login($open_id, [], $params);
                exit;
                
                //Todo签约流程
                /*if($result['Body']['IsQuickPay'] == 'N'){
                    
                    $base_url = $this->config->item("base_url");
                    $cmb_login_url = str_replace('http', 'https', $base_url) . '/index.php/api/account/cmb_login?device_id=' . $params['device_id'];
                    $url = $cmbchina->tplogin_url($cmb_login_url, true);
                    
                    header("Location: " . $url);
                    exit();
                }elseif($result['Body']['IsQuickPay'] == 'Y'){
                    
                    $agree_maps = [];
                    
                    foreach(explode('|', $result['Param']['Body']['AccAgreeMap']) as $key => $row){
                        list($agree_maps[$key]['acc_no'], $agree_maps[$key]['agrt_id']) = explode('=', $row);
                    }
                    
                    $this->cmb_user_login($open_id, $agree_maps, $params);
                }*/
                
            }
        
        }
    }
    
    
    /**
     * @desc 招商银行用户注册
     */
    private function cmb_user_login($open_id, $agree_maps, $params)
    {
        $user_type = 'cmb';
        
        $user = [];
        $user['username'] = '招行手机银行用户_'.$this->tag_code($open_id);
        
        $user_info = $this->user_model->get_user_info_by_open_id($open_id, $user_type);
    
        $this->load->helper('url');
        
        if($user_info){
            if($user_info['register_device_id'] == 0){
                $this->user_model->update_user_deviceId($user_info['id'], $params['device_id']);
            }
        }else{
            
            $user_data = array(
                "user_name" => isset($user['username']) ? $user['username'] : "",
                "avatar" => isset($user['avatar']) ? $user['avatar'] : "",
                "city" => isset($user['city']) ? $user['city'] : "",
                "province" => isset($user['province']) ? $user['province'] : "",
                "gender" => isset($user['gender']) ? $user['gender'] : "",
                "source" => $user_type,
                "reg_time" => date("Y-m-d H:i:s"),
                "open_id" => $open_id,
                "is_black" => 0,
                "equipment_id" => 0,
                "mobile" => $user['mobile'] ? $user['mobile'] : "",
            );
            
            $reg_user_id = $this->user_model->adUser($user_data, $params['device_id']);
            
            if($reg_user_id){
                $user_info = $this->user_model->get_user_info_by_id($reg_user_id);
                
                //直付通协议
                if($agree_maps){
                    $this->load->model("user_cmb_account_model");
                    foreach($agree_maps as $row){
                        $argt_item = $this->user_cmb_account_model->get_item(['agrt_id' => $row['agrt_id']]);
                        
                        if(!$argt_item){
                            $this->user_cmb_account_model->create([
                                'user_id' => $reg_user_id,
                                'acc_no' => $row['acc_no'],
                                'agrt_id' => $row['agrt_id'],
                                'create_dt' => date("Y-m-d H:i:s")
                            ]);
                        }
                    }
                }
            }else{
                $this->send_error_response("注册失败");
            }
        }
        
        if ($user_info["is_black"]) {
            //用户黑名单
            $this->response(['status' => FALSE,'message' => "你已进入黑名单"], REST_Controller::HTTP_BAD_REQUEST);
        }
        
        
        if($user_info['id']){
            $session_id = $this->session_id_2_user($user_info['id']);
            $user_cache_key = 'user_'.$session_id;
            $this->cache->save($user_cache_key,$user_info,604800);
            
            $cache_key = "role_". $user_info['open_id'];
            $this->cache->save($cache_key, $user_info['role'], REST_Controller::USER_LIVE_SECOND);
            
            
            //TODO 判断用户是否有未支付订单
            $this->load->model('order_model');
            $wait_pay_order = $this->order_model->get_wait_pay_order($user_info['id']);
            if($wait_pay_order){
                $base_url = $this->config->item("base_url");
                $redirect_url = $base_url . '/public/cmb_open.html?action=doPay&d='.$params['device_id'].'&token='.$session_id.'&user_id='.$user_info['id'].'&order_name='.$wait_pay_order['order_name'];
                redirect($redirect_url);
            }
            
            //生成开门口令
            $open_passwd = md5(time() . mt_rand(1,1000000));
            $open_passwd_key = 'open_passwd:'.$params['device_id'].'_'.$user_info['open_id'];
            $this->cache->redis->save($open_passwd_key, $open_passwd, 300);
            
            $base_url = $this->config->item("base_url");
            $redirect_url = $base_url . '/public/cmb_open.html?action=doOpen&d='.$params['device_id'].'&token='.$session_id.'&user_id='.$user_info['id'].'&open_passwd='.$open_passwd;
            redirect($redirect_url);
        }
        
        $this->send_error_response("登录失败");
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


    /*
     * 获取关爱通openid
     * */
    public function gat_user_login( $auth_code, $device_id, $redirect_uri ){
        //暂时关闭
//        $this->send_error_response("该盒子暂不不支持关爱通开通");
//        exit;
        $houzui = substr($redirect_uri, (strripos($redirect_uri,'.',0)+1));//关爱通传过来的 后缀.test  或者 .com
        $houzui = explode('/', $houzui);
        $houzui = $houzui[0];
        if($redirect_uri!='' && $houzui!='test' && $houzui!='com'){
            $this->send_error_response("参数错误");
        }
        //测试伪数据  仅限5楼那台设备 和 关爱通二楼
//        if($device_id != 73065889918 && $device_id != 68805328909){
//            $this->send_error_response("该盒子暂不不支持关爱通开通");
//            exit;
//        }
        $this->load->helper('guanaitong_send');

        $open_id = send_gat_user_request($auth_code);
//        $open_id = '1111';//测试伪数据
        if($open_id===0){
            $this->send_error_response("用户授权错误,请重试");
            write_log(" gat_open_id_error :" . $auth_code.'-'.$device_id);            //返回错误信息
        }
        $this->load->config('guanaitong', TRUE);

        $this->load->helper('cookie');
        set_cookie('device_id', $device_id);
        $gateway_url = $this->config->item('gateway_web_'.$houzui, 'guanaitong');

        $redirect_url = $gateway_url.'/citybox/funds?auth_code='.$auth_code.'&d='.$device_id;
        $user['username'] = '关爱通用户_'.$this->tag_code($open_id);
        $this->register_and_login_gat($open_id, 'gat', $device_id, 0,$redirect_url, $user, $auth_code);
    }

    /*
     * @desc 关爱通用户注册 登录
     * */
    private function register_and_login_gat($open_id,$type,$device_id,$money=0,$redirect_url='', $user=array(), $auth_code=''){
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
            } else {
                $this->send_error_response("用户信息注册异常");
            }
        }else{
            if($user_info['register_device_id'] == 0){
                $this->user_model->update_user_deviceId($user_info['id'],$device_id);
            }
        }

        //判断用户是否有未支付订单
        $this->load->model('order_model');
        $order_no_pay = $this->order_model->get_order_no_pay($user_info['id']);
        if($order_no_pay>0){//存在未支付订单， 跳到订单列表
            $redirect_url = '/public/orders.html?status=0&auth_code='.$auth_code;
        }
        $this->login_user($user_info,$money,$redirect_url,$device_id);
    }


    //关爱通登录， 只登录不注册, 订单列表， 用户中心使用到
    public function gat_only_login_get(){
        $auth_code = $this->get('auth_code');
        $this->load->helper('guanaitong_send');
        $open_id = send_gat_user_request($auth_code);
        if($open_id===0){
            $this->send_error_response("用户授权错误,请重试");
        }
        $user_info = $this->user_model->get_user_info_by_open_id($open_id, 'gat');
        if(empty($user_info)){//用户未注册
            $user_info = $this->user_model->create_user(array('user_name'=>'关爱通用户_'.$this->tag_code($open_id), 'open_id'=>$open_id), 'gat');
        }
        $this->login_user($user_info);
    }


    /* 生成唯一短标示码 */
    function tag_code($str) {
        $str = crc32($str);
        $x = sprintf("%u", $str);
        $show = '';
        while ($x > 0) {
            $s = $x % 62;
            if ($s > 35) {
                $s = chr($s + 61);
            } elseif ($s > 9 && $s <= 35) {
                $s = chr($s + 55);
            }
            $show .= $s;
            $x = floor($x / 62);
        }
        return $show;
    }

    /**
     * 小程序 code 获取用户信息
     */
    public function wx_jscode2session_post(){
        $device_id = $this->post('device_id');
        $js_code = $this->post('js_code');
        $userInfo = $this->post('userInfo');
        $this->check_null_and_send_error($js_code,'js_code 不能为空');
        $this->load->helper('wechat_send');
        $config  = get_platform_config_by_device_id($device_id);
        $data=get_wx_jscode2session($js_code,$config);
        if(!$data){
            $this->send_error_response("系统异常");
        } else if(isset($data['errcode'])){
            $this->send_error_response($data['errmsg']);
        }else{
            $key = 'program_session_key_'.$data['openid'];
            $this->cache->save($key,$data, 7200);

            $program_openid = $data['openid'];
            $unionid = $data['unionid'];
            if($unionid){
                $user = $this->user_model->get_user_info_by_union_id($unionid);
                if ($user) {
                    if(!$user['program_openid']){
                        $this->user_model->update_user_info($user['id'],array("program_openid" => $data['openid']));
                        $this->login_user($user,0,'',$device_id);
                    }
                    $user['open_id'] = $data['openid'];
                    $user['session_key'] = $key;
                    $user['wechat_type'] = 'program';//小程序
                    $this->login_user($user,0,'',$device_id);
                }else{
                    //注册用户
                    $user_data =  array(
                        "user_name" => '',
                        "avatar" => '',
                        "city" => '',
                        "province" => '',
                        "gender" => '',
                        "source" => 'wechat',
                        "unionid" => $unionid,
                        "reg_time" => date("Y-m-d H:i:s"),
                        "open_id" => '',
                        "program_openid" => $data['openid'],
                        "is_black" => 0,
                        "equipment_id" => 0,
                    );
                    $rs = $this->user_model->adUser($user_data,$device_id);
                    $user = $this->user_model->get_user_info_by_id($rs);
                    $user['session_key'] = $key;
                    $user['wechat_type'] = 'program';//小程序
                    $user['open_id'] = $data['openid'];
                    $this->login_user($user,0,'',$device_id);
                }
            }else{
                $exist = $this->user_model->get_user_info_by_program_id($program_openid,'wechat');
                if($exist && $exist['unionid']){
                    $user = $exist;
                    $user['session_key'] = $key;
                    $user['wechat_type'] = 'program';//小程序
                    $user['open_id'] = $program_openid;
                    $this->login_user($user,0,'',$device_id);
                }else{
                    //授权获取信息
                    $this->send_ok_response(array('session_key'=>$key,'message'=>'没有授权信息'));
                }
            }
        }
    }

    public function login_user_program_post(){
        $device_id = $this->post('device_id');
        $js_code = $this->post('js_code');
        $userInfo = $this->post('userInfo');
        $encrypted_data = $this->post('encrypted_data');
        $appid = $this->post('appid');
        $iv = $this->post('iv');
        if($userInfo){
            $userInfo = json_decode($userInfo,true);
        }
        $this->check_null_and_send_error($js_code,'js_code 不能为空');
        $this->load->helper('wechat_send');

        $data = $this->cache->get($js_code);
        write_log('cache sessoionkey '.var_export($data,1));

        if(!$data){
            $this->send_error_response("系统异常");
        } else if(isset($data['errcode'])){
            $this->send_error_response($data['errmsg']);
        }else{
            $sessionKey = $data['session_key'];
            write_log('cache session_key '.var_export($sessionKey,1));
            $this->load->library('wechat/WXBizDataCrypt');
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encrypted_data, $iv, $data1 );

            write_log('sessionKey decode'.$appid.''.$sessionKey.',errCode='.$errCode.',解密内容'.var_export($data1,1));
            if ($errCode == 0) {
                $data1 = json_decode($data1,TRUE);
                $unionid = $data1['unionId'];
            }else{
                $this->send_error_response($errCode);
            }
            if($unionid){
                $user = $this->user_model->get_user_info_by_union_id($unionid);
                if ($user) {
                    if(!$user['program_openid']){
                        $this->user_model->update_user_info($user['id'],array("program_openid" => $data['openid']));
                        $this->login_user($user,0,'',$device_id);
                    }
                    $user['open_id'] = $data['openid'];
                    $user['session_key'] = $js_code;
                    $user['wechat_type'] = 'program';//小程序
                    $this->login_user($user,0,'',$device_id);
                }else{
                    //注册用户
                    $user_data =  array(
                        "user_name" => $userInfo ['nickName'],
                        "avatar" => $userInfo ['avatarUrl'],
                        "city" => $userInfo['city'],
                        "province" => $userInfo['province'],
                        "gender" => $userInfo['gender'],
                        "source" => 'wechat',
                        "unionid" => $unionid,
                        "reg_time" => date("Y-m-d H:i:s"),
                        "open_id" => '',
                        "program_openid" => $data['openid'],
                        "is_black" => 0,
                        "equipment_id" => 0,
                    );
                    $rs = $this->user_model->adUser($user_data,$device_id);
                    $user = $this->user_model->get_user_info_by_id($rs);
                    $user['session_key'] = $js_code;
                    $user['wechat_type'] = 'program';//小程序
                    $user['open_id'] = $data['openid'];
                    $this->login_user($user,0,'',$device_id);
                }
            }else{
                $this->send_error_response('miss_unionid');
            }
        }
    }

    public function program_jscode2session_post(){

        $js_code = $this->post('js_code');
        $this->check_null_and_send_error($js_code,'js_code 不能为空');
        $this->load->helper('wechat_send');
        $config  = get_platform_config_by_device_id(0);
        $data=get_wx_jscode2session($js_code,$config);
        if(!$data){
            $this->send_error_response("系统异常");
        } else if(isset($data['errcode'])){
            $this->send_error_response($data['errmsg']);
        }else{
            $this->send_ok_response($data);
        }
    }
    /**
     * 微信小程序的用户信息补全
     */
    public function wx_program_user_info_post(){

        $encrypted_data = $this->post('encrypted_data');
        $appid = $this->post('appid');
        $iv = $this->post('iv');
        $js_code = $this->post('code');
        $deviceId = $this->post('device_id');

        $this->check_null_and_send_error($encrypted_data,'encrypted_data不能为空');
        $this->check_null_and_send_error($appid,'appid不能为空');
        $this->check_null_and_send_error($iv,'iv不能为空');

        $config  = get_platform_config_by_device_id($deviceId);
        $this->load->helper('wechat_send');
        $data1=get_wx_jscode2session($js_code,$config);
        if(!$data1){
            $this->send_error_response("系统异常");
        } else if(isset($data1['errcode'])){
            $this->send_error_response($data1['errmsg']);
        }else{
            $sessionKey = $data1['session_key'];
            $this->load->library('wechat/WXBizDataCrypt');
            $pc = new WXBizDataCrypt($appid, $sessionKey);
            $errCode = $pc->decryptData($encrypted_data, $iv, $data );
            write_log('sessionKey decode'.$appid.''.$sessionKey.',errCode='.$errCode.',解密内容'.var_export($data,1));
            if ($errCode == 0) {
                $data = json_decode($data,TRUE);
                $unionid = $data['unionId'];
                if(!$unionid) {
                    $this->send_error_response('unionId');//缺少unionid
                }
                $user = $this->user_model->get_user_info_by_union_id($unionid);
                if(!$user){
                    $user_data =  array(
                        "user_name" => $data ['nickName'],
                        "avatar" => $data ['avatarUrl'],
                        "city" => $data['city'],
                        "province" => $data['province'],
                        "gender" => $data['gender'],
                        "source" => 'wechat',
                        "unionid" => $data['unionId'],
                        "reg_time" => date("Y-m-d H:i:s"),
                        "open_id" => '',
                        "program_openid" => $data['openId'],
                        "is_black" => 0,
                        "equipment_id" => 0,
                    );
                    $rs = $this->user_model->adUser($user_data,$deviceId);
                    $user = $this->user_model->get_user_info_by_id($rs);
                }else if($user && !$user['program_openid']){
                    $this->user_model->update_user_info($user['id'],array("program_openid" => $data['openId']));
                }
                $user['session_key'] = $sessionKey;
                $user['wechat_type'] = 'program';//小程序
                $user['open_id'] = $data['openId'];

                $this->login_user($user,0,'',0);
            } else {
                $this->send_error_response($errCode);
            }
        }
    }

    /**
     * 小程序签约需要的参数
     */
    public function program_entrust_post(){
        $uid = $this->post('uid');
        $device_id = $this->post('device_id');
        $this->load->helper('wechat_send');
        $config  = get_platform_config_by_device_id($device_id);
        $data= array(
            'contract_code'=>uuid_32(),
            'contract_display_account'=>'魔盒CITYBOX微信免密支付',
            'request_serial'=>uuid_32(),
        );
        $ret =get_program_entrust($data,$config);
        $this->send_ok_response($ret);
    }

    public function program_notify_entrust_post(){
        if($_GET['platform']){
            $platform = $_GET['platform'];
            unset($_GET['platform']);
        }
        $config =  get_platform_config($platform);

        $config['wechat_appid'] =  $config['wechat_program_appid'];
        $config['wechat_secret'] = $config['wechat_program_secret'];

        write_log("program agreement notify:" . var_export($GLOBALS['HTTP_RAW_POST_DATA'], 1));
        $this->load->helper('wechat_send');
        $notify = wechat_notify($config);
        write_log("program_notify_entrust=>".var_export($notify,1),'info');
        if($notify){
            if ($notify->data["result_code"] == "FAIL") {

            }else if($notify->data["result_code"] == "SUCCESS") {
                $data["agreement_no"] = $notify->data["contract_id"];
                $data["sign_time"] = $notify->data["operate_time"];
                $data["partner_id"] = $notify->data["mch_id"];
                $data['sign_detail'] = $notify->data;

                $ret = $this->user_agreement_model->update_agreement_sign($notify->data["openid"],$data,'wechat',$notify->data["mch_id"],1);
                if($ret){
                    $this->update_user_cache($ret['user_id'],$ret);
                }
            }
        }
    }
    public function update_wechat_unioid_get()
    {
        $users = $this->user_model->get_no_unionid_user();
        write_log('users=>'.var_export($users, 1));
        $this->load->helper('wechat_send');
        $platform_config = get_platform_config_by_device_id(0);
        foreach ($users as $user){
            $openid = $user['open_id'];
            $data = get_user_by_openid_msg_execute($platform_config,$openid);
            write_log(var_export($data, 1));
            $u = json_decode($data,true);

            if ($u && $u['unionid']) {
                $this->user_model->update_user_info($user['id'],array("unionid" => $u['unionid']));
            }
        }
        $this->send_ok_response($users);
    }

    function ge_user_unionid_by_open_id_get(){
        $this->load->helper('wechat_send');
        $platform_config = get_platform_config_by_device_id(0);

        $platform_config['wechat_appid'] =  $platform_config['wechat_program_appid'];
        $platform_config['wechat_secret'] = $platform_config['wechat_program_secret'];

        $data = get_program_user_by_openid_msg_execute($platform_config,'oxl4R0WHxRpMZcj4BwI0JJu9yYAY');
        write_log(var_export($data, 1));
        $u = json_decode($data,true);
        $this->send_ok_response($u);
        return $u;
    }


    public function program_update_agreement_get(){
        $num = $this->get('num');
        if($num)
            $num = 50;
        $users = $this->user_model->get_agreement_is_null('wechat',$num);
        var_dump($users);

        foreach ($users as $u){
            $is_p = '';
            $open_id = $u['open_id'];
            if(!$u['open_id']){
                $open_id = $u['program_openid'];
                $is_p ='program';
            }
            echo 'open_id='.$open_id,',',$is_p;
            $sign = $this->get_wechat_user_agreement_query($open_id,0,$is_p);
            print_r($sign);
            if ($sign['return_code']=='SUCCESS' && $sign['result_code']=='SUCCESS' && $sign['contract_state']=='0') {
                //更新用户的签约号
                $data["agreement_no"] = $sign["contract_id"];
                $data["sign_time"] = $sign["contract_signed_time"];
                $data["partner_id"] = $sign["mch_id"];
                $data['sign_detail'] = $sign;
                $this->user_model->update_agreement_sign($open_id, $data,'wechat',$is_p);
                print_r($data);
            }
            sleep(1);
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

    /*
     * @desc 索迪斯登录注册
     * */
    public function sodexo_login($device_id){
        $this->send_error_response('设备暂不支持此方式登录');
        $tid  = $this->get('TID');
        $code = $this->get('code');
        $sign = $this->get('sign');
        $this->load->helper('sodexo_helper');
        if($sign != get_sign($device_id, $code)){
            //$this->send_error_response('签名错误');
        }
        if($device_id != '68805328909' && $device_id != '73115566603' ){
            $this->send_error_response('设备暂不支持此方式登录');
        }
        $user_info = $this->user_model->get_user_info_by_open_id($tid, 'sodexo');
        if(empty($user_info)){//用户未注册
            $user_info = $this->user_model->create_user(array('user_name'=>'sodexo_'.$this->tag_code($tid), 'open_id'=>$tid), 'sodexo', $device_id);
        }
        $rs = $this->login_user_easy($user_info);
        if(!$rs['status']){
            $this->response($rs, $rs['code']);
        }

        if(!$device_id || !$code || !$user_info['open_id'] || $user_info['source'] !='sodexo' ){
            $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
        }
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $rs = $device->request_open_door($device_id, $user_info, 'gat');
        if($rs == 'succ'){
            $this->load->driver('cache');
            $this->cache->redis->save('pay_code:'.$device_id, $code, 180);
            $base_url = $this->config->item("base_url");
            $redirect_url = $base_url . '/public/open_succ.html?from=sodexo&deviceId='.$device_id;
            return $redirect_url;
        }else{
            $this->send_error_response($rs."，开门失败，请稍后重试");
        }

    }

    /**
     * 简单版登录
     * @param $user array 用户信息
     * @return array
     * */
    private function login_user_easy($user)
    {
        $this->load->driver('cache');
        if (!$user) {//用户未注册
            return array('status' => FALSE,'message' => "用户未注册",'code'=>REST_Controller::HTTP_NOT_FOUND);
        }
        if ($user["is_black"]) {//用户黑名单
            return array('status' => FALSE,'message' => "你已进入黑名单",'code'=>REST_Controller::HTTP_BAD_REQUEST);
        }

        $user['role'] = 'user';
        $session_id = $this->session_id_2_user($user['id']);
        $user_cache_key = 'user_'.$session_id;
        $this->cache->save($user_cache_key,$user,604800);//记录用户保存7天
        if ($user) {
            $key1 = "role_". $user['open_id'];
            $this->cache->save($key1, $user['role'], REST_Controller::USER_LIVE_SECOND);
            $this->load->helper('cookie_helper');
            set_cookie('UserSN', $user['id'], 604800);
            set_cookie('token', $session_id, 604800);
            return array('status' => true);
        } else {
            return array('status' => FALSE,'message' => "cache错误",'code'=>REST_Controller::HTTP_BAD_REQUEST);
        }
    }

}

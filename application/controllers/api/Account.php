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
    const RANK_LIVE_SECOND = 60 * 60;//1小时
    const RANK_KEY = 'mars_rank';//1小时
    const RANK_SIZE = 10;
    const RANK_LAST_UPDATE = '';

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model('user_fin_model');
        $this->load->model('user_sign_model');
        $this->load->model('user_friend_model');
        $this->load->model('user_model');
    }

    public function get_info_get()
    {
        $rs = $this->get_curr_user();
        $this->send_ok_response($rs);
    }

    public function get_rank_get()
    {
        $last_update = date("Y-m-d H:i:s");
        $list = $this->cache->get(self::RANK_KEY);
        if(!$list){
            $list = $this->user_fin_model->get_rank(self::RANK_SIZE);
            if(count($list) >= self::RANK_SIZE){
                $this->cache->save(self::RANK_KEY,$list,self::RANK_LIVE_SECOND);
                $this->cache->save(self::RANK_LAST_UPDATE,$last_update,self::RANK_LIVE_SECOND);
            }
        }else{
            $last_update = $this->cache->get(self::RANK_LAST_UPDATE);
        }
        $this->send_ok_response(array('rank'=>$list,'last_update'=>$last_update));
    }

    private function login_user($user_id)
    {
        $user = $this->user_model->get_user_info_by_id($user_id);
        if ($user) {
            $fin = $this->user_fin_model->get_fin_by_id($user_id);
            write_log($this->db->last_query());
            $user['fin'] = $fin;
            $session_id = session_id_2_user($user['id']);
            $user_cache_key = 'user_'.$session_id;
            $this->cache->save($user_cache_key,$user,604800);//记录用户保存7天
            $key1 = "role_". $user['open_id'];
            $this->cache->save($key1, $user['role'], REST_Controller::USER_LIVE_SECOND);
            write_log('login_user_succ=>'.var_export($user,1));
            $this->user_sign_model->get_sign_today($user_id);
            $this->send_ok_response(['token' => $session_id, 'user' => $user]);
        } else {
            $this->send_error_response("cache错误");
        }
    }

    /**
     * 小程序 code 获取用户信息
     */
    public function wx_jscode2session_post(){
        $js_code = $this->post('wxLoginCode');
        $userInfo = $this->post('rawData');
        $encrypted_data = $this->post('encryptedData');
        $iv = $this->post('iv');
        $invite_code = $this->post('inviteCode');


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
                if($invite_code){
                    $this->user_friend_model->invite($user_id,$invite_code);
                }
            }
            $this->login_user($user_id);
        }
    }
    public function qr_code_get(){
        $user = $this->get_curr_user();
        $invite_code = $user['invite_code'];
        $str = "https://lxy.bootoa.cn/public/c.html?c=".$invite_code;
//        general_qr_code($str);
        $qr_url = "http://qr.liantu.com/api.php?text=".urlencode($str)."";

        $num = $this->user_friend_model->get_friend_count($user['id']);
        if(!$num)
            $num = 0;
        $this->send_ok_response(array('qr'=>$qr_url,'user'=>$user,'num'=>$num));
    }
    public function fin_log_get(){
        $user = $this->get_curr_user();
        $logs = $this->user_fin_model->get_log($user['id']);
        $this->send_ok_response($logs);
    }
    public function energy_log_get(){
        $user = $this->get_curr_user();
        $logs = $this->user_fin_model->get_energy_log($user['id']);
        $this->send_ok_response($logs);
    }

    public function get_fins_collect_get(){
        $user = $this->get_curr_user();
        $fins = $this->user_fin_model->get_fins_collect($user['id']);
        foreach ($fins as &$v){
            $v['top'] = rand(1,200);
            $v['left'] = rand(1,200);
        }
        $this->send_ok_response($fins);
    }
    public function collect_fin_post(){
        $id = $this->post('id');
        $this->check_null_and_send_error($id,'缺少cans');
        $fin_land = $this->user_fin_model->collect_fin($id);
        $this->send_ok_response(array('land'=>$fin_land));
    }
}

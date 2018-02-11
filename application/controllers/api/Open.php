<?php
/**
 * 针对第三方开放的api
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/1/5
 * Time: 下午2:40
 */
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries\REST_Controller;
class Open extends REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        write_log("open construct: ");
        $this->load->model("user_model");
        $this->load->config('open', TRUE);
        $this->load->library("open/OpenLib");
        $this->load->library("open/SodexoLib");
    }

    /**
     * @desc 发送json数据
     */
    private function showJson($data, $is_unicode=0, $is_log=1)
    {
        if($is_log){
            write_log("open json result: ".var_export($data,true));
        }
        $data = is_null($data) ? $this->_json : $data;
        if($is_unicode){
            echo json_encode($data, JSON_UNESCAPED_UNICODE);
        }else{
            echo json_encode($data);
        }
        die();
    }

    public function sodexo_open_door_get(){
        $this->load->driver('cache');
        $tid       = $this->get('TID');//索迪斯分配终端id，用于获取支付码
        $code      = $this->get('code');//支付码
        $device_id = $this->get('d');//设备编码
        $sign      = $this->get('sign');
        $source    = $this->get('source');
        $mobile    = $this->get('open_id');//用户唯一标识 同时也是手机号
        $SodexoLib = new SodexoLib();
        write_log("sodexo open door: ".var_export($_GET,true));
        if($source != 'sodexo'){
            $this->showJson(array('code'=>503, 'msg'=>'非法的扫码来源'));
        }
        if($sign != $SodexoLib->get_sign($device_id, $code, $mobile)){
            $this->showJson(array('code'=>504, 'msg'=>'签名失败'));
        }
        if( $this->cache->redis->exists('pay_code_key:'.$code)){       //支付码的码值 存30天， 反正重复的支付码来扫开门
            $this->showJson(array('code'=>506, 'msg'=>'支付码重复使用'));
        }
        $user_info = $this->user_model->get_user_info_by_mobile($mobile, null, 'sodexo');
        if(empty($user_info)){//用户未注册
            $user_info = $this->user_model->create_user(array('user_name'=>'sodexo_'.$mobile, 'open_id'=>'sodexo_'.$mobile, 'equipment_id'=>$tid, 'mobile'=>$mobile), 'sodexo', $device_id);
        }
        $rs = $this->check_user_easy($user_info);
        if($rs['code'] == 501){
            $this->showJson($rs);
        }
        if(!$device_id || !$code || !$user_info['open_id'] || $user_info['source'] !='sodexo' || !$mobile ){
            $this->showJson(array('code'=>507, 'msg'=>'缺少参数，请重新扫码'));
        }
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $open_msg = $device->request_open_door($device_id, $user_info, 'sodexo');
        if($open_msg == 'succ'){
            $this->cache->redis->save('pay_code_key:'.$code, 1, 2592000);//支付码的码值 存30天， 反正重复的支付码来扫开门
            $this->cache->redis->save('pay_code:'.$device_id.':'.$user_info['id'], $code, 180);
            $this->db->update('user', array('equipment_id'=>$tid), array('id'=>$user_info['id']));//更新索迪斯用户tid，获取支付码会用到
            $this->showJson(array('code'=>200, 'msg'=>'开门成功'));
        }else{
            if(is_array($open_msg) && $open_msg['status']=='limit_open_refer'){
                $open_msg = '设备暂不支持此开门方式';
            }
            $code = $this->get_error_code($open_msg);
            $this->showJson(array('code'=>$code, 'msg'=>$open_msg.'开门失败，请稍后重试'));
        }
    }

    /**
     * 用户检查
     * @param $user array 用户信息
     * @return array
     * */
    private function check_user_easy($user)
    {
        if (!$user) {//用户未注册
            return array('code' => 501,'message' => "用户未注册");
        }
        if ($user["is_black"]) {//用户黑名单
            return array('code' => 501,'message' => "你已进入黑名单");
        }
        return array('code' => 200);
    }
    /* 生成唯一短标示码 */
    private function tag_code($str) {
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
     * 根据msg 返回错误code
     * @param $msg string
     * @return int
    */
    private function get_error_code($msg){
        switch($msg){
            case '设备正在初始化':
            case '设备正在维护中':
            case '设备发生故障':
            case '设备正在使用中':
            case '开门失败':
            case '系统错误':
                $code = 500;
                break;
            case '你没有操作权限':
            case '您已经被限制购买':
                $code = 501;
                break;
            case '您有未支付的订单':
                $code = 502;
                break;
            case '非法的扫码来源':
                $code = 503;
                break;
            case '签名失败':
                $code = 504;
                break;
            case '设备暂不支持此开门方式':
                $code = 505;
                break;
            case '支付码重复使用':
                $code = 506;
                break;
            case '缺少参数，请重新扫码':
                $code = 507;
                break;
            default:
                $code = 500;
                break;
        }
        return $code;
    }
//rand secret




    public function open_door_post(){
        $device_id = $param['d'] = $this->post('d');//设备编码
        $sign      = $this->post('sign');
        $source    = $param['source'] = $this->post('source');
        $open_id   = $param['open_id']= $this->post('open_id');//用户唯一标识 同时也是手机号
        $param['rand'] = $this->post('rand');
        $openLib = new OpenLib();
        write_log("open door: ".var_export($_GET,true));
        if(!in_array($source, array('sdy'))){
            $this->showJson(array('code'=>503, 'msg'=>'非法的扫码来源'));
        }
        if($sign != $openLib->get_open_sign($param)){
            $this->showJson(array('code'=>504, 'msg'=>'签名失败'));
        }
        $user_info = $this->user_model->get_user_info_by_open_id($open_id, $source);
        if(empty($user_info)){//用户未注册
            $user_info = $this->user_model->create_user(array('user_name'=>'沙丁鱼_'.$this->tag_code($open_id), 'open_id'=>$open_id), $source, $device_id);
        }
        $rs = $this->check_user_easy($user_info);
        if($rs['code'] == 501){
            $this->showJson($rs);
        }
        if(!$device_id || !$user_info['open_id'] || $user_info['source'] !=$source ){
            $this->showJson(array('code'=>507, 'msg'=>'缺少参数，请重新扫码'));
        }
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $open_msg = $device->request_open_door($device_id, $user_info, 'sodexo');
        if($open_msg == 'succ'){
            $this->showJson(array('code'=>200, 'msg'=>'开门成功'));
        }else{
            if(is_array($open_msg) && $open_msg['status']=='limit_open_refer'){
                $open_msg = '设备暂不支持此开门方式';
            }
            $code = $this->get_error_code($open_msg);
            $this->showJson(array('code'=>$code, 'msg'=>$open_msg.'开门失败，请稍后重试'));
        }
    }

    public function equipment_list_post(){
        $source    = $param['source'] =$this->post('source');
        $param['rand'] = $this->post('rand');
        $sign      = $this->post('sign');
        $open_config = $this->config->item("platform_id", 'open');
        $platform_id = $open_config[$source];
        if(!in_array($source, array('sdy'))){
            $this->showJson(array('code'=>503, 'msg'=>'非法的扫码来源'));
        }
        $openLib = new OpenLib();
        if($sign != $openLib->get_open_sign($param)){
            $this->showJson(array('code'=>504, 'msg'=>'签名失败'));
        }
        $this->load->model('equipment_model');
        $list = $this->equipment_model->get_eq_by_platform_id($platform_id);
        if(!empty($list)){
            $this->showJson(array('code'=>200, 'data'=>$list), 0, 0);
        }else{
            $this->showJson(array('code'=>500, 'msg'=>'数据异常'));
        }
    }
}
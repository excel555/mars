<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';
require APPPATH . '/libraries/barcode/BarcodeGeneratorPNG.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Device Controller
 * 设备管理
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/22/17
 * Time: 12:56
 */
class Device extends REST_Controller {

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("user_model");
        $this->load->helper("mapi_send");
        $this->load->model("receive_box_log_model");
        $this->load->model("box_status_model");
        $this->load->model("card_model");
        $this->load->helper("device");
        $this->load->model('equipment_model');
        $this->load->model('active_discount_model');
        $this->config->load('thirdpartycard', TRUE);
        
    }

    /**
     * 接收售货机信息
     * 更新售货机信息
     *
     */
    public function receive_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $version = $this->raw_data("version");
        $currentTimestamp = $this->raw_data("currentTimestamp");
        $deviceState = $this->raw_data("deviceState");
        $powerState = $this->raw_data("powerState");
        $upsState = $this->raw_data("upsState");

        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($version,"version参数不能为空");
        $this->check_null_and_send_error($currentTimestamp,"currentTimestamp参数不能为空");
        $this->check_null_and_send_error($deviceState,"deviceState参数不能为空");
        $this->check_null_and_send_error($powerState,"powerState参数不能为空");
        $this->check_null_and_send_error($upsState,"upsState参数不能为空");

        $msg_log_id = $this->log_msg($msg_id,"box_info",$this->raw_data(),$deviceId);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        $device->action_box_info($this->raw_data());

        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);;

    }

    /**
     * 接收售货机心跳信息
     *
     */
    public function receive_heart_msg_post()
    {

//        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
//        $msgCreateTime = $this->raw_data("msgCreateTime");
//        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
//        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
//        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
//
//        $msg_log_id = $this->log_msg($msg_id,"heart",$this->raw_data(),$deviceId);
//
//        $ret = array(
//            "status"=>true,
//            "message"=>"接收成功"
//        );
//        $this->update_msg($msg_log_id,$ret,"close");
//        $this->send_ok_response($ret);;
        $this->heart_msg($deviceId);
    }

    private function heart_msg($deviceId){

        active_device_helper($deviceId);
        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->send_ok_response($ret);;
    }
    /**
     * 接收售货机电源变化
     */
    public function receive_power_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $powerState = $this->raw_data("powerState");
        $upsState = $this->raw_data("upsState");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($powerState,"powerState参数不能为空");
        $this->check_null_and_send_error($upsState,"upsState参数不能为空");
        $msg_log_id = $this->log_msg($msg_id,"power",$this->raw_data(),$deviceId);

        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);
    }

    /**
     * 接收开关门信息
     * msgType 消息类型，1-门已打开 2-门已关闭 3-开门超时自动上锁
     */
    public function receive_door_msg_post()
    {

        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $msgType = $this->raw_data("msgType");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($msgType,"msgType参数不能为空");


        $type = "open_door";
        if($msgType == "2"){
            $type = "close_door";
        }else if($msgType == "1"){

        }elseif($msgType == "3"){
            //3-开门超时自动上锁
            $type = "over_time_close_door";
        }
        $msg_log_id = $this->log_msg($msg_id,$type,$this->raw_data(),$deviceId);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        if($msgType == "2"){
            $device->close_door($deviceId);//设置为盘点中

        }else if($msgType == "1"){
            $device->open_door($deviceId,$msg_id);
        }else if($msgType == "3"){
            $device->over_time_close_door($deviceId);
        }


        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);
    }
    /**
     * 接收设备盘点信息
     * 先返回成功再去更改盘点的状态 以及支付
     */
    public function receive_stock_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $labels = $this->raw_data("labels");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
//        $this->check_null_and_send_error($labels,"labels参数不能为空");
        $msg_log_id = $this->log_msg($msg_id,"stock",$this->raw_data(),$deviceId);
        $labels = array_unique($labels);//标签去重
        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );

        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret,NULL,1);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        $device->stock($deviceId,$labels);
        die;
    }

    private function log_msg($msg_id,$msg_type,$param,$device_id){

        active_device_helper($device_id);//激活设备
//        //记录消息推送多次异常问题
//        if($msg_type != 'heart'){
//            $this->load->model("log_abnormal_model");
//            $key = 'box_msg_' .$msg_type."_" .$device_id;
//            $msg_cache = $this->cache->get($key);
//            if($msg_cache){
//                $ab_data = array(
//                    'box_id' => $device_id,
//                    'content' => "零售机推送消息异常，5秒钟连续接收重复的".$msg_type."消息" ,
//                    'uid' => 0,
//                    'log_type' => 7//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功 5 网络监测异常
//                );
//                $this->log_abnormal_model->insert_log($ab_data);
//                write_log('设备'.$device_id.$ab_data['content'].var_export($msg_id,1),'crit');
//            }
//            $this->cache->save($key, $msg_id, 5);//消息记录5秒
//        }


        //先查询消息是否处理过
        $status = 'wait';
        $rs = $this->receive_box_log_model->get_info_by_msg_id_and_msg_type($msg_id,$msg_type);
        if($rs){
            $status = 'ignore';
        }
        $data=array(
            'msg_id'=>$msg_id,
            'msg_type'=>$msg_type,
            'param'=>json_encode($param),
            'receive_time'=>date("Y-m-d H:i:s"),
            'status'=>$status,
            'device_id'=>$device_id
        );
        $insert_id = $this->receive_box_log_model->insert_log($data);
        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"接收成功,notify"
            );
            $this->send_ok_response($ret);
        }else{
            return $insert_id;
        }
    }

    private function update_msg($id,$response,$status){
        $data=array(
            'response'=>json_encode($response),
            'response_time'=>date("Y-m-d H:i:s"),
            'status'=>$status,
            'id'=>$id
        );
        return $this->receive_box_log_model->update_log($data);
    }

    /**
     * 获取设备列表
     * start_time：设备添加时间戳，查询此时间之后的设备列表，如不传，则返回所有设备列表
     */
    public  function list_device_get(){
        $start_time = $this->get('start_time');
        if(!$start_time)
            $start_time = strtotime('2016-12-01');
        $req_data = array('startTime'=>$start_time);
        $this->load->helper('device_send');
        $rs = device_list_request_execute($req_data);
        $this->send_ok_response($rs);
    }

    /**
     * 请求开门
     */
    public function open_door_post(){
        $device_id = $this->post('device_id');
        $type = $this->post('type');

        $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
//        $this->check_null_and_send_error($type,"缺少参数，请重新扫码");
        $user = $this->get_curr_user();

        if($user['source'] != 'alipay' && $user['source'] != 'wechat'){
            write_log('用户信息异常:'.var_export($user,1),'info');
            $this->response([
                'status' => FALSE,
                'message' => "用户信息异常"
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }
//        if($user['source'] == 'alipay'){
//            $key = 'device_door_'.$device_id;
//            $device_door = $this->cache->get($key);
//            write_log('check cache '.' '.$key.":".$this->cache->get($key));
//            if(!$device_door || $device_door != $user['open_id']){
//                $this->cache->delete($key);
//                $this->send_error_response("扫码超时，请重新扫码");
//            }
//            $this->cache->delete($key);
//        }



        $key = "role_". $user['open_id'];
        $this->cache->save($key, 'user', REST_Controller::USER_LIVE_SECOND);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        write_log('请求开门:'.$device_id.','.var_export($user,1).','.$type);
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

    public function test_open_door_get()
    {
        $device_id = $this->get("box_id");
        $user = $this->get_curr_user();
        $this->load->library("Device_lib");
        $device = new Device_lib();
        $status = $device->request_open_door($device_id,$user,'alipay');
        if($status == 'succ'){
            $this->send_ok_response(array('status'=>'succ','message'=>'请求开门中...'));
        }else{
            $this->send_error_response($status."，请稍后重试");
        }
    }

    public function stock_get(){
        $device_id = $this->get("box_id");
        $this->load->model('box_status_model');
        $box_status = $this->box_status_model->get_info_by_box_id($device_id);
        if($box_status['status'] != 'free'){
            $this->send_error_response('设备不是空闲状态，不允许发送盘点，请稍后重试');
        }
        $this->load->helper("device_send");
        $data =array('deviceId'=>$device_id);
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($device_id);
        $is_new = 0;
        if($equipment_info['type'] == 'rfid-2'){
            //北京新设备
            $is_new = 1;
        }elseif ($equipment_info['type'] == 'scan-1'){
            $is_new = 2;
        }elseif ($equipment_info['type'] == 'vision-1'){
            $is_new = 3;
        }
        $rs =  stock_request_execute($data,$is_new);
        $this->send_ok_response($rs);
    }

    /**
     * 获取设备信息
     */
    public function get_info_get(){
        $device_id = $this->get("box_id");
        $this->load->helper("device_send");
        $req_data = array(
            'deviceId' => $device_id,
        );
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($device_id);
        $is_new = 0;
        if($equipment_info['type'] == 'rfid-2'){
            //北京新设备
            $is_new = 1;
        }elseif ($equipment_info['type'] == 'scan-1'){
            $is_new = 2;
        }elseif ($equipment_info['type'] == 'vision-1'){
            $is_new = 3;
        }
        $rs = device_info_request_execute($req_data,$is_new);
        $this->send_ok_response($rs);
    }  
    

    /**
     * 当前设备的广告
     */
    public function index_ad_get(){
        $device_id = $this->get('device_id');
        $this->check_null_and_send_error($device_id,"缺少参数");
        $banner_list = array();
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        //获取设备所属的platform_id
        $equipment_sql = "select platform_id,type from cb_equipment where equipment_id = '".$device_id."'";
        $equipment = $this->db->query($equipment_sql)->row_array();
        if ($equipment){
            $platform_id = $equipment['platform_id'];
            $type = $equipment['type'];
        }
        //获取用户id 判断是否新客
        $user = $this->get_curr_user();
        $uid = $user['id'];
        $user_mobile = $user['mobile'];
        //acountid从user拿
        $sql = "select acount_id from cb_user where id=".$uid;
        $acount = $this->db->query($sql)->row_array();
        if ($acount['acount_id']){
            $acount_id = $acount['acount_id'];
        } else {
            $acount_id = $user['acount_id'];
        }
        
        //获取用户等级,魔力,魔豆
        $user_rank = 1;
        $moli = 0;
        $modou = 0;
        $yue = 0;
        $sql = "select user_rank,moli,modou,yue from cb_user_acount where id=".$acount_id;
        $user_rs = $this->db->query($sql)->row_array();
        if ($user_rs){
            $user_rank = $user_rs['user_rank'];
            $moli = $user_rs['moli'];
            $modou = $user_rs['modou'];
            $yue = $user_rs['yue'];
        }
        //可抵扣多少元
        $discount_return = '';
        if ($modou >= 100){
            $discount_return = '可抵扣'.floor($modou / 100).'元';
        }
        
        $user_name = $user['user_name'];
        //用户名为空时返回等级显示
        if (empty($user_name)){
            if ($user_rank == 1){
                $user_name = '魔兔宝宝';   
            } elseif ($user_rank == 2){
                $user_name = '青铜魔兔';
            } elseif ($user_rank == 3){
                $user_name = '黄金魔兔';
            } elseif ($user_rank == 4){
                $user_name = '皇冠魔兔';                
            }
        }
        $user_avatar = $user['avatar'];
        //根据时间返回问候语
        $return_hello = '早安';
        $h=intval(date('G'));
        if ($h >= 0 && $h <11){
            $return_hello = '早安';
        } else if ($h >= 11 && $h < 17){
            $return_hello = '想吃就吃';
        } else {
            $return_hello = '不要忘记吃晚饭';
        }
        //获取会员专享文案
        $member_title = '';
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and active_status=1 and user_rank like '%{$user_rank}%' and member_title <> '' order by id desc limit 1";
        $rs = $this->db->query($sql)->row_array();
        if ($rs){
            $member_title = $rs['member_title'];
        }
        //新增加的返回
        //昵称
        $banner_list['user_name'] = $user_name;
        //头像
        $banner_list['avatar'] = $user_avatar;
        //魔力值
        $banner_list['moli'] = $moli;
        //魔豆值
        $banner_list['modou'] = $modou;
        //余额
        $banner_list['yue'] = $yue;
        //会员专享文案（可能为空）
        $banner_list['member_tile'] = $member_title;
        //问候文案
        $banner_list['return_hello'] = $return_hello;
        //抵扣文案
        $banner_list['discount_return'] = $discount_return;
        //设备类型
        $banner_list['equipment_type'] = $type;
        
        $sql = "select count(*) as buy_times from cb_order where order_status = 1 and uid = ".$uid;
        $rs = $this->db->query($sql)->row_array();
        $buy_times = 0;
        if ($rs){
            $buy_times = $rs['buy_times'];
        }
        //查看用户来源（什么途径开门）
        $refer = '';
        $refer_sql = "select refer from cb_box_status where box_id = '".$device_id."' and user_id=".$uid;
        $refer_row = $this->db->query($refer_sql)->row_array();
        if ($refer_row){
            $refer = $refer_row['refer'];
        }
        //购买次数三次以上是老客 is_new = 0
        if ($buy_times > 2){
            $banner_list['is_new'] = 0;
        } else {    //否则是新客 is_new = 1
            $banner_list['is_new'] = 1;
        }
        //显示的banner数量
        $show_banner = 5;
        //取出弹窗图片
        $pop_img = '';
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 2 and user_rank like '%{$user_rank}%' order by id desc limit 0,10";
        $rs = $this->db->query($sql)->result_array();
        $j = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($j == 0){
                    $j++;
                    $pop_img = IMG_HOST.'/'.$val['img_banner'];
                }
            }
        }
        $banner_list['pop_img'] = $pop_img;
        
        //先拿banner中的广告
        $banner_list['banner'] = array();
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 1 and user_rank like '%{$user_rank}%' order by id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $i = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($i <= 4){
                    $i++;
                    $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                }
            }
        }
        $show_active_banner = 4 - $i;
        //再拿active_banner
        if ($show_active_banner > 0){
            $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and active_status=1 and img_banner<>'' and user_rank like '%{$user_rank}%' order by id desc limit 0,20";
            $rs = $this->db->query($sql)->result_array();
            $j = 0;
            foreach($rs as $val){
                $is_show = 0;
                $is_refer = 0;
                $is_limit = 0;  //是否没到达最大次数
                $active_limit = $val['active_limit'];
                //调用张涛方法 看活动还剩多少次
                $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
                if ($active_num > 0){
                    $is_limit = 1;
                }
                if ($val['active_people'] == 1){//仅首单显示
                    if ($buy_times == 0){
                        $is_show = 1;
                    }
                } elseif($val['active_people'] == 2){//白名单显示
                    //获取白名单
                    $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                    $white = $this->db->query($sql)->row_array();
                    if ($white){
                        $white_user = $white['mobile'];
                        $white_user_mobile = explode("#",$white_user);
                        if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                            $is_show = 1;
                        }
                    }
                } else {
                    $is_show = 1;
                }
                //有配置商品id和分类id时需排除库存外商品
                if ($is_show == 1){
                    if ($val['product_ids'] || $val['class_id']){
                        $product_ids = $val['product_ids'];
                        $class_id = $val['class_id'];
                        $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                        $sql.=" and (";
                        if ($product_ids){
                            $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                        }
                        if ($class_id){
                            if ($product_ids){
                                $sql.=" or t4.id in (".$class_id.")";
                            } else {
                                $sql.=" t4.id in (".$class_id.")";
                            }
                
                        }
                        $sql.=" )";
                        //没有内容时 则不显示
                        $exist_products = $this->db->query($sql)->result_array();
                        if (!$exist_products){
                            $is_show = 0;
                        }
                    }
                }
                if ($val['refer'] == 'all'){
                    $is_refer = 1;
                } else {
                    if ($val['refer'] == $refer){
                        $is_refer = 1;
                    }
                }
                if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                    if ($j <= $show_active_banner){
                        $j++;
                        $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                    }
                    
                }
            }
        }
        
        //再拿active_icon 取3条
        $banner_list['active_icon'] = array();
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and active_status=1 and img_icon<>'' and icon_show = 1 and user_rank like '%{$user_rank}%' order by id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $k = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            $is_limit = 0;  //是否没到达最大次数
            $active_limit = $val['active_limit'];
            //调用张涛方法 看活动还剩多少次
            $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
            if ($active_num > 0){
                $is_limit = 1;
            }
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //有配置商品id和分类id时需排除库存外商品
            if ($is_show == 1){
                if ($val['product_ids'] || $val['class_id']){
                    $product_ids = $val['product_ids'];
                    $class_id = $val['class_id'];
                    $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                    $sql.=" and (";
                    if ($product_ids){
                        $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                    }
                    if ($class_id){
                        if ($product_ids){
                            $sql.=" or t4.id in (".$class_id.")";
                        } else {
                            $sql.=" t4.id in (".$class_id.")";
                        }
            
                    }
                    $sql.=" )";
                    //没有内容时 则不显示
                    $exist_products = $this->db->query($sql)->result_array();
                    if (!$exist_products){
                        $is_show = 0;
                    }
                }
            }
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                if ($k < 3){
                    $k++;
                    $banner_list['active_icon'][] = array('img_url'=>IMG_HOST.'/'.$val['img_icon'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                }
                
            }
        }
        if(count($banner_list['banner']) == 0){
            //没有轮播并且没有icon，则默认给一张轮播图片
            $banner_list['banner'][] = array('img_url'=>DEFALUT_BANNER_IMG,'start_time'=>date('Y.m.d'),'end_time'=>date('Y.m.d'),'remarks'=>'CITYBOX魔盒');
        }
        //最后取优惠券
        $banner_list['coupon'] = array();
        $today = date('Y-m-d');
        $where_coupon = array(
            'uid'=>$uid,
            /* 'begin_date <='=>$today, */
            'to_date >='=>$today,
            'is_used'=>0
        );
        $where_in_coupon = array(0,$platform_id);
        $banner_list['coupon'] =$this->card_model->get_avalible_cards($where_coupon,$where_in_coupon);
        foreach ($banner_list['coupon'] as $k=>$v){
            if (!strstr($v['source'],$refer)){//增加判断用户渠道
                unset($banner_list['coupon'][$k]);
            } else {
                $banner_list['coupon'][$k]['begin_date'] = date('Y.m.d',strtotime($v['begin_date']));
                $banner_list['coupon'][$k]['to_date'] = date('Y.m.d',strtotime($v['to_date']));
            }
        }
        //会员等级
        $banner_list['user_rank'] = $user_rank;
        $this->send_ok_response($banner_list);
    }
    
    //首页 开门页 结算页获取是不是有第三方发券
    public function exist_thirdparty_get(){
        $device_id = $this->get('device_id');
        $user = $this->get_curr_user();
        /* $user_sql = 'select * from cb_user where id = 1';
         $user = $this->db->query($user_sql)->row_array();
         $device_id = '68805328909';
         $is_index = 1; */
        $uid = $user['id'];
        $this->check_null_and_send_error($device_id,"缺少参数");
        $banner_list = array();
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        //获取设备所属的platform_id
        $equipment_sql = "select platform_id,type from cb_equipment where equipment_id = '".$device_id."'";
        $equipment = $this->db->query($equipment_sql)->row_array();
        $platform_id = 0;
        $type = '';
        if ($equipment){
            $platform_id = $equipment['platform_id'];
            $type = $equipment['type'];
        }
        //获取第三方优惠券
        $thirdpartycard = $this->config->item("thirdparty","thirdpartycard");
        //cityshop的
        $thirdpartysql = "select * from cb_thirdpartycard where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%') and active_status=1 and platform_id = {$platform_id} and refer = 'cityshop' order by id desc limit 1";
        $rs = $this->db->query($thirdpartysql)->row_array();
        $return_result['thirdparty_cityshop']['active_id'] = '';
        if ($rs){
            $active_id = $rs['id'];
            $sql = "select * from cb_card_third where uid = {$uid} and active_id = {$active_id} and status = 1";
            $row = $this->db->query($sql)->row_array();
            $return_result['thirdparty_cityshop']['is_get'] = 0;
            if ($row){
                $return_result['thirdparty_cityshop']['is_get'] = 1;
            }
            //判断是否领取过
            $return_result['thirdparty_cityshop']['active_id'] = $rs['id'];
            $return_result['thirdparty_cityshop']['title'] = $rs['title'];
            $cityshop_config = $thirdpartycard['cityshop'];
            $return_result['thirdparty_cityshop']['click_type'] = $cityshop_config['click_type'];
            $return_result['thirdparty_cityshop']['code'] = $cityshop_config['code'];
            //返回此活动相关信息
            $rule_text = $rs['rule_text'];
            //活动规则
            $return_result['thirdparty_cityshop']['rule_text'] = explode("\n",$rule_text);
        }
        $this->send_ok_response($return_result);
    }
    
    //20171212改版首页
    public function new_index_get(){
        $device_id = $this->get('device_id');
        $is_index = $this->get('is_index');
        /* $device_id = '68805328909';
        $is_index = 1; */
        $this->check_null_and_send_error($device_id,"缺少参数");
        $banner_list = array();
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        //获取设备所属的platform_id
        $equipment_sql = "select platform_id,type from cb_equipment where equipment_id = '".$device_id."'";
        $equipment = $this->db->query($equipment_sql)->row_array();
        $platform_id = 0;
        $type = '';
        if ($equipment){
            $platform_id = $equipment['platform_id'];
            $type = $equipment['type'];
        }
        //获取用户id 判断是否新客
        $user = $this->get_curr_user();
        /* $user_sql = 'select * from cb_user where id = 1';
        $user = $this->db->query($user_sql)->row_array(); */
        $uid = $user['id'];
        $user_mobile = $user['mobile'];
        //acountid从user拿
        $sql = "select acount_id from cb_user where id=".$uid;
        $acount = $this->db->query($sql)->row_array();
        if ($acount['acount_id']){
            $acount_id = $acount['acount_id'];
        } else {
            $acount_id = $user['acount_id'];
        }
        //是否有未支付订单
        if ($is_index == 1){
            $banner_list['unpay_order'] = 0;
            $this->load->model("order_model");
            $order = $this->order_model->list_orders($uid, 0, 1, 1000000);
            if ($order){
                $banner_list['unpay_order'] = 1;
            }
        }
        //获取用户等级,魔力,魔豆
        $user_rank = 1;
        $moli = 0;
        $modou = 0;
        $yue = 0;
        $sql = "select user_rank,moli,modou,yue from cb_user_acount where id=".$acount_id;
        $user_rs = $this->db->query($sql)->row_array();
        if ($user_rs){
            $user_rank = $user_rs['user_rank'];
            $moli = $user_rs['moli'];
            $modou = $user_rs['modou'];
            $yue = $user_rs['yue'];
        }
        //可抵扣多少元
        $discount_return = '';
        if ($modou >= 100){
            $discount_return = '可抵扣'.floor($modou / 100).'元';
        }
        
        $user_name = $user['user_name'];
        //用户名为空时返回等级显示
        if (empty($user_name)){
            if ($user_rank == 1){
                $user_name = '魔兔宝宝';
            } elseif ($user_rank == 2){
                $user_name = '青铜魔兔';
            } elseif ($user_rank == 3){
                $user_name = '黄金魔兔';
            } elseif ($user_rank == 4){
                $user_name = '皇冠魔兔';
            }
        }
        $user_avatar = $user['avatar'];
        //根据时间返回问候语
        $return_hello = '早安';
        $h=intval(date('G'));
        if ($h >= 0 && $h <11){
            $return_hello = '早安';
        } else if ($h >= 11 && $h < 17){
            $return_hello = '想吃就吃';
        } else {
            $return_hello = '不要忘记吃晚饭';
        }
        //获取会员专享文案
        $member_title = '';
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and user_rank like '%{$user_rank}%' and member_title <> '' order by id desc limit 1";
        $rs = $this->db->query($sql)->row_array();
        if ($rs){
            $member_title = $rs['member_title'];
        }
        //新增加的返回
        //魔豆值
        $banner_list['modou'] = $modou;
        //余额
        $banner_list['yue'] = $yue;
        //设备类型
        $banner_list['equipment_type'] = $type;
        if ($is_index == 0){
            //昵称
            $banner_list['user_name'] = $user_name;
            //头像
            $banner_list['avatar'] = $user_avatar;
            //魔力值
            $banner_list['moli'] = $moli;
            //会员专享文案（可能为空）
            $banner_list['member_tile'] = $member_title;
            //问候文案
            $banner_list['return_hello'] = $return_hello;
            //抵扣文案
            $banner_list['discount_return'] = $discount_return;
        }
        
        $sql = "select count(*) as buy_times from cb_order where order_status = 1 and uid = ".$uid;
        $rs = $this->db->query($sql)->row_array();
        $buy_times = 0;
        if ($rs){
            $buy_times = $rs['buy_times'];
        }
        //查看用户来源（什么途径开门） 
        $refer = $user['source'] ? $user['source'] : 'alipay';
        /* $refer_sql = "select refer from cb_box_status where box_id = '".$device_id."' and user_id=".$uid;
        $refer_row = $this->db->query($refer_sql)->row_array();
        if ($refer_row){
            $refer = $refer_row['refer'];
        } */
        //购买次数三次以上是老客 is_new = 0
        if ($buy_times > 2){
            $banner_list['is_new'] = 0;
        } else {    //否则是新客 is_new = 1
            $banner_list['is_new'] = 1;
        }
        
        //当前时间
        $index_card = -1;//默认不展示 modify by linxingyu
        $his = date('H:i:s');
        $nowday = date('w');
        $search_nowday = ','.$nowday.',';
        //获取首页领券活动
        $sql = "select * from cb_indexcard where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and per_day like '%{$search_nowday}%'  and active_status=1 order by id desc limit 1";
        $rs = $this->db->query($sql)->row_array();
        if ($rs){
            //and (start_hour<='{$his}' and end_hour>='{$his}')

            if($rs['start_hour'] > $his && $his < $rs['end_hour'] ){
                $index_card = 0;//未开始
            } else if($his > $rs['end_hour']){
                $index_card = -1;//已结束则不展示
            } else{
                $index_card = $rs['id'];
            }
        }
        $banner_list['index_card'] = $index_card;
        
        //显示的banner数量
        $show_banner = 5;
        //取出弹窗图片
        $pop_img = '';
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 2 and user_rank like '%{$user_rank}%' order by id desc limit 0,10";
        $rs = $this->db->query($sql)->result_array();
        $j = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($j == 0){
                    $j++;
                    $pop_img = IMG_HOST.'/'.$val['img_banner'];
                }
            }
        }
        $banner_list['pop_img'] = $pop_img;
        
        //先拿banner中的广告
        $banner_list['banner'] = array();
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 1 and user_rank like '%{$user_rank}%' order by id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $i = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($i <= 4){
                    $i++;
                    $click_url = '';
                    if ($val['click_type'] == 1){
                        $click_url = $val['click_url'];
                    } elseif ($val['click_type'] == 2){
                        $click_url = IMG_HOST.'/'.$val['click_url'];
                    }
                    $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks'],'click_type'=>$val['click_type'],'click_url'=>$click_url);
                }
            }
        }
        $show_active_banner = 4 - $i;
        //再拿active_banner
        if ($show_active_banner > 0){
            $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and img_banner<>'' and user_rank like '%{$user_rank}%' and platform_id = {$platform_id} order by weight desc,id desc limit 0,20";
            $rs = $this->db->query($sql)->result_array();
            $j = 0;
            foreach($rs as $val){
                $is_show = 0;
                $is_refer = 0;
                $is_limit = 0;  //是否没到达最大次数
                $active_limit = $val['active_limit'];
                //调用张涛方法 看活动还剩多少次
                $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
                if ($active_num > 0){
                    $is_limit = 1;
                }
                if ($val['active_people'] == 1){//仅首单显示
                    if ($buy_times == 0){
                        $is_show = 1;
                    }
                } elseif($val['active_people'] == 2){//白名单显示
                    //获取白名单
                    $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                    $white = $this->db->query($sql)->row_array();
                    if ($white){
                        $white_user = $white['mobile'];
                        $white_user_mobile = explode("#",$white_user);
                        if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                            $is_show = 1;
                        }
                    }
                } else {
                    $is_show = 1;
                }
                //有配置商品id和分类id时需排除库存外商品
                if ($is_show == 1){
                    if ($val['product_ids'] || $val['class_id']){
                        $product_ids = $val['product_ids'];
                        $class_id = $val['class_id'];
                        $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                        $sql.=" and (";
                        if ($product_ids){
                            $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                        }
                        if ($class_id){
                            if ($product_ids){
                                $sql.=" or t4.id in (".$class_id.")";
                            } else {
                                $sql.=" t4.id in (".$class_id.")";
                            }
        
                        }
                        $sql.=" )";
                        //没有内容时 则不显示
                        $exist_products = $this->db->query($sql)->result_array();
                        if (!$exist_products){
                            $is_show = 0;
                        }
                    }
                }
                if ($val['refer'] == 'all'){
                    $is_refer = 1;
                } else {
                    if ($val['refer'] == $refer){
                        $is_refer = 1;
                    }
                }
                if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                    if ($j <= $show_active_banner){
                        $j++;
                        $click_url = '';
                        if ($val['click_type'] == 1){
                            $click_url = $val['click_url'];
                        } elseif ($val['click_type'] == 2){
                            $click_url = IMG_HOST.'/'.$val['click_url'];
                        }
                        $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks'],'click_type'=>$val['click_type'],'click_url'=>$click_url);
                    }
                }
            }
        }
        
        //再拿active_icon 取3条
        $banner_list['active_icon'] = array();
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and img_icon_new<>'' and icon_show = 1 and user_rank like '%{$user_rank}%' and platform_id = {$platform_id} order by weight desc,id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $k = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            $is_limit = 0;  //是否没到达最大次数
            $active_limit = $val['active_limit'];
            //调用张涛方法 看活动还剩多少次
            $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
            if ($active_num > 0){
                $is_limit = 1;
            }
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //有配置商品id和分类id时需排除库存外商品
            if ($is_show == 1){
                if ($val['product_ids'] || $val['class_id']){
                    $product_ids = $val['product_ids'];
                    $class_id = $val['class_id'];
                    $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                    $sql.=" and (";
                    if ($product_ids){
                        $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                    }
                    if ($class_id){
                        if ($product_ids){
                            $sql.=" or t4.id in (".$class_id.")";
                        } else {
                            $sql.=" t4.id in (".$class_id.")";
                        }
        
                    }
                    $sql.=" )";
                    //没有内容时 则不显示
                    $exist_products = $this->db->query($sql)->result_array();
                    if (!$exist_products){
                        $is_show = 0;
                    }
                }
            }
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                if ($k < 3){
                    $k++;
                    $click_url = '';
                    if ($val['click_type'] == 1){
                        $click_url = $val['click_url'];
                    } elseif ($val['click_type'] == 2){
                        $click_url = IMG_HOST.'/'.$val['click_url'];
                    }
                    $banner_list['active_icon'][] = array('img_url'=>IMG_HOST.'/'.$val['img_icon_new'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks'],'click_type'=>$val['click_type'],'click_url'=>$click_url);
                }
        
            }
        }
        if(count($banner_list['banner']) == 0){
            //没有轮播并且没有icon，则默认给一张轮播图片
            $banner_list['banner'][] = array('img_url'=>DEFALUT_BANNER_IMG,'start_time'=>date('Y.m.d'),'end_time'=>date('Y.m.d'),'remarks'=>'CITYBOX魔盒');
        }
        //最后取优惠券
        $banner_list['coupon'] = array();
        $today = date('Y-m-d');
        $where_coupon = array(
            'uid'=>$uid,
            /* 'begin_date <='=>$today, */
            'to_date >='=>$today,
            'is_used'=>0
        );
        $where_in_coupon = array(0,$platform_id);
        $banner_list['coupon'] =$this->card_model->get_avalible_cards($where_coupon,$where_in_coupon);
        foreach ($banner_list['coupon'] as $k=>$v){
            if (!strstr($v['source'],$refer)){//增加判断用户渠道
                unset($banner_list['coupon'][$k]);
            } else {
                //营销限制 1.无限制 2.不与运营活动重复
                //$banner_list['coupon'][$k]['use_with_sales'] = $v['use_with_sales'];
                //商品限制  0.无限制 1.指定商品 2.指定品类
                //$banner_list['coupon'][$k]['product_limit_type'] = $v['product_limit_type'];
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
                    $banner_list['coupon'][$k]['product_limit_value'] = $limit_products; 
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
                    $banner_list['coupon'][$k]['product_limit_value'] = $limit_product_classes;
                }
                $banner_list['coupon'][$k]['begin_date'] = date('Y.m.d',strtotime($v['begin_date']));
                $banner_list['coupon'][$k]['to_date'] = date('Y.m.d',strtotime($v['to_date']));
            }
        }
        //优惠券数量
        $banner_list['coupon_count'] = count($banner_list['coupon']);
        //会员等级
        $banner_list['user_rank'] = $user_rank;
        $this->send_ok_response($banner_list);
    }
    
    /**
     * 当前订单的广告
     */
    public function order_and_ad_get(){
        $order_name = $this->get('order_name');
        //$device_id = '68805328909';
        $device_id = $this->get('device_id');
        $this->check_null_and_send_error($device_id,"缺少参数");
        //获取设备所属的platform_id
        $platform_id = 0;
        $equipment_sql = "select platform_id from cb_equipment where equipment_id = '".$device_id."'";
        $equipment = $this->db->query($equipment_sql)->row_array();
        if ($equipment){
            $platform_id = $equipment['platform_id'];
        }
        
        //$order_name = '';
        //$device_id = '68805328909';
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
            //预计获得多少魔豆
            $modou_sql = "SELECT modou FROM cb_user_acount_modou WHERE order_name = '".$order_name."' and modou > 0";
            $modou_row = $this->db->query($modou_sql)->result_array();
            $modou_count = 0;
            if ($modou_row){
                foreach($modou_row as $each_modou){
                    $modou_count = $modou_count + $each_modou['modou'];
                }
            }
            $order['get_modou'] = $modou_count;
            $return_result["order"] = $order;
            $return_result["order_goods"] = $this->order_product_model->list_order_products($order_name);
            
        } else {
            $return_result["order"] = array();
            $return_result["order_goods"] = array();
        }
        //$device_id = $order['box_no'];
        
        //获取用户id 判断是否新客
        $user = $this->get_curr_user();
        $uid = $user['id'];
        $user_mobile = $user['mobile'];
        $acount_id = $user['acount_id'];
        //获取用户等级
        $user_rank = 1;
        $sql = "select user_rank from cb_user_acount where id=".$acount_id;
        $user_rs = $this->db->query($sql)->row_array();
        if ($user_rs){
            $user_rank = $user_rs['user_rank'];
        }
        $sql = "select count(*) as buy_times from cb_order where order_status = 1 and uid = ".$uid;
        $rs = $this->db->query($sql)->row_array();
        $buy_times = 0;
        if ($rs){
            $buy_times = $rs['buy_times'];
        }
        //查看用户来源（什么途径开门）
        $refer = '';
        $refer_sql = "select refer from cb_box_status where box_id = '".$device_id."' and user_id=".$uid;
        $refer_row = $this->db->query($refer_sql)->row_array();
        if ($refer_row){
            $refer = $refer_row['refer'];
        }
        
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        
        //取出结算页优惠券活动
        $sql = "select * from cb_ordercard where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%') and active_status=1 order by id desc limit 1";
        $rs = $this->db->query($sql)->row_array();
        $return_result['ordercard']['card_count'] = 0;
        $return_result['ordercard']['active_id'] = '';
        $return_result['ordercard']['share_img'] = '';
        $return_result['ordercard']['share_title'] = '';
        $return_result['ordercard']['share_content'] = '';
        if ($rs && $order_name){
            $return_result['ordercard']['card_count'] = $rs['card_count'];
            $return_result['ordercard']['active_id'] = $rs['id'];
            $return_result['ordercard']['share_img'] = IMG_HOST.'/'.$rs['share_img'];
            $return_result['ordercard']['share_title'] = $rs['share_title'];
            $return_result['ordercard']['share_content'] = $rs['share_content'];
        }
        
        //取出底部图片
        $return_result['foot_img'] = array('img_url'=>DEFALUT_BANNER_IMG,'start_time'=>date('Y.m.d'),'end_time'=>date('Y.m.d'),'remarks'=>'CITYBOX魔盒','click_type'=>0,'click_url'=>'');
        $foot_img = '';
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 3 and user_rank like '%{$user_rank}%' order by id desc limit 0,10";
        $rs = $this->db->query($sql)->result_array();
        $j = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($j == 0){
                    $j++;
                    if ($val['click_type'] == 1){
                        $click_url = $val['click_url'];
                    } elseif ($val['click_type'] == 2){
                        $click_url = IMG_HOST.'/'.$val['click_url'];
                    }
                    $return_result['foot_img'] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks'],'click_type'=>$val['click_type'],'click_url'=>$click_url);
                }
            }
        }
        
        //再拿active_icon 取3条
        $return_result['banner'] = array();
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and img_icon<>'' and icon_show = 1 and user_rank like '%{$user_rank}%' and platform_id={$platform_id} order by weight desc,id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $k = 0;
        
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            $is_limit = 0;  //是否没到达最大次数
            $active_limit = $val['active_limit'];
            //调用张涛方法 看活动还剩多少次
            $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
            if ($active_num > 0){
                $is_limit = 1;
            }
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //有配置商品id和分类id时需排除库存外商品
            if ($is_show == 1){
                if ($val['product_ids'] || $val['class_id']){
                    $product_ids = $val['product_ids'];
                    $class_id = $val['class_id'];
                    $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                    $sql.=" and (";
                    if ($product_ids){
                        $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                    }
                    if ($class_id){
                        if ($product_ids){
                            $sql.=" or t4.id in (".$class_id.")";
                        } else {
                            $sql.=" t4.id in (".$class_id.")";
                        }
            
                    }
                    $sql.=" )";
                    //没有内容时 则不显示
                    $exist_products = $this->db->query($sql)->result_array();
                    if (!$exist_products){
                        $is_show = 0;
                    }
                }
            }
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                if ($k < 3){
                    $k++;
                    $return_result['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_icon'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                }
        
            }
        }
        
        //可以领取的优惠券
        $return_result['use_coupon'] = array();
        //展示的优惠券
        $return_result['coupon'] = array();
        $today = date('Y-m-d');
        $where_coupon = array(
            'uid'=>$uid,
            /* 'begin_date <='=>$today, */
            'to_date >='=>$today,
            'is_used'=>0
        );
        $where_in_coupon = array(0,$platform_id);
        $return_result['coupon'] = $this->card_model->get_avalible_cards($where_coupon,$where_in_coupon);
        foreach ($return_result['coupon'] as $k=>$v){
            if (!strstr($v['source'],$refer)){//增加判断用户渠道
                unset($return_result['coupon'][$k]);
            } else {
                $return_result['coupon'][$k]['begin_date'] = date('Y.m.d',strtotime($v['begin_date']));
                $return_result['coupon'][$k]['to_date'] = date('Y.m.d',strtotime($v['to_date']));
            }
        }
        //var_dump($return_result);exit;
        $this->send_ok_response($return_result); 
    }
    
    public function buy_box_address_get(){
        $user = $this->get_curr_user();
        $uid = $user['id'];
        $user_mobile = $user['mobile'];
        //获取最近开过门的盒子
        $sql = "SELECT DISTINCT(t1.box_no) FROM cb_log_open t1 LEFT JOIN cb_equipment t2 ON t2.equipment_id = t1.box_no WHERE t2.status = 1 AND t1.uid = ".$uid." order by t1.id desc";
        $rs = $this->db->query($sql)->result_array();
        if (!$rs){
            $return_result['is_open'] = 0;
        } else {
            $return_result['is_open'] = 1;
            foreach($rs as $key=>$v){
                if ($key == 0){
                    $sql = "select * from cb_equipment where equipment_id = '".$v['box_no']."'";
                    $first_box = $this->db->query($sql)->row_array();
                    $return_result['first'] = array('id'=>$first_box['id'],'equipment_id'=>$first_box['equipment_id'],'name'=>$first_box['name'],'address'=>$first_box['address']);
                    $return_result['box'][] = array('id'=>$first_box['id'],'equipment_id'=>$first_box['equipment_id'],'name'=>$first_box['name'],'address'=>$first_box['address']);
                } else {
                    //获取每台设备的信息
                    $sql = "select * from cb_equipment where equipment_id = '".$v['box_no']."'";
                    $each_box = $this->db->query($sql)->row_array();
                    $return_result['box'][] = array('id'=>$each_box['id'],'equipment_id'=>$each_box['equipment_id'],'name'=>$each_box['name'],'address'=>$each_box['address']);
                }
                
            }
        }
        //获取用户是不是消费过
        $this->send_ok_response($return_result);
    }
    
    public function box_info_get(){
        $device_id = $this->get('device_id');
        $this->check_null_and_send_error($device_id,"缺少参数");
        $banner_list = array();
        $time = date('Y-m-d H:i:s');
        $search_device = ','.$device_id.',';
        //获取设备所属的platform_id
        $equipment_sql = "select equipment_id,name,address,platform_id,add_price from cb_equipment where equipment_id = '".$device_id."'";
        $equipment = $this->db->query($equipment_sql)->row_array();
        
        $add_price = '';
        if ($equipment){
            $platform_id = $equipment['platform_id'];
            if ($equipment['add_price'] && $equipment['add_price']>1){
                $add_price = $equipment['add_price'];
            }
            $banner_list['equipment']['name'] = $equipment['name'];
            $banner_list['equipment']['address'] = $equipment['address'];
            $banner_list['equipment']['equipment_id'] = $equipment['equipment_id'];
        }
        //获取用户id 判断是否新客
        $user = $this->get_curr_user();
        $uid = $user['id'];
        $user_mobile = $user['mobile'];
        $acount_id = $user['acount_id'];
        //获取用户等级
        $user_rank = 1;
        $sql = "select user_rank from cb_user_acount where id=".$acount_id;
        $user_rs = $this->db->query($sql)->row_array();
        if ($user_rs){
            $user_rank = $user_rs['user_rank'];
        }
        $sql = "select count(*) as buy_times from cb_order where order_status = 1 and uid = ".$uid;
        $rs = $this->db->query($sql)->row_array();
        $buy_times = 0;
        if ($rs){
            $buy_times = $rs['buy_times'];
        }
        
        //查看用户来源（魔盒页面仅限支付宝）
        $refer = 'alipay';
        //购买次数三次以上是老客 is_new = 0
        if ($buy_times > 2){
            $banner_list['is_new'] = 0;
        } else {    //否则是新客 is_new = 1
            $banner_list['is_new'] = 1;
        }
        //显示的banner数量
        $show_banner = 5;
        
        //先拿banner中的广告
        $banner_list['banner'] = array();
        $sql = "select * from cb_active_banner where (start_time<='{$time}' and end_time>='{$time}') and box_no like '%{$search_device}%' and status=1 and type = 1 and user_rank like '%{$user_rank}%' order by id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $i = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_banner_white_user where banner_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1){
                if ($i <= 4){
                    $i++;
                    $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                }
            }
        }
        $show_active_banner = 4 - $i;
        //再拿active_banner
        if ($show_active_banner > 0){
            $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and img_banner<>'' and user_rank like '%{$user_rank}%' and platform_id={$platform_id} order by weight desc,id desc limit 0,20";
            $rs = $this->db->query($sql)->result_array();
            $j = 0;
            foreach($rs as $val){
                $is_show = 0;
                $is_refer = 0;
                $is_limit = 0;  //是否没到达最大次数
                $active_limit = $val['active_limit'];
                //调用张涛方法 看活动还剩多少次
                $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
                if ($active_num > 0){
                    $is_limit = 1;
                }
                if ($val['active_people'] == 1){//仅首单显示
                    if ($buy_times == 0){
                        $is_show = 1;
                    }
                } elseif($val['active_people'] == 2){//白名单显示
                    //获取白名单
                    $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                    $white = $this->db->query($sql)->row_array();
                    if ($white){
                        $white_user = $white['mobile'];
                        $white_user_mobile = explode("#",$white_user);
                        if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                            $is_show = 1;
                        }
                    }
                } else {
                    $is_show = 1;
                }
                //有配置商品id和分类id时需排除库存外商品
                if ($is_show == 1){
                    if ($val['product_ids'] || $val['class_id']){
                        $product_ids = $val['product_ids'];
                        $class_id = $val['class_id'];
                        $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                        $sql.=" and (";
                        if ($product_ids){
                            $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                        }
                        if ($class_id){
                            if ($product_ids){
                                $sql.=" or t4.id in (".$class_id.")";
                            } else {
                                $sql.=" t4.id in (".$class_id.")";
                            }
        
                        }
                        $sql.=" )";
                        //没有内容时 则不显示
                        $exist_products = $this->db->query($sql)->result_array();
                        if (!$exist_products){
                            $is_show = 0;
                        }
                    }
                }
                //增加判断来源
                if ($val['refer'] == 'all'){
                    $is_refer = 1;
                } else {
                    if ($val['refer'] == $refer){
                        $is_refer = 1;
                    }
                }
                if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                    if ($j <= $show_active_banner){
                        $j++;
                        $banner_list['banner'][] = array('img_url'=>IMG_HOST.'/'.$val['img_banner'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                    }
        
                }
            }
        }
        
        //再拿active_icon 取3条
        $banner_list['active_icon'] = array();
        $sql = "select * from cb_active_discount where (start_time<='{$time}' and end_time>='{$time}') and (box_no like '%{$search_device}%' or box_num = 1) and active_status=1 and img_icon<>'' and icon_show = 1 and user_rank like '%{$user_rank}%' and platform_id={$platform_id} order by weight desc,id desc limit 0,20";
        $rs = $this->db->query($sql)->result_array();
        $k = 0;
        foreach($rs as $val){
            $is_show = 0;
            $is_refer = 0;
            $is_limit = 0;  //是否没到达最大次数
            $active_limit = $val['active_limit'];
            //调用张涛方法 看活动还剩多少次
            $active_num = $this->active_discount_model->get_active_limit($val['id'], $device_id, $active_limit,$val['active_type']);
            if ($active_num > 0){
                $is_limit = 1;
            }
            if ($val['active_people'] == 1){//仅首单显示
                if ($buy_times == 0){
                    $is_show = 1;
                }
            } elseif($val['active_people'] == 2){//白名单显示
                //获取白名单
                $sql = "select mobile from cb_active_white_user where active_id = {$val['id']}";
                $white = $this->db->query($sql)->row_array();
                if ($white){
                    $white_user = $white['mobile'];
                    $white_user_mobile = explode("#",$white_user);
                    if ($user_mobile&&in_array($user_mobile,$white_user_mobile)){
                        $is_show = 1;
                    }
                }
            } else {
                $is_show = 1;
            }
            //有配置商品id和分类id时需排除库存外商品
            if ($is_show == 1){
                if ($val['product_ids'] || $val['class_id']){
                    $product_ids = $val['product_ids'];
                    $class_id = $val['class_id'];
                    $sql = "SELECT t1.label,t1.equipment_id AS equipment_id,t2.product_id AS product_id,t3.product_name AS product_name,t4.id AS class_id,t4.name AS class_name FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id LEFT JOIN cb_product_class t4 ON t4.id = t3.class_id WHERE t1.equipment_id = '".$device_id."' AND t1.status = 'active'";
                    $sql.=" and (";
                    if ($product_ids){
                        $sql.="t2.product_id in (".str_replace('#', ',', $product_ids).")";
                    }
                    if ($class_id){
                        if ($product_ids){
                            $sql.=" or t4.id in (".$class_id.")";
                        } else {
                            $sql.=" t4.id in (".$class_id.")";
                        }
        
                    }
                    $sql.=" )";
                    //没有内容时 则不显示
                    $exist_products = $this->db->query($sql)->result_array();
                    if (!$exist_products){
                        $is_show = 0;
                    }
                }
            }
            //增加判断来源
            if ($val['refer'] == 'all'){
                $is_refer = 1;
            } else {
                if ($val['refer'] == $refer){
                    $is_refer = 1;
                }
            }
            if ($is_show == 1 && $is_refer == 1 && $is_limit == 1){
                if ($k < 3){
                    $k++;
                    $banner_list['active_icon'][] = array('img_url'=>IMG_HOST.'/'.$val['img_icon'],'start_time'=>date('Y.m.d',strtotime($val['start_time'])),'end_time'=>date('Y.m.d',strtotime($val['end_time'])),'remarks'=>$val['remarks']);
                }
        
            }
        }
        if(count($banner_list['banner']) == 0){
            //没有轮播并且没有icon，则默认给一张轮播图片
            $banner_list['banner'][] = array('img_url'=>DEFALUT_BANNER_IMG,'start_time'=>date('Y.m.d'),'end_time'=>date('Y.m.d'),'remarks'=>'CITYBOX魔盒');
        }
        
        //获取盒子中还有哪些商品(价格需考虑溢价设备)
        $array_class = array();
        $exist = array();
        $array_products = array();
        $sql = "SELECT t1.product_id,t2.product_name,t1.stock,t2.img_url,t2.class_id,t2.volume,t2.price,t3.name AS class_name,t4.id AS parent_class_id,t4.name AS parent_class_name FROM cb_equipment_stock t1 LEFT JOIN cb_product t2 ON t2.id = t1.product_id LEFT JOIN cb_product_class t3 ON t3.id = t2.class_id LEFT JOIN cb_product_class t4 ON t4.id = t3.parent_id  WHERE equipment_id = '".$device_id."' ORDER BY t4.id ASC,t1.product_id ASC";
        $ps = $this->db->query($sql)->result_array();
        foreach ($ps as $i=>$p){
            if (!in_array($p['parent_class_id'],$exist)){
                if ($p['parent_class_id']){
                    $array_class[] = array('id'=>$p['parent_class_id'],'name'=>$p['parent_class_name']);
                } else {
                    $array_class[] = array('id'=>0,'name'=>'其他');
                }
                $exist[] = $p['parent_class_id'];
            }
            if ($p['parent_class_id']){
                $array_products[] = array('parent_class_id'=>$p['parent_class_id'],'img_url'=>IMG_HOST.'/'.$p['img_url'],'product_name'=>$p['product_name'],'volume'=>$p['volume'],'price'=>$p['price'],'stock'=>$p['stock']);
            } else {
                $array_products[] = array('parent_class_id'=>0,'img_url'=>IMG_HOST.'/'.$p['img_url'],'product_name'=>$p['product_name'],'volume'=>$p['volume'],'price'=>$p['price'],'stock'=>$p['stock']);
            }
            
        }
        $banner_list['classes'] = $array_class;
        $banner_list['products'] = $array_products;
        
        $return_result = $banner_list;
        $this->send_ok_response($return_result);
    }
    
    //扫码领取优惠券
    function qr_card_get(){
        $qrcard_id = $this->get('qrcard_id');
        $this->check_null_and_send_error($qrcard_id,"缺少参数");
        $sql = "select * from cb_qrcard where id = '".$qrcard_id."' and active_status = 1";
        $qrcard = $this->db->query($sql)->row_array();
        $error_tips = '';
        if (!$qrcard){
            $this->send_error_response("主人，活动不存在哦");
        }
        $time = date('Y-m-d H:i:s');
        $act_status= 1;//正常
        if ($time < $qrcard['start_time']){
            $error_tips = '主人，活动尚未开始';
            $act_status = 0;//未开始
        }
        if ($time > $qrcard['end_time']){ 
            $error_tips = '主人，活动都结束好久了';
            $act_status = 0;//未开始
        }

        //获取用户id 判断今天是否参加过
        $user = $this->get_curr_user();
        $uid = $user['id'];
        $select_start_time = date('Y-m-d 00:00:00');
        $select_end_time = date('Y-m-d 23:59:59');
        if ($qrcard['get_type'] == 1){//每天一次
            $sql = "select * from cb_card where uid = '".$uid."' and card_active_id='".$qrcard_id."' and card_source = 3 and send_time between '".$select_start_time."' and '".$select_end_time."' ";
        } else {//共计一次
            $sql = "select * from cb_card where uid = '".$uid."' and card_active_id='".$qrcard_id."' and card_source = 3";
        }
        $is_attend = $this->db->query($sql)->row_array();
        if ($is_attend && $act_status == 1){
            if ($qrcard['get_type'] == 1){
                $error_tips = '主人，每天只能领取一次哦';
            } else {
                $error_tips = '主人，活动期间只能领取一次哦';
            }
            
        }
        $return_result = array();
        $return_result['status'] = $act_status;//add by linxingyu
        //返回此活动相关信息
        $rule_text = $qrcard['rule_text'];
        //活动规则
        $return_result['rule_text'] = explode("\n",$rule_text);
        //活动名称
        $return_result['title'] = $qrcard['title'];
        //上方banner图
        $return_result['banner_img'] = IMG_HOST.'/'.$qrcard['banner_img'];
        //分享图
        $return_result['share_img'] = IMG_HOST.'/'.$qrcard['share_img'];
        //分享标题
        $return_result['share_title'] = $qrcard['share_title'];
        //分享文案
        $return_result['share_content'] = $qrcard['share_content'];
        //领取的类型
        $return_result['get_type'] = $qrcard['get_type'];

        //用户抽奖流程
        $cards = json_decode($qrcard['card_model_config'], true);
        $i = 1;
        foreach ($cards as &$val) {
            $val['id'] = $i;
            $i++;
            $arr[$val['id']] = $val['percent'];
        }
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        $card_tag = $cards[$rid - 1]['tag']; //中奖项
        //获取此优惠券的信息
        $sql = "select * from cb_card_model where tag = '".$card_tag."'";
        $card_model_info = $this->db->query($sql)->row_array();
        if (!$card_model_info && $act_status == 1){
            $error_tips = '主人，动作太快人家受不了';
        }
        //返回用户领取到的优惠券相关信息
        $return_result['coupon_money'] = $card_model_info['card_money'];
        $return_result['coupon_remarks'] = $card_model_info['card_remarks'];
        $return_result['coupon_begin_date'] = $card_model_info['card_begin_date'];
        $return_result['coupon_end_date'] = $card_model_info['card_end_date'];
        //参加过则返回实际领取的券
        if ($is_attend){
            $real_tag = $is_attend['tag'];
            $real = "select * from cb_card_model where tag = '".$real_tag."'";
            $real_info = $this->db->query($real)->row_array();
            $return_result['coupon_money'] = $real_info['card_money'];
            $return_result['coupon_remarks'] = $real_info['card_remarks'];
            $return_result['coupon_begin_date'] = $real_info['card_begin_date'];
            $return_result['coupon_end_date'] = $real_info['card_end_date'];
        }
        //发放优惠券
        $card_data = array(
            'card_number' =>'',//空
            'uid' => '',
            'send_time'=>date('Y-m-d H:i:s'),
            'card_money'=>$card_model_info['card_money'],
            'order_money_limit'=>isset($card_model_info['order_money_limit']) ? $card_model_info['order_money_limit'] : '',
            'product_limit_type'=>$card_model_info['product_limit_type'],
            'use_with_sales'=>$card_model_info['use_with_sales'],
            'source'=>$card_model_info['source_limit'],
            'tag'=>$card_model_info['tag'],
            'card_name'=>$card_model_info['card_remarks'],
            'is_used'=>0,
            'platform_id'=>$card_model_info['platform_id'],
            'card_source'=>3,
            'card_active_id'=>$qrcard_id
        );
        
        if($card_model_info['order_limit_type']==1){
            $card_data['order_money_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 2;
        }elseif($card_model_info['order_limit_type']==2){
            $card_data['order_product_num_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 3;
        }else{//do nothing
            $order_limit_type = 1;
        }
        
        $card_data['order_limit_type'] = $order_limit_type;
        
        if($card_model_info['product_limit_type']==1){
            $card_data['product_id'] = $card_model_info['product_limit_value'];
        }elseif($card_model_info['product_limit_type']==2){
            $card_data['class_id'] = $card_model_info['product_limit_value'];
        }else{//do nothing
        }
        
        if($card_model_info['time_limit_type'] == 1){
            $card_data['begin_date'] = $card_model_info['card_begin_date'];
            $card_data['to_date'] = $card_model_info['card_end_date'];
        }else{
            $card_data['begin_date'] = date('Y-m-d');
            $card_data['to_date'] = date("Y-m-d",strtotime("+".($card_model_info['card_last']?$card_model_info['card_last']:1)." day"));;
        }
        $card_data['card_number'] = $this->rand_card_number($card_model_info['card_pre']);
        $card_data['uid'] = $uid;
        if ($error_tips == ''){
            $this->db->insert('card',$card_data);
            $insert_id = $this->db->insert_id();
            if ($insert_id > 0){
                
            } else {
                $error_tips = '主人，动作太快人家受不了';
            }
        }
        //获取此活动中奖的10个用户
        $sql = "SELECT t1.card_money,t2.user_name,t2.avatar,DATE_FORMAT(t1.send_time,'%m.%d %H:%i') AS send_time FROM cb_card t1 LEFT JOIN cb_user t2 ON t1.uid = t2.id WHERE card_active_id = '".$qrcard_id."' and card_source = 3 ORDER BY t1.id DESC LIMIT 10";
        $card_users = $this->db->query($sql)->result_array();
        $return_result['card_users'] = $card_users;
        //新增返回错误信息
        $return_result['error_tips'] = $error_tips;
        $this->send_ok_response($return_result);
    }
    
    
    //首页领取优惠券
    function index_card_get(){
        $card_id = $this->get('card_id');
        $this->check_null_and_send_error($card_id,"缺少参数");
        $sql = "select * from cb_indexcard where id = '".$card_id."' and active_status = 1";
        $indexcard = $this->db->query($sql)->row_array();
        $error_tips = '';
        if (!$indexcard){
            $this->send_error_response("主人，活动不存在哦");
        }
        $time = date('Y-m-d H:i:s');
        $act_status= 1;//正常
        if ($time < $indexcard['start_time']){
            $this->send_error_response("主人，活动尚未开始");
            $act_status = 0;//未开始
        }
        if ($time > $indexcard['end_time']){
            $this->send_error_response("主人，活动都结束好久了");
            $act_status = 0;//未开始
        }
        $per_day = $indexcard['per_day'];
        $start_hour = $indexcard['start_hour'];
        $end_hour = $indexcard['end_hour'];
        $his = date('H:i:s');
        $nowday = date('w');
        $search_nowday = ','.$nowday.',';
        if (strpos($per_day,$search_nowday) === false){
            $this->send_error_response("不在领取时间段内哦！");
            $act_status = 0;//未开始
        }
        if ($start_hour < $his && $end_hour > $his){
            
        } else {
            $this->send_error_response("不在领取时间段内哦！");
            $act_status = 0;//未开始
        }
        
    
        //获取用户id 判断今天是否参加过
        $user = $this->get_curr_user();
        /* $user_sql = 'select * from cb_user where id = 1';
         $user = $this->db->query($user_sql)->row_array(); */
        $uid = $user['id'];
        $select_start_time = date('Y-m-d 00:00:00');
        $select_end_time = date('Y-m-d 23:59:59');
        if ($indexcard['get_type'] == 1){//每天一次
            $sql = "select * from cb_card where uid = '".$uid."' and card_active_id='".$card_id."' and card_source = 4 and send_time between '".$select_start_time."' and '".$select_end_time."' ";
        } else {//共计一次
            $sql = "select * from cb_card where uid = '".$uid."' and card_active_id='".$card_id."' and card_source = 4";
        }
        $is_attend = $this->db->query($sql)->row_array();
        if ($is_attend && $act_status == 1){
            if ($indexcard['get_type'] == 1){
                $error_tips = '您已领取过此福利';
            } else {
                $error_tips = '您已领取过此福利';
            }
    
        }
        $return_result = array();
        $return_result['status'] = $act_status;//add by linxingyu
        
        $card_tag = $indexcard['tag']; //中奖项
        //获取此优惠券的信息
        $sql = "select * from cb_card_model where tag = '".$card_tag."'";
        $card_model_info = $this->db->query($sql)->row_array();
        if (!$card_model_info && $act_status == 1){
            $error_tips = '主人，动作太快人家受不了';
        }
        //返回用户领取到的优惠券相关信息
        $return_result['coupon_money'] = $card_model_info['card_money'];
        $return_result['coupon_remarks'] = $card_model_info['card_remarks'];
        $return_result['coupon_begin_date'] = $card_model_info['card_begin_date'];
        $return_result['coupon_end_date'] = $card_model_info['card_end_date'];
        $return_result['product_limit_type'] = $card_model_info['product_limit_type'];
        //参加过则返回实际领取的券
        $return_result['is_attend'] = 0;
        if ($is_attend){
            $return_result['is_attend'] = 1;
            $real_tag = $is_attend['tag'];
            $real = "select * from cb_card_model where tag = '".$real_tag."'";
            $real_info = $this->db->query($real)->row_array();
            $return_result['coupon_money'] = $real_info['card_money'];
            $return_result['coupon_remarks'] = $real_info['card_remarks'];
            $return_result['coupon_begin_date'] = $real_info['card_begin_date'];
            $return_result['coupon_end_date'] = $real_info['card_end_date'];
        }
        //发放优惠券
        $card_data = array(
            'card_number' =>'',//空
            'uid' => '',
            'send_time'=>date('Y-m-d H:i:s'),
            'card_money'=>$card_model_info['card_money'],
            'order_money_limit'=>isset($card_model_info['order_money_limit']) ? $card_model_info['order_money_limit'] : '',
            'product_limit_type'=>$card_model_info['product_limit_type'],
            'use_with_sales'=>$card_model_info['use_with_sales'],
            'source'=>$card_model_info['source_limit'],
            'tag'=>$card_model_info['tag'],
            'card_name'=>$card_model_info['card_remarks'],
            'is_used'=>0,
            'platform_id'=>$card_model_info['platform_id'],
            'card_source'=>4,
            'card_active_id'=>$card_id
        );
    
        if($card_model_info['order_limit_type']==1){
            $card_data['order_money_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 2;
        }elseif($card_model_info['order_limit_type']==2){
            $card_data['order_product_num_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 3;
        }else{//do nothing
            $order_limit_type = 1;
        }
    
        $card_data['order_limit_type'] = $order_limit_type;
    
        if($card_model_info['product_limit_type']==1){
            $card_data['product_id'] = $card_model_info['product_limit_value'];
            $product_limit_value = $card_data['product_id'];
            $where_value = '('.$product_limit_value.')';
            $v_sql = "select * from cb_product where id in ".$where_value."";
            $products = $this->db->query($v_sql)->result_array();
            $limit_products = '';
            foreach($products as $eachProduct){
                $limit_products = $limit_products.$eachProduct['product_name'].',';
            }
            $limit_products = substr($limit_products,0,strlen($limit_products)-1);
            $return_result['product_limit_value'] = $limit_products;
        }elseif($card_model_info['product_limit_type']==2){
            $card_data['class_id'] = $card_model_info['product_limit_value'];
            $product_limit_value = $card_data['class_id'];
            $where_value = '('.$product_limit_value.')';
            $v_sql = "select * from cb_product_class where id in ".$where_value."";
            $product_classes = $this->db->query($v_sql)->result_array();
            $limit_product_classes = '';
            foreach($product_classes as $eachProductclass){
                $limit_product_classes = $limit_product_classes.$eachProductclass['name'].',';
            }
            $limit_product_classes = substr($limit_product_classes,0,strlen($limit_product_classes)-1);
            $return_result['product_limit_value'] = $limit_product_classes;
        }else{//do nothing
        }
    
        if($card_model_info['time_limit_type'] == 1){
            $card_data['begin_date'] = $card_model_info['card_begin_date'];
            $card_data['to_date'] = $card_model_info['card_end_date'];
        }else{
            $card_data['begin_date'] = date('Y-m-d');
            $card_data['to_date'] = date("Y-m-d",strtotime("+".($card_model_info['card_last']?$card_model_info['card_last']:1)." day"));;
        }
        $card_data['card_number'] = $this->rand_card_number($card_model_info['card_pre']);
        $card_data['uid'] = $uid;
        if ($error_tips == ''){
            $this->db->insert('card',$card_data);
            $insert_id = $this->db->insert_id();
            if ($insert_id > 0){
    
            } else {
                $error_tips = '主人，动作太快人家受不了';
            }
        }
        //新增返回错误信息
        $return_result['error_tips'] = $error_tips;
        $this->send_ok_response($return_result);
    }
    
    //结算页领取优惠券
    function order_card_get(){
        $ordercard_id = $this->get('ordercard_id');
        $order_name = $this->get('order_name');
        /* $ordercard_id = 1;
        $order_name = '12345'; */
        $this->check_null_and_send_error($ordercard_id,"缺少参数");
        $this->check_null_and_send_error($order_name,"缺少参数");
        $sql = "select * from cb_ordercard where id = '".$ordercard_id."' and active_status = 1";
        $ordercard = $this->db->query($sql)->row_array();
        $error_tips = '';
        if (!$ordercard){
            $this->send_error_response("主人，活动不存在哦");
        }
        $time = date('Y-m-d H:i:s');
        $act_status= 1;//正常
        if ($time < $ordercard['start_time']){
            $error_tips = '主人，活动尚未开始';
            $act_status = 0;//未开始
        }
        if ($time > $ordercard['end_time']){
            $error_tips = '主人，活动都结束好久了';
            $act_status = 0;//未开始
        }
    
        //获取用户id 判断今天是否参加过
        $user = $this->get_curr_user();
        /* $user_sql = 'select * from cb_user where id = 11';
        $user = $this->db->query($user_sql)->row_array(); */
        $uid = $user['id'];
        $select_start_time = date('Y-m-d 00:00:00');
        $select_end_time = date('Y-m-d 23:59:59');
        $sql = "select * from cb_card where uid = '".$uid."' and card_order_name='".$order_name."' and card_source = 5";
        $is_attend = $this->db->query($sql)->row_array();
        if ($is_attend && $act_status == 1){
            $error_tips = '主人，只能领取一次哦';
        }
        $return_result = array();
        $return_result['status'] = $act_status;//add by linxingyu
        //返回此活动相关信息
        $rule_text = $ordercard['rule_text'];
        //活动规则
        $return_result['rule_text'] = explode("\n",$rule_text);
        //活动名称
        $return_result['title'] = $ordercard['title'];
        //分享图
        $return_result['share_img'] = IMG_HOST.'/'.$ordercard['share_img'];
        //分享标题
        $return_result['share_title'] = $ordercard['share_title'];
        //分享文案
        $return_result['share_content'] = $ordercard['share_content'];
        //一共几个红包
        $return_result['card_count'] = $ordercard['card_count'];
        //已经领取了几个红包
        $sql = "select count(*) as count_card from cb_card where card_order_name='".$order_name."' and card_source = 5";
        $card_count_row = $this->db->query($sql)->row_array();
        $card_count = $card_count_row['count_card'];
        if ($card_count >= $ordercard['card_count']){
            $error_tips = '红包已经抢完拉！';
        }
        
        //概率的分母(红包总数-已经领取的红包数)
        $exist_count = $ordercard['card_count'] - $card_count;
        $return_result['exist_count'] = $exist_count;
    
        //用户抽奖流程
        $cards = json_decode($ordercard['card_model_config'], true);
        $i = 1;
        $arr_tags = array();
        foreach ($cards as &$val) {
            $arr_tags[] = $val['tag'];
            //计算当前概率
            $tag = $val['tag'];
            $count = $val['count'];
            $sql = "select count(*) as count_tag from cb_card where tag = '".$tag."' and card_order_name='".$order_name."' and card_source = 5";
            $count_tag_row = $this->db->query($sql)->row_array();
            $count_tag = $count_tag_row['count_tag'];
            //概率的分子
            $current_count = $count - $count_tag;
            if ($exist_count > 0){
                $percent = round($current_count / $exist_count * 100);
            } else {
                $percent = 100;
            }
            
            $val['id'] = $i;
            $i++;
            $arr[$val['id']] = $percent;
        }
        $rid = $this->get_rand($arr); //根据概率获取奖项id
        $card_tag = $cards[$rid - 1]['tag']; //中奖项
        //获取此优惠券的信息
        $sql = "select * from cb_card_model where tag = '".$card_tag."'";
        $card_model_info = $this->db->query($sql)->row_array();
        if (!$card_model_info && $act_status == 1){
            $error_tips = '主人，动作太快人家受不了';
        }
        //返回用户领取到的优惠券相关信息
        $return_result['coupon_money'] = $card_model_info['card_money'];
        $return_result['coupon_remarks'] = $card_model_info['card_remarks'];
        $return_result['coupon_begin_date'] = $card_model_info['card_begin_date'];
        $return_result['coupon_end_date'] = $card_model_info['card_end_date'];
        $return_result['is_attend'] = 0;
        //参加过则返回实际领取的券
        if ($is_attend){
            $return_result['is_attend'] = 1;
            $real_tag = $is_attend['tag'];
            $real = "select * from cb_card_model where tag = '".$real_tag."'";
            $real_info = $this->db->query($real)->row_array();
            $return_result['coupon_money'] = $real_info['card_money'];
            $return_result['coupon_remarks'] = $real_info['card_remarks'];
            $return_result['coupon_begin_date'] = $real_info['card_begin_date'];
            $return_result['coupon_end_date'] = $real_info['card_end_date'];
        }
        //发放优惠券
        $card_data = array(
            'card_number' =>'',//空
            'uid' => '',
            'send_time'=>date('Y-m-d H:i:s'),
            'card_money'=>$card_model_info['card_money'],
            'order_money_limit'=>isset($card_model_info['order_money_limit']) ? $card_model_info['order_money_limit'] : '',
            'product_limit_type'=>$card_model_info['product_limit_type'],
            'use_with_sales'=>$card_model_info['use_with_sales'],
            'source'=>$card_model_info['source_limit'],
            'tag'=>$card_model_info['tag'],
            'card_name'=>$card_model_info['card_remarks'],
            'is_used'=>0,
            'platform_id'=>$card_model_info['platform_id'],
            'card_source'=>5,
            'card_active_id'=>$ordercard_id,
            'card_order_name'=>$order_name
        );
    
        if($card_model_info['order_limit_type']==1){
            $card_data['order_money_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 2;
        }elseif($card_model_info['order_limit_type']==2){
            $card_data['order_product_num_limit'] = $card_model_info['order_limit_value'];
            $order_limit_type = 3;
        }else{//do nothing
            $order_limit_type = 1;
        }
    
        $card_data['order_limit_type'] = $order_limit_type;
    
        if($card_model_info['product_limit_type']==1){
            $card_data['product_id'] = $card_model_info['product_limit_value'];
        }elseif($card_model_info['product_limit_type']==2){
            $card_data['class_id'] = $card_model_info['product_limit_value'];
        }else{//do nothing
        }
    
        if($card_model_info['time_limit_type'] == 1){
            $card_data['begin_date'] = $card_model_info['card_begin_date'];
            $card_data['to_date'] = $card_model_info['card_end_date'];
        }else{
            $card_data['begin_date'] = date('Y-m-d');
            $card_data['to_date'] = date("Y-m-d",strtotime("+".($card_model_info['card_last']?$card_model_info['card_last']:1)." day"));;
        }
        $card_data['card_number'] = $this->rand_card_number($card_model_info['card_pre']);
        $card_data['uid'] = $uid;
        if ($error_tips == ''){
            $this->db->insert('card',$card_data);
            $insert_id = $this->db->insert_id();
            if ($insert_id > 0){
    
            } else {
                $error_tips = '主人，动作太快人家受不了';
            }
        }
        //获取此活动中奖的10个用户
        $sql = "SELECT t1.card_money,t2.user_name,t2.avatar,DATE_FORMAT(t1.send_time,'%m.%d %H:%i') AS send_time FROM cb_card t1 LEFT JOIN cb_user t2 ON t1.uid = t2.id WHERE card_order_name = '".$order_name."' and card_source = 5 ORDER BY t1.id DESC";
        $card_users = $this->db->query($sql)->result_array();
        //获取优惠券的最大面额
        $this->db->select('max(card_money) max_card_money');
        $this->db->where_in('tag',$arr_tags);
        $this->db->from('card_model');
        $max_card_money = $this->db->get()->row()->max_card_money;
        foreach ($card_users as $k=>&$v){
            if ($v['card_money'] == $max_card_money){
                $v['is_best'] = 1;
                for ($j = 0 ;$j<$k;$j++){
                    $card_users[$j]['is_best'] = 0;
                }
            } else {
                $v['is_best'] = 0;
            }
            
        }
        $return_result['card_users'] = $card_users;
        //新增返回错误信息
        $return_result['error_tips'] = $error_tips;
        $this->send_ok_response($return_result);
    }
    
    //领取第三方优惠券
    public function thirdparty_card_get(){
        $thirdpartycard_id = $this->get('thirdpartycard_id');
        $mobile = $this->get('mobile');
        /* $thirdpartycard_id = 1;
           $mobile = '13333333338'; */
        $this->check_null_and_send_error($thirdpartycard_id,"缺少参数");
        $this->check_null_and_send_error($mobile,"缺少参数");
        $sql = "select * from cb_thirdpartycard where id = '".$thirdpartycard_id."' and active_status = 1";
        $thirdpartycard = $this->db->query($sql)->row_array();
        $error_tips = '';
        if (!$thirdpartycard){
            $this->send_error_response("主人，活动不存在哦");
        }
        $refer = $thirdpartycard['refer'];
        $platform_id = $thirdpartycard['platform_id'];
        $time = date('Y-m-d H:i:s');
        $act_status= 1;//正常
        if ($time < $thirdpartycard['start_time']){
            $this->send_error_response("主人，活动尚未开始");
            //$error_tips = '主人，活动尚未开始';
            //$act_status = 0;//未开始
        }
        if ($time > $thirdpartycard['end_time']){
            $this->send_error_response("主人，活动都结束好久了");
            //$error_tips = '主人，活动都结束好久了';
            //$act_status = 0;//未开始
        }
    
        $user = $this->get_curr_user();
        /* $user_sql = 'select * from cb_user where id = 1';
        $user = $this->db->query($user_sql)->row_array(); */
        $uid = $user['id'];
        $select_start_time = date('Y-m-d 00:00:00');
        $select_end_time = date('Y-m-d 23:59:59');
        $sql = "select * from cb_card_third where uid = '".$uid."' and active_id='".$thirdpartycard_id."' and status = 1";
        $is_attend = $this->db->query($sql)->row_array();
        if ($is_attend){
            $old_mobile = $is_attend['mobile'];
            if ($old_mobile == $mobile){
                $this->send_error_response("已经领取");
            } else {
                //销毁之前的优惠券
                $this->db->update('card_third',array('status'=>0),array('uid'=>$uid,'active_id'=>$thirdpartycard_id,'status'=>1));
            }
            //$this->send_error_response("主人，只能领取一次哦");
            //$error_tips = '主人，只能领取一次哦';
        }
        $return_result = array();
        $return_result['status'] = $act_status;//add by linxingyu
        //返回此活动相关信息
        $rule_text = $thirdpartycard['rule_text'];
        //活动规则
        $return_result['rule_text'] = explode("\n",$rule_text);
        //活动名称
        $return_result['title'] = $thirdpartycard['title'];
        //一共几个红包
        $return_result['card_count'] = $thirdpartycard['card_count'];
        //已经领取了几个红包
        $sql = "select count(*) as count_card from cb_card_third where active_id='".$thirdpartycard_id."' and status = 1";
        $card_count_row = $this->db->query($sql)->row_array();
        $card_count = $card_count_row['count_card'];
        if ($card_count >= $thirdpartycard['card_count']){
            $this->send_error_response("优惠券已经发完拉！");
            //$error_tips = '优惠券已经发完拉！';
        }
        
        $exist_count = $thirdpartycard['card_count'] - $card_count;
        $return_result['exist_count'] = $exist_count;
    
        //发放优惠券
        $return_result['is_attend'] = 0;
        //城超优惠券流程
        if ($refer == 'cityshop'){
            //4个参数分别为用户id，用户输入的手机号，活动id，来源
            $send_result = $this->send_thirdparty_cityshop($uid,$mobile,$thirdpartycard_id,$refer,$platform_id);
            if ($send_result['code'] == 1){
                $return_result['coupon_money'] = $send_result['card_money'];
                $return_result['order_money_limit'] = $send_result['order_money_limit'];
                $return_result['coupon_type'] = $send_result['coupon_type'];
                $return_result['coupon_name'] = $send_result['coupon_name'];
                $return_result['use_desc'] = $send_result['use_desc'];
                $return_result['remark'] = $send_result['remark'];
                $return_result['coupon_begin_date'] = date('Y.m.d',strtotime($send_result['coupon_begin_date']));
                $return_result['coupon_end_date'] = date('Y.m.d',strtotime($send_result['coupon_end_date']));
            } else {
                $this->send_error_response($send_result['send_err_msg']);
            }
            
        }
        //新增返回错误信息
        //$return_result['error_tips'] = $error_tips;
        $this->send_ok_response($return_result);
    }
    
    private function send_thirdparty_cityshop($uid,$mobile,$active_id,$refer,$platform_id){
        //获取第三方优惠券
        $thirdpartycard = $this->config->item("thirdparty","thirdpartycard");
        if ($refer == 'cityshop'){
            $cityshop_config = $thirdpartycard['cityshop'];
            $aes_key = $cityshop_config['aes_key'];
            $validate_url = $cityshop_config['validate_url'];
            $coupon_url = $cityshop_config['coupon_url'];
            $coupon_id = $cityshop_config['coupon_id'];
            $send_err_msg = $cityshop_config['send_err_msg'];
            $headers = array();
            $params = array('aes_key'=>$aes_key);
            //'http://pre.haocaiji.shop/index.php?route=openapi/coupon/sendCoupon'
            $url = $validate_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            $data = $result->data;
            $token = $data ->token;
            curl_close($ch);
            
            $headers = array();
            $params = array(
                'aes_key'=>$aes_key,
                'token'=>$token,
                'coupon_id'=>$coupon_id,
                'tel'=>$mobile
            );
            $url = $coupon_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            $return_result['code'] = $result->code;
            if ($result->code == 1){
                $data = $result->data;
                $coupon_rule = $data->coupon_rule;
                $arr_coupon_rule = explode('-',$coupon_rule);
                $return_result['card_money'] = $arr_coupon_rule[1];
                $return_result['order_money_limit'] = $arr_coupon_rule[0];
                $return_result['coupon_type'] = $data->coupon_type;
                $return_result['coupon_name'] = $data->coupon_name;
                $return_result['use_desc'] = $data->use_desc;
                $return_result['remark'] = $data->remark;
                $return_result['coupon_begin_date'] = $data->start_date;
                $return_result['coupon_end_date'] = $data->end_date;
                $return_result['card_number'] = $data->coupon_no;
                //往card_third表插记录
                $insert_data = array(
                    'card_number'=>$return_result['card_number'],
                    'active_id'=>$active_id,
                    'coupon_id'=>$coupon_id,
                    'uid'=>$uid,
                    'mobile'=>$mobile,
                    'send_time'=>date('Y-m-d H:i:s'),
                    'coupon_name'=>$return_result['coupon_name'],
                    'use_desc'=>$return_result['use_desc'],
                    'remark'=>$return_result['remark'],
                    'begin_date'=>$return_result['coupon_begin_date'],
                    'end_date'=>$return_result['coupon_end_date'],
                    'card_money'=>$return_result['card_money'],
                    'order_money_limit'=>$return_result['order_money_limit'],
                    'refer'=>$refer,
                    'coupon_type'=>$return_result['coupon_type'],
                    'platform_id'=>$platform_id
                );
                $this->db->insert('card_third',$insert_data);
            } else {
                $return_result['send_err_msg'] = $cityshop_config['send_err_msg'][$result->msg];
            }
            return $return_result;
        }
    }
    
    //点击详情
    public function thirdparty_detail_get(){
        $id = $this->get('id');
        $this->check_null_and_send_error($id,"缺少参数");
        $sql = "select * from cb_card_third where id = '".$id."' and status = 1";
        $card_third = $this->db->query($sql)->row_array();
        if (!$card_third){
            $this->send_error_response("优惠券不存在！");
        }
        $mobile = $card_third['mobile'];
        $coupon_no = $card_third['card_number'];
        $active_id = $card_third['active_id'];
        $refer = $card_third['refer'];
        $is_used = 0 ;
        if ($card_third['is_used'] == 1){
            $is_used = 1;
        } else {
            //城超接口看有没有使用过
            $detail_result = $this->detail_thirdparty_cityshop($coupon_no,$mobile,$refer);
            if ($detail_result['code'] == 1){
                $is_used = $detail_result['is_used'];
            } else {
                $this->send_error_response($detail_result['send_err_msg']);
            }
        }
        
        if ($is_used == 1){
            $return_result['is_used'] = 1;
        } else {
            $return_result['is_used'] = 0;
            //更新点击时间
            $this->db->update('card_third',array('detail_time'=>date('Y-m-d H:i:s')),array('id'=>$id));
            $sql = "select * from cb_thirdpartycard where id = '".$active_id."' and active_status = 1";
            $thirdpartycard = $this->db->query($sql)->row_array();
            //返回此活动相关信息
            $rule_text = $thirdpartycard['rule_text'];
            //活动规则
            $return_result['rule_text'] = explode("\n",$rule_text);
            $return_result['card_number'] = $coupon_no;
            $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $data = base64_encode($bc->getBarcode($coupon_no, $bc::TYPE_CODE_128, 4, 100));
            $src = "data:image/png;base64,{$data}";
            $return_result['img_src'] = $src;
        }
        $this->send_ok_response($return_result);
    }
    
    private function detail_thirdparty_cityshop($coupon_no,$mobile,$refer){
        //获取第三方优惠券
        $thirdpartycard = $this->config->item("thirdparty","thirdpartycard");
        if ($refer == 'cityshop'){
            $cityshop_config = $thirdpartycard['cityshop'];
            $aes_key = $cityshop_config['aes_key'];
            $validate_url = $cityshop_config['validate_url'];
            $used_url = $cityshop_config['used_url'];
            $coupon_id = $cityshop_config['coupon_id'];
            $headers = array();
            $params = array('aes_key'=>$aes_key);
            //'http://pre.haocaiji.shop/index.php?route=openapi/coupon/sendCoupon'
            $url = $validate_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            $data = $result->data;
            $token = $data ->token;
            curl_close($ch);
    
            $headers = array();
            $params = array(
                'aes_key'=>$aes_key,
                'token'=>$token,
                'coupon_no'=>$coupon_no,
                'tel'=>$mobile
            );
            $url = $used_url;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);
            $return_result['code'] = $result->code;
            if ($result->code == 1){
                $data = $result->data;
                $return_result['is_used'] = $data->status == 50 ? 0 : 1;
                if ($return_result['is_used'] == 1){
                    $this->db->update('card_third',array('is_used'=>1),array('card_number'=>$coupon_no,'mobile'=>$mobile,'is_used'=>0));
                }
            } else {
                $return_result['send_err_msg'] = $result->msg;
            }
            return $return_result;
        }
    }
    
    public function barcode_get(){
        /* $card_number = $this->get('card_number');
        $this->load->helper('utils_helper');
        $barcode = new BarCode128($card_number);
        $barcode->createBarCode(); */
        $card_number = $this->get('card_number');
        $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $data = base64_encode($bc->getBarcode($card_number, $bc::TYPE_CODE_128, 4, 100));
        $src = "data:image/png;base64,{$data}";
        return $src;
        //return "<img src = '$src' />";
        //$im = imagecreatefromstring($data);
        //imagepng($im);
    }
    
    /**
     * 绑定标签检测用户信息
     */
    function bind_user_check_post(){
        $token = $this->post("token");
        $this->check_null_and_send_error($token,"token 不能为空");
        $user = explode(":",$token);
        write_log('bind_user_check_post'.var_export($user,1));
        if(count($user) == 2){
            $key = "bind_user_token_".$user[0];
             write_log('bind_user_check_post - cache :'.$key.'->'.var_export($t,1));
            if($token == $t){
                $this->send_ok_response("succ");
            }
        }
        $this->send_error_response("你没有权限操作");
    }


    function label_product_table_get()
    {
        $labels = $this->get('labels') ?: "";;
        $this->label_product_table_action($labels);
    }

    function label_product_table_post()
    {
        $labels = $this->post('labels') ?: "";;
        $this->label_product_table_action($labels);
    }

    private function label_product_table_action($labels)
    {
        $this->load->model("label_product_model");
        $labels = ltrim($labels, '[');
        $labels = rtrim($labels, ']');
        if (!empty($labels)) {
            $arr = explode(',', $labels);
            foreach ($arr as $v) {
                $rs = $this->label_product_model->getProductByLabel(trim($v));
                if (!$rs)
                    $rs = array('product_name' => '', 'product_id' => '', 'label' => trim($v));
                $array[] = $rs;
            }
        } else {
            $array[] = array('product_name' => '没有识别到标签', 'product_id' => '没有识别到标签', 'label' => 'xxx');
        }

        $result = array(
            'total' => count($array),
            'rows' => array_values($array),
        );
        $this->send_ok_response($result);
    }


    public function bind_lable_product_get(){
        $label_product = $this->input->get('label_product');
        $this->bind_lable_product_action($label_product);
    }
    public function bind_lable_product_post(){
        $label_product = $this->input->post('label_product');
        $this->bind_lable_product_action($label_product);
    }

    private function bind_lable_product_action($label_product){
        $array_ret = array();
        $error = "";
        if(!empty($label_product)){
            $label_product = json_decode($label_product,TRUE);
            foreach ($label_product as $lp){
                if(empty($lp['label'])){
                    $error .='存在标签'.$lp['label'].'没有绑定新商品,';
                }else{
                    $data = array();
                    if(intval($lp['new_product_id']>0)){
                        $data[$lp['label']]=$lp['new_product_id'];
                        $array_ret[] = $data;
                    }else if(intval($lp['product_id'])>0){
                        $data[$lp['label']]=$lp['product_id'];
                        $array_ret[] = $data;
                    }
                }
            }
            $this->load->model('label_product_model');
            $rs =  $this->label_product_model->exportLabels($array_ret);
            $this->send_ok_response((array('total'=>'0','rows'=>array())));
        }
    }

    public function ajax_search_proudct_by_name_get(){
        $name = $this->input->get('name');
        $platform_id = $this->input->get('platform') ?: 1 ;
        $sql = "SELECT id,product_name,price FROM cb_product WHERE product_name like '%$name%' and status = 1 and platform_id =".$platform_id;
        $info = $this->db->query($sql)->result_array();
        if($info){
            $this->send_ok_response($info);
        }
    }

    //-------------------------------------北京研发的设备接收信息接口------------------------------------
    /**
     * 售货机信息
     */
    public function query_device_info_post(){
        $deviceId = $this->raw_data("deviceId");
        $this->check_null_and_send_error($deviceId,'设备id不能为空');
        $device = $this->equipment_model->get_info_by_equipment_id($deviceId);
        if(!$device){
            $this->send_error_response("设备不存在");
        }
        $qr = $this->equipment_model->get_eq_qr($deviceId);
        $device_ret = array(
            'qrImg'=>$qr['qr'],
            'address'=>$device['address'],
            'name'=>$device['name'],
            'createTime'=>date("Y-m-d H:i:s",$device['created_time']),
            'status'=>$device['status']
        );
        $this->send_ok_response($device_ret);
    }

    /**
     * 获取SIM卡的信息
     */
    public function query_sim_info_post(){
        $simId = $this->raw_data("simId");
        $this->check_null_and_send_error($simId,'simId不能为空');

        $this->load->model('sim_model');
        $rs = $this->sim_model->get_sim_info_by_id($simId);
        if(!$rs){
            $this->send_error_response("sim卡不存在");
        }
        $this->send_ok_response($rs);
    }

    /**
     * 接收售货机信息
     * 更新售货机信息
     *
     */
    public function receive_new_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $version = $this->raw_data("version");
        $currentTimestamp = $this->raw_data("currentTimestamp");
        $deviceState = $this->raw_data("deviceState");
        $powerState = $this->raw_data("powerState");
        $upsState = $this->raw_data("upsState");

        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($version,"version参数不能为空");
        $this->check_null_and_send_error($currentTimestamp,"currentTimestamp参数不能为空");
        $this->check_null_and_send_error($deviceState,"deviceState参数不能为空");
        $this->check_null_and_send_error($powerState,"powerState参数不能为空");
        $this->check_null_and_send_error($upsState,"upsState参数不能为空");

        $msg_log_id = $this->log_msg($msg_id,"box_info",$this->raw_data(),$deviceId);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        $device->action_box_info($this->raw_data());

        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);;

    }

    /**
     * 接收售货机心跳信息
     *
     */
    public function receive_heart_new_msg_post()
    {
//        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
//        $msgCreateTime = $this->raw_data("msgCreateTime");
//        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
//        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
//        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
//
//        $msg_log_id = $this->log_msg($msg_id,"heart",$this->raw_data(),$deviceId);
//
//        $ret = array(
//            "status"=>true,
//            "message"=>"接收成功"
//        );
//        $this->update_msg($msg_log_id,$ret,"close");
//        $this->send_ok_response($ret);;
        $this->heart_msg($deviceId);

    }
    /**
     * 接收售货机电源变化
     */
    public function receive_power_new_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $powerState = $this->raw_data("powerState");
        $upsState = $this->raw_data("upsState");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($powerState,"powerState参数不能为空");
        $this->check_null_and_send_error($upsState,"upsState参数不能为空");
        $msg_log_id = $this->log_msg($msg_id,"power",$this->raw_data(),$deviceId);

        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);
    }

    /**
     * 接收开关门信息
     * msgType 消息类型，1-门已打开 2-门已关闭 3-开门超时自动上锁
     */
    public function receive_door_new_msg_post()
    {

        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $msgType = $this->raw_data("msgType");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($msgType,"msgType参数不能为空");


        $type = "open_door";
        if($msgType == "2"){
            $type = "close_door";
        }else if($msgType == "1"){

        }elseif($msgType == "3"){
            //3-开门超时自动上锁
            $type = "over_time_close_door";
        }
        $msg_log_id = $this->log_msg($msg_id,$type,$this->raw_data(),$deviceId);

        $this->load->library("Device_lib");
        $device = new Device_lib();
        if($msgType == "2"){
            $device->close_door($deviceId);//设置为盘点中

        }else if($msgType == "1"){
            $device->open_door($deviceId,$msg_id);
        }else if($msgType == "3"){
            $device->over_time_close_door($deviceId);
        }


        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );
        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);
    }
    /**
     * 接收设备盘点信息
     * 先返回成功再去更改盘点的状态 以及支付
     * scene : boot 开机启动 /closeDoor 关门触发 /fetchInventory手动请求盘点/recovery异常恢复
     */
    public function receive_stock_new_msg_post()
    {
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $labels = $this->raw_data("labels");
        $scene = $this->raw_data("scene");//发送盘点消息场景


        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
//        $this->check_null_and_send_error($labels,"labels参数不能为空");
        $msg_log_id = $this->log_msg($msg_id,"stock",$this->raw_data(),$deviceId);



        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );


        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret,NULL,1);

        $new_labels = array();
        if($labels){
            foreach ($labels as $v){
                if(strlen($v) != 16){
                    //RFID标签id固定是16位的标签
                    continue;
                }
                $str = $v;
                $n_1 = '';
                for($i=strlen($str)-1;$i>=0;){
                    $k = $i-1;
                    $n_1 .=  $str[$k];
                    $n_1 .=  $str[$i];
                    $i = $i-2;
                }
                if($n_1){
                    $new_labels[] = $n_1;
                }
            }
        }

        $this->load->library("Device_lib");
        $device = new Device_lib();
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($deviceId);
        if(substr($equipment_info['type'],0,4) == 'rfid'){
            //rfid 设备
            $new_labels = array_unique($new_labels);//标签去重
            if(in_array($scene,array('closeDoor','recovery','boot'))){
                $device->stock($deviceId,$new_labels);
            }else if(in_array($scene,array('fetchInventory'))){
                $device->init_stock($deviceId,$new_labels,$scene);
            }
        }else{
            $device->scan_stock($deviceId,$labels,$equipment_info);
        }
        die;
    }

    /**
     * 接收设备异常消息
     *
     */
    public function receive_exception_new_msg_post(){
        $msg_id = $this->raw_data("msgId");
        $deviceId = $this->raw_data("deviceId");
        $msgCreateTime = $this->raw_data("msgCreateTime");
        $code = $this->raw_data("code");
        $msg = $this->raw_data("msg");//消息场景

        $this->check_null_and_send_error($deviceId,"deviceId参数不能为空");
        $this->check_null_and_send_error($msgCreateTime,"msgCreateTime参数不能为空");
        $this->check_null_and_send_error($msg_id,"msgId参数不能为空");
        $this->check_null_and_send_error($code,"code参数不能为空");
        $this->check_null_and_send_error($msg,"msg参数不能为空");

        $msg_log_id = $this->log_msg($msg_id,"exception",$this->raw_data(),$deviceId);
        $ret = array(
            "status"=>true,
            "message"=>"接收成功"
        );

        $this->load->model('log_abnormal_model');
        $ab_data = array(
            'box_id' => $deviceId,
            'content' => "设备异常消息:" . $msg.'[code='.$code.']',
            'uid' => 0,
            'log_type' => 8//设备推送异常告警
        );
        $this->log_abnormal_model->insert_log($ab_data);

        $this->update_msg($msg_log_id,$ret,"close");
        $this->send_ok_response($ret);
    }
    
    
    private function rand_card_number($p_card_number = '') {
        $a = "0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9,0,1,2,3,4,5,6,7,8,9";
        $a_array = explode(",", $a);
        $tname = '';
        for ($i = 1; $i <= 10; $i++) {
            $tname.=$a_array[rand(0, 31)];
        }
        if ($this->checkCardNum($p_card_number . $tname)) {
            $tname = $this->rand_card_number($p_card_number);
        }
        return $p_card_number.$tname;
    }
    
    private function checkCardNum($card_number) {
        $this->db->from('card');
        $this->db->where('card_number', $card_number);
        $query = $this->db->get();
        $num = $query->num_rows();
        if ($num > 0) {
            return true;
        } else {
            return false;
        }
    }
    
    private function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }
    
    
}

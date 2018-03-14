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
        $this->load->helper("device");
        $this->load->model('equipment_model');

    }



    private function log_msg($msg_id,$msg_type,$param,$device_id){

        active_device_helper($device_id);//激活设备

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
     * 请求开门
     */
    public function open_door_post(){

        $device_id = $this->post('device_id');
        $type = $this->post('type');
        $agreement_no = $this->post('agreement_no');
        $alipay_user_id = $this->post('alipay_user_id');
        $scene = $this->post('scene');
        $sign_time = $this->post('sign_time');

        $this->check_null_and_send_error($device_id,"缺少参数，请重新扫码");
//        $this->check_null_and_send_error($type,"缺少参数，请重新扫码");
        $user = $this->get_curr_user();

        if ($agreement_no && $alipay_user_id) {
            $this->load->model('user_agreement_model');
            $partner_id = get_3rd_partner_id_by_device_id($device_id, $user['source']);
            $data = array(
                'principal_id'=>$alipay_user_id,
                'thirdpart_id'=>$partner_id,
                'agreement_no'=>$agreement_no,
                'scene'=>urldecode($scene),
                'sign_time'=>urldecode($sign_time),
            );
            $ret = $this->user_agreement_model->update_agreement_sign($alipay_user_id,$data,'alipay',$partner_id);
            if($ret){
                update_user_cache($ret['user_id'],$ret);
            }
        }

        if($user['source'] != 'alipay' && $user['source'] != 'wechat'){
            write_log('用户信息异常:'.var_export($user,1),'info');
            $this->response([
                'status' => FALSE,
                'message' => "用户信息异常"
            ], REST_Controller::HTTP_UNAUTHORIZED);
        }

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
        $is_new = 0;
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
        $is_new = 0;
        $rs = device_info_request_execute($req_data,$is_new);
        $this->send_ok_response($rs);
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

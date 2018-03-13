<?php

/**
 * 定时脚本
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 4/19/17
 * Time: 14:03
 */
class Cron_event extends CI_Controller
{
    const EXCEPTION_MIN = 15;
    const EXCEPTION_SCAN_MIN = 3;
    const PAY_TIME_MIN = 30;
    const PAY_TIME_DAY = 15;//15天前的订单
    const CACHE_BOX_TIME_MIN = 10;
    const CHECK_BOX_TIME_MIN = 2;
    const PAY_TIME_MONTH = 60;

    function __construct()
    {
        parent::__construct();
        $this->load->helper("device_send");
        $this->load->library("Device_lib");
        $this->load->model("order_model");
        $this->load->model("order_pay_model");
        $this->load->helper("mapi_send");
        $this->load->helper("device");
        $this->load->model("user_model");
    }


    public function pay_order()
    {
        $hour = date("H");
        if($hour >=7 && $hour <= 23) {
            write_log('pay_order in ....');
            $this->load->model("order_model");
            $order = $this->order_model->list_not_pay_order_for_cron(self::PAY_TIME_MIN,self::PAY_TIME_DAY);
            write_log('未支付的订单 ' . var_export($order, 1));

            if ($order) {
                $device = new Device_lib();
                foreach ($order as $o1) {
                    $this->order_model->update_order_last_update($o1['order_name']);
                }
                foreach ($order as $o) {
                    if($o['refer'] == 'gat' || $o['refer'] == 'sodexo' || $o['refer'] == 'cmb' || $o['refer'] == 'sdy'){
                        continue;
                    }
                    if(strtotime($o['order_time']) > strtotime("-6min")){
                        //离现在6分钟前产生的订单，不发起支付，可能是异步没有返回的，可能是刚刚发起支付，避免2次支付
                        continue;
                    }
                    $data = array(
                        'money' => $o['money'],
                        'order_name' => $o['order_name'],
                        'detail' => $o['goods']
                    );
                    if($o['refer'] == 'alipay'){
                        $rs_pay_info = $this->last_pay_info($data);
                        if($rs_pay_info){
                            $data['pay_info'] = $rs_pay_info;
                            write_log('  last_pay_info '.var_export($rs_pay_info,1));
                        }
                    }
                    $device->pay($data, $o['uid'], $o['refer'], $o['box_no']);
                    $this->order_model->update_order_last_update($o['order_name']);
                }
            }
        }
    }


    public function last_pay_info($order){
        $order_name =  $order['order_name'];
        $rs_pay = $this->order_pay_model->get_pay_order_by_order_name($order_name);
        if($rs_pay && $rs_pay[0]['pay_no']){
            //排除关闭的支付单
            if($rs_pay[0]['pay_no'] && !strpos('xx'.$rs_pay[0]['pay_comment'],"交易已关闭")){
                $date = substr($rs_pay[0]['pay_no'],0,6);
                if(intval($date) >= intval(date('ymd',strtotime('-14day')))){
                    $result['pay_no'] = $rs_pay[0]['pay_no'];
                    $result['money']  = $order['money'];
                    $subject = '';
                    $goods = array();
                    foreach($order['detail'] as $k=>$v){
                        $subject .= ' '.$v['product_name'];
                        $goods[$k]['goodsId']   = $v['product_id'];
                        $goods[$k]['goodsName'] = $v['product_name'];
                        $goods[$k]['quantity']  = $v['qty'];
                        $goods[$k]['price']     = $v['price'];
                    }
                    $result['subject']  = $subject;
                    $result['goods']    = $goods;
                    $result['order_name']    = $order['order_name'];
                    return $result;
                }else{
                    return false;
                }
            }
        }else{
            return false;
        }

    }

    /**
     * 修正盒子的状态
     * 1.busy/scan/stock 状态下，15分钟未改变
     */
    public function fix_box_status()
    {
        write_log('fix_box_status in ....');
        $hour = date("H");
        if($hour >=7 && $hour <= 23) {
            $this->load->model('box_status_model');
            $this->load->model("request_msg_log_model");
            $this->load->model("log_abnormal_model");
            $rs = $this->box_status_model->get_exception_boxs(self::EXCEPTION_MIN);

            $this->load->model('equipment_model');
            $box_list = $this->equipment_model->list_active_box();
            $devices = array();
            foreach ($box_list as $b){
                $devices[] = $b['equipment_id'];
            }
            write_log('零售机状态异常： ' . var_export($rs, 1));
            if ($rs) {
                foreach ($rs as $v) {
                    if(in_array($v['box_id'],$devices)) { // 设备启用状态

                        if($v['status'] == 'scan'){
                            //直接更新为free
                            $data['box_id'] = $v['box_id'];
                            $data['use_scene'] = "custom";//更新盒子状态为 fix_status
                            $data['status'] = 'free';
                            $data['last_update'] = date("Y-m-d H:i:s");
                            $this->box_status_model->update_box_status($data);
                        } else{
                            $status_text = "异常";
                            switch ($v['status']){
                                case "stock":
                                    $status_text = "等待盘点状态";
                                    break;
                                case "busy":
                                    $status_text = "开门状态";
                                    break;
                            }
                            $ab_data = array(
                                'box_id' => $v['box_id'],
                                'content' => '零售机停留在:' . $status_text . "," . self::EXCEPTION_MIN . "分钟未更改，最后更新时间为" . $v['last_update'],
                                'uid' => $v['user_id'],
                                'log_type' => 2//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功
                            );
                            $this->log_abnormal_model->insert_log($ab_data);

                            $data['box_id'] = $v['box_id'];
                            $data['use_scene'] = "fix_status";//更新盒子状态为 fix_status
                            $data['status'] = $v['status'];
                            $data['last_update'] = date("Y-m-d H:i:s");
                            $this->box_status_model->update_box_status($data);

                            $this->get_box_info($v['box_id']);//获取盒子的信息
                        }

                    }
                }
            }
        }
    }


    public function fix_box_scan_status()
    {
        write_log('fix_box_scan_status in ....');
        $hour = date("H");
        if($hour >=7 && $hour <= 23) {
            $this->load->model('box_status_model');
            $this->load->model("request_msg_log_model");
            $this->load->model("log_abnormal_model");
            $rs = $this->box_status_model->get_exception_boxs(self::EXCEPTION_SCAN_MIN);

            $this->load->model('equipment_model');
            $box_list = $this->equipment_model->list_active_box();
            $devices = array();
            foreach ($box_list as $b){
                $devices[] = $b['equipment_id'];
            }
            if ($rs) {
                foreach ($rs as $v) {
                    if(in_array($v['box_id'],$devices) && $v['status'] == 'scan'){
                        //直接更新为free
                        $data['box_id'] = $v['box_id'];
                        $data['use_scene'] = "custom";//更新盒子状态为 fix_status
                        $data['status'] = 'free';
                        $data['last_update'] = date("Y-m-d H:i:s");
                        $this->box_status_model->update_box_status($data);
                    }
                }
            }
        }
    }




    public function box_heart_check()
    {
        write_log('box_heart_check in ....');
        $hour = date("H");
        if($hour >=7 && $hour <= 23) {
            $this->load->driver('cache');
            $key_eq = "equiment_list_for_cron";
            $box_list = $this->cache->redis->get($key_eq);
            if (!$box_list) {
                $this->load->model('equipment_model');
                $box_list = $this->equipment_model->list_active_box();
                $this->cache->redis->save($key_eq,$box_list,30*60);//30分钟
            }
            if ($box_list && count($box_list) > 0) {
                $error = "";
                $corn_heart_box_list_str = $this->cache->redis->get("corn_heart_box_list");//获取已提醒过的设备列表
                if($corn_heart_box_list_str){
                    $corn_heart_box_list = explode(",",$corn_heart_box_list_str);
                }
                foreach ($box_list as $v) {
                    if(count($corn_heart_box_list)> 0 && in_array($v['equipment_id'],$corn_heart_box_list)){
                        continue;//如果设备已经在提醒过，则不在检查
                    }
                    $e = device_last_status_helper($v['equipment_id']);
                    if ($e != 'online') {
                        $this->load->model("log_abnormal_model");
                        $ab_data = array(
                            'box_id' => $v['equipment_id'],
                            'content' => "零售机心跳监测异常",
                            'uid' => 0,
                            'log_type' => 5//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功 5 网络监测异常
                        );
                        if($e){
                            $ab_data['content'] .= ",最后一次消息发生在 ".$e;
                        }
                        $error .= '零售机心跳监测异常:设备号' . $v['equipment_id'] . '，名称：' . $v['name'] . '，地址：' . $v['address'] . "，" . self::CHECK_BOX_TIME_MIN . "分钟未接收到心跳消息";
                        $this->log_abnormal_model->insert_log($ab_data);
                        $corn_heart_box_list[] = $v['equipment_id'];
                    }
                }
                $corn_heart_box_list = array_unique($corn_heart_box_list);
                $corn_heart_box_list = array_filter($corn_heart_box_list);//去掉空值
                $corn_heart_box_list_str = join(',',$corn_heart_box_list);
                $this->cache->redis->save("corn_heart_box_list",$corn_heart_box_list_str,3600 * 24);
                if (strlen($error) > 1){
                    write_log('心跳异常零售机' . $error, 'crit');
                }
            }
        }
    }

    /**
     * 请求设备信息
     * @param $device_id
     * @return mixed
     */
    public function get_box_info($device_id)
    {
        $req_data = array(
            'deviceId' => $device_id,
        );
        $this->load->model('equipment_model');
        $is_new = 0;

        $rs = device_info_request_execute($req_data,$is_new);
        if ($rs) {
            $rs = json_decode($rs, TRUE);
            if ($rs['requestId'] && $rs['state']['code'] == 0) {
                return $rs;
            }
        }
        return FALSE;
    }
}

?>
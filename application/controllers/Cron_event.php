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
        $this->load->helper("message");
        $this->load->helper("device_send");
        $this->load->library("Device_lib");
        $this->load->model("order_model");
        $this->load->model("order_pay_model");
        $this->load->helper("mapi_send");
        $this->load->helper("device");
        $this->load->helper('guanaitong_send');
        $this->load->model("user_model");
    }
    
    
    /**
     * 拉取招行最新公钥
     */
    public function pull_cmb_key()
    {
        $this->load->library('CmbChina');
        $this->load->driver('cache');
        $cache_key = "cmb_public_key";
            
        $cmbchina = new CmbChina();
        $retval = $cmbchina->pullPublicKey();
        
        if($retval['code'] == 200){
            $cache_val = $this->cache->redis->save($cache_key, $retval['data']);
        }
        
        return true;
    }

    public function pay_order()
    {
        $hour = date("H");
        if($hour >=7 && $hour <= 23) {
            write_log('pay_order in ....');
            $this->load->model("order_model");
            $this->load->helper("message");
            $order = $this->order_model->list_not_pay_order_for_cron(self::PAY_TIME_MIN,self::PAY_TIME_DAY);
            write_log('未支付的订单 ' . var_export($order, 1));

            if ($order) {
                $device = new Device_lib();
                foreach ($order as $o1) {
                    $this->order_model->update_order_last_update($o1['order_name']);
                }
                foreach ($order as $o) {
                    if($o['refer'] == 'gat' || $o['refer'] == 'sodexo' || $o['refer'] == 'cmb'){
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

    //索迪斯 未支付订单 一天发送一次支付请求
    public function sodexo_pay_order()
    {
        $this->load->model("order_model");
        $this->load->model('user_acount_model');
        $this->load->library("open/SodexoLib");
        $SodexoLib = new SodexoLib();
        $order = $this->order_model->list_not_pay_order_for_cron(5, self::PAY_TIME_MONTH, 'sodexo');
        write_log('未支付的订单 ' . var_export($order, 1));
        if ($order) {
            $device = new Device_lib();
            foreach ($order as $o) {
                if (strtotime($o['order_time']) > strtotime("-6min")) {
                    //离现在6分钟前产生的订单，不发起支付，可能是异步没有返回的，可能是刚刚发起支付，避免2次支付
                    continue;
                }
                //查询订单状态
                $pay_info = $this->order_pay_model->get_order_one_pay($o['order_name']);
                $user_info = $this->user_model->get_user_info(array('id'=>$o['uid']));
                $check_data = array('box_no'=>$o['box_no'], 'mobile' => $user_info['mobile'], 'amount'=>$pay_info['money'], 'transactionId'=>$pay_info['trade_no']);
                $rs = $SodexoLib->checkPay($check_data);//检查支付状态

                if($rs['code'] == 200 && $rs['status'] == 'success'){
                    $this->order_pay_model->update_pay_status($pay_info['pay_no'], 1, '支付成功');
                    $this->order_model->update_order($o['order_name'], 1);
                    //支付成功, 赠送魔力值， 魔豆
                    $this->load->model('user_acount_model');
                    $this->user_acount_model->update_user_acount($o['uid'], $pay_info['money'], $o['order_name']);
                    echo $o['order_name'].'-again|';
                }else{
                    $data = array(
                        'money' => $o['money'],
                        'order_name' => $o['order_name'],
                        'detail' => $o['goods']
                    );
                    $rs = $device->pay($data, $o['uid'], $o['refer'], $o['box_no']);
                    $this->order_model->update_order_last_update($o['order_name']);
                    if($rs['code'] == 200){
                        echo $o['order_name'].'-ok|';
                    }else{
                        echo $o['order_name'].'-error|';
                    }
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

    public function not_bind_label(){
        $sql = "select label,equipment_id from cb_equipment_label where `status`='active' and label not in(SELECT label from cb_label_product)";
        $labels = $this->db->query($sql)->result_array();
        $boxs = array();
        foreach ($labels as $v){
            $boxs[$v['equipment_id']][] = $v['label'];
        }
        $this->load->model('equipment_model');
        $error = "";
        foreach ($boxs as $k=>$box){
            $box_info = $this->equipment_model->get_info_by_equipment_id($k);
            $error .="设备：".$box_info['equipment_id'].",名称:".$box_info['name'].",地址:".$box_info['address'].",存在未绑定标签：".join(",",$box)."\n";
        }
        if (strlen($error) > 1) {
            write_log($error, 'crit');
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




    /**
     * 心跳监测
     */
//    public function box_heart_check()
//    {
//        write_log('box_heart_check in ....');
//        $hour = date("H");
//        if($hour >=7 && $hour <= 23) {
//            $this->load->model('equipment_model');
//            $box_list = $this->equipment_model->list_active_box();
//            if (count($box_list) > 0) {
//                $this->load->model("receive_box_log_model");
//                $this->load->model("log_abnormal_model");
//                $error = "";
//                $this->load->driver('cache');
//                $corn_heart_box_list_str = $this->cache->redis->get("corn_heart_box_list");
//                if($corn_heart_box_list_str){
//                    $corn_heart_box_list = explode(",",$corn_heart_box_list_str);
//                }
//                foreach ($box_list as $v) {
//                    if(count($corn_heart_box_list)> 0 && in_array($v['equipment_id'],$corn_heart_box_list)){
//                        continue;//如果设备已经在提醒过，则不在检查
//                    }
//                    $log = $this->receive_box_log_model->check_heart_in_min(self::CHECK_BOX_TIME_MIN, $v['equipment_id']);
//                    if (!$log) {
//                        $ab_data = array(
//                            'box_id' => $v['equipment_id'],
//                            'content' => "零售机心跳监测异常，" . self::CHECK_BOX_TIME_MIN . "分钟未接收到心跳消息",
//                            'uid' => 0,
//                            'log_type' => 5//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功 5 网络监测异常
//                        );
//                        $error .= '零售机心跳监测异常:设备号' . $v['equipment_id'] . '，名称：' . $v['name'] . '，地址：' . $v['address'] . "，" . self::CHECK_BOX_TIME_MIN . "分钟未接收到心跳消息";
//                        $this->log_abnormal_model->insert_log($ab_data);
//                    }
//                }
//                if (strlen($error) > 1){
//                    write_log('心跳异常零售机' . $error, 'crit');
//                }
//            }
//        }
//    }


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
        if ($rs) {
            $rs = json_decode($rs, TRUE);
            if ($rs['requestId'] && $rs['state']['code'] == 0) {
                return $rs;
            }
        }
        return FALSE;
    }

    public function query_unpay_order_init(){
        $orders = $this->order_model->list_not_pay_order_for_cron(2,365);
        write_log('query_unpay_order ... ' . var_export($orders, 1));
        foreach ($orders as $order){
            $pay_orders = $this->order_pay_model->get_pay_order_by_order_name($order['order_name']);
            if($pay_orders){
                foreach($pay_orders as $v){
                    if($v['pay_type'] == 1 && $v['pay_status'] == 0){
                        //支付宝
                        $config = get_platform_config_by_device_id($order['box_no']);
                        $rs = $this->alipay_query_order($v['pay_no'],$config);
                        $type = 'alipay';
                    }else if($v['pay_type'] == 2 && $v['pay_status'] == 0){
                        //微信支付
                        $config = get_platform_config_by_device_id($order['box_no']);
                        $rs = $this->wechat_query_order($v['pay_no'],$config);
                    }

                    if($rs){
                        $pay_info = array('subject'=>'魔盒CITYBOX购买商品','pay_no'=>$rs['out_trade_no']);
                        $comment = '支付成功';
                        $pay_status = 1;
                        $open_id = $rs['buyer_id'];
                        $pay_money = $rs['total_fee'];
                        $uid = $v['uid'];
                        $pay_user = $rs['buyer_email'];
                        $trade_number = $rs['trade_no'];
                        $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);

                        $lib = new Device_lib();
                        $lib->update_order_and_pay($pay_info,$notify_data,$type);
                    }
                }
            }
        }
    }
    /**
     * 主动查询未支付的订单
     */
    public function query_unpay_order(){
        $orders = $this->order_model->list_not_pay_order_for_cron(2,0.1);//查询0.1天 = 2.4小时 内的未支付订单
        var_dump($orders);
        write_log('query_unpay_order ... ' . var_export($orders, 1));

        foreach ($orders as $order){
            $pay_orders = $this->order_pay_model->get_pay_order_by_order_name($order['order_name']);
            $config = get_platform_config_by_device_id($order['box_no']);
            if($pay_orders){
                foreach($pay_orders as $v){
                    if($v['pay_type'] == 1 && $v['pay_status'] == 0){
                        //支付宝
                        $rs = $this->alipay_query_order($v['pay_no'],$config);
                        $type = 'alipay';
                    }else if($v['pay_type'] == 2 && $v['pay_status'] == 0){
                        //微信支付
                        $rs = $this->wechat_query_order($v['pay_no'],$config);
                        $type = 'wechat';
                    }else if($v['pay_type'] == 6 && in_array($v['pay_status'], array(0, 2)) ){
                        //关爱通未支付 查询
                        $rs = check_order_pay($v['pay_no']);
                        if($rs['code'] === 0 && $rs['msg'] === 'OK' && $rs['data']['outer_trade_no'] == $v['pay_no']){//订单支付成功
                            $rs['total_fee'] = $rs['data']['pay_amount'];
                            $rs['trade_no']  = $rs['data']['outer_trade_no'];
                            $rs['out_trade_no'] = $rs['data']['outer_trade_no'];
                        }
                    }else if($v['pay_type'] == 9 && in_array($v['pay_status'], array(0, 2)) ){
                        //todo  索迪斯未支付订单查询
                        $rs=false;

                    }else if($v['pay_type'] == 5 && $v['pay_status'] == 0){
                        //微信手动支付
                        $rs = $this->wechat_query_order($v['pay_no'],$config);
                        $type = 'wechat';
                    }else if($v['pay_type'] == 4 && $v['pay_status'] == 0){
                        //支付宝手动支付
                        $rs = $this->alipay_query_wap_order($v['pay_no'],$config);
                        $type = 'alipay';
                    }
                    if($rs){
                        $pay_info = array('subject'=>'魔盒CITYBOX购买商品','pay_no'=>$rs['out_trade_no']);
                        $comment = '支付成功';
                        $pay_status = 1;
                        $open_id = $rs['buyer_id'];
                        $pay_money = $rs['total_fee'];
                        $uid = $v['uid'];
                        $pay_user = $rs['buyer_email'];
                        $trade_number = $rs['trade_no'];
                        $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);


                        $lib = new Device_lib();
                        $lib->update_order_and_pay($pay_info,$notify_data,$type);
                    }
                }
            }
        }
    }

    private function alipay_query_order($out_trade_no,$config){
        $this->load->library("aop/request/AlipayAcquireQueryRequest");
        $alipay = new AlipayAcquireQueryRequest();
        $alipay->setOutTradeNo($out_trade_no);
        $rs = mapiClient_request_execute($alipay,null,$config);
        write_log('支付宝查询结果 out_trade_no =>' .$out_trade_no.':'.var_export($rs, 1));
        if($rs && $rs['is_success'] === "T" && $rs['response']['alipay']['result_code'] === 'SUCCESS' &&  $rs['response']['alipay']['trade_status'] === 'TRADE_SUCCESS' ){
            $data = array(
                'out_trade_no'=>$rs['response']['alipay']['out_trade_no'],
                'trade_no'=>$rs['response']['alipay']['trade_no'],
                'buyer_email'=>$rs['response']['alipay']['buyer_logon_id'],
                'total_fee'=>$rs['response']['alipay']['total_fee'],
                'buyer_id'=>$rs['response']['alipay']['buyer_user_id'],
                'pay_status'=>$rs['response']['alipay']['trade_status']
            );
            return $data;
        }
        return FALSE;
    }
    private function alipay_query_wap_order($out_trade_no,$config){

        $this->load->helper('aop_send');
        $rs = alipay_query_wap_order($out_trade_no,$config);
        write_log('网页支付支付宝查询结果 out_trade_no =>' .$out_trade_no.':'.var_export($rs, 1));
        $rs = $rs['alipay_trade_query_response'];
        if($rs && $rs['code'] == "10000" &&  $rs['trade_status'] === 'TRADE_SUCCESS' ){
            $data = array(
                'out_trade_no'=>$rs['out_trade_no'],
                'trade_no'=>$rs['trade_no'],
                'buyer_email'=>$rs['buyer_logon_id'],
                'total_fee'=>$rs['total_amount'],
                'buyer_id'=>$rs['buyer_user_id'],
                'pay_status'=>$rs['trade_status']
            );
            return $data;
        }
        return FALSE;
    }
    private function wechat_query_order($out_trade_no,$config){
        $this->load->helper('wechat_send');
        $rs = query_wx_order($out_trade_no,$config);
        if($rs && $rs['return_code'] === 'SUCCESS' && $rs['result_code'] === 'SUCCESS' &&  $rs['trade_state'] === 'SUCCESS'){
            $data = array(
                'out_trade_no'=>$rs['out_trade_no'],
                'trade_no'=>$rs['transaction_id'],
                'buyer_email'=>'',
                'total_fee'=>$rs['total_fee'],
                'buyer_id'=>$rs['openid'],
                'pay_status'=>$rs['trade_state']
            );
            return $data;
        }
        return FALSE;
    }

    /**
     * 未支付订单队列处理
     */
    public function mq_pay(){
        //支付单队列
        $this->config->load("platform_config", TRUE);
        $key = $this->config->item("ajax_pay_key", "platform_config");
        $this->load->helper('mq');
        $count = 0;
        $max = length_mq_for_redis($key);
        write_log('mq length ----'.$max);
        $device = new Device_lib();
        while ($max > $count) {
            $data = pop_mq_for_redis($key);
            write_log('mq:'.var_export($data,1));
            if (is_array($data)){
                $rs = $device->pay($data['order'],$data['user_id'],$data['refer'],$data['box_id']);
                write_log('mq pay :'.var_export($rs,1));
            }
            $count++;
        }
    }



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


    public function program_update_agreement(){


        $this->load->model("user_model");
        $num = 500000;
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
            sleep(2);
        }
    }

    /**
     * 修正微信支付订单
     */
    public function fix_order_unpay()
    {

        $where = " WHERE  `order_name`  in (17122803642695,
                    17122810011496,
                    17122838648115,
                    17122842608388,
                    17122847401718,
                    17122856371327,
                    17122857234740,
                    17122906905069,
                    17122911147709,
                    17122922216629,
                    17122933605328,
                    17122935438689,
                    17122938071235,
                    17123004906096,
                    17123038985228,
                    17123041861249,
                    17123133670660,
                    18010142149734,
                    18010200750054,
                    18010205852494,
                    18010222777752,
                    18010227744828,
                    18010229950069,
                    18010233303951,
                    18010244565927,
                    18010247518470,
                    18010249782445,
                    18010249989097,
                    18010255118029,
                    18010331224742,
                    18010332782685)";

        $sql = "select * from cb_order $where ";
        $orders = $this->db->query($sql)->result_array();
        var_dump($orders);die;


        foreach ($orders as $order){

            $sql_uo = "update cb_order set order_status=0 WHERE order_name = '{$order['order_name']}' ";
            $this->db->query($sql_uo);

            $pay_orders = $this->order_pay_model->get_pay_order_by_order_name($order['order_name']);
            $config = get_platform_config_by_device_id($order['box_no']);
            if($pay_orders){
                foreach($pay_orders as $v){
                    $sql_up = " update cb_order_pay set pay_status=0,pay_comment='' where pay_no = '{$v['pay_no']}' ";
                    $this->db->query($sql_up);
                   if($v['pay_type'] == 2){
                        //微信支付
                        $rs = $this->wechat_query_order($v['pay_no'],$config);
                        $type = 'wechat';
                        $pay_status_old = 2;
                    }else if($v['pay_type'] == 5){
                        //微信手动支付
                        $rs = $this->wechat_query_order($v['pay_no'],$config);
                        $type = 'wechat';

                   }
                   if($rs){
                        $pay_info = array('subject'=>'魔盒CITYBOX购买商品','pay_no'=>$rs['out_trade_no']);
                        $comment = '支付成功';
                        $pay_status = 1;
                        $open_id = $rs['buyer_id'];
                        $pay_money = $rs['total_fee'];
                        $uid = $v['uid'];
                        $pay_user = $rs['buyer_email'];
                        $trade_number = $rs['trade_no'];
                        $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);

                        var_dump($pay_info);
                        var_dump($notify_data);
                        $lib = new Device_lib();
                        $lib->update_order_and_pay($pay_info,$notify_data,$type);
                    }
                }
            }
        }

    }

    /**
     * 	自助售货机入驻申请接口
     */
    function automat_upload(){
        $this->load->helper('koubei_send');

    }

    /**
     * 五芳斋推单
     */
    function wfz_pos_order(){
        $this->load->driver('cache');
        $wfz_pos_order_complete_key = "wfz_pos_order_complete";
        $complete = $this->cache->redis->get($wfz_pos_order_complete_key);
        if($complete){
            $this->cache->redis->save($wfz_pos_order_complete_key,1);
            return;
        }
        $this->load->helper('wfz');
        $this->load->model("order_model");
        $this->load->model("wfz_pos_model");

        $run_time = $this->wfz_pos_model->get_run_time();
        if(!$run_time){
            $run_time = '2018-01-28';//五芳斋上线的时间
        }
        $last_update_time = date("Y-m-d H:i:s");

        //订单创建
        $orders_create = $this->order_model->wfz_pos_order($run_time,1);
        $this->wfz_process($last_update_time,$orders_create,0,1);
        $this->wfz_process($last_update_time,$orders_create,1,2);

        //todo 订单同意退款
        $orders_agree = $this->order_model->wfz_pos_order($run_time,4);
        $this->wfz_process($last_update_time,$orders_agree,0,1);
        $this->wfz_process($last_update_time,$orders_agree,1,2);
        $this->wfz_process($last_update_time,$orders_agree,2,3);
        $this->wfz_process($last_update_time,$orders_agree,3,4);

        //todo 订单拒绝退款
        $orders_against = $this->order_model->wfz_pos_order($run_time,5);
        $this->wfz_process($last_update_time,$orders_against,0,1);
        $this->wfz_process($last_update_time,$orders_against,1,2);
        $this->wfz_process($last_update_time,$orders_against,2,3);
        $this->wfz_process($last_update_time,$orders_against,3,5);

        $this->cache->redis->save($wfz_pos_order_complete_key,0);
    }

    private function wfz_process($run_time,$order,$front_status,$update_status){
        $this->load->helper('wfz');
        $this->load->model("order_model");
        $this->load->model("wfz_pos_model");
        $this->load->driver('cache');
        $today = date("Y-m-d");
        foreach ($order as $item) {
            $state = $this->wfz_pos_model->get_status($item['order_name']);
            if (($front_status == 0 && !$state) || ($state && $state['status'] == $front_status)) {
                switch ($update_status){
                    case 1:
                        //订单创建
                        $day_sn = 1 + $this->wfz_pos_model->get_no($today) ;
                        $body = $this->general_wfz_create_msg($item, $day_sn);
                        if(!$body){
                            $this->order_model->update_order_last_update($item['order_name']);
                            continue;
                        }
                        $rs = wfz_new_order_execute($body, $item['order_name']);
                        break;
                    case 2:
                        $rs = wfz_order_pay_execute("", $item['order_name']);
                        break;
                    case 3:
                        $body = $this->general_wfz_refund_message($item);
                        if(!$body){
                            $this->order_model->update_order_last_update($item['order_name']);
                            continue;
                        }
                        $rs = wfz_order_refund_execute($body, $item['order_name']);
                        break;
                    case 4:
                        $rs = wfz_order_refund_pass_execute("", $item['order_name']);
                        break;
                    case 5:
                        $rs = wfz_order_refund_against_execute("", $item['order_name']);
                        break;
                }
                if ($rs) {
                    $rs = json_decode($rs, 1);
                    if ($rs['code'] == 0) {
                        if($update_status == 1){
                            $this->wfz_pos_model->insert_status($item['order_name'], $update_status,$run_time);
                        }else{
                            $this->wfz_pos_model->update_status($item['order_name'], $update_status);
                        }
                    } else {
                        $this->order_model->update_order_last_update($item['order_name']);//不成功，则修改最后修改时间，下次再同步
                    }
                }else{
                    $this->order_model->update_order_last_update($item['order_name']);//不成功，则修改最后修改时间，下次再同步
                }
                sleep(1);
            }
        }
    }

    function general_wfz_create_msg($order,$day_sn){

        $this->load->model("order_pay_model");
        $this->load->model("order_product_model");
        $this->load->model("product_model");
        $pay = $this->order_pay_model->get_pay_succ_by_order_name($order['order_name']);

        $this->load->model("order_discount_log_model");
        $order["discount"] = $this->order_discount_log_model->list_order_discount($order['order_name']);//优惠信息
        if(!$order["discount"]){
            $order["discount"] = array();
        }else{
            foreach ($order["discount"] as &$v){
                $v['nm'] = $v['text'];
                $v['money'] = $v['discount_money'];
                $v['shcd'] = $v['discount_money'];
                $v['ptcd'] = 0;
                $v['other'] = "";
                unset($v['text']);
                unset($v['discount_money']);
            }
        }
        if(!empty($order['use_card']) && $order['card_money'] >0){
            $this->load->model('card_model');
            $card_info = $this->card_model->get_card_by_number($order['use_card']);
            if($card_info){
                $card = array(
                    'nm'=>$card_info['card_name'],
                    'money'=>floatval($order['card_money']),
                    "shcd"=> floatval($order['card_money']),//商户承担
                    "ptcd"=>0,//平台承担
                    "other"=> ""
                );
                $order["discount"][] = $card;
            }
        }
        $this->load->helper('koubei_send');
        $config = get_platform_config($order['platform_id']);
        $query_pay = koubei_query_order($pay['pay_no'],$config);

        $goods = $this->order_product_model->list_order_products($order['order_name']);
        $foods = array();
        foreach ($goods as $good){
            $code = $this->product_model->getSerialCodeByid($good['product_id']);
            $foods[] = array(
                "nm"=> $good['product_name'],
                "qty"=> $good['qty'],
                "price"=> floatval($good['price']),
                "money"=> floatval($good['total_money']),
                "sku"=> $code['serial_number'],
                "items"=> array()
            );
        }
        $data = array(
            "oid"=>$order['order_name'],
            "sid"=>WFZ_SHOP_ID,
            "day_sn"=>$day_sn,
            "xdfs"=>"",
            "yj"=>floatval($order['good_money']),//订单原价
            "sf"=>floatval($order['money']),//用户实付
            "sr"=>floatval($order['money']),//商户实收
            "number"=>1,
            "bz"=>"",
            "yhzh"=>-floatval($order['good_money'] - $order['money']), //优惠总和
            "shcd"=>-floatval($order['good_money'] - $order['money']),//商户承担优惠金额
            "ptcd"=>0,//平台承担优惠金额
            "xdsj"=>strtotime($pay['pay_time']),//下单时间 unix时间戳
            "foods"=>$foods,
            "pays"=>array(
                array(
                    "tp"=> "alipay",
                    "meta"=>json_encode($query_pay)
                )
            ),
            "activitys"=>$order["discount"],
            "cus"=>array(
                "nm"=> "",
                "phone"=> "",
                "gps"=> "",
                "address"=> ""
            ),
            "other"=>""
        );
        return json_encode($data);
    }

    private function general_wfz_refund_message($order){
        $this->load->model('order_refund_model');
        $refund = $this->order_refund_model->get_refund($order['order_name']);
        if($refund){
            $data = array(
                'tp'=>'all',
                'money'=>floatval($refund['really_money']),
                'reason'=>$refund['reason'] == 1 ? "订单结算错误" :"商品质量问题",
                'foods'=>array()
            );
            return json_encode($data);
        }
        return false;
    }

    public function upload_device_data(){
        $this->load->helper('koubei_send');
        $sql="select * from cb_equipment WHERE  `status` = 1 and sync_alipay=1 AND alipay_terminal_id =''";
        $eqs = $this->db->query($sql)->result_array();
        foreach ($eqs as $eq){

            if($eq['province'] && $eq['city'] && $eq['area'] ){
                $config = get_platform_config(KOUBEI_PLATFORM_ID);
                echo $eq['equipment_id'];
                $region_name = $this->get_region_name($eq['province'] , $eq['city'] , $eq['area']);
                $point_position = $this->clear_region_data($region_name,$eq['address']);
                if($point_position){
                    $biz = array(
                        "terminal_id" => $eq['equipment_id'],
                        "product_user_id" => $config['product_user_id'],
                        "merchant_user_id" => $config['merchant_user_id'],
                        "machine_type" => "AUTOMAT",
                        "machine_cooperation_type" => "COOPERATION_CONTRACT",
                        "machine_delivery_date" => date("Y-m-d H:i:s",$eq['created_time']),
                        "machine_name" => "鲜动科技",
                        "delivery_address" => array
                        (
                            "area_code" => 310115,
                            "machine_address" => "周浦康杉路488号",
                            "province_code" => 310000,
                            "city_code" => 310100,
                        ),
                        "point_position" => $point_position,
                        "merchant_user_type" => "ALIPAY_MERCHANT"
                    );
                    print_r($biz);
                    $send_rs = ant_merchant_upload($biz,$config);
                    print_r($send_rs);
                    if($send_rs && $send_rs['code'] == 10000 && $send_rs['alipay_terminal_id']){
                        $up_sql = "update cb_equipment set alipay_terminal_id='{$send_rs['alipay_terminal_id']}' where equipment_id='{$eq['equipment_id']}'";
                        $this->db->query($up_sql);
                    }
                }else{
                    continue;
                }
            }

        }
    }

    private function get_region_name($province_id,$city_id,$area_id){
        $sql="select * from cb_sys_regional WHERE AREAIDS = '$province_id' or AREAIDS = '$city_id' or AREAIDS = '$area_id'";
        $rs = $this->db->query($sql)->result_array();
        foreach ($rs as $v){
            if($v['AREAIDS'] == $province_id){
                $ret['province'] = $v['AREANAME'];
            }else if($v['AREAIDS'] == $city_id){
                $ret['city'] = $v['AREANAME'];
            }else if($v['AREAIDS'] == $area_id){
                $ret['area'] = $v['AREANAME'];
            }
        }
        return $ret;
    }
    private function clear_region_data($data,$address){
        $province = $data['province'];
        $city = $data['city'];
        $area = $data['area'];
        if(in_array($province,['上海','北京','天津','重庆'])){
            $area = $city;
            $city = $province."市";
        }else{
            $province = $province."省";
        }
        echo $sql="select * from cb_alipay_region WHERE  areaName = '$province' or  areaName = '$city' or  areaName = '$area'";
        $rs = $this->db->query($sql)->result_array();
        $ret = array('machine_address'=>$address);
        foreach ($rs as $v){
            if($v['areaName'] == $province){
                $ret['province_code'] = $v['areaCode'];
            }else if($v['areaName'] == $city){
                $ret['city_code'] = $v['areaCode'];
            }else if($v['areaName'] == $area){
                $ret['area_code'] = $v['areaCode'];
            }
        }
//        print_r($ret);die;
        if(count($ret) == 4)
            return $ret;
        else
            return false;
    }
}

?>
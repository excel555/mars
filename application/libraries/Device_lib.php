<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 4/19/17
 * Time: 10:04
 */

class Device_lib
{
    protected $CI;

    public function __construct()
    {
        // Assign the CodeIgniter super-object
        $this->CI =& get_instance();
        $this->CI->load->model("box_status_model");
        $this->CI->load->model("equipment_label_model");
        $this->CI->load->model("label_product_model");
        $this->CI->load->model("product_model");
        $this->CI->load->model("log_open_model");
        $this->CI->load->model("log_abnormal_model");
        $this->CI->load->model("stock_log_model");
        $this->CI->load->helper("message");
        $this->CI->load->model('equipment_model');
        $this->CI->config->load("tips", TRUE);
        $this->CI->load->driver('cache',
            array('adapter' => 'redis', 'key_prefix' => 'citybox_')
        );
        $this->CI->load->model("user_model");
        $this->CI->load->helper("mapi_send");

    }

    /**
     * 请求开门操作
     */
    public function request_open_door($sceneId, $user, $type)
    {
        $open_door_msg = $this->CI->config->item("open_door_tip_msg", "tips");
        if (!$user['source']) {
            return $open_door_msg['defalut'];
        }

        $type = $user['source'];
        $equipment_info = $this->CI->equipment_model->get_info_by_equipment_id($sceneId);

        if ($equipment_info && isset($equipment_info['platform_id'])) {
            $this->CI->load->model('user_platform_relations_model');
            $this->CI->user_platform_relations_model->add_platform_relation($user['id'], $equipment_info['platform_id']);
        }
        if (!$equipment_info || (isset($equipment_info['status']) && $equipment_info['status'] != 1)) {
            //设备不是可用状态
            $msg_data = $this->CI->config->item("box_trouble_msg", "tips");
            $msg_data['buyer_id'] = $user['open_id'];
            return $open_door_msg['device_dead'];
        }
        $operation_id = 1;//普通用户开门

        if ($user['is_black'] == 1) {
            $msg_data = $this->CI->config->item("user_black_msg", "tips");
            $msg_data['buyer_id'] = $user['open_id'];
            return $open_door_msg['black'];
        }

        //检查用户是否存在未支付的订单
        $this->CI->load->model("order_model");
        $order = $this->CI->order_model->list_orders($user['id'], 0, 1, 1000000);
        if ($order) {
            write_log("未支付订单" . $user['id'] . var_export($order, 1));
            return $open_door_msg['no_pay'];
        }

        //支付宝开门再次验证是否签约
        $partner_id = get_3rd_partner_id_by_device_id($sceneId, $user['source']);
        if (($user['source'] === "alipay" || $user['source'] === "wechat") && empty($user[$partner_id . '_agreement_no'])) {
            $rs_url = $this->get_agreement_sign_url($user['source'], $sceneId);
            return array('status' => 'qianyue', 'url' => $rs_url);
        }

        $box_status = $this->CI->box_status_model->get_info_by_box_id($sceneId);
        if ($box_status && ($box_status['status'] == "trouble" || $box_status['use_scene'] == "fix_status")) {
            return $open_door_msg['device_init'];
        } else if ($box_status && ($box_status['status'] == "busy" || $box_status['status'] == "scan" || $box_status['status'] == "stock")) {
            return $open_door_msg['device_busy'];
        } else if (!$box_status || ($box_status && $box_status['status'] == 'free')) {
            $role = "user";

            $this->CI->load->helper("device_send");
            $req_data = array(
                'deviceId' => trim($sceneId),
                'userId' => rand(1, 999999)
            );
            $is_new = 0;

            //只允许做一个人请求开门
            $open_key = 'openning_' . $sceneId;
            $openning = $this->CI->cache->get($open_key);
            if ($openning) {
                return $open_door_msg['device_busy'];
            }
            $this->CI->cache->save($open_key, $user['id']);
            $rs = open_door_request_execute($req_data, $is_new);//请求开门
            if (strpos($rs, 'timed out')) {
                //如果请求返回超时，则再请求一次 Connection timed out after 10000 milliseconds
                $rs = open_door_request_execute($req_data, $is_new);//请求开门
            }

            $msg_id = null;
            if ($rs) {
                $rs = json_decode($rs, TRUE);
                if ($rs['requestId'] && $rs['state']['code'] == 0) {
                    $msg_id = $rs['requestId'];
                }
            }
            if (!$msg_id) {
                write_log("请求开门接口返回失败" . var_export($rs, 1) . ",请求体" . var_export($req_data, 1), 'crit');
                $ab_data = array(
                    'box_id' => $sceneId,
                    'content' => "请求开门接口返回失败:" . $rs['state']['tips'],
                    'uid' => $user['id'],
                    'log_type' => 2//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功
                );
                $this->CI->log_abnormal_model->insert_log($ab_data);

                //友好的提示信息
                if (strpos($rs, 'timed out')) {
                    $rs['state']['tips'] = '请求开门超时';
                } else if (isset($rs['state']['code'])) {
                    switch ($rs['state']['code']) {
                        case 10001://系统错误
                        case 10002://IP限制
                        case 10003://非法请求
                        case 10004://缺少appkey
                        case 10005://缺少必填参数
                        case 10006://参数值非法
                        case 20101://指令发送失败
                        case 20102://指令ACK失败
                            $rs['state']['tips'] = '系统异常';
                            break;
                        case 20001://售货机不存在
                        case 20002://售货机失联
                        case 20003://售货机正与其他用户交易中
                        case 20201://售货机正与当前用户交易中
                        case 20202:
//                            $rs['state']['tips'] = $rs['state']['tips'] ;
                            break;
                        default:
                            $rs['state']['tips'] = '未知错误';
                            break;
                    }
                }
                $this->CI->cache->delete($open_key);
                return isset($rs['state']['tips']) ? $rs['state']['tips'] : $open_door_msg['open_fail'];
            }

            $data = array('msg_id' => $msg_id, 'refer' => $type, 'user_id' => $user['id'], 'open_id' => $user['open_id'], 'box_id' => $sceneId, 'last_update' => date("Y-m-d H:i:s"), 'notify_msg' => '', 'role' => $role);
            $data['status'] = 'scan';


            //记录开门消息
            $log_id = $this->CI->log_open_model->open_log($user['id'], $sceneId, $type, $operation_id);
            $data['open_log_id'] = $log_id;

            if (!$box_status) {
                $this->CI->box_status_model->insert_box_status($data);
            } else {
                $this->CI->box_status_model->update_box_status($data);
            }
            $this->CI->cache->delete($open_key);
            return 'succ';
        }
        return $open_door_msg['defalut'];
    }

    public function open_door($box_id, $msg_id)
    {
//        $box_status = $this->CI->box_status_model->get_info_by_box_id_and_msg_id($box_id, $msg_id);
        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if (!$box_status) {
            return FALSE;
        }
        if ($box_status['status'] == "scan") {
            $type = $box_status['refer'];
            $role = $box_status['role'];
            $user = $this->CI->user_model->get_user_info_by_id($box_status['user_id']);
            $data['box_id'] = $box_id;
            $data['last_update'] = date("Y-m-d H:i:s");
            $data['status'] = "busy";//设置状态为 用户购物中...

            //推送消息
            $msg_data = $this->CI->config->item("open_door_succ_msg", "tips");
            if ($role == "admin") {
                $msg_data = $this->CI->config->item("open_door_succ_admin_msg", "tips");

            }
            $data['last_update'] = date("Y-m-d H:i:s");
            $this->CI->box_status_model->update_box_status($data);
            write_log("请求开门，user" . var_export($user, 1) . ",sceneId:" . $box_id . ",零售及状态结果：" . var_export($data, 1));

            //删除购物车cache
            $cache_cart_key = 'cart_' . $box_id . "_" . $box_status['user_id'];
            $this->CI->cache->delete($cache_cart_key);
            $this->CI->cache->delete($cache_cart_key . "_code");


            return TRUE;
        } else {
            return FALSE;
        }
        return $rs;
    }

    /**
     * 关门后设置为盘点
     * @param $box_id
     * @param $labels
     */
    public function close_door($box_id)
    {
        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if ($box_status['status'] == "busy") {
            $status = 'stock';
            //查询设备类型
            $device = $this->CI->equipment_model->get_info_by_equipment_id($box_id);
            if (!$device) {
                write_log("关门时，设备不存在" . var_export($box_status, 1));
            } else {
                if (substr(trim($device['type']), 0, 4) == 'rfid') {
                    $status = 'stock';
                } else {
                    //非RFID设备
                    $status = 'stock';//扫码设备
                    if ($box_status['role'] == 'admin') {
                        //获取配送单
                        $key_no = "deliver_no_" . $box_id;
                        $ship_no = $this->CI->cache->get($key_no);//配送单的单号
                        if ($ship_no) {
                            $this->CI->load->model('equipment_stock_model');
                            $this->CI->equipment_stock_model->update_stock_by_deliver($ship_no);
                        }
                    }
                    //删除购物车cache
                    $cache_cart_key = 'cart_' . $box_id . "_" . $box_status['user_id'];
                    $this->CI->cache->delete($cache_cart_key);
                    $this->CI->cache->delete($cache_cart_key . "_code");
                }
                $data['box_id'] = $box_id;
                $data['status'] = $status;
                $data['last_update'] = date("Y-m-d H:i:s");
                $this->CI->box_status_model->update_box_status($data);

                $log_id = $box_status['open_log_id'];
                //记录关门消息
                $this->CI->log_open_model->close_log($log_id);

            }


        } else {
            write_log("关门前状态不对" . var_export($box_status, 1));
        }
    }

    /**
     * 超时自动上锁
     * @param $box_id
     */
    public function over_time_close_door($box_id)
    {
        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if ($box_status['status'] == "busy" || $box_status['status'] == "scan") {
            $data['box_id'] = $box_id;
            $data['status'] = "free";//设置状态为 空闲
            $data['last_update'] = date("Y-m-d H:i:s");
            $this->CI->box_status_model->update_box_status($data);

            $log_id = $box_status['open_log_id'];
            //记录关门消息
            $this->CI->log_open_model->close_log($log_id);

            //删除购物车cache
            $cache_cart_key = 'cart_' . $box_id . "_" . $box_status['user_id'];
            $this->CI->cache->delete($cache_cart_key);
            $this->CI->cache->delete($cache_cart_key . "_code");
        } else {
            write_log("关门前状态不对" . var_export($box_status, 1));
        }

    }

    /**
     * @param $box_id
     * @param $products
     * 盘点消息类型：
     *  1.用户购买，关门后盘点【custom】
     *  2.主动发起盘点请求【fix_status ....】
     *
     */
    public function stock($box_id, $labels)
    {
        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if (!$box_status) {
            //没有找到零售机的信息，认为是新添加的售货机
            $data = array('use_scene' => 'custom', 'msg_id' => '', 'refer' => 'alipay', 'user_id' => '', 'open_id' => '', 'box_id' => $box_id, 'last_update' => date("Y-m-d H:i:s"), 'notify_msg' => '', 'role' => 'user');
            $data['status'] = 'free';
            $this->CI->box_status_model->insert_box_status($data);
            $box_status = $data;
        }
        $old = $this->get_labels($box_id);
        $diff = $this->label_diff($old, $labels);

        $exception = 0;

        $data_stock = array(
            'create' => date("Y-m-d H:i:s"),
            'old' => join(',', $old),
            'stock' => join(',', $labels),
            'diff' => json_encode($diff),
            'type' => $box_status['role'],
            'device_status' => $box_status['status'],
            'device_id' => $box_id,
        );
        if ($box_status['status'] == "stock" || $box_status['status'] == "busy" || $box_status['status'] == "scan") {
            if ($box_status['role'] == "admin") {
                $this->box_label_change($diff, $box_id, 'down');
            } else {
                //用户购买
                $this->box_label_change($diff, $box_id, 'saled');
                if (isset($diff['del']) && count($diff['del']) >= 1) {
                    $diff['del'] = array_unique($diff['del']);
                    $products = $this->get_label_products($diff['del'], $box_status, "用户购买：");
                    $products = array_values($products);
                }

                //普通用户购买
                if ($products && $exception == 0) {//标签不为空
                    $order = $this->create_and_pay($box_id, $box_status, $products);
                    if ($order) {
                        $log_id = $box_status['open_log_id'];
                        //记录订单消息
                        $this->CI->log_open_model->update_log($log_id, $order['order_name']);
                    } else {
                        write_log('创建订单失败:' . var_export($order, 1) . $box_id . var_export($products, 1), 'crit');
                    }
                }
                $data['box_id'] = $box_id;
                $data['status'] = "free";//设置状态为 空闲
                $data['last_update'] = date("Y-m-d H:i:s");
                $data['use_scene'] = 'custom';
                $rs_d = $this->CI->box_status_model->update_box_status($data);
                write_log('设备更新为free状态:' . var_export($data, 1) . '，更新结果：' . var_export($rs_d, 1));
                $data_stock['obj_no'] = isset($order['order_name']) ? $order['order_name'] : "";
                $this->CI->stock_log_model->insert_stock_log($data_stock);

                //支付
                if ($order) {
                    $rs_pay = $this->pay($order, $box_status['user_id'], $box_status['refer'], $box_status['box_id']);
                    write_log('rs_pay:' . var_export($rs_pay, 1));
                }

            }
        } else if ($box_status['status'] == "free") {
            $data_stock['obj_no'] = "初始化";
            $this->CI->stock_log_model->insert_stock_log($data_stock);
            //初始化
            if (isset($diff['add']) && count($diff['add']) >= 1) {
                $this->get_label_products($diff['add'], $box_status, "初始化：");
            }
            if (isset($diff['del']) && count($diff['del']) >= 1) {
                $this->get_label_products($diff['del'], $box_status, "初始化：");
            }
            $this->box_label_change($diff, $box_id, 'down');
        } else {
            write_log("盘点结算前状态不对" . var_export($box_status, 1));
        }
    }

    /**
     * @param $box_id
     * @param $labels
     * 扫码设备盘点生成订单
     */
    public function scan_stock($box_id, $labels, $eqment_info)
    {
        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if (!$box_status) {
            //没有找到零售机的信息，认为是新添加的售货机
            $data = array('use_scene' => 'custom', 'msg_id' => '', 'refer' => 'alipay', 'user_id' => '', 'open_id' => '', 'box_id' => $box_id, 'last_update' => date("Y-m-d H:i:s"), 'notify_msg' => '', 'role' => 'user');
            $data['status'] = 'free';
            $this->CI->box_status_model->insert_box_status($data);
            $box_status = $data;
        }

        $data_stock = array(
            'create' => date("Y-m-d H:i:s"),
            'old' => '',
            'stock' => join(',', $labels),
            'diff' => join(',', $labels),
            'type' => $box_status['role'],
            'device_status' => $box_status['status'],
            'device_id' => $box_id
        );

        if (count($labels) > 0 && ($box_status['status'] == "stock" || $box_status['status'] == "busy" || $box_status['status'] == "scan")) {
            $product_serails = $this->CI->product_model->get_products_by_serial_code($labels, $eqment_info['platform_id']);

            if ($product_serails) {
                $labels_product_ids = $products = $labels_products = array();
                foreach ($product_serails as $p) {
                    $labels_product_ids[$p['serial_number']] = $p['product_id'];
                    $labels_products[] = $p['serial_number'];
                }
                foreach ($labels as $label) {
                    $label = trim($label);
                    if (in_array($label, $labels_products)) {

                        if (!isset($products[$label])) {
                            $products[$label] = array('product_id' => $labels_product_ids[$label], 'qty' => 1);
                        } else {
                            $products[$label] = array('product_id' => $labels_product_ids[$label], 'qty' => $products[$label]['qty'] + 1);
                        }
                    }
                }
                write_log('scan product' . var_export($products, 1));
                $products = array_values($products);
                $order = $this->create_and_pay($box_id, $box_status, $products);
                write_log('scan order' . var_export($order, 1));
                if ($order) {
                    $log_id = $box_status['open_log_id'];
                    //记录订单消息
                    $this->CI->log_open_model->update_log($log_id, $order['order_name']);
                } else {
                    write_log('创建订单失败:' . var_export($order, 1) . $box_id . var_export($products, 1), 'crit');
                }
            }
        }

        $data['box_id'] = $box_id;
        $data['status'] = "free";//设置状态为 空闲
        $data['last_update'] = date("Y-m-d H:i:s");
        $data['use_scene'] = 'custom';
        $rs_d = $this->CI->box_status_model->update_box_status($data);
        write_log('设备更新为free状态:' . var_export($data, 1) . '，更新结果：' . var_export($rs_d, 1));
        $data_stock['obj_no'] = isset($order['order_name']) ? $order['order_name'] : "";
        $this->CI->stock_log_model->insert_stock_log($data_stock);

        if ($order) {
            //是否异步处理订单
            $this->CI->config->load("platform_config", TRUE);
            $ajax_pay = $this->CI->config->item("ajax_pay", "platform_config");
            if ($ajax_pay) {
                $this->ajax_pay($order, $box_status);
            } else {
                $rs_pay = $this->pay($order, $box_status['user_id'], $box_status['refer'], $box_status['box_id']);
                write_log('rs_pay:' . var_export($rs_pay, 1));
                if ($rs_pay['code'] == 408) {
                    //返回失败，则直接进异步处理
                    $order['pay_info'] = $rs_pay['pay_info'];
                    $this->ajax_pay($order, $box_status);
                }
            }

        }
    }

    public function init_stock($box_id, $labels, $scene)
    {

        $box_status = $this->CI->box_status_model->get_info_by_box_id($box_id);
        if (!$box_status) {
            //没有找到零售机的信息，认为是新添加的售货机
            $data = array('use_scene' => 'custom', 'msg_id' => '', 'refer' => 'alipay', 'user_id' => '', 'open_id' => '', 'box_id' => $box_id, 'last_update' => date("Y-m-d H:i:s"), 'notify_msg' => '', 'role' => 'user');
            $data['status'] = 'free';
            $this->CI->box_status_model->insert_box_status($data);
            $box_status = $data;
        }
        $old = $this->get_labels($box_id);
        $diff = $this->label_diff($old, $labels);

        $data_stock = array(
            'create' => date("Y-m-d H:i:s"),
            'old' => join(',', $old),
            'stock' => join(',', $labels),
            'diff' => json_encode($diff),
            'type' => $box_status['role'],
            'device_status' => $box_status['status'],
            'device_id' => $box_id,
            'obj_no' => $scene
        );

        $this->CI->stock_log_model->insert_stock_log($data_stock);
        //初始化
        if (isset($diff['add']) && count($diff['add']) >= 1) {
            $this->get_label_products($diff['add'], $box_status, $scene);
        }
        if (isset($diff['del']) && count($diff['del']) >= 1) {
            $this->get_label_products($diff['del'], $box_status, $scene);
        }
        $this->box_label_change($diff, $box_id, 'down');
    }

    private function ajax_pay($order, $box_status)
    {
        $this->CI->load->helper('mq');
        $this->CI->config->load("platform_config", TRUE);
        $ajax_pay_key = $this->CI->config->item("ajax_pay_key", "platform_config");
        $data_ajax_pay = array(
            'order' => $order,
            'user_id' => $box_status['user_id'],
            'refer' => $box_status['refer'],
            'box_id' => $box_status['box_id'],
        );
        $rs_pay = push_mq_for_redis($ajax_pay_key, $data_ajax_pay);
        if (!$rs_pay) {
            write_log('支付单进入队列失败:' . var_export($rs_pay, 1) . var_export($data_ajax_pay, 1), 'crit');
        }
        return $rs_pay;
    }

    /**
     * @param $data
     * 设备信息处理
     */
    public function action_box_info($data)
    {
        $box_status = $this->CI->box_status_model->get_info_by_box_id($data['deviceId']);
        if ($box_status['use_scene'] == "custom") {

        } else if ($box_status['use_scene'] == "fix_status") {
            //修正状态、系统主动发起请求 $data['use_scene'] = 'custom';//如果涉及到开门，则其场景肯定是 普通
            $device_status = "";
            switch ($data['deviceState']) {//1-空闲 2-开门中 3-盘点中
                case '1':
                    $device_status = 'free';
                    break;
                case '2':
                    $device_status = 'busy';
                    break;
                case '3':
                    $device_status = 'stock';
                    break;
            }

            $data['box_id'] = $data['deviceId'];
            $data['status'] = $device_status;
            $data['use_scene'] = 'custom';
            $data['last_update'] = date("Y-m-d H:i:s");
            $this->CI->box_status_model->update_box_status($data);

            //发起一次盘点，然后修正状态
            if ($device_status != $box_status['status']) {

                $is_new = 0;
                $this->CI->load->helper("device_send");
                stock_request_execute(array('deviceId' => $data['deviceId']), $is_new);
            }
        }
    }

    private function box_label_change($diff, $box_id, $status)
    {
        $diff_add = array();
        if (isset($diff['add']) && count($diff['add']) >= 1) {
            array_walk($diff['add'], function ($label) use ($box_id, &$diff_add) {
                $tmp = $label;
                $group = array();
                $group['equipment_id'] = $box_id;
                $group['label'] = $tmp;
                $group['created_time'] = date("Y-m-d H:i:s");
                $group['last_update_time'] = date("Y-m-d H:i:s");
                $group['status'] = 'active';
                $diff_add[] = $group;
            });
            $this->CI->equipment_label_model->insert_box_label($diff_add);
            unset($diff_add);
        }
        if (isset($diff['del']) && count($diff['del']) >= 1) {
            foreach ($diff['del'] as $v) {
                $this->CI->equipment_label_model->update_box_label(array('equipment_id' => $box_id, 'label' => $v, 'status' => $status));
            }
        }
    }

    /**
     * 计算两个labels差异
     * @param $old 上一次盒子的商品
     * @param $new 最新盘点的结果
     */

    private function label_diff($old, $new)
    {
        $add = array();
        $del = array();
        $same = array();
        if ($new) {
            foreach ($new as $n) {
                if (in_array($n, $old)) {
                    $same[] = $n;
                } else {
                    $add[] = $n;
                }
            }
        }
        if ($old) {
            foreach ($old as $o) {
                if (!in_array($o, $new)) {
                    $del[] = $o;
                }
            }
        }
        return array(
            'add' => $add,
            'del' => $del,
            'same' => $same,
        );
    }

    private function get_labels($box_id, $status = 'active')
    {
        $labels = $this->CI->equipment_label_model->get_labels_by_box_id($box_id, $status);
        if (!$labels)
            return array();
        $ret = array();
        foreach ($labels as $v) {
            $ret[] = $v['label'];
        }
        $ret = array_unique($ret);//去从
        return $ret;
    }

    private function get_label_products($labels, $box_status, $note = "")
    {
        $labels1 = $this->CI->label_product_model->get_product_by_labels($labels);

        if (!$labels1) {
            $ab_data = array(
                'box_id' => $box_status['box_id'],
                'content' => $note . "存在标签未绑定商品，标签有：" . join(",", $labels),
                'uid' => $box_status['user_id'],
                'log_type' => 4//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功 4商品异常
            );
            $this->CI->log_abnormal_model->insert_log($ab_data);
            write_log("设备" . $box_status['box_id'] . "," . $note . "存在标签未绑定商品，标签有：" . join(",", $labels), 'crit');//标签问题
            return array();
        }

        $ret = array();
        $have_label = array();
        foreach ($labels1 as $v) {
            $ret[$v['product_id']]['product_id'] = $v['product_id'];
            if (isset($ret[$v['product_id']]['qty'])) {
                $ret[$v['product_id']]['qty'] += 1;
            } else {
                $ret[$v['product_id']]['qty'] = 1;
            }
            $ret[$v['product_id']]['label'] = $v['label'];
            $ret[$v['product_id']]['product_name'] = $v['product_name'];

            $have_label[] = $v['label'];
        }

        if (count($labels1) != count($labels)) {
            //有些标签未绑定商品
            $ab_data = array(
                'box_id' => $box_status['box_id'],
                'content' => $note . "存在标签未绑定商品，标签有：" . join(",", array_diff($labels, $have_label)),
                'uid' => $box_status['user_id'],
                'log_type' => 4//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功 4商品异常
            );
            $this->CI->log_abnormal_model->insert_log($ab_data);
            write_log("设备" . $box_status['box_id'] . "," . $note . "存在标签未绑定商品，标签有：" . join(",", array_diff($labels, $have_label)), 'crit');//标签问题
        }
        return $ret;
    }

    public function create_and_pay($box_id, $box_status, $products)
    {
        $this->CI->load->model("order_model");
        $order = $this->CI->order_model->create_order($products, $box_status['user_id'], $box_id, $box_status['refer']);//创建支付单
        return $order;
    }

    public function pay($order, $uid, $type = 'alipay', $device_id = 0)
    {
        $this->CI->load->model("order_model");
        $order_info = $this->CI->order_model->get_order_by_name($order['order_name']);
        if ($order_info && $order_info['order_status'] == '1') {
            //订单已支付
            $rs_pay['code'] = 200;
            return $rs_pay;
        }
        $this->CI->load->model("user_model");
        $this->CI->load->model("order_pay_model");
        if (isset($order['pay_info']) && is_array($order['pay_info'])) {
            //如果有支付的信息，则不创建支付单
            $pay_info = $order['pay_info'];
        } else {
            $pay_info = $this->CI->order_pay_model->create_pay($order, $uid, $type);//创建支付单
        }
        $pay_info['device_id'] = $device_id;
        $user = $this->CI->user_model->get_user_info_by_id($uid);

        if ($pay_info['money'] <= 0) {
            //0元订单支付流程
            $notify_data = array('comment' => '支付成功', 'pay_status' => 1, 'pay_money' => '0', 'open_id' => $user['open_id'], 'uid' => $user['id'], 'trade_number' => '', 'pay_user' => '');
            $this->update_order_and_pay($pay_info, $notify_data, $type);
            $rs_pay['code'] = 200;
            return $rs_pay;
        } else {
            if ($type == "alipay") {
                //支付宝支付
                //todo 口碑 支付
                $platform_config = get_platform_config_by_device_id($device_id);
                $partner_id = $platform_config['mapi_partner'];
                $this->CI->load->model('user_agreement_model');
                $exsit1 = $this->CI->user_agreement_model->get_user_agreement_3rd($user['id'], $partner_id);
                if ($exsit1) {
                    $pay_info['agreement_no'] = $exsit1[0]['agreement_no'];
                } else {
                    $pay_info['agreement_no'] = '';
                }

                $is_isv = get_isv_platform($device_id);
                if ($is_isv) {
                    $this->CI->load->helper("koubei_send");
                    $rs = koubei_createandpay_request($pay_info, $platform_config, KOUBEI_SHOP_ID);
                    if ($rs['code'] != 10000) {
                        //异常
                        if ($rs['code'] == 10003) {
                            //10003(ORDER_SUCESS_PAY_INPROCESS)支付等待中状态。
                            //下单成功支付处理中
                            $comment = '下单成功支付处理中';
                            $error_code = 'ORDER_SUCCESS_PAY_INPROCESS';
                            $detail_error_des = $rs['sub_msg'];
                            $notify_data = array('comment' => $comment, 'pay_status' => 4, 'pay_money' => 0, 'open_id' => $user['open_id'], 'uid' => $user['id'], 'trade_number' => '', 'pay_user' => '', 'error_code' => $error_code, 'detail_error_des' => $detail_error_des);
                            $this->update_order_and_pay($pay_info, $notify_data, $type);
                            //---------start zmxy feedback-----//
                            $this->CI->load->helper('zmxy_send');
                            $pay = $this->CI->order_pay_model->get_pay_info_by_pay_no($pay_info['pay_no']);
                            $feed_data = general_zmxy_order_data($pay['order_name'], 0);//未支付
                            send_single_feedback($feed_data, $platform_config);
                            //---------end zmxy feedback-----//
                        } else {
                            //支付失败
                            $comment = $rs['sub_msg'];
                            $detail_error_des = $rs['sub_msg'];
                            $error_code = $rs['sub_code'];
                            $notify_data = array('comment' => $comment, 'pay_status' => 2, 'pay_money' => 0, 'open_id' => $user['open_id'], 'uid' => $user['id'], 'trade_number' => '', 'pay_user' => '', 'error_code' => $error_code, 'detail_error_des' => $detail_error_des);
                            $this->update_order_and_pay($pay_info, $notify_data, $type);
                        }
                    } else if ($rs['code'] == 10000) {
                        //下单成功并支付 ---- 此处不做处理，在异步通知处理
                        $rs_pay['code'] = 200;
                        return $rs_pay;
                    } else {
                        //curl错误，则直接返回
                        $rs_pay['code'] = 408;//超时
                        $rs_pay['message'] = "支付宝扣款异常";
                        $rs_pay['pay_info'] = $pay_info;
                        return $rs_pay;
                    }
                } else {
                    //普通信用代扣，1.0版本
                    $this->CI->load->helper("mapi_send");
                    $pay_info['seller_id'] = $platform_config['pay_sell_id'];
                    $rs = send_alipay_createandpay_request($pay_info, $platform_config);
                    if ($rs && $rs['is_success'] === "T" && $rs['response']['alipay']['result_code'] === 'ORDER_SUCCESS_PAY_SUCCESS') {
                        //下单成功并支付 ---- 此处不做处理，在异步通知处理
                        $rs_pay['code'] = 200;
                        return $rs_pay;
                    } else if ($rs && $rs['is_success'] === "T" && $rs['response']['alipay']['result_code'] === 'ORDER_SUCCESS_PAY_INPROCESS') {
                        //下单成功支付处理中
                        $comment = '下单成功支付处理中';
                        $error_code = 'ORDER_SUCCESS_PAY_INPROCESS';
                        $detail_error_des = $rs['response']['alipay']['detail_error_des'];
                        $notify_data = array('comment' => $comment, 'pay_status' => 4, 'pay_money' => 0, 'open_id' => $user['open_id'], 'uid' => $user['id'], 'trade_number' => '', 'pay_user' => '', 'error_code' => $error_code, 'detail_error_des' => $detail_error_des);
                        $this->update_order_and_pay($pay_info, $notify_data, $type);
                        //---------start zmxy feedback-----//
                        $this->CI->load->helper('zmxy_send');
                        $pay = $this->CI->order_pay_model->get_pay_info_by_pay_no($pay_info['pay_no']);
                        $feed_data = general_zmxy_order_data($pay['order_name'], 0);//未支付
                        send_single_feedback($feed_data, $platform_config);
                        //---------end zmxy feedback-----//
                    } else if ($rs) {
                        //支付失败
                        $comment = $rs['response']['alipay']['display_message'];
                        $detail_error_des = $rs['response']['alipay']['detail_error_des'];
                        $error_code = $rs['response']['alipay']['detail_error_code'];
                        $notify_data = array('comment' => $comment, 'pay_status' => 2, 'pay_money' => 0, 'open_id' => $user['open_id'], 'uid' => $user['id'], 'trade_number' => '', 'pay_user' => '', 'error_code' => $error_code, 'detail_error_des' => $detail_error_des);
                        $this->update_order_and_pay($pay_info, $notify_data, $type);
                        //---------start zmxy feedback-----//
                        $this->CI->load->helper('zmxy_send');
                        $pay = $this->CI->order_pay_model->get_pay_info_by_pay_no($pay_info['pay_no']);
                        $feed_data = general_zmxy_order_data($pay['order_name'], 0);//未支付
                        send_single_feedback($feed_data, $platform_config);
                        //---------end zmxy feedback-----//
                        $rs_pay['code'] = -100;
                        $rs_pay['message'] = $comment ? $comment : "";
                        return $rs_pay;
                    } else {
                        //curl错误，则直接返回
                        $rs_pay['code'] = 408;//超时
                        $rs_pay['message'] = "支付宝扣款异常";
                        $rs_pay['pay_info'] = $pay_info;
                        return $rs_pay;
                    }
                }
            }
        }
        return array('code' => -100);
    }

    /**
     * @param $pay_info = array('subject'=>'','goods'=>array(),'money'=>0.1,'pay_no'=>'');
     * @param $notify_data = array('comment'=>'支付成功','pay_status'=>1,'pay_money'=>'','open_id'=>'','uid'=>'','trade_number'=>'','pay_user'=>'')
     * @param $pay_status ：1 支付成功 2 支付失败 4 支付中 5 退款
     * @param $type
     *
     */
    public function update_order_and_pay($pay_info, $notify_data, $type)
    {
        $this->CI->load->model("order_model");
        $this->CI->load->model("order_pay_model");

        $pay_status = $notify_data['pay_status'];
        $comment = $notify_data['comment'];
        $detail_error_des = isset($notify_data['detail_error_des']) ? ',' . $notify_data['detail_error_des'] : '';
        $this->CI->db->trans_begin();
        //查询支付单
        $pay = $this->CI->order_pay_model->get_pay_info_by_pay_no($pay_info['pay_no']);
        if ($pay && $pay['pay_status'] != "1") {
            //更新支付单
            $this->CI->order_pay_model->update_pay($pay_info['pay_no'], $notify_data['trade_number'], $notify_data['pay_money'], $pay_status, $notify_data['pay_user'], $comment . $detail_error_des);
            //更新订单
            if ($pay_status == 1) {
                $this->CI->order_model->update_order($pay['order_name'], $pay_status);
                //支付成功, 赠送魔力值， 魔豆
                if ($pay_status == 1) {
                    $this->CI->load->model('user_acount_model');
                    $this->CI->user_acount_model->update_user_acount($notify_data['uid'], $notify_data['pay_money'], $pay['order_name']);
                }

            } elseif ($pay_status == 4) {
                //支付确认中
                $this->CI->order_model->update_order($pay['order_name'], 2);//下单成功支付处理中
            }

            if ($this->CI->db->trans_status() === FALSE) {
                $this->CI->db->trans_rollback();
                write_log("更新pay/order 失败，支付单信息" . var_export($pay, 1), 'crit');
            } else {
                $this->CI->db->trans_commit();
                $this->CI->config->load("tips", TRUE);
                if ($pay_status == 1) {

                } else if ($pay_status == 2 || $pay_status == 4) {

                    $order_info = $this->CI->order_model->get_order_by_name($pay['order_name']);
                    if ($order_info) {
                        $ab_data = array(
                            'box_id' => $order_info['box_no'],
                            'content' => $pay['order_name'] . "未支付成功，错误：" . $comment . $detail_error_des,
                            'uid' => $notify_data['uid'],
                            'log_type' => 3//日志类型   1：商品增多   2:开关门状态异常   3：支付不成功
                        );
                        $this->CI->log_abnormal_model->insert_log($ab_data);
                    }

                    if ($notify_data['error_code'] == 'BUYER_BANKCARD_BALANCE_NOT_ENOUGH' || $notify_data['error_code'] == 'BUYER_BALANCE_NOT_ENOUGH') {

                    } elseif ($notify_data['error_code'] == 'AGREEMENT_NOT_EXIST' || $notify_data['error_code'] == 'AGREEMENT_INVALID' || $notify_data['error_code'] == 'AGREEMENT_ERROR') {


                    } elseif ($notify_data['error_code'] == 'ORDER_SUCCESS_PAY_INPROCESS') {

                    } elseif ($notify_data['error_code'] == 'PRODUCT_AMOUNT_LIMIT_ERROR') {

                    } elseif ($notify_data['error_code'] == 'CONTRACT_NOT_EXIST') {


                    } elseif ($notify_data['error_code'] == 'RULELIMIT') {

                    } else {
                        if (empty($pay_info['agreement_no'])) {
                            //协议不存在
                            $msg_data = $this->CI->config->item("pay_fail_msg", "tips");
                            $msg_data['buyer_id'] = $notify_data['open_id'];
                        } else {
                            $msg_data = $this->CI->config->item("pay_fail_unkonw_msg", "tips");
                            $msg_data['buyer_id'] = $notify_data['open_id'];
                            $msg_data['url'] .= $pay['order_name'];
                            $msg_data['keyword2'] = $comment;
                        }
                    }
                }
            }
        } else {
            if ($pay_status == 5) {
                //退款成功
                $this->CI->load->model("order_refund_model");
                $rs = $this->CI->order_refund_model->get_refund($pay_info['pay_no']);
                //更新支付单
                $refund_data = array(
                    'trade_no' => $notify_data['trade_number'],
                    'refund_fee' => $notify_data['pay_money']

                );
                $this->CI->order_refund_model->update_refund($refund_data);
                //更新订单
                $this->CI->order_model->update_order($pay['order_name'], 4);
                if ($this->CI->db->trans_status() === FALSE) {
                    $this->CI->db->trans_rollback();
                    write_log("[notify_post]更新refund/order 失败,退款单信息" . var_export($rs, 1), 'crit');
                } else {
                    $this->CI->db->trans_commit();
                }
            }
            if (!$pay) {
                write_log("[pay]支付单找不到,支付信息" . var_export($pay_info, 1), 'crit');
            } else {
                write_log("[pay]支付单已经更改状态" . var_export($pay_info, 1), 'info');
            }
        }
    }

    /**
     * 更新用户协议字段
     */
    private function update_user_sign($agreement_no, $open_id, $refer)
    {
        $data['agreement_no'] = $agreement_no;
        $data["sign_time"] = date("Y-m-d H:i:s");
        $this->CI->user_model->update_agreement_sign($open_id, $data, $refer);
    }


    private function get_agreement_sign_url($refer = 'alipay', $device_id)
    {
        $platform_config = get_platform_config_by_device_id($device_id);
        if (empty($refer) || $refer === 'alipay') {
            //todo 口碑获取签约URL
            $is_isv = get_isv_platform($device_id);
            if ($is_isv) {
                $this->CI->load->helper("koubei_send");
                return koubei_request_agreement_url($platform_config, $device_id);
            } else {
                $this->CI->load->helper("mapi_send");
                return mapiClient_request_get_agreement_url($platform_config, $device_id);
            };
        } elseif ($refer === 'wechat') {
            $this->CI->load->helper('wechat_send');
            $data = array(
                'contract_code' => time() . rand(1000, 99999),
                'contract_display_account' => '魔盒CITYBOX微信免密支付',
                'request_serial' => time() . rand(1000, 99999),
            );
            return entrustweb($data, $platform_config);
        }
    }

}

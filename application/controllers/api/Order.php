<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Order Controller
 * 订单管理
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */
class Order extends REST_Controller {

    const PAGE_SIZE = 10;
    const REFUND_STATUS = 3;   //退款中
    const ORDER_STATUS_REJECT = 5;//驳回申请
    const ORDER_STATUS_REFUND_APPLY = 3;//退款申请
    const ORDER_STATUS_REFUND = 4;//退款完成
    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->model("order_model");
        $this->load->model("order_product_model");
        $this->load->helper("utils");
        $this->load->helper("aop_send");
        $this->load->helper("mapi_send");
        $this->load->helper("message");
        $this->load->model('order_pay_model');
        $this->load->library("aop/request/AlipayAcquireRefundRequest");
        $this->load->library("aop/request/AlipayAcquireQueryRequest");

    }

    /**
     * 申请退款
     */
    public function refund_post()
    {
        $order_name     = $this->post("order_name");
        $reason = $this->post("reason");
        $reason_detail = htmlspecialchars($this->post("reason_detail"));
        $refund_money  = floatval(sprintf('%.2f', $this->post("refund_money")));
        $photo         = $this->post("photo");

        $this->check_null_and_send_error($order_name,"订单号不能为空");
        $rs = $this->order_model->get_order_by_name($order_name);
        $user = $this->get_curr_user();
        if(!$rs OR $rs["uid"] != $user["id"])
        {
            $this->send_error_response("订单不存在");
        }
        if($rs['order_status'] == Order::REFUND_STATUS){
            $this->send_error_response("订单退款已经申请中，请不要重复提交");
        }
        $data=array(
            "id"=>$rs['id'],
            "order_status"=>Order::REFUND_STATUS,
            'reason' => $reason,
            'reason_detail' => $reason_detail,
            'refund_money'  => $refund_money,
            'photo'         => json_encode($photo),
            'uid'           => $rs["uid"],
            'box_no'        => $rs["box_no"],
            'order_name'    => $rs["order_name"]
        );
        $rs = $this->order_model->refund($data);
        $this->send_ok_response($rs);
    }

    /**
     * 退款审批
     */
    public function refund_approval_post(){
        $refund_no = $this->post("order_name");
        $really_money = $this->post("really_money");//实退金额
        $operation_id = $this->post("operation_id");//操作人
        $this->check_null_and_send_error($refund_no,"订单号不能为空");
        $rs = $this->order_model->get_order_by_name($refund_no);
        write_log($refund_no.var_export($rs,1),'info');
        $this->load->model("order_refund_model");
        $rs_refund = $this->order_refund_model->get_refund($refund_no);
        if(!$rs)
        {
            $this->send_error_response("订单不存在");
        }
        if(!$rs_refund){
            $this->send_error_response("退款申请单不存在");
        }
        $refund_status = false;
        $pay = $this->order_pay_model->get_pay_info_by_pay_no($rs_refund['pay_no']);
        if($pay){
            $config = get_platform_config_by_device_id($rs['box_no']);
            if(floatval($pay['money']) == 0 || floatval($really_money) == 0){
                //0元退款
                $refund_status = true;
                $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>'','refund_fee'=>$really_money,'buyer_id'=>$pay['pay_account']);
                $result['refund_status'] = "succ";
            }
            else if($pay['pay_type'] == 1){
                //todo 口碑 免密退款
                $is_isv = get_isv_platform($rs['box_no']);
                if($is_isv){
                    $this->load->helper('koubei_send');
                    $arr = array(
                        'out_trade_no'=>$rs_refund['pay_no'],
                        'refund_amount'=>$really_money,
                        'refund_reason'=>'顾客申请退款',
                        'operator_id'=>$operation_id,
                        'out_request_no'=>$rs_refund['pay_no'].rand(100,999)//如需部分退款，则此参数必传。
                    );
                    $result = koubei_refund_request($arr,$config);

                    if($result['code'] == 10000){
                        $refund_status = true;
                        $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result['trade_no'],'refund_fee'=>$really_money,'buyer_id'=>$result['buyer_user_id'],);
                    }
                }else{
                    //支付宝免密支付
                    $alipay = new AlipayAcquireRefundRequest();
                    $alipay->setRefundAmount($really_money);
                    $alipay->setRefundReason("顾客申请退款");
                    $alipay->setOutRequestNo($rs_refund['order_name']);
                    $alipay->setOperatorId($operation_id);
                    $alipay->setOutTradeNo($rs_refund['pay_no']); //支付单号
                    $result = mapiClient_request_execute($alipay,null,$config);
                    write_log("退款结果------：".var_export($result,1),'info');
                    if ($result && $result['is_success'] === "T" && $result['response']['alipay']['result_code'] === 'SUCCESS'){
                        $refund_status = true;
                        $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result['response']['alipay']['trade_no'],'refund_fee'=>$really_money,'buyer_id'=>$result['response']['alipay']['buyer_user_id']);
                    }
                }
            }elseif ($pay['pay_type'] == 4){
                //todo 口碑 网页支付退款
                $is_isv = get_isv_platform($rs['box_no']);
                if($is_isv){
                    $this->load->helper('koubei_send');
                    $arr = array(
                        'out_trade_no'=>$rs_refund['pay_no'],
                        'refund_amount'=>$really_money,
                        'refund_reason'=>'顾客申请退款',
                        'operator_id'=>$operation_id,
                        'out_request_no'=>$rs_refund['pay_no'].rand(100,999)//如需部分退款，则此参数必传。
                    );
                    $result = koubei_refund_request($arr,$config);

                    if($result['code'] == 10000){
                        $refund_status = true;
                        $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result['trade_no'],'refund_fee'=>$really_money,'buyer_id'=>$result['buyer_user_id'],);
                    }
                }else{
                    //支付宝网页支付
                    $this->load->library("aop/request/AlipayTradeRefundRequest");
                    $alipay = new AlipayTradeRefundRequest();
                    $arr = array(
                        'out_trade_no'=>$rs_refund['pay_no'],
                        'refund_amount'=>$really_money,
                        'refund_reason'=>'顾客申请退款',
                        'operator_id'=>$operation_id,
                        'out_request_no'=>$rs_refund['pay_no'].rand(100,999)//如需部分退款，则此参数必传。
                    );
                    $alipay->setBizContent(json_encode($arr));



                    $alipay->setNotifyUrl($config['notify_url']);
                    $this->load->helper('aop_send');
                    $result = aopclient_request_execute($alipay,null,$config);
                    $responseNode = str_replace(".", "_", $alipay->getApiMethodName()) . "_response";
                    $resultCode = $result->$responseNode->code;
                    if(!empty($resultCode)&&$resultCode == 10000){
                        $refund_status = true;
                        $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result->$responseNode->trade_no,'refund_fee'=>$really_money,'buyer_id'=>$result->$responseNode->buyer_user_id);
                    }
                }

            }elseif ($pay['pay_type'] == 2 || $pay['pay_type'] == 5){
                //微信支付退款
                $data['out_trade_no'] = $rs_refund['pay_no'];
                $data['out_refund_no'] = time();
                $data['total_fee'] = $pay['pay_money'];
                $data['refund_fee'] = $really_money;
                $data['op_user_id'] = $operation_id;

                $this->load->helper('wechat_send');
                $config = get_platform_config_by_device_id($rs['box_no']);
                $result = refund_wechat($data,$config);

                if($result['return_code'] =='SUCCESS' && $result['result_code'] == 'SUCCESS'){
                    $refund_status = true;
                    $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result['refund_id'],'refund_fee'=>$really_money,'buyer_id'=>$pay['pay_account']);
                }
            }else if($pay['pay_type'] == 3){
                //天天果园
                $this->load->model('user_model');
                $open_id = $this->user_model->get_user_open_id($pay['uid']);
                $request_data = array('source'=>'wap','money'=>$really_money,'order_name'=>$pay['order_name'],'uid'=>$open_id);
                $this->load->helper('fruitday_send');
                $result = refund_money_request_execute($request_data);
                if($result['code'] == 200){
                    $refund_status = true;
                    $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$result['data']['trade_number'],'refund_fee'=>$really_money,'buyer_id'=>$pay['pay_account']);
                }else{
                    write_log('果园接口退款失败'.var_export($rs,1),'crit');
                }
            }else if($pay['pay_type'] == 6){//关爱通退款
                $refund_trade_no = date('YmdHis').$this->rand_code(10);
                $request_data = array('refund_trade_no'=>$refund_trade_no,'outer_trade_no'=>$pay['pay_no'],'reason'=>$rs_refund['reason_detail'], 'amount'=>$really_money);
                $this->load->helper('guanaitong_send');
                $result = send_gat_refund_request($request_data);
                $result['pay_type'] = $pay['pay_type'];
                $this->load->model('user_model');
                $open_id = $this->user_model->get_user_open_id($pay['uid']);
                $this->config->load("tips", TRUE);
                if($result['code'] == 0){
                    $refund_status = true;
                    $data= array('refer'=>$rs['refer'],'box_no'=>$rs['box_no'],'pay_no'=>$pay['pay_no'],'trade_no'=>$refund_trade_no,'refund_fee'=>$really_money,'buyer_id'=>$pay['pay_account']);
                    $msg_data = $this->config->item("gat_refund_succ_msg", "tips");
                    $msg_data['first'] = $msg_data['first'].$really_money.'元';
                    $msg_data['buyer_id'] =  $open_id;
                    Message::send_notify_msg($msg_data, 'gat', $rs_refund['box_no']);
                }else{
                    $msg_data = $this->config->item("gat_refund_error_msg", "tips");
                    $msg_data['buyer_id'] =  $open_id;
                    Message::send_notify_msg($msg_data, 'gat', $rs_refund['box_no']);
                    write_log('关爱通退款失败'.var_export($rs,1),'crit');
                }
            }else if($pay['pay_type'] == 8){//招商银行退款
                
                $this->load->library('CmbChina');
                
                $cmbchina = new CmbChina();
                
                $refund_trade_no = date('YmdHis').$this->rand_code(6);
                
                $result = $cmbchina->doRefund([
                    'order_name' => $pay['pay_no'],
                    'refund_id' => $refund_trade_no,
                    'order_date' => date("Ymd", strtotime($pay['pay_time'])),
                    'refund_fee' => $really_money
                ]);
                
                $this->load->model('user_model');
                $open_id = $this->user_model->get_user_open_id($pay['uid']);
                
                $result['pay_type'] = 8;
                
                if($result['code'] == 200){
                    $refund_status = true;
                    $data = array(
                        'refer' => $rs['refer'],
                        'box_no' => $rs['box_no'],
                        'pay_no' => $pay['pay_no'],
                        'trade_no' => $result['refund_id'],
                        'refund_fee' => $really_money,
                        'buyer_id' => $open_id
                    );
                }else{
                    $refund_status = false;
                }
            }else if($pay['pay_type'] == 11){
                //天天果园免密支付
                $request_data = array('out_refund_id'=>$pay['pay_no'],'source'=>'wap','refund_money'=>$really_money * 100,'order_name'=>$pay['order_name']);
                $this->load->helper('fruitday_send');
                $result = refund_money_mianmi_request_execute($request_data);
                if($result['code'] == 200){
                    $this->send_ok_response(array('refer'=>'fruitday_refund_ajax','status'=>$result['code'] ));
                }else{
                    $this->send_ok_response(array('refer'=>'fruitday_refund_ajax_error','msg'=>$result['msg'] ));
                    write_log('果园接口退款失败'.var_export($rs,1),'crit');
                }

            }
            write_log("退款结果------：".$refund_status.var_export($data,1),'info');
            if ($refund_status) {
                $this->update_order_refund($data);
            }
        }
        write_log("退款结果：".var_export($result,1),'info');
        $this->send_ok_response($result);
    }

    function rand_code($length=6) {
        $code="";
        for($i=0;$i<$length;$i++) {
            $code .= mt_rand(0,9);
        }
        return $code;
    }

    /**
     * data= array('pay_no'=>'','trade_no'=>'','refund_fee'=>'','buyer_id'=>'',)
     */
    private function update_order_refund($data){
        $this->load->model("order_refund_model");
        $pay_status = 4; //退款成功
        $rs = $this->order_refund_model->get_refund_by_pay_no($data['pay_no']);
        if ($rs && !$rs['trade_no']) {
            $this->db->trans_begin();
            //更新退款
            $data_refund = array(
                'out_trade_no'=>$data['pay_no'],
                'trade_no'=>$data['trade_no'],
                'refund_fee'=>$data['refund_fee']
            );
            $this->order_refund_model->update_refund($data_refund);
            write_log("退款结果------update_refund：".var_export($data_refund,1),'info');
            //更新订单
            $this->order_model->update_order($rs['order_name'],$pay_status);
            write_log("退款结果------update_order：".var_export($data_refund,1),'info');
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                write_log("[refund_approval_post]更新refund/order 失败,退款单信息".var_export($rs,1),'crit');
            } else {
                write_log("退款结果------trans_commit：".var_export($data_refund,1),'info');
                $this->db->trans_commit();

                $this->config->load("tips", TRUE);
                $msg_data = $this->config->item("refund_succ_msg", "tips");
                $msg_data['buyer_id'] = $data['buyer_id'];
                $msg_data['url'] .= $rs['order_name'];
                $msg_data['keyword2'] = "￥".$data['refund_fee'];
                Message::send_refund_msg($msg_data,$data['refer'],$data['box_no']);
                write_log("退款结果------send_refund_msg：".var_export($msg_data,1),'info');
            }
        }
    }


    /**
     * 退款驳回
     */
    public function refund_against_post(){
        $order_name = $this->post("order_name");
        $this->check_null_and_send_error($order_name,"订单号不能为空");
        $rs = $this->order_model->get_order_by_name($order_name);
        if(!$rs)
        {
            $this->send_error_response("订单不存在");
        }
        $this->load->model("order_refund_model");
        $rs_refund = $this->order_refund_model->get_refund($order_name);
        if(!$rs_refund){
            $this->send_error_response("退款申请单不存在");
        }

        $this->config->load("tips", TRUE);
        $msg_data = $this->config->item("refund_against_msg", "tips");
        $this->load->model("user_model");
        $user = $this->user_model->get_user_info_by_id($rs_refund['uid']);
        if($user && $user['open_id']){
            $msg_data['buyer_id'] = $user['open_id'];
            $msg_data['url'] .= $rs['order_name'];
            $msg_data['keyword2'] = "￥".$rs_refund['refund_money'];
            Message::send_refund_msg($msg_data,$rs['refer'],$rs['box_no']);
        }
        $this->send_ok_response(array("status"=>'succ'));
    }
    /**
     * 订单列表
     * status 0:未支付1支付
     */
    public function list_order_get()
    {
        $page = (int)$this->get("page");
        $status = (int)$this->get("status");
        $auth_code = $this->get("auth_code");
        if($page<=0)
            $page = 1;
        $page_size = $this->get("page_size");
        if(!$page_size)
            $page_size = PAGE_SIZE;
        $user = $this->get_curr_user();
        $rs = $this->order_model->list_orders($user["id"],$status,$page,$page_size);
        $this->send_ok_response($rs);
    }

    /**
     * 获取订单详情
     */
    public function get_detail_get()
    {
        $order_name = $this->get("order_name");
        $order_no = $this->get("order_no");
        if(empty($order_no) && empty($order_name)){
            $this->check_null_and_send_error($order_name,"订单号不能为空");
        }
        if(empty($order_name)  && $order_no){
            $this->load->model('order_pay_model');
            $pay = $this->order_pay_model->get_pay_info_by_pay_no($order_no);
            if($pay){
                $order_name = $pay['order_name'];
            }
        }
        $user = $this->get_curr_user();
        $rs = $this->order_model->get_order_by_name($order_name);
        if($rs["uid"] != $user["id"]){
            $this->send_error_response("订单不存在");//不能查看别人的订单
        }
        $rs["discount"] = $this->order_discount_log_model->list_order_discount($order_name);//优惠信息
        if(!$rs["discount"]){
            $rs["discount"] = array();
        }
        if(!empty($rs['use_card']) && $rs['card_money'] >0){
            $this->load->model('card_model');
            $card_info = $this->card_model->get_card_by_number($rs['use_card']);
            if($card_info){
                $card = array(
                    'text'=>$card_info['card_name'],
                    'discount_money'=>$rs['card_money']
                );
                $rs["discount"][] = $card;
            }
        }
        $rs["goods"] = $this->order_product_model->list_order_products($order_name);
        if($rs['order_status'] == self::ORDER_STATUS_REJECT){
            //查找驳回的回复
            $this->load->model("order_refund_model");
            $refund = $this->order_refund_model->get_refund($order_name);
            if($refund && $refund['admin_apply']){
                $rs["refund_reply"] = $refund['admin_apply'];
            }
        }
        if($rs['order_status'] == self::ORDER_STATUS_REFUND_APPLY || $rs['order_status'] == self::ORDER_STATUS_REFUND ){
            //退款金额
            $rs_refund= $this->order_refund_model->get_refund($order_name);
            $rs["refund_money"] = $rs_refund ? $rs_refund['refund_money'] : 0;
            if($rs["really_money"]){
                $rs["refund_money"] = $rs["really_money"];
            }
        }
        $bind_phone = $this->get("bind_phone");

        if($bind_phone){
            if(!$user['mobile']){
                $this->load->model('user_model');
                $user = $this->user_model->get_user_info_by_id($user['id']);
                $rs['mobile'] = $user['mobile'];
            }
            $rs['mobile'] = $user['mobile'] ? $user['mobile'] : 0;
        }
        $this->send_ok_response($rs);

    }

    /**
     * 配送单
     */
    public function list_deliver_get(){
        $this->load->model("deliver_model");
        $page = (int)$this->get("page");
        $deliver_no = $this->get("deliver_no");
        if($page<=0)
            $page = 1;
        $page_size = $this->get("page_size");
        if(!$page_size)
            $page_size = PAGE_SIZE;
        $user = $this->get_curr_user();
        $rs = $this->deliver_model->list_deliver($user["id"],$page,$page_size,$deliver_no);
        $this->send_ok_response($rs);
    }

    /**
     * 手动发起根据订单号发起支付
     */
    public function pay_by_manual_get(){
        $order_name = $this->get("order_name");
        $user = $this->get("user");//用户自动发起
        if(substr($order_name,0,1) == "M"){
            //余额充值的订单
            $this->load->model('recharge_online_model');
            $rs = $this->recharge_online_model->get_info_by_order_name($order_name);
            if(!$rs){
                $this->send_error_response("充值单不存在");
            }
            if($rs['status'] == 2){
                $this->send_error_response("充值单不存在已支付");
            }
            $pay_info = array(
                'pay_no'=>$order_name,
                'money' => $rs['money'],
                'order_name' => $order_name,
                'subject' => "在线充值".$rs['money'],
                'goods'=>array(array('goodsName'=>"在线充值".$rs['money'],'goodsId'=>'0','quantity'=>1,'price'=>$rs['money']))
            );
            $rs['box_no'] = 0;
        }else{
            $rs = $this->order_model->get_order_by_name($order_name);
            if(!$rs){
                $this->send_error_response("订单不存在");
            }
            if($rs['order_status'] != 0 && $rs['order_status'] != 2){
                $this->send_error_response("订单已支付");
            }
            $data = array(
                'money' => $rs['money'],
                'order_name' => $order_name,
                'detail' => $this->order_product_model->list_order_products($order_name)
            );
        }

        if($rs['refer'] == 'alipay' && $user == "1"){
            //todo 口碑 网页支付
            $this->load->model("order_pay_model");
            if(!$pay_info){
                $pay_info = $this->order_pay_model->create_pay($data, $rs['uid'], 'alipay_wap');//创建支付单
            }
            $config = get_platform_config_by_device_id($rs['box_no']);
            $is_isv = get_isv_platform($rs['box_no']);
            if($is_isv){
                $this->load->helper("koubei_send");
                $this->load->model("user_model");
                $pay_info['open_id'] = $this->user_model->get_user_open_id($rs['uid']);
                $rs = koubei_wap_pay_request($pay_info,$config,$rs['box_no'],KOUBEI_SHOP_ID);
                $rs_pay = array('code'=>200,'message'=>$rs,'is_isv'=>1);
            }else{
                $this->load->helper("aop_send");
                $rs = pay_wap_alipay_request($pay_info,$config,$rs['box_no']);
                $rs_pay = array('code'=>200,'message'=>$rs);
            }
            $this->send_ok_response($rs_pay);
        } elseif ($rs['refer'] == 'wechat' && $user == "1"){
            $this->load->model("order_pay_model");
            if(!$pay_info) {
                $pay_info = $this->order_pay_model->create_pay($data, $rs['uid'], 'wechat_wap');//创建支付单
            }
            $this->load->helper("wechat_send");
            $this->load->model("user_model");
            $pay_info['open_id'] = $this->user_model->get_user_open_id($rs['uid']);
            $config = get_platform_config_by_device_id($rs['box_no']);
            write_log('wechat payinfo'.var_export($pay_info,1),'info');
            $rs_pay = pay_wap($pay_info,$config);
            $this->send_ok_response($rs_pay);
        } elseif ($rs['refer'] == 'gat' && $user == "1"){  //关爱通
            $this->load->model("order_pay_model");
            $pay_info = $this->order_pay_model->create_pay($data, $rs['uid'], 'alipay_wap');//创建支付单
            $this->load->helper("aop_send");
            $config = get_platform_config_by_device_id($rs['box_no']);
            $rs = pay_wap_alipay_request($pay_info,$config,$rs['box_no']);
            $rs_pay = array('code'=>200,'message'=>$rs);
            $this->send_ok_response($rs_pay);
        } else {
            $this->load->library("Device_lib");
            $device = new Device_lib();

            $rs_pay = $device->pay($data, $rs['uid'], $rs['refer'],$rs['box_no']);
            $this->order_model->update_order_last_update($order_name);
            if($rs_pay['code'] == 200){
                $this->send_ok_response($rs_pay);
            }else{
                $this->send_error_response($rs_pay['message']);
            }

        }

    }

    /**
     * 微信小程序网页支付
     */
    function program_wap_pay_post(){
        $order_name = $this->post("order_name");
        $this->check_null_and_send_error($order_name,'订单号不能为空');
        if(substr($order_name,0,1) == "M"){
            //余额充值的订单
            $this->load->model('recharge_online_model');
            $rs = $this->recharge_online_model->get_info_by_order_name($order_name);
            if(!$rs){
                $this->send_error_response("充值单不存在");
            }
            if($rs['status'] == 2){
                $this->send_error_response("充值单不存在已支付");
            }
            $pay_info = array(
                'pay_no'=>$order_name,
                'money' => $rs['money'],
                'order_name' => $order_name,
                'subject' => "在线充值".$rs['money'],
                'goods'=>array(array('goodsName'=>"在线充值".$rs['money'],'goodsId'=>'0','quantity'=>1,'price'=>$rs['money']))
            );
            $rs['box_no'] = 0;
        }else{
            $rs = $this->order_model->get_order_by_name($order_name);
            if(!$rs){
                $this->send_error_response("订单不存在");
            }
            if($rs['order_status'] != 0 && $rs['order_status'] != 2){
                $this->send_error_response("订单已支付");
            }
        }

        $data = array(
            'money' => $rs['money'],
            'order_name' => $order_name,
            'detail' => $this->order_product_model->list_order_products($order_name)
        );
        $this->load->model("order_pay_model");
        if(!$pay_info) {
            $pay_info = $this->order_pay_model->create_pay($data, $rs['uid'], 'wechat_wap');//创建支付单
        }
        $this->load->helper("wechat_send");
        $this->load->model("user_model");
        $pay_info['open_id'] = $this->user_model->get_user_open_id($rs['uid'],'program_openid');
        $config = get_platform_config_by_device_id($rs['box_no']);
        //替换APPID未小程序的APPID
        $config['wechat_appid'] =  $config['wechat_program_appid'];
        $config['wechat_secret'] = $config['wechat_program_secret'];

        $pay_info['attach'] = $rs['box_no'];
        write_log('wechat program payinfo'.var_export($pay_info,1),'info');
        $rs_pay = pay_wap($pay_info,$config);
        $this->send_ok_response($rs_pay);
    }

    /**
     * 后台调用更新支付单的状态
     */
    public function update_pay_order_get()
    {
        $order_name = $this->get("order_name");
        $this->check_null_and_send_error($order_name,"订单号不能为空");
        $rs = $this->order_model->get_order_by_name($order_name);
        if(!$rs){
            $this->send_error_response("订单不存在");
        }
        if($rs['pay_status'] != 0){
            $this->send_error_response("订单已支付");
        }
        $pay_orders = $this->order_pay_model->get_pay_order_by_order_name($order_name);
        $config = get_platform_config_by_device_id($rs['box_no']);
        if($pay_orders){
            foreach($pay_orders as $v){
                if($v['pay_type'] == 1 && $v['pay_status'] == 0){
                    //支付宝
                    $rs = $this->alipay_query_order($v['pay_no'],$config);
                }else if($v['pay_type'] == 2 && $v['pay_status'] == 0){
                    //微信支付
                    $rs = $this->wechat_query_order($v['pay_no'],$config);
                }

                if($rs){
                    $this->update_order_and_pay($rs);
                    $this->send_ok_response($rs);
                }
            }
        }
        $this->send_error_response("订单未支付");
    }

    private function alipay_query_order($out_trade_no,$config){
        $alipay = new AlipayAcquireQueryRequest();
        $alipay->setOutTradeNo($out_trade_no);
        $rs = mapiClient_request_execute($alipay,$config);
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

    private function wechat_query_order($out_trade_no,$config){
        $this->load->helper('wechat_send');

//        $config = get_platform_config(1);

        $rs = query_wx_order($out_trade_no,$config);
        if($rs && $rs['result_code'] === 'SUCCESS' &&  $rs['result_code'] === 'SUCCESS' ){
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

    private function update_order_and_pay($data){

        $pay_status = 1;
        $comment = "支付成功";
        $pay = $this->order_pay_model->get_pay_info_by_pay_no($data['out_trade_no']);
        if($pay && $pay['pay_status'] == "0"){
            //更新支付单
            $this->order_pay_model->update_pay($data['out_trade_no'],$data['trade_no'],$data['total_fee'],$pay_status,$data['buyer_email'],$comment);
            //更新订单
            $this->order_model->update_order($pay['order_name'],$pay_status);

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                write_log("[update_order_and_pay] 更新pay/order 失败，支付单信息".var_export($pay,1),'crit');
            } else {
                $this->db->trans_commit();
                $order_goods = $this->order_product_model->list_order_products($pay['order_name']);
                $data['subject'] ="";
                if($order_goods){
                    foreach($order_goods as $k=>$v){
                        $data['subject'] .= ' '.$v['product_name'];
                    }
                }
                if($pay['pay_type'] == 1){
                    //发送支付宝信息
                    $this->config->load("tips", TRUE);
                    $msg_data = $this->config->item("pay_succ_msg", "tips");
                    $msg_data['buyer_id'] = $data['buyer_id'];
                    $msg_data['url'] .= $pay['order_name'];
                    $msg_data['first'] .= $data['out_trade_no'];
                    $msg_data['keyword1'] = "￥" . $data['total_fee'];
                    $order_info = $this->order_model->get_order_by_name($pay['order_name']);
                    if($order_info['discounted_money']>0){
                        $msg_data['keyword1'] .= "，优惠金额￥".$order_info['discounted_money'];
                    }
                    $msg_data['keyword2'] = $data['subject'];
                    Message::send_pay_succ_msg($msg_data,"alipay",$order_info['box_no']);
                }else if($pay['pay_type'] == 2){
                    //todo 发送支付消息
                }
            }
        }else{
            write_log(" [[update_order_and_pay]] 支付单找不到,".var_export($data,1),'crit');
        }
    }

    /**
     * 微信支付回调
     */
    public function wecht_notify_pay_get(){
        $this->wechat_pay();
    }
    public function wecht_notify_pay_post(){
        $this->wechat_pay();
    }
    private function wechat_pay(){
        $this->load->library("Device_lib");
        $this->load->model("receive_alipay_log_model");
        $msg_data = array(
            'msg_type'=>'wechat_notify',
            'param'=>$GLOBALS['HTTP_RAW_POST_DATA'],
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        write_log(" notify para:" . var_export($GLOBALS['HTTP_RAW_POST_DATA'], 1),'info');

        $this->load->helper('wechat_send');

        $xml2arr = xml2array($GLOBALS['HTTP_RAW_POST_DATA']);
        if(isset($xml2arr['attach']) && !empty($xml2arr['attach']))
        {
            $device_id = $xml2arr['attach'];
        }
        $config = get_platform_config_by_device_id($device_id);

        $notify = wechat_notify($config);
        write_log('WeChat check notify:'.var_export($notify,1),'info');
        if($notify){
            if(substr($notify->data['out_trade_no'],0,1) == 'M'){
                if($notify->data["result_code"] == "SUCCESS") {
                    //充值单
                    //充值单
                    write_log("wap recharge notify para:" . $notify->data['out_trade_no'].",". $notify->data['transaction_id'],'info');
                    $this->load->model('recharge_online_model');
                    $rs_recharge = $this->recharge_online_model->success_recharge($notify->data['out_trade_no'], $notify->data['transaction_id']);
                    write_log("wap recharge notify result:" . var_export($rs_recharge,1),'info');
                }
            }else {
                $pay = $this->order_pay_model->get_pay_info_by_pay_no($notify->data['out_trade_no']);
                $pay_info = array('subject' => '大朗科技购买商品', 'pay_no' => $notify->data['out_trade_no']);
                if ($pay) {
                    if ($notify->data["result_code"] == "FAIL") {
                        $pay_status = 2;//支付失败
                        $comment = $notify->data["err_code_des"];
                        $notify_data = array('comment' => $comment, 'pay_status' => $pay_status, 'pay_money' => 0, 'open_id' => '', 'uid' => $pay['uid'], 'trade_number' => '', 'pay_user' => '', 'error_code' => $notify->data['err_code']);
                    } else if ($notify->data["result_code"] == "SUCCESS") {
                        $comment = '微信支付成功';
                        $pay_status = 1;
                        $open_id = $notify->data['openid'];
                        $pay_money = $notify->data['total_fee'] / 100;
                        $uid = $pay['uid'];
                        $pay_user = $open_id;
                        $trade_number = $notify->data['transaction_id'];
                        $notify_data = array('comment' => $comment, 'pay_status' => $pay_status, 'pay_money' => $pay_money, 'open_id' => $open_id, 'uid' => $uid, 'trade_number' => $trade_number, 'pay_user' => $pay_user);
                    }
                    $lib = new Device_lib();
                    $lib->update_order_and_pay($pay_info, $notify_data, 'wechat');
                }else{
                    write_log("[wecht_notify_pay_post]支付单找不到,推送信息".var_export($_REQUEST,1),'crit');
                }
            }
        }else{
            write_log("[wecht_notify_pay_post]验证签名失败,推送信息".var_export($_REQUEST,1),'crit');
        }
    }

    /**
     * 支付宝支付异步通知
     */
    public function notify_post()
    {
        $this->load->model("order_pay_model");
        $this->load->model("order_model");
        $this->load->model("receive_alipay_log_model");
        $this->load->library("Device_lib");
        $msg_data = array(
            'msg_type'=>'alipay-notify',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        write_log(" notify para:" . var_export($_REQUEST, 1),'info');
        $config = get_platform_config_by_device_id($_REQUEST['device_id']);
        unset($_REQUEST['device_id']);
        write_log('config=>'.var_export($config,1),'info');
        if (verifyNotify($config)) {
            //验证通过
            write_log(" notify verify:验证通过",'info');
            if($_POST['trade_status'] === 'TRADE_SUCCESS' && $_POST['refund_status'] === 'REFUND_SUCCESS') {
                $pay_info['pay_no'] = $_POST['out_biz_no'];
                $notify_data = array('pay_status'=>5,'trade_number'=>$_POST['trade_no'],'pay_money'=>$_POST['refund_fee']);
                $lib = new Device_lib();
                $lib->update_order_and_pay($pay_info,$notify_data,'wechat');

            }else if ($_POST['trade_status'] === 'TRADE_SUCCESS') {
                $pay = $this->order_pay_model->get_pay_info_by_pay_no($_POST['out_trade_no']);
                $pay_info = array('subject'=>'大朗科技购买商品','pay_no'=> $_POST['out_trade_no']);
                if($pay && $pay['pay_status'] != "1"){
                    $comment = '支付成功';
                    $pay_status = 1;
                    $open_id = $_POST['buyer_id'];
                    $pay_money = $_POST['total_fee'];
                    $uid = $pay['uid'];
                    $pay_user = $_POST['buyer_email'];
                    $trade_number = $_POST['trade_no'];
                    $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);
                    $lib = new Device_lib();
                    $lib->update_order_and_pay($pay_info,$notify_data,'alipay');
                }else{
                    if(!$pay){
                        write_log("[notify_post]支付单找不到,支付单号".$_POST['out_trade_no'],'crit');
                    }else{
                        write_log("[notify_post]支付单已经更改状态".$_POST['out_trade_no'],'info');
                    }
                }
                //---------start zmxy feedback-----//
                $this->load->helper('zmxy_send');
                $feed_data = general_zmxy_order_data($pay['order_name'],2,date('Y-m-d H:i:s'));//未支付
                send_single_feedback($feed_data,$config);
                //---------end zmxy feedback-----//
            }
            echo "success";
        } else {
            write_log("[notify_post]验证不通过".var_export($_REQUEST,1),'info');
            echo "fail";
        }
    }

    public function notify_alipay_wap_post()
    {
        $this->load->model("order_pay_model");
        $this->load->model("order_model");
        $this->load->model("receive_alipay_log_model");
        $this->load->library("Device_lib");
        $msg_data = array(
            'msg_type'=>'alipay-wap-notify',
            'param'=>json_encode($_REQUEST),
            'receive_time'=>date('Y-m-d H:i:s')
        );
        $this->receive_alipay_log_model->insert_log($msg_data);
        write_log("wap notify para:" . var_export($_REQUEST, 1),'info');

        $this->load->helper('aop_send');

        $device_id = $_REQUEST['device_id'];
        $platform_config= get_platform_config_by_device_id($device_id);
        unset($_REQUEST['device_id']);
        unset($_REQUEST['order_name']);

        if (alipay_check($_REQUEST,$platform_config)) {
            //验证通过
            write_log(" notify verify:验证通过");
            if($_POST['trade_status'] === 'TRADE_SUCCESS' && isset($_POST['refund_status']) && $_POST['refund_status'] === 'REFUND_SUCCESS') {
                $pay_info['pay_no'] = $_POST['out_biz_no'];
                $notify_data = array('pay_status'=>5,'trade_number'=>$_POST['trade_no'],'pay_money'=>$_POST['refund_fee']);
                $lib = new Device_lib();
                $lib->update_order_and_pay($pay_info,$notify_data,'wechat');
            }else if ($_POST['trade_status'] === 'TRADE_SUCCESS') {
                if(substr($_POST['out_trade_no'],0,1) == 'M'){
                    //充值单
                    write_log("wap recharge notify para:" . $_POST['out_trade_no']. $_POST['trade_no'],'info');
                    $this->load->model('recharge_online_model');
                    $rs_recharge = $this->recharge_online_model->success_recharge($_POST['out_trade_no'], $_POST['trade_no']);
                    write_log("wap recharge notify result:" . var_export($rs_recharge,1),'info');
                }else{
                    $pay = $this->order_pay_model->get_pay_info_by_pay_no($_POST['out_trade_no']);
                    $pay_info = array('subject'=>'大朗科技购买商品','pay_no'=> $_POST['out_trade_no']);
                    if($pay && $pay['pay_status'] != "1"){
                        $comment = '支付成功';
                        $pay_status = 1;
                        $open_id = $_POST['buyer_id'];
                        $pay_money = $_POST['buyer_pay_amount'];
                        $uid = $pay['uid'];
                        $pay_user = $_POST['buyer_logon_id'];
                        $trade_number = $_POST['trade_no'];
                        $notify_data = array('comment'=>$comment,'pay_status'=>$pay_status,'pay_money'=>$pay_money,'open_id'=>$open_id,'uid'=>$uid,'trade_number'=>$trade_number,'pay_user'=>$pay_user);
                        $lib = new Device_lib();
                        $lib->update_order_and_pay($pay_info,$notify_data,'alipay');
                    }else{
                        if(!$pay){
                            write_log("[notify_alipay_wap_post]支付单找不到,支付单号".$_POST['out_trade_no'],'crit');
                        }else{
                            write_log("[notify_alipay_wap_post]支付单已经更改状态".$_POST['out_trade_no'],'info');
                        }
                    }
                    //---------start zmxy feedback-----//
                    $this->load->helper('zmxy_send');
                    $feed_data = general_zmxy_order_data($pay['order_name'],2,date('Y-m-d H:i:s'));//未支付
                    send_single_feedback($feed_data,$platform_config);
                    //---------end zmxy feedback-----//
                }
            }
            echo "success";
        } else {
            echo "fail";
        }
    }

    /**
     * 关爱通支付回调
     */
    public function notify_gat_post(){
        //todo 关爱通支付回调，参考前面的回调方法


    }

    /**
     * 检查订单状态查询接口
     *
    */
    public function check_order_get(){

        $this->load->model('user_model');
        $order_name = $this->get("order_name");
        $order = $this->order_model->get_order_by_name($order_name);

        if(!$order){
            $this->send_error_response("订单不存在");
        }
        if($order['refer'] == 'sodexo'){
            //查询订单状态
            $pay_info   = $this->order_pay_model->get_order_one_pay($order_name);
            $user_info  = $this->user_model->get_user_info(array('id'=>$order['uid']));
            $check_data = array('box_no'=>$order['box_no'], 'mobile' => $user_info['mobile'], 'amount'=>$pay_info['money'], 'transactionId'=>$pay_info['trade_no']);
            $this->load->library("open/SodexoLib");
            $SodexoLib = new SodexoLib();
            $rs = $SodexoLib->checkPay($check_data);//检查支付状态
            if($rs['code'] == 200 && $rs['status'] == 'success'){//支付成功
                $notify = array('pay_no' => $pay_info['pay_no'],'money'=>null, 'order_name'=>$order_name, 'uid'=>$order['uid'] );
                $this->order_model->order_pay_success($notify);
                $this->send_ok_response(array('code'=>200));
            }else{
                $this->send_ok_response(array('code'=>500));
            }
        }
    }

    /**
     * 沙丁鱼支付成功回调
     */
    public function notify_sdy_post(){
        $order_name = $param['order_name'] = $this->post("order_name");
        $source     = $param['source']     = $this->post('source');
        $money      = $param['money']      = $this->post('money');
        $code       = $param['code']       = $this->post('code');
        $param['rand'] = $this->post('rand');
        $sign      = $this->post('sign');

        $this->load->library("open/OpenLib");
        $openLib = new OpenLib();
        if(!in_array($source, array('sdy'))){
            $this->send_ok_response(array('code'=>503, 'msg'=>'非法的扫码来源'));
        }
        if($sign != $openLib->get_open_sign($param)){
            $this->send_ok_response(array('code'=>504, 'msg'=>'签名失败'));
        }
        if(!$order_name || !$source || !$money || !$param['rand']){
            $this->send_ok_response(array('code'=>507, 'msg'=>'缺少参数'));
        }
        $order      = $this->order_model->get_order_by_name($order_name);//订单信息
        $pay_info   = $this->order_pay_model->get_order_one_pay($order_name);//支付信息
        if($pay_info){
            if($code==200){//支付成功
                $notify = array('pay_no' => $pay_info['pay_no'],'money'=>$money, 'order_name'=>$order_name, 'uid'=>$order['uid'] );
                $this->order_model->order_pay_success($notify);
                $this->send_ok_response(array('code'=>200));
            }else{
                $this->order_pay_model->update_pay_status($pay_info['pay_no'], 2, '沙丁鱼支付失败');
                $this->send_ok_response(array('code'=>200));
            }
        }
        $this->send_ok_response(array('code'=>500));
    }
}

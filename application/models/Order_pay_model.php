<?php
if (! defined ( 'BASEPATH' )) exit ( 'No direct script access allowed' );
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/13
 * Time: 下午1:24
 */
class Order_pay_model extends MY_Model
{

    const PAY_TYPE_ALIAPY = 1;//支付宝免密支付
    const PAY_TYPE_WECHAT = 2;//微信支付
    const PAY_TYPE_FRUITDAY = 3;//天天果园
    const PAY_TYPE_ALIPAY_WAP = 4;//支付宝网页
    const PAY_TYPE_WECHAT_WAP = 5;//微信网页
    const PAY_TYPE_GAT = 6;//关爱通免密支付
    const PAY_TYPE_GAT_WAP = 7;//关爱通网页支付
    const PAY_TYPE_CMB = 8;//招商银行手机支付
    const PAY_TYPE_SODEXO = 9;//索迪斯支付

    const PAY_STATUS_CONFIRM = 4;//支付状态0:待支付，1：支付成功，2：支付失败，3：部分支付 4:下单成功支付处理中


    function __construct()
    {
        parent::__construct ();
    }

    public function table_name()
    {
        return 'order_pay';
    }

    /*
     *
     * @desc 发起支付宝支付
     * @param $order_info array 订单详情
     * @param $uid int 用户id
     * @return array(
     *          'pay_no'=>xxxx,
     *          'money'=>xxxx,
     *          'subject'=>xxxx,
     *          'goods'=>array()
     * )
     * */
    public function create_pay($order_info, $uid,$pay_type='alipay'){
        $this->load->model('Order_model');
//        if($order_info['money']<=0){
//            return array();
//        }
        $pay_no = $this->get_pay_no($order_info['order_name']);
        switch ($pay_type){
            case 'alipay':
                $pay_type = self::PAY_TYPE_ALIAPY;
                break;
            case 'wechat':
                $pay_type = self::PAY_TYPE_WECHAT;
                break;
            case 'fruitday':
                $pay_type = self::PAY_TYPE_FRUITDAY;
                break;
            case 'alipay_wap':
                $pay_type = self::PAY_TYPE_ALIPAY_WAP;
                break;
            case 'wechat_wap':
                $pay_type = self::PAY_TYPE_WECHAT_WAP;
                break;
            case 'gat':
                $pay_type = self::PAY_TYPE_GAT;
                break;
            case 'gat_wap':
                $pay_type = self::PAY_TYPE_GAT_WAP;
                break;
            case 'cmb':
                $pay_type = self::PAY_TYPE_CMB;
                break;
            case 'sodexo':
                $pay_type = self::PAY_TYPE_SODEXO;
                break;
            default:
                $pay_type = self::PAY_TYPE_ALIAPY;
                break;
        }
        $insert_id = $this->insert_pay($pay_no, $order_info['order_name'],$pay_type,  $order_info['money'], $uid);
        if($insert_id>0){
            $result['pay_no'] = $pay_no;
            $result['money']  = $order_info['money'];
            $subject = '';
            $goods = array();
            $this->load->model('product_model');
            foreach($order_info['detail'] as $k=>$v){
                $subject .= ' '.$v['product_name'];
                $rs_code = $this->product_model->getSerialCodeByid($v['product_id']);//获取69码
                $goods[$k]['goodsId']   = $rs_code ? $rs_code['serial_number'] : "citybox-".$v['product_id'];
                if(!$goods[$k]['goodsId']){
                    $goods[$k]['goodsId'] = "dlkj-".$v['product_id'];
                }
                $goods[$k]['goodsName'] = $v['product_name'];
                $goods[$k]['quantity']  = $v['qty'];
                $goods[$k]['price']     = $v['price'];
            }
            $result['subject']  = $subject;
            $result['goods']    = $goods;
            $result['order_name']    = $order_info['order_name'];
            return $result;
        }else{
            return array();
        }

    }

    /*insert*/
    public function insert_pay($pay_no, $order_name, $pay_type, $money, $uid){
        $param['pay_no']        = $pay_no;
        $param['order_name']    = $order_name;
        $param['pay_type']      = $pay_type;
        $param['money']         = $money;
        $param['pay_time']      = date('Y-m-d H:i:s');
//        $param['callback_time'] = '0000-00-00 00:00:00';
        $param['pay_status']    = 0;
        $param['uid']           = $uid;
        return $this->insert($param);
    }

    /*
     * @desc 更新支付状态
     * @param $pay_no str 商户流水
     * @param $trade_no str 支付宝流水
     * @param $pay_money float 支付宝实付金额
     * @param $pay_status  int 支付状态
     * @param $pay_account varchar(50) 支付账号
     * @param $pay_comment varchar(50)
     * @return bool
     * */
    public function update_pay($pay_no, $trade_no, $pay_money, $pay_status, $pay_account, $pay_comment){
        $param['trade_no']        = $trade_no;
        $param['pay_money']       = $pay_money;
        $param['pay_status']      = $pay_status;
        $param['pay_account']     = $pay_account;
        $param['callback_time'] = date('Y-m-d H:i:s');
        $param['pay_comment']     = $pay_comment;
        return $this->db->update($this->table_name(), $param, array('pay_no'=>$pay_no));
    }

    /**
     * 更新支付单
     * */
    public function update_pay_status($pay_no, $pay_status, $pay_comment, $pay_money=null){
        $param['pay_status']      = $pay_status;
        $param['callback_time']   = date('Y-m-d H:i:s');
        $param['pay_comment']     = $pay_comment;
        if($pay_money !== null){
            $param['pay_money']       = $pay_money;
        }
        return $this->db->update($this->table_name(), $param, array('pay_no'=>$pay_no));
    }


    public function get_pay_no($order_name){
        $pay_no = $order_name.$this->rand_code(4);
        $this->db->where('pay_no', $pay_no);
        $this->db->from($this->table_name());
        $res = $this->db->get()->num_rows();
        if($res>0){
            return  $this->get_pay_no($order_name);
        }
        return $pay_no;
    }

    function rand_code($length=6) {
        $code="";
        for($i=0;$i<$length;$i++) {
            $code .= mt_rand(0,9);
        }
        return $code;
    }

    function get_pay_info_by_pay_no($pay_no){
        $where = array('pay_no'=>$pay_no);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_pay_succ_by_order_name($order_name){
        $where = array('order_name'=>$order_name,'pay_status'=>1);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_pay_order_by_order_name($order_name){
        $where = array('order_name'=>$order_name);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->result_array();
        return $res;
    }
    /**
     * 获取订单最新支付单
     * @param $order_name string 订单号
     * @return array
    */
    function get_order_one_pay($order_name){
        $where = array('order_name'=>$order_name);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

}
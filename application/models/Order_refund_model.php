<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/25
 * Time: 下午3:37
 * 退款状态1:待处理 2：拒绝  3：退款中  4：退款成功 5：退款失败
 */

class Order_refund_model extends MY_Model
{

    const STATUS_APPLY   = 1;//待处理
    const STATUS_REFUSE  = 2;//拒绝
    const STATUS_DOING   = 3;//退款中
    const STATUS_SUCCESS = 4;//退款成功
    const STATUS_ERROR   = 5;//退款失败

    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'order_refund';
    }

    /*
     * @desc 创建一个退款申请
     * */
    public function create_one($data){
        if(!$data['order_name'] || !$data['uid']){
            return false;
        }
        $refund = $this->get_refund($data['order_name']);
        if($refund){
            return false;
        }
        $param['order_name']    = $data['order_name']?$data['order_name']:'';
        $param['uid']           = $data['uid']?$data['uid']:0;
        $param['box_no']        = $data['box_no']?$data['box_no']:'';
        $param['create_time']   = date('Y-m-d H:i:s');
        $param['reason']        = $data['reason']?intval($data['reason']):1;
        $param['reason_detail'] = $data['reason_detail']?$data['reason_detail']:'';
        $param['refund_money']  = $data['refund_money']?$data['refund_money']:'';
        $param['refund_status'] = self::STATUS_APPLY;
        $param['photo']         = $data['photo']?$data['photo']:'';
        $this->load->model('order_pay_model');
        $pay_succ = $this->order_pay_model->get_pay_succ_by_order_name($data['order_name']);
        if(!$pay_succ)
            return FALSE;
        $param['pay_no']         = $pay_succ['pay_no'];
        $param['platform_id']         = $data['platform_id'];
        //$param['product_id']    = $data['product_id']?$data['product_id']:'';
        return $this->insert($param);
    }

    //生成退款单号
    public function get_refund_name()
    {
        $order_name = date("ymdi") . rand(100000,999999);
        $this->db->where('pay_no', $order_name);
        $this->db->from($this->table_name());
        $res = $this->db->get()->num_rows();
        if ($res) {
            return $this->get_refund_name();
        }
        return $order_name;
    }

    public function get_refund($order_name){
        $this->db->where(array('order_name' => $order_name));
        $this->db->from($this->table_name());
        return $this->db->get()->row_array();
    }

    public function get_refund_by_pay_no($pay_no){
        $this->db->where(array('pay_no' => $pay_no));
        $this->db->from($this->table_name());
        return $this->db->get()->row_array();
    }

    public function update_refund($data){
        $param['trade_no']        = $data['trade_no'];
        $param['really_money']       = $data['refund_fee'];
        return $this->db->update($this->table_name(), $param, array('pay_no'=>$data['out_trade_no']));
    }
}
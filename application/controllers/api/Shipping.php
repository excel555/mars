<?php
/*
 * Created by PhpStorm.
 * User: tangcw
 * Date: 17/4/24
 */
require APPPATH . '/libraries/REST_Controller.php';
use Restserver\Libraries as rl;

class Shipping extends rl\REST_Controller
{

    function __construct()
    {
        // Construct the parent class
        parent::__construct();
        $this->load->helper("utils");
        $this->load->model("shipping_order_model");
    }

    /*
     * 测试
     */
    public function confirm_status_post()
    {
        $shipping_no = $this->post("orderNo");
        $status = $this->post("status");

        $this->check_null_and_send_error($shipping_no,"出库单号参数不能为空");
        $shipping_order = $this->db->select('*')->from('shipping_order')->where(array(
            'shipping_no'=>$shipping_no
        ))->get()->row_array();
        if (!$shipping_order){
            $this->send_error_response('该出库单不存在！');
        }

        $this->db->trans_begin();

        //更改订单状态
        $order_data = array(
            'shipping_no' => $shipping_no,
            'status' => $status
        );
        $this->shipping_order_model->update_shipping_order($order_data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $rs =  false;
        } else {
            $this->db->trans_commit();
            $rs = true;
        }
        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"接收成功"
            );
            $this->send_ok_response($ret);
        }else{
            $this->send_error_response('插入失败！');
        }
    }
    
    public function confirm_num_post()
    {
        $shipping_no = $this->post("orderNo");
        $order_items = $this->post("order_items");
        $order_items = json_decode($order_items);

        $this->check_null_and_send_error($shipping_no,"出库单号参数不能为空");
        $this->check_null_and_send_error($order_items,"订单商品参数不能为空");

        $shipping_order = $this->db->select('*')->from('shipping_order')->where(array(
            'shipping_no'=>$shipping_no
        ))->get()->row_array();
        if (!$shipping_order){
            $this->send_error_response('该出库单不存在！');
        }
        $order_id = $shipping_order['id'];
        $this->db->trans_begin();
        foreach ($order_items as $k=>$val){
            $inner_code = $val->innerCode;
            $count = $val->count;
            $this->db->from('product');
            $this->db->where(array('inner_code'=>$inner_code));
            $product = $this->db->get()->row_array();
//            $product = $this->db->select('*')->from('product')->where(array(
//                'inner_code'=>$inner_code
//            ))->get()->row_array();
            if ($product){
                $productId = $product['id'];
                $product_data = array(
                    'order_id'=>$order_id,
                    'product_id'=>$productId,
                    'real_num'=>$count
                );
                $this->shipping_order_model->update_shipping_order_products($product_data);
            }
        }

        

        //更改订单状态
        $order_data = array(
            'shipping_no' => $shipping_no,
            'operation_id' => 1
        );
        $this->shipping_order_model->update_shipping_order($order_data);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $rs =  false;
        } else {
            $this->db->trans_commit();
            $rs = true;
        }
        if($rs){
            $ret = array(
                "status"=>true,
                "message"=>"接收成功"
            );
            $this->send_ok_response($ret);
        }else{
            $this->send_error_response('插入失败！');
        }
    }



}
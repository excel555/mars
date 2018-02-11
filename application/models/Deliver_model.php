<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/4/05
 * 配送工单  盘点后的数据插入
 */

class Deliver_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    public function table_name()
    {
        return 'deliver';
    }
    /*
     * 插入配送工单数据
     * pramns
     * $deliver_data
     * $deliver_products_data
     *
     */
    function insert_deliver($deliver_data,$deliver_products_data)
    {
        $deliver_data['deliver_no'] = $this->get_delivery_name();

        $this->db->trans_begin();
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($deliver_data['equipment_id']);
        if($equipment_info && isset($equipment_info['platform_id'])){
            $deliver_data['platform_id'] = $equipment_info['platform_id'];
        }

        $this->db->insert('deliver',$deliver_data);
        $deliver_id = $this->db->insert_id();


        if(isset($deliver_products_data)){
            foreach($deliver_products_data as $k=>$v){
                $deliver_products_data[$k]['deliver_id'] = $deliver_id;
            }
        }
        if($deliver_products_data){
            $this->db->insert_batch('deliver_product',$deliver_products_data);
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return $deliver_data['deliver_no'];
        }
    }

    //生成配送单号
    public function get_delivery_name()
    {
        $order_name = date("ymdi") . rand(100000,999999);
        $this->db->where('deliver_no', $order_name);
        $this->db->from($this->table_name());
        $res = $this->db->get()->num_rows();
        if ($res) {
            return $this->get_order_name();
        }
        return $order_name;
    }


    function list_deliver($uid,$page=1,$page_size=10,$deliver_no){

        if($deliver_no){
            $where = array('originator'=>$uid,'deliver_no'=>$deliver_no);
        }else{
            $where = array('originator'=>$uid);
        }
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $this->db->limit($page_size,($page-1)*$page_size);
        $res = $this->db->get($this->table_name())->result_array();
        $this->load->model("deliver_product_model");
        if($res){
            foreach($res as $k=> $g){
                $num_up = 0;
                $num_down = 0;
                $res[$k]["goods"] = $this->deliver_product_model->list_deliver_products($g['id']);
                foreach ($res[$k]["goods"]  as $n){
                    if($n['type'] == 1)
                        $num_up += $n['quantity'];
                    if($n['type'] == 2)
                        $num_down += $n['quantity'];
                }
                $res[$k]["goods_up_num"] = $num_up;
                $res[$k]["goods_down_num"] = $num_down;
            }
        }
        return $res;
    }

    function get_deliver_by_no($deliver_no){

        if(!$deliver_no){
            return false;
        }
        $where = array('deliver_no'=>$deliver_no);
        $this->db->where($where);
        $this->db->order_by('id','DESC');
        $res = $this->db->get($this->table_name())->row_array();
        $this->load->model("deliver_product_model");
        if($res) {
            $res["goods"] = $this->deliver_product_model->list_deliver_products($res['id']);
        }
        return $res;
    }

    function update_shipping_no($shipping_no,$deliver_no){
        if($shipping_no && $deliver_no){
            $this->db->set('shipping_no',$shipping_no);
            $this->db->where('deliver_no', $deliver_no);
            $this->db->update($this->table_name());
        }

    }
}
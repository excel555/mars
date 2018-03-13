<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/12/5
 * Time: 下午3:57
 */

class Shipping_config_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    /*
     * 获取商品数据
     * */
    public function get_all_data($product_id='',$class_id=array(), $eq_name='', $limit=50,$offset=0, $admin_id=0){
        $this->db->select('sc.product_id, sc.product_name,sc.pre_qty, e.name as eq_name, e.equipment_id, e.level, e.admin_id');
        $where = array('p.platform_id'=>$this->platform_id);
        if($product_id){
            $where['sc.product_id'] = $product_id;
        }
        if($eq_name){
            $where['e.name like']   = '%'.$eq_name.'%';
        }
        if($admin_id){
            $where['e.admin_id']    = $admin_id;
        }
        $this->db->from('shipping_config sc');
        $this->db->join('product p', 'p.id=sc.product_id');
        $this->db->join('equipment e','sc.equipment_id=e.id');
        $this->db->where($where);
        if(!empty($class_id)){
            $this->db->where_in('p.class_id', $class_id);
        }
        $this->db->limit($limit,$offset);
        $this->db->order_by('p.id', 'desc');
        return $this->db->get()->result_array();
    }

    public function get_all_data_num($product_id='',$class_id=array(), $eq_name='', $admin_id=0){
        $where = array('p.platform_id'=>$this->platform_id);
        if($product_id){
            $where['sc.product_id'] = $product_id;
        }
        if($eq_name){
            $where['e.name like'] = '%'.$eq_name.'%';
        }
        if($admin_id){
            $where['e.admin_id']    = $admin_id;
        }
        $this->db->from('shipping_config sc');
        $this->db->join('product p', 'p.id=sc.product_id');
        $this->db->join('equipment e','sc.equipment_id=e.id');
        $this->db->where($where);
        if(!empty($class_id)){
            $this->db->where_in('p.class_id', $class_id);
        }
        return $this->db->get()->num_rows();
    }

}
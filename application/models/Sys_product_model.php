<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/24
 */

class Sys_product_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    function get_list($where,$limit='',$offset='',$order='',$sort=''){
        $this->db->select("u.id,u.old_product,u.old_product_name,u.new_product,i.product_name");
        $this->db->from('sys_product u');
        $this->db->join('product i',"u.new_product=i.id",'left');
        $this->db->where($where);
        if($sort){
            $this->db->order_by($sort,$order);
        }
        if($limit){
            $this->db->limit(100000,$offset);
        }

        $list = $this->db->get()->result_array();

        $this->db->select("*");
        $this->db->from('sys_product u');
        $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    function update_bind($old_id,$new_id){
        $data['new_product'] = $new_id;
        $where['old_product'] =$old_id;
        return $this->db->update('cb_sys_product',$data,$where);
    }
}
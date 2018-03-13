<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/12
 * Time: 上午11:10
 * 商品model
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Product_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'product';
    }

    /*
     * @desc 通过id获取商品详情
     * @param $product_ids array  商品id
     * @return array
     * */
    public function get_info_by_ids($product_ids){
        $this->db->from($this->table_name());
        $this->db->where_in('id', $product_ids);
        $res = $this->db->get()->result_array();
        write_log('product sql'.$this->db->last_query());
        if(empty($res)){
            return array();
        }
        $tmp = array();
        foreach($res as $k=>$v){
            $tmp[$v['id']] = $v;
        }
        return $tmp;
    }
    public function get_info_by_id($product_id){
        $this->db->from($this->table_name());
        $this->db->where('id', $product_id);
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }
    public function get_info_by_serial_code($code,$platform){
        $this->db->select("p.id,p.id as product_id,ps.serial_number,p.img_url,p.product_name,p.price,p.old_price,p.unit");
        $this->db->from("product_serial_num as ps");
        $this->db->join('product as p', 'ps.product_id = p.id', 'left');
        $this->db->where(array('ps.serial_number'=> $code,'ps.platform_id'=>$platform,'ps.type'=>'1'));
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    public function get_products_by_serial_code($codes,$platform){
        $this->db->select("ps.product_id,ps.serial_number");
        $this->db->from("product_serial_num as ps");
        $this->db->where(array('ps.platform_id'=>$platform,'ps.type'=>'1'));
        $this->db->where_in('ps.serial_number',$codes);
        $res = $this->db->get()->result_array();
        return $res;
    }

    public function getSerialCodeByid($id){
        $this->db->select("ps.serial_number");
        $this->db->from("product_serial_num as ps");
        $this->db->where(array('ps.product_id'=> $id,'ps.type'=>1));
        $res = $this->db->get()->row_array();
        return $res;
    }
}
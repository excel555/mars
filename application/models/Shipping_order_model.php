<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Shipping_order_model extends MY_Model
{

	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'shipping_order';
    }


    function update_shipping_order($data){
        $param = array();
        
        if ($data['status']){
            $param['status'] = $data["status"];
        }
        if ($data['operation_id']){
            $param['operation_id'] = $data["operation_id"];
        }
        if($data['dm_name']){
            $param['dm_name'] = $data['dm_name'];
        }
        if($data['dm_mobile']){
            $param['dm_mobile'] = $data['dm_mobile'];
        }

        if (empty($param)){
            return true;
        }
        
        return $this->db->update($this->table_name(),$param,array('shipping_no' => $data['shipping_no']));
    }
    
    function update_shipping_order_products($data){
        $param = array();
        
        if ($data['real_num']){
            $param['real_num'] = $data["real_num"];
            return $this->db->update('shipping_order_products',$param,array('order_id'=>$data['order_id'],'product_id'=>$data['product_id']));
        } else {
            return true;
        }
    
        
    }

    function update_shipping_order_simple($data,$where,$where_in){
        if($where)
            $this->db->where($where);
        if($where_in)
            $this->db->where_in($where_in['key'],$where_in['val']);
        return $this->db->update($this->table_name(),$data);
    }
    function get_info_by_ship_no($ship_no){
        if(empty($ship_no)){
            return false;
        }
        $where = array('shipping_no'=>$ship_no);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = $query->row_array();
        return $res;
    }
}

?>

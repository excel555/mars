<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Shipping_order_product_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'shipping_order_products';
    }
    public function return_field()
    {
        return ["id","product_name","stock","add_qty","price","real_num","total_money","product_id",'order_id','add_qty','pre_qty'];
    }

	function list_order_products($deliver_id){
        $field = rtrim(join(",",$this->return_field()),",");
		$where = array('order_id'=>$deliver_id);
		$this->db->select($field);
		$this->db->where($where);
        $res = $this->db->get($this->table_name())->result_array();
        foreach($res as $k=> $g){
            $res[$k]["thumbnailPic"] ="";
            $res[$k]["quantity"] =$g['qty'];
            $res[$k]["name"] = $g['product_name'];
        }
		return $res;
	}

    /**
     * @param $order_id
     * @param $data
     * 更新出库单是真实数量
     */
	function update_pro_real_num($order_id,$data){
	    $update_arr = array();
        foreach ($data as $v){
            if($v['type'] == 1){
                //上架
                $sql = "update cb_shipping_order_products set real_num = real_num + {$v['qty']} WHERE  order_id ={$order_id} AND product_id ={$v['product_id']}";
                $this->db->query($sql);
            }
        }
       ;
    }
}

?>

<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Order_product_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'order_product';
    }
    public function return_field()
    {
        return ["id","order_name","product_id","qty","price","total_money","product_name"];
    }

	function list_order_products($order_name){
        $field = rtrim(join(",",$this->return_field()),",");
		$where = array('order_name'=>$order_name);
		$this->db->select($field);
		$this->db->where($where);
        $res = $this->db->get($this->table_name())->result_array();
        $this->load->model('product_model');
        foreach($res as $k=> $g){
            $p = $this->product_model->get_info_by_id($g['product_id']);
            $img = '';
            if(isset($p['img_url']) && !empty($p['img_url'])){
                $img = IMG_HOST.'/'.$p['img_url'];
            }
            $res[$k]["thumbnailPic"] = $img;
            $res[$k]["quantity"] =$g['qty'];
            $res[$k]["name"] = $g['product_name'];
        }
		return $res;
	}
}

?>

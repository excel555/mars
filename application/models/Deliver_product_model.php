<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Deliver_product_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'deliver_product';
    }
    public function return_field()
    {
        return ["id","product_name","img_url","qty","type","real_qty","deliver_id"];
    }

	function list_deliver_products($deliver_id){
        $field = rtrim(join(",",$this->return_field()),",");
		$where = array('deliver_id'=>$deliver_id);
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
}

?>

<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Order_discount_log_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'order_discount_log';
    }
    public function return_field()
    {
        return ["id","order_name","content"];
    }

	function list_order_discount($order_name){
        $ret = array();
        $field = rtrim(join(",",$this->return_field()),",");
		$where = array('order_name'=>$order_name);
		$this->db->select($field);
		$this->db->where($where);
        $res = $this->db->get($this->table_name())->row_array();
        if($res && isset($res['content']) && !empty($res['content'])){
            $discounts = json_decode($res['content'],TRUE);
            if($discounts){
                foreach ($discounts as $discount){
                    if(isset($discount['text']) && !empty($discount['text'])){
                        $ret[] = $discount;
                    }
                }
            }
        }
		return $ret;
	}
}

?>

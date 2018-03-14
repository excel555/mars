<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Wfz_pos_model extends MY_Model
{

	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'wfz_pos';
    }


    function update_status($order_name,$status){
        $this->db->set('status',$status);
        $this->db->where('order_name', $order_name);
        return $this->db->update($this->table_name());
    }

    function insert_status($order_name,$status,$run_time){
        $data=array(
            'order_name'=>$order_name,
            'status'=>$status,
            'run_time'=>$run_time,
            'pay_time'=>date('Y-m-d')
        );
        return $this->db->insert($this->table_name(),$data);
    }

    function get_status($order_name){
        $this->db->select("*");
        $this->db->where('order_name',$order_name);
        $this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        return $res;
    }
    function get_no($day){
        $this->db->select("*");
        $this->db->where('pay_time',$day);
        $this->db->from($this->table_name());
        $res = $this->db->get()->num_rows();
        return $res;
    }

    function get_run_time(){
        $this->db->select("*");
        $this->db->from($this->table_name());
        $this->db->order_by('id','DESC');
        $res = $this->db->get()->row_array();
        return $res ? $res['run_time'] : "2018-01-01";
    }
}

?>

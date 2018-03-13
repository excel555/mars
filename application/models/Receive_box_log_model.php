<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Receive_box_log_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'receive_box_log';
    }
    public function return_field()
    {
        return ["id","msg_id","msg_type","param","receive_time","response_time","response","status"];
    }

    function get_info_by_msg_id_and_msg_type($msg_id,$msg_type){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('msg_type'=>$msg_type,'msg_id'=>$msg_id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }


    function update_log($data){
        $this->db->set('status',$data["status"]);
        $this->db->set('response',$data["response"]);
        $this->db->set('response_time',$data["response_time"]);
        $this->db->where('id', $data['id']);
        return $this->db->update($this->table_name());
    }

    function insert_log($data){
        $this->db->insert($this->table_name(),$data);
        return $this->db->insert_id();
    }

    function check_heart_in_min($min,$device_id){
        $field = rtrim(join(",",$this->return_field()),",");
        $this->db->select($field);
        $this->db->from($this->table_name());
        $this->db->order_by('id','DESC');
        $this->db->where(array('receive_time >='=>date("Y-m-d H:i:s",time() - $min * 60),'msg_type'=>'heart','device_id'=>$device_id));
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }
}

?>

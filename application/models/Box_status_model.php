<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Box_status_model extends MY_Model
{

	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'box_status';
    }
    public function return_field()
    {
        return ["id","box_id","user_id","open_id","last_update","status","notify_msg","memo","role","refer","msg_id","open_log_id","use_scene"];
    }

    function get_info_by_box_id($id,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");
        }
        $where = array('box_id'=>$id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }
    function get_info_by_box_id_and_msg_id($id,$msg_id){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('box_id'=>$id,'msg_id'=>$msg_id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }



    function update_box_status($data){
        //$this->db->set('user_id',$data["user_id"]);
        //$this->db->set('open_id',$data["open_id"]);
        if(isset($data["refer"]))
            $this->db->set('refer',$data["refer"]);
        if(isset($data["role"]))
            $this->db->set('role',$data["role"]);
        if(isset($data["user_id"]))
            $this->db->set('user_id',$data["user_id"]);
        if(isset($data["open_id"]))
            $this->db->set('open_id',$data["open_id"]);
        $this->db->set('status',$data["status"]);
        if(isset($data["msg_id"]))
            $this->db->set('msg_id',$data["msg_id"]);
        if(isset($data["open_log_id"]))
            $this->db->set('open_log_id',$data["open_log_id"]);
        if(isset($data["use_scene"]))
            $this->db->set('use_scene',$data["use_scene"]);
        $this->db->set('last_update',$data["last_update"]);
        $this->db->where('box_id', $data['box_id']);
        return $this->db->update($this->table_name());
    }

    function insert_box_status($data){
        return $this->db->insert($this->table_name(),$data);
    }

    /**
     *
     * @return null
     */
    function get_exception_boxs($exception_min){
        $field = rtrim(join(",",$this->return_field()),",");
        $this->db->select($field);
        $this->db->where_in('status',array('scan','busy','stock','pay'));
        $this->db->where('last_update <=',date("Y-m-d H:i:s",time() - $exception_min * 60));
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->result_array();
        return $res;
    }
}

?>

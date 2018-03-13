<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_label_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'equipment_label';
    }
    public function return_field()
    {
        return ["id","equipment_id","label","created_time","last_update_time","status"];
    }

    function get_labels_by_box_id($id,$status='active'){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('equipment_id'=>$id,'status'=>$status);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = array();
        if($query)
            $res = $query->result_array();
        return $res;
    }



    function update_box_label($data){
        $this->db->set('status',$data["status"]);
        $this->db->set('last_update_time',date("Y-m-d H:i:s"));
        $this->db->where(array('equipment_id'=>$data['equipment_id'],'label'=>$data['label'],'status'=>'active'));
        return $this->db->update($this->table_name());
    }

    function insert_box_label($data){
        return $this->db->insert_batch($this->table_name(),$data);
    }

    function get_label_saled_list($label,$equipment_id){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('label'=>$label,'equipment_id'=>$equipment_id,'status'=>'saled');
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $res = $this->db->get()->result_array();
        return $res;
    }
}

?>

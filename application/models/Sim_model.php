<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Sim_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'sim';
    }
    public function return_field()
    {
        return ["sim","flow_total","nondir_volume","flow_pool_residue",'card_state'];
    }

    function get_sim_info_by_id($sim,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('sim'=>$sim);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        return $this->db->get()->row_array();

    }
}

?>

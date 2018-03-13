<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_platform_relations_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'user_platform_relations';
    }
    public function return_field()
    {
        return ["id","platform_id","uid"];
    }

    function add_platform_relation($uid,$platform_id){
        $where = array('uid'=>$uid,'platform_id'=>$platform_id);
        $this->db->select("id");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $exsit = $this->db->get()->row_array();
        if(!$exsit){
            return $this->db->insert($this->table_name(),$where);
        }
    }
}

?>

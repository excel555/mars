<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Mobile_black_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'mobile_black';
    }
    public function return_field()
    {
        return ["id","mobile","create_time"];
    }

    function get_black_list($field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");
        }
        $this->db->select($field);
        $this->db->from($this->table_name());
        return $this->db->get()->result_array();

    }
}

?>

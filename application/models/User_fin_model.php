<?php
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_fin_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'user_fin';
    }

    function get_fin_by_id($id){
        $where = array('id'=>$id);
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        return $res;
    }

}
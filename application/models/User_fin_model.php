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

    function get_rank($size){
        $this->db->select("*");
        $this->db->from($this->table_name());
        $this->db->order_by('land','DESC');
        $this->db->limit($size);
        $res = $this->db->get()->result_array();
        foreach ($res as &$re){
            $where = array('id'=>$re['user_id']);
            $this->db->select("user_name");
            $this->db->where($where);
            $this->db->from($this->table_name());
            $u = $this->db->get()->row_array();
            $re['user_name'] = $u['user_name'];
        }
        return $res;
    }
    function get_fin_by_id($id){
        $where = array('user_id'=>$id);
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        return $res;
    }

}
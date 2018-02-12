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
            $this->db->from('user');
            $u = $this->db->get()->row_array();
            $re['user_name'] = $u['user_name'];
        }
        return $res;
    }
    function get_log($uid){
        $this->db->select("*");
        $this->db->from('user_fin_log');
        $this->db->order_by('id','DESC');
        $this->db->where(array('user_id'=>$uid));
        $res = $this->db->get()->result_array();
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

    function get_energy_log($uid){
        $this->db->select("*");
        $this->db->from('user_energy_log');
        $this->db->order_by('id','DESC');
        $this->db->where(array('user_id'=>$uid));
        $res = $this->db->get()->result_array();
        return $res;
    }
    function get_fins_collect($uid){
        $this->db->select("*");
        $this->db->from('fin_land_list');
        $this->db->where(array('user_id'=>$uid,'send_time <='.date("Y-m-d H:i:s"),'status'=>0));
        $res = $this->db->get()->result_array();
        write_log($this->db->last_query());
        return $res;
    }
}
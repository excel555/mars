<?php
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_friend_model extends MY_Model
{
    const SIGN_FRIEND = 10;
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'user_friend';
    }

    function get_friend_list($id){
        $where = array('parent_id'=>$id);
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
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

    function invite($uid,$invite_code){

        $this->db->select("id");
        $this->db->where(array('invite_code'=>$invite_code));
        $this->db->from('user');
        $u = $this->db->get()->row_array();
        $parent_id = $u['id'];


        $this->db->trans_start();
        $user_data = array('user_id'=>$uid,'parent_id'=>$parent_id,'send_time'=>date("Y-m-d H:i:s"));
        $this->db->insert($this->table_name(),$user_data);
        $last_id = $this->db->insert_id();


        $user_energy_log['user_id']           = $parent_id;
        $user_energy_log['create_time']          = date("Y-m-d H:i:s");
        $user_energy_log['obj_type']    = '邀请好友';
        $user_energy_log['obj_id']    = $last_id;
        $energy = $this->get_invite_energy($parent_id);
        $user_energy_log['energy']    = $energy;
        $user_energy_log['energy_type']    = 'add';
        $this->db->insert('user_energy_log', $user_energy_log);

        $user = get_cache_user($parent_id);

        $this->db->set('lastupdate_time',date("Y-m-d H:i:s"));
        $this->db->set('energy',$energy + $user['fin']['energy']);
        $this->db->where(array('user_id'=>$parent_id));
        $this->db->update('user_fin');

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            update_user_cache($parent_id,array("energy"=>$energy + $user['fin']['energy']));
            return $last_id;
        }
    }

    function get_invite_energy($parent_id){
        $where = array('parent_id'=>$parent_id);
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $row_nums = $this->db->get()->num_rows();
        if($row_nums >= 10){
            return 2;
        }else{
            return 10;
        }

    }

}
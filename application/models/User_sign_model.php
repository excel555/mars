<?php
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_sign_model extends MY_Model
{
    const SIGN_GIFT = 2;
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'user_sign';
    }

    function get_sign_today($id){
        $where = array('user_id'=>$id,'sign_day'=>date("Y-m-d"));
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        if($res){
            return false;
        }else{
            $this->sign($id);
        }
        return $res;
    }

    function sign($uid){
        $this->db->trans_start();
        $user_data = array('user_id'=>$uid,'sign_day'=>date("Y-m-d"),'create_time'=>date("Y-m-d H:i:s"));
        $this->db->insert($this->table_name(),$user_data);
        $last_id = $this->db->insert_id();


        $user_energy_log['user_id']           = $uid;
        $user_energy_log['create_time']          = date("Y-m-d H:i:s");
        $user_energy_log['obj_type']    = '每天签到';
        $user_energy_log['obj_id']    = $last_id;
        $user_energy_log['energy']    = self::SIGN_GIFT;
        $user_energy_log['energy_type']    = 'add';
        $this->db->insert('user_energy_log', $user_energy_log);

        $user = get_cache_user($uid);

        $this->db->set('lastupdate_time',date("Y-m-d H:i:s"));
        $this->db->set('energy',self::SIGN_GIFT + $user['fin']['energy']);
        $this->db->where(array('user_id'=>$uid));
        $this->db->update('user_fin');

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            update_user_cache($uid, array("fin"=>array('energy'=>self::SIGN_GIFT + $user['fin']['energy'])));
            return $last_id;
        }
    }

    function get_user_sign_today(){
        $where = array('sign_day'=>date("Y-m-d"));
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        return $this->db->get()->result_array();
    }

    function insert_form_id($uid,$form_id){
        $this->db->insert('wait_send_msg',array('user_id'=>$uid,'create_time'=>date("Y-m-d H:i:s"),'form_id'=>$form_id,'status'=>0));
    }

    function is_sign_today($id){
        $where = array('user_id'=>$id,'sign_day'=>date("Y-m-d"));
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        if($res){
            return true;
        }else{
            return false;
        }
    }
}
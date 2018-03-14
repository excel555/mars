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
            $re['user_name'] = $u['user_name'] ? $u['user_name'] :"";
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
        $this->db->where(array('user_id'=>$uid,'send_time <='=>date("Y-m-d H:i:s"),'status'=>0));
        $res = $this->db->get()->result_array();
        write_log($this->db->last_query());
        return $res;
    }

    function collect_fin($id){
        $this->db->select("*");
        $this->db->from('fin_land_list');
        $this->db->where(array('id'=>$id));
        $res = $this->db->get()->row_array();
        if($res && $res['status'] == 0){

            $this->db->set('status',1);
            $this->db->where(array('id'=>$id));
            $this->db->update('fin_land_list');

            $this->db->trans_start();
            $user_data = array('user_id'=>$res['user_id'],'obj_type'=>'日常领取','obj_id'=>$id,'fin_type'=>'add','land'=>$res['land'],'create_time'=>date("Y-m-d H:i:s"));
            $this->db->insert('user_fin_log',$user_data);
            $last_id = $this->db->insert_id();


            $user = get_cache_user($res['user_id']);

            $v_land = floatval($res['land'])+ floatval($user['fin']['land']);
            $this->db->set('lastupdate_time',date("Y-m-d H:i:s"));
            $this->db->set('land',$v_land);
            $this->db->where(array('user_id'=>$res['user_id']));
            $this->db->update('user_fin');

            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                update_user_cache($res['user_id'], array("fin"=>array('land'=>$v_land)));
                return $v_land;
            }
        }
        return false;
    }

    /**
     * 获取24小时内收集土地的用户
     */
    function get_collect_users($day){
        $this->db->select("*");
        $this->db->from('user_fin_log');
        $this->db->where(array('create_time >='=>date("Y-m-d H:i:s",strtotime("-{$day}days"))));
        $this->db->group_by("user_id");
        $res = $this->db->get()->result_array();
        write_log($this->db->last_query());
        return $res;
    }
}
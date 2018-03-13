<?php
/**
 * Created by PhpStorm.
 * User: sunyt
 * Date: 17/3/24
 */

class Warn_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    function get_list($where,$limit='',$offset='',$order='',$sort=''){
        $this->db->select("*");
        $this->db->from('warn u');
        $this->db->where($where);
        if($sort){
            $this->db->order_by($sort,$order);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }

        $list = $this->db->get()->result_array();

        $this->db->select("*");
        $this->db->from('warn u');
        $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }

    function get_user_list($where,$limit='',$offset='',$order='',$sort=''){
        $this->db->select("*");
        $this->db->from('warn_user u');
        $this->db->where($where);
        if($sort){
            $this->db->order_by($sort,$order);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }

        $list = $this->db->get()->result_array();

        $this->db->select("*");
        $this->db->from('warn_user u');
        $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }
    function get_group_list($where,$limit='',$offset='',$order='',$sort=''){
        $this->db->select("*");
        $this->db->from('warn_group u');
        $this->db->where($where);
        if($sort){
            $this->db->order_by($sort,$order);
        }
        if($limit){
            $this->db->limit($limit,$offset);
        }

        $list = $this->db->get()->result_array();

        $this->db->select("*");
        $this->db->from('warn_group u');
        $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        return $result;
    }
    function get_group_user($group_id){
        $where = array('group_id'=>$group_id);
        $this->db->select("g.group_id,g.user_id,u.name");
        $this->db->from('warn_user_group g');
        $this->db->join('warn_user u','u.id=g.user_id','left');
        $this->db->where($where);
        return $this->db->get()->result_array();
    }

    function get_info($id){
        $rs = $this->db->select("*")->from('warn')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }
    function get_user_info($id){
        $rs = $this->db->select("*")->from('warn_user')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }
    function get_group_info($id){
        $rs = $this->db->select("*")->from('warn_group')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }

    function save($data){
        if((int)$data['id']==0){
            $data['id'] = 0;
            $data['create_time'] = date('Y-m-d H:i:s');
            return $this->db->insert('cb_warn',$data);
        }
        else
            return $this->db->update('cb_warn',$data,array('id'=>$data['id']));
    }
    function save_user($data){
        if((int)$data['id']==0){
            $data['id'] = 0;
            $data['create_time'] = date('Y-m-d H:i:s');
            return $this->db->insert('cb_warn_user',$data);
        }
        else
            return $this->db->update('cb_warn_user',$data,array('id'=>$data['id']));
    }
    function save_group($data){
        if($data['update_status'] == 'update_status'){
            $data_group = array(
                'status'=>$data['status'],
            );
            $this->db->update('cb_warn_group',$data_group,array('id'=>$data['id']));
            return 1;
        }

        $data_group = array(
            'name'=>$data['name'],
            'device_type'=>$data['device_type'],
            'platform_id'=>$this->platform_id,
            'status'=>$data['status'],
        );
        if((int)$data['id']==0){
            $data_group['id'] = 0;
            $data_group['create_time'] = date('Y-m-d H:i:s');
            $id = $this->db->insert('cb_warn_group',$data_group);
        }else{
            $id = $data['id'];
            $this->db->update('cb_warn_group',$data_group,array('id'=>$data['id']));
        }
        if($id){
            $data_user = array();
            foreach ($data['user'] as $v) {
                $tmp = array(
                    'user_id' => $v,
                    'platform_id' => $this->platform_id,
                    'group_id' => $id,
                    'create_time' => date("Y-m-d H:i:s")
                );
                $data_user[] = $tmp;
            }
            $this->db->delete('cb_warn_user_group', array('group_id' => $id));
            if($data_user){
                $this->db->insert_batch('cb_warn_user_group',$data_user);
            }
            return 1;
        }
        return 0;
    }

}
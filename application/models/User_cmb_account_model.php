<?php
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_cmb_account_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'user_cmb_account';
    }
    
    
    public function create($data)
    {
        $this->db->insert($this->table_name(), $data);
    }
    
    public function get_item($where)
    {
        $this->db->select('*');
        $this->db->from($this->table_name());
        
        if( isset($where['user_id']) ){
            $this->db->where('user_id', $where['user_id']);
        }
        if( isset($where['agrt_id']) ){
            $this->db->where('agrt_id', $where['agrt_id']);
        }
        
        $query = $this->db->get();

        return $query->result_array();
    }
    

}
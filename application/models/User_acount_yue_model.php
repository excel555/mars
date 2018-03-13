<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/10/27
 * Time: 下午1:18
 */

class User_acount_yue_model extends MY_Model
{



    function __construct()
    {
        parent::__construct ();
    }

    public function table_name()
    {
        return 'user_acount_yue';
    }

    public function get_list($uid){
        $this->db->from($this->table_name());
        $this->db->order_by('id','DESC');
        $this->db->where(array('acount_id'=>$uid));
        return  $this->db->get()->result_array();
    }

}
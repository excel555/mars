<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/12
 * Time: 上午11:27
 * 首单活动
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Active_first_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'active_first';
    }

    /*
    * @desc 获取当前机器的首单优惠活动
    * @param $box_no string 盒子编码
    * @return array
    * */
    public function get_active($box_no){
        $where['box_no'] = $box_no;
        $this->db->where($where);
        $this->db->from($this->table_name());
        $this->db->order_by('id desc');
        $rs = $this->db->get();
        if($rs){
            $rs = $rs->row_array();
        }
        return $rs;
    }

}
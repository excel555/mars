<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/11
 * Time: 下午2:30
 * 记录异常日志
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Log_abnormal_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'log_abnormal';
    }

    /*
     * @desc 异常日志
     * @param $data array （
     *              'box_no'=>XXX //盒子编码
     *              'content'=>xxx //异常内容
     *              'uid'=>xxxx  //用户id
     *              'log_type'=> //日志类型   1：商品增多   2:开关门状态异常   3：支付不成功
     *                  ）
     * @param $content 异常内容
     * @return int log id
     * */

    function insert_log($data){
        //创建日志 加入平台
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($data['box_id']);
        $param['platform_id'] = $equipment_info['platform_id']?$equipment_info['platform_id']:1;

        $param['box_no'] = $data['box_id'];
        $param['content'] = $data['content']?$data['content']:'';
        $param['uid'] = $data['uid']?$data['uid']:0;
        $param['log_type'] = $data['log_type']?$data['log_type']:0;
        $param['addTime'] = date('Y-m-d H:i:s');
        return $this->insert($param);
    }


    public function get_box_info($id, $fild='*', $return=''){
        $this->db->select($fild);
        $this->db->from('equipment');
        $this->db->where('id', $id);
        $rs = $this->db->get()->row_array();
        if($return){
            return $rs[$return];
        }
        return $rs;
    }


}
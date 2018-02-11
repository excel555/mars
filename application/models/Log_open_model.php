<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/11
 * Time: 下午2:30
 * 记录开关门操作日志
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Log_open_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'log_open';
    }

    /*
     * @desc 开门日志
     * @param $uid  开门用户id
     * @param $box_no 开门机器编码
     * @param $refer str 来源： alipay/wechat/fruitday
     * @param $operation_id int 操作类型：1购买，2上下架
     * @return int log id
     * */

    function open_log($uid, $box_no, $refer='alipay', $operation_id=1){
        //创建日志 加入平台
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($box_no);
        $param['platform_id'] = $equipment_info['platform_id']?$equipment_info['platform_id']:1;

        $param['uid'] = $uid;
        $param['box_no'] = $box_no;
        $param['open_time']  = date('Y-m-d H:i:s');
        $param['close_time'] = '0000-00-00 00:00:00';
        $param['order_name'] = 0;
        $param['refer']  = $refer;
        $param['operation_id']  = $operation_id;
        return $this->insert($param);
    }

    /*
     * @desc 关门记录
     * @param $log_id int 开门记录id
     * @param $order_name string 订单
     * */
    function close_log($log_id, $order_name=0){
        $param['close_time'] = date('Y-m-d H:i:s');
        if($order_name){
            $param['order_name'] = $order_name;
        }
        return  $this->db->update($this->table_name(),$param, array('id'=>$log_id));
    }

    /*
     * @desc 绑定开关门 订单
     * @param $log_id int 开门记录id
     * @param $order_name string 订单
     * */

    public function update_log($log_id, $order_name){
        $param['order_name'] = $order_name;
        return  $this->db->update($this->table_name(),$param, array('id'=>$log_id));
    }


    function get_open_log($id){
        $where = array('id'=>$id);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }
}
<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User extends MY_Controller
{
    public $workgroup = 'user';

    function __construct() {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("equipment_model");
        $this->load->model('user_acount_model');
        $this->load->model("order_model");
    }

    public function user_list(){
        $this->page('user/user_list.html');
    }

    public function user_black_list(){
        $this->page('user/black_list.html');
    }
    public function user_black_list_table(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:10;
        $offset = $gets['offset']?$gets['offset']:0;
        $this->db->from('mobile_black');
        if($gets['mobile']){
           $this->db->like("mobile",$gets['mobile']);
        }
        $this->db->order_by('id desc');
        $this->db->limit($limit,$offset);
        $list = $this->db->get()->result_array();
        $this->db->select("id");
        $this->db->from('mobile_black');
        if($gets['mobile']){
            $this->db->like("mobile",$gets['mobile']);
        }
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }
    public function user_list_table(){
        $limit         = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset        = $this->input->get('offset')?$this->input->get('offset'):0;
        $mobile        = $this->input->get('search_mobile')?$this->input->get('search_mobile'):'';
        $user_name     = $this->input->get('search_user_name')?$this->input->get('search_user_name'):'';
        $source        = $this->input->get('search_source')?$this->input->get('search_source'):'';
        $search_device = $this->input->get('search_device')?$this->input->get('search_device'):'';
        $start_time    = $this->input->get('search_start_time')?$this->input->get('search_start_time'):'';
        $end_time      = $this->input->get('search_end_time')?$this->input->get('search_end_time'):'';
        $id            = $this->input->get('search_id')?$this->input->get('search_id'):'';
        $sort          = $this->input->get('sort')?'i.'.$this->input->get('sort'):'u.id';
        $order         = $this->input->get('order')?$this->input->get('order'):'asc';
        $box_param['name']    = $search_device;
        $search_box = array();
        if($box_param['name']){
            $search_box = $this->equipment_model->get_box_no($box_param, 'equipment_id');//盒子搜索
            if(empty($search_box)){
                $search_box = array('-1');
            }
        }
        $where = '';
        if($id){
            $where .= " and u.id={$id}";
        }
        if($mobile){
            $where .= " and u.mobile={$mobile}";
        }
        if($user_name){
            $where .= " and u.user_name like '%{$user_name}%'";
        }
        if($source){
            $where .= " and u.source='{$source}'";
        }
        $time_where = '';
        if($start_time){
            $time_where .= " and u.reg_time>='{$start_time}'";
        }
        if($end_time){
            $time_where .= " and u.reg_time<='{$end_time}'";
        }
        if(!empty($search_box)){
            $search_box = implode("','", $search_box);
            $where .= " and u.register_device_id in('{$search_box}')";
        }
        $sql = " SELECT u.is_black, u.id, u.mobile, u.user_name,u.city, u.reg_time,u.source, u.open_id, u.register_device_id, u.acount_id, i.buy_times, i.total_money, i.open_times from cb_user u LEFT JOIN cb_user_daily_info i ON u.id=i.uid WHERE  i.platform_id = {$this->platform_id} and u.id is not null {$where} {$time_where} ORDER BY {$sort} {$order} LIMIT {$offset}, {$limit}";
        $list = $this->db->query($sql)->result_array();

        $sql = " SELECT count(u.id) as total from cb_user u LEFT JOIN cb_user_daily_info i ON u.id=i.uid WHERE  i.platform_id = {$this->platform_id} and u.id is not null {$where} {$time_where}";
        $total = $this->db->query($sql)->row_array();

        $result = array(
            'total'  => $total['total'],
            'rows'  => $list
        );
        echo json_encode($result);exit;
    }


    function update_black(){
        $params = $this->input->post();
        $uid = $params['user_id'];
        $this->user_model->update_user($uid,array('is_black'=>1));
        echo "succ";
    }
    function insert_black_mobile(){
        $params = $this->input->post();
        $mobile = $params['mobile'];
        $this->user_model->insert_black_mobile($mobile);
        echo "succ";
    }
    function del_black_mobile(){
        $mobile = $this->input->post("mobile");
         $this->user_model->del_black_mobile($mobile);
        echo "succ";


    }
}
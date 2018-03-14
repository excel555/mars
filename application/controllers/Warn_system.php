<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Warn_system extends MY_Controller
{
    public $workgroup = 'warn';
    public $exception_arr;
    public $warn_method;
    public $warn_object;
    function __construct() {
        parent::__construct();
        $this->exception_arr = array(
            '1'=>'商品增多异常',
            '2'=>'开关门异常',
            '3'=>'支付失败',
            '4'=>'标签标签异常',
            '5'=>'零售机心跳异常',
            '6'=>'盘点差异巨大',
            '7'=>'消息重复推送',
            '8'=>'设备状态异常',
            '9'=>'不稳定标签',
        );
        $this->warn_method = array(
            'email'=>'邮件',
            'sms'=>'短信',
            'wechat'=>'微信',
        );
        $this->warn_object = array(
            'box_admin'=>'零售机负责人',
            'tech'=>'技术负责人',
        );
        $this->status = array(
            '0'=>'停用',
            '1'=>'启用',
        );
        $this->device_type = array(
            'rfid'=>'RFID',
            'scan'=>'扫码设备',
            'vision'=>'视觉设备',
        );
        $this->load->model("warn_model");
    }

    public function setting(){
        $this->page('warn_system/setting.html');
    }
    public function user(){
        $this->page('warn_system/user.html');
    }
    public function group(){
        $this->page('warn_system/group.html');
    }
    public function user_add(){
        $this->page('warn_system/add_user.html');
    }
    public function group_add(){
        $where = array('platform_id'=>$this->platform_id);
        $user_list = $this->warn_model->get_user_list($where,1000,0);
        $this->_pagedata['user_list'] = $user_list['rows'];
        $this->page('warn_system/add_group.html');
    }
    public function add(){
        $where = array('platform_id'=>$this->platform_id);
        $user_list = $this->warn_model->get_group_list($where,1000,0);
        $this->_pagedata['user_list'] = $user_list['rows'];
        $this->page('warn_system/add.html');
    }
    public function get_list(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $where = array('platform_id'=>$this->platform_id);
        $result = $this->warn_model->get_list($where,$limit,$offset,$order,$sort);

        $where1 = array('platform_id'=>$this->platform_id);
        $user_list = $this->warn_model->get_group_list($where1,1000,0);
        $groups = array();
        foreach ($user_list['rows'] as $u){
            $groups[$u['id']] = $u['name'];
        }

        foreach ($result['rows'] as &$v){
            $v['warn_type'] = $this->exception_arr[$v['warn_type']];
            $gs = explode(",",$v['group_ids']);
            $str  = "";
            foreach ($gs as $g){
                if($g) {
                    $str .= $groups[$g]."、";
                }
            }
            $v['warn_object_input'] = $str;//$this->warn_object[$v['warn_object']];
            $v['warn_method'] = $this->warn_method[$v['warn_method']];
            $v['status'] = $this->status[$v['status']];
        }
        echo json_encode($result);
    }
    public function get_user_list(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $where = array('platform_id'=>$this->platform_id);
        $result = $this->warn_model->get_user_list($where,$limit,$offset,$order,$sort);
        foreach ($result['rows'] as &$v){

            $v['status'] = $this->status[$v['status']];
        }
        echo json_encode($result);
    }

    public function get_group_list(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $where = array('platform_id'=>$this->platform_id);
        $result = $this->warn_model->get_group_list($where,$limit,$offset,$order,$sort);
        foreach ($result['rows'] as &$v){
            $v['device_type'] = $this->device_type[$v['device_type']];
            $v['status'] = $this->status[$v['status']];
            $user = $this->warn_model->get_group_user($v['id']);
            $v['user'] = "";
            foreach ($user as $u){
                $v['user'] .= $u['name'].",";
            }
        }
        echo json_encode($result);
    }
    public function edit(){
        $id = $this->uri->segment(3);
        $info = $this->warn_model->get_info($id);
        $this->_pagedata['warn'] = $info;

        $where = array('platform_id'=>$this->platform_id);
        $user_list = $this->warn_model->get_group_list($where,1000,0);

        $uids = explode(',',$info['group_ids']);
        foreach ($user_list['rows'] as &$u){
            if(in_array($u['id'],$uids)){
                $u['checked'] = 1;
            }
        }

        $this->_pagedata['user_list'] = $user_list['rows'];
        $this->page('warn_system/add.html');
    }
    public function edit_user(){
        $id = $this->uri->segment(3);
        $info = $this->warn_model->get_user_info($id);
        $this->_pagedata['warn'] = $info;
        $this->page('warn_system/add_user.html');
    }
    public function edit_group(){
        $id = $this->uri->segment(3);
        $info = $this->warn_model->get_group_info($id);
        $this->_pagedata['warn'] = $info;
        $where = array('platform_id'=>$this->platform_id);
        $user_list = $this->warn_model->get_user_list($where,1000,0);

        $user = $this->warn_model->get_group_user($id);
        $uids = array_column($user,'user_id');
        foreach ($user_list['rows'] as &$u){
            if(in_array($u['id'],$uids)){
                $u['checked'] = 1;
            }
        }
        $this->_pagedata['user_list'] = $user_list['rows'];
        $this->page('warn_system/add_group.html');
    }

    public function save()
    {
        $_POST['platform_id'] = $this->platform_id;
        $_POST['admin_id'] = $this->session->userdata('sess_admin_data')['adminid'];
        if($_POST['update_status'] != "update_status"){
            $_POST['group_ids'] = join(",",$_POST['group_ids']);
        }
        unset($_POST['update_status']);
        $rs = $this->warn_model->save($_POST);
        if($rs){
            redirect('/warn_system/setting');
        }
    }
    public function save_user()
    {
        $_POST['platform_id'] = $this->platform_id;
        $rs = $this->warn_model->save_user($_POST);
        if($rs){
            redirect('/warn_system/user');
        }else{
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该用户已经添加过！");</script></head>';
            redirect('/warn_system/user');
        }
    }
    public function save_group()
    {
        $rs = $this->warn_model->save_group($_POST);
        if($rs){
            redirect('/warn_system/group');
        }
    }

}
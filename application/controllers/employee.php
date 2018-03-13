<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Employee extends MY_Controller {

    public $workgroup = 'employee';


    function __construct() {
        parent::__construct();
        $this->load->model("admin_model");
        $this->load->model("admin_equipment_model");
//        $this->load->model('products/product_price_model');
//        $this->load->model('product_model');
    }


    function adduser() {
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        $this->title = '新增店员';
        $this->_pagedata["tips"] = "";
        $this->_pagedata["groupList"] = $this->admin_model->getGroupList();

        if ($this->input->post("submit")) {
            $name = trim($this->input->post("name"));
            $alias = trim($this->input->post("alias"));
            $mobile = trim($this->input->post("mobile"));
            $id_card = $this->input->post("id_card");
            $email = trim($this->input->post("email"));
            $pwd = trim($this->input->post("pwd"));
            $pwdConfim = trim($this->input->post("pwdconfirm"));
            $group = $this->input->post("group");
            $stores = $this->input->post("store");
            $funcs = $this->input->post("func");
            $box_no = $this->input->post("box_no");


            if ($pwdConfim != $pwd) {
                $this->_pagedata ["tips"] = "两次密码输入不一致";
            } else if (trim($name) == "" || trim($pwd) == "" || trim($pwdConfim) == "" || empty($group)) {
                $this->_pagedata ["tips"] = "填写不完整";
            } else {
                $admin_id = $this->admin_model->insertAdmin($name, $pwd, $alias, $mobile, $id_card, $email);
                if ($admin_id > 0) {
                    if (!empty($stores)) {
                        $this->admin_model->insertAdminStore($admin_id, $stores);
                    }
                    if (!empty($funcs)) {
                        $this->admin_model->insertAdminFunc($admin_id, $funcs);
                    }
                    if (!empty($group)) {
                        $this->admin_model->insertAdminGroup($admin_id, $group);
                    }
                    //插入管理的设备列表
                    $this->db->delete('admin_equipment', array('admin_id' => $admin_id));
                    if (!empty($box_no)){
                        $data = array();
                        foreach($box_no as $box_id){
                            $data[] = array(
                                'admin_id'=>$admin_id,
                                'equipment_id'=>$box_id
                            );
                        }
                        $this->db->insert_batch('admin_equipment',$data);
                    }
                    
                    $this->_pagedata["tips"] = "新增成功";
                } else {
                    $this->_pagedata["tips"] = "用户名已存在";
                }
            }
        }
        
        $this->load->model('equipment_admin_model');
        $equipment_list = $this->equipment_admin_model->get_equipments();
        $this->_pagedata['equipment_list'] = $equipment_list;

        $this->page('admin/adduser.html');
    }


function getuserlist() {
        //get search condition
        $curr_uid = $this->session->userdata('sess_admin_data')["adminid"];
        //$curr_user = $this->admin_model->getUser($curr_uid);
        $curr_user = $this->admin_model->getUserb($curr_uid);
        $group_id = array();
        foreach ($curr_user as $key => $value) {
            $group_id[] = $value['group_id'];
        }

        $search = $_POST;
        $search_group_id = -1;
        $search_is_lock = -1;
        $search_lock_limit = -1;
        $is_open = 0;

        //if($curr_uid ==1 || $curr_user->groupid==1){
        $is_open = 1;
        //$search_group_id = 6;
        if (isset($search['search_group_id'])) {
            if ($search['search_group_id'] !== '-1') {
                $search_group_id = $search['search_group_id'];
            }
        } else {
            $search['search_group_id'] = "-1";
        }

        if (isset($search['search_lock_limit'])) {
            if ($search['search_lock_limit'] !== '-1') {
                $search_lock_limit = $search['search_lock_limit'];
            }
        } else {
            $search['search_lock_limit'] = "-1";
        }

        if (isset($search['search_is_lock'])) {
            if ($search['search_is_lock'] !== '-1') {
                $search_is_lock = $search['search_is_lock'];
            }
        } else {
            $search['search_is_lock'] = "-1";
        }

        //$name = trim($search['name'])?trim($search['name']):'';

        if (isset($search['name'])) {
            $name = trim($search['name']);
        } else {
            $search['name'] = '';
        }
        
        if (isset($search['alias'])) {
            $alias = trim($search['alias']);
        } else {
            $search['alias'] = '';
        }
        
        if (isset($search['mobile'])) {
            $mobile = trim($search['mobile']);
        } else {
            $search['mobile'] = '';
        }

        // if($search_group_id==6){
        //  $search['search_group_id'] = 6;
        // }
//        $this->title = '用户';
        $this->_pagedata['search'] = $search;
        $this->_pagedata['is_open'] = $is_open;
        $this->_pagedata['grouplist'] = $this->admin_model->getGroupList();
        $this->_pagedata ["list"] = $this->admin_model->getUserList($search_group_id, $search_is_lock, $search_lock_limit,$name,$alias,$mobile);
        $this->page('admin/listuser.html');


    }


}

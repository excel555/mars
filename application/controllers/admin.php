<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Admin extends MY_Controller {

    public $workgroup = 'employee';

    const LOCK_LIMIT_MAX = 5;
    const RESET_PWD = "123456##!";
    const SERVER_USER_PWD = "123456##_fday2015";
    private $com_redis_pre = 'comercial_';
    public $redis;

    private $stockNum = 20; //小于50显示库存警告

    function __construct() {
        parent::__construct();
        $this->load->model("admin_model");
        $this->load->model("admin_equipment_model");
    }

    function index() {
        $this->title = '';
        $this->workgroup  = '';
        $sql = " SELECT count(u.id) as total from cb_user u   ";
        $total = $this->db->query($sql)->row_array();

        $sql = " SELECT count(u.id) as total from cb_order u  WHERE  u.platform_id = {$this->platform_id}  ";
        $total_order = $this->db->query($sql)->row_array();

        $sql = " SELECT count(u.id) as total from cb_product u  WHERE  u.platform_id = {$this->platform_id} and u.status=1  ";
        $total_product = $this->db->query($sql)->row_array();

        $sql = " SELECT count(u.id) as total from cb_equipment u  WHERE  u.platform_id = {$this->platform_id} and u.status !=99 ";
        $total_eq = $this->db->query($sql)->row_array();

        $this->_pagedata["data"] = array(
            'user_count'=>$total['total'],
            'device_count'=>$total_eq['total'],
            'order_count'=>$total_order['total'],
            'product_count'=>$total_product['total'],
        );
        $this->page('admin/index.html');

    }

    /**
     * 获取商品配送地址
     */
    private function getProductRegion($productRegionArray = array())
    {
        if (empty($productRegionArray)) {
            return array();
        }

        $this->load->model('region_model');
        // 获取一级地域列表
        $regions = $this->region_model->getSonRegions(0);
        $regioinPairs = array_column($regions, 'name', 'id');

        return array_intersect_key($regioinPairs, array_flip($productRegionArray));
    }

    public function export()
    {
        $array  = $this->getWarningProducts();

        foreach ($array as &$value) {
            $regionTemp = $this->getProductRegion(unserialize($value['send_region']));
            $value['send_region'] = implode(',', $regionTemp);
        }

        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $filename = date('Y-m-d');
        $exceler->setFileName('库存报警' . $filename . '.csv');
        $excel_title = ["商品ID","商品名称","库存","价格","售卖区域",];
        $exceler->setTitle($excel_title);
        $exceler->setContent($array);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
    }

    /**
     * 获得库存警告的商品列表。
     * @param  integer $offset 偏移量
     * @param  integer $limit  数据量
     * @return array
     */
    private function getWarningProducts($field = "",$offset = 0, $limit = 0)
    {
        $now_time = date('Y-m-d H:i:s',time());
        $sql_fields = $field ? : "b.product_id, a.product_name, b.stock, b.price, a.send_region";

        $sql = "SELECT {$sql_fields}
            FROM ttgy_product AS a
            LEFT JOIN ttgy_product_price AS b
            ON a.id = b.product_id
            WHERE (
                a.use_store = 1
                AND a.xsh = 0
                AND b.stock IS NOT NULL
                AND b.stock < {$this->stockNum}
                AND (a.online = 1 OR a.mobile_online OR a.app_online = 1)
                ) OR (
                a.use_store = 1
                AND a.xsh = 1
                AND b.start_time < '{$now_time}'
                AND b.over_time > '{$now_time}'
                AND b.stock IS NOT NULL
                AND b.stock < {$this->stockNum}
                AND (a.online = 1 OR a.mobile_online OR a.app_online = 1)
                )
            ORDER BY b.stock ASC, b.product_id ASC";

        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }

        $res = $this->db->query($sql);
        $array = $res->result_array();

        return $array;
    }

    public function table()
    {
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;

        $array = $this->getWarningProducts("", $offset, $limit);

        foreach ($array as &$value) {
            $regionTemp = $this->getProductRegion(unserialize($value['send_region']));
            $value['send_region'] = implode('<br />', $regionTemp);
            $product_id = $value['product_id'];
            $value['product_url'] = "<a href=\"/products/productList/add_edit/{$product_id}\">{$product_id}</a>";
        }

        $total = (int)$this->getWarningProducts("count(*) as c")[0]['c'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    function changepwd() {
//        $this->title = '修改密码';
        $this->_pagedata ["tips"] = "";

        if ($this->input->post("submit")) {
            $old = $this->input->post("old");
            $new = $this->input->post("new");
            $newConfim = $this->input->post("newconfirm");
            $id = $this->session->userdata('sess_admin_data')["adminid"];
            if ($new != $newConfim) {
                $this->_pagedata["tips"] = "新密码确认失败";
            } else if (trim($old) == "" || trim($new) == "" || trim($newConfim) == "") {
                $this->_pagedata["tips"] = "填写不完整";
            } else {
                $tips = get_pwd_strength($new);
                if (!empty($tips)) {
                    $this->_pagedata["tips"] = $tips;
                } else {
                    if ($this->admin_model->changePwd($id, $old, $new) != 0) {
                        $this->_pagedata["tips"] = "原密码错误";
                    } else {
                        $this->_pagedata["tips"] = "更新成功";
                    }
                }
            }
        }
        $this->page('admin/changepwd.html');
    }

    function login() {
        $name = trim(addslashes($this->input->post("name")));
        $pwd = addslashes($this->input->post("pwd"));
        $data['tips'] = "";
        if ($this->input->post("submit")) {
//            echo $name;
//            echo $pwd;
            if ($name && $pwd) {
                $admin = $this->admin_model->getAdmin($name);

                if ($admin) {
                    if ($admin['lock_limit'] >= self::LOCK_LIMIT_MAX) {
                        $data['tips'] = '账户被冻结，请联系管理员！';
                        $this->load->view('admin/login', $data);
                    } else {

                        if ($admin['pwd'] == md5($pwd)) {
                            $this->admin_model->updateLock($admin['id'], 0);
                            $sess_admin_data = array(
                                'adminid' => $admin['id'],
                                'adminname' => $admin['name'],
                                'adminalias' => empty($admin['alias']) ? $admin['name'] : $admin['alias'],
                                'adminflag' => explode(",", $admin['flag']),
                                'adminTimestamp' => time(),
                                'adminfirst'=> $admin['is_first'],
                                'adminLevel'=> $admin['level'],
                                'adminPlatformId'=> $admin['platform_id'],
                            );

                            $this->session->set_userdata('sess_admin_data', $sess_admin_data);


                            $requestIP = $this->input->ip_address();

                            $this->admin_model->insertLogin($admin['id'], $requestIP);
                            $this->admin_model->updateLoginTime($admin['id']);

                            redirect("admin/index");

                        } else {
                            $lock_limit = $admin['lock_limit'] + 1;
                            $lock_limit = $lock_limit >= 5 ? 5 : $lock_limit;
                            $this->admin_model->updateLock($admin['id'], $lock_limit);
                            $data['tips'] = '输入用户名或密码有误！';
                            $this->load->view('admin/login', $data);
                        }
                    }
                } else {
                    $data['tips'] = '输入用户名或密码有误！';
                    $this->load->view('admin/login', $data);
                }
            } else {
                $data['tips'] = '请输入用户名或密码！';
                $this->load->view('admin/login', $data);
            }
        } else {
            $this->load->view('admin/login', $data);
        }
    }

    function upuser() {
        $id = $this->uri->segment(3);
        $this->currwork = 'admin/getuserlist';
//        $this->title = '用户';
        $this->_pagedata["tips"] = "";
        $this->_pagedata["groupList"] = $this->admin_model->getGroupList();
        if ($this->input->post("submit")) {
            $alias = $this->input->post("alias");
            $groupid = $this->input->post("group");
            $id = $this->input->post("id");
            $stores = $this->input->post("store");
            $funcs = $this->input->post("func");
            $is_first = $this->input->post('is_first');
            $mobile = $this->input->post('mobile');
            $wx_open_id = $this->input->post('wechat_open_id');
            $id_card = $this->input->post('id_card');
            $email = $this->input->post('email');
            $box_no = $this->input->post('box_no');
            $res = $this->admin_model->upUser($id, $alias, $is_first, $mobile, $id_card, $email,$wx_open_id);
            if ($res) {
                if (!empty($groupid)) {
                    $this->admin_model->delAdminGroup($id);
                    $this->admin_model->inseerAdminGroup($id,$groupid);
                } else {
                    $this->admin_model->delAdminGroup($id);
                }

                if (!empty($funcs)) {
                    $this->admin_model->updateAdminFunc($id, $funcs);
                } else {
                    $this->admin_model->delAdminFunc($id);
                }
                //插入管理的设备列表
                $this->db->delete('admin_equipment', array('admin_id' => $id));
                if (!empty($box_no)){
                    $data = array();
                    foreach($box_no as $box_id){
                        $data[] = array(
                            'admin_id'=>$id,
                            'equipment_id'=>$box_id
                        );
                    }
                    $this->db->insert_batch('admin_equipment',$data);
                }
                $this->_pagedata ["tips"] = "更新成功";
            }
        }
        $this->load->model('equipment_admin_model');
        $store_list = $this->equipment_admin_model->get_store_list();

        $code = array();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_admin_model->get_equipments();
        //已有权限的设备列表
        $boxes = $this->admin_equipment_model->getList($id);
        foreach($equipment_list as $k=>$v){
            $equipment_list[$k]['checked'] = in_array($v['equipment_id'], $boxes)?1:0;
        }
        
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->_pagedata ["id"] = $id;
        $this->_pagedata ["item"] = $this->admin_model->getUser($id);
        $this->_pagedata ["groups"] = $this->admin_model->getGroups($id);
        $this->page('admin/upuser.html');
    }

    function addserveruser(){
        $this->_pagedata["tips"] = "";

        if ($this->input->post("submit")) {
            $name = trim($this->input->post("name"));
            $names = explode(',', $name);
            if(empty($name)){
                $this->_pagedata["tips"] = "用户名不能为空";
            }else{
                $res = $this->admin_model->insServerUser($names,self::SERVER_USER_PWD);
                if(!$res){
                    $this->_pagedata["tips"] = "请修改重复名称";
                }else{
                    $this->admin_model->insGuanUser($names,self::SERVER_USER_PWD);
                    $this->_pagedata["tips"] = "新增成功";
                }
            }
        }
        $this->page('admin/addserveruser.html');
    }

    function adduser() {
//        $this->title = '新增用户';
        $this->_pagedata["tips"] = "";
        $this->_pagedata["groupList"] = $this->admin_model->getGroupList();

        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            $alias = $this->input->post("alias");
            $mobile = $this->input->post("mobile");
            $id_card = $this->input->post("id_card");
            $email = $this->input->post("email");
            $pwd = $this->input->post("pwd");
            $pwdConfim = $this->input->post("pwdconfirm");
            $group = $this->input->post("group");
            $stores = $this->input->post("store");
            $funcs = $this->input->post("func");


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
                    $this->_pagedata["tips"] = "新增成功";
                } else {
                    $this->_pagedata["tips"] = "用户名已存在";
                }
            }
        }

        $this->page('admin/adduser.html');
    }

    function addgroup() {
        $this->currwork = 'admin/getgrouplist';
//        $this->title = '分组';
        $this->_pagedata["tips"] = "";

        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            if ($this->admin_model->insertGroup($name) != 0) {
                $this->_pagedata["tips"] = "用户名已存在";
            } else {
                $this->_pagedata["tips"] = "新增成功";
            }
        }

        $this->page('admin/addgroup.html');
    }

    function addfunc() {
//        $this->title = '功能';
        $this->_pagedata["tips"] = "";

        if ($this->input->post("submit")) {
            $name = $this->input->post("name");
            if ($this->admin_model->insertFunc($name) != 0) {
                $this->splash('error', '添加失败');
            } else {
                $this->splash('succ', '添加成功');
            }
        }

        $this->display('admin/addfunc.html');
    }

    function deluser() {
        if ($this->input->get("aid")) {
            $this->admin_model->delAdmin($this->input->get("aid"));
        }
        redirect("/admin/getuserlist");
    }

    function delgroup() {
        if ($this->input->get("gid")) {
            $this->admin_model->delGroup($this->input->get("gid"));
        }
        redirect("/admin/getgrouplist");
    }

    function delfunc() {
        if ($this->input->get("id")) {
            $this->admin_model->delFunc($this->input->get("id"));
        }
        redirect("/admin/getfunclist");
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
        if($curr_uid ==1 || in_array(1, $group_id)){
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
            $this->title = '用户';
            $this->_pagedata['search'] = $search;
            $this->_pagedata['is_open'] = $is_open;
            $this->_pagedata['grouplist'] = $this->admin_model->getGroupList();
            $this->_pagedata ["list"] = $this->admin_model->getUserList($search_group_id, $search_is_lock, $search_lock_limit,$name,$alias,$mobile);
            $this->page('admin/listuser.html');
        }else{
            //$search_group_id = $curr_user->groupid;
            //$search_group_id = $group_id;
            $this->title = '用户';
            //$this->_pagedata['search'] = $search;
            $this->_pagedata['is_open'] = $is_open;
            //$this->_pagedata['grouplist'] = $this->admin_model->getGroupList();
            $this->_pagedata ["list"] = $this->admin_model->getUserListb($group_id);
            $this->page('admin/listuser.html');
        }


    }

    function ajax_user() {
        $ids = $_POST['id'];
        if (empty($ids)) {
            return false;
        }
        $id = implode(',', $ids);

        $data['key'] = $_POST['key'];
        $data['val'] = $_POST['val'];

        $res = $this->admin_model->update($id, $data);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function getgrouplist() {
//        $this->title = '分组';
        $this->_pagedata ["list"] = $this->admin_model->getGroupList();
        $this->page('admin/listgroup.html');
    }

    function getfunclist() {
        $this->title = '功能';
        $this->_pagedata ["list"] = $this->admin_model->getFuncList();
        $this->page('admin/listfunc.html');
    }

    function getpermission() {
        $this->currwork = 'admin/getgrouplist';
//        $this->title = '分组';
        $this->_pagedata["tips"] = "";

        $gid = 0;
        if ($this->input->get("gid")) {
            $gid = $this->input->get("gid");

            if ($this->input->post("submit")) {
                $flags = "";
                if ($this->input->post("check")) {
                    $flags = implode(",", $this->input->post("check"));
                }
                $this->admin_model->updateFlag($flags, $gid);
                $this->_pagedata["tips"] = "更新成功";
            }

            $modules = $this->function_class->getModulesXml("ModulesList");
            $options = $this->function_class->getModulesXml("OptionList");
            $modulesArr = "";
            $level = $this->session->userdata('sess_admin_data')["adminLevel"];

            foreach ($modules as $module) {
                $moduleArr = array();
                $moduleArr['nodeName'] = $module->nodeValue;
                foreach ($options as $option) {
                    $type = $option->getAttribute("type");
                    if($level==1){//超级管理员
                        if ($type == $module->getAttribute("value")) {
                            $value = $option->getAttribute("value");
                            $name = $option->nodeValue;
                            $moduleArr['nodeValue'][] = array(
                                'name' => $name,
                                'value' => $value
                            );
                        }
                    }else{//平台管理员、普通账号
                        if ($type == $module->getAttribute("value")) {
                            $value = $option->getAttribute("value");
                            $name = $option->nodeValue;

                            if(strpos($this->function_class->level_2_group,$value.","))
                            {
                                $moduleArr['nodeValue'][] = array(
                                    'name' => $name,
                                    'value' => $value
                                );
                            }
                        }
                    }
                }
//                if(count())
                if(count($moduleArr['nodeValue']))
                    $modulesArr[] = $moduleArr;
            }
//            var_dump($modulesArr);exit;
            $this->_pagedata ["modulesArr"] = $modulesArr;
            $this->_pagedata ["gid"] = $gid;
            $this->_pagedata ["flag"] = $this->admin_model->getFlag($gid)->flag;
        } else {
            redirect("/admin/index");
        }

        $this->page('admin/permission.html');
    }

    function ajaxHandleGroup() {
        $ajaxReturn = array('code' => 0, 'msg' => '');
        $id = $this->input->post("id");
        if (empty($id)) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $ids = is_array($id) ? $id : array($id);
        $data['gid'] = $this->input->post("gid");
        if ($data['gid'] <= 0) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $res = $this->admin_model->updateAdminGroup($ids, $data['gid']);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function ajaxResetPwd() {
        $ajaxReturn = array('code' => 0, 'msg' => '');
        $id = (int) $this->input->post("id");
        if (empty($id)) {
            $ajaxReturn['msg'] = "非法请求!";
            echo json_encode($ajaxReturn);
            exit;
        }
        $res = $this->admin_model->updateAdminPwd($id, self::RESET_PWD);
        $ajaxReturn['code'] = !$res ? 0 : 1;
        $ajaxReturn['msg'] = !$res ? "更新失败，请重新尝试" : "更新成功";
        echo json_encode($ajaxReturn);
        exit;
    }

    function logout() {
        session_destroy();
        $this->session->sess_destroy();
        $this->load->view('admin/login');
    }

}

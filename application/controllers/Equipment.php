<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Equipment extends MY_Controller
{

    public $workgroup = 'equipment';

//    public $img_http = base_url();

    function __construct()
    {
        parent::__construct();
        $this->load->model("equipment_admin_model");
        $this->load->model("equipment_label_model");
        $this->load->model("label_product_model");
        $this->load->model("region_model");
        $this->load->model("product_admin_model");
    }

    function index()
    {
        $this->title = '设备信息管理';
        $this->_pagedata ["list"] = $this->equipment_admin_model->getList();
        $this->page('equipment/index.html');
    }

    public function table()
    {
        $limit = $this->input->get('limit') ?: 10;
        $offset = $this->input->get('offset') ?: 0;
        $search_name = $this->input->get('search_name') ?: '';
        $search_code = $this->input->get('search_code') ?: '';
        $search_heart_status = $this->input->get('search_heart_status') ?: '';
        $sort = $this->input->get('sort') ?: '';
        $order = $this->input->get('order') ?: '';
        if ($this->input->get('search_status') === '0') {
            $search_status = 0;
        } else {
            $search_status = $this->input->get('search_status') ?: '';
        }
        $search_equipment_id = $this->input->get('search_equipment_id') ?: '';
        $where = array();
        if ($search_name) {
            $where['name'] = $search_name;
        }
        if ($search_code) {
            $where['code'] = $search_code;
        }

        if ($search_status || $search_status === 0) {
            $where['status'] = $search_status;
        }
        if ($search_equipment_id) {
            $where['equipment_id'] = $search_equipment_id;
        }


        $array = $this->equipment_admin_model->get_list($where, $limit, $offset, false, $sort, $order);

        echo json_encode($array);
    }

    public function add()
    {
        $this->page('equipment/add.html');
    }

    public function add_save()
    {
        $equipment_id = $_POST['equipment_id'];
        $equipment = $this->equipment_admin_model->findByBoxId($equipment_id);
        if ($equipment) {
            $this->_pagedata["tips"] = "该盒子id已存在！";
            $this->page('equipment/add.html');
        } else {
            $name = $_POST['name'];
            $code = $_POST['code'];
            //查看盒子code是否存在
            $codeEquipment = $this->equipment_admin_model->findByBoxCode($code);
            if ($codeEquipment) {
                $this->_pagedata["tips"] = "该盒子code已存在！";
                $this->page('equipment/add.html');
                exit;
            }
            $address = $_POST['address'];

            $type = $_POST['type'];
            $province = $_POST['province'];
            $city = $_POST['city'];
            $area = $_POST['area'];

            $serial_num = $equipment_id;
            $data = array();
            $data['name'] = $name;
            $data['code'] = $code;
            $data['serial_num'] = $serial_num;
            $data['equipment_id'] = $equipment_id;
            $data['status'] = 1;
            $data['merchantid'] = 1;
            $data['created_time'] = time();
            $data['address'] = $address;

            $data['type'] = $type;
            $data['province'] = $province;
            $data['city'] = $city;
            $data['area'] = $area;
            $data['qr'] = $this->qr_code($equipment_id);
            $insertBox = $this->equipment_admin_model->insertData($data);
            if ($insertBox) {
                redirect('/equipment/index');
            }
        }
    }
    public function qr_code($equipment_id){
        if ($this->input->is_ajax_request()) {
            $equipment_id = $_POST['equipment_id'];
        }
        $img_p ="";
        if($equipment_id){
            //生成二维码统一用这个
            $qr_url = get_device_qr_common_pr($equipment_id);
            $qr_url = str_replace('DEVICEID',$equipment_id,$qr_url);//替换关键字
            $img = general_qr_code($qr_url);
            $img_p = base_url().'uploads/'.$img;
        }
        if ($this->input->is_ajax_request()) {
            $this->showJson(array('status' => 'success', 'qrcode' => $img_p));
        }
        else{
            return $img_p;
        }
    }

    public function edit()
    {
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "SELECT * FROM cb_equipment WHERE id=$id and platform_id = $this->platform_id";
        $info = $this->db->query($sql)->row_array();
        if (empty($info))
            redirect('/equipment/index');

        $this->_pagedata['id'] = $id;
        $this->_pagedata['info'] = $info;
        $this->page('equipment/edit.html');
    }

    //编辑保存
    public function edit_save()
    {
        $id = $_POST['id'];


        $code = $_POST['code'];
        //查看盒子code是否存在
        $codeEquipment = $this->equipment_admin_model->findByBoxCode($code, $id);
        if ($codeEquipment) {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该设备code已存在！");location.href = "/equipment/edit/' . $id . '";</script></head>';
            exit;
        }
        $equipment_id = $_POST['equipment_id'];


        $sql = "SELECT * FROM cb_equipment WHERE id=$id AND platform_id=$this->platform_id";
        $info = $this->db->query($sql)->row_array();
        if (empty($info))
            redirect('/equipment/index');

        $res = $this->db->update('equipment', $_POST, array('id' => $id));

        if ($res) {
            redirect('/equipment/index');
        } else {
            redirect('/equipment/edit/' . $id);
        }

    }

    //启用
    public function start()
    {
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "UPDATE cb_equipment SET status=1 WHERE id=$id and platform_id = $this->platform_id";
        $res = $this->db->query($sql);
        $equipment = $this->equipment_admin_model->findById($id);
        if ($equipment) {
            $equipment_id = $equipment['equipment_id'];
            $this->db->update('equipment', array('status' => 1), array('equipment_id' => $equipment_id));
        }


        redirect('/equipment/index');
    }

    //停用
    public function stop()
    {
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "UPDATE cb_equipment SET status=0 WHERE id=$id and platform_id = $this->platform_id";
        $res = $this->db->query($sql);
        $equipment = $this->equipment_admin_model->findById($id);
        if ($equipment) {
            $equipment_id = $equipment['equipment_id'];
            $this->db->update('equipment', array('status' => 0), array('equipment_id' => $equipment_id));
        }
        redirect('/equipment/index');
    }



    public function stock($equipment_id)
    {

        if ($equipment_id) {
            $labels = $this->equipment_label_model->get_labels_by_box_id($equipment_id, 'active');
            $labels = array_column($labels, 'label');

            $labels1 = $this->label_product_model->get_product_by_labels($labels);
            $count_all = 0;
            foreach ($labels1 as $v) {
                $ret[$v['product_id']]['price'] = $v['price'];
                $ret[$v['product_id']]['product_id'] = $v['product_id'];
                if (isset($ret[$v['product_id']]['qty'])) {
                    $ret[$v['product_id']]['qty'] += 1;
                } else {
                    $ret[$v['product_id']]['qty'] = 1;
                }
                $ret[$v['product_id']]['label'] = $v['label'];
                $ret[$v['product_id']]['product_name'] = $v['product_name'];
                $count_all += $ret[$v['product_id']]['qty'];
            }

            $this->_pagedata['count_all'] = $count_all;
            $this->_pagedata['stock_data'] = $ret;
            $this->page('equipment/stock.html');
        } else {
            redirect('/equipment/index');
        }
    }

    public function ordertime()
    {
        $sql = "UPDATE cb_equipment t1 SET firstordertime = (SELECT UNIX_TIMESTAMP(MIN(order_time)) FROM cb_order WHERE box_no = t1.equipment_id) WHERE (firstordertime = '' or firstordertime is null);";
        $this->db->query($sql);
        redirect('/equipment/index');
    }

}

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


    public function qr_code_fruitday()
    {
        if ($this->input->is_ajax_request()) {
            $refer = $_POST['refer'];
            $equipment_id = $_POST['equipment_id'];
            $params = array(
                'box_id' => $equipment_id,
                'refer' => $refer
            );
            //        var_dump($params);
            $sign = $this->create_sign_cbapi($params);
            //        echo $sign.'<br>';

            $headers = array("sign:$sign", "platform:admin");


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
            //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
            //增加来源 alipay/fruitday
//            $refer = 'fruitday';
            curl_setopt($ch, CURLOPT_URL, CITY_BOX_API . '/api/public_tool/create_qr_code?box_id=' . $equipment_id . '&refer=' . $refer);
            //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $result = json_decode($result);
            if ($result->qr_img) {
                $fruitday_qr_sql = "select * from cb_equipment_qr where refer='{$refer}' and equipment_id= '" . $equipment_id . "'";
                $fruitday_qr = $this->db->query($fruitday_qr_sql)->row_array();
                if ($fruitday_qr) {
                    $sql = "UPDATE cb_equipment_qr SET qr='" . $result->qr_img . "',update_time = '" . time() . "' WHERE id=" . $fruitday_qr['id'] . "";
                } else {
                    $sql = "INSERT INTO cb_equipment_qr (equipment_id,refer,qr,update_time) VALUES ('" . $equipment_id . "','{$refer}','" . $result->qr_img . "','" . time() . "')";
                }
                //更新db
                $res = $this->db->query($sql);
                $p_db = $this->load->database('platform_master', TRUE);
                if ($refer == 'fruitday') {
                    $p_db->update('equipment', array('qr_fruitday' => $result->qr_img), array('equipment_id' => $equipment_id));
                } elseif ($refer == 'common') {
                    $p_db->update('equipment', array('qr_common' => $result->qr_img), array('equipment_id' => $equipment_id));
                }
                $this->showJson(array('status' => 'success', 'qrcode' => $result->qr_img));
            } else {
                $this->showJson(array('status' => 'error', 'msg' => '获取失败！'));
            }

        }
        $this->showJson(array('status' => 'error', 'msg' => '获取失败！'));

    }

    public function updateFromAddress()
    {
        if ($this->input->is_ajax_request()) {
            $param['address'] = $this->input->post('address') ? $this->input->post('address') : '';
            if (!$param['address']) {
                $this->showJson(array('status' => 'error'));
            }
            $time = time();
            $service = 'deliver.getActiveTmsCode';
            $params = array(
                'timestamp' => $time,
                'service' => $service,
                'address' => $param['address'],
                'source' => 'app',
                'version' => '9.9.9'
            );
            $params['sign'] = $this->create_sign_nirvana2($params);
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            curl_setopt($ch, CURLOPT_URL, 'http://nirvana2.fruitday.com/api');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            $result = json_decode($result);

            $this->showJson(array('status' => 'success', 'tmscode' => $result->tmscode, 'lonlat' => $result->lonlat, 'storename' => $result->storename));

        }
        $this->showJson(array('status' => 'error'));
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

    public function pandian($equipment_id)
    {
        $equipment = $this->equipment_admin_model->findById($equipment_id);
        $boxId = $equipment['equipment_id'];
        if ($boxId) {
            $params = array(
                'box_id' => $boxId
            );
            //        var_dump($params);
            $sign = $this->create_sign_cbapi($params);
            //        echo $sign.'<br>';

            $headers = array("sign:$sign", "platform:admin");


            $ch = curl_init();
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            //curl_setopt($ch, CURLOPT_URL, 'http://cbapi1.fruitday.com/api/permission/update_shipping_permission');
            //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/public_tool/create_qr_code?box_id=68805328909');
            curl_setopt($ch, CURLOPT_URL, CITY_BOX_API . '/api/device/stock?box_id=' . $boxId);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $result = curl_exec($ch);
            curl_close($ch);
            $result1 = json_decode(json_decode($result));
            if ($result1->state) {
                $state = $result1->state;
                $tips = $state->tips;
            } else {
                $tips = json_decode($result)->message ? json_decode($result)->message : '发送盘点指令失败';
            }
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("' . $tips . '");location.href = "/equipment/index";</script></head>';
        } else {
            redirect('/equipment/index');
        }
    }


    public function create_sign_v2($params)
    {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
        return $sign;
    }

    public function create_sign_nirvana2($params)
    {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_nirvana2), 0, -1) . 'w');
        return $sign;
    }

    public function create_sign_cbapi($params)
    {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_cityboxapi), 0, -1) . 'w');
        return $sign;
    }

    public function test()
    {
        var_dump(CITY_BOX_API);
        exit;
        $time = '2016-09-01 23:23:23';
        $test = date('Y-m-d 00:00:00', strtotime($time));
        echo $test;
        exit;

        $last_hour = date("Y-m-d H:i:s", strtotime("-1 hour"));
        $is_set_expire = $this->db->select('id')->from("equipment_label")->where("expire_time <>", '')->get()->result_array();
        //第一次计算所有标签的保质期
        if (!$is_set_expire) {
            $sql = "UPDATE cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id SET expire_time = DATE_ADD(t1.created_time,INTERVAL CONVERT(t3.preservation_time,SIGNED) DAY) WHERE t1.status = 'active' AND t3.preservation_time > 0 AND (expire_time  = '' OR expire_time IS NULL)";
            $res = $this->db->query($sql);
        } else {   //计算一个小时内上架的商品保质期
            $sql = "SELECT t1.*,t3.preservation_time FROM cb_equipment_label t1 LEFT JOIN cb_label_product t2 ON t2.label = t1.label LEFT JOIN cb_product t3 ON t3.id = t2.product_id WHERE t1.STATUS='active' AND t1.created_time > '" . $last_hour . "' AND t3.preservation_time > 0 ";
            $result = $this->db->query($sql)->result_array();
            foreach ($result as $row) {
                $label = $row['label'];
                $preservation_time = $row['preservation_time'];
                $id = $row['id'];
                //获取之前就存在的expire_time
                $v2 = $this->db->select('id,expire_time')->from('equipment_label')->where(array('label' => $label))->order_by('id', 'asc')->get()->row_array();
                $new_id = $v2['id'];
                $expire_time = $v2['expire_time'];
                if ($new_id == $id) {//同一条记录
                    $update_sql = "UPDATE cb_equipment_label SET expire_time = DATE_ADD(created_time,INTERVAL CONVERT('" . $preservation_time . "',SIGNED) DAY) where id = " . $id;
                } else {//非同一条记录
                    $update_sql = "update cb_equipment_label set expire_time = '" . $expire_time . "' where id = " . $id;
                }
                echo $update_sql;
                $res = $this->db->query($update_sql);
            }
        }
        echo 'finish';

    }

    public function test2()
    {
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();

        $now_time = date("Y-m-d H:i:s");
        $eq_qty_arr = $this->db->select('equipment_id')->from('equipment')->get()->result_array();
        if (!empty($eq_qty_arr)) {
            foreach ($eq_qty_arr as $v) {
                $arr_expire_labels = array();
                $labels = $this->db->select('t1.equipment_id,t1.label,t1.created_time,t2.product_id')->from('equipment_label t1')->join('label_product t2', 't2.label = t1.label')->where(array(
                    't1.status' => 'active',
                    't1.expire_time <=' => $now_time,
                    't1.equipment_id' => $v['equipment_id']
                ))->get()->result_array();

                if (!empty($labels)) {
                    $i = 0;
                    foreach ($labels as $v1) {
                        $arr_expire_labels['products'][$v1['product_id']][] = $v1;
                        $i++;
                    }
                    $arr_expire_labels['total_num'] = $i;
                    $this->redis->hSet("pro_expire_hash", 'eq_id_' . $v['equipment_id'], json_encode($arr_expire_labels));
                }
            }
        }

        //var_dump($result);exit;
    }

    /*
    *展示设备在百度地图的分布情况
    */
    public function map()
    {
        $this->_pagedata['store_list'] = $this->equipment_admin_model->get_store_list();

        $this->title = '设备分布概览';
        $equ_list = $this->equipment_admin_model->getList(array('status' => 1, 'platform_id' => $this->platform_id));
        $equ_data = array();
        foreach ($equ_list as $key => $value) {
            if (!empty($value['baidu_xyz'])) {
                $equ_data[$key]['name'] = $value['name'];
                $equ_data[$key]['baidu_xyz'] = $value['baidu_xyz'];
            }
        }
        $this->_pagedata ["list"] = $equ_data;
        $this->page('equipment/map.html');
    }

    public function ajax_eq_heart()
    {
        $device_id = $this->input->post('device_id');
        $info = get_device_heart_status_by_redis($device_id);

        die($info);
    }

    private function create_platform_host_sign($params)
    {
        if (isset($params['sign'])) {
            unset($params['sign']);
        }
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . PLATFORM_HOST_SECRET), 0, -1) . 'P');
        return $sign;
    }

    //导出机器
    public function equipment_export()
    {
        $p_db = $this->load->database('platform_master', TRUE);
        $stores = $p_db->select('code,name')->get_where('store', ['is_valid' => 1, 'platform_id' => $this->platform_id])->result_array();
        $store_array = array();
        foreach ($stores as $store) {
            $store_array[$store['code']] = $store['name'];
        }

        $search_name = $this->input->get('search_name') ?: '';
        $search_admin_name = $this->input->get('search_admin_name') ?: '';
        $search_bd_admin = $this->input->get('search_bd_admin') ?: '';
        $search_address = $this->input->get('search_address') ?: '';
        $search_province = $this->input->get('search_province') ?: '';
        $search_city = $this->input->get('search_city') ?: '';
        $search_area = $this->input->get('search_area') ?: '';
        $search_replenish_location = $this->input->get('search_replenish_location');
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
        if ($search_admin_name) {
            $where['alias'] = $search_admin_name;
        }
        if ($search_bd_admin) {
            $where['bd_alias'] = $search_bd_admin;
        }
        if ($search_address) {
            $where['address'] = $search_address;
        }
        if ($search_province) {
            $where['province'] = $search_province;
        }
        if ($search_city) {
            $where['city'] = $search_city;
        }
        if ($search_area) {
            $where['area'] = $search_area;
        }
        if ($search_replenish_location && $search_replenish_location != -1) {
            $where['replenish_location'] = $search_replenish_location;
        }
        if ($search_status || $search_status == 0) {
            $where['status'] = $search_status;
        }
        if ($search_equipment_id) {
            $where['equipment_id'] = $search_equipment_id;
        }
        $where['admin_id'] = $this->adminid;
        $array = $this->equipment_admin_model->getEquipments("", $where, 0, 0, false, $sort, $order);
        $total = (int)$this->equipment_admin_model->getEquipments("count(*) as c", $where)[0]['c'];

        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', 'ID')
            ->setCellValue('B1', '盒子id')
            ->setCellValue('C1', '设备名称')
            ->setCellValue('D1', '设备编码')
            ->setCellValue('E1', '是否自营')
            ->setCellValue('F1', '管理员')
            ->setCellValue('G1', 'bd负责人')
            ->setCellValue('H1', '地址')
            ->setCellValue('I1', '补货仓')
            ->setCellValue('J1', '配送仓')
            ->setCellValue('K1', '送货方式')
            ->setCellValue('L1', '状态')
            ->setCellValue('M1', '创建时间')
            ->setCellValue('N1', '运营时间')
            ->setCellValue('O1', '运营时长')
            ->setCellValue('P1', '货位')
            ->setCellValue('Q1', '点位场景')
            ->setCellValue('R1', '设备等级')
            ->setCellValue('S1', '溢价比例');
        $objPHPExcel->getActiveSheet()->setTitle('设备信息');

        foreach ($array as $item) {
            $data[] = array(
                $item['id'],
                $item['equipment_id'],
                $item['name'],
                $item['code'],
                $item['is_self_type'] == 99 ? '自营' : '非自营 ',
                $item['admin_name'],
                $item['bd_admin'],
                $item['province_city_area'] . $item['address'],
                isset($store_array[$item['replenish_warehouse']]) ? $store_array[$item['replenish_warehouse']] : '',
                isset($store_array[$item['replenish_location']]) ? $store_array[$item['replenish_location']] : '',
                $item['send_type'] == 'motor' ? '电车 ' : '汽车',
                $item['status_name'],
                $item['created_time'],
                $item['firstordertime'],
                $item['order_day'],
                $item['goods_allocation'],
                $item['enterprise_scene'],
                $item['level'],
                $item['add_price'] > 1 ? ($item['add_price'] - 1) * 100 . '%' : '不变',
            );
        }
        for ($i = 2, $j = 0; $i <= $total + 1; $i++) {
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $i, $data[$j][0])
                ->setCellValue('B' . $i, $data[$j][1])
                ->setCellValue('C' . $i, $data[$j][2])
                ->setCellValue('D' . $i, $data[$j][3])
                ->setCellValue('E' . $i, $data[$j][4])
                ->setCellValue('F' . $i, $data[$j][5])
                ->setCellValue('G' . $i, $data[$j][6])
                ->setCellValue('H' . $i, $data[$j][7])
                ->setCellValue('I' . $i, $data[$j][8])
                ->setCellValue('J' . $i, $data[$j][9])
                ->setCellValue('K' . $i, $data[$j][10])
                ->setCellValue('L' . $i, $data[$j][11])
                ->setCellValue('M' . $i, $data[$j][12])
                ->setCellValue('N' . $i, $data[$j][13])
                ->setCellValue('O' . $i, $data[$j][14])
                ->setCellValue('P' . $i, $data[$j][15])
                ->setCellValue('Q' . $i, $data[$j][16])
                ->setCellValue('R' . $i, $data[$j][17])
                ->setCellValue('S' . $i, $data[$j][18]);
            $j++;
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $filename = '设备列表';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');


    }

    public function calculateBox()
    {
        $num = 51;
        $count_big = 0;
        $count_medium = 0;
        $count_small = 0;
        $result = $num;
        for ($x = 0; $x <= 10; $x++) {
            if ($result - 40 > 29) {
                $count_big++;
                $result = $result - 40;
            }
        }
        if ($result <= 19) {
            $count_small++;
        } elseif ($result > 19 && $result <= 29) {
            $count_medium++;
        } elseif ($result > 29 && $result <= 40) {
            $count_big++;
        } elseif ($result > 40) {
            //排序数组
            $array = array();
            //40+29
            $result1 = $result - 40 - 29;
            if ($result1 <= 0) {
                $array['4029'] = $result1;
            }
            //40+19
            $result2 = $result - 40 - 19;
            if ($result2 <= 0) {
                $array['4019'] = $result2;
            }
            //29+19
            $result3 = $result - 29 - 19;
            if ($result3 <= 0) {
                $array['2919'] = $result3;
            }
            //29+29
            $result4 = $result - 29 - 29;
            if ($result4 <= 0) {
                $array['2929'] = $result4;
            }
            arsort($array);
            $first = key($array);
            if ($first == '4029') {
                $count_big++;
                $count_medium++;
            } elseif ($first == '4019') {
                $count_big++;
                $count_small++;
            } elseif ($first == '2919') {
                $count_medium++;
                $count_small++;
            } elseif ($first == '2929') {
                $count_medium++;
                $count_medium++;
            }
        }
        echo '<pre>';
        var_dump($array);
        echo $result1 . '<br>' . $result2 . '<br>' . $result3 . '<br>' . $result4 . '<br>';
        var_dump(array('big' => $count_big, 'medium' => $count_medium, 'small' => $count_small));
        echo '<br>';
        echo $result;

    }

    //批量生成设备二维码
    public function code()
    {


        $this->title = '批量生成设备二维码';
        $equipment_list = $this->equipment_admin_model->get_equipments();
        //获取表equipment_qr字段refer为common的设备id
        $equipment_id = $this->equipment_admin_model->get_equipment_qr();
        $equipment_id = array_column($equipment_id, 'equipment_id');

        $equipment_code = array();
        foreach ($equipment_list as $key => $value) {

            if (!in_array($value['equipment_id'], $equipment_id) && $value['status'] != 99) {
                $equipment_code[] = $value;
            }
        }

        $this->_pagedata['equipment_list'] = $equipment_code;

        // var_dump($equipment_list);die;
        $this->page('equipment/code.html');

    }

    //批量生成设备banner
    public function banner()
    {
        $this->title = '批量生成通用设备Banner';
        $equipment_list = $this->equipment_admin_model->get_equipments();
        //获取表equipment_qr字段refer为common的设备id
//        $equipment_id = $this->equipment_admin_model->get_equipment_qr('common_banner');
//        $equipment_id = array_column($equipment_id, 'equipment_id');

        $equipment_code = array();
        foreach ($equipment_list as $key => $value) {
            if ($value['status'] != 99) {
                $equipment_code[] = $value;
            }
        }
        $this->_pagedata['equipment_list'] = $equipment_code;
        $this->page('equipment/banner.html');
    }

    public function eq_banner()
    {

        $refer = $this->uri->segment(4);
        $equipment_ids = $this->uri->segment(3);
        $equipment_ids = urldecode($equipment_ids);
        $equipment_ids = explode(",", $equipment_ids);
        $banners = array();
        $equipment_list = $this->equipment_admin_model->get_equipments();
        $equipment_info = array();
        foreach ($equipment_list as $k => $v) {
            $equipment_info[$v['equipment_id']] = $v;
        }
        foreach ($equipment_ids as $value) {

            $source_refer = substr($refer, 0, -7);
            $qr_sql = "select * from cb_equipment_qr where refer='{$source_refer}' and equipment_id= '{$value}' ";
            $refer_qr = $this->db->query($qr_sql)->row_array();
            if (!$refer_qr) {
                $img = $this->general_code($value, $source_refer);
            } else {
                $img = $refer_qr['qr'];
            }
            if (!$img) {
                continue;
            }

            $img_banner = $this->general_banner($value, $img, $equipment_info[$value]['code'], $refer, $source_refer);
            $equipment_info[$value]['banner'] = $img_banner;
            $banners[] = $equipment_info[$value];
        }
        $this->ex_banner($banners);
    }

    private function general_banner($eq_id, $qr_img, $code, $refer, $source_refer)
    {

        $fruitday_qr_sql = "select * from cb_equipment_qr where refer='{$refer}' and equipment_id= '{$eq_id}' ";
        $fruitday_qr = $this->db->query($fruitday_qr_sql)->row_array();
        if ($fruitday_qr) {
            return $fruitday_qr['qr'];
        } else {
            $this->load->helper('img');
            $img_banner = qr_banner($qr_img, $code, $source_refer);
            $img_banner = $this->config->item('base_url') . '/uploads/' . $img_banner;
            $sql = "INSERT INTO cb_equipment_qr (equipment_id,refer,qr,update_time) VALUES ('{$eq_id }','{$refer}','" . $img_banner . "','" . time() . "')";
            $this->db->query($sql);
            return $img_banner;
        }
    }

    public function banner_refer()
    {
        if ($this->input->is_ajax_request()) {
            $refer = $_POST['refer'];
            $code = $_POST['code'];
            $equipment_id = $_POST['equipment_id'];
            $source_refer = substr($refer, 0, -7);
            $qr_sql = "select * from cb_equipment_qr where refer='{$source_refer}' and equipment_id= '{$equipment_id}' ";
            $refer_qr = $this->db->query($qr_sql)->row_array();
            if (!$refer_qr) {
                $img = $this->general_code($equipment_id, $source_refer);
            } else {
                $img = $refer_qr['qr'];
            }
            $banner_img = $this->general_banner($equipment_id, $img, $code, $refer, $source_refer);
            if ($refer == 'common_banner') {
                $p_db = $this->load->database('platform_master', TRUE);
                $p_db->update('equipment', array('banner_common' => $banner_img), array('equipment_id' => $equipment_id));
            }
        }
        $array = array('status' => 'success', 'qrcode' => $banner_img);
        $this->showJson($array);
    }

    private function ex_banner($ex_data)
    {
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '设备id')
            ->setCellValue('B1', '设备名称')
            ->setCellValue('C1', '设备编码')
            ->setCellValue('D1', '图片链接');
        $objPHPExcel->getActiveSheet()->setTitle('商品信息');


        foreach ($ex_data as $eachProduct) {
            $data[] = array(
                "'" . $eachProduct['equipment_id'],
                $eachProduct['name'],
                $eachProduct['code'],
                $eachProduct['banner'],

            );
        }

        for ($i = 2, $j = 0; $i <= count($data) + 1; $i++) {
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $i, $data[$j][0])
                ->setCellValue('B' . $i, $data[$j][1])
                ->setCellValue('C' . $i, $data[$j][2])
                ->setCellValue('D' . $i, $data[$j][3]);
            $j++;
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $filename = 'banner导出列表';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    private function general_code($eq_id, $refer)
    {
        $params = array(
            'box_id' => $eq_id,
            'refer' => $refer
        );
        $sign = $this->create_sign_cbapi($params);
        $headers = array("sign:$sign", "platform:admin");

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        //增加来源 alipay/fruitday
        curl_setopt($ch, CURLOPT_URL, CITY_BOX_API . '/api/public_tool/create_qr_code?box_id=' . $eq_id . '&refer=' . $refer);
        log_message('error', ':请求二维码的url：' . CITY_BOX_API . '/api/public_tool/create_qr_code?box_id=' . $eq_id . '&refer=' . $refer);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        $result = json_decode($result);
        if ($result->qr_img) {
            $fruitday_qr_sql = "select * from cb_equipment_qr where refer='{$refer}' and equipment_id= '{$eq_id}' ";
            $fruitday_qr = $this->db->query($fruitday_qr_sql)->row_array();
            if ($fruitday_qr) {
                $sql = "UPDATE cb_equipment_qr SET qr='" . $result->qr_img . "',update_time = '" . time() . "' WHERE id=" . $fruitday_qr['id'];
            } else {
                $sql = "INSERT INTO cb_equipment_qr (equipment_id,refer,qr,update_time) VALUES ('{$eq_id }','{$refer}','" . $result->qr_img . "','" . time() . "')";
            }
            $this->db->query($sql);
            return $result->qr_img;
        }
        return false;
    }

    public function code_fruitday()
    {

        if ($this->input->is_ajax_request()) {

            $refer = $_POST['refer'];
            $equipment_ids = $_POST['equipment_id'];

            $equipment_ids = explode(",", $equipment_ids);


            $array = array();
            foreach ($equipment_ids as $key => $value) {

                $params = array(
                    'box_id' => $value,
                    'refer' => $refer
                );
                //        var_dump($params);
                $sign = $this->create_sign_cbapi($params);
                //        echo $sign.'<br>';

                $headers = array("sign:$sign", "platform:admin");


                $ch = curl_init();
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
                //增加来源 alipay/fruitday
                curl_setopt($ch, CURLOPT_URL, CITY_BOX_API . '/api/public_tool/create_qr_code?box_id=' . $value . '&refer=' . $refer);
                //curl_setopt($ch, CURLOPT_URL, 'http://stagingcityboxapi.fruitday.com/api/device/list_device?start_time=');
                log_message('error', ':请求二维码的url：' . CITY_BOX_API . '/api/public_tool/create_qr_code?box_id=' . $value . '&refer=' . $refer);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec($ch);
                curl_close($ch);
                $result = json_decode($result);
                if ($result->qr_img) {
                    $fruitday_qr_sql = "select * from cb_equipment_qr where refer='{$refer}' and equipment_id= '{$value}' ";
                    $fruitday_qr = $this->db->query($fruitday_qr_sql)->row_array();
                    if ($fruitday_qr) {
                        $sql = "UPDATE cb_equipment_qr SET qr='" . $result->qr_img . "',update_time = '" . time() . "' WHERE id=" . $fruitday_qr['id'] . "";
                    } else {
                        $sql = "INSERT INTO cb_equipment_qr (equipment_id,refer,qr,update_time) VALUES ('" . $value . "','{$refer}','" . $result->qr_img . "','" . time() . "')";
                    }
                    //更新db
                    $res = $this->db->query($sql);

                    $array[$value] = array('status' => 'success', 'qrcode' => $result->qr_img);
                    //$this->showJson(array('status'=>'success','qrcode'=>$result->qr_img));
                } else {
                    $array[$value] = array('status' => 'fail', 'msg' => '获取二维码失败！');
                    //$this->showJson(array('status'=>'error','msg'=>'获取失败！'));
                }
            }

            $this->showJson($array);
        } else {
            $this->showJson($array[] = array('status' => 'error', 'msg' => '提交错误！'));
        }
    }


    public function newbox()
    {
        $this->title = '新设备待分配';
        //商户管理员列表
        $admin_sql = "select * from s_admin where is_lock = 0 and platform_id=$this->platform_id";
        $admin_info = $this->db->query($admin_sql)->result_array();
        $this->_pagedata['admin_info'] = $admin_info;
        //平台bd负责人列表
        $p_db = $this->load->database('platform_master', TRUE);
        $p_db->dbprefix = '';
        $p_admins = $p_db->select('*')->from('s_admin')->where(['is_lock' => 0])->get()->result();
        $this->_pagedata['bd_admin_info'] = $p_admins;

        $this->page('equipment/newbox.html');

    }

    public function newbox_table()
    {
        $admin_sql = "select * from s_admin where is_lock = 0 and platform_id=$this->platform_id";
        $admin_info = $this->db->query($admin_sql)->result_array();
        $option_text = '<option value="-1">待分配</option>';
        foreach ($admin_info as $admin) {
            $option_text .= "<option value = '" . $admin['id'] . "'>" . $admin['alias'] . "</option>";
        }
        $p_db = $this->load->database('platform_master', TRUE);
        $limit = 999;
        $offset = $this->input->get('offset') ?: 0;
        $search_name = $this->input->get('search_name') ?: '';
        $search_address = $this->input->get('search_address') ?: '';
        if ($this->input->get('search_admin_status') === '0') {
            $search_admin_status = 0;
        } else {
            $search_admin_status = $this->input->get('search_admin_status') ?: '';
        }
        $search_db_duty = $this->input->get('search_db_duty') ?: '';
        $search_protocol_start_time = $this->input->get('search_protocol_start_time') ?: '';
        $search_protocol_end_time = $this->input->get('search_protocol_end_time') ?: '';

        //$where['platform_id'] = $this->platform_id;
        $where = ' a.work_status = 4 and a.merchant_owned = ' . $this->platform_id;
        //where条件
        if ($search_name) {
            $where .= ' and a.name like "%' . $search_name . '%" ';
        }
        if ($search_address) {
            $where .= ' and a.address like "%' . $search_address . '%" ';
        }
        if ($search_admin_status || $search_admin_status === 0) {
            $where .= ' and b.admin_status = ' . $search_admin_status;
        }
        if ($search_db_duty) {
            $where .= ' and a.db_duty = ' . $search_db_duty;
        }
        if ($search_protocol_start_time) {
            $where .= " and protocol_start_time>='{strtotime($search_protocol_start_time)}' ";
        }
        if ($search_protocol_end_time) {
            $where .= " and protocol_end_time<='{strtotime($search_protocol_end_time)}' ";
        }
        $sql = "select b.id,b.clue_id,b.equipment_id,b.admin_status,b.equipment_name,a.name,a.province,a.city,a.area,a.address,a.contacts,a.phone,a.scene,a.schedule_time,a.first_deliver,c.alias as bd_alias from p_clue_equipment b left join p_clue a on b.clue_id = a.clue_id left join s_admin c on c.id = a.db_duty where " . $where . " order by b.id desc limit {$offset},{$limit}";
        $array = $p_db->query($sql)->result_array();

        foreach ($array as $k => $v) {
            //场景
            $array[$k]['scene'] = self::$scene[$v['scene']];
            $array[$k]['admin_status_name'] = $v['admin_status'] == 1 ? '已分配' : '未分配';
            $array[$k]['schedule_time'] = date('Y-m-d H:i:s', $array[$k]['schedule_time']);
            if ($v['admin_status'] == 0) {
                $operation = '<div class="operation_class">';
                $operation .= '<select class="form-control shift-info">';
                $operation .= $option_text;
                $operation .= '</select>';
                $operation .= '<input type="hidden" value="" />';
                $operation .= ' <button class="btn btn-danger btn-sm" onclick="assign(this)" equipment_id="' . $v['equipment_id'] . '" id="' . $v['id'] . '">分配</button>  ';
                $operation .= '</div>';
                $array[$k]['operation'] = $operation;
            } else {
                $array[$k]['operation'] = "<a onclick=\"showadmin('" . $v['equipment_id'] . "')\">查看管理员</a>";
            }
            $array[$k]['contacts_phone'] = $v['contacts'] . '<br>' . $v['phone'];
            $first_deliver = '';
            $first_deliver .= "<input title='" . $v['first_deliver'] . "' type='text' size='20' value='" . $v['first_deliver'] . "' />";
            $first_deliver .= ' <button class="btn btn-warning btn-sm" onclick="firstdeliver(this)" clue_id="' . $v['clue_id'] . '">修改</button>  ';
            $array[$k]['first_deliver'] = $first_deliver;

            $ids = array($v['province'], $v['city'], $v['area']);
            $this->db->select('AREAIDS, AREANAME');
            $this->db->from('sys_regional');
            $this->db->where_in('AREAIDS', $ids);
            $regional = $this->db->get()->result_array();
            $tmp = array();
            foreach ($regional as $y => $z) {
                $tmp[$z['AREAIDS']] = $z['AREANAME'];
            }
            $result['province'] = $tmp[$v['province']] ? $tmp[$v['province']] : '';
            $result['city'] = $tmp[$v['city']] ? $tmp[$v['city']] : '';
            $result['area'] = $tmp[$v['area']] ? $tmp[$v['area']] : '';
            $result['address'] = $v['address'];
            $array[$k]['address'] = $result['province'] . $result['city'] . $result['area'] . $result['address'];
        }

        $sql = "select count(*) cou from p_clue_equipment b left join p_clue a on b.clue_id = a.clue_id left join s_admin c on c.id = a.db_duty where " . $where;
        $total_row = $p_db->query($sql)->row_array();
        $total = $total_row['cou'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    public function oldbox()
    {
        $this->title = '旧设备未分配';
        //商户管理员列表
        $admin_sql = "select * from s_admin where is_lock = 0 and platform_id=$this->platform_id";
        $admin_info = $this->db->query($admin_sql)->result_array();
        $this->_pagedata['admin_info'] = $admin_info;

        $this->page('equipment/oldbox.html');

    }

    public function oldbox_table()
    {
        $admin_sql = "select * from s_admin where is_lock = 0 and platform_id=$this->platform_id";
        $admin_info = $this->db->query($admin_sql)->result_array();
        $option_text = '<option value="-1">待分配</option>';
        foreach ($admin_info as $admin) {
            $option_text .= "<option value = '" . $admin['id'] . "'>" . $admin['alias'] . "</option>";
        }
        $limit = 999;
        $offset = $this->input->get('offset') ?: 0;
        $search_name = $this->input->get('search_name') ?: '';
        $search_code = $this->input->get('search_code') ?: '';
        $search_equipment_id = $this->input->get('search_equipment_id') ?: '';

        //$where['platform_id'] = $this->platform_id;
        $where = " a.admin_id = 0 and FROM_UNIXTIME(a.created_time) < '2017-12-11 14:00:00' and c.admin_id = '" . $this->adminid . "' and a.platform_id = " . $this->platform_id;
        //where条件
        if ($search_name) {
            $where .= ' and a.name like "%' . $search_name . '%" ';
        }
        if ($search_code) {
            $where .= ' and a.code like "%' . $search_code . '%" ';
        }
        if ($search_equipment_id) {
            $where .= " and a.equipment_id = '" . $search_equipment_id . "'";
        }
        $sql = "select a.equipment_id,a.name,a.code,a.status,a.created_time from cb_equipment a LEFT JOIN cb_admin_equipment c on a.equipment_id = c.equipment_id where " . $where . " order by a.id desc limit {$offset},{$limit}";
        $array = $this->db->query($sql)->result_array();

        foreach ($array as $k => $v) {
            if ($v['status'] == 1) {
                $array[$k]['status_name'] = '启用';
            } elseif ($v['status'] == 0) {
                $array[$k]['status_name'] = '停用';
            } elseif ($v['status'] == 99) {
                $array[$k]['status_name'] = '报废';
            }
            $array[$k]['created_time'] = date('Y-m-d H:i:s', $array[$k]['created_time']);
            if ($v['admin_id'] == 0) {
                $operation = '<div class="operation_class">';
                $operation .= '<select class="form-control shift-info" style="display:inline;width:60%;">';
                $operation .= $option_text;
                $operation .= '</select>';
                $operation .= '<input type="hidden" value="" />';
                $operation .= ' <button class="btn btn-danger btn-sm" onclick="assign(this)" equipment_id="' . $v['equipment_id'] . '"">分配</button>  ';
                $operation .= '</div>';
                $array[$k]['operation'] = $operation;
            } else {
                $array[$k]['operation'] = '';
            }

        }

        $sql = "select count(*) as cou from cb_equipment a LEFT JOIN cb_admin_equipment c on a.equipment_id = c.equipment_id where " . $where;
        $total_row = $this->db->query($sql)->row_array();
        $total = $total_row['cou'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    public function assign_admin()
    {
        $equipment_id = $this->input->post('equipment_id');
        $admin_id = $this->input->post('admin_id');
        $clue_equipment_id = $this->input->post('clue_equipment_id');
        if ($admin_id > 0 && $equipment_id) {
            //更新equipment表admin_id字段
            $this->db->update('equipment', array('admin_id' => $admin_id), array('equipment_id' => $equipment_id));
            //新增设备白名单
            if ($this->platform_id == 1) {
                $ids = array(3, 5, 7, 11, 16, 17, 18, 20, 21, 42, 48, 70, 78, 52, 55, 130, 62, 115, 212, 218, 190, 89, 266, 95, 93, 345, 239, 291, 290);
                foreach ($ids as $eachId) {
                    $admin_data = array();
                    $admin_data['admin_id'] = $eachId;
                    $admin_data['equipment_id'] = $equipment_id;
                    $exist = $this->admin_equipment_admin_model->ifAdminEquipment($eachId, $equipment_id);
                    if (!$exist) {
                        $this->db->insert('admin_equipment', $admin_data);
                    }
                }
            }
            //往cb_admin_equipment表插入一条管理记录
            $ifAdminEquipment = $this->admin_equipment_admin_model->ifAdminEquipment($admin_id, $equipment_id);
            if (!$ifAdminEquipment) {
                $admin_data['admin_id'] = $admin_id;
                $admin_data['equipment_id'] = $equipment_id;
                $this->db->insert('admin_equipment', $admin_data);
            }
            $p_db = $this->load->database('platform_master', TRUE);
            $p_db->update('clue_equipment', array('admin_status' => 1), array('id' => $clue_equipment_id));
            $result = array('status' => true, 'msg' => '分配成功！');
        } else {
            $result = array('status' => false, 'msg' => '参数错误！');
        }


        echo json_encode($result);
    }


    public function assign_admin_old()
    {
        $equipment_id = $this->input->post('equipment_id');
        $admin_id = $this->input->post('admin_id');
        if ($admin_id > 0 && $equipment_id) {
            //更新equipment表admin_id字段
            $this->db->update('equipment', array('admin_id' => $admin_id), array('equipment_id' => $equipment_id));
            //新增设备白名单
            if ($this->platform_id == 1) {
                $ids = array(3, 5, 7, 11, 16, 17, 18, 20, 21, 42, 48, 70, 78, 52, 55, 130, 62, 115, 212, 218, 190, 89, 266, 95, 93, 345, 239, 291, 290);
                foreach ($ids as $eachId) {
                    $admin_data = array();
                    $admin_data['admin_id'] = $eachId;
                    $admin_data['equipment_id'] = $equipment_id;
                    $exist = $this->admin_equipment_admin_model->ifAdminEquipment($eachId, $equipment_id);
                    if (!$exist) {
                        $this->db->insert('admin_equipment', $admin_data);
                    }

                }
            }
            //往cb_admin_equipment表插入一条管理记录
            $ifAdminEquipment = $this->admin_equipment_admin_model->ifAdminEquipment($admin_id, $equipment_id);
            if (!$ifAdminEquipment) {
                $admin_data['admin_id'] = $admin_id;
                $admin_data['equipment_id'] = $equipment_id;
                $this->db->insert('admin_equipment', $admin_data);
            }
            $result = array('status' => true, 'msg' => '分配成功！');
        } else {
            $result = array('status' => false, 'msg' => '参数错误！');
        }

        echo json_encode($result);
    }

    public function edit_firstdeliver()
    {
        $clue_id = $this->input->post('clue_id');
        $first_deliver = $this->input->post('first_deliver');
        if ($clue_id > 0) {
            $p_db = $this->load->database('platform_master', TRUE);
            $p_db->update('clue', array('first_deliver' => $first_deliver), array('clue_id' => $clue_id));
            $result = array('status' => true, 'msg' => '修改成功！');
        } else {
            $result = array('status' => false, 'msg' => '参数错误！');
        }

        echo json_encode($result);
    }

    public function get_firstdeliver()
    {
        $equipment_id = $this->input->post('equipment_id');
        $p_db = $this->load->database('platform_master', TRUE);
        $sql = "SELECT b.first_deliver FROM p_clue_equipment a LEFT JOIN p_clue b ON a.clue_id = b.clue_id WHERE a.equipment_id = '" . $equipment_id . "'";
        $row = $p_db->query($sql)->row_array();
        if ($row) {
            if (empty($row['first_deliver'])) {
                $result = array('status' => true, 'msg' => '待市场或分配新设备的运营录入');
            } else {
                $result = array('status' => true, 'msg' => $row['first_deliver']);
            }

        } else {
            $result = array('status' => false, 'msg' => '未找到首次补货情况！');
        }
        echo json_encode($result);
    }

    public function get_clue()
    {
        $equipment_id = $this->input->post('equipment_id');
        $p_db = $this->load->database('platform_master', TRUE);
        $sql = "SELECT a.clue_id FROM p_clue_equipment a WHERE a.equipment_id = '" . $equipment_id . "'";
        $row = $p_db->query($sql)->row_array();
        if ($row) {
            $result = array('status' => true, 'clue_id' => $row['clue_id']);
        } else {
            $result = array('status' => false, 'msg' => '未找到点位资料！');
        }
        echo json_encode($result);
    }

    public function show_admin()
    {
        $equipment_id = $this->input->post('equipment_id');
        if ($equipment_id) {
            $sql = "select a.admin_id,b.alias from cb_equipment a left join s_admin b on a.admin_id = b.id where a.equipment_id = '" . $equipment_id . "'";
            $equipment_info = $this->db->query($sql)->row_array();
            $result = array('status' => true, 'msg' => '已分配给管理员' . $equipment_info['alias'] . '！');
        } else {
            $result = array('status' => false, 'msg' => '参数错误！');
        }
        echo json_encode($result);
    }

    /*
     * @desc 筑家生成二维码
     * @param
     * */
    public function zhujia_refer()
    {
        $equipment_id = $this->input->post('equipment_id');
        $zhujia_code = $this->input->post('zhujia_code');//生活号编号
        $zhujia_qr = $this->config->item('zhujia_qr');
        $zhujia_url = $this->config->item('zhujia_url');
        $zhujia_secret = $this->config->item('zhujia_secret');
        $zhujia_qr = array_values($zhujia_qr);
        if (!in_array($zhujia_code, $zhujia_qr)) {
            $this->showJson(array('status' => 'error', 'msg' => '筑家编码不正确'));
        }
        if (!$equipment_id) {
            $this->showJson(array('status' => 'error', 'msg' => '缺少设备参数'));
        }
        $param['rand'] = rand(1000, 9999);
        $param['secret'] = $zhujia_secret;
        $param['timestamp'] = time();
        $param['sign'] = md5(md5($param['rand'] . $param['secret']) . $param['timestamp']);
        $param['lifeSerial'] = $zhujia_code;
        $param['sceneId'] = $equipment_id;
        $param['gotoUrl'] = 'https://cityboxapi.fruitday.com/public/auth.html?deviceId=' . $equipment_id;
        $result = $this->get_api_zhujia($param, $zhujia_url);
        $result = json_decode($result, true);
        if ($result['errCode'] == 0 && $result['data']['codeImg']) {
            $eq_param['qr'] = $result['data']['codeImg'];
            $eq_param['zhujia'] = $zhujia_code;
            $this->db->update('equipment', $eq_param, array('equipment_id' => $equipment_id));
            $this->showJson(array('status' => 'success', 'qrcode' => $result['data']['codeImg']));
        } else {
            $this->showJson(array('status' => 'error', 'msg' => $result['errMsg'], 'param' => $result));
        }
    }

    public function clue()
    {
        $clue_id = $this->uri->segment(3);
        $this->load->model('business_model');
        $admin_list = $this->business_model->getAdmin();
        //企业场景
        $this->_pagedata['scene'] = self::$scene;
        //企业性质
        $this->_pagedata['nature'] = self::$nature;
        //所属行业
        $this->_pagedata['industry'] = self::$industry;
        $this->_pagedata['admin_list'] = $admin_list;

        //        获取地理位置
        $clue_list = $this->business_model->getRow($clue_id, "clue", "clue_id");
        $province = $this->business_model->position($clue_list['province']);
        $city = $this->business_model->position($clue_list['city']);
        $area = $this->business_model->position($clue_list['area']);
        $clue_list['province_name'] = $province['AREANAME'];
        $clue_list['city_name'] = $city['AREANAME'];
        $clue_list['area_name'] = $area['AREANAME'];
        //获取商户
        $this->_pagedata['commercial_list'] = $this->business_model->get_all_platforms();
        //获取bd负责人
        $admin_name = $this->business_model->getUser($clue_list['db_duty']);
        $clue_list ['admin_name'] = $admin_name['alias'];
        if ($clue_list['install_time']) {

            $clue_list['install_time'] = date("Y-m-d H:i:s", $clue_list['install_time']);
        }

        $clue_list['days'] = explode(",", $clue_list['days']);

        $this->_pagedata['clue_list'] = $clue_list;

        $this->_pagedata['img_http'] = $this->img_http;

        $this->page("equipment/clue.html");

    }

    public function getTmsStoreInfo()
    {
        $address = $this->input->post('address');
        $time = time();
        $service = 'deliver.getStoreByAddress';
        $params = array(
            'timestamp' => $time,
            'service' => $service,
            'address' => $address,
            'source' => 'app',
            'version' => '9.9.9'
        );
        $params['sign'] = $this->create_sign_nirvana2($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_URL, 'https://nirvana2.fruitday.com/api');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        $result = json_decode($result, 1);

        $this->showJson(array('status' => 'success', 'prewhCode' => $result['prewhCode'], 'prewhName' => $result['prewhName']));
    }

    public function get_near_equipment()
    {
        $this->page('equipment/near.html');
    }

    public function ajaxGetNearEquipment()
    {
        $lng = $this->input->post('lng');
        $lat = $this->input->post('lat');
        $distance = $this->input->post('distance');
        $sql = "select equipment_id,NAME,SUBSTRING_INDEX(baidu_xyz,',', 1) AS lng,SUBSTRING_INDEX(baidu_xyz,',',-1) AS lat from cb_equipment where sqrt( ( ((" . $lng . "-SUBSTRING_INDEX(baidu_xyz,',', 1))*PI()*12656*cos(((" . $lat . "+SUBSTRING_INDEX(baidu_xyz,',',-1))/2)*PI()/180)/180) * ((" . $lng . "-SUBSTRING_INDEX(baidu_xyz,',', 1))*PI()*12656*cos (((" . $lat . "+SUBSTRING_INDEX(baidu_xyz,',',-1))/2)*PI()/180)/180) ) + ( ((" . $lat . "-SUBSTRING_INDEX(baidu_xyz,',',-1))*PI()*12656/180) * ((" . $lat . "-SUBSTRING_INDEX(baidu_xyz,',',-1))*PI()*12656/180) ) )/2 < " . $distance . " and status = 1 and platform_id = " . $this->platform_id;
        $rows = $this->db->query($sql)->result_array();
        $array = array();
        $array2 = array();
        foreach ($rows as $k => $row) {
            $array[] = $row['equipment_id'];
            $array2[] = $row['equipment_id'] . '(' . $row['NAME'] . ')';
        }
        $equipments = implode(",", $array);
        $equipments_name = implode(",", $array2);
        $this->showJson(['status' => true, 'msg' => '获取设备列表成功', 'equipments' => $equipments, 'equipments_name' => $equipments_name]);
    }

    //批量设备同步支付宝
    function alipay()
    {
        $this->title = '批量设备同步支付宝';
        $equipment_list = $this->equipment_admin_model->get_alipay_equipments();
        $equipment_code = array();
        foreach ($equipment_list as $key => $value) {
            if ($value['status'] != 99) {
                $equipment_code[] = $value;
            }
        }
        $this->_pagedata['equipment_list'] = $equipment_code;
        $this->page('equipment/alipay.html');
    }

    function alipay_save()
    {
        $equipment_ids = $this->input->post('equipment_ids');
        $equipment_ids = explode(",", $equipment_ids);
        $succ = "";
        $err = "";
        foreach ($equipment_ids as $v) {

            $res = $this->db->set("sync_alipay", 1)->where_in("equipment_id", $v)->update("equipment");
            if ($res) {
                $succ .= $v . ",";
            } else {
                $err .= $v . ",";
            }
        }

        $data = ['succ' => $succ, "err" => $err];
        echo json_encode($data);


    }


}

<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Products extends MY_Controller
{

    public $workgroup = 'products';

    function __construct()
    {
        parent::__construct();
        $this->load->model("product_admin_model");
        $this->load->model("product_class_model");
        $this->load->model("equipment_model");
        $this->load->model("equipment_label_model");
        $this->load->model("label_product_admin_model");
        $this->load->model('equipment_stock_model');
        $this->load->model('user_model');


    }

    function labels_list()
    {
        $this->title = '商品标签列表';
        $this->page('products/labels_list.html');
    }

    function labels()
    {
        $this->title = '商品标签录入';
        $this->page('products/labels.html');

    }

    function labels_bind()
    {
        $this->title = '商品标签绑定';
        $this->_pagedata['platform_id'] = $this->platform_id;
        $this->_pagedata['token'] = $this->adminid . ":" . $this->session->userdata['session_id'];
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        $redis->set('bind_user_token_' . $this->adminid, $this->_pagedata['token'], 3600 * 2); //2小时
        $this->page('products/labels_bind.html');

    }

    function read_label_table()
    {
        $this->load->library("Curl");
        $curl = new Curl();
        $v = $this->input->get('v') ?: "1";;
        $iipp = getLocalIP();
        $result = $curl->request('http://' . $iipp . ':10002/stock?version=' . $v);
//        var_dump($result['errorMessage']);
        log_message('error', '标签url' . 'http://' . $iipp . ':10002/stock?version=' . $v . var_export($result, 1));
        if (!empty($result['response'])) {
            $rs = json_decode($result['response'], true);
            if ($rs['code'] == '400') {
                $array[] = array('product_name' => $rs['message'], 'product_id' => $rs['message'], 'label' => 'xxx');
            } else {
                $labels = $rs['body'];
                $labels = ltrim($labels, '[');
                $labels = rtrim($labels, ']');
                if (!empty($labels)) {
                    $arr = explode(',', $labels);
                    foreach ($arr as $v) {
                        $rs = $this->label_product_admin_model->getProductByLabel(trim($v));
                        if (!$rs)
                            $rs = array('product_name' => '', 'product_id' => '', 'label' => trim($v));
                        $array[] = $rs;
                    }
                } else {
                    $array[] = array('product_name' => '没有识别到标签', 'product_id' => '没有识别到标签', 'label' => 'xxx');
                }
            }
        } else {
            $array[] = array('product_name' => '请先启动jar包', 'product_id' => 'jar未启动', 'label' => 'xxx');
        }
        $result = array(
            'total' => count($array),
            'rows' => $array,
        );
        echo json_encode($result);
    }

    function labels_table()
    {
        $limit = $this->input->get('limit') ?: 10;
        $offset = $this->input->get('offset') ?: 0;
        $search_label = $this->input->get('search_label') ?: '';
        $product_name = $this->input->get('search_product_name') ?: '';
        $where['platform_id'] = $this->platform_id;
        if ($search_label) {
            $where['label'] = $search_label;
        }
        if ($product_name) {
            $where1['product_name like'] = '%' . $product_name . '%';
            $products = $this->product_admin_model->getList($where1);
            $ids = "";
            foreach ($products as $p) {
                $ids .= $p['id'] . ",";
            }
            if ($ids != "") {
                $where['product_id'] = rtrim($ids, ',');
            }
        }

        $array = $this->label_product_admin_model->getLabels("", $where, $offset, $limit);
        $total = (int)$this->label_product_admin_model->getLabels("count(*) as c", $where)[0]['c'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    function label_product_search()
    {

        $product_id = $this->input->get('product_id');
        $labels = $this->label_product_admin_model->getLabelByProductId($product_id);
        $result = array(
            'total' => count($labels),
            'rows' => $labels
        );
        echo json_encode($result);
    }

    function labels_save()
    {
        $labels = $this->input->post('labels');
        $product_id = $this->input->post('product_id');
        $warehouse = $this->input->post('warehose');
        //查看product_id是否存在
        $where['id'] = $product_id;
        $product = $this->product_admin_model->getProduct('*', $where);
        if ($product['platform_id'] != $this->platform_id) {
            $this->showJson(array('status' => 'error', 'msg' => '没有该商品id权限'));
        }
        if (empty($product)) {
            $this->showJson(array('status' => 'error', 'msg' => '该商品id不存在'));
        }

        if (!$warehouse) {
            $warehouse = 0;
//            $this->showJson(array('status'=>'error', 'msg'=>'仓库不能为空'));
        }
        $arr = explode("\n", $labels);
        $log_data = array(
            'product_id' => $product_id,
            'labels' => json_encode($arr),
            'adminname' => $this->operation_name,
            'created_time' => time()
        );
        $this->db->insert('label_log', $log_data);
        $log_id = $this->db->insert_id();
        $i = 0;
        $succ_label = array();
        foreach ($arr as $val) {
            $param = array();
            if (trim($val) != '') {
                $where_label['label'] = $val;
                $is_exist = $this->label_product_admin_model->getLabelProduct($where_label);
                if ($is_exist) {
                    $param['warehouse_id'] = $warehouse;
                    $param['product_id'] = $product_id;
                    if (!$this->db->update('label_product', $param, array('id' => $is_exist['id']))) {
                        $this->showJson(array('status' => 'error', 'msg' => '标签绑定失败，请重新绑定！'));
                    } else {
                        $i++;
                        $succ_label[] = $val;
                    }
                } else {
                    $param['warehouse_id'] = $warehouse;
                    $param['label'] = trim($val);
                    $param['product_id'] = $product_id;
                    $param['created_time'] = time();
                    $this->db->insert('label_product', $param);
                    if ($this->db->insert_id() > 0) {
                        $i++;
                        $succ_label[] = $val;
                    } else {
                        $this->showJson(array('status' => 'error', 'msg' => '标签绑定失败，请重新绑定！'));
                    }
                }
            }
        }
        $this->db->update('label_log', array('succ_labels' => json_encode($succ_label)), array('id' => $log_id));
        $this->showJson(array('status' => 'success', 'msg' => $i . '个标签已成功绑定到商品' . $product_id . '上！'));
    }

    function index()
    {
        $this->page('products/index.html');
    }

    public function table()
    {
        $limit = $this->input->get('limit') ?: 10;
        $offset = $this->input->get('offset') ?: 0;
        $order = $this->input->get('order') ?: '';
        $sort = $this->input->get('sort') ?: '';
        $search_name = $this->input->get('search_name') ?: '';

        $where = array();
        if ($search_name) {
            $where['name'] = $search_name;
        }

        $array = $this->product_admin_model->getProducts("", $where, $offset, $limit, $order, $sort, 1);

        $total = (int)$this->product_admin_model->getProducts("count(*) as c", $where)[0]['c'];
        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    public function add()
    {
        $where_class = array('parent_id' => 0);
        $limit_class = 99;
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select style="width:230px;" id = "class_id" name = "class_id" class = "form-control">';
        foreach ($classList as $eachClass) {
            $show_class = $show_class . '<optgroup label="' . $eachClass['name'] . '">' . $eachClass['name'];
            $where_child_class = array('parent_id' => $eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach ($child_classList as $eachChild) {
                $show_class = $show_class . '<option value="' . $eachChild['id'] . '">' . $eachChild['name'] . '</option>';
            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';

        $this->_pagedata['class_info'] = $show_class;
        $this->_pagedata['platform_id'] = $this->platform_id;
        $this->page('products/add.html');
    }

    public function add_save()
    {
        $where_class = array('parent_id' => 0);
        $limit_class = 99;

        $param['product_id'] = 0;
        $param['class_id'] = intval($this->input->post('class_id'));
        $param['product_name'] = $this->input->post('product_name');
        $param['price'] = $this->input->post('price');
        $param['old_price'] = $this->input->post('old_price');
        $param['purchase_price'] = $this->input->post('purchase_price');
        $param['volume'] = $this->input->post('volume');
        $param['unit'] = $this->input->post('unit');
        $param['preservation_time'] = $this->input->post('preservation_time');
        $param['refrigeration'] = $this->input->post('refrigeration');
        $param['is_updown'] = $this->input->post('is_updown');
        $param['created_time'] = time();
        $param['status'] = 1;
        $param['platform_id'] = $this->platform_id;
        $param['serial_num'] = $this->input->post('serial_num');

        if (!empty($param['serial_num'])) {
            $is_ser = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 2, 'serial_number' => $param['serial_num']));
            if (!$is_ser) {
                $is_error = 1;
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("唯一自定义货号重复！");window.history.back();</script></head>';
                exit;
            }
        }


        $extra_serial = $this->input->post('extra_serial_num');
        if (!empty($extra_serial) && $extra_serial[0] != '') {
            $extra_serial = array_unique($extra_serial);
            $is_can = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 1), $extra_serial);
            if (!$is_can) {
                $is_error = 1;
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商品69码已存在！");window.history.back();</script></head>';
                exit;
            }
        }
        $this->db->trans_begin();
        $this->db->insert('product', $param);

        $product_id = $this->db->insert_id();

        if ($product_id > 0) {
            //添加图片
            if ($_FILES['img_url']['tmp_name']) {

                $tag = rand_pass_new();

                $photo_array = upload_pic2($product_id, $_FILES['img_url']['tmp_name'], $_FILES['img_url']['name'], 1, '01', $this->config->item('WEB_BASE_PATH') . 'uploads/', $tag);
                $field = array(
                    'img_url' => $photo_array[1]
                );
//                var_dump($photo_array);die;
            }


            $this->db->update('product', $field, array('id' => $product_id));
            if (!empty($param['serial_num'])) {
                $ser_num[] = array(
                    'product_id' => $product_id,//$product_id,
                    'serial_number' => trim($param['serial_num']),
                    'platform_id' => $this->platform_id,
                    'type' => 2
                );
                $this->product_admin_model->add_product_serial_nums($ser_num);
            }
            if (!empty($extra_serial) && $extra_serial[0] != '') {
                foreach ($extra_serial as $v) {
                    $arr[] = array(
                        'product_id' => $product_id,//$product_id,
                        'serial_number' => trim($v),
                        'platform_id' => $this->platform_id,
                        'type' => 1
                    );
                }
                $this->product_admin_model->add_product_serial_nums($arr);
            }

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("提交失败请稍候重试！");</script></head>';
                $this->page('products/add.html');
                exit;
            } else {
                $this->db->trans_commit();
                redirect('/products/index');
            }
        }
        //}

    }


    public function edit()
    {
        $id = $this->uri->segment(3);
        $act = $this->uri->segment(2);
        $sql = "SELECT * FROM cb_product WHERE id=$id and platform_id = $this->platform_id";
        $info = $this->db->query($sql)->row_array();
        if (!$info) {
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商品不存在或没有权限编辑该商品！");window.location.href="/products/index";</script></head>';
        }
        $where_class = array('parent_id' => 0);
        $limit_class = 99;
        $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
        $show_class = '<select style="width:230px;" id = "class_id" name = "class_id" class = "form-control">';
        foreach ($classList as $eachClass) {
            $show_class = $show_class . '<optgroup label="' . $eachClass['name'] . '">' . $eachClass['name'];
            $where_child_class = array('parent_id' => $eachClass['id']);
            $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
            foreach ($child_classList as $eachChild) {
                if ($info['class_id'] == $eachChild['id']) {
                    $show_class = $show_class . '<option value="' . $eachChild['id'] . '" selected=selected>' . $eachChild['name'] . '</option>';
                } else {
                    $show_class = $show_class . '<option value="' . $eachChild['id'] . '">' . $eachChild['name'] . '</option>';
                }

            }
            $show_class .= '</optgroup>';
        }
        $show_class .= '</select>';

        $ser_num_info = $this->product_admin_model->get_product_serial_nums(array('type' => 1, 'product_id' => $id, 'platform_id' => $this->platform_id));

        $info['supplier_name'] = '';
        if ($info['supplier_id'] > 0) {
            $supplier = $this->warehouse_model->supplier_info($info['supplier_id']);
            if (isset($supplier['name'])) {
                $info['supplier_name'] = $supplier['name'];
            }

        }
//        var_dump($ser_num_info);

        $this->_pagedata['class_info'] = $show_class;
        $this->_pagedata['platform_id'] = $this->platform_id;
        $this->_pagedata['id'] = $id;
        $this->_pagedata['info'] = $info;
        $this->_pagedata['ser_num_info'] = $ser_num_info;
        $this->page('products/edit.html');
    }

    //编辑保存
    public function edit_save()
    {
        $id = $_POST['id'];
        $param['class_id'] = intval($this->input->post('class_id'));
        $param['product_name'] = $this->input->post('product_name');
        $param['price'] = $this->input->post('price');
        $param['old_price'] = $this->input->post('old_price');
        $param['purchase_price'] = $this->input->post('purchase_price');
        $param['volume'] = $this->input->post('volume');
        $param['serial_num'] = $this->input->post('serial_num');



        if (!empty($param['serial_num'])) {
//            $is_ser = $this->product_admin_model->check_is_unique_serial_num(array('platform_id'=>$this->platform_id,'serial_num'=>$param['serial_num'],'id !='=>$id));
            $is_ser = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 2, 'serial_number' => $param['serial_num'], 'product_id !=' => $id));
            if (!$is_ser) {
                $is_error = 1;
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("唯一自定义货号重复！");location.href = "/products/edit/' . $id . '";</script></head>';
                exit;
            }
        }

        $extra_serial = $this->input->post('extra_serial_num');
        if (!empty($extra_serial) && $extra_serial[0] != '') {
            $extra_serial = array_unique($extra_serial);
            $is_can = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 1, 'product_id !=' => $id), $extra_serial);
            if (!$is_can) {
                $is_error = 1;
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商品69码已存在！");location.href = "/products/edit/' . $id . '";</script></head>';
                exit;
            }
        }




        $param['unit'] = $this->input->post('unit');
        $param['tags'] = $this->input->post('tags');
        $param['preservation_time'] = $this->input->post('preservation_time');
        $param['refrigeration'] = $this->input->post('refrigeration');
        $param['is_updown'] = $this->input->post('is_updown');

        if ($_FILES['img_url']['tmp_name']) {
            $tag = rand_pass_new();
            $photo_array = upload_pic2($id, $_FILES['img_url']['tmp_name'], $_FILES['img_url']['name'], 1, '01', $this->config->item('WEB_BASE_PATH') . 'uploads/', $tag);
            $img_url = $photo_array[1];
        } else {
            $img_url = $_POST['old_img_url'];
        }

        $param['img_url'] = $img_url;

        $this->db->trans_begin();
        $res = $this->db->update('product', $param, array('id' => $id));

        if (!empty($param['serial_num'])) {
            $ser_num[] = array(
                'product_id' => $id,
                'serial_number' => trim($param['serial_num']),
                'platform_id' => $this->platform_id,
                'type' => 2
            );
            $this->product_admin_model->delete_product_serial_nums(array('product_id' => $id, 'type' => 2, 'platform_id' => $this->platform_id));
            $this->product_admin_model->add_product_serial_nums($ser_num);
        } else {
            $this->product_admin_model->delete_product_serial_nums(array('product_id' => $id, 'type' => 2, 'platform_id' => $this->platform_id));
        }


        if (!empty($extra_serial) && $extra_serial[0] != '') {
            foreach ($extra_serial as $v) {
                $arr[] = array(
                    'product_id' => $id,//$product_id,
                    'serial_number' => trim($v),
                    'platform_id' => $this->platform_id,
                    'type' => 1
                );
            }

            $this->product_admin_model->delete_product_serial_nums(array('product_id' => $id, 'type' => 1, 'platform_id' => $this->platform_id));

            $this->product_admin_model->add_product_serial_nums($arr);
        } else {
            $this->product_admin_model->delete_product_serial_nums(array('product_id' => $id, 'type' => 1, 'platform_id' => $this->platform_id));
        }


        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("提交失败请稍候重试！");</script></head>';
            redirect('/products/edit/' . $id);
        } else {
            $this->db->trans_commit();
            redirect('/products/index');
        }
    }


    public function delete($ids)
    {
        $ids = urldecode($ids);
        $ids_arr = explode('|', $ids);
        $ids_arr = array_filter($ids_arr);
        //检查是不是有绑定标签
        /* foreach ($ids_arr as $id){
            $where['product_id'] = $id;
            $result = $this->label_product_admin_model->getLabelProduct($where);
            if ($result){
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("该商品还有对应绑定的标签！不能删除！");history.back();</script></head>';
                exit;
            }
        } */
        $rs = $this->product_admin_model->product_update_where_in(array('status' => 0), array('platform_id' => $this->platform_id), $ids_arr);

        redirect('/products/index');
    }

    //添加分类
    public function add_class()
    {
        if ($this->input->is_ajax_request()) {
            $param['parent_id'] = $this->input->post('parent_class_id') ? $this->input->post('parent_class_id') : 0;
            $param['name'] = $this->input->post('class_name');
            $param['ctime'] = time();
            if ($this->db->insert('product_class', $param)) {
                $result = $this->db->insert_id();
                $this->showJson(array('status' => 'success', 'class_id' => $result, 'class_name' => $param['name'], 'parent_class_id' => $param['parent_class_id']));
            }
        }
        $this->showJson(array('status' => 'error'));
    }

    //编辑分类
    public function edit_class()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('classid');
            $param['name'] = $this->input->post('class_name');
            if ($this->db->update('product_class', $param, array('id' => $id))) {
                $this->showJson(array('status' => 'success'));
            }
        }
        $this->showJson(array('status' => 'error'));

    }

    //删除分类
    public function delete_class()
    {
        if ($this->input->is_ajax_request()) {
            $id = $this->input->post('classid');
            if ($this->db->delete('product_class', array('id' => $id))) {
                $this->showJson(array('status' => 'success'));
            }
        }
        $this->showJson(array('status' => 'error'));

    }



    public function ajax_search_proudct_by_id()
    {
        $id = $this->input->post('id');
        $sql = "SELECT * FROM cb_product WHERE id=$id and platform_id =" . $this->platform_id;
        $info = $this->db->query($sql)->row_array();
        if ($info) {
            echo $info['product_name'];
        }
        echo "";

    }

    public function ajax_search_proudct_by_name()
    {
        $name = $this->input->post('name');
        if (empty($name)) {
            echo json_encode('');
            die;
        }

        $info = $this->db->from('product')
            ->like('product_name', $name)
            ->where(['status' => 1, 'platform_id' => $this->platform_id])
            ->get()
            ->result_array();
        echo json_encode($info ? $info : '');
        die;
    }

    public function ajax_search_product_eq_name()
    {
        $name = $this->input->post('name');
        $id = $this->input->post('id');
        if ($id) {
            $sql = "SELECT * FROM cb_product WHERE product_name = '" . $name . "' and id <> " . $id . " and status = 1 and platform_id =" . $this->platform_id;
        } else {
            $sql = "SELECT * FROM cb_product WHERE product_name = '" . $name . "' and status = 1 and platform_id =" . $this->platform_id;
        }

        $info = $this->db->query($sql)->result_array();
        if ($info) {
            $result = json_encode(array('result' => 500, 'msg' => '该商品已重复'));
        } else {
            $result = json_encode(array('result' => 200));
        }
        echo $result;
    }

    public function ajax_search_label_product()
    {
        $labels = $this->input->post('label');

        $labels = ltrim($labels, '[');
        $labels = rtrim($labels, ']');

        $ret = array();
        if (!empty($labels)) {
            $arr = explode(',', $labels);

            foreach ($arr as $v) {
                $rs = $this->label_product_admin_model->getProductByLabel(trim($v));
                if (!$rs)
                    $rs = array();
                $ret[trim($v)] = $rs;
            }
        }

        die(json_encode($ret));
    }

    public function gen_product_unique_code()
    {
        echo $this->product_admin_model->gen_product_unique_code();
        die;
    }

}

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

    public function ajax_search_product()
    {
        if ($this->input->is_ajax_request()) {
            $limit = 10;
            $offset = 0;
            $search_name = $this->input->post('search_name') ?: '';
            $equipment_id = $this->input->post('equipment_id') ?: '';
            $replenish_warehouse = $this->input->post('replenish_warehouse') ?: '';
//            if (!$equipment_id){
//                $this->showJson(array('status'=>'error'));
//            }
            $where = array();
            if ($search_name) {
                $where['id'] = $search_name;
            }
            $array = $this->product_admin_model->getProducts("", $where, $offset, $limit);
            if ($array && $equipment_id) {
                $product_id = $array[0]['id'];
                //查看库存量
                $sql = 'select equipment_id,add_price,replenish_warehouse from cb_equipment where id = ' . $equipment_id;
                $info = $this->db->query($sql)->row_array();

                //仓库库存
                $this->load->model("warehouse_model");
                $wh_stock = $this->warehouse_model->get_stock([
                    'replenish_warehouse' => $info['replenish_warehouse'],
                    'product_id' => $product_id
                ]);
                $array[0]['wh_stock'] = end($wh_stock);
                $add_price = '';
                if ($info['add_price'] && $info['add_price'] > 1) {
                    $add_price = $info['add_price'];
                }
                $stock_data = $this->equipment_label_model->getStock($info['equipment_id']);
                $array[0]['stock_num'] = 0;
                foreach ($stock_data as $stock) {
                    if ($stock['product_id'] == $product_id) {
                        $array[0]['stock_num'] = $stock['count_num'] ? $stock['count_num'] : 0;
                    }
                }
                //查看预存量
                $shipping_config_list = $this->deliver_model->get_deliver_shipping_list($equipment_id);
                $array[0]['pre_num'] = 0;
                foreach ($shipping_config_list as $config) {
                    if ($config['product_id'] == $product_id) {
                        $array[0]['pre_num'] = $config['pre_qty'] ? $config['pre_qty'] : 0;
                    }
                }
                //计算初始补货量
                $add_num = $array[0]['pre_num'] - $array[0]['stock_num'];
                if (in_array($this->platform_id, [1, 2])) {
                    $add_num = min($add_num, $array[0]['wh_stock']);
                }
                $add_num = $add_num < 0 ? 0 : $add_num;
                $array[0]['add_num'] = $add_num;
                if ($add_price) {
                    $array[0]['price'] = $add_price * $array[0]['price'];
                }
            } elseif ($array && $replenish_warehouse) {
                //仓库库存
                $this->load->model("warehouse_model");
                $wh_stock = end($this->warehouse_model->get_stock([
                    'replenish_warehouse' => $replenish_warehouse,
                    'product_id' => $array[0]['id']
                ]));
                $array[0]['wh_stock'] = $wh_stock ? $wh_stock : [];
            }
            $this->showJson(array('status' => 'success', 'product_info' => $array));
        }
        $this->showJson(array('status' => 'error'));
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

    public function template()
    {
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '商品类目')
            ->setCellValue('B1', '商品名称')
            ->setCellValue('C1', '商品价格')
            ->setCellValue('D1', '商品原价')
            ->setCellValue('E1', '商品进价')
            ->setCellValue('F1', '规格')
            ->setCellValue('G1', '计量单位')
            ->setCellValue('H1', '保鲜期（天）')
            ->setCellValue('I1', '唯一自定义货号')
            ->setCellValue('J1', '69码')
            ->setCellValue('K1', '是否允许叫货(是/否)')
            ->setCellValue('L1', '是否冷藏(是/否)');
        $objPHPExcel->getActiveSheet()->setTitle('商品信息');

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('10');
        $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('15');
        $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('20');
        $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth('20');
        $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth('15');
        $objPHPExcel->getActiveSheet()->getColumnDimension('L')->setWidth('15');

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $filename = '批量添加商品模板';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function multi_add()
    {
        include(APPPATH . 'libraries/Excel/PHPExcel/IOFactory.php');
        $tmp_file = $_FILES['excel']['tmp_name'];

        @set_time_limit(0);
        $objPHPExcelReader = PHPExcel_IOFactory::load($tmp_file);  //加载excel文件
        //导入成功数
        $succ_count = 0;
        //导入失败数
        $fail_count = 0;
        //导入失败数组
        $fail_arr = array();
        foreach ($objPHPExcelReader->getWorksheetIterator() as $sheet)  //循环读取sheet
        {
            $insert_data = array();
            foreach ($sheet->getRowIterator() as $row)  //逐行处理
            {
                if ($row->getRowIndex() < 2)  //确定从哪一行开始读取
                {
                    continue;
                }
                foreach ($row->getCellIterator() as $k => $cell)  //逐列读取
                {
                    $data = $cell->getValue(); //获取cell中数据
                    switch ($k) {
                        case 'A':
                            $insert_data['class_name'] = $data;
                            break;
                        case 'B':
                            $insert_data['product_name'] = (string)$data;
                            break;
                        case 'C':
                            $insert_data['price'] = $data;
                            break;
                        case 'D':
                            $insert_data['old_price'] = $data;
                            break;
                        case 'E':
                            $insert_data['purchase_price'] = $data;
                            break;
                        case 'F':
                            $insert_data['volume'] = (string)$data;
                            break;
                        case 'G':
                            $insert_data['unit'] = $data;
                            break;
                        case 'H':
                            $insert_data['preservation_time'] = $data;
                            break;
                        case 'I':
                            $insert_data['serial_num'] = $data;
                            break;
                        case 'J':
                            $extra_serial_num = $data;
                            break;
                        case 'K':
                            $insert_data['is_updown'] = $data == '是' ? 1 : 0;
                            break;
                        case 'L':
                            $insert_data['refrigeration'] = $data == '是' ? 1 : 0;
                            break;
                    }
                }
                //验证序列号
                $is_ser = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 2, 'serial_number' => $insert_data['serial_num']));
                if (!$is_ser) {
                    $fail_count++;
                    $fail_arr[] = $insert_data;
                } else {
                    //验证69码
                    $is_extra_ser = $this->product_admin_model->check_is_unique_serial_number(array('platform_id' => $this->platform_id, 'type' => 1, 'serial_number' => $extra_serial_num));
                    if (!$is_extra_ser) {
                        $fail_count++;
                        $fail_arr[] = $insert_data;
                    } else {
                        //验证分类是否正确
                        $where_arr = array('name' => $insert_data['class_name'], 'parent_id >' => 0);
                        $is_product_class = $this->product_class_model->getList($where_arr);
                        if (!$is_product_class) {
                            $fail_count++;
                            $fail_arr[] = $insert_data;
                        } else {
                            $class_id = $is_product_class[0]['id'];
                            $class_name = $insert_data['class_name'];
                            $insert_data['class_id'] = $class_id;
                            unset($insert_data['class_name']);
                            //添加入库
                            $insert_data['platform_id'] = $this->platform_id;
                            $insert_data['img_url'] = 'images/box_products_img/4947/1/1-180x180-4947-W36FHFW7.jpg';
                            $insert_data['status'] = 1;
                            $insert_data['created_time'] = time();
                            $insert_data['inner_code'] = '';
                            $insert_data['product_no'] = '';
                            $this->db->insert('product', $insert_data);
                            $product_id = $this->db->insert_id();
                            if ($product_id > 0) {
                                $ser_num = array();
                                if (!empty($insert_data['serial_num'])) {
                                    $ser_num[] = array(
                                        'product_id' => $product_id,//$product_id,
                                        'serial_number' => trim($insert_data['serial_num']),
                                        'platform_id' => $this->platform_id,
                                        'type' => 2
                                    );
                                }
                                if (!empty($extra_serial_num)) {
                                    $ser_num[] = array(
                                        'product_id' => $product_id,//$product_id,
                                        'serial_number' => trim($extra_serial_num),
                                        'platform_id' => $this->platform_id,
                                        'type' => 1
                                    );
                                }
                                $this->product_admin_model->add_product_serial_nums($ser_num);
                                $succ_count++;
                            } else {
                                $fail_count++;
                                $insert_data['class_name'] = $class_name;
                                $fail_arr[] = $insert_data;
                            }
                        }
                    }
                }
            }
        }

        //失败数>0 导出excel
        if ($fail_count > 0) {
            $objPHPExcel = new PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0)
                ->setCellValue('A1', '商品类目')
                ->setCellValue('B1', '商品名称')
                ->setCellValue('C1', '商品价格')
                ->setCellValue('D1', '商品原价')
                ->setCellValue('E1', '商品进价')
                ->setCellValue('F1', '规格')
                ->setCellValue('G1', '计量单位')
                ->setCellValue('H1', '保鲜期（天）')
                ->setCellValue('I1', '唯一自定义货号')
                ->setCellValue('J1', '是否允许叫货(是/否)')
                ->setCellValue('K1', '是否冷藏(是/否)');
            $objPHPExcel->getActiveSheet()->setTitle('商品信息');

            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('15');
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('20');
            $objPHPExcel->getActiveSheet()->getColumnDimension('J')->setWidth('15');
            $objPHPExcel->getActiveSheet()->getColumnDimension('K')->setWidth('15');


            $data = array();
            foreach ($fail_arr as $eachProduct) {
                $data[] = array(
                    $eachProduct['class_name'],
                    $eachProduct['product_name'],
                    $eachProduct['price'],
                    $eachProduct['old_price'],
                    $eachProduct['purchase_price'],
                    $eachProduct['volume'],
                    $eachProduct['unit'],
                    $eachProduct['preservation_time'],
                    $eachProduct['serial_num'],
                    $eachProduct['is_updown'] == 1 ? '是' : '否',
                    $eachProduct['refrigeration'] == 1 ? '是' : '否'
                );
            }
            for ($i = 2, $j = 0; $i <= $fail_count + 1; $i++) {
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
                    ->setCellValue('K' . $i, $data[$j][10]);
                $j++;
            }

            // Redirect output to a client’s web browser (Excel2007)
            $filename = $fail_count . '个失败商品导出(' . $succ_count . '个成功商品)';
            $objPHPExcel->initHeader($filename);
            $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
            $objWriter->save('php://output');
        } else {
            $this->_pagedata["tips"] = "导入成功，共导入" . $succ_count . "个商品。";
            $limit_class = 99;
            $offset = $this->input->get('offset') ?: 0;
            $where_class = array('parent_id' => 0);
            $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
            $show_class = '<ul>';
            foreach ($classList as $eachClass) {
                $show_class = $show_class . '<li class="parent_li" classid="' . $eachClass['id'] . '">' . $eachClass['name'];
                $where_child_class = array('parent_id' => $eachClass['id']);
                $child_classList = $this->product_class_model->getProductClasses("", $where_child_class, 0, $limit_class);
                $show_class .= "<ul>";
                foreach ($child_classList as $eachChild) {
                    $show_class = $show_class . '<li classid="' . $eachChild['id'] . '">' . $eachChild['name'] . '</li>';
                }
                $show_class .= "</ul>";
                $show_class .= '</li>';
            }
            $show_class .= '</ul>';
            $this->_pagedata ['show_class'] = $show_class;
            $this->_pagedata ["treecss"] = base_url('assets/css/easyTree.css');
            $this->_pagedata ["treejs"] = base_url("assets/js/easyTree.js");
            $classList = $this->product_class_model->getProductClasses("", $where_class, 0, $limit_class);
            $show_class = '<select id = "search_class_id" name = "search_class_id" class = "form-control">';
            $show_class = $show_class . '<option value="-1">全部类目</option>';
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

            //判断是否有添加商品权限
            if (in_array(93, $this->session->userdata('sess_admin_data')["adminflag"])) {
                $this->_pagedata['add_product'] = 1;
            } else {
                $this->_pagedata['add_product'] = 0;
            }
            $this->page('products/index.html');
            exit;
        }

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

    public function search_product()
    {
        if ($this->input->is_ajax_request()) {
            if (in_array($this->platform_id, [1, 10, 5])) {
                $product_no = $this->input->post('product_no');
                $time = time();
                $service = 'open.importCityshopProduct';
                $params = array(
                    'timestamp' => $time,
                    'service' => $service,
                    "product_no" => $product_no,
                );
                $params['sign'] = $this->create_sign_v2($params);
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
                curl_setopt($ch, CURLOPT_TIMEOUT, 10);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_URL, 'http://nirvana.fruitday.com/openApi');
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                $result = curl_exec($ch);
                $json_result = json_decode($result);
                $product_data = $json_result->product_data;
                $groupFlag = $product_data->groupFlag ? unserialize($product_data->groupFlag) : '';
                $warehouse = $product_data->warehouse ? unserialize($product_data->warehouse) : '';

                if (in_array($this->platform_id, [10, 5])) {
                    echo $result;
                    die;
                }

                if ($groupFlag) {
                    if (!in_array(2, $groupFlag) || !in_array(3, $groupFlag)) {
                        $result = json_encode(array('result' => 500, 'msg' => '必须是城超和express商品！'));
                    }
                } else {
                    $result = json_encode(array('result' => 500, 'msg' => '必须是城超和express商品！'));
                }
                if ($warehouse) {
                    if (!in_array(5, $warehouse) || !in_array(10, $warehouse)) {
                        $result = json_encode(array('result' => 500, 'msg' => '必须是上海和北京大仓！'));
                    }
                } else {
                    $result = json_encode(array('result' => 500, 'msg' => '必须是上海和北京大仓！'));
                }
                echo $result;
            } else {
                $result = json_encode(array('result' => 200));
                echo $result;
            }
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

    public function export()
    {
        $search_name = $this->input->get('search_name') ?: '';
        $search_class_id = $this->input->get('search_class_id') ?: '';
        if ($this->input->get('search_is_paper_order') === '0') {
            $search_is_paper_order = 0;
        } else {
            $search_is_paper_order = $this->input->get('search_is_paper_order') ?: '';
        }
        $search_tag = $this->input->get('search_tag') ?: '';
        $where = array();
        if ($search_name) {
            $where['name'] = $search_name;
        }
        if ($search_class_id) {
            $where['class_id'] = $search_class_id;
        }
        if ($search_tag) {
            $where['tag'] = $search_tag;
        }
        if ($search_is_paper_order || $search_is_paper_order === 0) {
            $where['is_paper_order'] = $search_is_paper_order;
        }

        $limit = $this->input->get('limit') ?: 999;
        $offset = $this->input->get('offset') ?: 0;
        $array = $this->product_admin_model->getProducts("", $where, $offset, $limit, 'desc', 'created_time', 1);
        $total = (int)$this->product_admin_model->getProducts("count(*) as c", $where)[0]['c'];
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '商品id')
            ->setCellValue('B1', '商品类目')
            ->setCellValue('C1', '商品名称')
            ->setCellValue('D1', 'OMS编码')
            ->setCellValue('E1', 'SAP编码')
            ->setCellValue('F1', '商品价格')
            ->setCellValue('G1', '商品原价')
            ->setCellValue('H1', '商品进价')
            ->setCellValue('I1', '规格')
            ->setCellValue('J1', '计量单位')
            ->setCellValue('K1', '保鲜期（天）')
            ->setCellValue('L1', '在售库存')
            ->setCellValue('M1', '69码');
        $objPHPExcel->getActiveSheet()->setTitle('商品信息');


        foreach ($array as $eachProduct) {
            $data[] = array(
                $eachProduct['id'],
                $eachProduct['class_name'],
                $eachProduct['product_name'],
                $eachProduct['product_no'],
                $eachProduct['inner_code'],
                $eachProduct['price'],
                $eachProduct['old_price'],
                $eachProduct['purchase_price'],
                $eachProduct['volume'],
                $eachProduct['unit'],
                $eachProduct['preservation_time'],
                $eachProduct['sum_stock'],
                $eachProduct['serial_num']
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
                ->setCellValue('M' . $i, $data[$j][12]);
            $j++;
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $filename = '商品列表';
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
    }

    public function upload_img()
    {
        //            echo 1;exit;
        $this->config->load("qiniu", true, true);
        $this->load->library('Qiniu/qiniu', $this->config->item('qiniu'));
        $allImg = $_FILES;
        $result = array();
        foreach ($allImg as $k => $v) {
            if (!empty($v['tmp_name'])) {
                $imgName = uniqid("product_");
                $imageType = substr($v['name'], strripos($v['name'], '.', 0));

                if ($this->qiniu->put($imgName . $imageType, $v['tmp_name'])) {
                    $result[$k] = $this->config->item('qiniu')['url'] . '/' . $imgName . $imageType;
                };
            }
        };
        return $result;
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


    public function bi_data()
    {
        $this->_pagedata['admin'] = $this->user_model->get_all_admin();
        $this->_pagedata['p_class'] = $this->product_class_model->get_all_class();
        $this->_pagedata['start_time'] = date('Y-m-d', strtotime('-31 days'));
        $this->_pagedata['end_time'] = date('Y-m-d', strtotime('-1 days'));

        $this->page('products/bi_data.html');

    }

    public function bi_table()
    {
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 50;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $admin_id = $this->input->get('search_admin');

        $search_product_name = trim($this->input->get('search_product_name'));
        $tmp = explode('|', $search_product_name);
        $class_id = $this->input->get('search_class_id');
        if ($class_id == '' || $class_id == 'null') {
            $class_id = array();
        } else {
            $class_id = explode(',', $class_id);
        }
        $eq_name = trim($this->input->get('search_eq_name'));

        $product_list = $this->shipping_config_model->get_all_data(intval($tmp[0]), $class_id, $eq_name, $limit, $offset, $admin_id);
        $total = $this->shipping_config_model->get_all_data_num(intval($tmp[0]), $class_id, $eq_name, $admin_id);
        if (empty($product_list)) {
            $this->showJson(array('total' => 0, 'rows' => array()));
        }
        $eq_id = $product_id = array();
        foreach ($product_list as $k => $v) {
            $eq_id[$v['equipment_id']] = $v['equipment_id'];
            $product_id[$v['product_id']] = $v['product_id'];
        }
        $stock = $this->equipment_stock_model->get_stock($eq_id, $product_id);//当前设备库存
        $stock_cang = $this->warehouse_model->get_product_stock($product_id);//仓库库存

        $sale = $this->bi_product_admin_model->get_sale($eq_id, $product_id, $start_time, $end_time);
        $days = intval((strtotime($end_time) - strtotime($start_time)) / 86400);
        if ($_GET['is_explore'] == 1) {
            return $this->bi_table_explore($product_list, $stock, $stock_cang, $sale, $days, $end_time);
        }
        $days = $days > 7 ? 7 : ($days + 1);
        foreach ($product_list as $k => $v) {
            $product_list[$k]['stock'] = intval($stock[$v['equipment_id']][$v['product_id']]);
            $product_list[$k]['stock_cang'] = intval($stock_cang[$v['product_id']]);
            for ($i = 0; $i < $days; $i++) {
                $key = date('Y-m-d', strtotime($end_time . ' -' . $i . ' days'));
                $product_list[$k][$key] = intval($sale[$v['product_id']][$v['equipment_id']][$key]);
            }
            $product_list[$k]['sale_num'] = intval(array_sum($sale[$v['product_id']][$v['equipment_id']]));
        }
        $result = array(
            'total' => intval($total),
            'rows' => $product_list
        );
        $this->showJson($result);
    }

    public function bi_table_explore($product_list, $stock, $stock_cang, $sale, $days, $end_time)
    {
        $days = $days + 1;
        $key_array = array(0 => 'G', 1 => 'H', 2 => 'I', 3 => 'J', 4 => 'K', 5 => 'L', 6 => 'M', 7 => 'N', 8 => 'O', 9 => 'P', 10 => 'Q', 11 => 'R', 12 => 'S', 13 => 'T', 14 => 'U', 15 => 'V', 16 => 'W', 17 => 'X', 18 => 'Y', 19 => 'Z', 20 => 'AA', 21 => 'AB', 22 => 'AC', 23 => 'AD', 24 => 'AE', 25 => 'AF', 26 => 'AG', 27 => 'AH', 28 => 'AI', 29 => 'AJ', 30 => 'AK', 31 => 'AL', 32 => 'AM', 33 => 'AN');

        foreach ($product_list as $k => $v) {
            $product_list[$k]['stock'] = intval($stock[$v['equipment_id']][$v['product_id']]);
            $product_list[$k]['stock_cang'] = intval($stock_cang[$v['product_id']]);
            for ($i = 0; $i < $days; $i++) {
                $key = date('Y-m-d', strtotime($end_time . ' -' . $i . ' days'));
                $product_list[$k][$key] = intval($sale[$v['product_id']][$v['equipment_id']][$key]);
            }
            $product_list[$k]['sale_num'] = intval(array_sum($sale[$v['product_id']][$v['equipment_id']]));
        }

        $admin_tmp = $this->user_model->get_all_admin();
        $admin = array();
        foreach ($admin_tmp as $ak => $av) {
            $admin[$av['id']] = $av;
        }

        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objH = $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '设备名称')
            ->setCellValue('B1', '商品名称')
            ->setCellValue('C1', '商品id')
            ->setCellValue('D1', '(此刻)设备在售库存')//trade_no
            ->setCellValue('E1', '(此刻)设备预存量')
            ->setCellValue('F1', '(全部)仓库库存')
            ->setCellValue('G1', '时段总销售件数')
            ->setCellValue('H1', '管理员')
            ->setCellValue('I1', '点位等级');

        for ($i = 0; $i < $days; $i++) {
            $key_d = date('Y-m-d', strtotime($end_time . ' -' . $i . ' days'));
            $objH->setCellValue($key_array[($i + 3)] . '1', $key_d);
        }

        $objPHPExcel->getActiveSheet()->setTitle('商品数据列表-' . intval($_GET['page']));


        $product_list = array_values($product_list);
        foreach ($product_list as $k => $v) {
            $key = $k + 2;
            $objV = $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $key, $v['eq_name'])
                ->setCellValue('B' . $key, $v['product_name'])
                ->setCellValue('C' . $key, $v['product_id'])
                ->setCellValue('D' . $key, $v['stock'])
                ->setCellValue('E' . $key, $v['pre_qty'])
                ->setCellValue('F' . $key, $v['stock_cang'])
                ->setCellValue('G' . $key, $v['sale_num'])
                ->setCellValue('H' . $key, $admin[$v['admin_id']]['alias'])
                ->setCellValue('I' . $key, $v['level']);

            for ($i = 0; $i < $days; $i++) {
                $key_d = date('Y-m-d', strtotime($end_time . ' -' . $i . ' days'));
                $objV->setCellValue($key_array[($i + 3)] . $key, $v[$key_d]);
            }
        }
        @set_time_limit(0);
        $objPHPExcel->initHeader('商品数据列表-' . intval($_GET['page']));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function bi_table_filed()
    {
        $start_time = $this->input->get('search_start_time');
        $end_time = $this->input->get('search_end_time');
        $days = intval((strtotime($end_time) - strtotime($start_time)) / 86400);
        $days = $days > 7 ? 7 : ($days + 1);
        $result = array();
        for ($i = 0; $i < $days; $i++) {
            $key = date('Y-m-d', strtotime($end_time . ' -' . $i . ' days'));
            $result[$i]['title'] = date('m-d', strtotime($end_time . ' -' . $i . ' days')) . '销售件数';
            $result[$i]['field'] = $key;
        }
        $this->showJson($result);
    }

    public function download_html($num)
    {
        $limit = 2000;
        $page = ceil($num / $limit);
        $result = array();
        for ($i = 1; $i <= $page; $i++) {
            $start = ($i - 1) * $limit;
            $next = $i * $limit;
            $next = $next > $num ? $num : $next;
            $result[$i]['text'] = '导出第' . $start . '-' . $next . '条数据';
            $result[$i]['url'] = '/products/bi_table?is_explore=1&page=' . $i . '&limit=' . $limit . '&offset=' . $start;
        }
        $this->Smarty->assign('list', $result);
        $html = $this->Smarty->fetch('order/download_model.html');
        $this->showJson(array('status' => 'success', 'html' => $html));
    }
}

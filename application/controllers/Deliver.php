<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Deliver extends MY_Controller
{
    public $workgroup = 'deliver';
    public $redis;
    public $send_batch_data = array(1=>'第一批次',2=>'第二批次',3=>'第三批次');

    function __construct() {
        parent::__construct();
        $this->load->model("deliver_model");
        $this->load->model("equipment_model");
        $this->load->model("equipment_label_model");
        $this->load->model("shipping_order_model");
        $this->load->model("product_model");
        $this->load->model("shipping_permission_model");
        $this->load->model("deliver_member_model");
        $this->load->model("admin_equipment_model");
        $this->load->library('phpredis');
        $this->redis = $this->phpredis->getConn();
    }

    //配送工单
    public function deliver_list(){
        $this->_pagedata['day'] = date('j');
        $this->page('deliver/deliver_list.html');
    }

    public function deliver_list_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:10;
        $offset     = $gets['offset']?$gets['offset']:0;
        $search_box_name     = $gets['search_box_name'];
        $search_originator_name  = $gets['search_originator_name'];
        $search_time_begin = $gets['search_time_begin'];
        $search_time_end = $gets['search_time_end'];

        $where = array(
            'd.platform_id'=>$this->platform_id
        );
        if(isset($search_box_name)){
            $where['e.name like'] = '%'.$search_box_name.'%';
        }
        if(isset($search_originator_name)){
            if(preg_match("/^1[0-9]{10}$/",$search_originator_name)){
                $where['a.mobile'] =  $search_originator_name;
            }else{
                $where['a.user_name like'] =  '%'.$search_originator_name.'%';
            }

        }
        if(isset($search_time_begin)){
            $where['d.time >='] = $search_time_begin;
        }
        if(isset($search_time_end)){
            $where['d.time <='] = $search_time_end;
        }

        $result = $this->deliver_model->get_list($where,$limit,$offset);
        $result['notify'] = $this->deliver_model->deliver_list_notify($this->platform_id, date('Y-m-d', $search_time_begin?strtotime($search_time_begin):time()), date('Y-m-d', strtotime('+1 day', $search_time_end? strtotime($search_time_end):time())));
        echo json_encode($result);
    }

    public function deliver_list_export(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        $gets = $this->input->get();
        $search_box_name = $gets['search_box_name'];
        $search_originator_name = $gets['search_originator_name'];
        $search_time_begin = $gets['search_time_begin'];
        $search_time_end = $gets['search_time_end'];

        $isTotal = $gets['isTotal'];

        $where = array(
            'd.platform_id' => $this->platform_id
        );
        if (isset($search_box_name)) {
            $where['e.name like'] = '%' . $search_box_name . '%';
        }
        if (isset($search_originator_name)) {
            if (preg_match("/^1[0-9]{10}$/", $search_originator_name)) {
                $where['a.mobile'] = $search_originator_name;
            } else {
                $where['a.user_name like'] = '%' . $search_originator_name . '%';
            }

        }
        if (isset($search_time_begin)) {
            $where['d.time >='] = $search_time_begin;
        }
        if (isset($search_time_end)) {
            $where['d.time <='] = $search_time_end;
        }
        $params = [];
        $params['where'] = $where;
        $params['pagesize'] = 3000;

        if($isTotal){
            $total = $this->deliver_model->get_list_export($params, true);
            $start = 1;
            $list = [];
            while ($total >$start){
                $list[] = [
                    'start' => $start,
                    'end' => $params['pagesize'] + $start - 1,
                ];
                $start += $params['pagesize'];
            }
            echo json_encode(['status'=>'y', 'data' => $list]);die;
        }

        $params['offset'] = !empty($gets['offset']) ? $gets['offset'] - 1 : 0;

        $result = $this->deliver_model->get_list_export($params);

        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $columns = [
            'A' => ['width' => 18, 'title' => '配送单号', 'field' => 'deliver_no',],
            'B' => ['width' => 25, 'title' => '设备名称', 'field' => 'name'],
            'C' => ['width' => 18, 'title' => '配送员', 'field' => 'user_name'],
            'D' => ['width' => 18, 'title' => '手机', 'field' => 'mobile'],
            'E' => ['width' => 18, 'title' => '创建时间', 'field' => 'time'],
            'F' => ['width' => 18, 'title' => '配送开始时间', 'field' => 'begin_time'],
            'G' => ['width' => 18, 'title' => '配送结束时间', 'field' => 'end_time'],
            'H' => ['width' => 5, 'title' => '类型', 'field' => 'type'],
            'I' => ['width' => 30, 'title' => '商品名称', 'field' => 'product_name'],
            'J' => ['width' => 5, 'title' => '数量', 'field' => 'qty']
        ];
        $sheet = $objPHPExcel->setActiveSheetIndex(0);
        $sheet->setTitle('配送工单商品');
        $line = 1;
        foreach ($columns as $k => $v) {
            $sheet->getColumnDimension($k)->setWidth($v['width']);
            $sheet->setCellValue("{$k}{$line}", $v['title']);
        }
        $line++;

        foreach ($result as $row) {
            foreach ($columns as $k => $v) {
                if ($v['field'] == 'type') {
                    $row[$v['field']] = $row[$v['field']] == 1 ? '上架' : '下架';
                }
                if($k == 'A'){
                    $sheet->getcell("{$k}{$line}")->setValueExplicit($row[$v['field']], PHPExcel_Cell_DataType::TYPE_STRING);
                }else{
                    $sheet->setCellValue("{$k}{$line}", $row[$v['field']]);
                }
            }
            $line++;
        }

        $objPHPExcel->initHeader('配送工单商品' . date('Y-m-d'));
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        die;
    }

    public function show($id){

        $deliver  = $this->deliver_model->get_info_by_id($id);
        if(empty($deliver)){
            redirect("/deliver/deliver_list");
        }else{
            $deliver_products = $this->deliver_model->get_deliver_products($id);
            $this->_pagedata['deliver'] = $deliver;
            $this->_pagedata['deliver_products'] = $deliver_products;
            $this->page('deliver/deliver_detail.html');
        }
    }



    //出库单逻辑
    public function shipping(){
        $this->_pagedata['store_list'] = $this->equipment_model->get_store_list();
        $this->page('deliver/shipping_equipment.html');
    }


    public function equipment_table()
    {
        $limit = $this->input->get('limit') ? : 10;
        $offset = $this->input->get('offset') ? : 0;
        $search_name = $this->input->get('search_name') ? : '';
        $search_admin_name = $this->input->get('search_admin_name') ? : '';
        $search_address = $this->input->get('search_address') ? : '';
        $search_province = $this->input->get('search_province') ? : '';
        $search_city = $this->input->get('search_city') ? : '';
        $search_area = $this->input->get('search_area') ? : '';
        $search_replenish_location = $this->input->get('search_replenish_location');

        $where = array();
        if ($search_name){
            $where['name'] = $search_name;
        }
        if ($search_admin_name){
            $where['alias'] = $search_admin_name;
        }
        if ($search_address) {
            $where['address'] = $search_address;
        }
        if ($search_province) {
            $where['province'] = $search_province;
        }
        if ($search_city){
            $where['city'] = $search_city;
        }
        if ($search_area){
            $where['area'] = $search_area;
        }
        $where['admin_id'] = $this->adminid;
        if($search_replenish_location && $search_replenish_location!=-1){
            $where['replenish_location'] = $search_replenish_location;
        }
        $array = $this->equipment_model->getEquipments("", $where, $offset, $limit, true, 'created_time', 'desc', false);

        $total = (int)$this->equipment_model->getEquipments("count(*) as c",$where)[0]['c'];

        $result = array(
            'total' => $total,
            'rows' => $array,
        );
        echo json_encode($result);
    }

    public function equipment_data()
    {
        $params = array_merge($_GET, $_POST);
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = isset($params['offset']) ? $params['offset'] : 0;
        $order_by = 'e.id desc';

        if (isset($params['sort'])) {
            $order_by = $params['sort'] . ' ' . $params['order'];
        }

        $where = [
            'e.platform_id' => $this->platform_id,
            'e.status !=' => '99',
            'ae.admin_id' => $this->adminid
        ];

        if (isset($params['search_name'])) {
            $params['name'] = trim($params['search_name']);
            $where['e.name like'] = "%" . $params['name'] . "%";
        }

        if (isset($params['search_admin_name'])) {
            $params['search_admin_name'] = trim($params['search_admin_name']);
            $where['a.alias like'] = "%" . $params['search_admin_name'] . "%";
        }

        if (isset($params['search_replenish_location']) && $params['search_replenish_location'] != -1) {
            $params['search_replenish_location'] = trim($params['search_replenish_location']);
            $where['e.replenish_location'] = $params['search_replenish_location'];
        }
        

        $total = $this->equipment_model->getEquipmentsForShipping(compact('where'), true);
        $rows = $this->equipment_model->getEquipmentsForShipping(compact('where', 'limit', 'offset', 'order_by'));

        echo json_encode(compact('total', 'rows'), JSON_UNESCAPED_UNICODE);
    }
    
    //添加出库单
    public function shipping_add_order($equipment_id, $stock_all){

        if (in_array($this->platform_id, [1, 2])) {
            $datetime_options = [];
            $i = -1;
            while (++$i < 8){
                $datetime_options[] = date('Y-m-d', strtotime("+{$i} day"));
            }
            $equipment = $this->db->select('id,name,equipment_id,is_auto,platform_id,level')->get_where('equipment', ['id' => $equipment_id, 'platform_id' => $this->platform_id])->row_array();
            if(empty($equipment)){
                echo '非法操作';die;
            }
            $shipping_order = $this->db->select('id,shipping_no,operation_id')->get_where('shipping_order', ['is_del' => 0, 'equipment_id' => $equipment['equipment_id'], 'send_date' => date('Y-m-d')])->result_array();
            $this->load->model('deliver_order_model');
            $equipment['max_qty'] = $this->deliver_order_model->get_equipment_max_qty($equipment);
            $this->title = '添加出库单';
            $this->_pagedata['datetime_options'] = $datetime_options;
            $this->_pagedata['equipment'] = $equipment;
            $this->_pagedata['so_name'] = Shipping_order_model::$operation_name;
            $this->_pagedata['day'] = date('j');
            $this->_pagedata['shipping_order'] = $shipping_order;
            $this->_pagedata['send_batch_options'] = $this->send_batch_data;
            $this->page('deliver_order/auto_replenish_add.html');
            return;
        }
        $equipment_id = $this->uri->segment(3);
        $stock_all = $this->uri->segment(4);
        $shipping_config_list = $this->deliver_model->get_deliver_shipping_list($equipment_id);
        $datetime = date('Y-m-d');
        $datetime2 = date('Y-m-d',strtotime("+1 day"));
        $datetime_tomorrow = date('Y-m-d',strtotime("+2 days"));
        $datetime3 = date('Y-m-d',strtotime("+3 days"));
        $datetime4 = date('Y-m-d',strtotime("+4 days"));
        $datetime5 = date('Y-m-d',strtotime("+5 days"));
        $datetime6 = date('Y-m-d',strtotime("+6 days"));
        $datetime7 = date('Y-m-d',strtotime("+7 days"));
        $datetime_options = array($datetime,$datetime2,$datetime_tomorrow,$datetime3,$datetime4,$datetime5,$datetime6,$datetime7);
        if (!$shipping_config_list){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("请先配置预存量！");history.back();</script></head>';
        }
        $equipment = $this->equipment_model->findById($equipment_id);
        $add_price = '';
        if ($equipment['add_price'] && $equipment['add_price'] > 1){
            $add_price = $equipment['add_price'];
        }
        $boxId = $equipment['equipment_id'];
        if ($equipment['admin_id'] <= 0){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("请先配置该设备的管理员！");history.back();</script></head>';
        }
        if (!$equipment['address']){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("请先配置该设备的地址！");history.back();</script></head>';
        }

        //获取实时库存
        $stock_data = $this->equipment_label_model->getStock($boxId);
        $new_stock_data = array();
        foreach ($stock_data as $stock){
            $new_stock_data[$stock['product_id']] = array('product_id'=>$stock['product_id'],'pre_num'=>$stock['count_num'],'product_name'=>$stock['product_name'],'price'=>$stock['price']);
        }
        //ksort($new_stock_data);
        //获取存量量
        $new_config_data = array();
        foreach($shipping_config_list as $config){
            $new_config_data[$config['product_id']] = array('product_id'=>$config['product_id'],'pre_num'=>$config['pre_qty'],'product_name'=>$config['product_name'],'price'=>$config['price']);
        }
        //ksort($new_config_data);
        $pre_data_keys = array_keys($new_config_data);
        $stock_data_keys = array_keys($new_stock_data);
        //var_dump($pre_data_keys,$stock_data_keys);
        $arr_table = array();
        //取交集   根据预存量补货
        /* $add1 = array_intersect($pre_data_keys,$stock_data_keys);
        
        if(!empty($add1)){
            foreach($add1 as $eachAdd){
                $add_num = $new_config_data[$eachAdd]['pre_num'] - $new_stock_data[$eachAdd]['pre_num'];
                $arr_table[] = array('product_id'=>$eachAdd,'product_name'=>$new_config_data[$eachAdd]['product_name'],'price'=>$new_config_data[$eachAdd]['price'],'pre_qty'=>$new_config_data[$eachAdd]['pre_num'],'stock'=>$new_stock_data[$eachAdd]['pre_num'], 'add_num'=>$add_num);
            }
        } */

        //取差集   注意数组前后顺序     新增的货品
        /* $add2 = array_diff($pre_data_keys, $stock_data_keys);
        if(!empty($add2)) {
            foreach ($add2 as $eachAdd) {
                $add_num = $new_config_data[$eachAdd]['pre_num'] - $new_stock_data[$eachAdd]['pre_num'];
                $arr_table[] = array('product_id' => $eachAdd, 'product_name' => $new_config_data[$eachAdd]['product_name'], 'price' => $new_config_data[$eachAdd]['price'], 'pre_qty' => $new_config_data[$eachAdd]['pre_num'], 'stock' => $new_stock_data[$eachAdd]['pre_num'], 'add_num' => $add_num);
            }
        } */
        
        foreach ($pre_data_keys as $eachAdd) {
            $add_num = $new_config_data[$eachAdd]['pre_num'] - $new_stock_data[$eachAdd]['pre_num'];
            $arr_table[] = array('product_id' => $eachAdd, 'product_name' => $new_config_data[$eachAdd]['product_name'], 'price' => $new_config_data[$eachAdd]['price'], 'pre_qty' => $new_config_data[$eachAdd]['pre_num'], 'stock' => $new_stock_data[$eachAdd]['pre_num'], 'add_num' => $add_num);
        }

        $add3 = array_diff($stock_data_keys,$pre_data_keys);
        if(!empty($add3)) {
            foreach ($add3 as $eachAdd) {
                $arr_table[] = array('product_id' => $eachAdd, 'product_name' => $new_stock_data[$eachAdd]['product_name'], 'price' => $new_stock_data[$eachAdd]['price'], 'pre_qty' => 0, 'stock' => $new_stock_data[$eachAdd]['pre_num'], 'add_num' => 0);
            }
        }

//        echo '<pre>';
//        print_r($new_stock_data);
//        print_r($stock_data);
//        print_r($add3);
//        print_r($stock_data_keys);
//        print_r($pre_data_keys);
//
//
//        exit;
        //根据补货量排序
        $arr_table = $this->arraySortByKey($arr_table,'add_num',false);
        if ($add_price){
            foreach($arr_table as $k=>$v){
                $arr_table[$k]['price'] = $v['price'] * $add_price;
            }
        }
        $arr_table = array_merge($arr_table);

        //仓库库存
        $this->load->model("deliver_order_model");
        $sw = $this->deliver_order_model->get_store_warehouse();
        $wh_stock = $this->deliver_order_model->get_warehouse_stock($equipment['replenish_warehouse'], $sw[$equipment['replenish_warehouse']]);
        foreach ($arr_table as &$prod){
            $prod['wh_stock'] = isset($wh_stock[$prod['product_id']]) ? $wh_stock[$prod['product_id']] : 0;
        }

        $this->_pagedata['datetime_options'] = $datetime_options;
        $this->_pagedata['send_batch_options'] = $this->send_batch_data;
        $this->_pagedata['arr_table'] = $arr_table;
        $this->_pagedata['equipment_id'] = $equipment_id;
        $this->_pagedata['name'] = $equipment['name'];
        $this->_pagedata['stock_all'] = $stock_all;
        $this->page('deliver/add_order.html');
        
        
    }

    public function shipping_add_order_save(){
        $equipment_id = $_POST['equipment_id'];
        $send_date = $_POST['send_date'];
        $send_batch = $_POST['send_batch'];
        $products = $_POST['products'];
        if (!$equipment_id || !$send_date || !$products){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("参数错误！");location.href="/equipment/index";</script></head>';
        }
        $shipping_no = $this->shipping_no('cb');
        $equipment = $this->equipment_model->findById($equipment_id);

        //仓库库存
        if($this->platform_id == 1){
            $this->load->model("deliver_order_model");
            $sw = $this->deliver_order_model->get_store_warehouse();
            $wh_stock = $this->deliver_order_model->get_warehouse_stock($equipment['replenish_warehouse'], $sw[$equipment['replenish_warehouse']]);

            foreach ($products as $prod){
                if($prod['add_num'] <= 0){
                    continue;
                }

                if(!isset($wh_stock[$prod['product_id']]) || $wh_stock[$prod['product_id']] < $prod['add_num']){
                    echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("'.$prod['product_name'] .'库存不足; 仓库库存:'.(int)$wh_stock[$prod['product_id']].'");history.back();</script></head>';die;
                }
            }
        }
        //是否纸质单
        /* if(!empty($equipment)){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("错误的设备号！");location.href="/equipment/index";</script></head>';
        } */
        $is_paper_order = $equipment['is_paper_order'];
        if ($is_paper_order == 0){
            $boxId = $equipment['equipment_id'];
            $total_amount = 0.00;
            $total_amount_paper = 0.00;
            $this->db->trans_begin();
            //中台订单
            $is_zt = 0;
            //纸质订单
            $is_paper = 0;
            //中台订单的产品id数组
            $zt_array = array();
            //纸质订单的产品id数组
            $paper_array = array();
            foreach($products as $product){
                if ($product['add_num'] != '' && $product['add_num'] != 0){
                    $productModel = $this->product_model->findById($product['product_id']);
                    if ($productModel['inner_code'] != ''){
                        $is_zt = 1;
                        $zt_array[] = $product['product_id'];
                        $total_amount = $total_amount + $product['price'] * $product['add_num'];
                    } else {
                        $paper_array[] = $product['product_id'];
                        $is_paper = 1;
                        $total_amount_paper = $total_amount_paper + $product['price'] * $product['add_num'];
                    }
                }
            }
            $deliver_time = '09:00-12:00';
            //生成中台订单表和订单地址表
            if ($is_zt == 1){
                $data = array(
                    'shipping_no'=>$shipping_no,
                    'time'=>date('Y-m-d H:i:s'),
                    'status'=>0,
                    'operation_id'=>0,
                    'equipment_id'=>$boxId, //equipment表equipment_id
                    'total_amount'=>$total_amount,
                    'send_date'=>$send_date,
                    'send_batch'=>$send_batch,
                    'deliver_time'=>$deliver_time,
                    'is_paper_order'=>0
                );
                $order_id = $this->shipping_order_model->insertData($data);
                $address_data = array(
                    'order_id'=>$order_id,
                    'address'=>$equipment['address'],
                    'name'=>$equipment['admin_alias'],
                    'mobile'=>$equipment['mobile'],
                    'province'=>$equipment['province'],
                    'city'=>$equipment['city'],
                    'area'=>$equipment['area']
                );
                $address_rs = $this->shipping_order_model->insertAddressData($address_data);
            }
            //生成纸质订单表和订单地址表
            if ($is_paper == 1){
                $shipping_no_paper = $this->shipping_no('cbp');
                $data = array(
                    'shipping_no'=>$shipping_no_paper,
                    'time'=>date('Y-m-d H:i:s'),
                    'status'=>1,
                    'operation_id'=>0,
                    'equipment_id'=>$boxId, //equipment表equipment_id
                    'total_amount'=>$total_amount_paper,
                    'send_date'=>$send_date,
                    'send_batch'=>$send_batch,
                    'deliver_time'=>$deliver_time,
                    'is_paper_order'=>1
                );
                $order_id_paper = $this->shipping_order_model->insertData($data);
                $address_data = array(
                    'order_id'=>$order_id_paper,
                    'address'=>$equipment['address'],
                    'name'=>$equipment['admin_alias'],
                    'mobile'=>$equipment['mobile'],
                    'province'=>$equipment['province'],
                    'city'=>$equipment['city'],
                    'area'=>$equipment['area']
                );
                $address_rs_paper = $this->shipping_order_model->insertAddressData($address_data);
            }

            foreach($products as $product){
                if ($product['add_num'] != '' && $product['add_num'] != 0){
                    //中台订单产品表
                    if (in_array($product['product_id'],$zt_array)){
                        $product_data = array(
                            'order_id'=>$order_id,
                            'product_id'=>$product['product_id'],
                            'product_name'=>$product['product_name'],
                            'price'=>$product['price'],
                            'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
                            'stock'=>$product['stock'] ? $product['stock'] : 0,
                            'add_qty'=>$product['add_num'],
                            'real_num'=>0,
                            'total_money'=>$product['price'] * $product['add_num']
                        );
                        $product_rs = $this->shipping_order_model->insertProductsData($product_data);
                    }
                    //纸质订单产品表
                    if (in_array($product['product_id'],$paper_array)){
                        $product_data = array(
                            'order_id'=>$order_id_paper,
                            'product_id'=>$product['product_id'],
                            'product_name'=>$product['product_name'],
                            'price'=>$product['price'],
                            'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
                            'stock'=>$product['stock'] ? $product['stock'] : 0,
                            'add_qty'=>$product['add_num'],
                            'real_num'=>0,
                            'total_money'=>$product['price'] * $product['add_num']
                        );
                        $product_rs_paper = $this->shipping_order_model->insertProductsData($product_data);
                    }
                }


            }
            if ($this->db->trans_status() === FALSE) {
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("生成出库单失败！");history.back();</script></head>';
                $this->db->trans_rollback();
            } else {
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("生成出库单成功！");history.back();</script></head>';
                $this->db->trans_commit();
            }
            if ($is_zt == 1){
                //打中台订单
                $orders = array(array(
                    'type'=>99,
                    'chId'=>$equipment['is_self_type'] == 99 ? 20 : 21,
                    'orderNo'=>$shipping_no,
                    'createdate'=>date('Y-m-d H:i:s'),
                    'payDate'=>date('Y-m-d H:i:s'),
                    'sendDate'=>$send_date,
                    'delivertime'=>'09:00-12:00',
                    'score'=>0,
                    'note'=>'',
                    'orderAmount'=>$total_amount,
                    'freightFee'=>0,
                    'totalAmount'=>$total_amount,
                    'disamount'=>0,
                    'dedamount'=>0,
                    'payDiscount'=>0,
                    'bankDiscount'=>0,
                    'ispay'=>1,
                    'payment'=>0,
                    'status'=>0,
                    'invoice_info'=>null,
                    'member_info'=>null,
                    'consignee_info'=>array(
                        'reAddress'=>$equipment['address'],
                        'receiver'=>$equipment['name'],
                        'reMobile'=>$equipment['mobile'],
                        'lonlat'=>$equipment['baidu_xyz']
                    ),
                    'payment_info'=>null,
                    'sapCode'=>$equipment['replenish_location'],
                    'merchantNo'=>'FD003',
                    'groupNo'=>$shipping_no,
                    'sendType'=>'1',
                    'transport_type'=>$equipment['send_type']
                ));
                foreach($products as $product){
                    $product_model = $this->product_model->findById($product['product_id']);
                    if ($product_model['inner_code'] != ''){
                        if ($product['add_num'] != 0 && $product['add_num'] != ''){
                            $orders[0]['order_items'][] = array(
                                'prdno'=>$product_model['product_no'],
                                'prdName'=>$product['product_name'],
                                'barcode'=>'',
                                'price'=>$product['price'],
                                'discount'=>'0.000',
                                'disPrice'=>$product['price'],
                                'count'=>$product['add_num'],
                                'unitWeight'=>0,
                                'qty'=>$product['add_num'],
                                'cardAmount'=>0,
                                'totalAmount'=>$product['price'] * $product['add_num'],
                                'distype'=>2,
                                'score'=>0,
                                'volume'=>$product_model['volume'],
                                'cardDiscount'=>0,
                                'saletype'=>1,
                                'innerCode'=>$product_model['inner_code'],
                                'groups'=>[]
                            );
                        }
                    }

                }
                $params = array(
                    'url' => 'http://open-api.fruitday.com/api',
                    'method' => 'order.save',
                    'data' => $orders,
                );
                $response = $this->realtime_call($params,6);
                //增加数据库记录日志
            }
        } else {//直接纸质单流程
            $boxId = $equipment['equipment_id'];
            $total_amount_paper = 0.00;
            $this->db->trans_begin();
            //纸质订单
            foreach($products as $product){
                if ($product['add_num'] > 0){
                    $total_amount_paper = $total_amount_paper + $product['price'] * $product['add_num'];
                }
                
            }
            $deliver_time = '09:00-12:00';
            //生成纸质订单表和订单地址表
            $shipping_no_paper = 'cbp'.date("ymdi").$this->rand_code(4);
            $data = array(
                'shipping_no'=>$shipping_no_paper,
                'time'=>date('Y-m-d H:i:s'),
                'status'=>in_array($this->platform_id, [10, 5]) ? 0:1,
                'operation_id'=>0,
                'equipment_id'=>$boxId, //equipment表equipment_id
                'total_amount'=>$total_amount_paper,
                'send_date'=>$send_date,
                'send_batch'=>$send_batch,
                'deliver_time'=>$deliver_time,
                'is_paper_order'=>1
            );
            $order_id_paper = $this->shipping_order_model->insertData($data);
            $address_data = array(
                'order_id'=>$order_id_paper,
                'address'=>$equipment['address'],
                'name'=>$equipment['admin_alias'],
                'mobile'=>$equipment['mobile'],
                'province'=>$equipment['province'],
                'city'=>$equipment['city'],
                'area'=>$equipment['area']
            );
            $address_rs_paper = $this->shipping_order_model->insertAddressData($address_data);

            foreach($products as $product){
                if ($product['add_num'] > 0){
                    //纸质订单产品表
                    $product_data = array(
                        'order_id'=>$order_id_paper,
                        'product_id'=>$product['product_id'],
                        'product_name'=>$product['product_name'],
                        'price'=>$product['price'],
                        'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
                        'stock'=>$product['stock'] ? $product['stock'] : 0,
                        'add_qty'=>$product['add_num'],
                        'real_num'=>0,
                        'total_money'=>$product['price'] * $product['add_num']
                    );
                    $product_rs_paper = $this->shipping_order_model->insertProductsData($product_data);
                }


            }
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("生成出库单失败！");history.back();</script></head>';

            } else {
                $this->db->trans_commit();
                echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("生成出库单成功！");history.back();</script></head>';
            }
        }
        
        redirect('/deliver/shipping');
    }
    
    public function shipping_view_order($equipment_id){
        $equipment = $this->equipment_model->findById($equipment_id);
        redirect('deliver/shipping_order_list?search_name='.$equipment['name']);
//        var_dump($equipment);exit;
//        $boxId = $equipment['equipment_id'];
//        $this->_pagedata['equipment_id'] = $boxId;
//        $this->page('deliver/view_order.html');
    }
    
    public function shipping_view_order_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:10;
        $offset     = $gets['offset']?$gets['offset']:0;
        $search_equipment_id = $this->uri->segment(3);
        //$search_equipment_id     = $gets['equipment_id'];
            
        $where = array();
        if(isset($search_equipment_id)){
            $where['equipment_id'] = $search_equipment_id;
        }
        
        $result = $this->shipping_order_model->get_list($where,$limit,$offset);
        echo json_encode($result);
    }
    
    public function shipping_view_order_products($order_id){
        $this->_pagedata['shipping_order'] = $this->shipping_order_model->getById($order_id);
        $this->page('deliver/view_order_products.html');
    }

    public function shipping_edit_order_products($order_id){
        $shipping_order= $this->shipping_order_model->getById($order_id);
        $this->load->helper('public');
        if($shipping_order['operation_id']!=0){
            header("Content-Type:text/html;charset=utf-8");
            echo ('已出库的配送单无法编辑');
        }else{

            $equipment_pri_id = $this->db->select('id,replenish_warehouse')->from('equipment')->where(array('equipment_id'=>$shipping_order['equipment_id']))->get()->row_array();
            if(!empty($shipping_order['products'])) {
                //仓库库存
                $this->load->model("warehouse_model");
                $wh_stock = $this->warehouse_model->get_stock([
                    'replenish_warehouse' => $equipment_pri_id['replenish_warehouse'],
                    'product_id' => array_column($shipping_order['products'], 'product_id')
                ]);
                foreach ($shipping_order['products'] as &$prod) {
                    $prod['wh_stock'] = isset($wh_stock[$prod['product_id']]) ? $wh_stock[$prod['product_id']] : [];
                }
            }
            $this->_pagedata['shipping_order'] = $shipping_order;
            $this->_pagedata['equipment_pri_id'] = $equipment_pri_id['id'];
            $this->page('deliver/edit_order_products.html');
        }
    }

    public function shipping_add_batch_order_products($ids){
        $decode_ids = urldecode($ids);
        $ids_arr = explode('|',$decode_ids);
        $ids_arr = array_filter($ids_arr);
        $shipping_order= $this->shipping_order_model->getOrderInfoByWhereIn($ids_arr);
        $shipping_nos = "";

        if(!empty($shipping_order)){
            foreach ($shipping_order as $v){
                $shipping_nos.=$v['shipping_no'].",";
                if($v['operation_id'] != 0){
                    $cannt_do = true;
                    break;
                }
            }
        }
        if($cannt_do){
            header("Content-Type:text/html;charset=utf-8");
            echo ('已出库的配送单无法编辑');
        }else{
//            var_dump($shipping_order);exit;
            $equipment = $this->db->get_where('equipment', ['equipment_id' => $shipping_order[0]['equipment_id']])->row_array();
            $this->_pagedata['choose_num'] = count($ids_arr);
            $this->_pagedata['choose_orders'] = trim($shipping_nos,",");
            $this->_pagedata['replenish_warehouse'] = $equipment['replenish_warehouse'];
            $this->_pagedata['ids'] = $ids;
            $this->page('deliver/add_orders_products.html');
        }
    }

    //提交
    public function shipping_do_insert_batch_order_products(){
        $post = $this->input->post();

        $shipping_order_product_data = $add_pro_info = array();

        if(!empty($post['product_id'])){
            foreach($post['product_id'] as $k=>$v){
                $add_pro_info[] = array(
                    'product_id'=>$post['product_id'][$k],
                    'product_name'=>$post['product_name'][$k],
                    'price'=>$post['price'][$k],
                    'pre_qty'=>0,
                    'stock'=>0,
                    'add_qty'=>$post['add_num'][$k],
                    'real_num'=>0,
                    'total_money'=>bcmul($post['price'][$k],$post['add_num'][$k],2)
                );
            }
        }else{
            $this->validform_fail(array('msg'=>'保存失败，商品数据不能为空'));
        }

        $decode_ids = urldecode($post['choose_ids']);
        $ids_arr = explode('|',$decode_ids);
        $ids_arr = array_filter($ids_arr);
        //获取设备的溢价状态
        $orders = $this->shipping_order_model->getShippingOrderPrice($ids_arr);
        $order_add = array();
        foreach($orders as $v){
            $order_add[$v['id']] = $v['add_price'];
        }

        if (in_array($this->platform_id, [1, 2])) {
            // $this->db->select('p.product_id, sum(p.add_qty) add_qty')
            //     ->from('shipping_order o')
            //     ->join('shipping_order_products p', 'p.order_id=o.id')
            //     ->where_in('o.id', $ids_arr)
            //     ->where_in('p.product_id', $post['product_id']);
            // $o_products = $this->db->get()->result_array();
            // $this->load->helper('public');
            // $o_products = array_column($o_products, 'add_qty', 'product_id');
            //仓库库存
            $this->load->model("warehouse_model");
            $wh_stock = $this->warehouse_model->get_stock([
                'replenish_warehouse' => $orders[0]['replenish_warehouse'],
                'product_id' => $post['product_id']
            ]);
            $total_items = count($ids_arr);
            foreach ($add_pro_info as $v){
                if($v['add_qty'] * $total_items > (int)$wh_stock[$v['product_id']]['stock_usable']){
                    $this->validform_fail(array('msg'=>$v['product_name'] . '可用库存不足: ' . (int)$wh_stock[$v['product_id']]['stock_usable']));
                }
            }
        }

        $this->db->trans_strict(FALSE);
        $this->db->trans_begin();

        if(!empty($ids_arr)){
            foreach ($ids_arr as $id){
                $add_price = $order_add[$id];
                foreach ($add_pro_info as $v){
                    //溢价设备
                    if ($add_price > 1){
                        $v['price'] = bcmul($v['price'],$add_price,2);
                        $v['total_money'] = bcmul($v['total_money'],$add_price,2);
                    }
                    //update_shipping_order_product_set
                    //get_shipping_order_product_info
                    $is_set = $this->shipping_order_model->get_shipping_order_product_info(array(
                        'order_id'=>$id,
                        'product_id'=>$v['product_id']
                    ));

                    if($is_set){//存在则更新
                        $data = array(
                            'add_qty'=>$v['add_qty'],
                            'total_money'=>$v['total_money']
                        );
                        $this->shipping_order_model->update_shipping_order_product_set($data,array(
                            'order_id'=>$id,
                            'product_id'=>$v['product_id']
                        ));
//                        echo $this->db->last_query();
                    }else{
                        $shipping_order_product_data[]=$v+array('order_id'=>$id);
                    }
                }
            }
        }else{
            $this->validform_fail(array('msg'=>'保存失败，订单号数据不能为空'));
        }

        if(!empty($shipping_order_product_data))
            $rs = $this->shipping_order_model->insertProductsBatchData($shipping_order_product_data);

        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            $this->validform_fail(array('msg'=>'保存失败，请稍候重试'));
        } else {
            $this->db->trans_commit();
            $this->validform_success(array('msg'=>'保存成功','to_url'=>'/deliver/shipping_order_list'));
        }
    }

    //预存量信息的操作
    public function shipping_config($equipment_id){
        $this->_pagedata['is_auto'] = $this->input->get('is_auto') == 1 ? 1 : 0;
        $this->_pagedata['equipment_id'] = $equipment_id;
        $this->display('deliver/shipping_config.html');
    }

    public function config_do_edit(){
        $post = $this->input->post();
        $data = array('pre_qty'=>$post['pre_qty']);
        $where = array('id'=>$post['cid']);
        $r = $this->deliver_model->edit_shipping_config($data,$where);
        if($r){
            $this->showJson(array('status'=>'success', 'msg' => '操作成功！'));
        }
    }

    public function config_do_del($id){
        $where=array('id'=>$id);
        $r = $this->deliver_model->del_shipping_config($where);
        if($r){
            $this->showJson(array('status'=>'success', 'msg' => '操作成功！'));
        }
    }

    public function config_do_add(){
        $post = $this->input->post();
        if(!is_numeric($post['product_id'])||empty($post['product_id'])){
            $this->showJson(array('status'=>'error', 'msg' => '请正确输入商品id！'));
        }
        if(!is_numeric($post['pre_qty'])||empty($post['pre_qty'])){
            $this->showJson(array('status'=>'error', 'msg' => '请正确输入商品的预存量！'));
        }
        if(empty($post['equipment_id'])){
            $this->showJson(array('status'=>'error', 'msg' => '系统参数确实，请联系管理员！'));
        }
        $this->load->model("product_model");
        $product_id = $post['product_id'];
        $equipment_id = $post['equipment_id'];
        $product = $this->product_model->getProduct("id,product_name",array('id'=>$product_id));
        if(empty($product))
            $this->showJson(array('status'=>'error', 'msg' => '商品不存在.'));

        $rs = $this->deliver_model->is_added_pre_product(array('product_id'=>$product_id,'equipment_id'=>$equipment_id));
        if($rs){
            $this->showJson(array('status'=>'error', 'msg' => '该商品已存在.'));
        }


        $data = array(
            'equipment_id'=>$equipment_id,
            'product_id'=>$product['id'],
            'product_name'=>$product['product_name'],
            'pre_qty'=>$post['pre_qty']
        );

//        var_dump($data);exit;
        $r = $this->deliver_model->add_shipping_config($data);
        if($r){
            $this->showJson(array('status'=>'success', 'msg' => '操作成功！','info'=>$data));
        }else{
            $this->showJson(array('status'=>'success', 'msg' => '系统异常！'));
        }
    }
    
    public function config_copy(){
        $post = $this->input->post();
        if(empty($post['equipment_id'])){
            $this->showJson(array('status'=>'error', 'msg' => '系统参数缺失，请联系管理员！'));
        }
        if(empty($post['codes'])){
            $this->showJson(array('status'=>'error', 'msg' => '请输入要复制到的设备编码！'));
        }
        //获取当前设备的预存量
        $configs = $this->deliver_model->get_shipping_config($post['equipment_id']);
        $codes = explode(',',$post['codes']);
        foreach ($codes as $code){
            //查看code是否存在以及是否有权限
            $equipment = $this->equipment_model->findByBoxCode($code);
            if (!$equipment){
                $this->showJson(array('status'=>'error', 'msg' => ''.$code.'该设备不存在！请重新输入！'));
            }
            $equipment_id = $equipment['equipment_id'];
            $ifAdminEquipment = $this->admin_equipment_model->ifAdminEquipment($this->adminid,$equipment_id);
            if (!$ifAdminEquipment){
                $this->showJson(array('status'=>'error', 'msg' => '你没有'.$code.'该设备的管理权限！不能复制到此设备！'));
            }
            if ($equipment['id'] == $post['equipment_id']){
                $this->showJson(array('status'=>'error', 'msg' => '不能复制到当前设备！'));
            }
            //删除此设备之前的预存量
            $this->db->delete('shipping_config',array('equipment_id'=>$equipment['id']));
            //插入当前设备的预存量
            $data = array();
            foreach($configs as $config){
                $data['equipment_id'] = $equipment['id'];
                $data['product_id'] = $config['product_id'];
                $data['product_name'] = $config['product_name'];
                $data['pre_qty'] = $config['pre_qty'];
                $data['platform_id'] = $this->platform_id;
                $this->db->insert('shipping_config',$data);
            }
        }
        
        
        $this->showJson(array('status'=>'success', 'msg' => '操作成功！'));
    }

    /*
     * todo list
     *
     * 1.出库单列表页
     * 2.出库单的增删改、推送功能按钮
     * 3.导出已经完成的出库单的功能
     *
     */

    //demo生成库存单
    /* public function demo(){
        //预存量arr
        $pre_data = array(
            111=>array(
                'product_id'=>111,
                'pre_num'=>10,
            ),
            112=>array(
                'product_id'=>112,
                'pre_num'=>10,
            ),
            113=>array(
                'product_id'=>113,
                'pre_num'=>10
            )
        );

        //库存量arr
        $stock_data = array(
            111=>array(
                'product_id'=>111,
                'pre_num'=>5,
            ),
            112=>array(
                'product_id'=>112,
                'pre_num'=>10,
            ),
            114=>array(
                'product_id'=>114,
                'pre_num'=>10,
            )
        );

        $pre_data_keys = array_keys($pre_data);
        $stock_data_keys = array_keys($stock_data);
        //var_dump($pre_data_keys,$stock_data_keys);

        //取交集   根据预存量补货
        var_dump(array_intersect($pre_data_keys,$stock_data_keys));

        //取差集   注意数组前后顺序     新增的货品
        var_dump(array_diff($pre_data_keys,$stock_data_keys));


        //库存多的数组无需处理

    } */
    
    //往sap那边打接口
    public function test_api(){
        $orders = array(array(
            'type'=>99,
            'chId'=>20,
            'orderNo'=>'cb11111',
            'createdate'=>'2017-05-10 12:00:00',
            'payDate'=>'2017-05-10 12:00:00',
            'sendDate'=>'2017-05-11',
            'delivertime'=>'08:00-09:00',
            'score'=>0,
            'note'=>'',
            'orderAmount'=>0.2,
            'freightFee'=>0,
            'totalAmount'=>0.2,
            'disamount'=>0,
            'dedamount'=>0,
            'payDiscount'=>0,
            'bankDiscount'=>0,
            'ispay'=>1,
            'payment'=>0,
            'status'=>0,
            'invoice_info'=>null,
            'member_info'=>null,
            'consignee_info'=>array(
                'reAddress'=>'上海长宁区天山路341号',
                'receiver'=>'admin',
                'reMobile'=>'18621790326',
                'lonlat'=>''
            ),
            'order_items'=>array(
                array(
                    'prdno'=>'20723798',
                    'prdName'=>'牛奶巧克力威化饼干',
                    'barcode'=>'',
                    'price'=>0.02,
                    'discount'=>'0.000',
                    'disPrice'=>0.02,
                    'count'=>5,
                    'unitWeight'=>0,
                    'qty'=>5,
                    'cardAmount'=>0,
                    'totalAmount'=>'0.1',
                    'distype'=>2,
                    'score'=>0,
                    'volume'=>'153克',
                    'cardDiscount'=>0,
                    'saletype'=>1,
                    'innerCode'=>'20943356',
                    'groups'=>[]
                ),
                array(
                    'prdno'=>'2170220105',
                    'prdName'=>'苏食童子鸡',
                    'barcode'=>'',
                    'price'=>0.02,
                    'discount'=>'0.000',
                    'disPrice'=>0.02,
                    'count'=>5,
                    'unitWeight'=>0,
                    'qty'=>5,
                    'cardAmount'=>0,
                    'totalAmount'=>'0.1',
                    'distype'=>2,
                    'score'=>0,
                    'volume'=>'',
                    'cardDiscount'=>0,
                    'saletype'=>1,
                    'innerCode'=>'21032509',
                    'groups'=>[]
                )
            ),
            'payment_info'=>null,
            'sapCode'=>'H001',
            'merchantNo'=>'FD003',
            'groupNo'=>'cb11111',
            'sendType'=>'1',
            'transport_type'=>'car'    
        ));
        $params = array(
            'url' => 'http://open-api.test.fruitday.com/api',
            'method' => 'order.save',
            'data' => $orders,
        );
        $response = $this->realtime_call($params,6);
        var_dump($response);
    }
    
    /**
     * 请求
     *
     * @param $params ['url']
     * @param $params ['appId']
     * @param $params ['method']
     * @param $params ['v']
     * @param $params ['jsonData']
     * @param $params ['secret']
     * @param $params ['cnone']
     * @param $params ['timestamp']
     *
     * @param int $timeout
     * @return mixed
     */
    public function realtime_call($params, $timeout = 6)
    {
        $this->load->library('poolhash');
        $this->load->library('aes', null, 'encrypt_aes');
        $this->load->library('curl', null, 'http_curl');
    
        $nParams = array(
            'appId'     => !empty($params['appId'])     ? $params['appId'] : POOL_O2O_OMS_APPID,
            'cnone'     => !empty($params['cnone'])     ? $params['cnone'] : $this->gen_uuid(),
            'timestamp' => !empty($params['timestamp']) ? $params['timestamp'] : date('Y-m-d H:i:s'),
            'method'    => !empty($params['method'])    ? $params['method'] : '',
            'v'         => !empty($params['v'])         ? $params['v'] : POOL_O2O_OMS_VERSION,
            'data'      => is_array($params['data'])    ? json_encode($params['data'], JSON_UNESCAPED_UNICODE) : $params['data'],
        );
    
        $secret = !empty($params['secret']) ? $params['secret'] : POOL_O2O_OMS_SECRET;
        unset($params['secret']);
    
        $nParams['sign'] = $this->poolhash->create_sign($nParams, $secret);
        $nParams['data'] = urlencode($this->encrypt_aes->AesEncrypt($nParams['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY)));
    
        $options['timeout'] = $timeout;
        if(defined('OPEN_CURL_PROXY') && OPEN_CURL_PROXY === true && defined('CURL_PROXY_ADDR') && defined('CURL_PROXY_PORT')){
            $options['proxy'] = CURL_PROXY_ADDR.":".CURL_PROXY_PORT;
        }
        $rs = $this->http_curl->request($params['url'], $nParams, 'POST', $options);
    
        if ($rs['errorNumber'] || $rs['errorMessage']) {
    
            $this->_error = array('errorNumber' => $rs['errorNumber'], 'errorMessage' => $rs['errorMessage']);
    
            //$this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
            return false;
        }
    
        $response = json_decode($rs['response'], true);
    
        if ($response['code'] != '1000' && $response['code'] != '200') {
            $this->_error = array('errorNumber' => $response['code'], 'errorMessage' => $response['msg']);
    
            //$this->insert_log($params['url'], $params['data'], $nParams['data'], 'fail');
            return false;
        }
        if(!empty($response['data'])){
            $response['data'] = urldecode($response['data']);
            $data = $this->encrypt_aes->AesDecrypt($response['data'], !empty($params['aesKey']) ? $params['aesKey'] : base64_decode(POOL_O2O_AES_KEY));
            //$data = $response['data'];
    
            $data = json_decode($data, true);
        }else{
            $data = true;
        }
    
        //$this->insert_log($params['url'], $params['data'], $nParams['data'], 'succ');
    
        return $data;
    }
    
    private function gen_uuid() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
    
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
    
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
    
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
    
    private function rand_code($length=6) {
        $code="";
        for($i=0;$i<$length;$i++) {
            $code .= mt_rand(0,9);
        }
        return $code;
    }

    public function shipping_no($pre)
    {
        while (true) {
            $shipping_no = $pre.date("ymdi").$this->rand_code(4);
            $rs = $this->db->get_where("shipping_order", ["shipping_no" => $shipping_no])->row();
            if (empty($rs)) {
                return $shipping_no;
            }
        }
    }


    public function permission(){
        $this->page('deliver/shipping_permission_list.html');
    }

    public function permission_list_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:10;
        $offset     = $gets['offset']?$gets['offset']:0;

        $search_box_name     = $gets['search_mobile'];

        $where = array('platform_id'=>$this->platform_id);
        if(isset($search_box_name)){
            $where['mobile like'] = '%'.$search_box_name.'%';
        }
        $result = $this->shipping_permission_model->get_list($where,$limit,$offset);
        echo json_encode($result);
    }

    public function add_permission(){
        $now = date('Y-m-d');
        $equipment_list = $this->equipment_model->get_equipments();
        $this->_pagedata['equipment_list'] = $equipment_list;
        if ($this->input->post("submit")) {
            $post = $this->input->post();
            if(!is_numeric($post['mobile'])||empty($post['mobile'])){
                $this->_pagedata["tips"] = "请正确输入配送员手机号!";
            }
            if(empty($post['add_time'])){
                $this->_pagedata["tips"] = "请正确输入起始时间!";
            }
            if(empty($post['end_time'])){
                $this->_pagedata["tips"] = "请正确输入结束时间!";
            }
//            if(empty($post['equipment_id'])){
//                $this->_pagedata["tips"] = "请正确输入需要增加权限的设备号!";
//            }
            if(count($post['box_no'])<=0){
                $this->_pagedata["tips"] = "请选择设备!";
            }

//            $arr_equipment_id = explode(',',$post['equipment_id']);
            $arr_equipment_id = $post['box_no'];
            foreach($arr_equipment_id as $v){
                $rs = $this->equipment_model->dump(array("equipment_id"=>$v));
                if(empty($rs)){
                    $this->_pagedata["tips"] = "请正确输入需要增加权限的设备号";
                    continue;
                }else{
                    $data[] = array(
                        'equipment_id'=>$v,
                        'mobile'=>$post['mobile'],
                        'add_time'=>$post['add_time'],
                        'end_time'=>$post['end_time']." 23:59:59",
                        'status'=>1,
                        "platform_id"=>$this->platform_id,
                        "old_deliver"=>$post['old_deliver']
                    );
                }
            }
            if($this->_pagedata["tips"]){

            }elseif(!empty($data)){
                $r = $this->shipping_permission_model->insertBatchPermissionData($data);
                if($r){
                    $this->_pagedata["tips"] = "新增成功";
                }else{
                    $this->_pagedata["tips"] = "操作失败，请联系管理员";
                }
            }
        }

        $this->_pagedata["add_time"] = $now;
        $this->page('deliver/shipping_permission_add.html');
    }

    public function unset_permission(){
        $id = $this->input->post('id');
        if(!isset($id)){
            $this->showJson(array('status'=>'error', 'msg' => '缺少必要参数.'));
        }
        $where = array("id"=>$id,"platform_id"=>$this->platform_id);
        $data = array("status"=>0);
        $rs = $this->shipping_permission_model->update($where,$data);
        if($rs){
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        }else{
            $this->showJson(array('status'=>'error', 'msg' => '操作失败请稍候再试.'));
        }
    }

    public function member(){
        $this->page('deliver/member_list.html');
    }

    public function member_list_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:10;
        $offset     = $gets['offset']?$gets['offset']:0;

        $search_mobile     = $gets['search_mobile'];
        $search_name = $gets['search_name'];

        $where = array("platform_id"=>$this->platform_id);
        if(isset($search_mobile)){
            $where['mobile like'] = '%'.$search_mobile.'%';
        }
        if(isset($search_name)){
            $where['name like'] = '%'.$search_name.'%';
        }
        $result = $this->deliver_member_model->get_list($where,$limit,$offset);
        echo json_encode($result);
    }

    public function add_member(){
        if ($this->input->post("submit")) {
            $post = $this->input->post();
            $name = trim($post['name']);
            $mobile = trim($post['mobile']);
            if($this->deliver_member_model->check_is_same(array(
                'name'=>$name,
                'mobile'=>$mobile
            ))){
                $this->_pagedata["tips"] = "重复的配送员信息！";
            }else{
                $data = array(
                    'mobile'=>$mobile,
                    'name'=>$name,
                    'time'=>date('Y-m-d H:i:s'),
                    'replenish_location'=>$post['replenish_location'],
                    'platform_id'=>$this->platform_id
                );
                $r = $this->deliver_member_model->insert($data);
                if($r){
                    $this->_pagedata["tips"] = "保存成功";
                }else{
                    $this->_pagedata["tips"] = "操作失败，请联系管理员！";
                }
            }
        }
        $stores = $this->equipment_model->get_store_list();
        $this->_pagedata['stores'] = $stores;
        $this->_pagedata['is_add'] = 1;
        $this->page('deliver/member_edit.html');
    }

    public function edit_member($id){
        $rs = $this->deliver_member_model->dump(array(
            'id'=>$id
        ));
        $stores = $this->equipment_model->get_store_list();

        if ($this->input->post("submit")) {
            $post = $this->input->post();

            $name = trim($post['name']);
            $mobile = trim($post['mobile']);
            $is = $this->deliver_member_model->check_is_same(array(
                'name'=>$name,
                'mobile'=>$mobile
            ));
             if($is&&$is['id']!=$id){
                 $this->_pagedata["tips"] = "重复的配送员信息！";
             }else {
                $data = array(
                    'mobile' => $mobile,
                    'name' => $name,
                    'replenish_location' => $post['replenish_location']
                );
                $r = $this->deliver_member_model->update($data, array('id' => $id));
                if ($r) {
                    $this->_pagedata["tips"] = "保存成功";
                } else {
                    $this->_pagedata["tips"] = "操作失败，请联系管理员！";
                }
             }
        }

        $this->_pagedata['stores'] = $stores;
        $this->_pagedata['info'] = $rs;
        $this->_pagedata['id'] = $id;
        $this->page('deliver/member_edit.html');
    }

    public function ajax_is_same_deliver_member_name(){
        $posts = $this->input->post();
        $is = $this->deliver_member_model->check_is_same(array(
            'name'=>$posts['name']
        ));
        if($is)
            echo json_encode(array('code'=>300,'msg'=>'配送员姓名重复！建议修改为其他别名'));
        else{
            echo json_encode(array('code'=>200));
        }
    }

    public function shipping_order_list(){
        $search_name = $this->input->get('search_name');
        if($search_name)
            $this->_pagedata['search_name'] = $search_name;
        $this->_pagedata['store_list'] = $this->equipment_model->get_store_list();
        $this->_pagedata['deliver_member_list'] = $this->deliver_member_model->get_member_list();
        $this->_pagedata['send_batch_options'] = $this->send_batch_data;
        $this->_pagedata['platform_id'] = $this->platform_id;
//        print_r($this->deliver_member_model->get_member_list());exit;
        $this->page('deliver/shipping_order_list.html');
    }

    public function shipping_order_list_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:50;
        $offset     = $gets['offset']?$gets['offset']:0;

        $search_name = $gets['search_name'];
        $search_address = $this->input->get('search_address') ? : '';
        $search_replenish_location  = $this->input->get('search_replenish_location');
        $search_replenish_warehouse = $this->input->get('search_replenish_warehouse');

        $search_order_type = $this->input->get('search_order_type') ? : '';
        $search_alias = $this->input->get('search_alias') ? : '';
        if ($this->input->get('search_is_paper_order') === '0'){
            $search_is_paper_order = 0;
        } else {
            $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
        }
        if ($this->input->get('search_operation_id') === '0'){
            $search_operation_id = 0;
        } else {
            $search_operation_id = $this->input->get('search_operation_id') ? : '';
        }
        $search_send_date = $this->input->get('search_send_date') ? : '';
        $search_send_date_end = $this->input->get('search_send_date_end') ? : '';
        $search_send_batch = $this->input->get('search_send_batch') ? : '';
        $search_deliver_member = $this->input->get('search_diliver_member') ? : '';
        $search_add_price = $this->input->get('search_add_price');
        $search_send_type = !empty($this->input->get('search_send_type')) ? $this->input->get('search_send_type') : '';

        $where = array(
            'so.platform_id'=>$this->platform_id
        );
        if (isset($search_name)){
//            var_dump($search_name,strpos($search_name,','));exit;
            $search_name = trim($search_name,',');
            if(strpos($search_name,',')){
                $where['or_where_name'] = $search_name;
            }else{
                $where['e.name like'] = '%'.$search_name.'%';
            }
        }
        if ($search_address) {
            $where['address'] = $search_address;
        }
        if ($search_replenish_location && $search_replenish_location!=-1){
            $where['e.replenish_location'] = $search_replenish_location;
        }
        if($search_replenish_warehouse && $search_replenish_warehouse!=-1){
            $where['e.replenish_warehouse'] = $search_replenish_warehouse;
        }
        if ($search_deliver_member){
            $where['or_where_deliver_member'] = $search_deliver_member;
        }
        if ($search_order_type) {
            $where['order_type'] = $search_order_type;
        }
        if ($search_is_paper_order || $search_is_paper_order === 0){
            $where['so.is_paper_order'] = $search_is_paper_order;
        }
        if ($search_operation_id || $search_operation_id === 0){
            $where['operation_id'] = $search_operation_id;
        }
        
        if($search_send_date && $search_send_date_end){
            $where['send_date >='] = $search_send_date;
            $where['send_date <='] = $search_send_date_end;
        }elseif($search_send_date){
            $where['send_date'] = $search_send_date.' 00:00:00';
        }elseif($search_send_date_end){
            $where['send_date'] = $search_send_date_end.' 00:00:00';
        }

        if ($search_send_batch){
            $where['send_batch'] = $search_send_batch;
        }
        if ($search_send_type){
            $where['e.send_type'] = $search_send_type;
        }
        if ($search_alias){
            $where['sa.alias'] = $search_alias;
        }
        if ($search_add_price){
            $where['add_price'] = $search_add_price;
        }
        $where['admin_id'] = $this->adminid;

//        var_dump($where);exit;
        if(empty($gets['export_type'])){
            $result = $this->shipping_order_model->get_shipping_order_list($where,$limit,$offset);
            echo json_encode($result);
        }elseif($gets['export_type'] == 1){
            $this->export_equipment_products($where);
        }
    }

    public function export_equipment_products($where)
    {
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        $gets = $this->input->get();
        if(empty($gets['search_send_date']) || empty($gets['search_send_date_end'])){
            echo '请选择配送日期段';die;
        }
        if($gets['search_send_date'] > $gets['search_send_date_end']){
            echo '请选择配送日期段无效';die;
        }
        if((strtotime($gets['search_send_date_end']) - strtotime($gets['search_send_date'])) > 3600 * 24 * 31){
            echo '配送日期段不能大于一个月';die;
        }


        $data = $this->shipping_order_model->export_equipment_products($where);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="' . sprintf('实际补货量%s-%s.csv', date('YmdH'), count($data)) . '"');
        header('Cache-Control: max-age=0');

        $columns = [
            'equipment_name' => '设备名',
            'product_name' => '商品名',
            'price' => '价格',
            'add_qty' => '补货量',
            'real_num' => '实际补货量',
            'total_money' => '总价'
        ];
        $to_encoding = 'gb2312';
        $from_encoding = 'utf-8';
        $out = fopen('php://output', 'w');
        $row = [];
        foreach ($columns as $c => $cv) {
            array_push($row, mb_convert_encoding($cv, $to_encoding, $from_encoding));
        }
        fputcsv($out, $row);
        foreach ($data as $key => $value) {
            $row = [];
            foreach ($columns as $c => $cv) {
                array_push($row, mb_convert_encoding(isset($value[$c]) ? $value[$c] : '', $to_encoding, $from_encoding));
            }
            fputcsv($out, $row);
        }
        fclose($out);
        die;
    }
    
    //导出总单
    public function export_orders_all(){
        $ids = $_POST['curr_id'];
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $shipping_orders = array();
        foreach ($ids_arr as $k=>$id){
            $shipping_order = $this->shipping_order_model->get_shipping_order($id,array('so.order_type'=>1,'so.platform_id'=>$this->platform_id));
//            var_dump($shipping_order);
            if($shipping_order){
                $shipping_orders[$shipping_order['replenish_location_name']][] = array('shipping_no'=>$shipping_order['shipping_no'],'order_id'=>$shipping_order['id'],'equipment_name'=>$shipping_order['equipment_name'],'send_date'=>$shipping_order['send_date']);
            }
        }
        if(!empty($shipping_orders)){
            foreach ($shipping_orders as $k=>$each){
                $data[] = array('所属补货仓：',$k);
                $shipping_nos = '';
                $products = array();
                $data[] = array('补货单列表：');
                foreach($each as $l=>$eachVal){
                    $num = $l + 1;
                    $data[] = array($num.'.'.$eachVal['shipping_no'].'|'.$eachVal['equipment_name'].'|'.$eachVal['send_date']);
                    //$shipping_nos = $shipping_nos.$eachVal['shipping_no'].',';
                    //补货商品
                    $shipping_products = $this->shipping_order_model->get_shipping_order_products($eachVal['order_id']);
                    foreach ($shipping_products as $shipping_product){
                        if ($shipping_product['add_qty'] != '' && $shipping_product['add_qty'] != 0){
                            //获取商品规格 class_name等信息
                            $product_info = $this->product_model->getProductWithClass($shipping_product['product_id']);
                            $volume = $product_info['volume'];
                            $class_name = $product_info['class_name'];
                            //合并相同产品id的补货量
                            if (empty($products[$shipping_product['product_id']])){
                                $products[$shipping_product['product_id']] = array('product_id'=>$shipping_product['product_id'],'price'=>$shipping_product['price'],'product_name'=>$shipping_product['product_name'],'volume'=>$product_info['volume'],'class_name'=>$product_info['class_name'],'add_qty'=>$shipping_product['add_qty']);
                            } else {
                                $old_qty = $products[$shipping_product['product_id']]['add_qty'];
                                $products[$shipping_product['product_id']] = array('product_id'=>$shipping_product['product_id'],'price'=>$shipping_product['price'],'product_name'=>$shipping_product['product_name'],'volume'=>$product_info['volume'],'class_name'=>$product_info['class_name'],'add_qty'=>$shipping_product['add_qty'] + $old_qty);
                            }
                        }

                    }

                }
                //$data[] = array('补货单列表：',$shipping_nos);
                //$data[] = array('补货商品清单');
                $count_all = 0;
                foreach($products as $m=>$eachProduct){
                    $count_all = $count_all + $eachProduct['add_qty'];
                }
                $data[] = array('商品清单：','小计：'.$count_all.'件','类目二','商品ID','规格','售价','数量','实际拣货备注');

                foreach($products as $m=>$eachProduct){
                    $data[] = array('商品名称：',$eachProduct['product_name'],$eachProduct['class_name'],$eachProduct['product_id'],$eachProduct['volume'],$eachProduct['price'],$eachProduct['add_qty']);
                }


                $data[] = array();

            }

        }

        @set_time_limit(0);
        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $exceler->setFileName('总单导出.csv');
        $exceler->setContent($data);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
        exit;
    }
    
    //导出总单
    public function export_orders_all_xls(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');

        $ids = $_POST['curr_id'];
        $shipping_orders = array();
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        
        if ($ids == ''){    //未选择id 则导出全部
            $gets = $this->input->get();
            $limit      = 999;
            $offset     = 0;
            
            $search_name = $gets['search_name'];
            $search_address = $this->input->get('search_address') ? : '';
            $search_replenish_location = $this->input->get('search_replenish_location');
            $search_order_type = $this->input->get('search_order_type') ? : '';
            if ($this->input->get('search_is_paper_order') === '0'){
                $search_is_paper_order = 0;
            } else {
                $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
            }
            if ($this->input->get('search_operation_id') === '0'){
                $search_operation_id = 0;
            } else {
                $search_operation_id = $this->input->get('search_operation_id') ? : '';
            }
            $search_send_date = $this->input->get('search_send_date') ? : '';
            $search_deliver_member = $this->input->get('search_diliver_member') ? : '';
            
            $where = array(
                'so.platform_id'=>$this->platform_id
            );
            if (isset($search_name)){
                $where['e.name like'] = '%'.$search_name.'%';
            }
            if ($search_address) {
                $where['address'] = $search_address;
            }
            if ($search_replenish_location && $search_replenish_location!=-1){
                $where['e.replenish_location'] = $search_replenish_location;
            }
            if ($search_deliver_member && $search_deliver_member!=-1){
                $where['so.dm_id'] = $search_deliver_member;
            }
            if ($search_order_type) {
                $where['order_type'] = $search_order_type;
            }
            if ($search_is_paper_order || $search_is_paper_order === 0){
                $where['so.is_paper_order'] = $search_is_paper_order;
            }
            if ($search_operation_id || $search_operation_id === 0){
                $where['operation_id'] = $search_operation_id;
            }
            if ($search_send_date){
                $where['send_date'] = $search_send_date.' 00:00:00';
            }
            $where['admin_id'] = $this->adminid;
            
            //        var_dump($where);exit;
            $result = $this->shipping_order_model->get_shipping_order_list($where,$limit,$offset);
            foreach ($result['rows'] as $eachResult){
                $shipping_orders[$eachResult['replenish_location_name']][] = array('shipping_no'=>$eachResult['shipping_no'],'order_id'=>$eachResult['id'],'equipment_name'=>$eachResult['equipment_name'],'equipment_type'=>$eachResult['equipment_type'],'send_date'=>$eachResult['send_date'],'goods_allocation' =>$eachResult['goods_allocation']);
            }
        } else {
            $ids_arr = explode('|',$ids);
            $ids_arr = array_filter($ids_arr);
            
            foreach ($ids_arr as $k=>$id){
                $shipping_order = $this->shipping_order_model->get_shipping_order($id,array('so.order_type'=>1,'so.platform_id'=>$this->platform_id));
                //            var_dump($shipping_order);
                if($shipping_order){
                    $shipping_orders[$shipping_order['replenish_location_name']][] = array('shipping_no'=>$shipping_order['shipping_no'],'order_id'=>$shipping_order['id'],'equipment_name'=>$shipping_order['equipment_name'],'equipment_type'=>$shipping_order['equipment_type'],'send_date'=>$shipping_order['send_date'],'goods_allocation' =>$shipping_order['goods_allocation']);
                }
            }
        }
        //echo '<pre>';
        //var_dump($shipping_orders);exit;
        
        if(!empty($shipping_orders)){

            $begin_line = 1;
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('30');
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('8');
            $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
            $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('8');
            $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('5');
            $objPHPExcel->getActiveSheet()->getColumnDimension('G')->setWidth('5');
            $objPHPExcel->getActiveSheet()->getColumnDimension('H')->setWidth('5');
            $objPHPExcel->getActiveSheet()->getColumnDimension('I')->setWidth('5');
            $objPHPExcel->getActiveSheet()->setTitle('总单导出');
            $export = [
                'shipping_no'=>[],
                'products' => []
            ];
            foreach ($shipping_orders as $k=>$each){
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
                $send_data_arr = array();
                foreach ($each as $eachVal){
                    if (!in_array($eachVal['send_date'],$send_data_arr)){
                        $send_data_arr[] = $eachVal['send_date'];
                    }
                }
                $objPHPExcel->setActiveSheetIndex(0)->setCellValue('A'.$begin_line, '所属补货仓：'.$k);
                $begin_line +=1;
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line, '配送日期：'.implode('|',$send_data_arr));
                $shipping_nos = '';
                $products = array();
                $begin_line +=1;
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'补货单列表：');
                $objPHPExcel->getActiveSheet()->setCellValue('B'.$begin_line, '货位：');
                $objPHPExcel->getActiveSheet()->mergeCells("C{$begin_line}:D{$begin_line}")->setCellValue('C'.$begin_line, '单号：');
                $objPHPExcel->getActiveSheet()->mergeCells("E{$begin_line}:F{$begin_line}")->setCellValue('E'.$begin_line,'时间：');
                foreach($each as $l=>$eachVal){
                    $num = $l + 1;
                    $begin_line +=1;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,$num.'.'.$eachVal['equipment_name']);
                    $objPHPExcel->getActiveSheet()->setCellValue('B'.$begin_line, $eachVal['goods_allocation']);
                    $objPHPExcel->getActiveSheet()->mergeCells("C{$begin_line}:D{$begin_line}")->setCellValue('C'.$begin_line,$eachVal['shipping_no']);
                    $objPHPExcel->getActiveSheet()->mergeCells("E{$begin_line}:F{$begin_line}")->setCellValue('E'.$begin_line, $eachVal['send_date']);
                    $export['shipping_no'][] = $eachVal['shipping_no'];
                    //$shipping_nos = $shipping_nos.$eachVal['shipping_no'].',';
                    //补货商品
                    if ((int)$eachVal['order_id'] > 0){
                        $shipping_products = $this->shipping_order_model->get_shipping_order_products((int)$eachVal['order_id']);
                    } else {
                        $shipping_products = array();
                    }
                    foreach ($shipping_products as $shipping_product){
                        if ($shipping_product['add_qty'] != '' && $shipping_product['add_qty'] != 0){
                            //获取商品规格 class_name等信息
                            $product_info = $this->product_model->getProductWithClass($shipping_product['product_id']);
                            $volume = $product_info['volume'];
                            $class_name = $product_info['class_name'];
                            $marking_qty = $shipping_product['add_qty'];
                            //合并相同产品id的补货量
                            if (empty($products[$shipping_product['product_id']])){
                                $products[$shipping_product['product_id']] = array('product_id'=>$shipping_product['product_id'],'price'=>$shipping_product['price'],'product_name'=>$shipping_product['product_name'],'volume'=>$product_info['volume'],'class_name'=>$product_info['class_name'],'add_qty'=>$shipping_product['add_qty'],'class_id'=>$product_info['class_id'],'refrigeration'=>$shipping_product['refrigeration'],'marking_qty' => $this->isEquipmentMarking($eachVal['equipment_type']) ? $marking_qty:0,'unmarking_qty' => !$this->isEquipmentMarking($eachVal['equipment_type']) ? $marking_qty:0);
                            } else {
                                $old_qty = $products[$shipping_product['product_id']]['add_qty'];
                                $old_marking_qty = $products[$shipping_product['product_id']]['marking_qty'];
                                $old_unmarking_qty = $products[$shipping_product['product_id']]['unmarking_qty'];
                                $products[$shipping_product['product_id']] = array('product_id'=>$shipping_product['product_id'],'price'=>$shipping_product['price'],'product_name'=>$shipping_product['product_name'],'volume'=>$product_info['volume'],'class_name'=>$product_info['class_name'],'add_qty'=>$shipping_product['add_qty'] + $old_qty,'class_id'=>$product_info['class_id'],'refrigeration'=>$shipping_product['refrigeration'],'marking_qty' => (($this->isEquipmentMarking($eachVal['equipment_type']) ? $marking_qty:0) + $old_marking_qty),'unmarking_qty' => ((!$this->isEquipmentMarking($eachVal['equipment_type']) ? $marking_qty:0) + $old_unmarking_qty));
                            }
                        }

                    }
                }

//                echo count($products);
                $newArr = array();
                foreach($products as $v){
                    //$newArr[]=$v['class_id'];
                    $newArr[]=$v['add_qty'];
                    $newArr1[]=$v['refrigeration'];
                }

//                print_r($newArr);exit;
                array_multisort($newArr1,SORT_ASC,$newArr,SORT_DESC,$products);

//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($products);
//                echo count($products);
//                exit;
                //$data[] = array('补货单列表：',$shipping_nos);
                //$data[] = array('补货商品清单');
                $count_all = 0;
                foreach($products as $m=>$eachProduct){
                    $count_all = $count_all + $eachProduct['add_qty'];
                }

                $begin_line += 2;

                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'商品清单：'.'共'.$count_all.'件');
                
                
                $begin_line += 1;
                
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'商品名称')
                    ->setCellValue('B'.$begin_line, '类目二')
                    ->setCellValue('C'.$begin_line, '商品ID')
                    ->setCellValue('D'.$begin_line, '规格')
                    ->setCellValue('E'.$begin_line, '售价')
                    ->setCellValue('F'.$begin_line, '贴标')
                    ->setCellValue('G'.$begin_line, '非标')
                    ->setCellValue('H'.$begin_line, '补货')
                    ->setCellValue('I'.$begin_line, '实拿');
//                    ->setCellValue('G'.$begin_line, '实际拣货备注');
                foreach($products as $m=>$eachProduct){
                    $begin_line += 1;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,$eachProduct['product_name'])
                        ->setCellValue('B'.$begin_line, $eachProduct['class_name'])
                        ->setCellValue('C'.$begin_line, $eachProduct['product_id'])
                        ->setCellValue('D'.$begin_line, $eachProduct['volume'])
                        ->setCellValue('E'.$begin_line, $eachProduct['price'])
                        ->setCellValue('F'.$begin_line, $eachProduct['marking_qty'])
                        ->setCellValue('G'.$begin_line, $eachProduct['unmarking_qty'])
                        ->setCellValue('H'.$begin_line, $eachProduct['add_qty'])
                        ->setCellValue('I'.$begin_line, '');

                    if($eachProduct['refrigeration']==1){
                        $objPHPExcel->getActiveSheet()->getStyle('A'.$begin_line.':C'.$begin_line)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
                    }
                    $export['products'][$eachProduct['product_id']] = $eachProduct['add_qty'];
//                    $data[] = array('商品名称：',$eachProduct['product_name'],$eachProduct['class_name'],$eachProduct['product_id'],$eachProduct['volume'],$eachProduct['price'],$eachProduct['add_qty']);
                }

                $begin_line += 4;
//                var_dump($begin_line);
//                $data[] = array();
            }

        }
        $end_line = $begin_line - 4;
        
        $styleArray = array(
            'borders' => array(
                'allborders' => array(
                    //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                    'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                    'color' => array('argb' => '000000'),
                ),
            ),
        );
        $objPHPExcel->getActiveSheet()->getStyle('A1:I'.$end_line)->applyFromArray($styleArray);
        $objPHPExcel->getActiveSheet()->getStyle('A1:I'.$end_line)->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_JUSTIFY);
//exit;


        //导出批次记录
        $bno ='EB'.date("ymdi").$this->rand_code(4);
        $this->shipping_order_model->logOrderExportBatch([
            'batch_no' => $bno,
            'content' => json_encode($export),
            'time' => date('Y-m-d H:i:s')
        ]);

        //条形码
        include(APPPATH . 'libraries/barcode/BarcodeGeneratorPNG.php');
        $objPHPExcel->getActiveSheet()->insertNewRowBefore(2);
        $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
        $data = $bc->getBarcode($bno, $bc::TYPE_CODE_128, 2, 50);
        $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
        $objDrawing->setImageResource(imagecreatefromstring($data));
        $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
        $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
        $objDrawing->setResizeProportional(false);
        $objDrawing->setWidthAndHeight(240,50);
        $objDrawing->setCoordinates('A2');
        $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
        $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(38);

        $objPHPExcel->getActiveSheet()->setCellValue('B2', $bno)->mergeCells("B2:C2");
        $objPHPExcel->getActiveSheet()->getStyle('A2:B2')->applyFromArray([
            'alignment' => [
                'vertical' => PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'horizontal' => PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
            ]
        ]);

        $filename = '总单'.date('Y-m-d');
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

public function export_orders_all_deliver_xls(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');

        $ids = $_POST['curr_id'];
        $shipping_orders = array();
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();

        if ($ids == ''){    //未选择id 则导出全部
            $gets = $this->input->get();
            $limit      = 999;
            $offset     = 0;

            $search_name = $gets['search_name'];
            $search_address = $this->input->get('search_address') ? : '';
            $search_replenish_location = $this->input->get('search_replenish_location');
            $search_order_type = $this->input->get('search_order_type') ? : '';
            if ($this->input->get('search_is_paper_order') === '0'){
                $search_is_paper_order = 0;
            } else {
                $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
            }
            if ($this->input->get('search_operation_id') === '0'){
                $search_operation_id = 0;
            } else {
                $search_operation_id = $this->input->get('search_operation_id') ? : '';
            }
            $search_send_date = $this->input->get('search_send_date') ? : '';
            $search_deliver_member = $this->input->get('search_diliver_member') ? : '';

            $where = array(
                'so.platform_id'=>$this->platform_id
            );
            if (isset($search_name)){
                $where['e.name like'] = '%'.$search_name.'%';
            }
            if ($search_address) {
                $where['address'] = $search_address;
            }
            if ($search_replenish_location && $search_replenish_location!=-1){
                $where['e.replenish_location'] = $search_replenish_location;
            }
            if ($search_deliver_member && $search_deliver_member!=-1){
                $where['so.dm_id'] = $search_deliver_member;
            }
            if ($search_order_type) {
                $where['order_type'] = $search_order_type;
            }
            if ($search_is_paper_order || $search_is_paper_order === 0){
                $where['so.is_paper_order'] = $search_is_paper_order;
            }
            if ($search_operation_id || $search_operation_id === 0){
                $where['operation_id'] = $search_operation_id;
            }
            if ($search_send_date){
                $where['send_date'] = $search_send_date.' 00:00:00';
            }
            $where['admin_id'] = $this->adminid;

            //        var_dump($where);exit;
            $result = $this->shipping_order_model->get_shipping_order_list($where,$limit,$offset);
            foreach ($result['rows'] as $eachResult){
                $shipping_orders[$eachResult['replenish_location_name']][] = array('shipping_no'=>$eachResult['shipping_no'],'order_id'=>$eachResult['id'],'equipment_name'=>$eachResult['equipment_name'],'equipment_type'=>$eachResult['equipment_type'],'send_date'=>$eachResult['send_date']);
                $shippingId2Info[$eachResult['shipping_no']] = array('shipping_no'=>$eachResult['shipping_no'],'order_id'=>$eachResult['id'],'equipment_name'=>$eachResult['equipment_name'],'equipment_type'=>$eachResult['equipment_type'],'send_date'=>$eachResult['send_date'],'goods_allocation'=>$eachResult['goods_allocation']);
            }
        } else {
            $ids_arr = explode('|',$ids);
            $ids_arr = array_filter($ids_arr);

            foreach ($ids_arr as $k=>$id){
                $shipping_order = $this->shipping_order_model->get_shipping_order($id,array('so.order_type'=>1,'so.platform_id'=>$this->platform_id));
//                            var_dump($shipping_order);exit;
                if($shipping_order){
                    $shipping_orders[$shipping_order['replenish_location_name']][] = array('shipping_no'=>$shipping_order['shipping_no'],'order_id'=>$shipping_order['id'],'equipment_name'=>$shipping_order['equipment_name'],'send_date'=>$shipping_order['send_date']);
                    $shippingId2Info[$shipping_order['shipping_no']] = array('shipping_no'=>$shipping_order['shipping_no'],'order_id'=>$shipping_order['id'],'equipment_name'=>$shipping_order['equipment_name'],'equipment_type'=>$shipping_order['equipment_type'],'send_date'=>$shipping_order['send_date'],'goods_allocation'=>$shipping_order['goods_allocation']);
                }
            }
        }

        if(!empty($shipping_orders)){
            foreach ($shipping_orders as $k=>$each){
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($shippingId2Info);exit;
                foreach($each as $l=>$eachVal){
                    //补货商品
                    $shipping_products = $this->shipping_order_model->get_shipping_order_products($eachVal['order_id']);
                    foreach ($shipping_products as $shipping_product){
                        if ($shipping_product['add_qty'] != '' && $shipping_product['add_qty'] != 0){
                            //获取商品规格 class_name等信息
                            $product_info = $this->product_model->getProductWithClass($shipping_product['product_id']);
//                            $volume = $product_info['volume'];
//                            $class_name = $product_info['class_name'];
//                            if (preg_match('/(\d+)/',$shippingId2Info[$eachVal['shipping_no']]['goods_allocation'],$r))
//                                $goods_allocation_num = $r[1];
//                            else
//                                $goods_allocation_num = 0;
                            $products[$shipping_product['product_id']][] = array('product_id'=>$shipping_product['product_id'],'price'=>$shipping_product['price'],'product_name'=>$shipping_product['product_name'],'volume'=>$product_info['volume'],'class_name'=>$product_info['class_name'],'refrigeration'=>$product_info['refrigeration'],'add_qty'=>$shipping_product['add_qty'],'class_id'=>$product_info['class_id'],'equipment_name'=>$shippingId2Info[$eachVal['shipping_no']]['equipment_name'],'equipment_type'=>$shippingId2Info[$eachVal['shipping_no']]['equipment_type'],'goods_allocation'=>$shippingId2Info[$eachVal['shipping_no']]['goods_allocation'],'send_date'=>$shippingId2Info[$eachVal['shipping_no']]['send_date']);//,'goods_allocation_num'=>$goods_allocation_num
                        }
                    }
                }
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($products);exit;
//                echo count($products);
                foreach($products as $k=>$v){
                    if(!empty($v)){
                        $newArr = array();
                        $newArr1 = array();
                        foreach($v as $v1){
                            $newArr[]=$v1['goods_allocation'];
//                            $numArr[]=$v1['goods_allocation_num'];
//                            var_dump($v1);exit;
                        }

                        array_multisort($newArr,SORT_NATURAL,$products[$k]);
//                        array_multisort($products[$k]);
                    }
                }
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($products);
//                echo count($products);
//                exit;
                //$data[] = array('补货单列表：',$shipping_nos);
                //$data[] = array('补货商品清单');
//                $count_all = 0;
//                foreach($products as $m=>$eachProduct){
//                    $count_all = $count_all + $eachProduct['add_qty'];
//                }
//
//                $begin_line += 1;
//                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'商品清单：'.'共'.$count_all.'件');
                

//                $begin_line += 4;
//                var_dump($begin_line);
//                $data[] = array();
            }

        }
//exit;
        $i=0;
        
        //排序
        foreach($products as $k=>$v){
            $v1 = $v[0];
            $newArr1[] = $v1['refrigeration'];
        }
        array_multisort($newArr1,SORT_DESC,$products);
        
        
        foreach($products as $m=>$eachProduct){
            $begin_line = 0;
            //                    header("Content-Type:text/html;charset=utf-8");
            //                echo '<pre>';
            //                print_r($products);exit;
            //                echo count($products);
            //                    var_dump($eachProduct);exit;
            $pro_total = 0;
        
            foreach ($eachProduct as $k=>$v){
                $pro_total += $v['add_qty'];
                //                        header("Content-Type:text/html;charset=utf-8");
                //                echo '<pre>';
                //                var_dump($k,$v);exit;
                if($k==0){
                    $objPHPExcel->createSheet();
                    $objPHPExcel->setActiveSheetIndex($i);
                    $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('10');
                    $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('36');
                    $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('20');
                    $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth('10');
                    $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth('5');
                    $objPHPExcel->getActiveSheet()->getColumnDimension('F')->setWidth('5');
        
                    $title = str_replace(array(':','\\','/','?','*','[',']'),array('-'),$v['product_name']);
                    if ($v['refrigeration'] == 1){
                        $title .= ' #冷藏';
                    }
                    $objPHPExcel->getActiveSheet()->setTitle($title);
                    //                        $objPHPExcel->getActiveSheet()->setTitle('总单-分拣单');
        
                    $begin_line += 1;
                    if ($v['refrigeration'] == 1){
                        $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'冷藏');
                        $objPHPExcel->getActiveSheet()->getStyle('A'.$begin_line)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
                        $begin_line += 2;
                    }
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'配送日期:'.$v['send_date']);
        
                    $begin_line += 1;
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'商品ID')
                    ->setCellValue('B'.$begin_line, '商品名称')
                    ->setCellValue('C'.$begin_line, '设备名称')
                    ->setCellValue('D'.$begin_line, '货位')
                    ->setCellValue('E'.$begin_line, '数量')
                    ->setCellValue('F'.$begin_line, '贴标');
                }
        
                if ($v['refrigeration'] == 1){
                    $objPHPExcel->getActiveSheet()->setCellValue('B3','总数:'.$pro_total);
                } else {
                    $objPHPExcel->getActiveSheet()->setCellValue('B1','总数:'.$pro_total);
                }
        
                $begin_line += 1;
                $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,$v['product_id'])
                ->setCellValue('B'.$begin_line,$v['product_name'])
                ->setCellValue('C'.$begin_line, $v['equipment_name'])
                ->setCellValue('D'.$begin_line, $v['goods_allocation'])
                ->setCellValue('E'.$begin_line, $v['add_qty'])
                ->setCellValue('F'.$begin_line, $this->isEquipmentMarking($v['equipment_type']) ? '是':'否');
                if(!$this->isEquipmentMarking($v['equipment_type'])){
                    $objPHPExcel->getActiveSheet()->getStyle('E'.$begin_line)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
                }
            }
            $i++;
            //边框样式
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                        'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:E'.$begin_line)->applyFromArray($styleArray);
            //                        ->setCellValue('G'.$begin_line, '');
            //                    $data[] = array('商品名称：',$eachProduct['product_name'],$eachProduct['class_name'],$eachProduct['product_id'],$eachProduct['volume'],$eachProduct['price'],$eachProduct['add_qty']);
        }

        $filename = '总单-分拣单'.date('Y-m-d');
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    public function export_orders_all_deliver_info_xls(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');

        $ids = $_POST['curr_id'];
        $shipping_orders = array();
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();

        if ($ids == ''){    //未选择id 则导出全部
            $gets = $this->input->get();
            $limit      = 999;
            $offset     = 0;

            $search_name = $gets['search_name'];
            $search_address = $this->input->get('search_address') ? : '';
            $search_replenish_location = $this->input->get('search_replenish_location');
            $search_order_type = $this->input->get('search_order_type') ? : '';
            if ($this->input->get('search_is_paper_order') === '0'){
                $search_is_paper_order = 0;
            } else {
                $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
            }
            if ($this->input->get('search_operation_id') === '0'){
                $search_operation_id = 0;
            } else {
                $search_operation_id = $this->input->get('search_operation_id') ? : '';
            }
            $search_send_date = $this->input->get('search_send_date') ? : '';
            $search_deliver_member = $this->input->get('search_diliver_member') ? : '';

            $where = array(
                'so.platform_id'=>$this->platform_id
            );
            if (isset($search_name)){
                $where['e.name like'] = '%'.$search_name.'%';
            }
            if ($search_address) {
                $where['address'] = $search_address;
            }
            if ($search_replenish_location && $search_replenish_location!=-1){
                $where['e.replenish_location'] = $search_replenish_location;
            }
            if ($search_deliver_member && $search_deliver_member!=-1){
                $where['so.dm_id'] = $search_deliver_member;
            }
            if ($search_order_type) {
                $where['order_type'] = $search_order_type;
            }
            if ($search_is_paper_order || $search_is_paper_order === 0){
                $where['so.is_paper_order'] = $search_is_paper_order;
            }
            if ($search_operation_id || $search_operation_id === 0){
                $where['operation_id'] = $search_operation_id;
            }
            if ($search_send_date){
                $where['send_date'] = $search_send_date.' 00:00:00';
            }
            $where['admin_id'] = $this->adminid;

            $result = $this->shipping_order_model->get_shipping_order_list($where,$limit,$offset);
//            var_dump($result);exit;

            foreach ($result['rows'] as $eachResult){
                $shipping_orders[$eachResult['dm_name']][] = array('shipping_no'=>$eachResult['shipping_no'],'order_id'=>$eachResult['id'],'equipment_name'=>$eachResult['equipment_name'],'equipment_type'=>$eachResult['equipment_type'],'send_date'=>$eachResult['send_date'],'dm_name'=>$eachResult['dm_name'],'dm_mobile'=>$eachResult['dm_mobile'],'goods_allocation'=>$eachResult['goods_allocation'],'address'=>$eachResult['address']);
            }
        } else {
            $ids_arr = explode('|',$ids);
            $ids_arr = array_filter($ids_arr);

            foreach ($ids_arr as $k=>$id){
                $shipping_order = $this->shipping_order_model->get_shipping_order($id,array('so.order_type'=>1,'so.platform_id'=>$this->platform_id));
//                            var_dump($shipping_order);exit;
                if($shipping_order){
                    $shipping_orders[$shipping_order['dm_name']][] = array('shipping_no'=>$shipping_order['shipping_no'],'order_id'=>$shipping_order['id'],'equipment_name'=>$shipping_order['equipment_name'],'equipment_type'=>$shipping_order['equipment_type'],'send_date'=>$shipping_order['send_date'],'dm_name'=>$shipping_order['dm_name'],'dm_mobile'=>$shipping_order['dm_mobile'],'goods_allocation'=>$shipping_order['goods_allocation'],'address'=>$shipping_order['address']);
                }
            }
        }

//
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($result);exit;

        if(!empty($shipping_orders)){
            foreach ($shipping_orders as $k=>$each) {
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($each);exit;
//
                if (!empty($each)) {
                    $newArr = array();
                    foreach ($each as $v1) {
                        $newArr[] = $v1['goods_allocation'];
                    }

                    array_multisort($newArr, SORT_NATURAL, $shipping_orders[$k]);
//                        array_multisort($products[$k]);
                }
//                header("Content-Type:text/html;charset=utf-8");
//                echo '<pre>';
//                print_r($shipping_orders);
//                exit;
            }

            $i=0;
            foreach($shipping_orders as $k=>$shipping_order){
                    $begin_line = 0;
                    $eq_num = 0;

                    foreach ($shipping_order as $k1=>$v){

//                        header("Content-Type:text/html;charset=utf-8");
//                        echo '<pre>';
//                        print_r($shipping_order);
//                        exit;

                        $eq_num ++;

                        if($k1==0){
                            $objPHPExcel->createSheet();
                            $objPHPExcel->setActiveSheetIndex($i);
                            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('18');
                            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('8');
                            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('50');

                            if($v['dm_name']==''){
                                $v['dm_name']='未分配';
                            }
                            $title = str_replace(array(':','\\','/','?','*','[',']'),array('-'),$v['dm_name']);
                            $objPHPExcel->getActiveSheet()->setTitle($title);
//                        $objPHPExcel->getActiveSheet()->setTitle('总单-分拣单');

                            $begin_line += 1;
                            $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,'配送日期:'.$v['send_date']);

                            $begin_line += 1;
                            $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,$v['dm_name'].'_'.$v['dm_mobile']);

                            $begin_line += 1;
                            $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line, '设备名称')
                                ->setCellValue('B'.$begin_line, '货位')
                                ->setCellValue('C'.$begin_line, '地址');
                        }

                        $objPHPExcel->getActiveSheet()->setCellValue('B1','总数:'.$eq_num);

                        $begin_line += 1;
                        $objPHPExcel->getActiveSheet()->setCellValue('A'.$begin_line,$v['equipment_name'])
                            ->setCellValue('B'.$begin_line, $v['goods_allocation'])
                            ->setCellValue('C'.$begin_line, $v['address']);
                    }

                    //边框样式
                    $styleArray = array(
                        'borders' => array(
                            'allborders' => array(
                                //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                                'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                                'color' => array('argb' => '000000'),
                            ),
                        ),
                    );
                    $objPHPExcel->getActiveSheet()->getStyle('A1:C'.$begin_line)->applyFromArray($styleArray);

                    $i++;
                }

        }
//exit;

        $filename = '配送信息'.date('Y-m-d');
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


    //导出纸质单
    public function export_orders_paper(){
        $ids = $_POST['curr_id'];
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $shipping_orders = array();
        foreach ($ids_arr as $k=>$id){
            $shipping_order = $this->shipping_order_model->get_shipping_order($id,array('so.platform_id'=>$this->platform_id));
            $data[] = array('订单号：',$shipping_order['shipping_no']);
            $data[] = array('订单类型：',$shipping_order['order_type']==2?"下架单":"出库单");
            $data[] = array('总金额：',$shipping_order['total_amount']);
            $data[] = array('单据类型：',$shipping_order['is_paper_order_name']);
            $data[] = array('订单状态：',$shipping_order['operation_name']);
            $data[] = array('配送日期：',$shipping_order['send_date']);
            $data[] = array('设备名称：',$shipping_order['equipment_name']);
            $data[] = array('设备编码：',$shipping_order['equipment_id']);
            $data[] = array('设备补给仓：',$shipping_order['replenish_location_name']);
            $shipping_order_address = $this->shipping_order_model->get_shipping_order_address($shipping_order['id']);
            $data[] = array('配送地址：',$shipping_order_address['province'].$shipping_order_address['city'].$shipping_order_address['area'].$shipping_order_address['address']);
            $shipping_products = $this->shipping_order_model->get_shipping_order_products($shipping_order['id']);
            if($shipping_order['order_type']==1)
                $data[] = array('补货商品清单');
            else
                $data[] = array('下架商品清单');
            foreach($shipping_products as $m=>$eachProduct){
                if ($eachProduct['add_qty'] != '' && $eachProduct['add_qty'] != 0){
                    if($shipping_order['order_type']==1)
                        $data[] = array('商品名称：',$eachProduct['product_name'],'补货总数：',$eachProduct['add_qty']);
                    else
                        $data[] = array('商品名称：',$eachProduct['product_name'],'下架总数：',$eachProduct['add_qty']);
                }
            }
            $data[] = array();
        }
        
        
        @set_time_limit(0);
        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $exceler->setFileName('订单导出.csv');
        $exceler->setContent($data);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
        exit;
    }

    //导出纸质单xls
    public function export_orders_paper_xls(){
        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        include(APPPATH . 'libraries/barcode/BarcodeGeneratorPNG.php');
        $objPHPExcel = new PHPExcel();


        $ids = $_POST['curr_id'];
        if ($ids == ''){    //未选择id 则导出全部
            $gets = $this->input->get();
            $limit      = 999;
            $offset     = 0;
        
            $search_name = $gets['search_name'];
            $search_address = $this->input->get('search_address') ? : '';
            $search_replenish_location = $this->input->get('search_replenish_location');
            $search_order_type = $this->input->get('search_order_type') ? : '';
            if ($this->input->get('search_is_paper_order') === '0'){
                $search_is_paper_order = 0;
            } else {
                $search_is_paper_order = $this->input->get('search_is_paper_order') ? : '';
            }
            if ($this->input->get('search_operation_id') === '0'){
                $search_operation_id = 0;
            } else {
                $search_operation_id = $this->input->get('search_operation_id') ? : '';
            }
            $search_send_date = $this->input->get('search_send_date') ? : '';
            $search_send_batch = $this->input->get('search_send_batch') ? : '';
            $search_deliver_member = $this->input->get('search_diliver_member') ? : '';
        
            $where = array(
                'so.platform_id'=>$this->platform_id
            );
            if (isset($search_name)){
                $where['e.name like'] = '%'.$search_name.'%';
            }
            if ($search_address) {
                $where['address'] = $search_address;
            }
            if ($search_replenish_location && $search_replenish_location!=-1){
                $where['e.replenish_location'] = $search_replenish_location;
            }
            if ($search_deliver_member && $search_deliver_member!=-1){
                $where['so.dm_id'] = $search_deliver_member;
            }
            if ($search_order_type) {
                $where['order_type'] = $search_order_type;
            }
            if ($search_is_paper_order || $search_is_paper_order === 0){
                $where['so.is_paper_order'] = $search_is_paper_order;
            }
            if ($search_operation_id || $search_operation_id === 0){
                $where['operation_id'] = $search_operation_id;
            }
            if ($search_send_date){
                $where['send_date'] = $search_send_date.' 00:00:00';
            }
            if ($search_send_date){
                $where['send_batch'] = $search_send_batch;
            }
            $where['admin_id'] = $this->adminid;
        
            //        var_dump($where);exit;
            $result = $this->shipping_order_model->get_shipping_order_list($where,$limit,$offset);
            $ids_arr = array();
            foreach ($result['rows'] as $eachResult){
                $ids_arr[] = $eachResult['id'];
            }
            
        } else {
            $ids_arr = explode('|',$ids);
            $ids_arr = array_filter($ids_arr);
        }
        foreach ($ids_arr as $k=>$id){
            $orders = $this->shipping_order_model->get_shipping_order($id,array('so.platform_id'=>$this->platform_id));
            $shipping_orders[] = $orders;
            $newArr[] = $orders['goods_allocation'];
        }

        array_multisort($newArr,SORT_NATURAL,$shipping_orders);


//                    header("Content-Type:text/html;charset=utf-8");
//            var_dump($shipping_orders);exit;

        $i=0;
        foreach($shipping_orders as $k=>$shipping_order){
            $shipping_order_address = $this->shipping_order_model->get_shipping_order_address($shipping_order['id']);
            $shipping_products = $this->shipping_order_model->get_shipping_order_products($shipping_order['id'],'`refrigeration` ASC');
//            header("Content-Type:text/html;charset=utf-8");
//            var_dump($shipping_products);exit;

            if($i!=0)
                $objPHPExcel->createSheet();
            $objPHPExcel->setActiveSheetIndex($i);
            $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth('36');
            $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth('8');
            $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth('8');

            $equipment_name  = $shipping_order['equipment_name'];
            $equipment_name = str_replace(array(':','\\','/','?','*','[',']'),array('-'),$equipment_name);
            $objPHPExcel->getActiveSheet()->setTitle($equipment_name);
            $objPHPExcel->getActiveSheet()->setCellValue('A1','订单号：'.$shipping_order['shipping_no'])
                ->setCellValue('B13',$shipping_order['admin_alias'])
                ->setCellValue('A13','设备运营：'.$shipping_order['admin_mobile'])
                ->setCellValue('A2', '订单类型：'.($shipping_order['order_type']==2?"下架单":"出库单"))
                ->setCellValue('A3', '总金额：'.$shipping_order['total_amount'])
                ->setCellValue('A4', '单据类型：'.$shipping_order['is_paper_order_name'])
                ->setCellValue('A5', '订单状态：'.$shipping_order['operation_name'])
                ->setCellValue('A6', '配送日期：'.$shipping_order['send_date'])
                ->setCellValue('A7', $shipping_order['equipment_name']."__".$shipping_order['goods_allocation'])
                ->setCellValue('B7', '盒子：')
                ->setCellValue('C7', $shipping_order['show_box'])
                ->setCellValue('A8', '设备编码：'.$shipping_order['equipment_id'])
                ->setCellValue('B8', '贴标：'.($this->isEquipmentMarking($shipping_order['equipment_type']) ? '是':'否'))
                ->setCellValue('A9', '设备补给仓：'.$shipping_order['replenish_location_name'])
                ->setCellValue('A10', '配送地址：'.$shipping_order_address['address'])
                ->setCellValue('A11', '设备配送仓：'.$shipping_order['replenish_location_name_ps'])
                ->setCellValue('A12', '配送批次：'.$this->send_batch_data[$shipping_order['send_batch']])
                ->setCellValue('A16', '商品名称')
                ->setCellValue('B16', $shipping_order['order_type']==1?'补货量':'下架量')
                ->setCellValue('C16', '实拿量')
            ;

            if(!$this->isEquipmentMarking($shipping_order['equipment_type'])){
                $objPHPExcel->getActiveSheet()->getStyle('B8')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
            }
            if(!empty($shipping_order['dm_car_no'])){
                $objPHPExcel->getActiveSheet()->setCellValue('A13', '电车编号：'.$shipping_order['dm_car_no']);
            }

            $j=17;
            $total_num = $refrigeration = 0;
            foreach($shipping_products as $m=>$eachProduct){
                if ($eachProduct['add_qty'] != '' && $eachProduct['add_qty'] != 0){
                    $objPHPExcel->getActiveSheet()->setCellValue('A'.$j,$eachProduct['product_name'])
                        ->setCellValue('B'.$j,$eachProduct['add_qty'])
                        ->setCellValue('C'.$j,!empty($eachProduct['sorting_num']) ? $eachProduct['sorting_num'] : '')
                    ;
                    $total_num += $eachProduct['add_qty'];
                    if($eachProduct['refrigeration']==1){
                        $objPHPExcel->getActiveSheet()->getStyle('A'.$j.':C'.$j)->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
                    }

                    $j++;
                }
            }
            $objPHPExcel->getActiveSheet()->setCellValue('A15', ($shipping_order['order_type']==1?'补货商品清单：':'下架商品清单：')."共".$total_num."件");
            $styleArray = array(
                'borders' => array(
                    'allborders' => array(
                        //'style' => PHPExcel_Style_Border::BORDER_THICK,//边框是粗的
                        'style' => PHPExcel_Style_Border::BORDER_THIN,//细边框
                        'color' => array('argb' => '000000'),
                    ),
                ),
            );
            $objPHPExcel->getActiveSheet()->getStyle('A1:C'.($j-1))->applyFromArray($styleArray);
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFill()->setFillType(PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setARGB("bfbfbf");
            $objPHPExcel->getActiveSheet()->getStyle('A7')->getFont()->setBold(true);


            //条形码
            $objPHPExcel->getActiveSheet()->insertNewRowBefore(2);
            $bc = new \Picqer\Barcode\BarcodeGeneratorPNG();
            $data = $bc->getBarcode($shipping_order['shipping_no'], $bc::TYPE_CODE_128, 2, 50);
            $objDrawing = new PHPExcel_Worksheet_MemoryDrawing();
            $objDrawing->setImageResource(imagecreatefromstring($data));
            $objDrawing->setRenderingFunction(PHPExcel_Worksheet_MemoryDrawing::RENDERING_JPEG);
            $objDrawing->setMimeType(PHPExcel_Worksheet_MemoryDrawing::MIMETYPE_DEFAULT);
            $objDrawing->setResizeProportional(false);
            $objDrawing->setWidthAndHeight(250,50);
            $objDrawing->setCoordinates('A2');
            $objDrawing->setWorksheet($objPHPExcel->getActiveSheet());
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(38);

            $i++;
        }

        $filename = '分单'.date('Y-m-d');
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }

    //ajax  调起分配员工的浮层
    public function apply_deliver_member_show($ids){
//        $this->input->set_cookie("username",11111,60);
//        echo $this->input->cookie("username");
        $last_search = $this->get_last_search();
//        var_dump($last_search);
//        $deliver_member_list = $this->deliver_member_model->getMemberList($ids);
        $this->_pagedata['ids'] = $ids;
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $replenish_location_arr = $this->shipping_order_model->getShippingInfoByArray1("replenish_location",false,array('key'=>'shipping_order.id','val'=>$ids_arr),array('table'=>'equipment e','on'=>'e.equipment_id=shipping_order.equipment_id'));
        if(!empty($replenish_location_arr)){
            foreach ($replenish_location_arr as $v){
                $replenish_locations[] = $v['replenish_location'];
            }
            $replenish_locations = array_unique($replenish_locations);
            $replenish_locations = $this->deliver_member_model->get_member_by_relation($replenish_locations);
            $this->_pagedata['replenish_locations'] = $replenish_locations;
        }

        $this->_pagedata['last_search'] = $last_search;
        $this->display('deliver/shipping_apply_member.html');
    }

    //ajax  调起分配员工的浮层
    public function change_deliver_date($ids){
        $this->_pagedata['ids'] = $ids;
        $this->_pagedata['today'] = date("Y-m-d");

        $this->display('deliver/shipping_change_deliver_date.html');
    }

    function do_change_deliver_date(){
        $post = $this->input->post();
        $ids = $post['ids'];
        $deliver_date = $post['deliver_date'];
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);

        if($this->platform_id==1){
            if(count($ids_arr)>1){
                $this->showJson(array('status'=>'error', 'msg' => '修改配送时间只允许单挑操作.'));
            }else{
                $shipping_info = $this->shipping_order_model->getShippingInfoByArray("equipment_id,operation_id,id,shipping_no,send_date,deliver_time,dm_id,order_type,shipping_no,dm_name",$ids_arr);
                $equipment_info = $this->equipment_model->findByBoxId($shipping_info[0]['equipment_id'],true);

                $address_info = $this->equipment_model->get_box_address($shipping_info[0]['equipment_id']);
//                var_dump($shipping_info);exit;

                $update_direct = false;
                if($shipping_info[0]['operation_id']==0||$shipping_info[0]['operation_id']==4){ //上海鲜动的未出库  的单子改时间直接操作
                    $update_direct = true;
                }else{
                    $res = $this->request_tms_cancel_order($shipping_info[0]['shipping_no'],$equipment_info['send_type']);

                    if($res['code']==200){

                        $equipment_log[] = array(
                            'equipment_id'=>$shipping_info[0]['equipment_id'],
                            'shipping_no'=>$shipping_info[0]['shipping_no']
                        );
//                    var_dump($equipment_log);exit;
//                    $deliver_date = $deliver_date;

                        $shipping_data[] = array(
                            "channelCode"=>"CITY_SHOP_BOX",
                            "cont_cell_ph"=>$equipment_info['mobile'],
                            "cont_name"=>$equipment_info['admin_alias'],
                            "createUser"=>"CITY_BOX_SYSTEM",
                            "da_address"=>$address_info['province'].$address_info['address'],//$equipment_info['address'],
//                        "deliveryTime"=>$deliver_date." 14:00:00.0",
                            "deliveryTime"=>$deliver_date." ".($equipment_info["hope_deliver_time"]?$equipment_info["hope_deliver_time"]:'09:00:00'),
//            "fullArea"=>"", 可为空
//            "isNeedPickup"=>0,
                            "orderDate"=>time()."000",
                            "order_memo"=>$equipment_info['name']."__".$equipment_info['goods_allocation'],  //配送的货量，需要人工输入
                            "regionType"=>1,//默认1
                            "sapCode"=>$equipment_info['replenish_location'],  //盒子的取货城超的sapCode    equipment 表的 replenish_location
                            "startDeliveryDate"=>$deliver_date." ".($equipment_info["min_deliver_time"]?$equipment_info["min_deliver_time"]:'07:00:00'),
                            "order_no"=>$shipping_info[0]['shipping_no'],
                            "sendType"=>$equipment_info['send_type'],
                            "boxCode"=>$equipment_info["code"],
                            "boxLevel"=>$equipment_info["level"]?$equipment_info["level"]:'A'
                        );
                    }else{
                        $this->showJson(array('status'=>'error', 'msg' => $res['msg']));
                    }
                }

            }

//        var_dump($equipment_log,$shipping_data);exit;
            if(!empty($equipment_log)&&!$update_direct){
                $requst = $this->request_tms_deliver($shipping_data,$equipment_log);

                if($requst['msg'] == 'failed') {
                    $this->showJson(array('status' => 'error', 'msg' => 'tms接口故障,请联系管理员.'));
                }else{
                    $rs = $this->shipping_order_model->shipping_order_update_where_in(array('send_date'=>$deliver_date),array('platform_id'=>$this->platform_id),$ids_arr);
                }
            }else{
                $rs = $this->shipping_order_model->shipping_order_update_where_in(array('send_date'=>$deliver_date),array('platform_id'=>$this->platform_id),$ids_arr);
            }
        }else{
            $rs = $this->shipping_order_model->shipping_order_update_where_in(array('send_date'=>$deliver_date),array('platform_id'=>$this->platform_id),$ids_arr);
        }
        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }

    //上海鲜动 推送tms
    function do_tms_deliver($ids){
//        $post = $this->input->post();
//        $ids = $post['ids'];
        $is_car = $this->input->get('is_car');
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);

        $rs = $this->shipping_order_model->getShippingInfoByArray("equipment_id,id,shipping_no,send_date,deliver_time,dm_id,order_type,shipping_no,dm_name",$ids_arr);


//        var_dump($rs);exit;
        //$this->load->model("warehouse_model");
        $stores = $this->equipment_model->get_store_list_byCode();
        foreach($rs as $v){
            //tms request 部分
            $equipment_info = $this->equipment_model->findByBoxId($v['equipment_id'],true);

            $address_info = $this->equipment_model->get_box_address($v['equipment_id']);

            $equipment_info['admin_id'];
            $send_type = !$is_car ? $equipment_info['send_type'] : 'car';
            $this->shipping_order_model->shipping_order_update(array('dm_id'=>0,'operation_id'=>4, 'send_type' => $send_type),array('id'=>$v['id']));

            if(($equipment_info['send_type'] == 'motor'|| $equipment_info['send_type'] == 'car')&&(!$v['dm_id']&&!$v['dm_name'])&&$v['order_type']==1&&$equipment_info['replenish_location']!='default'){//电瓶车、汽车、未分配过、出库单菜推送、  商户配送仓default的不推送tms
                //$wh = $this->warehouse_model->get_store_warehouse($equipment_info['replenish_warehouse']);

                $sapCode = $send_type == 'car' ? $equipment_info['replenish_warehouse'] : $equipment_info['replenish_location'];
                $equipment_log[] = array(
                    'equipment_id'=>$v['equipment_id'],
                    'shipping_no'=>$v['shipping_no']
                );
//                    var_dump($equipment_log);exit;
                $deliver_date = date('Y-m-d',strtotime($v['send_date']));
                $lonlat = explode(',', $equipment_info['baidu_xyz']);
                $shipping_data[] = array(
                    "channelCode"=>"CITY_SHOP_BOX",
                    "cont_cell_ph"=>$equipment_info['mobile'],
                    "cont_name"=>$equipment_info['admin_alias'],
                    "createUser"=>"CITY_BOX_SYSTEM",
                    "da_address"=>$address_info['province'].$address_info['address'],//$equipment_info['address'],
                    'longitude'=>(float)$lonlat[0],
                    'latitude'=>(float)$lonlat[1],
//                        "deliveryTime"=>$deliver_date." 14:00:00.0",
                    "deliveryTime"=>$deliver_date." ".($equipment_info["hope_deliver_time"]?$equipment_info["hope_deliver_time"]:'09:00:00'),
                    "fullArea"=>!empty($stores[$sapCode]) ? $stores[$sapCode] : '',
//            "isNeedPickup"=>0,
                    "orderDate"=>time()."000",
                    "order_memo"=>$equipment_info['name']."__".$equipment_info['goods_allocation'],  //配送的货量，需要人工输入
                    "regionType"=>1,//默认1
                    "sapCode"=>$sapCode,  //盒子的取货城超的sapCode    equipment 表的 replenish_location
                    "startDeliveryDate"=>$deliver_date." ".($equipment_info["min_deliver_time"]?$equipment_info["min_deliver_time"]:'07:00:00'),
                    "order_no"=>$v['shipping_no'],
                    "sendType"=>$send_type,
                    "boxCode"=>$equipment_info["code"],
                    "boxLevel"=>$equipment_info["level"]?$equipment_info["level"]:'A'


//                        'mobile'=>$equipment_info['mobile'],
//                        'name'=>$equipment_info['admin_alias'],
//                        'address'=>$equipment_info['address'],
//                        'deliveryTime'=>$deliver_date." 09:00:00.0",
//                        'orderDate'=>time()."000",
//                        'order_memo'=>'城超盒子补货，货物详单以纸质单据为准',
//                        'sapCode'=>$equipment_info['replenish_location'],
//                        'startDeliveryDate'=>$deliver_date." 12:00:00.0",
                );
            }
        }

//        var_dump($equipment_log,$shipping_data);

        if(!empty($equipment_log))
            $requst = $this->request_tms_deliver($shipping_data,$equipment_log);

        if($requst['msg'] == 'failed'){
            $this->showJson(array('status'=>'error', 'msg' => 'tms接口故障,请联系管理员.'));
        }else{
            if($rs)
                $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
            else
                $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
        }
    }

    //天天果园 推送oms
    function do_oms_deliver($ids)
    {
//        $post = $this->input->post();
//        $ids = $post['ids'];
        $ids = urldecode($ids);
        $ids_arr = explode('|', $ids);
        $ids_arr = array_filter($ids_arr);

        $rs = $this->shipping_order_model->getShippingInfoByArray("equipment_id,time,id,operation_id,shipping_no,send_date,deliver_time,dm_id,order_type,shipping_no,dm_name", $ids_arr);


//        var_dump($rs);exit;
        $orders = [];
        foreach ($rs as $v) {
            if ($v['order_type'] != 1) {
                $this->showJson(array('status' => 'error', 'msg' => '出错: 该操作只能是出库单'));
            }

            if (!in_array($v['status'], [0, 2])) {
                $this->showJson(array('status' => 'error', 'msg' => '出错: 该操作的订单状态必须是: 未出库 或 推送中'));
            }

            $products = $this->db->select('op.*,p.product_no,p.product_name')
                ->from('shipping_order_products op')
                ->join('product p', 'p.id=op.product_id')
                ->where(['order_id' => $v['id']])
                ->get()->result_array();

            $items = [];
            $money = 0;
            foreach ($products as $pro) {
                if (empty($pro['product_no'])) {
                    $this->showJson(array('status' => 'error', 'msg' => '出错: 果园外部编码为空: ' . $pro['product_name']));
                }
                $money += $pro['total_money'];
                array_push($items, [
                    "prdno" => $pro['product_no'],
                    "price" => $pro['price'],
                    "discount" => 0,
                    "add_discount" => 0,
                    "disPrice" => $pro['price'],
                    "count" => $pro['add_qty'],
                    "cardAmount" => 0,
                    "itemDisAmount" => 0,
                    "totalAmount" => $pro['total_money'],
                    "distype" => 2,
                    "primaryCode" => "",
                    "disCode" => "",
                    "score" => 0,
                    "saletype" => 1,
                    "groupName" => '',
                ]);
            }

            $equipment_info = $this->equipment_model->findByBoxId($v['equipment_id'], true);

            $address_info = $this->equipment_model->get_box_address($v['equipment_id']);
            $consignee_info = [
                "province" => $address_info['province'],
                "city" => $address_info['province'] == '上海'? '上海市':$address_info['city'],
                "region" => $address_info['province'] == '上海'? $address_info['city']: $address_info['area'],
                "reAddress" => $address_info['address'],
                "receiver" => $equipment_info['admin_alias'],
                "rePhone" => "",
                "lonLat" => $equipment_info['baidu_xyz'],
                "reMobile" => $equipment_info['mobile']
            ];

            $order = array(
                "p_order_name" => '',
                "type" => 30,
                "enterpriseno" => '',
                "chId" => 13,
                "isActivity" => 0,
                "orderNo" => $v['shipping_no'],
                "createdate" => $v['time'],
                "payDate" => $v['time'],
                "sendDate" => date('Y-m-d', strtotime($v['send_date'])),
                "delivertime" => 1,
                "delDay" => 0,
                "receiverDate" => date('Y-m-d', strtotime($v['send_date'])),
                "billno" => "",
                "score" => 0,
                "gcardinfo" => "",
                "note" => "",
                "orderAmount" => $money,
                "freightFee" => 0,
                "totalAmount" => $money,
                "disamount" => 0,
                "dedamount" => 0,
                "ispay" => 0,
                "payment" => 12,//
                "status" => 0,
                "deliverystate" => 2,
                "activityno" => '',
                "invoice_info" => (object)[],
                "member_info" => (object)[],
                "consignee_info" => $consignee_info,
                "wh" => "",
                "order_items" => $items,
                "payment_info" => [],
                "isCancel" => 0,
                "isPrint" => 0,
                "pmt_detail" => [],
                "lgcCode" => $this->platform_id == 5 ? 'G020' : 'G001',//上海：G001 广州：G020
            );


            $orders[] = $order;
        }

        if (empty($orders)) {
            $this->showJson(array('status' => 'error', 'msg' => '系统异常，请稍候再试.'));
        }

        $this->load->library('oms');

        $req_time = date("Y-m-d H:i:s");

        $this->shipping_order_model->shipping_order_update_where_in(array('status' => 2), array('status' => 0), $ids_arr);
        $rs = $this->oms->call('order_sync', $orders);
        $logs = [];
        foreach ($orders as $o) {
            $logs[] = [
                'box_no' => $o['orderNo'],
                'req_body' => json_encode($o, JSON_UNESCAPED_UNICODE),
                'req_time' => $req_time,
                'req_type' => 'oms_deliver',
                'response' => json_encode($rs, JSON_UNESCAPED_UNICODE),
                'response_time' => date("Y-m-d H:i:s"),
            ];
        }
        $this->oms->log($logs, true);

        if (isset($rs['code']) && isset($rs['msg'])) {
            $this->showJson(array('status' => 'error', 'msg' => $rs['msg']));
        }
        $this->shipping_order_model->shipping_order_update_where_in(array('dm_id' => 0, 'operation_id' => 4), array(), $ids_arr);
        $this->showJson(array('status' => 'success', 'msg' => '操作成功.'));
    }

    public function do_oms_deliver_cancel()
    {
        $params = $this->input->post();
        $row = $this->db->get_where('shipping_order', ['shipping_no' => $params['shipping_no'], 'platform_id' => $this->platform_id, 'is_del' => 0])->row_array();
        if(empty($row)){
            $this->showJson(array('status' => 'error', 'msg' => '出错: 订单不存在'));
        }

        if ($row['order_type'] != 1) {
            $this->showJson(array('status' => 'error', 'msg' => '出错: 该操作只能是出库单'));
        }

        if($row['operation_id'] != 4){
            $this->showJson(array('status' => 'error', 'msg' => '出错: 订单不是已推送，不能取消'));
        }

        $data = [
            'orderNo' => $params['shipping_no'],
            'state'   => 'cancel',
            'chId'    => 13,
            'oms_refund' => 0,
            'source' => '',
        ];

        $this->load->library('oms');
        $req_time = date("Y-m-d H:i:s");
        $rs = $this->oms->call('order_state', $data);
        $this->oms->log([
            'box_no' => $params['shipping_no'],
            'req_body' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'req_time' => $req_time,
            'req_type' => 'oms_deliver_cancel',
            'response' => json_encode($rs, JSON_UNESCAPED_UNICODE),
            'response_time' => date("Y-m-d H:i:s"),
        ]);
        if (isset($rs['code']) && isset($rs['msg'])) {
            $this->showJson(array('status' => 'error', 'msg' => $rs['msg']));
        }
        $this->shipping_order_model->shipping_order_update(['is_del' => 1], ['id' => $row['id']]);
        $this->showJson(array('status' => 'success', 'msg' => '操作成功.'));
    }

    //ajax  配送完成
    public function finish_shipping_order($ids){
        $this->_pagedata['ids'] = $ids;
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $rs = $this->shipping_order_model->shipping_order_update_where_in(array('operation_id'=>3),array('platform_id'=>$this->platform_id),$ids_arr);
        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }

    public function shipping_sync_succ($shipping_no)
    {
        if(empty($shipping_no)){
            $this->showJson(array('status' => 'error', 'msg' => '参数错误'));
        }

        $existed = $this->db->get_where('shipping_order', ['shipping_no' => $shipping_no])->row_array();
        if(empty($existed) || !in_array($existed['status'], [0,2])){
            $this->showJson(array('status' => 'error', 'msg' => '条件不满足'));
        }

        $this->db->update('shipping_order', ['status' => 1], ['id' => $existed['id']]);
        $this->showJson(array('status' => 'success', 'msg' => '更新成功'));
    }

    //搜配送员的auto_complete
    function ajax_get_deliver_member_ap(){
        $params = $this->input->post();
        $query = $params['query'];
        $data = $this->db->select('id,name,mobile')->from('deliver_member')->where(array(
            'name like'=>'%'.$query.'%'
        ))->or_where(array('mobile like'=>'%'.$query.'%'))->limit(10)->get()->result_array();
        echo json_encode($data);
    }

     function get_last_search(){
        $last_search = $this->input->cookie("dil_member_last_search");
        if(empty($last_search)){
            $last_search= json_encode(array(
                0=>'',
                1=>'',
                2=>'',
                3=>'',
                4=>''
            ));
        }
        $last_search = json_decode($last_search,1);
        return $last_search;
    }

    function set_last_serch($new_search,$last_search){
        for($i=5;$i>0;$i--){
            $last_search[$i] = $last_search[($i-1)];
        }
        unset($last_search[0],$last_search[5]);
        array_unshift($last_search,$new_search);
        $last_search = json_encode($last_search);
        $this->input->set_cookie("dil_member_last_search",$last_search,108000);
    }

    //给配送单选中的配送员添加权限
    function add_permission_2_shipping_order(){
        $posts = $this->input->post();
        $ids = urldecode($posts['ids']);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $dm_id = $posts['dm_id'];
        $mobile = $posts['mobile'];
        $name = $posts['name'];
        $today = date("Y-m-d");

        $cookie_val  = array(
            'id'=>$dm_id,
            'mobile'=>$mobile,
            'name'=>$name
        );
        $last_search = $this->get_last_search();
        $this->set_last_serch($cookie_val,$last_search);

        $rs = $this->shipping_order_model->getShippingInfoByArray("equipment_id,id,shipping_no,send_date,deliver_time,dm_id,order_type,shipping_no",$ids_arr);
        if(!empty($rs)){
            $this->db->trans_begin();
            foreach($rs as $v){
                //权限部分
                $deliver_date = date('Y-m-d',strtotime($v['send_date']));
                $this->shipping_order_model->shipping_order_update(array('dm_id'=>$dm_id,'operation_id'=>2),array('id'=>$v['id']));
                $data[] = array(
                    'equipment_id'=>$v['equipment_id'],
                    'mobile'=>$mobile,
                    'add_time'=>$deliver_date,
                    'end_time'=>$deliver_date." 23:59:59",
                    'status'=>1,
                    'shipping_no'=>$v['shipping_no'],
                    'platform_id'=>$this->platform_id
                );
            }
            $r = $this->shipping_permission_model->insertBatchPermissionData($data);
            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->showJson(array('status'=>'error', 'msg' => '操作失败请稍候再试（请勿重复将同一配送单分配给一个配送员）.'));
            } else {
                $this->db->trans_commit();
                $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
            }
        }else{
            $this->showJson(array('status'=>'error', 'msg' => '单号异常.'));
        }
    }

    //添加下架单
    public function off_shipping_add_order($equipment_id){
        $datetime = date('Y-m-d');
        $datetime2 = date('Y-m-d',strtotime("+1 day"));
        $datetime_tomorrow = date('Y-m-d',strtotime("+2 days"));
        $datetime3 = date('Y-m-d',strtotime("+3 days"));
        $datetime4 = date('Y-m-d',strtotime("+4 days"));
        $datetime5 = date('Y-m-d',strtotime("+5 days"));
        $datetime6 = date('Y-m-d',strtotime("+6 days"));
        $datetime7 = date('Y-m-d',strtotime("+7 days"));
        $datetime_options = array($datetime,$datetime2,$datetime_tomorrow,$datetime3,$datetime4,$datetime5,$datetime6,$datetime7);

        $equipment = $this->equipment_model->findById($equipment_id);
        $boxId = $equipment['equipment_id'];

        $expired_cache = $this->redis->hGet("pro_expire_hash",'eq_id_'.$boxId);
        $expired_list = json_decode($expired_cache,1);

        if ($equipment['admin_id'] <= 0){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("请先配置该设备的管理员！");history.back();</script></head>';
        }
        if (!$equipment['address']){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("请先配置该设备的地址！");history.back();</script></head>';
        }

        //获取实时库存
        $stock_data = $this->equipment_label_model->getStock($boxId);
        $new_stock_data = array();
//        var_dump($expired_list['off_num']);exit;
        foreach ($stock_data as $stock){
            $new_stock_data[$stock['product_id']] = array('product_id'=>$stock['product_id'],'stock'=>$stock['count_num'],'product_name'=>$stock['product_name'],'price'=>$stock['price'],'off_num'=>count($expired_list['products'][$stock['product_id']]),'off_arr'=>$expired_list['products'][$stock['product_id']]);
        }

        $this->_pagedata['datetime_options'] = $datetime_options;
        $this->_pagedata['arr_table'] = $new_stock_data;
        $this->_pagedata['equipment_id'] = $equipment_id;
        $this->_pagedata['name'] = $equipment['name'];
        $this->page('deliver/add_off_order.html');
    }

    public function off_shipping_add_order_save(){
        $equipment_id = $_POST['equipment_id'];
        $send_date = $_POST['send_date'];
        $products = $_POST['products'];
        if (!$equipment_id || !$send_date || !$products){
            echo '<head><meta http-equiv="Content-Type" content="text/html" charset="utf-8"><script>alert("参数错误！");location.href="/equipment/index";</script></head>';
        }
        $shipping_no = 'cb'.date("ymdi").$this->rand_code(4);
        $equipment = $this->equipment_model->findById($equipment_id);
        //是否纸质单
        //直接纸质单流程
        $boxId = $equipment['equipment_id'];
        $total_amount_paper = 0.00;
        $this->db->trans_begin();
        //纸质订单
        foreach($products as $product){
            $total_amount_paper = $total_amount_paper + $product['price'] * $product['off_num'];
        }
        $deliver_time = '09:00-12:00';
        //生成纸质订单表和订单地址表
        $shipping_no_paper = $this->shipping_no('cbo');
        $data = array(
            'shipping_no'=>$shipping_no_paper,
            'time'=>date('Y-m-d H:i:s'),
            'status'=>1,
            'operation_id'=>0,
            'equipment_id'=>$boxId, //equipment表equipment_id
            'total_amount'=>$total_amount_paper,
            'send_date'=>$send_date,
            'deliver_time'=>$deliver_time,
            'is_paper_order'=>1,
            'order_type'=>2
        );
        $order_id_paper = $this->shipping_order_model->insertData($data);
        $address_data = array(
            'order_id'=>$order_id_paper,
            'address'=>$equipment['address'],
            'name'=>$equipment['admin_alias'],
            'mobile'=>$equipment['mobile'],
            'province'=>$equipment['province'],
            'city'=>$equipment['city'],
            'area'=>$equipment['area']
        );
        $address_rs_paper = $this->shipping_order_model->insertAddressData($address_data);

        foreach($products as $product){
            if ($product['off_num'] != '' && $product['off_num'] != 0){
                //纸质订单产品表
                $product_data = array(
                    'order_id'=>$order_id_paper,
                    'product_id'=>$product['product_id'],
                    'product_name'=>$product['product_name'],
                    'price'=>$product['price'],
                    'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
                    'stock'=>$product['stock'] ? $product['stock'] : 0,
                    'add_qty'=>$product['off_num'],
                    'real_num'=>0,
                    'total_money'=>$product['price'] * $product['off_num']
                );
                $product_rs_paper = $this->shipping_order_model->insertProductsData($product_data);
            }
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
        } else {
            $this->db->trans_commit();
        }

        redirect('/deliver/shipping');
    }

    // private  推送tms
    function request_tms_deliver($data,$equipment_log){
        $this->load->library('tms');
//        $data = array(
//            "channelCode"=>"CITY_SHOP_BOX",
//            "cont_cell_ph"=>$shipping_data['mobile'],
//            "cont_name"=>$shipping_data['name'],
//            "createUser"=>"CITY_BOX_SYSTEM",
//            "da_address"=>$shipping_data['address'],
//            "deliveryTime"=>$shipping_data['deliveryTime'],
////            "fullArea"=>"", 可为空
////            "isNeedPickup"=>0,
//            "orderDate"=>$shipping_data['orderDate'],
//            "order_memo"=>$shipping_data['order_memo'],  //配送的货量，需要人工输入
//            "regionType"=>1,//默认1
//            "sapCode"=>$shipping_data['sapCode'],  //盒子的取货城超的sapCode    equipment 表的 replenish_location
//            "startDeliveryDate"=>$shipping_data['startDeliveryDate'],
//        );
        $params = array(
            'method' => 'citysuper.order.upload',
//            'omsOrderJson'=>'[{"channelCode":"CITY_SHOP_BOX","cont_cell_ph":"13918537453","cont_name":"王硕","createUser":"H005-wangshuo","da_address":"宜山路1289号复星B座","deliveryTime":"2017-06-08 11:00:00.0","fullArea":"SHPX-375","isNeedPickup":0,"orderDate":1496878696748,"order_memo":"干货 1 箱;冷藏 1 袋;","regionType":1,"sapCode":"H005","startDeliveryDate":"2017-06-08 10:00:00.0"}]',
            'omsOrderJson'=>json_encode($data)
        );
        $response = $this->tms->tms_call($params,$equipment_log);
        if ($response['success'] == true)
        {
            $res = array('code'=>'200','msg'=>'succ');
        }else{
            $res = array('code'=>'200','msg'=>'failed');
        }
        return $res;
    }


    // private  重置tms运单
    function request_tms_cancel_order($shipping_no,$sendType){
        $this->load->library('tms');
//        $data = array(
//            "channelCode"=>"CITY_SHOP_BOX",
//            "cont_cell_ph"=>$shipping_data['mobile'],
//            "cont_name"=>$shipping_data['name'],
//            "createUser"=>"CITY_BOX_SYSTEM",
//            "da_address"=>$shipping_data['address'],
//            "deliveryTime"=>$shipping_data['deliveryTime'],
////            "fullArea"=>"", 可为空
////            "isNeedPickup"=>0,
//            "orderDate"=>$shipping_data['orderDate'],
//            "order_memo"=>$shipping_data['order_memo'],  //配送的货量，需要人工输入
//            "regionType"=>1,//默认1
//            "sapCode"=>$shipping_data['sapCode'],  //盒子的取货城超的sapCode    equipment 表的 replenish_location
//            "startDeliveryDate"=>$shipping_data['startDeliveryDate'],
//        );
        $params = array(
            'method' => 'thirdpart.order.update',
            'wbNo'=>$shipping_no,
            'status'=>'CANCELLED',
            'sendType'=>$sendType
        );
        $response = $this->tms->tms_call($params,array(array('shipping_no'=>$shipping_no)));
//        var_dump($response);
        if ($response['success'] == true)
        {
            $res = array('code'=>'200','msg'=>'succ');
        }else{
            $res = array('code'=>'300','msg'=>$response['errorToken']);
        }
        return $res;
    }

    //删除配送单
    function shipping_delete_order($ids){
        $ids = urldecode($ids);
        $ids_arr = explode('|',$ids);
        $ids_arr = array_filter($ids_arr);
        $rs = $this->shipping_order_model->shipping_order_update_where_in(array('is_del'=>1),array('platform_id'=>$this->platform_id),$ids_arr);
        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }

    //编辑配送单商品数量
    function shipping_order_edit(){
        $posts = $this->input->post();
        $name = $posts['name'];
        $id = $posts['id'];
        $value = $posts['value'];
        $equipment_id = $posts['equipment_id'];

        $name = 'add_qty';//暂时只能改这个字段
        if(empty($id)||empty($value)){
            $this->showJson(array('status'=>'error', 'msg' => '入参有误.'));
        }

        $data = array($name=>$value);
        $where = array('id'=>$id);
        $order_product_info  =  $this->shipping_order_model->get_shipping_order_product($where);
        $data['total_money'] = number_format($order_product_info['price'] * $value,2);

        if (in_array($this->platform_id, [1, 2])) {
            $equipment = $this->db->select('replenish_warehouse')->from('equipment')->where(array('id' => $equipment_id))->get()->row_array();
            //仓库库存
            $this->load->model("warehouse_model");
            $wh_stock = end($this->warehouse_model->get_stock([
                'replenish_warehouse' => $equipment['replenish_warehouse'],
                'product_id' => $order_product_info['product_id']
            ]));
            if($value > 0 && (empty($wh_stock) || ($wh_stock['stock_usable'] + $order_product_info['add_qty']) < $value)){
                $this->showJson(array('status'=>'error', 'msg' => $order_product_info['product_name'].'可用库存不足:' . (int)$wh_stock['stock_usable']));
            }
        }


        $rs = $this->shipping_order_model->update_shipping_order_product($data,$where);

        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.' , 'data'=>$data));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }

    //删除配送单商品
    function shipping_order_product_delete(){
        $posts = $this->input->post();
        $id = $posts['id'];

        $where = array('id'=>$id);
        $rs = $this->shipping_order_model->delete_shipping_order_product($where);

        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }

    //添加配送单商品
    function shipping_order_product_add(){
        $product = $this->input->post();
        $product_data = array(
            'order_id'=>$product['order_id'],
            'product_id'=>$product['product_id'],
            'product_name'=>$product['product_name'],
            'price'=>$product['price'],
            'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
            'stock'=>$product['stock'] ? $product['stock'] : 0,
            'add_qty'=>$product['add_num'],
            'real_num'=>0,
            'total_money'=>$product['price'] * $product['add_num']
        );

        if (in_array($this->platform_id, [1, 2])) {
            $equipment = $this->db->select('replenish_warehouse')->from('equipment')->where(array('id' => $product['equipment_id']))->get()->row_array();
            //仓库库存
            $this->load->model("warehouse_model");
            $wh_stock = end($this->warehouse_model->get_stock([
                'replenish_warehouse' => $equipment['replenish_warehouse'],
                'product_id' => $product_data['product_id']
            ]));
            if($product_data['add_qty'] > 0 && (empty($wh_stock) || $wh_stock['stock_usable'] < $product_data['add_qty'])){
                $this->showJson(array('status'=>'error', 'msg' => $product_data['product_name'] . '可用库存不足: ' . (int)$wh_stock['stock_usable']));
            }
        }

        $rs = $this->shipping_order_model->insertProductsData($product_data);

        if($rs)
            $this->showJson(array('status'=>'success', 'msg' => '操作成功.'));
        else
            $this->showJson(array('status'=>'error', 'msg' => '系统异常，请稍候再试.'));
    }
    
    private function arraySortByKey($array=array(), $key='', $asc = true)
    {
        $result = array();
        // 整理出准备排序的数组
        foreach ( $array as $k => &$v ) {
            $values[$k] = isset($v[$key]) ? $v[$key] : '';
        }
        unset($v);
        // 对需要排序键值进行排序
        $asc ? asort($values) : arsort($values);
        // 重新排列原有数组
        foreach ( $values as $k => $v ) {
            $result[$k] = $array[$k];
        }
    
        return $result;
    }

    private function isEquipmentMarking($type){
        return in_array($type,array('rfid-1','rfid-2'));
        //return $type == 'rfid-1';
    }

    public function batch_shipping_config()
    {
        $this->title = '批量预存量';
        $this->page("deliver/batch_shipping_config.html");
    }

    public function batch_shipping_config_data()
    {
        $params = array_merge($_GET, $_POST);
        if (empty($params['product_id'])) {
            $this->validform_fail(['msg' => '商品ID不能为空']);
        }

        $product = $this->deliver_model->batch_shipping_config_data($params, $this->platform_id, $this->adminid);

        if (isset($product['status'], $product['msg'])) {
            $this->validform_fail($product);
        }

        $this->validform_success(['data' => $product]);
    }

    public function batch_shipping_config_save()
    {
        $params = array_merge($_GET, $_POST);
        if (empty($params['products'])) {
            $this->validform_fail(['msg' => '商品不能为空']);
        }

        if (empty($params['type']) || !in_array($params['type'], [1, 2])) {
            $this->validform_fail(['msg' => '批量操作类型错误']);
        }

        $params['products'] = json_decode($params['products'], true);
        $this->deliver_model->batch_shipping_config_save($params, $this->platform_id);
        $this->validform_success(['msg' => '保存成功!']);
    }

    public function shipping_config_data($id){
        $rows = $this->deliver_model->get_deliver_shipping_list((int)$id);
        $this->validform_success(['rows' => $rows]);
    }

    public function shipping_config_save()
    {
        $params = array_merge($_GET, $_POST);
        $params['clear'] = isset($params['clear']) && $params['clear'] == 1;

        if (empty($params['eid'])) {
            $this->validform_fail(['msg' => '请选择设备']);
        }

        if(!$params['clear']) {
            if (empty($params['products'])) {
                $this->validform_fail(['msg' => '商品列表不能为空']);
            }
            if (!is_array($params['products'])) {
                $params['products'] = json_decode($params['products'], true);
                if (JSON_ERROR_NONE !== json_last_error()) {
                    $this->validform_fail(['msg' => '商品列表解析错误']);
                }
            }

            if (empty($params['products'])) {
                $this->validform_fail(['msg' => '商品列表数据异常']);
            }
        }

        $rs = $this->deliver_model->shipping_config_save($params);
        if(!$rs){
            $this->validform_fail(['msg' => '保存失败']);
        }
        $this->validform_success(['msg' => '保存成功']);
    }
}
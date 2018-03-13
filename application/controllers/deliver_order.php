<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Deliver_order extends MY_Controller
{
    public $workgroup = 'deliver';
    public $redis;

    public function __construct()
    {
        parent::__construct();
        $this->load->model("deliver_order_model");
        $this->load->helper('public');
    }

    public function auto_preset()
    {
        $params = array_merge($_GET, $_POST);
        $rows = $this->deliver_order_model->auto_preset($params);
        echo json_encode(compact('rows'), JSON_UNESCAPED_UNICODE);
        die;
    }

    public function replenish_config()
    {
        $this->title = '自动补货商品配置';
        $this->page("deliver_order/replenish_config.html");
    }

    public function replenish_config_data()
    {
        $params = array_merge($_GET, $_POST);
        $limit = isset($params['limit']) ? $params['limit'] : 10;
        $offset = isset($params['offset']) ? $params['offset'] : 0;
        $order_by = 'c.id desc';

        $where = ['c.platform_id' => $this->platform_id];
        if (isset($params['name'])) {
            if (preg_match('#^@(\d+)$#', $params['name'], $m)) {
                $where['p.id'] = $m[1];
            } else {
                $where['p.product_name like'] = "%" . $params['name'] . "%";
            }
        }

        if (isset($params['is_valid'])) {
            $where['c.is_valid'] = $params['is_valid'];
        }

        if (isset($params['valid_date'])) {
            $today = date('Y-m-d');
            if($params['valid_date']){
                $where['c.start_date <='] = $today;
                $where['c.end_date >='] = $today;
                $where['c.is_valid'] = 1;
            }else{
                $where["(c.start_date > '${today}' || c.end_date < '${today}' || c.is_valid=0)"] = null;
            }
        }

        if (isset($params['sort'])) {
            $order_by = $params['sort'] . ' ' . $params['order'];
        }

        $total = $this->deliver_order_model->replenish_config_data(compact('where'), true);
        $rows = $this->deliver_order_model->replenish_config_data(compact('where', 'limit', 'offset', 'order_by'));

        echo json_encode(compact('total', 'rows'), JSON_UNESCAPED_UNICODE);
        die;

    }

    public function replenish_config_add()
    {
        $this->load->model("equipment_model");
        $equipments = $this->equipment_model->get_equipments(true);
        $this->_pagedata['equipments'] = $equipments;
        $this->_pagedata['start_date'] = date('Y-m-d');
        $this->_pagedata['end_date'] = date('Y-m-d', strtotime('+1 week'));
        $this->_pagedata['rc_type'] = Deliver_order_model::RC_TYPE;
        $this->_pagedata['rc_eq_type'] = Deliver_order_model::RC_EQ_TYPE;
        $this->display("deliver_order/replenish_config_add.html");
    }

    public function replenish_config_edit($id)
    {
        $this->load->model("equipment_model");
        $equipments = $this->equipment_model->get_equipments(true);
        $replenish_config = $this->deliver_order_model->replenish_config_row((int)$id);
        $this->_pagedata['equipments'] = $equipments;
        $this->_pagedata['replenish_config'] = $replenish_config;
        $this->_pagedata['rc_type'] = Deliver_order_model::RC_TYPE;
        $this->_pagedata['rc_eq_type'] = Deliver_order_model::RC_EQ_TYPE;
        $this->display("deliver_order/replenish_config_edit.html");
    }

    public function replenish_config_add_save()
    {
        $params = $this->input->post();
        $params = array_map('trim', $params);
        if (empty($params['product_id']) || !preg_match('#^\d+$#', $params['product_id'])) {
            $this->validform_fail(['msg' => '商品ID无效']);
        }

        if ($params['type'] != 3 && (empty($params['qty']) || !preg_match('#^\d+$#', $params['qty']))) {
            $this->validform_fail(['msg' => '数量无效']);
        }

        if (empty($params['name'])) {
            $this->validform_fail(['msg' => '备注不能为空']);
        }

        if (empty($params['type']) || !preg_match('#^\d+$#', $params['type'])) {
            $this->validform_fail(['msg' => '类型无效']);
        }

        if (empty($params['eq_type']) || !preg_match('#^\d+$#', $params['type'])) {
            $this->validform_fail(['msg' => '设备类型无效']);
        }

        if (empty($params['eq_value']) && in_array((int)$params['eq_type'], [2])) {
            $this->validform_fail(['msg' => '等级不能为空']);
        }

        if (empty($params['eids']) && in_array((int)$params['eq_type'], [3])) {
            $this->validform_fail(['msg' => '请选择设备']);
        }

        if (empty($params['start_date']) || empty($params['end_date']) || $params['end_date'] < $params['start_date']) {
            $this->validform_fail(['msg' => '时间段错误']);
        }

        if ($params['eq_type'] == 3) {
            $params['eq_value'] = $params['eids'];
        }
        $params['eq_value'] = trim($params['eq_value'], ',');
        $data = [
            'product_id' => (int)$params['product_id'],
            'qty' => (int)$params['qty'],
            'name' => $params['name'],
            'type' => (int)$params['type'],
            'eq_type' => (int)$params['eq_type'],
            'eq_value' => $params['eq_type'] != 1 ? strtoupper($params['eq_value']) : '',
            'is_valid' => !empty($params['is_valid']) ? 1 : 0,
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
            'admin_name' => $this->operation_name,
            'platform_id' => $this->platform_id,
        ];
        if(empty($params['id'])){
            $data['created_at'] = date('Y-m-d H:i:s');
            $this->deliver_order_model->replenish_config_add_save($data);
        }else{
            $this->deliver_order_model->replenish_config_add_save($data, $params['id']);
        }
        $this->validform_success(['msg' => '保存成功']);
    }

    public function replenish_config_editable(){
        $params = $this->input->post();
        if (!isset($params['field']) || !isset($params['row'])) {
            $this->validform_fail(['msg' => '参数错误']);
        };
        $this->deliver_order_model->replenish_config_editable($params);
        $this->validform_success(['msg' => '更新成功']);
    }

    public function auto_replenish_add($eid)
    {
        $this->title = '新增出库单';
        $this->_pagedata['eid'] = (int)$eid;
        $this->page("deliver_order/auto_replenish_add.html");
    }

    public function auto_replenish()
    {
        $params = array_merge($_GET, $_POST);
        $params['no_check_auto'] = 1;
        $replenish = $this->deliver_order_model->auto_replenish($params, true);
        if(empty($replenish)){
            $this->validform_fail(['msg' => '请先设定预处理']);
        }
        echo json_encode($replenish, JSON_UNESCAPED_UNICODE);
        die;
    }

    public function product_info()
    {
        $params = array_merge($_GET, $_POST);
        $params['platform_id'] = $this->platform_id;
        $product = $this->deliver_order_model->product_info($params);
        if(!$product){
            $this->validform_fail(['msg' => '商品不存在']);
        }
        $this->validform_success(['data' => $product]);
    }

    public function replenish_save()
    {
        $params = array_merge($_GET, $_POST);
        $params = array_map('trim', $params);

        $equipment_id = $params['equipment_id'];
        $send_date = $params['send_date'];
        $send_batch = $params['send_batch'];
        $products = !empty($params['products']) ? json_decode($params['products'], true) : [];
        if (!$equipment_id || !$send_date || !$products){
            $this->validform_fail(['msg' => '参数错误！']);
        }

        $this->load->model("equipment_model");
        $this->load->model("shipping_order_model");
        $equipment = $this->equipment_model->findById($equipment_id);

        //仓库库存
        $this->load->model("warehouse_model");
        $wh_stock = $this->warehouse_model->get_stock([
            'replenish_warehouse' => $equipment['replenish_warehouse'],
        ]);
        if(empty($wh_stock)){
            $this->validform_fail(['msg' => '仓库库存不存在']);
        }
        $eq_add_qty = 0;
        foreach ($products as $prod){
            if($prod['add_qty'] <= 0){
                continue;
            }
            if(!isset($wh_stock[$prod['product_id']]) || $wh_stock[$prod['product_id']]['stock_usable'] < $prod['add_qty']){
                $this->validform_fail(['msg' => $prod['product_name'] .'库存不足; 仓库可用库存:'.(int)$wh_stock[$prod['product_id']]['stock_usable']]);
            }
            $eq_add_qty += $prod['add_qty'];
        }

        if($equipment['is_auto'] == 1){
            $eq_max_qty = $this->deliver_order_model->get_equipment_max_qty($equipment);
            $eq_qty = $this->deliver_order_model->get_equipment_stock($equipment['equipment_id']);
            if($eq_max_qty < $eq_qty + $eq_add_qty){
                $this->validform_fail(['msg' => '该设备最大数量:'.(int)$eq_max_qty. ', 超:' . ($eq_qty + $eq_add_qty - $eq_max_qty)]);
            }
        }

        $boxId = $equipment['equipment_id'];
        $total_amount_paper = 0.00;
        $this->db->trans_begin();
        //纸质订单
        foreach($products as $product){
            if ($product['add_qty'] > 0){
                $total_amount_paper = $total_amount_paper + $product['price'] * $product['add_qty'];
            }

        }
        $deliver_time = '09:00-12:00';
        //生成纸质订单表和订单地址表
        $shipping_no_paper = $this->shipping_no('cbp');
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
            if ($product['add_qty'] > 0){
                //纸质订单产品表
                $product_data = array(
                    'order_id'=>$order_id_paper,
                    'product_id'=>$product['product_id'],
                    'product_name'=>$product['product_name'],
                    'price'=>$product['price'],
                    'pre_qty'=>$product['pre_qty'] ? $product['pre_qty'] : 0,
                    'stock'=>$product['stock'] ? $product['stock'] : 0,
                    'add_qty'=>$product['add_qty'],
                    'real_num'=>0,
                    'total_money'=>$product['price'] * $product['add_qty']
                );
                $product_rs_paper = $this->shipping_order_model->insertProductsData($product_data);
            }
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            $this->validform_fail(['msg' => '生成出库单失败！']);
        }

        $this->db->trans_commit();
        $this->validform_success(['msg' => '生成出库单成功']);
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

    public function auto_enable()
    {
        $params = $this->input->get();
        if(!isset($params['enable']) || !in_array($params['enable'], [0, 1])){
            $this->validform_fail(['msg' => '参数异常']);
        }
        $rs = $this->db->update('equipment', ['is_auto' => (int)$params['enable']], ['equipment_id' => $params['equipment_id']]);
        if(!$rs){
            $this->validform_fail(['msg' => '操作失败']);
        }
        $this->validform_success(['msg' => '操作成功']);
    }
}
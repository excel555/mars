<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Bi extends MY_Controller
{
    public $workgroup = 'order';

    function __construct() {
        parent::__construct();
        $this->load->model("equipment_model");
        $this->load->model("product_class_model");

    }

    public function index(){
        $this->_pagedata['search_replenish_location'] = $search_replenish_location = $this->input->get('search_replenish_location');
        $this->_pagedata['search_equipment_id']       = $search_equipment_id       = $this->input->get('search_equipment_id');

        $this->_pagedata['search_parent_class']       = $search_parent_class       = $this->input->get('search_parent_class')?$this->input->get('search_parent_class'):0;
        $this->_pagedata['search_class']              = $search_class              = $this->input->get('search_class');

        $this->_pagedata['search_start_time']         = $search_start_time         = $this->input->get('search_start_time')?$this->input->get('search_start_time'):date('Y-m-d', strtotime('-1 days'));
        $this->_pagedata['search_end_time']           = $search_end_time           = $this->input->get('search_end_time')?$this->input->get('search_end_time'):date('Y-m-d', strtotime('-1 days'));
        $this->_pagedata['search_order']              = $search_order              = $this->input->get('search_order')?$this->input->get('search_order'):0;
        $day = $this->input->get('day');
        if($day==7){
            $this->_pagedata['search_start_time'] = $search_start_time = date('Y-m-d' , strtotime('-7 days'));
            $this->_pagedata['search_end_time']   = $search_end_time   = date('Y-m-d' , strtotime('-1 days'));
        }elseif($day==30){
            $this->_pagedata['search_start_time'] = $search_start_time = date('Y-m-d' , strtotime('-30 days'));
            $this->_pagedata['search_end_time']   = $search_end_time   = date('Y-m-d' , strtotime('-1 days'));
        }elseif($day==60){
            $this->_pagedata['search_start_time'] = $search_start_time = date('Y-m-01', strtotime('-1 months'));
            $this->_pagedata['search_end_time']   = $search_end_time   = date('Y-m-t' , strtotime('-1 months'));
        }

        $search_equipment_arr = $search_class_arr = $equipment_tmp = $class_tmp = array();
        //搜索设备
        if($search_equipment_id>0){
            $search_equipment_arr[]= $search_equipment_id;
        }
        if($search_replenish_location && $search_replenish_location!=-1 && $search_equipment_id<=0){
            $equipment_tmp = $this->equipment_model->get_equipment_by_code($search_replenish_location);
            foreach($equipment_tmp as $k=>$v){
                $search_equipment_arr[] = $v['equipment_id'];
            }
        }
        $search_equipment_arr = array_unique($search_equipment_arr);
        //搜索商品小分类
        if($search_class>0){
            $search_class_arr[]= $search_class;
        }
        if($search_parent_class>0 && $search_class<=0){
            $class_tmp = $this->product_class_model->getList(array('parent_id'=>$search_parent_class));
            foreach($class_tmp as $k=>$v){
                $search_class_arr[]= $v['id'];
            }
        }
        $search_class_arr = array_unique($search_class_arr);
        $where['platform_id'] = $this->platform_id;
        if($search_start_time){
            $where['addDate >='] = $search_start_time;
        }
        if($search_end_time){
            $where['addDate <='] = $search_end_time;
        }
        $this->db->from('bi_product');
        $this->db->where($where);
        if(!empty($search_equipment_arr)){
            $this->db->where_in('equipment_id', $search_equipment_arr);
        }
        if(!empty($search_class_arr)){
            $this->db->where_in('class_id', $search_class_arr);
        }
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['product_id']]['sale_num'] = intval($tmp[$v['product_id']]['sale_num'])+intval($v['sale_num']);
            $tmp[$v['product_id']]['order_num']= intval($tmp[$v['product_id']]['order_num'])+intval($v['order_num']);
            $tmp[$v['product_id']]['product_id'] = $v['product_id'];
        }
        if($_GET['explore'] == 1){//按照当前搜索条件导出功能
            $tmp = $this->array_sort($tmp, 'sale_num', 'desc');//实付商品数
            return $this->explore_result($tmp, $search_start_time, $search_end_time);
        }
        if($search_order == 0){
            $tmp = $this->array_sort($tmp, 'sale_num', 'desc');//实付商品数
        }else{
            $tmp = $this->array_sort($tmp, 'order_num', 'desc');///订单数
        }
        $tmp = array_slice($tmp, 0,15);
        $product_name = $sale_num = $order_num = array();
        foreach($tmp as $k=>$v){
            $product_name[] = $this->get_product_name($v['product_id']);//textStyle
            $sale_num[]     = $v['sale_num'];
            $order_num[]    = $v['order_num'];
        }

        $this->_pagedata['store_list']   = $this->equipment_model->get_store_list();
        $this->_pagedata['parent_class'] = $this->product_class_model->getList(array('parent_id'=>0));
        $this->_pagedata['product_class']= $this->product_class_model->getList(array('parent_id'=>$search_parent_class));
        $this->_pagedata['box_list']     = $this->equipment_model->get_equipment_by_code($search_replenish_location);
        $this->_pagedata['product_name'] = json_encode($product_name);
        $this->_pagedata['sale_num']     = json_encode($sale_num);
        $this->_pagedata['order_num']    = json_encode($order_num);

        $this->page('bi/index.html');
    }

    public function explore_result($tmp, $start, $end){
        $file_name="{$start}-{$end}单品销售统计.csv";

        foreach ($tmp as $k => $v) {
            $data[] = array(
            $this->get_product_name($v['product_id']),
            $this->get_product_class_name($v['product_id']),
                $v['sale_num'],
                $v['order_num'],
            );
        }

        @set_time_limit(0);
        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $exceler->setFileName($file_name . '-users.csv');
        $excel_title = array("商品名称", "商品分类", "实付商品件数", "实付订单数");
        $exceler->setTitle($excel_title);
        $exceler->setContent($data);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
        exit;
    }


    public function get_box(){//replenish_location
        $code_arr = $this->input->post('code_arr');
        $rs = $this->equipment_model->get_equipment_by_code($code_arr);
        $this->showJson(array('status'=>'success', 'param' => $rs));
    }

    public function get_product_class(){
        $id = $this->input->post('id');
        $rs = $this->product_class_model->getList(array('parent_id'=>$id));
        $this->showJson(array('status'=>'success', 'param' => $rs));
    }

    //对二维数组进行排序
    public function array_sort($arr,$keys,$type='asc'){
        $tmp = array();
        foreach($arr as $k=>$v){
            $tmp[$k] = $v[$keys];
        }
        if($type == "asc"){
            array_multisort($arr, SORT_ASC, SORT_STRING, $tmp, SORT_ASC);
        }else{
            array_multisort($arr, SORT_DESC, SORT_STRING, $tmp, SORT_DESC);
        }
        return $arr;
    }

    public function get_product_name($product_id){
        $this->db->from('product');
        $this->db->where('id', $product_id);
        $rs = $this->db->get()->row_array();
        return $rs['product_name'];
    }

    public function get_product_class_name($product_id){
        $this->db->select('product_class.name');
        $this->db->from('product');
        $this->db->join('product_class', 'product.class_id=product_class.id');
        $this->db->where('product.id', $product_id);
        $rs = $this->db->get()->row_array();
        return $rs['name'];
    }



    //当天实时商品看板
    public function today_product(){
        $this->_pagedata['today_time'] = date('Y-m-d H:i:s');
        $this->_pagedata['store_list']   = $this->equipment_model->get_store_list();
        $this->_pagedata['parent_class'] = $this->product_class_model->getList(array('parent_id'=>0));
        $this->page('bi/today_product.html');
    }

    public function today_table(){

        $search_replenish_location = $this->input->get('search_replenish_location');
        $search_equipment_id       = $this->input->get('search_equipment_id');

        $search_parent_class       = $this->input->get('search_parent_class')?$this->input->get('search_parent_class'):0;
        $search_class              = $this->input->get('search_class');
        $search_product_name       = $this->input->get('search_product_name');

        $sort  = $this->input->get('sort')?$this->input->get('sort'):'sale_num';
        $order = $this->input->get('order')?$this->input->get('order'):'desc';
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;

        $search_equipment_arr = $search_class_arr = $equipment_tmp = $class_tmp = array();
        //搜索设备
        if($search_equipment_id>0){
            $search_equipment_arr[]= $search_equipment_id;
        }
        if($search_replenish_location  && !$search_equipment_id){
            $equipment_tmp = $this->equipment_model->get_equipment_by_code($search_replenish_location);
            foreach($equipment_tmp as $k=>$v){
                $search_equipment_arr[] = $v['equipment_id'];
            }
        }
        $search_equipment_arr = array_unique($search_equipment_arr);
        //搜索商品小分类
        if($search_class>0){
            $search_class_arr[]= $search_class;
        }
        if($search_parent_class>0 && $search_class<=0){
            $class_tmp = $this->product_class_model->getList(array('parent_id'=>$search_parent_class));
            foreach($class_tmp as $k=>$v){
                $search_class_arr[]= $v['id'];
            }
        }
        $search_class_arr = array_unique($search_class_arr);
        $this->load->model('product_model');
        $product_list = $this->product_model->getProductByClass($search_class_arr, $search_product_name, 'id');
        $where['o.order_status'] = 1;
        $where['o.platform_id'] = $this->platform_id;
        $where['o.order_time >'] = date('Y-m-d 00:00:00');
        $this->db->select('count(op.order_name) as order_num , sum(op.qty) as sale_num, op.product_id ');
        $this->db->from('order_product op');
        $this->db->join("order o", 'op.order_name = o.order_name');
        $this->db->where($where);
        if(!empty($search_equipment_arr)){
            $this->db->where_in('o.box_no', $search_equipment_arr);
        }

        if(!empty($product_list)){
            $this->db->where_in('op.product_id', $product_list);
        }
        $this->db->order_by($sort.' '.$order);
        $this->db->group_by('op.product_id');
        if($_GET['is_explore']!=1){
            $this->db->limit($limit,$offset);
        }
        $list = $this->db->get()->result_array();
        foreach($list as $k=>$v){
            $list[$k]['key'] = intval($k+1);
            $list[$k]['product_name'] = $this->get_product_name($v['product_id']);
        }
        if($_GET['is_explore']==1){
            $this->explore_today_product($list);
        }

        $this->db->select('count(DISTINCT(op.product_id) ) as num ');
        $this->db->from('order_product op');
        $this->db->join("order o", 'op.order_name = o.order_name');
        $this->db->where($where);
        if(!empty($search_equipment_arr)){
            $this->db->where_in('o.box_no', $search_equipment_arr);
        }
        if(!empty($product_list)){
            $this->db->where_in('op.product_id', $product_list);
        }
        $total = $this->db->get()->row_array();

        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);

    }

    public function explore_today_product($list){
        $file_name="实时商品销售统计.csv";

        foreach ($list as $k => $v) {
            $data[] = array(
                $v['product_name'],
                $v['sale_num'],
                $v['order_num'],
            );
        }

        @set_time_limit(0);
        $this->load->library("Excel_Export");
        $exceler = new Excel_Export();
        $exceler->setFileName($file_name);
        $excel_title = array("商品名称", "实付商品件数", "实付订单数");
        $exceler->setTitle($excel_title);
        $exceler->setContent($data);
        $exceler->toCode('GBK');
        $exceler->charset('utf-8');
        $exceler->export();
        exit;
    }

}
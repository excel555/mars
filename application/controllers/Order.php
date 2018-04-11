<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Order extends MY_Controller
{
    const ORDEY_STATUS_SUCC = 1;//已支付
    const ORDER_STATUS_DEFAULT = 0;//未支付
    const ORDER_STATUS_CONFIRM = 2;//下单成功支付处理中
    const ORDER_STATUS_REFUND_APPLY = 3;//退款申请
    const ORDER_STATUS_REFUND = 4;//退款完成
    const ORDER_STATUS_REJECT = 5;//驳回申请
    const ORDER_STATUS_CANCEL = -1;//订单取消


    public $workgroup = 'order';

    public $box_type = array(
        'rfid' => 'RFID',
        'scan' => '扫码',
        'vision'=>'视觉'
    );

    public $refer = array(
        'alipay' => '支付宝',
        'wechat' => '微信',
    );
    function __construct() {
        parent::__construct();
        $this->load->model("order_model");
        $this->load->model("showlog_model");
        $this->load->model('user_model');
        $this->load->model('product_class_model');
        $this->load->model('equipment_model');
        $this->load->config('order', true);
        $this->refer = $this->config->item("refer", 'order');
    }

    public function index(){
        $this->load->model("equipment_model");
        $this->_pagedata['start_time'] = $this->input->get('uid')?'':date('Y-m-d 00:00:00');
        $this->_pagedata['end_time']   = $this->input->get('uid')?'':date('Y-m-d 23:59:59');
        $this->_pagedata['refer']  = $this->refer;

        $this->page('order/index.html');
    }

    public function get_product(){
        $kw         = $_GET['matchInfo'];
        $matchCount = $_GET['matchCount']?$_GET['matchCount']:10;
        $where['platform_id'] = $this->platform_id;
        $this->db->from('product');
        $this->db->like('product_name', $kw);
        $this->db->limit($matchCount);
        $this->db->where($where);
        $list = $this->db->get()->result_array();
        $tmp = array();
        foreach($list as $k=>$v){
            $tmp[] = $v['id'].'|'.$v['product_name'];
        }
        $this->showJson($tmp);
    }


    //订单列表 新版

    public function table(){

        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $order_name = $this->input->get('search_order_name');
        $search_name     = $this->input->get('search_name');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $class_id   = $this->input->get('search_class_id');
        if(isset($_GET['day']) && $_GET['day']!=''){
//            $start_time = date('Y-m-d 00:00:00', strtotime($_GET['day']));
//            $end_time   = date('Y-m-d 23:59:59', strtotime($_GET['day']));
        }
        $name        = $this->input->get('search_name');
        $order_status= $this->input->get('search_order_status');
        $order_status= $order_status==-2?-1:$order_status;
        $uid        = $this->input->get('uid');
        $box_param['name']    = $name;
        $box_param['type'] = $this->input->get('search_type');
        $search_box = array();

        $user_id_arr = array();



        $where = 'o.platform_id='.$this->platform_id." ";
        if($search_name){
            $where .= " and o.box_no like '%".$search_name."%'";
        }
        if($uid){
            $where .= ' and o.uid='.$uid;
        }
        if($order_name){
            $where .= " and o.order_name='{$order_name}'";
        }
//        if($start_time){
//            $where .= " and o.order_time>='{$start_time}'";
//        }
//        if($end_time){
//            $where .= " and o.order_time<='{$end_time}'";
//        }
        if(isset($_GET['search_order_status'])){
            $where .= " and o.order_status=".$order_status;
        }
        if(isset($_GET['search_refer']) && !empty($_GET['search_refer'])){
            $where .= " and o.refer='{$_GET['search_refer']}'";
        }

        if($class_id && $class_id!='null'){
            $where .= " and p.class_id in({$class_id})";
        }
        if(!empty($user_id_arr)){
            if(is_numeric($user_id_arr)){
                $where .= " and o.uid ={$user_id_arr}";

            }else{
                $user_id_arr = implode(',', $user_id_arr);
                $where .= " and o.uid in({$user_id_arr})";
            }

        }

        $sql = "select o.* from cb_order o join `cb_order_product` op on o.order_name=op.order_name join cb_product p on op.product_id=p.id where {$where} group by o.id order by o.id desc limit {$offset}, {$limit}";
        $list = $this->db->query($sql)->result_array();
        if($_GET['is_explore'] == 1){
            return $this->explore($list);//共用筛选条件 导出
        }
        foreach ($list as $k => $v) {
            $tmp = $this->equipment_model->get_info_by_equipment_id($v['box_no']);//盒子搜索
            $list[$k]['name']   = $tmp['name'];

            $refer = $this->refer;
            if(isset($refer[$v['refer']])){
                $list[$k]['refer'] = $refer[$v['refer']];
            }
            if($v['order_status'] == 0){
                $list[$k]['order_status'] = '未支付';
            }elseif($v['order_status'] == self::ORDER_STATUS_CONFIRM){
                $list[$k]['order_status'] = '下单成功支付处理中';
            } elseif($v['order_status'] == 1){
                $list[$k]['order_status'] = '已支付';
            }elseif($v['order_status'] == 3){
                $list[$k]['order_status'] = '退款申请';
            }elseif($v['order_status'] == 4){
                $list[$k]['order_status'] = '退款完成';
            }elseif($v['order_status'] == self::ORDER_STATUS_REJECT){
                $list[$k]['order_status'] = '驳回申请';
            }elseif($v['order_status'] == self::ORDER_STATUS_CANCEL){
                $list[$k]['order_status'] = '已取消';
            }
            $product_list = $this->showlog_model->get_order_product($v['order_name']);
            $tmp = '';
            foreach($product_list as $kp=>$vp){
                $tmp .= intval($kp+1).'.'.$vp['product_name'].'('.$vp['qty'].'*'.$vp['price'].')<br/>';
            }
            $list[$k]['product'] = $tmp;
            $list[$k]['name']  = $list[$k]['name'].'['.$list[$k]['box_no'].']';

        }

        $sql = "select o.* from cb_order o where {$where} ";
        $total = $this->db->query($sql)->result_array();

        $result = array(
            'total' => intval(count($total)),
            'rows'  => $list
        );
        echo json_encode($result);
    }
}
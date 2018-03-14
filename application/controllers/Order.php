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
        'fruitday' => '天天果园',
        'gat'    => '关爱通',
        'sodexo' => '索迪斯',
        'sdy'    => '沙丁鱼',
        'cmb'    => '招商银行'
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
//        $this->_pagedata['admin']      = $this->user_model->get_all_admin();
//        $this->_pagedata['uid']  = $this->input->get('uid');
//        $this->_pagedata['info'] = $this->showlog_model->get_user_info($this->_pagedata['uid']);
//        $this->_pagedata['p_class']= $this->product_class_model->get_all_class();
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
                $list[$k]['order_status'] = '<button type="button" class="btn btn-warning" onclick="go_cannel(\''.$v['order_name'].'\')">取消订单</button>';
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


    public function sale_table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $start_time = $this->input->get('next_start_time')?$this->input->get('next_start_time'):date('Y-m-d', strtotime('-1 day'));
        $end_time   = $this->input->get('next_end_time')?$this->input->get('next_end_time'):date('Y-m-d', strtotime('-1 day'));
        if($start_time){
            $where['sale_date >='] = $start_time;
        }
        if($end_time){
            $where['sale_date <='] = $end_time;
        }
        $where['platform_id'] = $this->platform_id;
        $this->db->from('order_sale');
        $this->db->where($where);
        $this->db->limit($limit,$offset);
        $this->db->order_by('id', 'desc');
        $list = $this->db->get()->result_array();
        $this->load->model('equipment_model');
        foreach ($list as $k => $v) {
            $list[$k]['detail'] = '<button type="button" class="btn-success" onclick="show_model(\''.$v['box_no'].'\', \''.$v['sale_date'].'\')" >详情</button>';
            $tmp = $this->equipment_model->get_box_no(array('equipment_id'=>$v['box_no']), 'name');//盒子搜索
            $list[$k]['code']   = $tmp[0];
        }
        $this->db->from('order_sale');
        $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }

    public function explore_result(){
        $this->load->model('equipment_model');
        $start_time = $this->input->get('next_start_time')?$this->input->get('next_start_time'):date('Y-m-d', strtotime('-1 day'));
        $end_time   = $this->input->get('next_end_time')?$this->input->get('next_end_time'):date('Y-m-d', strtotime('-1 day'));
        if($start_time){
            $where['sale_date >='] = $start_time;
        }
        if($end_time){
            $where['sale_date <='] = $end_time;
        }

        $where['platform_id'] = $this->platform_id;
        $this->db->from('order_sale');
        $this->db->where($where);
        $this->db->order_by('id', 'desc');
        $list = $this->db->get()->result_array();

        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '销售日期')
            ->setCellValue('B1', '设备名称')
            ->setCellValue('C1', '负责人')
            ->setCellValue('D1', '订单数')
            ->setCellValue('E1', '商品销售件数')
            ->setCellValue('F1', '库存')
            ->setCellValue('G1', '商品金额')
            ->setCellValue('H1', '实付金额')
            ->setCellValue('I1', '优惠金额')
            ->setCellValue('J1', '客单价')
            ->setCellValue('K1', '笔单价')
            ->setCellValue('L1', '动销率')
            ->setCellValue('M1', '当日新增注册用户数')
            ->setCellValue('N1', '当日购买用户数')//查订单， 除未支付
            ->setCellValue('O1', '累计购买用户数');//查订单，除未支付
        $objPHPExcel->getActiveSheet()->setTitle("{$start_time}-{$end_time}销售统计");
        foreach ($list as $k => $v) {
            if($v['order_num'] == -1){
                $tmp = $this->order_model->get_order_payed($v['box_no'], $v['sale_date']. ' 00:00:00', $v['sale_date']. ' 23:59:59');
                $order_num = intval($tmp['order_num']);
                $user_num  = intval($tmp['user_num']);
            }else{
                $order_num = $v['order_num'];
                $user_num  = $v['user_num'];
            }
            if($v['total_user_num'] == -1){
                $total_user_num = $this->order_model->get_user_num($v['box_no'], $v['sale_date']);
            }else{
                $total_user_num = $v['total_user_num'];
            }
            $key = $k+2;
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$key, $v['sale_date'])
                ->setCellValue('B'.$key, $this->equipment_model->get_eq_name($v['box_no'], 'name'))
                ->setCellValue('C'.$key, $this->equipment_model->get_eq_admin($v['box_no']))
                ->setCellValue('D'.$key, $order_num)
                ->setCellValue('E'.$key, $v['sale_qty'])
                ->setCellValue('F'.$key, $v['stock'])
                ->setCellValue('G'.$key, $v['good_money'])
                ->setCellValue('H'.$key, $v['sale_money'])
                ->setCellValue('I'.$key, $v['discounted_money']>0?'-'.$v['discounted_money']:0)
                ->setCellValue('J'.$key, floatval(bcdiv($v['sale_money'], $user_num, 2)))
                ->setCellValue('K'.$key, floatval(bcdiv($v['sale_money'], $order_num, 2)))//笔单价
                ->setCellValue('L'.$key, (bcdiv($v['sale_qty'], ($v['sale_qty']+$v['stock']), 2)*100).'%'  )//动销率
                ->setCellValue('M'.$key, $this->user_model->get_reg_by_eq($v['box_no'], $v['sale_date']. ' 00:00:00', $v['sale_date']. ' 23:59:59'))//当日新增注册用户数
                ->setCellValue('N'.$key, $user_num)//当日购买用户数
                ->setCellValue('O'.$key, $total_user_num);//累计购买用户数
        }

        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader("{$start_time}-{$end_time}销售统计");
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }


    public function one_detail($box_no, $sale_date){
        if($sale_date){
            $where['o.order_time >='] = $sale_date.' 00:00:00';
            $where['o.order_time <'] = $sale_date.' 23:59:59';
        }
        if($box_no){
            $where['o.box_no'] = $box_no;
        }
        $where['op.pay_status'] = 1;
        $this->db->from('order o');
        $this->db->join('order_pay op', 'o.order_name=op.order_name');
        $this->db->where($where);
        $data['list'] = $this->db->get()->result_array();
        $data['box_no'] = $box_no;
        $data['sale_date'] = $sale_date;

        foreach ($data as $key => $value) {
            $this->Smarty->assign($key,$value);
        }
        $html = $this->Smarty->fetch('order/sale_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

    public function update_order_api($order_name){
        $params['order_name'] = htmlspecialchars($order_name);
        $rs = $this->get_api_content( $params, '/api/order/update_pay_order?order_name='.$order_name, 0);
        $rs = json_decode($rs, true);
        $this->showJson($rs);
    }


    public function order_cannel($order_name){
        $rs = $this->db->update('order', array('order_status'=>-1), array('order_name'=>$order_name));
        if($rs){
            $this->showJson(array('code'=>'200'));
        }
        $this->showJson(array('code'=>'500'));
    }


    /**
     * 新建导出任务
     * */
    public function add_export(){

    }

}
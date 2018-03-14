<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Refund extends MY_Controller
{
    public $workgroup = 'order';

    const ORDER_STATUS_REJECT = 5;//订单表驳回申请


    public $reason = array(
        0 => '申请原因',
        1 => '订单结算错误',
        2 => '商品质量问题'
    );

    public $status = array(
        0 => '退款状态',
        1 => '待处理',
        2 => '驳回申请',
        3 => '退款中',
        4 => '退款成功',
        5 => '退款失败'
    );
    public $case_type = array(
        0 => 'case归类',
        1 => '错扣问题',
        2 => '质量问题',
        3 => '活动问题',
        4 => '标签问题',
        5 => '客户问题',
        6 => '内部测试'
    );

    public $box_type = array(
        'rfid' => 'RFID',
        'scan' => '扫码',
        'vision'=>'视觉'
    );
    protected $refer;
    function __construct()
    {
        parent::__construct();
        $this->load->model("equipment_model");
        $this->load->model("user_acount_model");
        $this->load->model("order_model");
        $this->load->model("order_refund_model");
        $this->load->config('order', true);
        $this->refer = $this->config->item("refer", 'order');
    }

    public function index(){
        $this->_pagedata['store_list']   = $this->equipment_model->get_store_list();
        $this->_pagedata['is_need_name'] = 1;
        $this->_pagedata['reason'] = $this->reason;
        $this->_pagedata['status'] = $this->status;
        $this->_pagedata['case_type'] = $this->case_type;
        $this->_pagedata['refer'] = $this->refer;
        $this->page('refund/index.html');
    }

    public function detail($id){
        $this->_pagedata['case_type_list'] = $this->case_type;
        $this->_pagedata['reason_title'] = $this->reason;
        $this->_pagedata['status'] = $this->status;
        $this->db->from('order_refund');
        $this->db->where('id', $id);
        $row = $this->db->get()->row_array();
        $row['modou_money'] = floatval($row['modou']/100);
        foreach($row as $k=>$v){
            $this->_pagedata[$k] = $v;
        }
        $rs = $this->db->from('user')->where('id', $row['uid'])->get()->row_array();
        $this->_pagedata['mobile'] = $rs['mobile'];

        $product = $this->db->from('order_product')->where('order_name', $row['order_name'])->get()->result_array();
        $order   = $this->db->from('order')->where('order_name', $row['order_name'])->get()->row_array();
        $order_pay   = $this->db->from('order_pay')->where('order_name', $row['order_name'])->order_by('id desc')->get()->row_array();
        if($order_pay['pay_type'] == 8){//招商银行退款
            $order['money'] = $order_pay['pay_money'];//涉及到招商银行优惠
        }
        $this->_pagedata['order_product'] = $this->get_proportion($product, bcsub($order['good_money'], $order['discounted_money'], 2), $order['modou'], $order['yue'], $order['money']);
        $this->page('refund/detail.html');
    }

    public function table(){
        $limit      = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset     = $this->input->get('offset')?$this->input->get('offset'):0;
        $reason     = $this->input->get('search_reason');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $mobile     = $this->input->get('search_mobile');
        $refer      = $this->input->get('search_refer');
        $order_name = $this->input->get('search_order_name');
        $eq_name    = $this->input->get('search_eq_name');
        $product_name = $this->input->get('search_product_name');
        $case_type  = $this->input->get('search_case_type');
        $product_name = explode('|', $product_name);
        $product_id   = intval($product_name[0]);

        $refund_status     = $this->input->get('search_status');

        $box_param['equipment_id'] = $this->input->get('search_equipment_id');
        $box_param['name']         = $eq_name;
        $box_param['replenish_location'] = $this->input->get('search_replenish_location');
        $box_param['type'] = $this->input->get('search_type');
        $search_box = array();
        if($box_param['equipment_id'] || $box_param['name'] || $box_param['replenish_location'] || $box_param['type']){
            $search_box = $this->equipment_model->get_box_no($box_param, 'equipment_id');//盒子搜索
            if(empty($search_box)){
                $search_box = array(-1);
            }
        }

        $this->load->model("showlog_model");
        $where['r.platform_id'] = $this->platform_id;
        $user_id_arr = array();
        if($mobile){
            $this->load->model('user_model');
            $user_id_arr = $this->user_model->get_user_id_by_mobile($mobile);
            if(is_numeric($user_id_arr)){
                $where['r.uid'] = $user_id_arr;
            }
        }
        if($reason){
            $where['r.reason'] = $reason;
        }
        if($start_time){
            $where['o.order_time >='] = $start_time.' 00:00:00';
        }
        if($end_time){
            $where['o.order_time <='] = $end_time.' 23:59:59';
        }
        if($case_type){
            $where['r.case_type'] = $case_type;
        }

        if($refund_status){
            $where['r.refund_status'] = $refund_status;
        }
        if($order_name){
            $where['r.order_name'] = $order_name;
        }
        if($refer){
            $where['o.refer'] = $refer;
        }
        $this->db->select('r.*, o.refer, o.order_time');
        $this->db->from('order_refund r');
        $this->db->join('order o', 'r.order_name=o.order_name', 'left');
        $this->db->where($where);
        if($product_id>0){
            $this->db->join('order_product op', 'r.order_name=op.order_name', 'left');
            $this->db->where(array('op.product_id'=>$product_id));
        }
        if(!empty($search_box)){
            $this->db->where_in('r.box_no', $search_box);
        }
        if(is_array($user_id_arr) && !empty($user_id_arr)){
            $this->db->where_in('r.uid', $user_id_arr);
        }

        $this->db->order_by('r.id desc');
        $this->db->limit($limit,$offset);
        $list = $this->db->get()->result_array();
        if($_GET['is_explore'] == 1){
            return $this->explore($list);
        }
        $status = $this->status;
        $uid_array = array();
        foreach ($list as $k => $v) {
            if($v['reason'] == 1){
                $list[$k]['reason'] = '订单结算错误';
            }elseif($v['reason'] == 2){
                $list[$k]['reason'] = '商品质量问题';
            }
            $refer = $this->refer;
            $list[$k]['refer'] = isset($refer[$v['refer']])?$refer[$v['refer']]:$v['refer'];
            $list[$k]['mobile'] = $this->showlog_model->get_user_info($v['uid'], 'mobile', 'mobile');
            $list[$k]['refund_status'] = $status[$v['refund_status']];
            $list[$k]['operation'] = '<a class="label label-success" target="_blank" href="/refund/detail/'.$v['id'].'" >查看详情</a>';
            $tmp = $this->equipment_model->get_box_no(array('equipment_id'=>$v['box_no']), 'name');//盒子搜索
            $list[$k]['name']   = $tmp[0];
            $uid_array[$v['uid']] = $v['uid'];
        }

        $refund_order = $this->order_refund_model->get_user_refund_order($uid_array);
        foreach($list as $k=>$v){
            $list[$k]['refund_order'] = $refund_order[$v['uid']];
        }
        $this->db->select('count(*) as num, count(DISTINCT(r.uid)) as user_total, SUM(r.really_money) as really_money, SUM(r.refund_money) as refund_money');
        $this->db->from('order_refund r');
        $this->db->join('order o', 'r.order_name=o.order_name', 'left');
        $this->db->where($where);
        if($product_id>0){
            $this->db->join('order_product op', 'r.order_name=op.order_name', 'left');
            $this->db->where(array('op.product_id'=>$product_id));
        }
        if(!empty($search_box)){
            $this->db->where_in('r.box_no', $search_box);
        }
        if(is_array($user_id_arr) && !empty($user_id_arr)){
            $this->db->where_in('r.uid', $user_id_arr);
        }
        $total = $this->db->get()->row_array();

        //查询待处理的
        $this->db->select('count(r.id) as num');
        $this->db->from('order_refund r');
        $this->db->join('order o', 'r.order_name=o.order_name', 'left');
        $where['r.refund_status'] = 1;
        $this->db->where($where);
        if($product_id>0){
            $this->db->join('order_product op', 'r.order_name=op.order_name', 'left');
            $this->db->where(array('op.product_id'=>$product_id));
        }
        if(!empty($search_box)){
            $this->db->where_in('r.box_no', $search_box);
        }
        $last_total = $this->db->get()->row_array();
        //统计单品退款率
        $product_refund = array('qty'=>0,'refund_num'=>0);
        if($product_id){
            $where_p = array('op.product_id'=>$product_id, 'o.platform_id'=>$this->platform_id);
            if($start_time){
                $where_p['o.order_time >='] = $start_time.' 00:00:00';
            }
            if($end_time){
                $where_p['o.order_time <='] = $end_time.' 23:59:59';
            }
            $this->db->select('sum(op.qty) as qty, sum(op.refund_num) as refund_num');
            $this->db->from('order o');
            $this->db->join('order_product op', 'o.order_name=op.order_name', 'left');

            $this->db->where($where_p);

            if(!empty($search_box)){
                $this->db->where_in('o.box_no', $search_box);
            }
            $product_refund = $this->db->get()->row_array();
        }


        $result = array(
            'total' => intval($total['num']),
            'user_total' => intval($total['user_total']),
            'really_money' => floatval($total['really_money']),
            'refund_money' => floatval($total['refund_money']),
            'last_total'  => intval($last_total['num']),
            'rows' => $list,
            'product_avg' => $product_id?(bcdiv($product_refund['refund_num'], $product_refund['qty'], 4)*100):-1
        );
        echo json_encode($result);
    }

    //同意退款
    public function agree_refund($id){
        if($this->input->is_ajax_request()){
            $product_id = $this->input->post('product_id');
            if(empty($product_id)){
                $this->showJson(array('status'=>'error', 'msg' =>'没有退款商品'));
            }
            $param['really_money'] = $this->input->post('really_money');
            $param['admin_apply']  = $this->input->post('admin_apply');
            $param['admin_name']   = $this->_pagedata['adminname'];
            $param['admin_time']   = date('Y-m-d H:i:s');
            $param['modou']        = $this->input->post('modou')?$this->input->post('modou'):0;
            $param['modou']        = $param['modou']*100;
            $param['yue']          = $this->input->post('yue')?$this->input->post('yue'):0;
            $param['case_type']    = $this->input->post('case_type')?$this->input->post('case_type'):0;

            $this->db->from('order_refund');
            $this->db->where('id', $id);
            $rs = $this->db->get()->row_array();
            if($rs['refund_status'] != 1 && $rs['refund_status'] != 5){
                $this->showJson(array('status'=>'error', 'msg' =>'重复操作'));
            }

            $order   = $this->db->from('order')->where('order_name', $rs['order_name'])->get()->row_array();
            if($this->input->post('modou')>$order['modou']){
                $this->showJson(array('status'=>'error', 'msg' =>'魔豆金额有误'));
            }
            if($this->input->post('yue')>$order['yue']){
                $this->showJson(array('status'=>'error', 'msg' =>'余额金额有误'));
            }
            if($this->input->post('really_money')>$order['money']){
                $this->showJson(array('status'=>'error', 'msg' =>'实付金额有误'));
            }

            foreach($product_id as $k=>$v){
                $this->db->update('order_product', array('refund_num'=>$v), array('order_name'=>$rs['order_name'], 'product_id'=>$k));
            }
            $api_result = $this->refund_api($rs['order_name'], $param['really_money'], $this->adminid);
            $api_result = json_decode($api_result, true);
            if( $api_result['status']===false ){ //自检出现异常
                $param['refund_status'] = 5;
                $result = array('status'=>'error', 'msg' => $api_result['message'], 'param'=>$api_result);
            }elseif($api_result['return_code'] == 'SUCCESS' && $api_result['result_code']== 'SUCCESS' && isset($api_result['refund_id'])){
                //微信退款
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }
            elseif($api_result['pay_type'] == 6 && $api_result['code']==0 ){//关爱通退款成功
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }elseif($api_result['pay_type'] == 6 && $api_result['code']!=0 ){//关爱通退款失败
                $param['refund_status'] = 5;
                $result = array('status'=>'error', 'msg' => $api_result['msg'], 'param'=>$api_result);
            }elseif($api_result['is_success'] =='T' && $api_result['response']['alipay']['result_code']=='SUCCESS'){//支付宝出现异常
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }elseif($api_result['alipay_trade_refund_response']['code'] == 10000 || $api_result['code'] == 10000 ){
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }elseif($api_result['code']==200 && $api_result['msg'] == '余额退款成功'){//余额退款
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }elseif($api_result['refund_status'] == 'succ' && $param['really_money']==0){//实付是0 只退余额和魔豆
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }elseif($api_result['pay_type'] == 8 && $api_result['code']==200){//招商银行退款
                $param['refund_status'] = 4;
                $result = array('status'=>'success', 'msg' => '退款成功', 'param'=>$api_result);
            }else{
                $param['refund_status'] = 5;
                if($api_result['response']['alipay']['detail_error_des']){
                    $msg = $api_result['response']['alipay']['detail_error_des'];
                } else if($api_result['err_code_des']){
                    $msg = $api_result['err_code_des'];
                }else{
                    $msg = "退款失败";
                }
                $result = array('status'=>'error', 'msg' => $msg, 'param' => $api_result);
            }
            if($param['refund_status'] == 4){//退款成功，扣除魔力 魔豆
                $this->user_acount_model->refund_succ_order($rs['uid'], $param['really_money'], $rs['order_name'], $param['modou'], $param['yue'],$order['order_time']);
            }
            if($this->db->update('order_refund', $param, array('id'=>$id) )){
               $this->showJson($result);
           }
        }
        $this->showJson(array('status'=>'error','msg'=>'系统异常'));
    }

    /**/

    //驳回退款
    public function reject_refund($id){
        if($this->input->is_ajax_request()){
            $param['admin_apply']  = $this->input->post('admin_apply');
            $param['refund_status'] = 2;
            $param['admin_name'] = $this->_pagedata['adminname'];
            $param['admin_time'] = date('Y-m-d H:i:s');
            $param['case_type']    = $this->input->post('case_type')?$this->input->post('case_type'):0;
            $this->db->trans_begin();
            $this->db->update('order_refund', $param, array('id'=>$id) );
            $order_name = $this->input->post('order_name');
            $this->db->update('order', array('order_status'=>self::ORDER_STATUS_REJECT), array('order_name'=>$order_name) );
            if (!$order_name || $this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                $this->showJson(array('status'=>'error', 'msg'=>'操作失败'));
            } else {
                $this->db->trans_commit();
                $this->get_api_content( array('order_name' => $order_name), '/api/order/refund_against');
                $this->showJson(array('status'=>'success'));
            }
        }
        $this->showJson(array('status'=>'error'));
    }


    public function refund_api($order_name, $really_money, $operation_id ){
        $params['order_name'] = $order_name;
        $params['really_money'] = $really_money;
        $params['operation_id'] = $operation_id;

        return $this->get_api_content( $params, '/api/order/refund_approval');
    }

    public function explore($list){
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '手机号')
            ->setCellValue('B1', '订单号')
            ->setCellValue('C1', '退款流水')//trade_no
            ->setCellValue('D1', '售货机')
            ->setCellValue('E1', '创建时间')
            ->setCellValue('F1', '申请原因')
            ->setCellValue('G1', '原因详情')
            ->setCellValue('H1', '实际退款金额')
            ->setCellValue('I1', '申请退款金额')
            ->setCellValue('J1', '退货状态')
            ->setCellValue('K1', '操作人')
            ->setCellValue('L1', '商品id')
            ->setCellValue('M1', '商品名称')
            ->setCellValue('N1', '价格')
            ->setCellValue('O1', '退货数量')
            ->setCellValue('P1', '审核意见')
            ->setCellValue('Q1', '返还余额')
            ->setCellValue('R1', '返还魔豆')
            ->setCellValue('S1', 'case归类')
            ->setCellValue('T1', '用户id')
            ->setCellValue('U1', '用户昵称')
            ->setCellValue('V1', '设备id')
            ->setCellValue('W1', '设备类型')
        ;
        $objPHPExcel->getActiveSheet()->setTitle('退款列表');
        $equipment_list = $this->equipment_model->get_all_box_admin();//所有开启的盒子
        $order_name = array();
        foreach($list as $k=>$v){
            $order_name[] = $v['order_name'];
            $uid_array[]  = $v['uid'];
        }
        $product = $this->order_model->get_order_refund_product($order_name);//get_order_product 旧版
        $case_type = $this->case_type;
        $this->load->model('user_model');
        $key = 2;
        $box_type_list = $this->box_type;
        $user_info = $this->user_model->get_user_info_by_ids(array_unique($uid_array), 'id, user_name, mobile');
        foreach($list as $k=>$v){
            $box_type = $box_type_list[$equipment_list[$v['box_no']]['type']];
            $reason = '';
            if($v['reason']==1){
                $reason = '订单结算错误 ';
            }elseif($v['reason']==2){
                $reason = '商品质量问题';
            }
            $refund_status = '';
            if($v['refund_status']==1){
                $refund_status = '待处理';
            }elseif($v['refund_status']==2){
                $refund_status = '拒绝';
            }elseif($v['refund_status']==3){
                $refund_status = '退款中';
            }elseif($v['refund_status']==4){
                $refund_status = '退款成功';
            }elseif($v['refund_status']==5){
                $refund_status = '退款失败';
            }
            if(isset($product[$v['order_name']])){
                foreach($product[$v['order_name']] as $pk=>$pv){
                    if(empty($pv)){
                        continue;
                    }
                    $objPHPExcel->getActiveSheet()
                        ->setCellValue('A'.$key, $user_info[$v['uid']]['mobile'])
                        ->setCellValue('B'.$key, $v['order_name'])
                        ->setCellValue('C'.$key, $v['trade_no'])
                        ->setCellValue('D'.$key, $equipment_list[$v['box_no']]['name'])
                        ->setCellValue('E'.$key, $v['create_time'])
                        ->setCellValue('F'.$key, $reason)
                        ->setCellValue('G'.$key, $v['reason_detail'])
                        ->setCellValue('H'.$key, $v['really_money'])
                        ->setCellValue('I'.$key, $v['refund_money'])
                        ->setCellValue('J'.$key, $refund_status)
                        ->setCellValue('K'.$key, $v['admin_name'])
                        ->setCellValue('L'.$key, $pv['product_id'])
                        ->setCellValue('M'.$key, $pv['product_name'])
                        ->setCellValue('N'.$key, $pv['price'])
                        ->setCellValue('O'.$key, $pv['refund_num'])
                        ->setCellValue('P'.$key, $v['admin_apply'])
                        ->setCellValue('Q'.$key, $v['yue'])
                        ->setCellValue('R'.$key, bcdiv($v['modou'], 100, 2))
                        ->setCellValue('S'.$key, $case_type[$v['case_type']])
                        ->setCellValue('T'.$key, $user_info[$v['uid']]['id'])
                        ->setCellValue('U'.$key, $user_info[$v['uid']]['user_name'])
                        ->setCellValue('V'.$key, $v['box_no'])
                        ->setCellValue('W'.$key, $box_type)
                    ;
                    $key++;
                }

            }
        }
        @set_time_limit(0);

        // Redirect output to a client’s web browser (Excel2007)
        $objPHPExcel->initHeader('退款列表');
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');
        exit;
    }
    public function download_html($num){
        $limit = 500;
        $page = ceil($num/$limit);
        $result = array();
        for($i=1;$i<=$page; $i++){
            $start = ($i-1)*$limit;
            $next = $i*$limit;
            $next = $next>$num?$num:$next;
            $result[$i]['text'] = '导出第'.$start.'-'.$next.'条退款申请';
            $result[$i]['url']  = '/refund/table?is_explore=1&page='.$i.'&limit='.$limit.'&offset='.$start;
        }
        $this->Smarty->assign('list',$result);
        $html = $this->Smarty->fetch('order/download_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }
}
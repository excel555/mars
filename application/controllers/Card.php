<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Card extends MY_Controller
{
    public $workgroup = 'card';
    public $redis;
    public $img_http  = 'http://fdaycdn.fruitday.com/';
    public $week_str = array(
        '0'=>array('name'=>'日'),
        '1'=>array('name'=>'一'),
        '2'=>array('name'=>'二'),
        '3'=>array('name'=>'三'),
        '4'=>array('name'=>'四'),
        '5'=>array('name'=>'五'),
        '6'=>array('name'=>'六')  
    );

    function __construct() {
        parent::__construct();
        $this->load->model("card_model");
        $this->load->model("qrcard_model");
        $this->load->library('phpredis');
        $this->load->helper('public');
        $this->load->library('curl');
        $this->load->model('user_model');
        $this->redis = $this->phpredis->getConn();
    }

    public function index(){
        $this->_pagedata['uid'] = $this->input->get('uid');
        $this->page('card/card_list.html');
    }

    public function card_table(){
        $limit         = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset        = $this->input->get('offset')?$this->input->get('offset'):0;
        $card_number   = $this->input->get('search_card_number');
        $is_used       = $this->input->get('search_is_used');
        $uid           = $this->input->get('search_uid');
        $tag           = $this->input->get('search_tag');
        $card_active_id= $this->input->get('search_card_active_id');
        $card_source   = $this->input->get('search_card_source');
        $where = array('platform_id'=>$this->platform_id);
        $user_id_arr = $this->user_model->get_user_id_by_mobile($uid);
        if(!empty($user_id_arr)){
            if(is_numeric($user_id_arr)){
                $where['uid'] = $user_id_arr;
            }else{
                $user_id_arr = implode(',', $user_id_arr);
                $where['uid'] = " uid in({$user_id_arr}) ";
            }
        }
        if(isset($_GET['search_is_used'])){
            $where['is_used'] = $is_used;
        }
        if($tag){
            $where['tag'] = $tag;
        }
        if($card_active_id){
            $where['card_active_id'] = $card_active_id;
        }
        if($card_source){
            $where['card_source'] = $card_source;
        }

        $this->db->from('card');
        $this->db->where($where);
        if($card_number){
            $this->db->like('card_number',$card_number);
        }
        $this->db->limit($limit,$offset);
        $this->db->order_by('id desc');
        $list = $this->db->get()->result_array();
        foreach($list as $k=>$each){
            if ($each['is_used'] == 1){
                $list[$k]['is_used'] = '是';
            } else {
                $list[$k]['is_used'] = '否';
            }
            $list[$k]['source'] = trim($each['source'],',');
            if($each['card_source'] == 1){
                $list[$k]['card_source'] = '后台发券';
            }elseif($each['card_source'] == 2){
                $list[$k]['card_source'] = '开门领券';
            }elseif($each['card_source'] == 3){
                $list[$k]['card_source'] = '活动领券';
            }elseif($each['card_source'] == 4){
                $list[$k]['card_source'] = '首页领券';
            }elseif($each['card_source'] == 5){
                $list[$k]['card_source'] = '结算页领券';
            }
        }

        $this->db->from('card');
        $this->db->where($where);
        if($card_number){
            $this->db->like('card_number',$card_number);
        }
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        $this->showJson($result);
    }


    //配送工单
    public function card_model(){
        $this->page('card/card_model_list.html');
    }

    function card_add(){
        $this->_pagedata['today'] = date('Y-m-d');
        $this->page('card/card_add.html');
    }

    public function card_save(){
        $posts = $this->input->post();
        $now = date('Y-m-d H:i:s');

//        $posts['submit']=1;
//        $posts['tag']='Le5kk3';
//        $posts['uids']='1,2';

        if($posts['submit']){
            $card_model_info = $this->card_model->get_card_model_info(array(
                'tag'=>trim($posts['tag']),
                'begin_time <='=>$now,
                'end_time >='=>$now
            ));

            if(empty($card_model_info)){
                $this->validform_fail(array('msg'=>'您输入的优惠券模板tag不存在或者已过期'));
            }
            if(empty($posts['uids'])){
                $this->validform_fail(array('msg'=>'发放优惠券的uid不能为空'));
            }

            $uids = explode(',',$posts['uids']);
            if(!empty($uids)){
                $card_data = $this->card_model->init_card_by_card_model($card_model_info,$now);
                $arr_card = array();
                foreach ($uids as $v){
                    $card_data['card_number'] = $this->card_model->rand_card_number($card_model_info['card_pre']);
                    $card_data['uid'] = $v;
                    $arr_card[] = $card_data;
                }
                $rs = $this->card_model->insertBatchCard($arr_card);
            }

            if($rs){
                $this->validform_success(array('msg'=>'发放成功','to_url'=>'/card/card'));
            }else{
                $this->validform_fail(array('msg'=>'保存失败请稍候重试'));
            }
        }
    }

    public function card_model_table(){
        $gets = $this->input->get();
        $limit      = $gets['limit']?$gets['limit']:10;
        $offset     = $gets['offset']?$gets['offset']:0;
        $search_card_remarks     = $gets['search_card_remarks'];
        $search_tag  = $gets['search_tag'];

        if(isset($search_card_remarks)){
            $where['card_remarks like'] = '%'.$search_card_remarks.'%';
        }
        if(isset($search_tag)){
            $where['tag'] =  $search_tag;
        }

        $result = $this->card_model->get_card_model_list($where,$limit,$offset);
        echo json_encode($result);
    }

    private function card_model_data_fomat($posts){
        $class_id = $posts['class_id'];
        $str_class = implode(',',$class_id);
        $card_model_data = array(
            'card_remarks'=>$posts['card_remarks'],
            'card_pre'=>$posts['card_pre'],
            'card_money'=>$posts['card_money'],
            'begin_time'=>$posts['begin_time'],
            'end_time'=>$posts['end_time'],
            'order_limit_type'=>$posts['order_limit_type'],
            'order_limit_value'=>$posts['order_limit_value'],
            'product_limit_type'=>$posts['product_limit_type'],
            'product_limit_value'=>$posts['product_limit_type']==2?$str_class:$posts['product_limit_value'],
            'use_with_sales'=>$posts['use_with_sales'],
            'source_limit'=>implode(',',$posts['source_limit']),
            'time_limit_type'=>$posts['time_limit_type'],
            'card_begin_date'=>$posts['card_begin_date'],
            'card_end_date'=>$posts['card_end_date'],
            'card_last'=>$posts['card_last'],
            'tag'=>tag_code(microtime().mt_rand(0,9)),
            'platform_id'=>$this->platform_id
        );
        return $card_model_data;
    }

    public function card_model_add(){
        $this->_pagedata['today'] = date('Y-m-d');


        $this->load->model('product_class_model');
        $class_list  = $this->product_class_model->getList(array('parent_id >'=>1));
//        foreach($class_list as $k=>$v){
//            $class_list[$k]['checked'] = 1;
//        }
        $this->_pagedata['class_list']  = $class_list;

        $this->page('card/card_model_add.html');
    }

    public function card_model_edit($id){
        $this->_pagedata['today'] = date('Y-m-d');
        $rs = $this->card_model->get_card_model_info(array('id'=>$id));
        $this->_pagedata['detail'] = $rs;

        $arr_source_limit = explode(',',$rs['source_limit']);
        $arr = array('alipay','fruitday','wechat');

        foreach ($arr as $v){
            if(in_array($v,$arr_source_limit)){
                $checked_source[$v] = 1;
            }
        }

        $this->_pagedata['checked_source'] = $checked_source;


        $this->load->model('product_class_model');
        $class_list  = $this->product_class_model->getList(array('parent_id >'=>1));
        if($rs['product_limit_type']==2){
            $class_id_arr = explode(',', trim($rs['product_limit_value'], ','));
            foreach($class_list as $k=>$v){
                $class_list[$k]['checked'] = in_array($v['id'], $class_id_arr)?1:0;
            }
        }
        $this->_pagedata['class_list']  = $class_list;

//        var_dump($checked_source);exit;

        $this->page('card/card_model_add.html');
    }

    public function card_model_save(){
        $posts = $this->input->post();

        if($posts['submit']){
            $card_model_data = $this->card_model_data_fomat($posts);

            if($posts['id']){
                $where = array('id'=>$posts['id']);
                unset($card_model_data['tag']);
                $rs = $this->card_model->update_card_model($card_model_data,$where);
            }else{
                $rs = $this->card_model->add_card_model($card_model_data);
            }
            if($rs){
                $this->validform_success(array('msg'=>'保存成功','to_url'=>'/card/card_model'));
            }else{
                $this->validform_fail(array('msg'=>'保存失败请稍候重试'));
            }
            exit;
        }
    }
    
    public function qrcard(){
        $this->page('card/qrcard_list.html');
    }
    
    public function qrcard_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $title   = $this->input->get('search_title');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $active_status = $this->input->get('search_active_status');
        $active_time = $this->input->get('search_active_time');
        $get_type = $this->input->get('search_get_type');
        
        $where = "platform_id={$this->platform_id} and active_status>0 ";
        if($title){
            $where .= " and title like '%".$title."%'";
        }
        
        if($start_time){
            $where .= " and start_time>='{$start_time}' ";
        }
        if($end_time){
            $where .= " and end_time<='{$end_time}' ";
        }
        if($active_time==1){
            $where .= " and start_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==2){
            $where .= " and start_time<='".date('Y-m-d H:i:s')."' ";
            $where .= " and end_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==3){
            $where .= " and end_time<='".date('Y-m-d H:i:s')."' ";
        }
        if ($get_type){
            $where .= " and get_type={$get_type} ";
        }
        if($active_status>0){
            $where .= " and active_status={$active_status} ";
        }
        $sql = "select * from cb_qrcard where {$where} order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
        
        foreach ($list as $k => &$v) {
            if($v['start_time'] > date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >未开始</button>';
            }elseif($v['end_time'] < date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >已结束</button>';
            }elseif($v['start_time'] <= date('Y-m-d H:i:s') && $v['end_time'] >= date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >时间内</button>';
            }
            if($v['active_status']==1){//启用
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已启用</button>';
            }elseif($v['active_status']==2){
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已暂停</button>';
            }
            $v['card_model_config'] = json_decode($v['card_model_config']);
            $v['count_config'] = count($v['card_model_config']);
            $v['id'] = '<a  target="_blank" href="/card/qrcard_edit/'.$v['id'].'">'.$v['id'].'</a>';
            $v['get_type_text'] = $v['get_type'] == 1 ? '每日一次' : '共计一次';
        }
        
        $sql = "select count(id) as num from cb_qrcard where {$where} ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }
    
    public function qrcard_add(){
        $this->page('card/qrcard_add.html');
    }
    
    public function qrcard_edit($id){
        $this->db->from('qrcard');
        $this->db->where(array('id'=> $id));
        $detail = $this->db->get()->row_array();
        $this->_pagedata['detail'] = $detail;
        $this->_pagedata['detail']['card_model_config'] = json_decode($this->_pagedata['detail']['card_model_config'], true);
        $this->_pagedata['img_http']   = $this->img_http;
        $this->page('card/qrcard_add.html');
    }
    
    public function qrcard_save(){
        $id = $this->input->post('id');
        $param['title']         = $this->input->post('title');
        $param['start_time']    = $this->input->post('start_time')?$this->input->post('start_time'):date('Y-m-d 00:00:00');
        $param['end_time']      = $this->input->post('end_time')?$this->input->post('end_time'):date('Y-m-d 00:00:00', strtotime('1 day'));
        $param['admin_name']    = $this->_pagedata['adminname'];
        $param['banner_img']    = $this->input->post('banner_img');
        $param['rule_text']     = $this->input->post('rule_text');
        $param['share_img']     = $this->input->post('share_img');
        $param['share_title']   = $this->input->post('share_title');
        $param['share_content'] = $this->input->post('share_content');
        $param['platform_id']   = $this->platform_id;
        $param['get_type']      = $this->input->post('get_type');
    
        $config = array();
        $percent = $this->input->post('percent');
        $tag = $this->input->post('tag');
        if(!empty($tag)){
            foreach($tag as $k=>$v){
                if ($v != ''){
                    //todo 验证tag是否存在
                    $this->db->from('card_model');
                    $this->db->where(array('tag'=>$v,'is_delete'=>0));
                    $card_model = $this->db->get()->row_array();
                    if (!$card_model){
                        $this->showJson(array('status' => 'error', 'msg'=>$v.'该优惠券码不存在！'));
                    }
                    $config[$k]['tag']  = $v;
                    $config[$k]['percent'] = $percent[$k];
                }
            }
        }
    
        $param['card_model_config']   = json_encode($config);
        $this->db->trans_begin();
        if($id>0){
            $this->db->update('qrcard', $param, array('id'=>$id));
        }else{
            //新增活动  默认启用
            $param['active_status'] = 1;
            $param['created_time'] = time();
            $this->db->insert('qrcard', $param);
            $id = $this->db->insert_id();
            //生成二维码
            $url = 'http://cityboxapi.fruitday.com/public/coupon.html?id='.$id;
//            $url = 'http://980.so/api.php?url=' . urlencode($url);
            //modify by linxingyu
            $rs_short = sina_short_url($url);
            if($rs_short['urls'][0]['result'] == true){
                $new_url = $rs_short['urls'][0]['url_short'];
            }else{
                $this->showJson(array('status' => 'error', 'msg'=>'生成二维码错误'));
            }
            $qr_url = "http://qr.liantu.com/api.php?text=".$new_url."";
            //$img = $this->config->item("base_url") . 'uploads/' .general_qr_code($new_url);
            $this->db->update('qrcard', array('qrcode'=>$qr_url), array('id'=>$id));
        }
    
        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            $this->showJson(array('status' => 'error', 'msg'=>'系统错误'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status' => 'success'));
        }
    }
    
    //给活动设置状态
    public function set_qr_status(){
        $id  = $this->input->post('id');
        if(!$id){
            $this->showJson(array('status'=>'error', 'msg'=>'请选择编辑项'));
        }
        $val = $this->input->post('val');
        if(!in_array($val, array(0,1,2))){
            $this->showJson(array('status'=>'error', 'msg'=>'活动状态不符合'));
        }
        if($this->db->update('qrcard', array('active_status'=>$val), array('id'=>$id))){
            $this->showJson(array('status'=>'success', 'msg'=>'成功'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'网络异常，请稍后尝试'));
    }
    
    public function indexcard(){
        $this->page('card/indexcard_list.html');
    }
    
    public function indexcard_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $title   = $this->input->get('search_title');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $active_status = $this->input->get('search_active_status');
        $active_time = $this->input->get('search_active_time');
        $get_type = $this->input->get('search_get_type');
    
        $where = "platform_id={$this->platform_id} and active_status>0 ";
        if($title){
            $where .= " and title like '%".$title."%'";
        }
    
        if($start_time){
            $where .= " and start_time>='{$start_time}' ";
        }
        if($end_time){
            $where .= " and end_time<='{$end_time}' ";
        }
        if($active_time==1){
            $where .= " and start_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==2){
            $where .= " and start_time<='".date('Y-m-d H:i:s')."' ";
            $where .= " and end_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==3){
            $where .= " and end_time<='".date('Y-m-d H:i:s')."' ";
        }
        if ($get_type){
            $where .= " and get_type={$get_type} ";
        }
        if($active_status>0){
            $where .= " and active_status={$active_status} ";
        }
        $sql = "select * from cb_indexcard where {$where} order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
    
        foreach ($list as $k => &$v) {
            if($v['start_time'] > date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >未开始</button>';
            }elseif($v['end_time'] < date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >已结束</button>';
            }elseif($v['start_time'] <= date('Y-m-d H:i:s') && $v['end_time'] >= date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >时间内</button>';
            }
            if($v['active_status']==1){//启用
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已启用</button>';
            }elseif($v['active_status']==2){
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已暂停</button>';
            }
            $v['id'] = '<a  target="_blank" href="/card/indexcard_edit/'.$v['id'].'">'.$v['id'].'</a>';
            $v['get_type_text'] = $v['get_type'] == 1 ? '每日一次' : '共计一次';
        }
    
        $sql = "select count(id) as num from cb_indexcard where {$where} ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }
    
    public function indexcard_add(){
        $code = array();
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $this->_pagedata['week_str'] = $this->week_str;
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->page('card/indexcard_add.html');
    }
    
    public function indexcard_edit($id){
        $this->db->from('indexcard');
        $this->db->where(array('id'=> $id));
        $detail = $this->db->get()->row_array();
        $this->_pagedata['detail'] = $detail;
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $id_arr = explode(',', trim($this->_pagedata['detail']['box_no'], ','));
        foreach($equipment_list as $k=>$v){
            $equipment_list[$k]['checked'] = in_array($v['equipment_id'], $id_arr)?1:0;
        }
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $id_arr = explode(',', trim($this->_pagedata['detail']['per_day'], ','));
        foreach($this->week_str as $k=>&$v){
            $this->week_str[$k]['checked'] = in_array($k, $id_arr)?1:0;
        }
        $this->_pagedata['week_str'] = $this->week_str;
        $this->page('card/indexcard_add.html');
    }
    
    public function indexcard_save(){
        $id = $this->input->post('id');
        $box_no = $this->input->post('box_no');
        if(empty($box_no)){
            $this->showJson(array('status' => 'error', 'msg' => '设备号必填'));
        }
        $box_no = implode(',',$box_no);
        $param['box_no']        = ','.$box_no.',';
        $param['title']         = $this->input->post('title');
        $param['start_time']    = $this->input->post('start_time')?$this->input->post('start_time').' 00:00:00':date('Y-m-d 00:00:00');
        $param['end_time']      = $this->input->post('end_time')?$this->input->post('end_time').' 23:59:59':date('Y-m-d 23:59:59');
        $per_day = $this->input->post('per_day');
        $per_day = implode(',',$per_day);
        $param['per_day']        = ','.$per_day.',';
        $param['start_hour']    = $this->input->post('start_hour')?$this->input->post('start_hour'):'00:00:00';
        $param['end_hour']      = $this->input->post('end_hour')?$this->input->post('end_hour'):'23:55:55';
        $param['admin_name']    = $this->_pagedata['adminname'];
        $param['platform_id']   = $this->platform_id;
        $param['get_type']      = $this->input->post('get_type');
    
        $tag = $this->input->post('tag');
        $this->db->from('card_model');
        $this->db->where(array('tag'=>$tag,'is_delete'=>0));
        $card_model = $this->db->get()->row_array();
        if (!$card_model){
            $this->showJson(array('status' => 'error', 'msg'=>'该优惠券码不存在！'));
        }
        $param['tag'] = $tag;
        $this->db->trans_begin();
        if($id>0){
            $this->db->update('indexcard', $param, array('id'=>$id));
        }else{
            //新增活动  默认启用
            $param['active_status'] = 1;
            $param['created_time'] = time();
            $this->db->insert('indexcard', $param);
            $id = $this->db->insert_id();
         }
    
        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            $this->showJson(array('status' => 'error', 'msg'=>'系统错误'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status' => 'success'));
        }
    }
    
    //给活动设置状态
    public function set_index_status(){
        $id  = $this->input->post('id');
        if(!$id){
            $this->showJson(array('status'=>'error', 'msg'=>'请选择编辑项'));
        }
        $val = $this->input->post('val');
        if(!in_array($val, array(0,1,2))){
            $this->showJson(array('status'=>'error', 'msg'=>'活动状态不符合'));
        }
        if($this->db->update('indexcard', array('active_status'=>$val), array('id'=>$id))){
            $this->showJson(array('status'=>'success', 'msg'=>'成功'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'网络异常，请稍后尝试'));
    }
    
    public function ordercard(){
        $this->page('card/ordercard_list.html');
    }
    
    public function ordercard_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $title   = $this->input->get('search_title');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $active_status = $this->input->get('search_active_status');
        $active_time = $this->input->get('search_active_time');
    
        $where = "platform_id={$this->platform_id} and active_status>0 ";
        if($title){
            $where .= " and title like '%".$title."%'";
        }
    
        if($start_time){
            $where .= " and start_time>='{$start_time}' ";
        }
        if($end_time){
            $where .= " and end_time<='{$end_time}' ";
        }
        if($active_time==1){
            $where .= " and start_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==2){
            $where .= " and start_time<='".date('Y-m-d H:i:s')."' ";
            $where .= " and end_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==3){
            $where .= " and end_time<='".date('Y-m-d H:i:s')."' ";
        }
        if($active_status>0){
            $where .= " and active_status={$active_status} ";
        }
        $sql = "select * from cb_ordercard where {$where} order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
    
        foreach ($list as $k => &$v) {
            if($v['start_time'] > date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >未开始</button>';
            }elseif($v['end_time'] < date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >已结束</button>';
            }elseif($v['start_time'] <= date('Y-m-d H:i:s') && $v['end_time'] >= date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >时间内</button>';
            }
            if($v['active_status']==1){//启用
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已启用</button>';
            }elseif($v['active_status']==2){
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已暂停</button>';
            }
            $v['card_model_config'] = json_decode($v['card_model_config']);
            $v['id'] = '<a  target="_blank" href="/card/ordercard_edit/'.$v['id'].'">'.$v['id'].'</a>';
        }
    
        $sql = "select count(id) as num from cb_ordercard where {$where} ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }
    
    public function ordercard_add(){
        $code = array();
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->page('card/ordercard_add.html');
    }
    
    public function ordercard_edit($id){
        $this->db->from('ordercard');
        $this->db->where(array('id'=> $id));
        $detail = $this->db->get()->row_array();
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $id_arr = explode(',', trim($detail['box_no'], ','));
        foreach($equipment_list as $k=>$v){
            $equipment_list[$k]['checked'] = in_array($v['equipment_id'], $id_arr)?1:0;
        }
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->_pagedata['detail'] = $detail;
        $this->_pagedata['detail']['card_model_config'] = json_decode($this->_pagedata['detail']['card_model_config'], true);
        $this->_pagedata['img_http']   = $this->img_http;
        $this->page('card/ordercard_add.html');
    }
    
    public function ordercard_save(){
        $id = $this->input->post('id');
        $box_no = $this->input->post('box_no');
        if(empty($box_no)){
            $this->showJson(array('status' => 'error', 'msg' => '设备号必填'));
        }
        $box_no = implode(',',$box_no);
        $param['box_no']        = ','.$box_no.',';
        $param['title']         = $this->input->post('title');
        $param['start_time']    = $this->input->post('start_time')?$this->input->post('start_time'):date('Y-m-d 00:00:00');
        $param['end_time']      = $this->input->post('end_time')?$this->input->post('end_time'):date('Y-m-d 00:00:00', strtotime('1 day'));
        $param['card_count']    = $this->input->post('card_count');
        $param['admin_name']    = $this->_pagedata['adminname'];
        $param['rule_text']     = $this->input->post('rule_text');
        $param['share_img']     = $this->input->post('share_img');
        $param['share_title']   = $this->input->post('share_title');
        $param['share_content'] = $this->input->post('share_content');
        $param['platform_id']   = $this->platform_id;
    
        $config = array();
        $count = $this->input->post('count');
        $tag = $this->input->post('tag');
        if(!empty($tag)){
            foreach($tag as $k=>$v){
                if ($v != ''){
                    //todo 验证tag是否存在
                    $this->db->from('card_model');
                    $this->db->where(array('tag'=>$v,'is_delete'=>0));
                    $card_model = $this->db->get()->row_array();
                    if (!$card_model){
                        $this->showJson(array('status' => 'error', 'msg'=>$v.'该优惠券码不存在！'));
                    }
                    $config[$k]['tag']  = $v;
                    $config[$k]['count'] = $count[$k];
                }
            }
        }
    
        $param['card_model_config']   = json_encode($config);
        $this->db->trans_begin();
        if($id>0){
            $this->db->update('ordercard', $param, array('id'=>$id));
        }else{
            //新增活动  默认启用
            $param['active_status'] = 1;
            $param['created_time'] = time();
            $this->db->insert('ordercard', $param);
            $id = $this->db->insert_id();
        }
    
        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            $this->showJson(array('status' => 'error', 'msg'=>'系统错误'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status' => 'success'));
        }
    }
    
    //给活动设置状态
    public function set_order_status(){
        $id  = $this->input->post('id');
        if(!$id){
            $this->showJson(array('status'=>'error', 'msg'=>'请选择编辑项'));
        }
        $val = $this->input->post('val');
        if(!in_array($val, array(0,1,2))){
            $this->showJson(array('status'=>'error', 'msg'=>'活动状态不符合'));
        }
        if($this->db->update('ordercard', array('active_status'=>$val), array('id'=>$id))){
            $this->showJson(array('status'=>'success', 'msg'=>'成功'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'网络异常，请稍后尝试'));
    }
    
    public function thirdpartycard(){
        $this->page('card/thirdpartycard_list.html');
    }
    
    public function thirdpartycard_table(){
        $limit = $this->input->get('limit') ? $this->input->get('limit') : 10;
        $offset = $this->input->get('offset') ? $this->input->get('offset') : 0;
        $title   = $this->input->get('search_title');
        $start_time = $this->input->get('search_start_time');
        $end_time   = $this->input->get('search_end_time');
        $active_status = $this->input->get('search_active_status');
        $active_time = $this->input->get('search_active_time');
        $refer = $this->input->get('search_refer');
    
        $where = "platform_id={$this->platform_id} and active_status>0 ";
        if($title){
            $where .= " and title like '%".$title."%'";
        }
    
        if($start_time){
            $where .= " and start_time>='{$start_time}' ";
        }
        if($end_time){
            $where .= " and end_time<='{$end_time}' ";
        }
        if($active_time==1){
            $where .= " and start_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==2){
            $where .= " and start_time<='".date('Y-m-d H:i:s')."' ";
            $where .= " and end_time>='".date('Y-m-d H:i:s')."' ";
        }elseif($active_time==3){
            $where .= " and end_time<='".date('Y-m-d H:i:s')."' ";
        }
        if($active_status>0){
            $where .= " and active_status={$active_status} ";
        }
        if($refer){
            $where .= " and refer = '{$refer}' ";
        }
        $sql = "select * from cb_thirdpartycard where {$where} order by id desc limit {$offset},{$limit}";
        $list = $this->db->query($sql)->result_array();
    
        foreach ($list as $k => &$v) {
            if($v['start_time'] > date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >未开始</button>';
            }elseif($v['end_time'] < date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >已结束</button>';
            }elseif($v['start_time'] <= date('Y-m-d H:i:s') && $v['end_time'] >= date('Y-m-d H:i:s')){
                $v['active_time'] = '<button type="button" class="btn btn-success  btn-sm" >时间内</button>';
            }
            if($v['active_status']==1){//启用
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已启用</button>';
            }elseif($v['active_status']==2){
                $v['active_status'] = '<button type="button" class="btn btn-danger  btn-sm" >已暂停</button>';
            }
            $v['card_model_config'] = json_decode($v['card_model_config']);
            $v['id'] = '<a  target="_blank" href="/card/thirdpartycard_edit/'.$v['id'].'">'.$v['id'].'</a>';
        }
    
        $sql = "select count(id) as num from cb_thirdpartycard where {$where} ";
        $total = $this->db->query($sql)->row_array();
        $result = array(
            'total' => $total['num'],
            'rows' => $list
        );
        echo json_encode($result);
    }
    
    public function thirdpartycard_add(){
        $code = array();
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->page('card/thirdpartycard_add.html');
    }
    
    public function thirdpartycard_edit($id){
        $this->db->from('thirdpartycard');
        $this->db->where(array('id'=> $id));
        $detail = $this->db->get()->row_array();
        $this->load->model('equipment_model');
        $store_list = $this->equipment_model->get_store_list();
        foreach($store_list as $k=>$v){
            $code[] = $v['code'];
        }
        $equipment_list = $this->equipment_model->get_equipment_by_code($code);
        $id_arr = explode(',', trim($detail['box_no'], ','));
        foreach($equipment_list as $k=>$v){
            $equipment_list[$k]['checked'] = in_array($v['equipment_id'], $id_arr)?1:0;
        }
        $this->_pagedata['store_list'] = $store_list;
        $this->_pagedata['equipment_list'] = $equipment_list;
        $this->_pagedata['detail'] = $detail;
        $this->_pagedata['img_http']   = $this->img_http;
        $this->page('card/thirdpartycard_add.html');
    }
    
    public function thirdpartycard_save(){
        $id = $this->input->post('id');
        $box_no = $this->input->post('box_no');
        if(empty($box_no)){
            $this->showJson(array('status' => 'error', 'msg' => '设备号必填'));
        }
        $box_no = implode(',',$box_no);
        $param['box_no']        = ','.$box_no.',';
        $param['title']         = $this->input->post('title');
        $param['start_time']    = $this->input->post('start_time')?$this->input->post('start_time'):date('Y-m-d 00:00:00');
        $param['end_time']      = $this->input->post('end_time')?$this->input->post('end_time'):date('Y-m-d 00:00:00', strtotime('1 day'));
        $param['card_count']    = $this->input->post('card_count');
        $param['refer']         = $this->input->post('refer');
        $param['rule_text']     = $this->input->post('rule_text');
        $param['admin_name']    = $this->_pagedata['adminname'];
        $param['platform_id']   = $this->platform_id;
    
        $this->db->trans_begin();
        if($id>0){
            $this->db->update('thirdpartycard', $param, array('id'=>$id));
        }else{
            //新增活动  默认启用
            $param['active_status'] = 1;
            $param['created_time'] = time();
            $this->db->insert('thirdpartycard', $param);
            $id = $this->db->insert_id();
        }
    
        if ($this->db->trans_status() === FALSE ) {
            $this->db->trans_rollback();
            $this->showJson(array('status' => 'error', 'msg'=>'系统错误'));
        } else {
            $this->db->trans_commit();
            $this->showJson(array('status' => 'success'));
        }
    }
    
    //给活动设置状态
    public function set_thirdparty_status(){
        $id  = $this->input->post('id');
        if(!$id){
            $this->showJson(array('status'=>'error', 'msg'=>'请选择编辑项'));
        }
        $val = $this->input->post('val');
        if(!in_array($val, array(0,1,2))){
            $this->showJson(array('status'=>'error', 'msg'=>'活动状态不符合'));
        }
        if($this->db->update('thirdpartycard', array('active_status'=>$val), array('id'=>$id))){
            $this->showJson(array('status'=>'success', 'msg'=>'成功'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'网络异常，请稍后尝试'));
    }
    
    
    
    function get_rand($proArr) {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }
    
    public function del_card_model($id){
        $param['is_delete'] = 1;
        if($this->db->update('card_model', $param, array('id'=>$id))){
            $this->showJson(array('status'=>'success'));
        }
        $this->showJson(array('status'=>'error', 'msg'=>'删除失败'));
    }
    
}
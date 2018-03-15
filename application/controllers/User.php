<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class User extends MY_Controller
{
    public $workgroup = 'user';

    function __construct() {
        parent::__construct();
        $this->load->model("user_model");
        $this->load->model("equipment_model");
        $this->load->model('user_acount_model');
        $this->load->model("order_model");
    }

    public function user_list(){
        $this->page('user/user_list.html');
    }

    public function user_black_list(){
        $this->page('user/black_list.html');
    }
    public function user_black_list_table(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:10;
        $offset = $gets['offset']?$gets['offset']:0;
        $this->db->from('mobile_black');
        if($gets['mobile']){
           $this->db->like("mobile",$gets['mobile']);
        }
        $this->db->order_by('id desc');
        $this->db->limit($limit,$offset);
        $list = $this->db->get()->result_array();
        $this->db->select("id");
        $this->db->from('mobile_black');
        if($gets['mobile']){
            $this->db->like("mobile",$gets['mobile']);
        }
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $list
        );
        echo json_encode($result);
    }
    public function user_list_table(){
        $limit         = $this->input->get('limit')?$this->input->get('limit'):10;
        $offset        = $this->input->get('offset')?$this->input->get('offset'):0;
        $mobile        = $this->input->get('search_mobile')?$this->input->get('search_mobile'):'';
        $user_name     = $this->input->get('search_user_name')?$this->input->get('search_user_name'):'';
        $source        = $this->input->get('search_source')?$this->input->get('search_source'):'';
        $search_device = $this->input->get('search_device')?$this->input->get('search_device'):'';
        $start_time    = $this->input->get('search_start_time')?$this->input->get('search_start_time'):'';
        $end_time      = $this->input->get('search_end_time')?$this->input->get('search_end_time'):'';
        $id            = $this->input->get('search_id')?$this->input->get('search_id'):'';
        $sort          = $this->input->get('sort')?'i.'.$this->input->get('sort'):'u.id';
        $order         = $this->input->get('order')?$this->input->get('order'):'asc';
        $box_param['name']    = $search_device;
        $search_box = array();
        if($box_param['name']){
            $search_box = $this->equipment_model->get_box_no($box_param, 'equipment_id');//盒子搜索
            if(empty($search_box)){
                $search_box = array('-1');
            }
        }
        $where = '';
        if($id){
            $where .= " and u.id={$id}";
        }
        if($mobile){
            $where .= " and u.mobile={$mobile}";
        }
        if($user_name){
            $where .= " and u.user_name like '%{$user_name}%'";
        }
        if($source){
            $where .= " and u.source='{$source}'";
        }
        $time_where = '';
        if($start_time){
            $time_where .= " and u.reg_time>='{$start_time}'";
        }
        if($end_time){
            $time_where .= " and u.reg_time<='{$end_time}'";
        }
        if(!empty($search_box)){
            $search_box = implode("','", $search_box);
            $where .= " and u.register_device_id in('{$search_box}')";
        }
        $sql = " SELECT u.is_black, u.id, u.mobile, u.user_name,u.city, u.reg_time,u.source, u.open_id, u.register_device_id, u.acount_id, i.buy_times, i.total_money, i.open_times from cb_user u LEFT JOIN cb_user_daily_info i ON u.id=i.uid WHERE  i.platform_id = {$this->platform_id} and u.id is not null {$where} {$time_where} ORDER BY {$sort} {$order} LIMIT {$offset}, {$limit}";
        $list = $this->db->query($sql)->result_array();

        $sql = " SELECT count(u.id) as total from cb_user u LEFT JOIN cb_user_daily_info i ON u.id=i.uid WHERE  i.platform_id = {$this->platform_id} and u.id is not null {$where} {$time_where}";
        $total = $this->db->query($sql)->row_array();

        $result = array(
            'total'  => $total['total'],
            'rows'  => $list
        );
        echo json_encode($result);exit;
    }


    public function download_html($num){
        $limit = 5000;
        $page = ceil($num/$limit);
        $result = array();
        for($i=1;$i<=$page; $i++){
            $start = ($i-1)*$limit;
            $next = $i*$limit;
            $next = $next>$num?$num:$next;
            $result[$i]['text'] = '导出第'.$start.'-'.$next.'条用户';
            $result[$i]['url']  = '/user/user_list_table_new?is_export=1&page='.$i.'&limit='.$limit.'&offset='.$start;
        }
        $this->Smarty->assign('list',$result);
        $html = $this->Smarty->fetch('user/download_model.html');
        $this->showJson(array('status'=>'success', 'html' => $html));
    }

    function user_export($list){


        @set_time_limit(0);
        ini_set('memory_limit', '500M');
        include(APPPATH . 'libraries/Excel/PHPExcel.php');
        $objPHPExcel = new PHPExcel();
        $objPHPExcel->setActiveSheetIndex(0)
            ->setCellValue('A1', '用户ID')
            ->setCellValue('B1', '用户手机号')
            ->setCellValue('C1', '昵称')
            ->setCellValue('D1', '注册时间')
            ->setCellValue('E1', '最后购买时间')
            ->setCellValue('F1', '购买次数')
            ->setCellValue('G1', '消费金额')
            ->setCellValue('H1', '开门次数')
            ->setCellValue('I1', '注册设备')
            ->setCellValue('J1', '来源');
        $objPHPExcel->getActiveSheet()->setTitle('用户信息');

        foreach($list as $k=>$item){
            $i = $k+2;
            $objPHPExcel->getActiveSheet()
                ->setCellValue('A'.$i, $item['id'])
                ->setCellValue('B'.$i, $item['mobile'])
                ->setCellValue('C'.$i, $item['user_name'])
                ->setCellValue('D'.$i, $item['reg_time'])
                ->setCellValue('E'.$i, $item['last_buy_time'])
                ->setCellValue('F'.$i, $item['buy_times'])
                ->setCellValue('G'.$i, $item['total_money'])
                ->setCellValue('H'.$i, $item['open_times'])
                ->setCellValue('I'.$i, $item['name'])
                ->setCellValue('J'.$i, $item['source']);
        }

        // Redirect output to a client’s web browser (Excel2007)
        $filename = date('Y-m-d');
        $objPHPExcel->initHeader($filename);
        $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
        $objWriter->save('php://output');


        exit;
    }

    function user_bind($id){
        $data  = $this->user_model->get_info_by_id($id);
        $this->_pagedata['user'] = $data['user'];
        $this->_pagedata['admin'] = $data['admin'];
        $this->page('user/user_bind.html');
    }

    function data_encode($data){
        echo json_encode($data);
    }

    //绑定后台账户  已废弃
    function do_bind(){
        $params = $this->input->post();
        $uid = $params['uid'];
        $name = $params['name'];
        $admin_info = $this->user_model->get_admin_info($name);
        if (empty($admin_info)){
            $this->data_encode(array('code'=>'300','msg'=>'错误的后台账户'));
        }else{
            $where = array('id'=>$uid);
            $data = array('s_admin_id'=>$admin_info['id']);
            $r = $this->user_model->user_update($data,$where);
            if($r){
                $this->data_encode(array('code'=>'200','msg'=>'绑定成功'));
            }else{
                $this->data_encode(array('code'=>'300','msg'=>'操作失败，请稍后重试'));
            }
        }
    }

    //解绑后台账户
    function do_unbild(){
        $params = $this->input->post();
        $uid = $params['uid'];
        $where = array('id'=>$uid);
        $data = array('s_admin_id'=>0);
        $r = $this->user_model->user_update($data,$where);
        if($r){
            $this->data_encode(array('code'=>'200','msg'=>'解绑成功'));
        }else{
            $this->data_encode(array('code'=>'300','msg'=>'操作失败，请稍后重试'));
        }
    }

    function s_admin_ap(){
        $params = $this->input->post();
        $query = $params['query'];
        $this->db->dbprefix = '';
        $data = $this->db->select('name')->from('s_admin')->where(array(
            'name like'=>'%'.$query.'%'
        ))->limit(10)->get()->result_array();
//        foreach ($data as $v){
//            $json[] = $v['name'];
//        }
        $this->db->dbprefix = 'cb_';
        echo json_encode($data);
    }
    function update_black(){
        $params = $this->input->post();
        $uid = $params['user_id'];
        $this->user_model->update_user($uid,array('is_black'=>1));
        $params1['uid'] = htmlspecialchars($uid);
        $this->get_api_content( $params1, '/api/account/update_user_black?uid='.$uid, 0);
        echo "succ";
    }
    function insert_black_mobile(){
        $params = $this->input->post();
        $mobile = $params['mobile'];
        $this->user_model->insert_black_mobile($mobile);
        echo "succ";
    }
    function del_black_mobile(){
        $mobile = $this->input->post("mobile");
         $this->user_model->del_black_mobile($mobile);
        echo "succ";


    }
}
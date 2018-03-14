<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_admin_model extends CI_Model
{
    private $secret_v2 = 'd50b6a5ff6ff4a3j814y6f6b97ec62ab';
    public $redis;

    function __construct() {
        parent::__construct();
        $this->table = 'equipment';
    }
    
    function getList($where = array(),$limit = array()){
        $this->db->where($where);
        if (!empty($limit)) {
            $this->db->limit($limit['per_page'], $limit['curr_page']);
        }
        $this->db->select("*");
        $this->db->from($this->table);
        $this->db->order_by('id', 'asc');
        $query = $this->db->get();
        $res = $query->result_array();
        return $res;
    }

    function get_list($where,$limit='',$offset='',$order='',$sort='',$where_in = ''){
        $this->db->select("*");
        $this->db->from($this->table);
        if(!empty($where))
            $this->db->where($where);
        $this->db->limit($limit,$offset);
        $this->db->order_by('id', 'desc');
        $array = $this->db->get()->result_array();
        $this->load->model('equipment_label_model');
        $this->load->model('order_model');
        foreach($array as $k=>$eachRes){
            if ($eachRes['status'] == 1){
                $array[$k]['status_name'] = '启用';
            } elseif ($eachRes['status'] == 0){
                $array[$k]['status_name'] = '停用';
            } elseif ($eachRes['status'] == 99){
                $array[$k]['status_name'] = '报废';
            }
            if ($eachRes['type'] == 'rfid'){
                $array[$k]['type'] = 'RFID';
            } elseif ($eachRes['type'] == 'scan'){
                $array[$k]['type'] = '扫码';
            } elseif ($eachRes['type'] == 'vision'){
                $array[$k]['type'] = '视觉';
            }
            $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
            $array[$k]['firstordertime'] = $array[$k]['firstordertime'] ? date('Y-m-d H:i:s',$array[$k]['firstordertime']) : '无订单记录';
            $address = $this->get_box_address($eachRes['equipment_id']);
            $array[$k]['province_city_area'] = $address['province'].$address['city'].$address['area'];
            //心跳
            $array[$k]['heart_status']  = "正常";
            if('online' != device_last_status_helper($eachRes['equipment_id']) ){
                $array[$k]['heart_status'] = '异常';
            }
            $array[$k]['stock_all'] = count($this->equipment_label_model->get_labels_by_box_id($eachRes['equipment_id'],'active'));
            $array[$k]['count_order_people'] = count($this->order_model->get_orders($eachRes['equipment_id']));
            $array[$k]['report_link'] = '<a target="_blank" title="编辑" href="/equipment/edit/'.$eachRes['id'].'">编辑</a>&nbsp;';
            $array[$k]['report_link'] .= '<a target="_blank" title="查看库存" href="/equipment/stock/'.$eachRes['equipment_id'].'">库存</a>&nbsp;';
        }

        $this->db->select("*");
        $this->db->from($this->table);
        if(!empty($where))
            $this->db->where($where);
        $total = $this->db->get()->num_rows();
        $result = array(
            'total' => $total,
            'rows' => $array
        );
        return $result;
    }

    function getEquipments($where)
    {

        $this->db->where($where);
        $this->db->select("*");
        $this->db->from($this->table);
        $this->db->order_by('id', 'asc');
        $query = $this->db->get();
        $array = $query->result_array();

        foreach ($array as $k=>$eachRes){
            if ($eachRes['status'] == 1){
                $array[$k]['status_name'] = '启用';
            } elseif ($eachRes['status'] == 0){
                $array[$k]['status_name'] = '停用';
            } elseif ($eachRes['status'] == 99){
                $array[$k]['status_name'] = '报废';
            }

            $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
            $array[$k]['firstordertime'] = $array[$k]['firstordertime'] ? date('Y-m-d H:i:s',$array[$k]['firstordertime']) : '无订单记录';

            $array[$k]['camera'] = $eachRes['is_camera'] == 1 ? '是':'否';
            $array[$k]['stock_all'] = "<a href = '/equipment/stock/".$eachRes['id']."'>".$eachRes['stock_all']."</a>";

            $remarks = '';


            $array[$k]['remarks'] = $remarks;
        }

//        echo $sql;

        return $array;
    }

    public function getEquipmentsForShipping($params, $total=false)
    {
        $dbprefix = $this->db->dbprefix;
        $this->db->dbprefix = '';

        $this->db->from($dbprefix . 'equipment e')
            ->join('s_admin a', 'a.id=e.admin_id', 'left')
            ->join('cb_admin_equipment ae', 'ae.equipment_id=e.equipment_id', 'left');

        if (!empty($params['where'])) {
            foreach ((array)$params['where'] as $k => $v) {
                if (is_array($v)) {
                    $this->db->where_in($k, $v);
                }
                $this->db->where($k, $v);
            }
        }

        if ($total) {
            $cou = $this->db->select('count(*) as cou')->get()->row()->cou;
            $this->db->dbprefix = $dbprefix;
            return $cou;
        }
        $this->db->limit($params['limit'], $params['offset']);
        if (!empty($params['order_by'])) {
            $this->db->order_by($params['order_by']);
        }
        $this->db->select('e.name,e.id,e.equipment_id,a.alias as admin_name,is_auto');
        $rows = $this->db->get()->result_array();
        if(empty($rows)){
            return [];
        }
        $equipment_ids = array_column($rows, 'equipment_id');

        $stocks = $this->db->select('count(*) as sku_num,sum(stock) as num, equipment_id')
            ->from('cb_equipment_stock')
            ->where_in('equipment_id', $equipment_ids)
            ->group_by('equipment_id')
            ->get()->result_array();
        $sku = array_column($stocks, 'sku_num', 'equipment_id');
        $num = array_column($stocks, 'num', 'equipment_id');

        $date = $this->db->select('max(`send_date`) as send_date, equipment_id')
            ->from('cb_shipping_order')
            ->where_in('equipment_id', $equipment_ids)
            ->where(['is_del' => 0, 'status !=' => 3])
            ->group_by('equipment_id')
            ->get()->result_array();
        $date = array_column($date, null, 'equipment_id');

        $today = $this->db->select('send_date, equipment_id,operation_id,id')
            ->from('cb_shipping_order')
            ->where_in('equipment_id', $equipment_ids)
            ->where(['is_del' => 0, 'status !=' => 3, 'send_date' => date('Y-m-d')])
            ->get()->result_array();
        $today = array_column($today, null, 'equipment_id');

        $this->load->model("shipping_order_model");
        foreach ($rows as &$v){
            $v['sku_num'] = $sku[$v['equipment_id']];
            $v['num'] = $num[$v['equipment_id']];
            if(!empty($date[$v['equipment_id']])){
                $v['send_date'] = $date[$v['equipment_id']];
            }

            if(!empty($today[$v['equipment_id']])){
                $today[$v['equipment_id']]['send_date'] = date('Y-m-d', strtotime($today[$v['equipment_id']]['send_date']));
                $today[$v['equipment_id']]['operation_name'] = Shipping_order_model::$operation_name[$today[$v['equipment_id']]['operation_id']];
                $v['today'] = $today[$v['equipment_id']];
            }

            $has_cache = $this->redis->hGet("pro_expire_hash",'eq_id_' . $v['equipment_id']);

            if($has_cache){
                if (json_decode($has_cache,1)['total_num'] > 0){
                    $v['remarks'] = "<a style='color:#333;' href = '/deliver/off_shipping_add_order/".$v['id']."'>有<span style='color:red;font-weight: bold;'>".(json_decode($has_cache,1)['total_num'])."</span>个过保鲜期商品;</a>";
                } else {
                    $v['remarks'] = "有<span style='color:red;font-weight: bold;'>".(json_decode($has_cache,1)['total_num'])."</span>个过保鲜期商品;";
                }
            }
        }
        $this->db->dbprefix = $dbprefix;
        return $rows;
    }


    function get_box_no($data, $field=''){
        $where = array("platform_id"=>$this->platform_id);
        if($data['province']){
            $where['province'] = $data['province'];
        }
        if($data['city']){
            $where['city'] = $data['city'];
        }
        if($data['area']){
            $where['area'] = $data['area'];
        }
        if($data['code']){
            $where['code'] = $data['code'];
        }
        if($data['name']){
            $where['name like'] = '%'.$data['name'].'%';
        }
        if($data['equipment_id']){
            $where['equipment_id'] = $data['equipment_id'];
        }
        if($data['replenish_location']){
            $where['replenish_warehouse'] = $data['replenish_location'];
        }
        if($data['admin_id']){
            $where['admin_id'] = $data['admin_id'];
        }
        if($data['status']){
            $where['status'] = $data['status'];
        }
        if($data['type']){
            $where['type'] = $data['type'];
        }
        if(empty($where)){
            return array();
        }
        $this->db->from('equipment');
        $this->db->where($where);
        if($data['address']){
            $this->db->like('address', $data['address']);
        }
        $rs = $this->db->get()->result_array();
        if($field){
            $tmp = array();
            foreach($rs as $k=>$v){
                $tmp[] = $v[$field];
            }
            return $tmp;
        }
        return $rs;
    }

    //所有开启的盒子 status：1
    public function get_all_box(){
        $this->db->select('equipment_id, name, replenish_location, type');
        $this->db->from('equipment');
        $this->db->where(array('status'=>1));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }
    //所有有admin的盒子
    public function get_all_box_admin(){
        $this->db->select('equipment_id, name, replenish_location, type');
        $this->db->from('equipment');
        $this->db->where(array('admin_id >'=>0));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }

    //所有有admin的盒子
    public function get_store_list_byCode_admin(){
        $this->db->select('equipment_id, name, replenish_location, type');
        $this->db->from('equipment');
        $this->db->where(array('admin_id >'=>0));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['equipment_id']] = $v;
        }
        return $result;
    }

    function findByBoxId($equipment_id,$need_admin_info=false){
        $rs = $this->db->select("*")->from('equipment')->where(array(
            'equipment_id'=>$equipment_id,
            'platform_id'=>$this->platform_id
        ))->get()->row_array();

        if($need_admin_info&&$rs){
//            $this->db->dbprefix = '';
            $query = "SELECT `name`,`alias`,`mobile` FROM `s_admin` WHERE `id` = {$rs['admin_id']}";
            $admin_rs = $this->db->query($query)->row_array();
//            $admin_rs = $this->db->select('*')->from('s_admin')->where(array(
//                'id'=>$rs['admin_id']
//            ))->get()->row_array();
            if ($admin_rs){
                $rs['admin_name'] = $admin_rs['name'];
                $rs['admin_alias'] = $admin_rs['alias'];
                $rs['mobile'] = $admin_rs['mobile'];
            }
        }
        return $rs;
    }
    
    function findByBoxCode($code,$id = ''){
        if ($id == ''){
            $where = array('code'=>$code);
        } else {
            $where = array('code'=>$code,'id <>'=>$id);
        }
       
        $rs = $this->db->select("*")->from('equipment')->where($where)->get()->row_array();
        return $rs;
    }
    
    function findById($id){
        $this->db->dbprefix = '';
        $rs = $this->db->select("*")->from('cb_equipment')->where(array(
            'id'=>$id,
            'platform_id'=>$this->platform_id
        ))->get()->row_array();
        if ($rs && $rs['admin_id']){
            $this->db->dbprefix = '';
            $admin_rs = $this->db->select('*')->from('s_admin')->where(array(
                'id'=>$rs['admin_id']
            ))->get()->row_array();
            if ($admin_rs){
                $rs['admin_name'] = $admin_rs['name'];
                $rs['admin_alias'] = $admin_rs['alias'];
                $rs['mobile'] = $admin_rs['mobile'];
            }            
        }
        $this->db->dbprefix = 'cb_';
        return $rs;
    }
    
    function getInsertId(){
        $rs = $this->db->select("id")->from('equipment')->order_by('id desc')->get()->row_array();
        if (!$rs){
            return 1;
        } else {
            return $rs['id']+1;
        }
    }
    
    function getLastTime(){
        $rs = $this->db->select("created_time")->from('equipment')->order_by('created_time desc')->get()->row_array();
        if (!$rs){
            return '';
        } else {
            return $rs['created_time'];
        }
    }
    
    function insertData($data){
        return $this->db->insert('equipment',$data);
    }
    
	
    public function get_box_address($equipment_id){
        $this->db->from('equipment');
        $this->db->where('equipment_id', $equipment_id);
        $rs = $this->db->get()->row_array();
        $ids = array($rs['province'], $rs['city'], $rs['area']);

        $this->db->select('AREAIDS, AREANAME');
        $this->db->from('sys_regional');
        $this->db->where_in('AREAIDS', $ids);
        $regional = $this->db->get()->result_array();
        $tmp = array();
        foreach($regional as $k=>$v){
            $tmp[$v['AREAIDS']] = $v['AREANAME'];
        }
        $result['province'] = $tmp[$rs['province']]?$tmp[$rs['province']]:'';
        $result['city']     = $tmp[$rs['city']]?$tmp[$rs['city']]:'';
        $result['area']     = $tmp[$rs['area']]?$tmp[$rs['area']]:'';
        $result['address']  = $rs['address'];
        return $result;
    }

    //获取门店列表
    public function get_store_list(){
        return array();
    }
    
    public function get_store_list_byCode(){
        $p_db = $this->load->database('platform_master', TRUE);
        $stores = $p_db->select('code,name')->get_where('store', ['is_valid' => 1, 'platform_id'=>$this->platform_id])->result_array();
        $this->load->helper('public');
        return array_column($stores, 'name', 'code');
        /*
        $has_cache = $this->redis->get('get_store_list_byCode');
        if($has_cache){
            return json_decode($has_cache, true);
        }
        $time = time();
        $service = 'open.getStores';
        $params = array(
            'timestamp' => $time,
            'service' => $service,
            "code"=>'',
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
        $result = json_decode($result, true);
        curl_close($ch);
        $stores = array();
        if (!empty($result['stores'])){
            foreach ($result['stores'] as $val){
                $stores[$val['code']] = $val['name'];
            }
        }
        $stores['default'] = '商户自建补货仓';

        $this->redis->set('get_store_list_byCode', json_encode($stores), 86400*7);

        return $stores;*/
    }

    public function create_sign_v2($params) {
        ksort($params);
        $query = '';
        foreach ($params as $k => $v) {
            $query .= $k . '=' . $v . '&';
        }
        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
        return $sign;
    }
    
    public function get_equipments($is_valid = false){
        $this->db->select('equipment_id,name,replenish_location,replenish_warehouse,status,code');
        $this->db->from('equipment');
        $this->db->where(array('platform_id'=>$this->platform_id));
        if($is_valid){
            $this->db->where(['status !=' => 99]);
        }
        $this->db->order_by('id asc');
        $rs = $this->db->get()->result_array();
        return $this->array_sort($rs, 'name', 'asc');
    }

    public function get_admin_equipments($admin_id){
        $this->db->select('e.name equipment_name, e.equipment_id,e.id')
            ->from('admin_equipment ae')
            ->join('equipment e', 'e.equipment_id=ae.equipment_id')
            ->where(['ae.admin_id'=>(int)$admin_id, 'e.status !=' => 99]);
        return $this->db->get()->result_array();
    }

    //对二维数组 进行排序
    public function array_sort($arr,$keys,$type='asc'){
        $tmp = array();
        foreach($arr as $k=>$v){
            $tmp[$k] = trim($v[$keys]);
        }
        if($type == "asc"){
            array_multisort($arr, SORT_ASC, SORT_STRING, $tmp, SORT_ASC);
        }else{
            array_multisort($arr, SORT_DESC, SORT_STRING, $tmp, SORT_DESC);
        }
        return $arr;
    }


    /*
     * @desc 根据补货仓获取盒子列表
     * @param $code_arr array 补货仓code
     * */
    public function get_equipment_by_code($code_arr){
        $this->db->select('equipment_id,name,replenish_warehouse');
        $this->db->from('equipment');
        $this->db->where(array("platform_id"=>$this->platform_id,"status !="=>'99'));
        $this->db->where_in('replenish_warehouse', $code_arr);
        $this->db->order_by('id asc');
        $rs = $this->db->get()->result_array();
        return $this->array_sort($rs, 'name', 'asc');
    }

    //判断当前管理员有哪些盒子权限
    public function check_equipment($search_equipment_arr){
        $admin_id = $this->session->userdata('sess_admin_data')["adminid"];
        $this->load->model("admin_equipment_model");
        $equipment_list = $this->admin_equipment_model->getList($admin_id);
        if(!empty($search_equipment_arr)){
            foreach($search_equipment_arr as $k=>$v){
                if(!in_array($v, $equipment_list)){
                    unset($search_equipment_arr[$k]);
                }
            }
        }
        return $search_equipment_arr;
    }

    public function get_equipment_by_id($id_arr){
        $this->db->select('equipment_id,name');
        $this->db->from('equipment');
        $this->db->where(array("platform_id"=>$this->platform_id));
        $this->db->where_in('equipment_id', $id_arr);
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
    }
//    public function create_sign_v2($params) {
//        ksort($params);
//        $query = '';
//        foreach ($params as $k => $v) {
//            $query .= $k . '=' . $v . '&';
//        }
//        $sign = md5(substr(md5($query . $this->secret_v2), 0, -1) . 'w');
//        return $sign;
//    }

    function getStores(){
        //获取门店列表
        $time = time();
        $service = 'open.getStores';
        $params = array(
            'timestamp' => $time,
            'service' => $service,
            "code"=>'',
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
        $result = json_decode($result);
        curl_close($ch);
        $stores = array();
        if ($result->stores){
            foreach ($result->stores as $val){
                $stores[] = array('code'=>$val->code,'name'=>$val->name);
            }
        }
        return $stores;
    }

    public function dump($filter,$cols='*'){
        $this->db->select($cols);
        $this->db->where($filter);
        $this->db->from("equipment");
        $this->db->limit(1,0);
        $list = $this->db->get()->row_array();
        return $list;
    }

    function get_eq_name($equipment_id, $field=''){
        $where['equipment_id'] = $equipment_id;
        $this->db->from('equipment');
        $this->db->where($where);
        $rs = $this->db->get()->row_array();
        if($field){
            return $rs[$field];
        }
        return $rs;
    }

    //获取所有盒子的名称
    function get_eq_name_all(){
        $where['platform_id'] = $this->platform_id;
        $this->db->select('name,equipment_id');
        $this->db->from('equipment');
        $this->db->where($where);
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
    }

    //获取所有盒子的名称
    function get_eq_all(){
        $where['platform_id'] = $this->platform_id;
        $this->db->select('name,equipment_id,status');
        $this->db->from('equipment');
        $this->db->where($where);
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']]['name'] = $v['name'];
            $status='';
            if($v['status'] == 0){
                $status = '停用';
            }elseif($v['status'] == 1){
                $status = '启用';
            }elseif($v['status'] == 99){
                $status = '报废';
            }
            $tmp[$v['equipment_id']]['status'] = $status;
        }
        return $tmp;
    }

    //获取盒子负责人
    function get_eq_admin($equipment_id){
        $admin_id = $this->get_eq_name($equipment_id, 'admin_id');
        if(!$admin_id){
            return '';
        }
        $sql = "select `name` from s_admin where id={$admin_id}";
        $rs = $this->db->query($sql)->row_array();
        return $rs['name'];
    }

    /*
     * @desc 获取某天 首次订单的盒子
     * @param $start_time 开始时间 时间戳
     * @param $end_time 结束时间 时间戳
     * */
    function get_eq_num_first($start_time, $end_time){
        $where['platform_id'] = $this->platform_id;
        $where['status'] = 1;
        $where['firstordertime >='] = $start_time;
        $where['firstordertime <='] = $end_time;
        $this->db->from('equipment');
        $this->db->where($where);
        return $this->db->get()->num_rows();
    }

    /*
     * @desc 当天 首次订单的盒子
     * @param
     * */
    public function get_eq_curr(){
        $date = date('Y-m-d 00:00:00');
        $sql = "select count(DISTINCT(`box_no`)) as new_eq from cb_order where box_no in(SELECT `equipment_id` FROM (`cb_equipment`) WHERE `platform_id` = {$this->platform_id} AND `status` = 1 AND `firstordertime` is null) and order_time>'{$date}'";
        $rs = $this->db->query($sql)->row_array();
        return intval($rs['new_eq']);
    }
     /*
     * @desc 获取设备二维码为common
     * @param
     * */
    public function get_equipment_qr($refer = 'common'){
        $sql = 'select equipment_id from cb_equipment_qr where refer ="'.$refer.'"';
        $rs = $this->db->query($sql)->result_array();
        return $rs;
    }
    //根据equipment_id更新商户id 名称 状态
    function saveEquipment($data){
        $this->db->set('platform_id', $data['platform_id']);
        $this->db->set('name', $data['name']);
        $this->db->set('status', $data['status']);
        $this->db->where('equipment_id', $data['equipment_id']);
        $rs = $this->db->update('equipment');
        return $rs;
    }
    //根据新equipment_id更新商户id 名称 状态 并 还原旧equipment_id
    function replaceEquipment($data){
        $this->db->trans_begin();
        $this->db->set('platform_id',"");
        $this->db->set('name', "");
        $this->db->set('status', 0);
        $this->db->where('equipment_id', $data['equipment_id']);
        $this->db->update('equipment');

        $this->db->set('platform_id',$data['platform_id']);
        $this->db->set('name', $data['name']);
        $this->db->set('status', 1);
        $this->db->where('equipment_id', $data['replace_equipment_id']);
        $this->db->update('equipment');
        if ($this->db->trans_status() === FALSE)
        {
            $this->db->trans_rollback();
            return false;
        }else{
            $this->db->trans_commit();
            return true;
        }
    }
    //根据equipment_id更新状态
    function upEquipment($data){
        $this->db->set('status', $data['status']);
        $this->db->where('equipment_id', $data['equipment_id']);
        $rs = $this->db->update('equipment');
        return $rs;
    }
    function getRow($data){
        $this->db->where("equipment_id",$data['equipment_id']);
        $this->db->select("*");
        $this->db->from('equipment');
        $query = $this->db->get();
        $res = $query->row_array();

        return $res;
    }
    function rollback($equipment_id){
        $this->db->set('status', 0);
        $this->db->set('name', "");
        $this->db->set('platform_id', "");
        $this->db->where('equipment_id', $equipment_id);
         $this->db->update('equipment');
    }

    /*
     * @desc 根据id 获取设备
     * @param array $ids
     * */
    function get_eq_by_id($ids=array()){
        if(empty($ids)){
            return array();
        }
        $this->db->select('id,name,level');
        $this->db->from('equipment');
        $this->db->where_in('id', $ids);
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['id']] = $v;
        }
        return $tmp;
    }
    /**
     * 根据设备号  获取设备名
     * @param array $id_arr
     * @return array
    */
    public function get_equipment_by_ids($id_arr){
        if(empty($id_arr)){
            return array();
        }
        $this->db->select('equipment_id,name');
        $this->db->from('equipment');
        $this->db->where_in('equipment_id', $id_arr);
        $rs = $this->db->get()->result_array();
        $tmp = array();
        foreach($rs as $k=>$v){
            $tmp[$v['equipment_id']] = $v['name'];
        }
        return $tmp;
    }
    //获取没有同步支付宝的设备
    public function get_alipay_equipments(){
        $this->db->select('equipment_id,name,replenish_location,replenish_warehouse,status,code');
        $this->db->from('equipment');
        $this->db->where(['status !=' => 99,'sync_alipay =' => 0]);
        $this->db->order_by('id asc');
        $rs = $this->db->get()->result_array();
        return $this->array_sort($rs, 'name', 'asc');
    }
}

?>

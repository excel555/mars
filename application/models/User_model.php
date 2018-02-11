<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_model extends MY_Model
{
    private $field = "";
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'user';
    }
    public function return_field()
    {
        return ["id","mobile","user_name","reg_time","open_id","is_black","s_admin_id","avatar","city","province","gender","source","agreement_no","scene","sign_time","thirdpart_id","sign_detail","zm_open_id","program_openid","unionid","user_rank","acount_id","equipment_id"];
    }

	function get_user_info_by_id($id,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
		$where = array('id'=>$id);
		$this->db->select($field);
		$this->db->where($where);
		$this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
        if($res['acount_id']){
            $account          = $this->get_user_acount($res['acount_id']);
            $res['user_rank'] = isset($account['user_rank'])?intval($account['user_rank']):1;
            $res['moli']      = isset($account['moli'])?intval($account['moli']):0;
            $res['modou']     = isset($account['modou'])?intval($account['modou']):0;
            $res['yue']       = isset($account['yue'])?intval($account['yue']):0;
            $res['frozen_yue']= isset($account['frozen_yue'])?intval($account['frozen_yue']):0;
        }
		return $res;
	}

    function get_user_acount($acount_id){
        $this->db->from('user_acount');
        $this->db->where(array('id'=>$acount_id));
        return  $this->db->get()->row_array();
    }

    function get_user_info($where,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_user_info_by_open_id($open_id,$type,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('open_id'=>$open_id,'source'=>$type);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_user_info_by_program_id($open_id,$type,$field=NULL){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('program_openid'=>$open_id,'source'=>$type);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_user_info_by_mobile($mobile, $field=NULL, $source=null){
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");
        }
        $where = array('mobile'=>$mobile);
        if($source){
            $where['source'] = $source;
        }
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function get_user_info_by_union_id($union_id,$field=NULL){
        if(!$union_id){
            return false;
        }
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('unionid'=>$union_id,'source'=>'wechat');
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }


    function register($data)
    {
        return $this->insert($data);
    }

    //如果已注册过，返回uid
    function is_registered($open_id){
        $rs = $this->select('id')->from('user')->where('open_id',$open_id)->get()->row_array();
        return $rs['id'];
    }

    //增加用户
    function adUser($user_data,$device_id = ""){
        $platform_id = 0;//默认是0
        $this->db->trans_start();
        if($device_id){
            $this->load->model('equipment_model');
            $equipment_info = $this->equipment_model->get_info_by_equipment_id($device_id);
            if($equipment_info && isset($equipment_info['platform_id'])){
                $platform_id = $equipment_info['platform_id'];
            }
        }
        $user_data['platform_id'] = $platform_id;
        $user_data['register_device_id'] = $device_id;
        $this->db->insert('user',$user_data);
        $last_id = $this->db->insert_id();
        if ($platform_id){
            $this->db->insert('user_daily_info',array(
                'uid' => $last_id,
                'register_device_id'=>$device_id,
                'platform_id'=>$platform_id
            ));
            $this->load->model('user_platform_relations_model');
            $this->user_platform_relations_model->add_platform_relation($last_id,$platform_id);
        }
        //创建acount 信息
        $acount['moli']           = 0;
        $acount['modou']          = 1;
        $acount['update_time']    = date('Y-m-d H:i:s');
        $acount['user_rank']      = 1;
        $this->db->insert('user_acount', $acount);//创建acount账户
        $acount_id = $this->db->insert_id();

        $modou_param['acount_id'] = $acount_id;
        $modou_param['uid']       = $last_id;
        $modou_param['modou']     = 1;
        $modou_param['des']       = "首次赠送";
        $modou_param['add_time']  = date('Y-m-d H:i:s');
        $this->db->update('user', array('acount_id'=>$acount_id), array('id'=>$last_id));
        $this->db->insert('user_acount_modou', $modou_param);


        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return 0;
        } else {
            $this->db->trans_commit();
            return $last_id;
        }
    }
    function update_agreement_sign($open_id,$data,$refer = 'alipay',$is_program = ''){
        if(!$open_id)
            return false;
        $agreement_no = $data["agreement_no"];
        $sign_time = $data['sign_time'];
        $this->db->set('agreement_no',$agreement_no);
        if(isset($data["scene"]))
        {
            $this->db->set('scene',$data["scene"]);
        }
        if(isset($data["zm_open_id"])){
            $this->db->set('zm_open_id',$data["zm_open_id"]);
        }
        $this->db->set('sign_time',strtotime($sign_time));
        $thr_id = "";
        if(isset($data["thirdpart_id"])){
            $thr_id = $data["thirdpart_id"];
        }else if(isset($data["partner_id"])){
            $thr_id = $data['partner_id'];
        }
        if(!empty($thr_id))
        {
            $this->db->set('thirdpart_id',$thr_id);
        }
        $this->db->set('sign_detail',json_encode($data));
        if($is_program == 'program'){
            $this->db->where(array('program_openid'=>$open_id,'source'=>$refer));
        }else{
            $this->db->where(array('open_id'=>$open_id,'source'=>$refer));
        }
        return $this->db->update($this->table_name());
    }

    function delete_agreement_sign($open_id,$data,$refer = 'wechat'){
        $this->db->set('agreement_no','');
        $this->db->set('sign_time',strtotime($data["sign_time"]));
        $this->db->set('sign_detail','');
        $this->db->where(array('open_id'=>$open_id,'source'=>$refer));
        return $this->db->update($this->table_name());
    }

    function update_mobile($uid,$mobile){
        // 判断是否有相同手机号的用户
        $this->load->model('user_acount_model');
        $this->user_acount_model->acount_merge($uid, $mobile, $this->get_user_info_by_id($uid));//账号合并

        $this->db->set('mobile',$mobile);
        $this->db->where('id', $uid);
        return $this->db->update($this->table_name());
    }

    function get_user_open_id($id,$filed = 'open_id'){
        $where = array('id'=>$id);
        $this->db->select($filed);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return isset($res[$filed]) ?  $res[$filed] : "";
    }

    function update_user_info($uid,$data){
        $this->db->where('id', $uid);
        return $this->db->update($this->table_name(), $data);
    }
    function get_no_unionid_user($field=NULL){
        $where = array('unionid'=>'','source'=>'wechat');
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");
        }
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        return $this->db->get()->result_array();
    }

    function get_agreement_is_null($source,$limit = 100,$field=NULL){
        $where = array('agreement_no'=>'','source'=>$source);
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");
        }

        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $this->db->limit($limit);
        $this->db->order_by('id','DESC');
        return $this->db->get()->result_array();
    }

    /*
     * @desc 新增用户
     * @return array  用户信息
     * */
    public function create_user($user, $type, $device_id=0){
        $user_data = array(
            "user_name" => isset($user['user_name']) ? $user['user_name'] : "",
            "avatar" => isset($user['avatar']) ? $user['avatar'] : "",
            "city" => isset($user['city']) ? $user['city'] : "",
            "province" => isset($user['province']) ? $user['province'] : "",
            "gender" => isset($user['gender']) ? $user['gender'] : "",
            "source" => $type,
            "reg_time" => date("Y-m-d H:i:s"),
            "open_id" => isset($user['open_id'])?$user['open_id']:0,
            "is_black" => 0,
            "equipment_id" =>  isset($user['equipment_id'])?$user['equipment_id']:0,
            "mobile" => $user['mobile'],
        );
        $rs = $this->adUser($user_data, $device_id);
        if($rs){
            $user_data['id'] = $rs;
            return $this->get_user_info_by_id($rs);
        }
        return array();
    }


    function update_user_deviceId($uid,$device_id = ""){
        if(!$device_id){
            return false;
        }
        $this->db->trans_start();
        $platform_id = 0;
        $this->load->model('equipment_model');
        $equipment_info = $this->equipment_model->get_info_by_equipment_id($device_id);
        if($equipment_info && isset($equipment_info['platform_id'])){
            $platform_id = $equipment_info['platform_id'];
        }

        $user_data['platform_id'] = $platform_id;
        $user_data['register_device_id'] = $device_id;
        $this->db->update('user',$user_data, array('id'=>$uid));
        if ($platform_id){
            $this->db->insert('user_daily_info',array(
                'uid' => $uid,
                'register_device_id'=>$device_id,
                'platform_id'=>$platform_id
            ));
            $this->load->model('user_platform_relations_model');
            $this->user_platform_relations_model->add_platform_relation($uid,$platform_id);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }
    }
}

?>
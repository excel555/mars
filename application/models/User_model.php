<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_model extends MY_Model
{
    private $field = "";
    const REG_GIFT = 20;
    const SIGN_GIFT = 2;
    const FINISH_INFO_ENERGY = 20;
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'user';
    }

	function get_user_info_by_id($id){
		$where = array('id'=>$id);
		$this->db->select("*");
		$this->db->where($where);
		$this->db->from($this->table_name());
        $res = $this->db->get()->row_array();
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

    function get_user_info_by_mobile($mobile){
        $where = array('mobile'=>$mobile);
        $this->db->select("*");
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
    function is_registered($open_id,$refer){
        $where = array('open_id'=>$open_id,'refer'=>$refer);
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from('user_thrid');
        $res = $this->db->get()->row_array();
        return $res;
    }

    //增加用户
    function adUser($user_data,$open_id,$refer){
        $this->db->trans_start();
        $this->db->insert('user',$user_data);
        $last_id = $this->db->insert_id();

        $this->db->set('invite_code',random_code(''.$last_id));
        $this->db->where(array('id'=>$last_id));
        $this->db->update('user');

        $thrid['user_id']           = $last_id;
        $thrid['refer']          = $refer;
        $thrid['open_id']    = $open_id;
        $this->db->insert('user_thrid', $thrid);


        $user_energy_log['user_id']           = $last_id;
        $user_energy_log['create_time']          = date("Y-m-d H:i:s");
        $user_energy_log['obj_type']    = '注册';
        $user_energy_log['obj_id']    = $last_id;
        $user_energy_log['energy']    = self::REG_GIFT;
        $user_energy_log['energy_type']    = 'add';
        $this->db->insert('user_energy_log', $user_energy_log);

        $user_fin['user_id']           = $last_id;
        $user_fin['lastupdate_time']          = date("Y-m-d H:i:s");
        $user_fin['land']    = '0.0';
        $user_fin['energy']    = self::REG_GIFT;;
        $this->db->insert('user_fin', $user_fin);

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
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

    function update_user($uid,$mobile,$name,$idcard){

        $this->db->trans_start();
        $this->db->set('mobile',$mobile);
        $this->db->set('really_name',$name);
        $this->db->set('idcard',$idcard);
        $this->db->where(array('id'=>$uid));
        $rs_u = $this->db->update($this->table_name());

        $user_energy_log['user_id']           = $uid;
        $user_energy_log['create_time']          = date("Y-m-d H:i:s");
        $user_energy_log['obj_type']    = '完善信息';
        $user_energy_log['obj_id']    = $uid;
        $user_energy_log['energy']    = self::FINISH_INFO_ENERGY;
        $user_energy_log['energy_type']    = 'add';
        $this->db->insert('user_energy_log', $user_energy_log);

        $user = get_cache_user($uid);

        $this->db->set('lastupdate_time',date("Y-m-d H:i:s"));
        $this->db->set('energy',self::FINISH_INFO_ENERGY + $user['fin']['energy']);
        $this->db->where(array('user_id'=>$uid));
        $this->db->update('user_fin');

        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            update_user_cache($uid, array("fin"=>array('energy'=>self::FINISH_INFO_ENERGY + $user['fin']['energy'])));
            return $rs_u;
        }
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

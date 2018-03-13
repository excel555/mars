<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class User_agreement_model extends MY_Model
{
    private $field = "";
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'user_agreement';
    }

    /**
     * @param $open_id
     * @param $refer
     * @param $thirdpart_id
     * @param int $is_program
     * @return bool
     * 解约
     */
    function delete_agreement_sign($open_id,$refer,$agreement_no,$is_program = 0){
        if(!$open_id)
            return false;
        if($is_program){
            $where = array('program_openid'=>$open_id,"source"=>$refer);
        }else{
            $where = array('open_id'=>$open_id,"source"=>$refer);
        }
        $this->db->select("id");
        $this->db->where($where);
        $this->db->from('user');
        $user = $this->db->get()->row_array();
        if($user){
            $where = array('user_id'=>$user['id'],"agreement_no"=>$agreement_no);
            $this->db->select("id,thirdpart_id");
            $this->db->where($where);
            $this->db->from($this->table_name());
            $user_ag = $this->db->get()->row_array();
            if($user_ag){
                $this->db->set('agreement_no','');
                $this->db->where(array('id'=>$user_ag['id']));
                $this->db->update($this->table_name());
            }
            return array('thirdpart_id'=>$user_ag['thirdpart_id'],$user_ag['thirdpart_id'].'_agreement_no'=>"",'user_id'=>$user['id']);
        }
        return false;
    }

    /**
     * @param $open_id
     * @param $data
     * @param string $refer
     * @param $thirdpart_id
     * @param string $is_program
     * @return array|bool
     * $data = array(
        'principal_id'=>'open_id',
        'thirdpart_id'=>'app_auth_id',
        'agreement_no'=>'agreement_no',
        'scene'=>'sign_scene',
        'sign_time'=>'sign_time',
        );
     */
    function update_agreement_sign($open_id,$data,$refer = 'alipay',$thirdpart_id,$is_program = ''){
        if(!$open_id)
            return false;
        if($is_program){
            $where = array('program_openid'=>$open_id,"source"=>$refer);
        }else{
            $where = array('open_id'=>$open_id,"source"=>$refer);
        }
        $this->db->select("id");
        $this->db->where($where);
        $this->db->from('user');
        $user = $this->db->get()->row_array();
        if($user){
            $where = array('user_id'=>$user['id'],"thirdpart_id"=>$thirdpart_id);
            $this->db->select("id");
            $this->db->where($where);
            $this->db->from($this->table_name());
            $user_ag = $this->db->get()->row_array();
            $data_a = array();
            $data_a['agreement_no'] = $data["agreement_no"];
            $data_a['source'] = $refer;
            $data_a['user_id'] = $user['id'];
            $data_a['thirdpart_id'] = $thirdpart_id;
            $data_a['sign_detail'] = json_encode($data);
            if(isset($data["scene"])) {
                $data_a['scene'] = $data["scene"];
            }
            if(isset($data["zm_open_id"])){
                $data_a['zm_open_id'] = $data["zm_open_id"];
            }
            if(isset($data["sign_time"])){
                $data_a['sign_time'] = strtotime($data["sign_time"]);
            }
            if($user_ag){
                $this->db->where('id', $user_ag['id']);
                $this->db->update($this->table_name(),$data_a);

            }else{
                $this->db->insert($this->table_name(),$data_a);
            }
            return array('thirdpart_id'=>$thirdpart_id,$thirdpart_id.'_agreement_no'=>$data["agreement_no"],'user_id'=>$user['id']);
        }
        return false;
    }
    public function get_user_agreement_3rd($user_id,$thirdpart_id = null)
    {
        $where = array('user_id'=>$user_id);
        if($thirdpart_id){
            $where['thirdpart_id'] = $thirdpart_id;
        }
        $this->db->select("*");
        $this->db->where($where);
        $this->db->from($this->table_name());
        return $this->db->get()->result_array();
    }

}

?>

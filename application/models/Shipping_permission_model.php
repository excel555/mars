<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Shipping_permission_model extends MY_Model
{
    private $field = "";
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'shipping_permission';
    }
    public function return_field()
    {
        return ["id"];
    }

    //增加权限
    function addPermission($data){
        return $this->db->insert($this->table_name(),$data);
    }

    //更新权限
    function updatePermission($data,$where){
        return $this->db->update($this->table_name(),$data,$where);
    }

    //获取权限
    function getPermission($data){
        $rs = $this->db->from($this->table_name())->where($data)->get()->row_array();
        return $rs;
    }

    //判断是否拥有权限
    function canOpen($uid,$equipment_id=''){
        $this->load->model('user_model');
        $user = $this->user_model->get_user_info(array(
            'id'=>$uid,
            'source'=>'alipay'
        ));

        if(empty($user)){
            return false;
        }

        $mobile = $user['mobile'];

        $today = date('Y-m-d H:i:s');

        $where_sql = "`mobile` = '{$mobile}' AND `status` = 1";
        if(!empty($equipment_id))
            $where_sql .= " AND `equipment_id` = '{$equipment_id}'";
        $where_sql .= " AND (`add_time` >= '{$today}' OR `end_time` >= '{$today}')";  //增加or 逻辑，兼容有end_time 和 end_time为空的两个版本

        $sql = "SELECT * FROM (`cb_shipping_permission`) WHERE ".$where_sql."ORDER BY `id` desc";
        $rs = $this->db->query($sql)->row_array();
        if(!empty($rs)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 是否有老权限开门
     * @param $mobile
     */
    function old_deliver_permission($mobile){
        if(empty($mobile))
            return false;
        $where_sql = "`mobile` = '{$mobile}' AND `status` = 1 AND old_deliver = 1";
        $today = date('Y-m-d H:i:s');
        $where_sql .= " AND `add_time` <= '{$today}' AND `end_time` >= '{$today}' ";
        $sql = "SELECT * FROM (`cb_shipping_permission`) WHERE ".$where_sql."ORDER BY `id` desc";
        $rs = $this->db->query($sql)->row_array();
        if(!empty($rs)){
            return true;
        }else{
            return false;
        }
    }
}

?>

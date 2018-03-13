<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Equipment_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'equipment';
    }
    public function return_field()
    {
        return ["id","equipment_id","status","name","code","serial_num","qr","descriptions","admin_id",'address','platform_id','type', 'add_price'];
    }

    function get_box_info_by_qr($qrcode,$refer =""){
        $field = rtrim(join(",",$this->return_field()),",");

        $this->db->select($field);
        if($refer == "alipay"){
            $this->db->like('qr',$qrcode );
        }
        $this->db->where('status != ',99);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = array();
        if($query)
            $res = $query->row_array();
        return $res;
    }

    function list_active_box(){
        $field = rtrim(join(",",$this->return_field()),",");
        $this->db->select($field);
        $this->db->where('status',1);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = array();
        if($query)
            $res = $query->result_array();
        return $res;
    }

    /**
     * @param $equipment_id
     * @param null $field
     * @return mixed
     * 根据设备编号获取设备信息
     */
    function get_info_by_equipment_id($equipment_id,$field=NULL){
        if(empty($equipment_id)){
            return false;
        }
        if($field === NULL){
            $field = rtrim(join(",",$this->return_field()),",");

        }
        $where = array('equipment_id'=>$equipment_id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = $query->row_array();
        return $res;
    }

    /**
     * @param $equipment_id
     * @param $status 1启用，0停用
     * @return mixed
     */
    function update_status($equipment_id,$status){
        $where = array('equipment_id'=>$equipment_id);
        return $this->db->update($this->table_name(), array('status'=>$status), $where);
    }

    function get_eq_qr($equipment_id,$type='common'){
        $where = array('equipment_id'=>$equipment_id);
        $this->db->select('qr');
        $this->db->where($where);
        $this->db->from('equipment_qr');
        return $this->db->get()->row_array();;
    }
    /**
     * 获取商户的设备
     * @param int $platform_id 平台id
     * @return array
    */
    public function get_eq_by_platform_id($platform_id){
        $this->db->select('id,equipment_id as d,name,status');
        $this->db->where(array('platform_id'=>$platform_id));
        $this->db->from($this->table_name());
        $this->db->order_by('id desc');
        return $this->db->get()->result_array();
    }
}

?>

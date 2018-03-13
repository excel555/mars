<?php
class Admin_equipment_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    /**
     * 表名
     *
     * @return void
     * @author
     **/
    public function table_name()
    {
        return "admin_equipment";
    }
    
    public function getList($admin_id){
		$this->db->from("admin_equipment");
		$this->db->where('admin_id',$admin_id);
		$list = $this->db->get()->result_array();
		if(empty($list)){
			return array();
		}
		$result = array();
		foreach ($list as $key => $value) {
			$result[] = $value['equipment_id'];
		}
		return $result;
	}
	
	//用户是否有某个设备的权限
	public function ifAdminEquipment($admin_id,$equipment_id){
	    $this->db->from("admin_equipment");
	    $this->db->where('admin_id',$admin_id);
	    $this->db->where('equipment_id',$equipment_id);
	    $result = $this->db->get()->row_array();
	    return $result;
	}

}
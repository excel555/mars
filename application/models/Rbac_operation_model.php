<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Rbac_operation_model extends CI_Model
{	
	private $db_log;

	function __construct()
	{
		parent::__construct ();
		$this->db_log = $this->load->database('log', TRUE);
	}

	public function table_name()
	{
		//后台操作日志
		return 'rbac_operation';
	}
	
	function insOperation($data){
		$res = $this->db_log->insert($this->table_name(),$data);
		if($res){
			return true;
		}else{
			return false;
		}
	}
}

?>

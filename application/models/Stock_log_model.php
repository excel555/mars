<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Stock_log_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'stock_log';
    }
    public function return_field()
    {
        return ["id","obj_no","type","create",'result','device_status','device_id'];
    }

    function insert_stock_log($data){
        return $this->db->insert($this->table_name(),$data);
    }
}

?>

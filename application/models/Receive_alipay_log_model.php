<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Receive_alipay_log_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'receive_alipay_log';
    }
    public function return_field()
    {
        return ["id","msg_type","param","receive_time"];
    }

    function insert_log($data){
        $this->db->insert($this->table_name(),$data);
        return $this->db->insert_id();
    }
}

?>

<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Request_msg_log_model extends MY_Model
{

	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'request_msg_log';
    }
    public function return_field()
    {
        return ["id","box_no","req_time",'req_body',"response","req_type","ajax_response","ajax_response_time"];
    }

    function insert_log($data){
        $this->db->insert($this->table_name(),$data);
        return $this->db->insert_id();
    }

    function update_log($response,$insert_id){
        $this->db->set('response_time',date("Y-m-d H:i:s"));
        $this->db->set('response',$response);
        $this->db->where(array('id'=>$insert_id));
        return $this->db->update($this->table_name());
    }
}

?>

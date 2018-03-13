<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Label_product_model extends MY_Model
{
	function __construct()
	{
		parent::__construct ();
	}

    public function table_name()
    {
        return 'label_product';
    }
    public function return_field()
    {
        return ["label_product.id","label_product.product_id","label_product.label","label_product.created_time"];
    }

    function get_product_by_labels($labels){
        $field = rtrim(join(",",$this->return_field()),",");

        $this->db->select($field.",product.product_name,product.price");
        $this->db->where_in('label',$labels);
        $this->db->from($this->table_name());
        $this->db->join('product', 'product.id = label_product.product_id', 'left');
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->result_array();
        return $res;
    }
    function get_info_by_box_id_and_msg_id($id,$msg_id){
        $field = rtrim(join(",",$this->return_field()),",");
        $where = array('box_id'=>$id,'msg_id'=>$msg_id);
        $this->db->select($field);
        $this->db->where($where);
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = NULL;
        if($query)
            $res = $query->row_array();
        return $res;
    }



    function update_box_status($data){
        //$this->db->set('user_id',$data["user_id"]);
        //$this->db->set('open_id',$data["open_id"]);
        if(isset($data["role"]))
            $this->db->set('role',$data["role"]);
        $this->db->set('status',$data["status"]);
        if(isset($data["msg_id"]))
            $this->db->set('msg_id',$data["msg_id"]);
        $this->db->set('last_update',$data["last_update"]);
        $this->db->where('box_id', $data['box_id']);
        return $this->db->update($this->table_name());
    }

    function insert_box_label($data){
        return $this->db->insert_batch($this->table_name(),$data);
    }

    function getProductByLabel($label){
        $label_product = $this->db->select('u.label,u.product_id,i.product_name')->from("label_product u")
            ->join('product i',"u.product_id=i.id",'left')
            ->where(array('u.label'=>$label))->get()->row_array();
        return $label_product;
    }

    function getLabelProduct($where){
        $label_product = $this->db->select('*')->from("label_product")->where($where)->get()->row_array();
        return $label_product;
    }
    //导入标签
    function exportLabels($data,$warehouse_id=0){
        /* $data = array(
            array('111'=>1),
            array('222'=>1),
            array('333'=>1),
            array('444'=>1),
            array('555'=>1)
        ); */
        $array_insert = array();
        $array_update = array();
        $array_false = array();
        $array_all = array();
        $array_succ = array();
        foreach ($data as $each){
            $param = array();
            $label = array_keys($each)[0];
            $product_id = array_values($each)[0];
            $where_label['label'] = $label;
            $array_all[] = (string)$label;
            $is_exist = $this->getLabelProduct($where_label);
            if ($is_exist){//update
                if ($is_exist['product_id'] == $product_id){//商品id相同不执行update操作了
                    $array_update[] = $label;
                    $array_succ[] = (string)$label;
                } else {
                    $param['product_id']  = $product_id;
                    $param['warehouse_id']  = $warehouse_id;
                    if(!$this->db->update('label_product', $param, array('id'=>$is_exist['id']))){
                        $array_false[] = $label;
                    }else{
                        $array_update[] = $label;
                        $array_succ[] = (string)$label;
                    }
                }
            } else {//add
                $param['label'] = trim($label);
                $param['warehouse_id']  = $warehouse_id;
                $param['product_id']  = $product_id;
                $param['created_time'] = time();
                $this->db->insert('label_product',$param);
                if($this->db->insert_id()>0){
                    $array_insert[] = $label;
                    $array_succ[] = (string)$label;
                }else{
                    $array_false[] = $label;
                }
            }
        }
        $log_data = array(
            'product_id'=>$product_id,
            'labels'=>json_encode($array_all),
            'succ_labels'=>json_encode($array_succ),
            'adminname'=>'cityboxapi',
            'created_time'=>time()
        );
        $this->db->insert('label_log',$log_data);
        return $this->db->insert_id();
        return array('insert'=>$array_insert,'update'=>$array_update,'false'=>$array_false);
    }
}

?>

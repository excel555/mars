<?php

class Label_product_admin_model extends MY_Model
{
    function __construct(){
        parent::__construct();
    }

    function getLabelProduct($where){
        $label_product = $this->db->select('*')->from("label_product")->where($where)->get()->row_array();
        return $label_product;
    }
    function getLabelByProductId($product_id){
        $label_product = $this->db->select('u.label,u.product_id,i.product_name')->from("label_product u")
            ->join('product i',"u.product_id=i.id",'left')
            ->where(array('u.product_id'=>$product_id))->get()->result_array();
//        echo $this->db->last_query();
        return $label_product;
    }

    function getProductByLabel($label){
        $label_product = $this->db->select('u.label,u.product_id,i.product_name,i.price,i.volume,i.unit')->from("label_product u")
            ->join('product i',"u.product_id=i.id",'left')
            ->where(array('u.label'=>$label))->get()->row_array();
        return $label_product;
    }

    function getLabels($field = "",$where = "",$offset = 0, $limit = 0)
    {
        $sql_fields = $field ? : "a.*,b.product_name as product_name";
    
        $sql = "SELECT {$sql_fields}
        FROM cb_label_product AS a
        LEFT JOIN cb_product as b on b.id = a.product_id
        WHERE 1 = 1 ";
        if ($where['label']){
            $sql .= " AND a.label like '%".$where['label']."%'";
        }
        if ($where['product_id']){
            $sql .= " AND a.product_id in(".$where['product_id'].") ";
        }
        if($where['platform_id']){
            $sql .= " AND b.platform_id=".$where['platform_id'] ;
        }

        $sql .= " ORDER BY a.id desc";

        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
    
        $res = $this->db->query($sql);
        if ($res){
            $array = $res->result_array();
            foreach ($array as $k=>$eachRes){
                $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
            }
        }
    
    
        return $array;
    }
    
    //导入标签
    function exportLabels($data){
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
            $label = array_keys($each)[0];
            $product_id = array_values($each)[0];
            $where_label['label'] = $label;
            $array_all[] = (string)$label;
            $is_exist = $this->getLabelProduct($where_label);
            if ($is_exist){//update
                if ($is_exist['product_id'] == $product_id){
                    $array_update[] = $label;
                    $array_succ[] = (string)$label;
                } else {
                    $param['product_id']  = $product_id;
                    if(!$this->db->update('label_product', $param, array('id'=>$is_exist['id']))){
                        $array_false[] = $label;
                    }else{
                        $array_update[] = $label;
                        $array_succ[] = (string)$label;
                    }
                }
               
            } else {//add
                $param['label'] = trim($label);
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
            'adminname'=>$this->operation_name,
            'created_time'=>time()
        );
        $this->db->insert('label_log',$log_data);
        return array('insert'=>$array_insert,'update'=>$array_update,'false'=>$array_false);
    }

    //获取商品第一次上架时间
    public function get_product_sale_time($product_id){
        $this->db->select('l.`created_time`');
        $this->db->from('cb_label_product p');
        $this->db->join('cb_equipment_label l', 'p.label = l.label');
        $this->db->where(array('p.product_id'=>$product_id));
        $this->db->order_by('l.id asc');
        $rs = $this->db->get()->row_array();
        return $rs['created_time']?date('Y-m-d H:i:s', strtotime($rs['created_time'])):'~';
    }


    /*
     * @desc 获取商品库存分布
     * @return array
     * */
    //select count(el.id) as num, el.equipment_id from cb_equipment_label  el join cb_label_product lp on el.label=lp.label where el.status='active' and lp.product_id=4 group by el.equipment_id
    public function get_product_store($product_id){
        $this->db->select('count(el.id) as num, el.equipment_id');
        $this->db->from('cb_equipment_label  el');
        $this->db->join('cb_label_product lp', 'el.label=lp.label');
        $this->db->where(array('el.status'=>'active', 'lp.product_id'=>$product_id));
        $this->db->group_by('el.equipment_id');
        $this->db->order_by('num asc');
        $rs = $this->db->get()->result_array();
        $eq_id_arr = array();
        $store_total = 0;
        foreach($rs as $k=>$v ){
            $eq_id_arr[] = $v['equipment_id'];
        }
        if(empty($eq_id_arr)){
            return array();
        }
        $this->load->model('equipment_model');
        $eq = $this->equipment_model->get_equipment_by_id($eq_id_arr);
        $result = $r_key = array();
        $i=0;
        foreach($rs as $key=>$val){
            if(!isset($eq[$val['equipment_id']]) || !$eq[$val['equipment_id']]){
                continue;
            }
            $store_total += $val['num'];
            $result[$i]['name'] = $eq[$val['equipment_id']];
            $result[$i]['value']= $val['num'];
            $r_key[$i]          = $eq[$val['equipment_id']];
            $i++;
        }
        return array('key' => $r_key, 'value' => $result, 'store_total'=>$store_total);
    }

    /*
     * @desc 获取商品库存分布
     * */
    public function get_product_store_v2($product_id){
        //select es.* from `cb_equipment_stock` es join `cb_equipment` e on es.`equipment_id`=e.`equipment_id` where e.`platform_id`=1 and es.`product_id`=2
        $this->db->select("es.stock,e.name ");
        $this->db->from('cb_equipment_stock es');
        $this->db->join('cb_equipment e', 'es.equipment_id=e.equipment_id');
        $this->db->where(array('e.platform_id'=>$this->platform_id, 'es.product_id'=>$product_id));
        $this->db->order_by('es.stock asc');
        $rs = $this->db->get()->result_array();
        $result = $r_key = array();
        $i = $store_total = 0;
        foreach($rs as $key=>$val){
            $store_total += $val['stock'];
            $result[$i]['name'] = $val['name'];
            $result[$i]['value']= $val['stock'];
            $r_key[$i]          = $val['name'];
            $i++;
        }
        return array('key' => $r_key, 'value' => $result, 'store_total'=>$store_total);
    }

}
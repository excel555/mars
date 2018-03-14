<?php if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Product_admin_model extends CI_Model
{
    function __construct() {
        parent::__construct();
        $this->table = 'product';
    }
    
    function getList($where = array(),$limit = array()){
        $this->db->where($where);
        if (!empty($limit)) {
            $this->db->limit($limit['per_page'], $limit['curr_page']);
        }
        $this->db->select("*");
        $this->db->from($this->table);
        $this->db->order_by('id', 'asc');
        $query = $this->db->get();
        $res = $query->result_array();
        return $res;
    }
    
    function getProducts($field = "",$where = "",$offset = 0, $limit = 0,$order='',$sort='',$is_stock = 0)
    {
        $sql_fields = $field ? : "a.*,b.name as class_name";
        if ($is_stock == 1){
            $sql_fields .= ",(SELECT SUM(stock) FROM cb_equipment_stock s1 left join cb_equipment s2 on s2.equipment_id = s1.equipment_id WHERE s1.product_id = a.id and s2.platform_id=".$this->platform_id.") AS sum_stock";
            $sql_fields .= ',(SELECT GROUP_CONCAT(serial_number) FROM cb_product_serial_num WHERE product_id = a.id and type = 1) AS serial_num'; 
        }
       $sql = "SELECT {$sql_fields}
        FROM cb_product AS a 
        LEFT JOIN cb_product_class as b on b.id = a.class_id 
        WHERE 1 = 1 and a.status = 1 ";

        if ($where['name']){
            $sql.= " and a.product_name like '%".$where['name']."%'";
        }
        if ($where['class_id']){
            $sql.= " and a.class_id = '".$where['class_id']."'";
        }
        if ($where['tags']){
            $sql.= " and a.tags like '%".$where['tags']."%'";
        }
        if ($where['id']){
            $sql.= " and a.id = ".$where['id'];
        }
        if ($where['is_paper_order'] || $where['is_paper_order'] === 0){
            if ($where['is_paper_order'] == 1){
                $sql.= " and a.inner_code = ''";
            } else {
                $sql.= " and a.inner_code <> ''";
            }
        }

        $sql .= " and a.platform_id = ".$this->platform_id;
        if ($sort){
            if ($sort == 'created_date'){
                $sort = 'created_time';
            }
            $sql .= " order by ".$sort.' '.$order;
        } else {
            $sql .= " order by a.id desc";
        }
    
        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }

        $res = $this->db->query($sql);
        if ($res){
            $array = $res->result_array();
            foreach ($array as $k=>$eachRes){
                if ($eachRes['inner_code'] != ''){
                    $array[$k]['is_paper_order'] = '否';
                } else {
                    $array[$k]['is_paper_order'] = '是';
                }
                $array[$k]['created_date'] = $array[$k]['created_time'] == 0 ? '~' : date('Y-m-d',$array[$k]['created_time']);
                $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
                if ($eachRes['img_url'] != ''){
                    $path = base_url();
                    $array[$k]['product_img'] = "<img src='{$path}".$eachRes['img_url']."' width='50' height='50' />";
                } else {
                    $array[$k]['product_img'] = '无';
                }
                $array[$k]['show_id'] = "<a href='/products/edit/".$eachRes['id']."'>".$eachRes['id']."</a>";
            } 
        }
        return $array;
    }
    
    function getProductsByPlatform($field = "",$where = "",$offset = 0, $limit = 0,$platforms = '')
    {
        //$where = json_decode($where);
        $sql_fields = $field ? : "a.*,b.name as class_name";
        
        $sql = "SELECT {$sql_fields}
        FROM cb_product AS a
        LEFT JOIN cb_product_class as b on b.id = a.class_id
        WHERE 1 = 1 and a.status = 1 ";
        if ($where['product_name']){
            $sql.= " and a.product_name like '%".$where['product_name']."%'";
        }
        if ($where['class_id']){
            $sql.= " and a.class_id = '".$where['class_id']."'";
        }
        if ($where['tag']){
            $sql.= " and a.tags like '%".$where['tag']."%'";
        }
        if ($where['id']){
            $sql.= " and a.id = ".$where['id'];
        }
        if ($where['is_paper_order'] || $where['is_paper_order'] === 0){
            if ($where['is_paper_order'] == 1){
                $sql.= " and a.inner_code = ''";
            } else {
                $sql.= " and a.inner_code <> ''";
            }
        }
        if ($where['platform_id']){
            $sql .= " and a.platform_id = ".$where['platform_id'];
        }
    
        $sql .= " order by created_time desc";
    
        if ($limit > 0) {
            $sql .= " LIMIT {$offset},{$limit}";
        }
    
        $res = $this->db->query($sql);
        if ($res){
            $platforms = json_decode($platforms);
            $array = $res->result_array();
            foreach ($array as $k=>$eachRes){
                /* if ($eachRes['inner_code'] != ''){
                 $array[$k]['is_paper_order'] = '否';
                 } else {
                 $array[$k]['is_paper_order'] = '是';
                 } */
                $array[$k]['created_date'] = $array[$k]['created_time'] == 0 ? '~' : date('Y-m-d',$array[$k]['created_time']);
                $array[$k]['created_time'] = date('Y-m-d H:i:s',$array[$k]['created_time']);
                if ($eachRes['img_url'] != ''){
                    $array[$k]['product_img'] = "<img src='http://fdaycdn.fruitday.com/".$eachRes['img_url']."' width='50' height='50' />";
                } else {
                    $array[$k]['product_img'] = '无';
                }
                $array[$k]['platform_name'] = $platforms->$eachRes['platform_id'] ? : '' ;
            }
        }
    
    
        return $array;
    }
    
    public function getProduct($field,$where){
        if(!isset($where['platform_id'])){
            $where['platform_id'] = $this->platform_id;
        }
        return $this->db->select($field)->from('product')->where($where)->get()->row_array();
    }
    
    public function getProductWithClass($id){
        $sql = "SELECT a.*,b.name as class_name,b.id as class_id
        FROM cb_product AS a
        LEFT JOIN cb_product_class as b on b.id = a.class_id
        WHERE a.status = 1 and a.id = ".$id." and a.platform_id =".$this->platform_id;
        $res = $this->db->query($sql);
        $result = $res->row_array();
        return $result;
    }
    
    public function findById($id){
        $rs = $this->db->select("*")->from('product')->where(array(
            'id'=>$id
        ))->get()->row_array();
        return $rs;
    }

    //获取商品信息
    public function getProductByClass($class_arr, $search_product_name='', $field=''){
        $this->db->select('*');
        $this->db->from('product');
        if($search_product_name){
            $this->db->like('product_name', $search_product_name);
        }
        if(!empty($class_arr)){
            $this->db->where_in('class_id', $class_arr);
        }
        $this->db->where(array('platform_id'=>$this->platform_id));
        $rs = $this->db->get()->result_array();
        if(!$field){
            return $rs;
        }
        $result = array();
        foreach($rs as $k=>$v){
            $result[$k] = $v[$field];
        }
        return $result;

    }
    
    function product_update_where_in($data,$where="",$where_in=""){
        if($where_in)
            $this->db->where_in('id',$where_in);
        if($where)
            $this->db->where($where);
        return $this->db->update('product',$data);
    }

    //验证69码唯一性
    function check_is_unique_serial_number($where,$where_in=''){
        $this->db->from('product_serial_num');
        if($where){
            $this->db->where($where);
        }
        if ($where_in){
            $this->db->where_in('serial_number',$where_in);
        }
        $rs = $this->db->get()->result_array();
        return $rs?false:true;
    }

    //批量新增69码
    function add_product_serial_nums($arr){
        return $this->db->insert_batch('product_serial_num',$arr);
    }

    //查找69码
    function get_product_serial_nums($where){
        return $this->db->from('product_serial_num')->where($where)->get()->result_array();
    }

    //验证自定义货号唯一性
    function check_is_unique_serial_num($where){
        $rs = $this->db->from('product')->where($where)->get()->row_array();
        return $rs?false:true;
    }

    //删除 之前存的货号
    function delete_product_serial_nums($where){
        $this->db->delete('product_serial_num',$where);
    }

    /*
     * @desc  获取商品的详情
     * @param $product_ids array
     * */
    function get_product_list($product_ids){
        if(empty($product_ids)){
            return false;
        }
        $this->load->model('product_class_model');
        $product_class = $this->product_class_model->get_children_class();
        $this->db->from('product');
        $this->db->where_in('id', $product_ids);
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['id']]['product_name'] = $v['product_name'];
            $result[$v['id']]['price']        = $v['price'];
            $result[$v['id']]['class_name']   = $product_class[$v['class_id']]['name'];
            $result[$v['id']]['class_parent'] = $product_class[$v['class_id']]['parent'];
            $result[$v['id']]['purchase_price']= $v['purchase_price'];
        }
        return $result;
    }

    public function gen_product_unique_code()
    {
        while (true){
            $code =  'U'. date('ymd') . mt_rand(1000, 9999);
            $rs = $this->db->get_where('product_serial_num', ['serial_number' => $code])->row();
            if(empty($rs)){
                return $code;
            }
        }
    }

    public function get_product_name(){
        $this->db->from('product');
        $this->db->where(array('platform_id'=>$this->platform_id));
        $rs = $this->db->get()->result_array();
        $result = array();
        foreach($rs as $k=>$v){
            $result[$v['id']] = $v['product_name'];
        }
        return $result;
    }
}

?>

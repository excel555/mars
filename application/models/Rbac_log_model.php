<?php
/**
 * Created by PhpStorm.
 * User: liangsijun
 * Date: 16/6/1
 * Time: 上午11:31
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

class Rbac_log_model extends MY_Model {
    public function table_name() {
        return 'rbac_log';
    }

    /**
     * @param        $module  模块id 商品模块:1
     * @param        $index   相关id
     * @param        $operation 操作 c:创建 u:更新 d:删除
     * @param        $column  所更新的数据库字段
     * @param        $after 更新后的值
     * @param string $before 更新前的值
     * @param int    $parent 父id 如:sku父id为商品id
     */
    public function write_log($module, $index, $operation, $column, $after, $before = '', $parent = 0) {
        $operationRef = array('c'=>1 , 'u'=>2 , 'd'=>3);
        return $this->insert(array('module' => $module,
                            'index'=> $index,
                            'parent_id'=>$parent,
                            'operation'=>$operationRef[$operation],
                            'column'=>$column,
                            'before'=>$before,
                            'after'=>$after,
                            'operater_id'=>$this->session->userdata['sess_admin_data']['adminid'],
                            'operater_name'=>$this->session->userdata['sess_admin_data']['adminname'],
                            'create_time'=>date('Y-m-d H:i:s',time())

            )
        );

    }


    public function saveRedisStock($ppid, $use_store, $stock) {
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        if ($use_store == 0) {
            $stock = '';
        } elseif (!is_numeric($stock)) {
            $stock = 0;
        }
        $sku_info = array("use_store" => $use_store, "stock" => $stock);
        $redis->hMset("product:" . $ppid, $sku_info);
    }

    function getRedisProductStock($sku_id) {
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        if ($redis->exists("product:" . $sku_id)) {
            $sku_info = $redis->hMget("product:" . $sku_id, array("use_store", "stock"));
        } else {
            $sku_info = $this->initRedisStock($sku_id);
        }
        return $sku_info;
    }

    public function initRedisStock($sku_id) {
        $this->load->library('phpredis');
        $redis = $this->phpredis->getConn();
        $this->db->select('product_id,stock');
        $this->db->from('product_price');
        $this->db->where('id', $sku_id);
        $price_result = $this->db->get()->row_array();

        $stock = $price_result['stock'];
        $this->db->select('use_store');
        $this->db->from('product');
        $this->db->where('id', $price_result['product_id']);
        $product_result = $this->db->get()->row_array();
        $use_store = $product_result['use_store'];
        if ($use_store == 0) {
            $stock = '';
        } elseif (!is_numeric($stock)) {
            $stock = 0;
        }
        $sku_info = array("use_store" => $use_store, "stock" => $stock);
        $redis->hMset("product:" . $sku_id, $sku_info);
        return $sku_info;
    }

    public function getItems($cols = '*', $filter = array()) {
        $this->db->select($cols);
        $this->_filter($filter);
        $this->db->from('product')
            ->join('product_price', 'product.id=product_price.product_id', 'left')
            ->where(array('product_no !=' => ''))
            ->order_by('product.order_id desc ,product.id desc');
        $list = $this->db->get()->result_array();
        return $list ? $list : array();
    }

    /**
     * where key in ()...
     *
     * @param       $key
     * @param array $value
     */
    public function getAll($key, $value) {
        $this->db->where_in($key, $value);
        $this->db->select("*");
        $this->db->from($this->table_name());
        $query = $this->db->get();
        $res = $query->result_array();
        return $res;
    }

    public function getprice($product_no) {
        $sql = "select price from ttgy_erp_products where code='" . $product_no . "' limit 1";
        $res = $this->db->query($sql)->row_array();
        return $res;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/9/11
 * Time: 上午10:05
 */
class Equipment_stock_model extends MY_Model
{

    function __construct()
    {
        parent::__construct ();
    }

    public function table_name()
    {
        return 'equipment_stock';
    }

    /*
     * @desc 更新商品库存
     * @param  $data array(
     *                   0=>array('product_id'=>xxx, 'qty' => 1),
     *                   1=>array('product_id'=>xxx, 'qty' => 1)
     *               )
     * @param $equipment_id 盒子编码
     * */
    public function update_stock($data, $equipment_id){
        foreach($data as $k=>$v){
            $rs = $this->get_stock($v['product_id'], $equipment_id);
            if( intval($rs['stock']) > 0 ){
                $new_stock = $rs['stock']-$v['qty'];
                $new_stock = $new_stock>0?$new_stock:0;
                $rs_p = $this->db->update($this->table_name(), array('stock'=>$new_stock), array('product_id'=>$v['product_id'], 'equipment_id'=>$equipment_id));
                if(!$rs_p){
                    write_log('更新库存失败：'.$data['product_id'].'--'.$equipment_id,'info');
                }
            }
        }
        return true;
    }

    /*
     * @desc 获取商品库存
     * @param $product_id 商品id
     * @param $equipment_id 盒子编码
     * */
    public function get_stock($product_id, $equipment_id){
        $this->db->from($this->table_name());
        $this->db->where(array('product_id' => $product_id, 'equipment_id' => $equipment_id));
        return $this->db->get()->row_array();
    }

    /**
     * @param $deliver_no
     * 非RFID设备 关门时增加库存
     */
    public function update_stock_by_deliver($deliver_no){
        $this->load->model("deliver_model");
        $d = $this->deliver_model->get_deliver_by_no($deliver_no);
        $equipment_id = $d['equipment_id'];
        foreach ($d['goods'] as $v){
            if($v['type'] == 1){
                //上架
                $rs = $this->get_stock($v['product_id'], $equipment_id);
                if($rs){
                    $new_stock = $rs['stock'] + $v['qty'];
                    $this->db->update($this->table_name(), array('stock'=>$new_stock), array('product_id'=>$v['product_id'], 'equipment_id'=>$equipment_id));
                }else{
                    //插入
                    $data = array(
                        'product_id'=>$v['product_id'],
                        'equipment_id'=>$equipment_id,
                        'stock'=>$v['qty']
                    );
                    $this->db->insert($this->table_name(),$data);
                }
            }
        }
    }

    public function get_eq_products($equipment_id){
        $this->db->from($this->table_name());
        $this->db->where(array('equipment_id' => $equipment_id));
        return $this->db->get()->result_array();
    }


    /**
     * 手动修正库存
     */
    public function update_stock_by_handle($equipment_id,$goods){
        $ret = 0;
        foreach ($goods as $v) {
            $ret += $v['qty'];
            $rs = $this->get_stock($v['product_id'], $equipment_id);
            if ($rs) {
                $new_stock = $v['qty'];
                $this->db->update($this->table_name(), array('stock' => $new_stock), array('product_id' => $v['product_id'], 'equipment_id' => $equipment_id));
            } else {
                //插入
                $data = array(
                    'product_id' => $v['product_id'],
                    'equipment_id' => $equipment_id,
                    'stock' => $v['qty']
                );
                $this->db->insert($this->table_name(), $data);
            }
        }
        return $ret;
    }

}
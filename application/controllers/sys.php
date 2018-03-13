<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Sys extends MY_Controller
{
    public $workgroup = 'sys';

    function __construct() {
        parent::__construct();
        $this->load->model("sys_product_model");
    }

    public function change_new(){
        $this->page('sys/change_new.html');
    }
    public function change_bind(){
        $this->page('sys/sys_bind.html');
    }

    public function check_label(){
        $this->page('sys/check_old_label.html');
    }


    public function goods_list_table(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $notbind = $gets['notbind'];
        $where = array();
        if($notbind == 1){
            $where['u.new_product']="0";
        }
        $search_mobile = $gets['search_mobile'];

        if(isset($search_mobile)){
            $where['u.old_product_name like'] = '%'.$search_mobile.'%';
        }

        $result = $this->sys_product_model->get_list($where,$limit,$offset,$order,$sort);

        echo json_encode($result);
    }

    public function label_goods_list_table(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50000;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $labels = trim($gets['search_label']);
        $labels = rtrim($labels,',');
        if(empty($labels)){
            $rs_lable = array(
                'total' => 0,
                'rows' => array()
            );
            echo json_encode($rs_lable);
            die;
        }
        $labels = str_replace( " ", "",$labels);
        $labels = explode(",",$labels);
        $labels = array_unique($labels);

        $this->db->select("*");
        $this->db->from('rfid_product');
        $this->db->where_in("rfid",$labels);

        $rs_lable = $this->db->get()->result_array();;
//        var_dump($rs_lable);
        $rs_lable_ret = array();
        foreach($rs_lable as $lb){
            $lb['productid'] = $lb['product_id'];
            $rs_lable_ret[$lb['rfid']] = $lb;
        }

        $where = array();
        $result = $this->sys_product_model->get_list($where,$limit,$offset,$order,$sort);
        $result_product = array();

        foreach ($result['rows'] as $v){
            $result_product[$v['old_product']] = $v;
        }
        $labels_ret = array();
        $i=1;
        if(count($labels)>0){
            foreach ($labels as $value){
                if(isset($rs_lable_ret[$value])){
                    $data['label'] = $value;
                    $data['id'] = $i++;
                    $data['rfid'] = $rs_lable_ret[$value]['rfid'];
                    $data['new_product_id'] = $result_product[$rs_lable_ret[$value]['productid']]['new_product'];
                    $data['old_product'] = $result_product[$rs_lable_ret[$value]['productid']]['old_product_name'] ."[id=".$rs_lable_ret[$value]['productid']."]";
                    $data['new_product'] = $result_product[$rs_lable_ret[$value]['productid']]['product_name'];
                    $labels_ret[] = $data;
                }else{
                    $data['label'] = $value;
                    $data['id'] = $i++;
                    $data['rfid'] = '';
                    $data['new_product_id'] = '';
                    $data['old_product'] = '';
                    $data['new_product'] ='';
                    $labels_ret[] = $data;
                }

            }
        }
        $rs_lable = array(
            'total' => count($labels_ret),
            'rows' => $labels_ret
        );
        echo json_encode($rs_lable);
    }


    public function old_label_goods_list_table(){
        $gets = $this->input->get();
        $limit = $gets['limit']?$gets['limit']:50000;
        $offset = $gets['offset']?$gets['offset']:0;
        $sort = $gets['sort'];
        $order = $gets['order'];
        $labels = trim($gets['search_label']);
        $labels = rtrim($labels,',');
        if(empty($labels)){
            $rs_lable = array(
                'total' => 0,
                'rows' => array()
            );
            echo json_encode($rs_lable);
            die;
        }
        $labels = explode(",",$labels);
        $labels = array_unique($labels);
        $ant_master = $this->load->database('ant_master', TRUE);
        $ant_master->select("*");
        $ant_master->from('product2box');
        $ant_master->where_in("rfid",$labels);

        $rs_lable = $ant_master->get()->result_array();;

        $rs_lable = array(
            'total' => count($rs_lable),
            'rows' => $rs_lable
        );
        echo json_encode($rs_lable);
    }

    public function ajax_bind_product(){
        $old_id = (int)$this->input->post("old_id");
        $new_id = (int)$this->input->post("new_id");
        if($old_id<=0 || $new_id<=0){
            echo "";die;
        }
        $result = $this->sys_product_model->update_bind($old_id,$new_id);
        echo $result;
    }


    public function bind_lable_product(){
        $label_product = $this->input->post('label_product');
        $array_ret = array();
        $error = "";
        if(!empty($label_product)){
            $label_product = json_decode($label_product,TRUE);
            foreach ($label_product as $lp){
                if(empty($lp['label']) || empty($lp['new_product_id'])){
                    $error .='存在标签'.$lp['label'].'没有绑定新商品,';
                }else{
                    $data = array();
                    $data[$lp['label']]=$lp['new_product_id'];
                    $array_ret[] = $data;
                }
            }
            if(!empty($error)){
                die(json_encode(array('status'=>'fail','msg'=>$error)));
            }
            $this->load->model('label_product_model');
            $rs =  $this->label_product_model->exportLabels($array_ret);
            $rs_ret = array();
            if(count($rs['false'])>0){
                foreach ($rs['false'] as $l){
                    $rs_ret[$l] = '绑定失败';
                }
            }

            if(count($rs['insert'])>0){
                foreach ($rs['insert'] as $l2){
                    $rs_ret[$l2] = '绑定成功-插入';
                }
            }
            if(count($rs['update'])>0){
                foreach ($rs['update'] as $l1){
                    $rs_ret[$l1] = '绑定成功-更新';
                }
            }

            die(json_encode(array('status'=>'succ','result'=>$rs_ret)));
        }
        echo '';
    }

}
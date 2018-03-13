<?php

defined('BASEPATH') OR exit('No direct script access allowed');

// This can be removed if you use __autoload() in config.php OR use Modular Extensions
/** @noinspection PhpIncludeInspection */
require APPPATH . '/libraries/REST_Controller.php';

// use namespace
use Restserver\Libraries\REST_Controller;

/**
 * Client Controller
 * 第三方设备对接【一体机、手持pad】
 */
class Client extends REST_Controller {

    function __construct()
    {
        parent::__construct();
        $this->load->model("user_model");
    }

    /**
     * 绑定标签检测用户信息
     */
    function bind_user_check_post(){
        $token = $this->post("token");
        $this->check_null_and_send_error($token,"token 不能为空");
        $user = explode(":",$token);
        write_log('bind_user_check_post'.var_export($user,1));
        if(count($user) == 2){
            $key = "bind_user_token_".$user[0];
            $t = $this->cache->redis->get($key);
            write_log('bind_user_check_post - cache :'.$key.'->'.var_export($t,1));
            if($token == $t){
                $this->send_ok_response("succ");
            }
        }
        $this->send_error_response("你没有权限操作");
    }


    function label_product_table_get()
    {
        $labels = $this->get('labels') ?: "";;
        $this->label_product_table_action($labels);
    }

    function label_product_table_post()
    {
        $labels = $this->post('labels') ?: "";;
        $this->label_product_table_action($labels);
    }

    private function label_product_table_action($labels)
    {
        $this->load->model("label_product_model");
        $labels = ltrim($labels, '[');
        $labels = rtrim($labels, ']');
        if (!empty($labels)) {
            $arr = explode(',', $labels);
            foreach ($arr as $v) {
                $rs = $this->label_product_model->getProductByLabel(trim($v));
                if (!$rs)
                    $rs = array('product_name' => '', 'product_id' => '', 'label' => trim($v));
                $array[] = $rs;
            }
        } else {
            $array[] = array('product_name' => '没有识别到标签', 'product_id' => '没有识别到标签', 'label' => 'xxx');
        }

        $result = array(
            'total' => count($array),
            'rows' => array_values($array),
        );
        $this->send_ok_response($result);
    }


    public function bind_lable_product_get(){
        $label_product = $this->input->get('label_product');
        $warehose = $this->input->get('warehose');
        $this->bind_lable_product_action($label_product,$warehose);
    }
    public function bind_lable_product_post(){
        $label_product = $this->input->post('label_product');
        $warehose = $this->input->post('warehose');
        $this->bind_lable_product_action($label_product,$warehose);
    }

    private function bind_lable_product_action($label_product,$warehose){
        $array_ret = array();
        $error = "";
        if(!empty($label_product)){
            $label_product = json_decode($label_product,TRUE);
            foreach ($label_product as $lp){
                if(empty($lp['label'])){
                    $error .='存在标签'.$lp['label'].'没有绑定新商品,';
                }else{
                    $data = array();
                    if(intval($lp['new_product_id']>0)){
                        $data[$lp['label']]=$lp['new_product_id'];
                        $array_ret[] = $data;
                    }else if(intval($lp['product_id'])>0){
                        $data[$lp['label']]=$lp['product_id'];
                        $array_ret[] = $data;
                    }
                }
            }
            $this->load->model('label_product_model');
            $rs =  $this->label_product_model->exportLabels($array_ret,$warehose);
            $this->send_ok_response((array('total'=>'0','rows'=>array())));
        }
    }

    public function ajax_search_proudct_by_name_get(){
        $name = $this->input->get('name');
        $platform_id = $this->input->get('platform') ?: 1 ;
        $sql = "SELECT id,product_name,price FROM cb_product WHERE product_name like '%$name%' and status = 1 and platform_id =".$platform_id;
        $info = $this->db->query($sql)->result_array();
        if($info){
            $this->send_ok_response($info);
        }
    }

    public function ajax_warehouse_get(){
        $platform_id = $this->input->get('platform') ?: 1 ;
        $sql = "SELECT id,name FROM cb_warehouse WHERE platform_id =".$platform_id;
        $info = $this->db->query($sql)->result_array();
        if($info){
            $this->send_ok_response($info);
        }
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/11/28
 * Time: 下午6:39
 * @desc 在线充值
 */

class Recharge_online_model extends MY_Model
{
    const STATUS_DEFULT = 0;//待充值 充值状态 0:待充值1:充值中  2:充值成功 3:充值失败
    const STATUS_DOING  = 1;//充值中
    const STATUS_SUCCESS= 2;//成功
    const STATUS_ERROR  = 3;//失败

    //余额类型 0：消费，1：赠送 2：在线充值， 3:退款，4:卡券充值
    const YUE_TYPE_RE = 2;//在线充值
    const YUE_TYPE_ZENG = 1;//赠送

    //赠送的金额配置
    public $money_config = array(
        30 => array(
            'money' => 30,
            'gift'  => 5
        ),
        50 => array(
            'money' => 50,
            'gift'  => 10
        ),
        100 => array(
            'money' => 100,
            'gift'  => 25
        ),
        500 => array(
            'money' => 500,
            'gift'  => 100
        )
    );
    function __construct()
    {
        parent::__construct();
        $this->load->model('user_acount_model');
    }




    public function table_name()
    {
        return 'recharge_online';
    }

    /*
     * @desc  返回充值配置
     * @return array
     * */
    public function get_online_config(){
        return $this->money_config;
    }

    /*
     * @desc 创建在线充值
     * @param $acount_id int  账户id
     * @param $money int 充值金额
     *
     * */
    public function create_recharge($acount_id, $money, $refer, $uid=0){
        $acount_info = $this->user_acount_model->get_acount_info($acount_id);
        if(!$acount_info){
            write_log("recharge online: ".var_export($acount_info,true));
            return array('status'=>'error', 'msg'=>'用户信息异常');
        }
        $order_name = $this->get_order_name();
        $money_info = $this->money_config[$money];
        if(empty($money_info)){
            write_log("recharge online: ".var_export($money_info,true));
            return array('status'=>'error', 'msg'=>'充值金额异常');
        }
        $param['money']        = $money;
        $param['yue']          = bcadd($money_info['money'], $money_info['gift'], 2);
        $param['order_name']   = $order_name;
        $param['create_time']  = date('Y-m-d H:i:s');
        $param['acount_id']    = $acount_id;
        $param['uid']          = $uid;
        $param['refer']        = $refer;
        $param['status']       = self::STATUS_DEFULT;

        if($this->db->insert('recharge_online', $param)){
            return array('status'=>'success', 'param'=>$param);
        }
        return array('status'=>'error', 'msg'=>'充值失败，系统繁忙');
    }

    /*
     * @desc 充值成功回调
     * @param int $acount_id  账户id
     * @param str $order_name 订单
     * @param str $trade_no  第三方流水
     * */
    public function success_recharge( $order_name, $trade_no){
        $order_info = $this->get_info_by_order_name($order_name);
        if(!$order_info){
            write_log("recharge online: trade_no=".$trade_no.",order_name =".var_export($order_name,true));
            return array('status'=>'error', 'msg'=>'订单信息异常');
        }
        $acount_id = $order_info['acount_id'];
        $acount_info = $this->user_acount_model->get_acount_info($acount_id);
        if(!$acount_info){
            write_log("recharge online: ".var_export($acount_info,true));
            return array('status'=>'error', 'msg'=>'用户信息异常');
        }

        //更新充值表
        $online['status']      = self::STATUS_SUCCESS;
        $online['update_time'] = date('Y-m-d H:i:s');
        $online['trade_no']    = $trade_no;

        //更新用户余额
        $acount['yue']         = bcadd($acount_info['yue'], $order_info['yue'], 2);
        $acount['update_time'] = date('Y-m-d H:i:s');
        $refer = '';
        if($order_info['refer'] == 'wechat'){
            $refer = '微信';
        }elseif($order_info['refer'] == 'alipay'){
            $refer = '支付宝';
        }
        //insert 余额记录
        $acount_yue[0]['uid']      = $order_info['uid'];
        $acount_yue[0]['acount_id']= $order_info['acount_id'];
        $acount_yue[0]['des']      = $refer.'充值';
        $acount_yue[0]['yue']      = $order_info['money'];
        $acount_yue[0]['add_time'] = date('Y-m-d H:i:s');
        $acount_yue[0]['yue_type'] = self::YUE_TYPE_RE;//充值

        //赠送余额
        $zengsong_yue = bcsub($order_info['yue'], $order_info['money'], 2);
        if($zengsong_yue>0){
            $acount_yue[1]['uid']      = $order_info['uid'];
            $acount_yue[1]['acount_id']= $order_info['acount_id'];
            $acount_yue[1]['des']      = '充值赠送';
            $acount_yue[1]['yue']      = $zengsong_yue;
            $acount_yue[1]['add_time'] = date('Y-m-d H:i:s');
            $acount_yue[1]['yue_type'] = self::YUE_TYPE_ZENG;//赠送
        }

        $this->db->trans_begin();
        $this->db->update($this->table_name(), $online, array('order_name'=>$order_name));
        $this->db->update('user_acount', $acount, array('id'=>$acount_id));
        $this->db->insert_batch('user_acount_yue', $acount_yue);
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('status'=>'error', 'msg'=>'充值失败，系统繁忙');
        } else {
            $this->db->trans_commit();
            return array('status'=>'success');
        }

    }


    /*
     * @desc 更新订单状态, 发起订单需调用1， 支付失败调用3
     * @param str $order_name 订单编号
     * @param int $status  订单状态 1:充值中，2:成功  3:失败
     * @return array
     * */
    public function update_recharge_status($order_name, $status){
        $online['status']      = $status;
        if($this->db->update($this->table_name(), $online, array('order_name'=>$order_name))){
            return array('status'=>'success');
        };
        return array('status'=>'error', 'msg'=>'更新失败');

    }

    /*
     * @desc 获取订单信息
     * @param str $order_name 订单编号
     * */
    public function get_info_by_order_name($order_name){
        $this->db->from($this->table_name());
        $this->db->where('order_name', $order_name);
        return $this->db->get()->row_array();
    }


    //生成订单号
    public function get_order_name()
    {
        $order_name = "M".date("ymdi") . $this->rand_code(6);
        $res = $this->get_info_by_order_name($order_name);
        if ($res) {
            return $this->get_order_name();
        }
        return $order_name;
    }

    function rand_code($length=6) {
        $code="";
        for($i=0;$i<$length;$i++) {
            $code .= mt_rand(0,9);
        }
        return $code;
    }
    function get_list($acount_id){
        $this->db->from($this->table_name());
        $this->db->where(array('acount_id'=>$acount_id));
        return  $this->db->get()->result_array();
    }

}
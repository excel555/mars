<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/11/28
 * Time: 下午4:41
 * @desc 充值卡使用model
 */

class Recharge_cards_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
        $this->load->model('user_acount_model');
    }
    //余额类型 0：消费，1：赠送 2：在线充值， 3:退款，4:卡券充值 5：提现，6:冻结
    const YUE_TYPE_CARD = 4;//卡券充值


    public function table_name()
    {
        return 'recharge_cards';
    }

    /*
     * @desc 使用充值卡
     * @param $format_pass str 用户输入卡号密码
     * @param int $acount_id 账户id 不是用户id
     * @return    array(
     *               'status'=>'success',
     *               'msg' =>'充值成功',
     *               'yue'=>$user_yue    //账户总余额
     *            );
     * */
    public function recharge_card($format_pass, $acount_id, $uid=0){
        $check_times = $this->get_acount_error_times($acount_id);//查看用户输错次数
        if($check_times>10){
            return array('status'=>'error', 'msg'=>'您今天输错次数太多，请明天再来');
        }
        $format_pass = str_replace(' ', '' , $format_pass);
        $format_pass = htmlspecialchars($format_pass);
        $format_pass = addslashes($format_pass);
        $format_pass = strtolower($format_pass);
        $password    = $this->md5Pass($format_pass);
        if($this->get_card_pass($password)){//从cache里面判断 防止数据库主从延迟
            return array('status'=>'error', 'msg'=>'该充值卡已经被使用，请勿重复操作!');
        }

        $this->db->from('recharge_cards');
        $this->db->where(array('card_pass' => $password));
        $card_info = $this->db->get()->row_array();
        if(!$card_info || $password!=$card_info['card_pass']){
            $this->update_acount_error_times($acount_id);//记录错误次数
            return array('status'=>'error', 'msg'=>'卡号密码错误，请重新输入');
        }
        if($card_info['is_used'] == 1){
            return array('status'=>'error', 'msg'=>'该充值卡已经被使用，请勿重复操作');
        }
        if($card_info['to_date']<date('Y-m-d')){
            return array('status'=>'error', 'msg'=>'该充值卡已经过期');
        }
        $acount_info = $this->user_acount_model->get_acount_info($acount_id);
        write_log("recharge cards:acount_id: ".$acount_id);
        if(!$acount_info){
            return array('status'=>'error', 'msg'=>'用户非法，充值失败，请联系客服');
        }
        $now_time = date('Y-m-d H:i:s');
        //更新充值卡
        $card_param['is_used']  = 1;
        $card_param['content']  = $now_time.'由账户id'.$acount_id.'充值使用掉';
        $card_param['acount_id']= $acount_id;
        $card_param['used_time']= $now_time;

        $user_yue = bcadd(floatval($acount_info['yue']), $card_info['card_money'], 2);
        //更新用户余额
        $acount['yue']          = $user_yue;
        $acount['update_time']  = $now_time;

        //新增余额记录
        $acount_yue['uid']      = $uid;
        $acount_yue['acount_id']= $acount_id;
        $acount_yue['des']      = '卡券充值（卡号：'.$card_info['card_number'].'）';
        $acount_yue['yue']      = $card_info['card_money'];
        $acount_yue['add_time'] = date('Y-m-d H:i:s');
        $acount_yue['yue_type'] = self::YUE_TYPE_CARD;//卡券充值

        $this->db->trans_begin();

        $this->db->update('recharge_cards', $card_param, array('id'=>$card_info['id']));
        $this->db->update('user_acount', $acount, array('id'=>$acount_id));
        $this->db->insert('user_acount_yue', $acount_yue);

        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return array('status'=>'error', 'msg'=>'充值失败，系统繁忙');
        } else {
            $this->db->trans_commit();
            $this->update_card_pass($password);
            return array('status'=>'success', 'msg' =>'充值成功', 'yue'=>$user_yue);
        }

    }


    private function md5Pass($password) {
        if(strlen($password) == 16) {
            return md5(md5(substr($password,0,4)).md5(substr($password,4,4)).md5(substr($password,8,4)).md5(substr($password,12,16)));
        }
    }


    /*
     * @desc 获取 用户输错次数， 反刷
     * @param $acount_id int 账户id
     * */
    public function get_acount_error_times($acount_id){
        $this->load->driver('cache');
        $key = 'citybox_recharge_card:'. ':' . $acount_id . ':' .date('Y-m-d');
        if(!$this->cache->redis->exists($key)){
            return 0;
        }
        $cache_num = $this->cache->redis->get($key);
        return intval($cache_num);
    }

    /*
     * @desc 更新 用户输错次数， 反刷
     * @param $acount_id int 账户id
     * */
    public function update_acount_error_times($acount_id){
        $this->load->driver('cache');
        $key = 'citybox_recharge_card:'. ':' . $acount_id . ':' .date('Y-m-d');
        $cache_num = $this->cache->redis->get($key);
        $cache_num = intval($cache_num) + 1;
        $this->cache->redis->save($key, $cache_num, 86400);
    }

    /*
     * @desc 记录成功输入的卡号， 防止数据库主从延迟
     * @param $pass 密码 str
     * */
    public function get_card_pass($pass){
        $this->load->driver('cache');
        $key = 'citybox_recharge_card_pass:'. ':' . $pass;
        if($this->cache->redis->exists($key)){
            return 1;//已经使用
        }
        return 0;
    }

    /*
     * @desc 记录成功输入的卡号， 防止数据库主从延迟
     * @param $pass
     * */
    public function update_card_pass($pass){
        $this->load->driver('cache');
        $key = 'citybox_recharge_card_pass:'. ':' . $pass;
        $this->cache->redis->save($key, 1, 86400);
    }

}
<?php

/**
 * 定时脚本
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 4/19/17
 * Time: 14:03
 */
class Cron_event extends CI_Controller
{
    const EXCEPTION_MIN = 15;
    const EXCEPTION_SCAN_MIN = 3;
    const PAY_TIME_MIN = 30;
    const PAY_TIME_DAY = 15;//15天前的订单
    const CACHE_BOX_TIME_MIN = 10;
    const CHECK_BOX_TIME_MIN = 2;
    const PAY_TIME_MONTH = 60;

    function __construct()
    {
        parent::__construct();
        $this->load->model('user_fin_model');
        $this->load->model('user_sign_model');
        $this->load->model('user_friend_model');
        $this->load->model('user_model');
        $this->load->driver('cache',
            array('adapter' => 'redis', 'key_prefix' => 'citybox_')
        );

    }

    public function send_land(){
        $hour =  date('H');
        if($hour >= 7 && $hour<=23){
            $total_land = rand(1,5); //todo
            $users = $this->user_fin_model->get_collect_users(1);
            write_log('send_land cron '.var_export($users,1));
            $total_energy = 0;
            foreach ($users as $user1){
                $user_cache = get_cache_user($user1['user_id']);
                $total_energy += intval($user_cache['fin']['energy']);
            }
            foreach ($users as $user){
                    $data = array(
                        'user_id'=>$user['user_id'],
                        'send_time'=>date("Y-m-d H:i:s"),
                        'land'=>$this->get_land($user['user_id'],$total_energy,$total_land),
                        'status'=>0
                    );
                    $r = $this->db->insert('fin_land_list',$data);
                    if($r){
                        $this->cache->save(LAND_COLLECT_TIME_KEY.$user['user_id'],time());
                    }
            }
        }else{
            write_log('send_land cron 不执行');
        }
    }
    function get_land($uid,$total_energy,$total_land){
        $user = get_cache_user($uid);
        $energy = $user['fin']['energy'];
        return floatval($total_land/$total_energy) * $energy;
    }
}

?>
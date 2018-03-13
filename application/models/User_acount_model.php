<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/10/27
 * Time: 下午1:18
 */

class User_acount_model extends MY_Model
{



    function __construct()
    {
        parent::__construct ();
    }



    /*
     * @desc 支付成功后， 更新用户魔力值， 魔豆， 等级
     * @param $uid int 用户id
     * @param float $money 消费金额
     * @param var $order_name 订单编号
     * */
    function update_user_acount($uid, $money, $order_name){
        if($uid<=0 || $money<=0){
            return false;
        }
        $this->load->helper('platform_config_helper');
        $this->load->model('order_model');
        $user_info = $this->get_user_info_by_id($uid);
        if(empty($user_info) || !$user_info['acount_id']){
            return false;
        }
        $moli = $modou = ceil($money); //魔力 魔豆 1比1 兑换
        $order_info = $this->order_model->get_order_by_name($order_name);
        if($order_info['box_no'] && !check_device_use_yue($order_info['box_no'])){//不使用余额的平台也不使用魔豆
            $modou = 0;
        }else{
            $modou = (date("w") == 5)?$modou*2:$modou;//星期五， 魔豆翻倍
        }

        $moli_param['acount_id'] = $user_info['acount_id'];
        $moli_param['uid']       = $uid;
        $moli_param['moli']      = $moli;
        $moli_param['des']       = '订单'.$order_name.'赠送';
        $moli_param['add_time']  = date('Y-m-d H:i:s');

        $modou_param['acount_id'] = $user_info['acount_id'];
        $modou_param['uid']       = $uid;
        $modou_param['modou']     = $modou;
        $modou_param['des']       = '订单'.$order_name.'赠送';
        $modou_param['add_time']  = date('Y-m-d H:i:s');
        $modou_param['order_name']= $order_name;

        $moli_tmp = bcadd($user_info['moli'], $moli);
        $acount_param['moli']     = $moli_tmp;
        $acount_param['modou']    = bcadd($user_info['modou'], $modou);

        //用户等级
        if($moli_tmp<800){
            $user_rank = 1;
        }elseif(800<=$moli_tmp && $moli_tmp<1200){
            $user_rank = 2;
        }elseif(1200<=$moli_tmp && $moli_tmp<2000){
            $user_rank = 3;
        }else{
            $user_rank = 4;
        }
        $acount_param['user_rank']  = $user_rank;
        $acount_param['update_time']= date('Y-m-d H:i:s');

        $this->db->trans_begin();
        $this->db->update('user_acount', $acount_param, array('id'=>$user_info['acount_id']));//合并账户表
        $this->db->insert('user_acount_moli', $moli_param);//魔力
        if($modou>0){
            $this->db->insert('user_acount_modou', $modou_param);//魔豆
        }
        if ($this->db->trans_status() === FALSE) {
            $this->db->trans_rollback();
            return false;
        } else {
            $this->db->trans_commit();
            return true;
        }

    }


    /*
     * @desc 获取用户信息
     * */
    function get_user_info_by_id($uid){
        if(!$uid){
            return array();
        }
        $this->db->from('user');
        $this->db->where(array('id'=>$uid));
        $rs = $this->db->get()->row_array();
        $acount_id = intval($rs['acount_id']);

        $this->db->from('user_acount');
        $this->db->where(array('id'=>$acount_id));
        $acount = $this->db->get()->row_array();//账户信息

        if($acount_id==0 || !$acount){
            $acount = $this->create_acount($uid);//如果没有账户信息 则创建一个
        }
        $rs['acount_id'] = intval($acount['id']);
        $rs['user_rank'] = intval($acount['user_rank']);
        $rs['moli']      = intval($acount['moli']);//魔力
        $rs['modou']     = intval($acount['modou']);//魔豆
        $rs['yue']       = floatval($acount['yue']);//余额
        return $rs;
    }

    /*
     * @desc 创建 acount, 如果acount信息为空， 则为这个用户创建一个
     *
     * */
    function create_acount($uid){
        $acount['moli']        = 0;
        $acount['modou']       = 1;
        $acount['update_time'] = date('Y-m-d H:i:s');
        $acount['user_rank']   = 1;
        $this->db->insert('user_acount', $acount);
        $acount_id = $this->db->insert_id();

        $modou_param['acount_id'] = $acount_id;
        $modou_param['uid']       = $uid;
        $modou_param['modou']     = 1;
        $modou_param['des']       = "首单赠送";
        $modou_param['add_time']  = date('Y-m-d H:i:s');
        $this->db->insert('user_acount_modou', $modou_param);//首单赠送1个魔豆
        $this->db->update('user', array('acount_id'=>$acount_id), array('id'=>$uid));

        return array(
            'id' => $acount_id,
            'user_rank' => 1,
            'moli'      => 0,
            'modou'     => 1,
            'yue'       => 0
        );
    }



    /**
     * @desc 获取当前用户优惠, 会员权限， 魔豆使用
     * @param $uid int 用户id
     * @param $money float  消费金额
     * @param $order_name string 订单号
     * @param $refer string 来源
     * @param $platform_id int 平台id
     * @param $device_id string 设备id
     * @return array
     * */

    function get_user_discount($uid, $money, $order_name='', $refer='', $platform_id=0, $device_id=''){
        $result = array(
            'level_money' => 0,//等级折扣  暂时作废
            'modou'       => 0,//魔豆抵扣
            'total'       => 0,//总共
            'yue'         => 0//余额抵扣
        );
        if($money<=0){
            return $result;
        }
        $user_info = $this->get_user_info_by_id($uid);

        $dis_money = $this->acount_money($uid, $money, $user_info, $refer, $platform_id, $device_id);
        $result['modou'] = $dis_money['modou'];
        $result['yue']   = $dis_money['yue'];
        $result['total'] = $dis_money['total'];
        $acount_param = array();
        $acount_param['modou'] = $user_info['modou'];
        if($result['modou'] > 0){//魔豆没有抵扣钱，则不需要处理后续
            $modou_param['acount_id'] = $user_info['acount_id'];
            $modou_param['uid']       = $uid;
            $modou_param['modou']     = '-'.$result['modou']*100;
            $modou_param['des']       = '订单'.$order_name.'消耗';
            $modou_param['add_time']  = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_modou', $modou_param);
            $acount_param['modou']    = bcsub($user_info['modou'], $result['modou']*100);//账户剩余魔豆
        }

        if($result['yue']>0){//余额使用
            $yue_param['acount_id'] = $user_info['acount_id'];
            $yue_param['uid']       = $uid;
            $yue_param['yue']       = '-'.$result['yue'];
            $yue_param['des']       = '订单'.$order_name.'消费';
            $yue_param['add_time']  = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_yue', $yue_param);

            $acount_param['yue']    = bcsub($user_info['yue'], $result['yue'], 2);//账户剩余 余额

            if(date("w") == 5){ //星期五， 魔豆翻倍
                $modou = $result['yue']*2;
            }else{
                $modou = $result['yue'];
            }
            $modou_param['acount_id'] = $user_info['acount_id'];
            $modou_param['uid']       = $uid;
            $modou_param['modou']     = ceil($modou);
            $modou_param['des']       = '订单'.$order_name.'使用余额赠送';
            $modou_param['add_time']  = date('Y-m-d H:i:s');
            $modou_param['order_name']= $order_name;

            $this->db->insert('user_acount_modou', $modou_param);
            $acount_param['modou'] = bcadd($acount_param['modou'] , ceil($modou));//使用了余额，赠送魔豆
        }

        if(!empty($acount_param)){
            $acount_param['update_time']= date('Y-m-d H:i:s');
            $this->db->update('user_acount', $acount_param, array('id'=>$user_info['acount_id']));
        }
        return $result;

    }

    /**
     * @desc 计算账户能够优惠多少钱  source 来源:gat  fruitday 的用户不允许使用 余额 商户id14
     * @param $uid int 用户id
     * @param $money float 需支付的钱
     * @param $user_info array 用户信息
     * @param $refer string 来源
     * @param $platform_id int 平台id
     * @param $device_id string 设备id
     * @return array
     * */
    public function acount_money($uid, $money, $user_info=array(), $refer='', $platform_id=0, $device_id=''){
        $this->load->helper('platform_config_helper');
        $user_info   = empty($user_info)?$this->get_user_info_by_id($uid):$user_info;
        $can_modou   = intval($user_info['modou']/100);//账户剩余魔豆能够抵扣多少钱
        if( ($device_id && !check_device_use_yue($device_id)) ){//不使用魔豆
            $modou_final = 0;
        }else{
            $can_money   = intval($money/2);//魔豆最高抵扣剩余金额的50%
            $modou_final = $can_modou>$can_money?$can_money:$can_modou;//最终魔豆能够抵扣的钱， 取最小的值
        }
        //剩余的钱
        $last_money  = bcsub($money, $modou_final, 2);//剩余的钱
        $yue         = $last_money>$user_info['yue']?floatval($user_info['yue']):$last_money;//余额抵扣的钱, 取小值
        $yue_refer   = $this->config->item("yue_refer");
        //新版检测余额是否能够使用
        if(in_array($refer, $yue_refer) || ($device_id && !check_device_use_yue($device_id))){
            $yue = 0;
        }

        $total       = bcadd($modou_final, $yue, 2);
        return array('modou'=>$modou_final, 'yue'=>$yue, 'total'=>$total);
    }

    /*
     * @desc 计算用户能使用多少魔豆
     * @param int $uid 用户id
     * @param float $money 剩余金额
     * */
    public function modou_money($uid, $money){
        $user_info   = $this->get_user_info_by_id($uid);
        $can_modou   = intval($user_info['modou']/100);//账户剩余魔豆能够抵扣多少钱

        $can_money   = intval($money/2);//魔豆最高抵扣剩余金额的50%
        $modou_final = $can_modou>$can_money?$can_money:$can_modou;//最终抵扣的钱， 取最小的值

        return $modou_final;
    }


    /*
     * @desc 对相同手机号的用户 进行账号合并
     * @param int $uid  用户id
     * @param int $mobile 手机号
     * */
    public function acount_merge($uid, $mobile, $user_info){
        return $this->acount_merge_new($uid, $mobile, $user_info);
        $this->db->select('u.id,u.mobile,u.acount_id,ua.moli, ua.modou, ua.yue, ua.frozen_yue');
        $this->db->from('user u');
        $this->db->join('user_acount ua', 'u.acount_id=ua.id', 'left');
        $this->db->where( array('u.mobile'=>$mobile, 'u.id !='=>$uid) );
        $rs = $this->db->get()->result_array();
        if(!empty($rs) && $user_info['acount_id']){//存在一样手机号的账户
            $param['moli']  = 0;
            $param['modou'] = 0;
            $param['yue']   = 0;
            $param['frozen_yue'] = 0;
            foreach($rs as $k=>$v){
                if($user_info['acount_id'] != $v['acount_id']){//当用户acount_id和主账号一样 则不需要更新
                    $this->db->update('user', array('acount_id'=>$user_info['acount_id']), array('id'=>$v['id']));//将同手机号的账号 合并到当前账号
                }
                if($v['acount_id'] == $user_info['acount_id'] || $v['acount_id']!=$v['id']){//acount_id一样 不需要合并,acoun_id和uid不一样说明已经合并过
                    continue;
                }
                $param['moli']  += intval($v['moli']);
                $param['modou'] += intval($v['modou']);
                $param['yue']           = bcadd($param['yue'], $v['yue'], 2);
                $param['frozen_yue']    = bcadd($param['frozen_yue'], $v['frozen_yue'], 2);
            }
            if($param['moli']==0 && $param['modou']==0){//没有需要合并的
                return false;
            }

            $acount['moli']  = $param['moli'] +intval($user_info['moli']);
            $acount['modou'] = $param['modou']+intval($user_info['modou']);
            $acount['yue']           = bcadd($param['yue'], $user_info['yue'], 2);
            $acount['frozen_yue']    = bcadd($param['frozen_yue'], $user_info['frozen_yue'], 2);

            if($acount['moli']<800){
                $acount['user_rank'] = 1;
            }elseif(800<=$acount['moli'] && $acount['moli']<1200){
                $acount['user_rank'] = 2;
            }elseif(1200<=$acount['moli'] && $acount['moli']<2000){
                $acount['user_rank'] = 3;
            }else{
                $acount['user_rank'] = 4;
            }
            $acount['update_time'] = date('Y-m-d H:i:s');

            $this->db->trans_begin();
            $this->db->update('user_acount', $acount, array('id'=>$user_info['acount_id']));//更新总账户信息

            $modou['uid']       = $uid;
            $modou['acount_id'] = $user_info['acount_id'];
            $modou['modou']     = $param['modou'];
            $modou['des']       = '合并账号迁移过来';
            $modou['add_time']  = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_modou', $modou);//insert 魔豆记录

            $moli['uid']        = $uid;
            $moli['acount_id']  = $user_info['acount_id'];
            $moli['moli']       = $param['moli'];
            $moli['des']        = '合并账号迁移过来';
            $moli['add_time']   = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_moli', $moli);//insert 魔豆记录


            $yue['uid']        = $uid;
            $yue['acount_id']  = $user_info['acount_id'];
            $yue['yue']        = $param['yue'];
            $yue['des']        = '合并账号迁移过来';
            $yue['add_time']   = date('Y-m-d H:i:s');
            $yue['yue_type']   = 7;
            $yue['remarks']    = '对相同手机号，系统自动账号合并';
            $this->db->insert('user_acount_yue', $yue);//insert 余额记录

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return true;
            }
        }

        return true;

    }


    /*
     * @desc 对相同手机号的用户 进行账号合并
     * @param int $uid  用户id
     * @param int $mobile 手机号
     * */
    public function acount_merge_new($uid, $mobile, $user_info){
        if($user_info['mobile']){
            return false;
        }
        $this->db->select('u.id,u.mobile,u.acount_id,ua.moli, ua.modou, ua.yue, ua.frozen_yue');
        $this->db->from('user u');
        $this->db->join('user_acount ua', 'u.acount_id=ua.id', 'left');
        $this->db->where( array('u.mobile'=>$mobile) );
        $this->db->group_by('u.acount_id');
        $this->db->order_by('ua.yue desc');
        $rs = $this->db->get()->result_array();
        $current = current($rs);//主账号财富
        $acount_id = $current['acount_id'];//主账号id，以最先的账号
        if(!empty($rs) && $user_info['acount_id']){//存在一样手机号的账户
            $this->db->update('user', array('acount_id'=>$acount_id), array('id'=>$uid));//将当前账号合并到主账号
            //当前账号财富
            $param['moli']  = intval($user_info['moli']);
            $param['modou'] = intval($user_info['modou']);
            $param['yue']   = $user_info['yue'];
            $param['frozen_yue'] = $user_info['frozen_yue'];
            //循环财富
            foreach($rs as $k=>$v){
                if($acount_id != $v['acount_id']){//当用户acount_id和主账号不一样 这合并进去
                    $this->db->update('user', array('acount_id'=>$acount_id), array('id'=>$v['id']));//将同手机号的账号 合并到当前账号
                    $param['moli']  += intval($v['moli']);
                    $param['modou'] += intval($v['modou']);
                    $param['yue']           = bcadd($param['yue'], $v['yue'], 2);
                    $param['frozen_yue']    = bcadd($param['frozen_yue'], $v['frozen_yue'], 2);
                }
            }
            //主账号财富 加 循环的财富 加 当前新用户的财富
            $acount['moli']          = $param['moli'] + $current['moli'];
            $acount['modou']         = $param['modou']+ $current['modou'];
            $acount['yue']           = bcadd($param['yue'], $current['yue'], 2) ;
            $acount['frozen_yue']    = bcadd($param['frozen_yue'], $current['frozen_yue'], 2);

            if($acount['moli']<800){
                $acount['user_rank'] = 1;
            }elseif(800<=$acount['moli'] && $acount['moli']<1200){
                $acount['user_rank'] = 2;
            }elseif(1200<=$acount['moli'] && $acount['moli']<2000){
                $acount['user_rank'] = 3;
            }else{
                $acount['user_rank'] = 4;
            }
            $acount['update_time'] = date('Y-m-d H:i:s');

            $this->db->trans_begin();
            $this->db->update('user_acount', $acount, array('id'=>$acount_id));//更新总账户信息

            $modou['uid']       = $uid;
            $modou['acount_id'] = $acount_id;
            $modou['modou']     = $param['modou'];
            $modou['des']       = '合并账号迁移过来';
            $modou['add_time']  = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_modou', $modou);//insert 魔豆记录

            $moli['uid']        = $uid;
            $moli['acount_id']  = $acount_id;
            $moli['moli']       = $param['moli'];
            $moli['des']        = '合并账号迁移过来';
            $moli['add_time']   = date('Y-m-d H:i:s');
            $this->db->insert('user_acount_moli', $moli);//insert 魔豆记录


            $yue['uid']        = $uid;
            $yue['acount_id']  = $acount_id;
            $yue['yue']        = $param['yue'];
            $yue['des']        = '合并账号迁移过来';
            $yue['add_time']   = date('Y-m-d H:i:s');
            $yue['yue_type']   = 7;
            $yue['remarks']    = '对相同手机号，系统自动账号合并';
            $this->db->insert('user_acount_yue', $yue);//insert 余额记录

            if ($this->db->trans_status() === FALSE) {
                $this->db->trans_rollback();
                return false;
            } else {
                $this->db->trans_commit();
                return true;
            }
        }

        return true;

    }




    /*
     * @desc 获取账户信息
     * */
    function get_acount_info($acount_id){
        $this->db->select('u.id as uid, ua.id as acount_id, ua.moli, ua.modou, ua.yue, u.source');
        $this->db->from('user u');
        $this->db->join('user_acount ua', 'u.acount_id=ua.id');
        $this->db->where(array('u.acount_id'=>$acount_id));
        return $this->db->get()->row_array();
    }


}
<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/12
 * Time: 上午11:28
 * 满额减活动
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Active_discount_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public $product_data = array();
    public $active_data  = array();

    public function table_name()
    {
        return 'active_discount';
    }

    /*
     * @desc 获取当前机器的满额活动
     * @param $box_no string 盒子编码
     * @return array
     * */
    public function get_active($box_no){
        $where['box_no'] = $box_no;
        $where['start_time <'] = date('Y-m-d H:i:s');
        $where['end_time >'] = date('Y-m-d H:i:s');
        $this->db->where($where);
        $this->db->from($this->table_name());
        $rs = $this->db->get();
        if($rs){
            $rs = $rs->result_array();
            foreach($rs as $k=>&$v){
                $v['product_ids'] = explode('#', $v['product_ids']);
            }
        }
        return $rs;
    }


    /*
     * @desc 判断当前用户符合哪些活动
     * @param $box_no string 盒子编码
     * @param $refer string 渠道来源
     * @param $is_new bool false:老用户， true：新用户
     * */
    public function get_active_by_user($box_no, $refer='alipay', $is_new=true, $uid=0, $platform_id=1){
        if(!$is_new){//老用户，不能享受新客
            $where['active_people !=']  = 1;
        }
        $where['platform_id']  = $platform_id;//平台id
        $where['active_status'] = 1;//active_status  0:删除， 1:启用， 2:暂停
        $where['start_time <'] = date('Y-m-d H:i:s');
        $where['end_time >'] = date('Y-m-d H:i:s');
        $this->db->where($where);
        $this->db->where_in('refer', array('all', $refer));
        $this->db->from($this->table_name());
        $this->db->order_by('id desc');
        $rs = $this->db->get()->result_array();
        if(empty($rs)){
            return array();
        }
        //获取用户手机
        $this->load->model('user_model');
        $userInfo = $this->user_model->get_user_info_by_id($uid);
        $result = array();

        foreach($rs as $k=>$v){
            if($v['box_num'] == 0 && strpos($v['box_no'], $box_no)===false){//部分设备
                continue;//搜不到设备
            }
            if($v['active_people'] == 2){//0,全体用户， 1：新客， 2白名单
                if(!$userInfo['mobile']){//没有 手机号 不参加
                    continue ;
                }
                $mobile_arr = $this->get_white_mobile($v['id']);
                if(!in_array($userInfo['mobile'], $mobile_arr)){
                    continue ;
                }
            }
            $active_limit = $this->get_active_limit($v['id'], $box_no, $v['active_limit'], $v['active_type']);
            if($active_limit<=0){
                continue ;
            }
            $user_rank = explode(',', $v['user_rank']);
            if(!in_array(intval($userInfo['user_rank']), $user_rank)){
                continue ;
            }

            $result[$k] = $v;
            $result[$k]['product_ids']   = explode('#', $v['product_ids']);
            $result[$k]['black_product'] = explode('#', $v['black_product']);
            $result[$k]['class_id']      = explode(',', $v['class_id']);
        }

        return $result;
    }

    /*
     * @desc 获取活动白名单
     * @param $active_id 活动id
     * */
    public function get_white_mobile($active_id){
        $this->db->from('active_white_user');
        $this->db->where(array('active_id'=>$active_id));
        $rs = $this->db->get()->row_array();
        if(empty($rs)){
            return array();
        }
        return explode('#', $rs['mobile']);
    }

    /*
     * @desc 计算随机减活动
     * @param $config  array 活动配置
     * @param $min_money float 订单金额
     * @return float
     * */
    public function discount_suiji($config, $min_money){
        if(empty($config)){
            return 0;
        }

        $bingo = rand(1, 100000);
        $gift = array();
        /* 获取对应金额的优惠券start */
        $percent = 0;
        foreach ($config as $k => $v) {
            $percent += $v['percent'];
            $config[$k]['percent'] = $percent*1000;
        }
        foreach ($config as $k => $v) {
            if ($bingo <= $v['percent']) {
                $gift = $v;
                break;
            }
        }
        if(empty($gift)){
            return 0;
        }

        $bili = bcsub(100, $gift['discount_s']*10)/100;//相减，优惠比例
        $tmp = bcmul($bili, $min_money, 2);//相乘，优惠金额，保留两位小数
        if($gift['max_money']>0 && $tmp>$gift['max_money']){
            $tmp = $gift['max_money'];//不能超过最高值
        }

        return $tmp;
    }

    /*
     * @desc 计算首件商品减活动
     * @param $config  array 活动配置
     * @param $product_list array 订单商品
     * @return float
     * */
    public function discount_shoujian($config, $product_list){
        if( empty($product_list)){
            return 0;
        }
        $discount_money = 0;
        if($config['rewards'] == 1){//价格最高商品0折
            $product_list = $this->array_sort($product_list, 'price', 'desc');
            $tmp = current($product_list);
            $discount_money = $tmp['price'];
            $discount_money = ($config['max_money']>0 && $discount_money>$config['max_money'])?$config['max_money']:$discount_money;
        }
        if($discount_money>0){
            return $discount_money;
        }
        return 0;
    }

    /*
     * @desc 计算 分时折扣活动
     * @param $config array 活动配置
     * @param $product_list array  订单商品
     * @return float
     * */
    public function discount_fenshi($config, $min_money, $active_product){
        if($min_money<=0){
            return 0;
        }
        $hour = date('H');
        foreach($config as $k=>$v){
            if($hour>=$v['start_hour']  && $hour<$v['end_hour']){
                if( $v['final_money_f'] > 0 && !empty($active_product) ){//最终价格优先

                    $d_money = 0;
                    foreach($active_product as $pk=>$pv){
                        $d_money = bcadd($d_money, bcsub($pv['price'], $v['final_money_f'], 2)*$pv['qty'], 2 );
                    }
                    return $d_money;
                }
                $bili = bcsub(100, $v['discount_f']*10)/100;//相减，优惠比例
                $tmp = bcmul($bili, $min_money, 2);//相乘，优惠金额，保留两位小数
                if($v['max_money_f']>0 && $tmp>$v['max_money_f']){
                    $tmp = $v['max_money_f'];//不能超过最高值
                }
                return $tmp;
            }
        }
        return 0;
    }

    //对二维数组 进行排序
    public function array_sort($arr,$keys,$type='asc'){
        $tmp = array();
        foreach($arr as $k=>$v){
            $tmp[$k] = $v[$keys];
        }
        if($type == "asc"){
            array_multisort($arr, SORT_ASC, SORT_STRING, $tmp, SORT_ASC);
        }else{
            array_multisort($arr, SORT_DESC, SORT_STRING, $tmp, SORT_DESC);
        }
        return $arr;
    }


    /*
     * @desc  保存用户当天活动参与次数
     * @param $max_type 活动次数类型 1：当天   2：活动期间
     * @param $uid  用户id
     * @param $active_id  活动id
     */
    public function save_active_num($uid, $active_id, $max_type='1'){
        if($max_type==0){
            return true;
        }
        $this->load->driver('cache');
        $num = 1;
        if($max_type == 1){
            $key = 'active:' . $uid . ':' . $active_id ;
            $cache_num = $this->cache->redis->get($key);
            $cache_num = intval($cache_num) + $num;
            $this->cache->redis->save($key, $cache_num, $this->get_next_time());
        }elseif($max_type==2){
            $key = 'active_all:' . $uid . ':' . $active_id;
            $cache_num = $this->cache->redis->get($key);
            $cache_num = intval($cache_num) + $num;
            $this->cache->redis->save($key, $cache_num, 0);
        }
        return true;
    }

    /*
     * @desc  保存用户当天活动参与次数
     * @param $max_type 活动次数类型 1：当天   2：活动期间, 0:无限制
     * @param $uid  用户id
     * @param $active_id  活动id
     */
    public function get_active_num($uid, $active_id, $max_type='1'){
        if($max_type == 1) {
            $key = 'active:' . $uid . ':' . $active_id;
        }elseif($max_type == 2){
            $key = 'active_all:' . $uid . ':' . $active_id;
        }elseif($max_type == 0){
            return 0;
        }
        $this->load->driver('cache');
        $cache_num = $this->cache->redis->get($key);
        return intval($cache_num) ;
    }

    function get_next_time(){
        return strtotime(date('Y-m-d 23:59:59')) -time();
    }


    /*
     * @desc 计算活动优惠金额
     * @param int $uid 用户id
     * @param str $box_no 盒子id
     * @param str $refer 来源
     * @param int $platform_id 平台id
     * @param array $data = array(
     *                          array('product_id'=>xxx, 'qty' => xxx, 'class_id'=>xxx, 'price'=>xxx)
     *                      )
     * @return array
     * */
    public function get_order_after_active($uid, $box_no, $refer, $platform_id, $data){

        $is_new = $this->check_user_order($uid);
        $active_discount = $this->get_active_by_user($box_no, $refer, $is_new, $uid, $platform_id);
        $discount_log = array();// 优惠的总数记录
        $discount_money = 0;//优惠金额
        if (!empty($active_discount)) {

            foreach ($active_discount as $dk => $dv) {
                $min_money = 0;
                $active_product = array();//符合活动范围的商品id
                foreach ($data as $k => $v) {
                    if (  (in_array($v['class_id'], $dv['class_id']) || in_array($v['product_id'], $dv['product_ids'])) && !in_array($v['product_id'], $dv['black_product']) ) {
                        $min_money += $v['price']*$v['qty'];
                        $active_product[$k]['product_id'] = $v['product_id'];//符合活动范围的商品id
                        $active_product[$k]['price']      = $v['price'];
                        $active_product[$k]['qty']        = $v['qty'];
                    }elseif($dv['active_type'] == 4){//搭配购 所有商品都要计算
                        $min_money += $v['price']*$v['qty'];
                    }
                }
                if ($min_money >= $dv['min_money']) {
                    $today_cache = $this->get_active_num($uid, $dv['id'], $dv['max_type']);
                    if($dv['max_type']>0 && $dv['max_times']<=$today_cache){//max_type: 0-无限制，1-当日，2 活动期间
                        continue;
                    }
                    $one_active_money = 0;//当个活动优惠价格
                    if($dv['active_type'] == 0 ){
                        if($dv['discount_money'] > 0){//满额减
                            $one_active_money = $dv['discount_money'];
                        }elseif(0 < $dv['discount'] && $dv['discount'] < 100){//满额折
                            $bili = bcsub(100, $dv['discount'])/100;//相减，优惠比例
                            $one_active_money = bcmul($bili, $min_money, 2);//相乘，优惠金额
                        }
                    }elseif($dv['active_type'] == 1 ){//随机减
                        $one_active_money = $this->discount_suiji(json_decode($dv['config'], true), $min_money);
                    }elseif($dv['active_type'] == 2 ){//首件商品减
                        $one_active_money = $this->discount_shoujian(json_decode($dv['config'], true), $data);
                    }elseif($dv['active_type'] == 3 ){//分时折扣
                        $one_active_money = $this->discount_fenshi(json_decode($dv['config'], true), $min_money, $active_product);
                    }elseif($dv['active_type'] == 4 ){//搭配购
                        $one_active_money = $this->discount_dapei(json_decode($dv['config'], true), $data);
                    }
                    if($one_active_money>0){
                        $this->save_active_num($uid, $dv['id'], $dv['max_type']);//记录参加次数
                        $discount_money = bcadd($discount_money, $one_active_money, 2);
                        $discount_log[$dk]['text']           = $dv['active_title']?$dv['active_title']:'';
                        $discount_log[$dk]['discount_money'] = $one_active_money;
                        $discount_log[$dk]['cache']          = $today_cache;
                    }
                }
            }
            return array('discount_money' => $discount_money, 'discount_log' => $discount_log);
        }
    }

    /*
     * @desc 判断用户是否新用户
     * @param $uid int 用户id
     * @return bool
     * */
    public function check_user_order($uid){
        $where = array('uid'=>$uid, 'order_status !='=>4);//排除退款完成的
        $this->db->where($where);
        $this->db->from('order');
        $query = $this->db->get();
        $res = $query->row_array();
        if(empty($res)){
            return true;//新用户
        }
        return false;
    }


    /*
     * @desc 搭配购
     * @param  array $config 配置信息
     * @param  array  $data  商品信息
     */
    public function discount_dapei($config, $dapei_data){
        $dapei_product = $config['data'];
        $result = $one_money = 0;
        if($config['dapei_again'] == 1){//重复享受
            do {
                $one_money = $this->xunhuan_data($dapei_product, $dapei_data);//当money大于0  这继续循环
                $dis_money = bcsub($one_money, $config['dapei_price'], 2);

                if($dis_money>0){
                    $result    = bcadd($result, $dis_money, 2);
                }
            } while ($one_money>0);
        }else{
            $one_money = $this->xunhuan_data($dapei_product, $dapei_data);
            $result    = bcsub($one_money, $config['dapei_price'], 2);//减去最终金额 就是优惠金额
        }
        return $result>0?$result:0;
    }

    public function xunhuan_data($dapei_product, &$dapei_data){
        $result = 0;
        foreach($dapei_product as $k=>$v){
            $tmp = 0;
            foreach($dapei_data as $key=>$val){
                if(($v['dapei_product'] == $val['product_id']) && ($v['dapei_num']<=$val['qty'])){
                    $tmp = bcmul($v['dapei_num'], $val['price'], 2);
                    $dapei_data[$key]['qty'] = $val['qty']-$v['dapei_num'];
                }
            }
            if($tmp<=0){
                return 0;//不符合商品组合
            }else{
                $result = bcadd($result, $tmp, 2);
            }
        }
        return $result;
    }




    /*
     * @desc 计算活动优惠金额
     * @param int $uid 用户id
     * @param str $box_no 盒子id
     * @param str $refer 来源
     * @param int $platform_id 平台id
     * @param array $data = array(
     *                          array('product_id'=>xxx, 'qty' => xxx, 'class_id'=>xxx, 'price'=>xxx)
     *                      )
     * @return array
     * */
    public function get_order_after_active_v2($uid, $box_no, $refer, $platform_id, $data){

        foreach($data as $k=>$v){
            $this->product_data[$v['product_id']] = $v;
            $this->product_data[$v['product_id']]['total_money'] = bcmul($v['price'], $v['qty'], 2);
            $this->product_data[$v['product_id']]['last_money']  = bcmul($v['price'], $v['qty'], 2);//剩余金额
            $this->product_data[$v['product_id']]['no_share']    = 0;//不同享优惠金额
            $this->product_data[$v['product_id']]['share']       = 0;//同享优惠金额
            $this->product_data[$v['product_id']]['has_qty']     = $v['qty'];//同享优惠金额
        }
        $is_new = $this->check_user_order($uid);
        $active = $this->get_active_by_user($box_no, $refer, $is_new, $uid, $platform_id);
        $discount_log = $active_tmp = array();// 优惠的总数记录
        $discount_money = 0;//优惠金额
        if(empty($active)){
            return array();
        }
        //首先对搭配购 进行计算
        foreach($active as $dk=>$dv){

            $today_cache = $this->get_active_num($uid, $dv['id'], $dv['max_type']);//当天享受活动次数

            if($dv['max_type']>0 && $dv['max_times']<=$today_cache){//max_type: 0-无限制，1-当日，2 活动期间
                continue;
            }

            $min_money = 0;
            $dis_tmp   = array();
            if(in_array($dv['active_type'], array(0,1,3))){
                foreach ($this->product_data as $k => $v) {//计算商品最低门槛
                    if (  (in_array($v['class_id'], $dv['class_id']) || in_array($v['product_id'], $dv['product_ids'])) && !in_array($v['product_id'], $dv['black_product']) && $v['qty']>0 ) {
                        $min_money = bcadd($min_money, bcmul($v['qty'], $v['price'], 2), 2);////剩余的钱 总和
                        $dis_tmp[$v['product_id']]['qty'] = $v['qty'];//待删
                        $dis_tmp[$v['product_id']]['product_money'] = bcmul($v['qty'], $v['price'], 2);//商品价格
                        $dis_tmp[$v['product_id']]['price']         = $v['price'];
                        $dis_tmp[$v['product_id']]['product_id']    = $v['product_id'];
                    }
                }

                if($min_money<=0 || $min_money < $dv['min_money']){//不符合最低金额的要求
                    continue;
                }

            }
            $one_money = 0;
            if($dv['active_type'] == 4){//搭配购
                $this->discount_dapei_v2(json_decode($dv['config'], true), $dv['is_share'], $dv['id'], $dv['join_again']);
            }elseif($dv['active_type'] == 0){//满额减

                $one_money = $this->discount_man_v2($dv, $min_money);

            }elseif($dv['active_type'] == 1){//随机减

                $one_money = $this->discount_suiji_v2(json_decode($dv['config'], true), $min_money);
            }elseif($dv['active_type'] == 3){//分时折扣
                $this->discount_fenshi_v2(json_decode($dv['config'], true), $min_money, $dis_tmp, $dv['is_share'], $dv['id'], $dv['join_again']);
            }
            if(in_array($dv['active_type'], array(0,1)) && $one_money>0){//将优惠金额平摊
                $a_money = $one_money;

                foreach($dis_tmp as $k=>$v){
                    $tmp_money = bcdiv(bcmul($v['product_money'], $one_money, 3), $min_money, 2);//商品金额除以最低金额，得到比例，计算本次单个商品优惠

                    $a_money   = bcsub($a_money, $tmp_money, 2);//得出剩余的优惠额度
                    if($a_money >0 && $a_money< 0.05){
                        $tmp_money = bcadd($tmp_money, $a_money, 2);//最后一分钱  给最后一个商品
                    }

                    if($dv['is_share']==0){//不同享
                        if($this->product_data[$k]['no_share']  < $tmp_money){//当前活动金额大
                            $this->product_data[$k]['no_share'] = $tmp_money;
                            $this->active_data['no_share'][$k]  = array('id'=>$dv['id'], 'money'=>$tmp_money);//不同享要求覆盖
                        }
                    }elseif($dv['is_share']==1){//同享优惠金额相加

                        $last_money = bcsub($this->product_data[$k]['total_money'], bcadd($this->product_data[$k]['no_share'], $this->product_data[$k]['share'], 2), 2);
                        $tmp_money  = $last_money>$tmp_money?$tmp_money:$last_money;//同享的活动 得判断剩余的金额

                        $this->product_data[$k]['share']           = bcadd($this->product_data[$k]['share'], $tmp_money, 2);
                        $this->active_data['share'][$k][$dv['id']] = $tmp_money;//同享要所有
                    }
                }

            }

            $active_tmp[$dv['id']]['title']       = $dv['active_title'];
            $active_tmp[$dv['id']]['cache']       = $today_cache;
            $active_tmp[$dv['id']]['max_type']    = $dv['max_type'];
            $active_tmp[$dv['id']]['active_type'] = $dv['active_type'];

        }
        $p_data = array();//返回每件商品的优惠金额

        foreach($this->product_data as $k=>$v){
            $tmp = bcadd($v['no_share'], $v['share'], 2);
            $product_dis = $tmp>$v['total_money'] ? $v['total_money'] : $tmp;
            $p_data[$k]  = $product_dis;
            $discount_money = bcadd($discount_money, $product_dis, 2);
        }
        foreach($this->active_data['no_share'] as $k=>$v){//不同享的活动
            $discount_log[$v['id']]['text']           = $active_tmp[$v['id']]['title'];
            $discount_log[$v['id']]['discount_money'] = bcadd(floatval($discount_log[$v['id']]['discount_money']), $v['money'], 2);
            $discount_log[$v['id']]['cache']          = $active_tmp[$v['id']]['cache'];
            $discount_log[$v['id']]['max_type']       = $active_tmp[$v['id']]['max_type'];
        }
        foreach($this->active_data['share'] as $k=>$v){//不同享的活动
            foreach($v as $pk=>$pv){
                $discount_log[$pk]['text']           = $active_tmp[$pk]['title'];
                $discount_log[$pk]['discount_money'] = bcadd(floatval($discount_log[$pk]['discount_money']), $pv, 2);
                $discount_log[$pk]['cache']          = $active_tmp[$pk]['cache'];
                $discount_log[$pk]['max_type']       = $active_tmp[$pk]['max_type'];
            }
        }
        foreach($discount_log as $lk=>$lv){
            if($lv['discount_money']<=0){
                unset($discount_log[$lk]);
                continue;
            }
            $this->save_active_num($uid, $lk, $lv['max_type']);//记录参加次数
            $this->update_active_limit($lk, $box_no, $active_tmp[$lk]['active_type']);
        }
        return array('discount_money' => $discount_money, 'discount_log' => $discount_log, 'data'=>$p_data);
    }




    /**
     * 搭配购
     * @param  array $config 配置信息
     * @param  int   $is_share  是否同享  0：不同享 1：同享
     * @param $active_id int 活动id
     * @param $join_again int 是否重复
     * @return bool
     */
    public function discount_dapei_v2($config, $is_share, $active_id=0, $join_again=0){

        $dapei_product = $config['data'];
        $dis_money = $money = 0;
        $join_again = isset($config['dapei_again'])?$config['dapei_again']:$join_again;
        if($join_again == 1){//重复享受
            do {
                $dis_money = $this->xunhuan_data_v2($dapei_product, $config['dapei_price']);//当money大于0  这继续循环
            } while ($dis_money>0);
        }else{
            $this->xunhuan_data_v2($dapei_product, $config['dapei_price']);
        }

        foreach($this->product_data as $k=>$v){
            $tmp = $v['dis_money'];//$v['last_money']>$v['dis_money']?$v['dis_money']:$v['last_money'];
            if($is_share==0){//不同享
                if($v['no_share']<$v['dis_money']){//当前活动金额大
                    $this->product_data[$k]['no_share']   = $tmp;
                    $this->active_data['no_share'][$k]    = array('id'=>$active_id, 'money'=>$tmp);
                }
            }elseif($is_share==1){//同享优惠金额相加
                $last_money = bcsub($v['total_money'], bcadd($v['no_share'], $v['share'], 2), 2);
                $tmp        = $last_money>$tmp?$tmp:$last_money;//同享的活动 得判断剩余的金额

                $this->product_data[$k]['share']      = bcadd($v['share'], $tmp, 2);
                $this->active_data['share'][$k][$active_id]   =$tmp;
            }
            $this->product_data[$k]['dis_money']    = 0;//重置打折金额
        }
        return true;
    }


    public function xunhuan_data_v2($dapei_product, $dapei_price){
        $total = 0;
        $dis_tmp = array();
        $is_return = false;//判断是否需要数量退回， 只要其中一个商品不符合 就回退
        $tmp_product = $this->product_data;//定义一个局部变量， 保持商品原始数据
        foreach($dapei_product as $k=>$v){
            $tmp = 0;
            foreach($this->product_data as $key=>$val){
                if(($v['dapei_product'] == $val['product_id']) && ($v['dapei_num']<=$val['has_qty'])){
                    $tmp = bcmul($v['dapei_num'], $val['price'], 2);//商品的价值
                    $this->product_data[$key]['has_qty'] = $val['has_qty']-$v['dapei_num'];
                }
            }
            if($tmp<=0){
                $is_return = true;//有存在不符合的商品  回退
            }else{
                $dis_tmp[$v['dapei_product']] = $tmp;
                $total = bcadd($total, $tmp, 2);
            }
        }
        // 商品 又不符合规定的  回退
        if($is_return){
            $this->product_data = $tmp_product;
            return 0;
        }

        $result = bcsub($total, $dapei_price, 2);//优惠金额

        $a_money = $result;

        foreach($dis_tmp as $k=>$v){
            $tmp_money =  bcdiv(bcmul($v, $result, 2), $total,2);
            $a_money   = bcsub($a_money, $tmp_money, 2);//如果有最后一分钱
            if($a_money < 0.05 && $a_money>0){
                $tmp_money = bcadd($tmp_money, $a_money, 2);//最后一分钱  给最后一个商品
            }
            $this->product_data[$k]['dis_money'] = bcadd(floatval($this->product_data[$k]['dis_money']), $tmp_money, 2); //根据商品价值比例 计算对应的优惠比例
        }
        return $result;
    }

    /*
     * @desc 满额减, 满额赠， 精确到每个商品的价格
     * */
    public function discount_man_v2($config, $min_money){
        $result = 0;
        if($config['discount_money'] > 0){//满额减
            $result = floatval($config['discount_money']);
        }elseif(0 < $config['discount']){//满额折
            if($config['discount']>10){//将0-100的转换成 0-10
                $config['discount'] = $config['discount']/10;
            }
            $bili = bcsub(10, $config['discount'], 2)/10;//相减，优惠比例
            $result = bcmul($bili, $min_money, 2);//相乘，优惠金额
        }
        return $result;
    }


    /*
     * @desc 计算随机减活动
     * @param $config  array 活动配置
     * @param $min_money float 订单金额
     * @return float
     * */
    public function discount_suiji_v2($config, $min_money){
        if(empty($config)){
            return 0;
        }
        $bingo = rand(1, 100000);
        $gift = array();
        /* 获取对应金额的优惠券start */
        $percent = 0;
        foreach ($config as $k => $v) {
            $percent += $v['percent'];
            $config[$k]['percent'] = $percent*1000;
        }
        foreach ($config as $k => $v) {
            if ($bingo <= $v['percent']) {
                $gift = $v;
                break;
            }
        }

        if(empty($gift)){
            return 0;
        }

        $bili = bcsub(10, $gift['discount_s'], 2)/10;//相减，优惠比例
        $result = bcmul($bili, $min_money, 2);//相乘，优惠金额，保留两位小数
        if($gift['max_money']>0 && $result>$gift['max_money']){
            $result = $gift['max_money'];//不能超过最高值
        }
        return floatval($result);
    }



    /**
     * @desc 计算 分时折扣活动
     * @param $config array 活动配置
     * @param $min_money float  最小金额
     * @param $dis_tmp array  符合条件的商品
     * @param $is_share  int  是否同享
     * @param $active_id int  活动id
     * @param $join_again int 是否重复享受
     * @return float
     * */
    public function discount_fenshi_v2($config, $min_money, $dis_tmp, $is_share=0, $active_id=0, $join_again=0){
        $hour = date('H');
        $dis_tmp = $this->array_sort($dis_tmp, 'price', 'desc');
        foreach($config as $k=>$v){
            if(is_numeric($v['start_hour']) && is_numeric($v['end_hour'])){
                if($hour<$v['start_hour'] || $hour>$v['end_hour']){//当前小时不匹配
                    continue;
                }
            }else{
                $day = date('Y-m-d');
                if( time()<strtotime($day.' '.$v['start_hour']) || time()>strtotime($day.' '.$v['end_hour'])){
                    continue;
                }
            }

            if ($v['final_money_f'] > 0 && !empty($dis_tmp)) {//最终价格优先，会给每个商品 都重复计算
                $result = 0;
                foreach ($dis_tmp as $pv) {
                    $pk = $pv['product_id'];
                    if($join_again==0){//只能享受一次
                        $tmp_money = bcsub($pv['price'], $v['final_money_f'], 2);//单价 减去 最终金额  得到优惠金额
                        $tmp_money = $tmp_money > 0 ? $tmp_money : 0;
                    }else{
                        $final_money = bcmul($v['final_money_f'], $pv['qty'], 2);//相乘 //最终金额
                        $tmp_money = bcsub($pv['product_money'], $final_money, 2);//剩余金额减去 最终金额
                        $tmp_money = $tmp_money > 0 ? $tmp_money : 0;
                    }

                    if ($is_share == 0) {//不同享
                        if ($this->product_data[$pk]['no_share'] < $tmp_money) {//当前活动金额大
                            $this->product_data[$pk]['no_share'] = $tmp_money;
                            $this->active_data['no_share'][$pk] = array('id' => $active_id, 'money' => $tmp_money);;
                        }
                    } elseif ($is_share == 1) {//同享优惠金额相加
                        $last_money = bcsub($this->product_data[$pk]['total_money'], bcadd($this->product_data[$pk]['no_share'], $this->product_data[$pk]['share'], 2), 2);
                        $tmp_money = $last_money > $tmp_money ? $tmp_money : $last_money;//同享的活动 得判断剩余的金额
                        $this->product_data[$pk]['share'] = bcadd($this->product_data[$pk]['share'], $tmp_money, 2);
                        $this->active_data['share'][$pk][$active_id] = $tmp_money;
                    }
                    $result = bcadd($result, $tmp_money, 2);//多个商品相加
                    if($join_again==0){//只能享受一次
                        return $result;
                    }
                }
                return $result;
            }

            //优惠比例， 相对于整个订单
            $bili = bcsub(10, $v['discount_f'], 2) / 10;//相减，优惠比例， 相对于整个订单
            $result = bcmul($bili, $min_money, 2);//相乘，优惠金额，保留两位小数
            if ($v['max_money_f'] > 0 && $result > $v['max_money_f']) {
                $result = $v['max_money_f'];//不能超过最高值
            }
            $a_money = $result;
            foreach ($dis_tmp as $dv) {
                $dk = $dv['product_id'];
                if($join_again==0){//只能享受一次, 只有一件物品才能享受
                    $tmp_money = bcmul($dv['price'], $bili, 2);//只优惠一件价格最高的物品
                    if ($v['max_money_f'] > 0 && $tmp_money > $v['max_money_f']) {
                        $tmp_money = $v['max_money_f'];//不能超过最高值
                    }
                }else{
                    $tmp_money = bcdiv(bcmul($dv['product_money'], $result, 3), $min_money, 2);//商品金额除以最低金额，得到比例，计算本次单个商品优惠
                }
                $a_money = bcsub($a_money, $tmp_money, 2);//得出剩余的优惠额度
                if ($a_money > 0 && $a_money < 0.05) {
                    $tmp_money = bcadd($tmp_money, $a_money, 2);//最后一分钱  给最后一个商品
                }

                if ($is_share == 0) {//不同享
                    if ($this->product_data[$dk]['no_share'] < $tmp_money) {//当前活动金额大
                        $this->product_data[$dk]['no_share'] = $tmp_money;
                        $this->active_data['no_share'][$dk] = array('id' => $active_id, 'money' => $tmp_money);//不同享要求覆盖
                    }
                } elseif ($is_share == 1) {//同享优惠金额相加
                    $last_money = bcsub($this->product_data[$dk]['total_money'], bcadd($this->product_data[$dk]['no_share'], $this->product_data[$dk]['share'], 2), 2);
                    $tmp_money = $last_money > $tmp_money ? $tmp_money : $last_money;//同享的活动 得判断剩余的金额

                    $this->product_data[$dk]['share'] = bcadd($this->product_data[$dk]['share'], $tmp_money, 2);
                    $this->active_data['share'][$dk][$active_id] = $tmp_money;//同享要所有
                }
                if($join_again==0){//只能享受一次, 只有一件物品才能享受
                    return $result;
                }
            }

            return $result;
        }
        return 0;
    }

    /*
     * @desc 获取 活动相对于 盒子的 剩余次数
     * @param $active_id int 活动id
     * @param $equipment_id str 设备id
     * @param $active_limit int 次数
     * @param $active_type int 活动类型
     * */
    public function get_active_limit($active_id, $equipment_id=0, $active_limit=9999999, $active_type=''){
        $day = $active_type==3?':'.date('Y-m-d'):'';
        $this->load->driver('cache');
        $key = 'active_limit:'. ':' . $active_id . ':' . $equipment_id.$day;
        if(!$this->cache->redis->exists($key)){
            $this->cache->redis->save($key, $active_limit, 0);
        }
        $cache_num = $this->cache->redis->get($key);
        return intval($cache_num);
    }

    /*
     * @desc 更新 活动相对于 盒子的 剩余次数
     * @param $active_id int 活动id
     * @param $equipment_id str 设备id
     * @param $active_type int 活动类型
     * */
    public function update_active_limit($active_id, $equipment_id=0, $active_type=''){
        $day = $active_type==3?':'.date('Y-m-d'):'';
        $this->load->driver('cache');
        $key = 'active_limit:'. ':' . $active_id . ':' . $equipment_id.$day;
        $cache_num = $this->cache->redis->get($key);
        $cache_num = intval($cache_num) - 1;
        $this->cache->redis->save($key, $cache_num, 0);
    }
}

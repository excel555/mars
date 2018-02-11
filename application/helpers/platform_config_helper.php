<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */
/*
function get_platform_config($id){
    $ci =&get_instance();

    $ci->config->load('platform_config', TRUE);
    $ret_config = $ci->config->item('platform_config');

    $ci->load->config('platform',true);
    if($id) {
        $key = $ci->config->item('platform_redis_key_ex', 'platform') . $id;
        $redis_app_id = $ci->cache->redis->hGet($key, 'ali_appid');
        if (!$redis_app_id) {
            $params = array(
                'timestamp' => time() . '000',
                'source' => 'api',
                'id' => $id,
            );
            $url = $ci->config->item('platform_update_cache_url', 'platform');
            $secret = $ci->config->item('platform_secret', 'platform');

            $params['sign'] = create_platform_host_sign($params, $secret);

            $options['timeout'] = 100;
            $result = HttpRequest::curl($url, $params);
            $result = !empty($result) ? json_decode($result, 1) : "";
            if (isset($result['code']) == 200) {
                $replace_filed['app_id'] = $result['ali_appid'];
                $replace_filed['alipay_public_key'] = $result['ali_secret'];
                $replace_filed['pay_sell_id'] = $result['pay_user_id'];
                $replace_filed['pay_succ_tpl_id'] = $result['pay_succ_tpl_id'];
                $replace_filed['pay_fail_tpl_id'] = $result['pay_fail_tpl_id'];
                $replace_filed['refund_tpl_id'] = $result['refund_tpl_id'];
                $replace_filed['notify_tpl_id'] = $result['notify_tpl_id'];
                //wechat
                $replace_filed['wechat_appid'] = $result['wechat_appid'];
                $replace_filed['wechat_secret'] = $result['wechat_secret'];
                $replace_filed['wechat_mchid'] = $result['wechat_mchid'];
                $replace_filed['wechat_key'] = $result['wechat_key'];
                $replace_filed['wechat_planid'] = $result['wechat_planid'];
                $replace_filed['wechat_pay_succ_tpl_id'] = $result['wechat_pay_succ_tpl_id'];
                $replace_filed['wechat_pay_fail_tpl_id'] = $result['wechat_pay_fail_tpl_id'];
                $replace_filed['wechat_refund_tpl_id'] = $result['wechat_refund_tpl_id'];
                $replace_filed['wechat_notify_tpl_id'] = $result['wechat_notify_tpl_id'];
            }
        }else{
            $replace_filed['app_id'] = $redis_app_id;
            $replace_filed['alipay_public_key'] = $ci->cache->redis->hGet($key, 'ali_secret');
            $replace_filed['pay_sell_id'] = $ci->cache->redis->hGet($key, 'pay_user_id');
            $replace_filed['pay_succ_tpl_id'] = $ci->cache->redis->hGet($key, 'pay_succ_tpl_id');
            $replace_filed['pay_fail_tpl_id'] = $ci->cache->redis->hGet($key, 'pay_fail_tpl_id');
            $replace_filed['refund_tpl_id'] = $ci->cache->redis->hGet($key, 'refund_tpl_id');
            $replace_filed['notify_tpl_id'] = $ci->cache->redis->hGet($key, 'notify_tpl_id');
            //wechat
            $replace_filed['wechat_appid'] = $ci->cache->redis->hGet($key, 'wechat_appid');
            $replace_filed['wechat_secret'] = $ci->cache->redis->hGet($key, 'wechat_secret');
            $replace_filed['wechat_mchid'] = $ci->cache->redis->hGet($key, 'wechat_mchid');
            $replace_filed['wechat_key'] = $ci->cache->redis->hGet($key, 'wechat_key');
            $replace_filed['wechat_planid'] = $ci->cache->redis->hGet($key, 'wechat_planid');
            $replace_filed['wechat_pay_succ_tpl_id'] = $ci->cache->redis->hGet($key, 'wechat_pay_succ_tpl_id');
            $replace_filed['wechat_pay_fail_tpl_id'] = $ci->cache->redis->hGet($key, 'wechat_pay_fail_tpl_id');
            $replace_filed['wechat_refund_tpl_id'] = $ci->cache->redis->hGet($key, 'wechat_refund_tpl_id');
            $replace_filed['wechat_notify_tpl_id'] = $ci->cache->redis->hGet($key, 'wechat_notify_tpl_id');
        }
        if(count($replace_filed)>0){
            foreach ($replace_filed as $k=>$v){
                if(!empty($v) && !$v)
                {
                    $ret_config[$k] = $v;
                }
            }
        }
    }
    return $ret_config;
}
function get_platform_config_by_device_id($device_id){
    //使用默认配置
    $ci =&get_instance();
    $id = 0;
    if($device_id){
        //获取平台ID
        $ci->load->model('equipment_model');
        $rs_eq = $ci->equipment_model->get_info_by_equipment_id($device_id);
        $id = isset($rs_eq['platform_id']) ? $rs_eq['platform_id'] : 0;
    }
    $ret_config = get_platform_config($id);
    return $ret_config;
}
*/
function get_platform_config_by_device_id($device_id){
    //使用默认配置
    $ci =&get_instance();
    $ci->config->load('platform_config', TRUE);
    $defalut_config = $ci->config->item('platform_config');
//    if($device_id){
//        //获取平台ID
//        $ci->load->config('platform',true);
//        $key = $ci->config->item('platform_redis_new_key_ex', 'platform')."d_".$device_id;
//        $cache = $ci->cache->redis->get($key);
//        if(!$cache){
//            $key_pd = $ci->config->item('platform_redis_new_key_ex', 'platform')."pd_".$device_id;//记录设备对应的platform_id
//            $cache_pd = $ci->cache->redis->get($key_pd);
//            if($cache_pd){
//                $platform_id = $cache_pd;
//            }else{
//                $ci->load->model('equipment_model');
//                $rs_eq = $ci->equipment_model->get_info_by_equipment_id($device_id);
//                $platform_id = isset($rs_eq['platform_id']) ? $rs_eq['platform_id'] : 0;
//                if($platform_id){
//                    $ci->cache->redis->save($key_pd,$platform_id,5*60);//5分钟
//                }
//            }
//            return get_platform_config($platform_id);
//        }else{
//            $custom_config = json_decode($cache,1);
//            $ret = merge_replace_config($defalut_config,$custom_config);
//            return $ret;
//        }
//    }else{
//        return $defalut_config;
//    }
}

function get_platform_config($id){
    $ci =&get_instance();
    $ci->config->load('platform_config', TRUE);
    return $defalut_config = $ci->config->item('platform_config');
//    if(!$id || $id == "0" || $id == 0){
//      return $defalut_config;
//    }
//    $ci->load->config('platform',true);
//    $key = $ci->config->item('platform_redis_new_key_ex', 'platform')."p_".$id;
//    $cache = $ci->cache->redis->get($key);
//
//    if(!$cache){
//        //找不到cache,则刷新缓存
//        $url = $ci->config->item('platform_update_new_cache_url', 'platform');
//        $secret = $ci->config->item('platform_secret', 'platform');
//
//        $params = array(
//            'timestamp' => time() . '000',
//            'source' => 'api',
//        );
//        $params['sign'] = create_platform_host_sign($params, $secret);
//        $options['timeout'] = 100;
//        $result = HttpRequest::curl($url, $params);
//        $result = $result ? json_decode($result,1) : "";
//        if (isset($result['code']) == 200) {
//            $custom_config = $result[$key];
//            $ret = merge_replace_config($defalut_config,$custom_config);
//            return $ret;
//        }else{
//            return $defalut_config;
//        }
//    }elseif($cache == "defalut" ){
//        return $defalut_config;
//    }else{
//        $custom_config = json_decode($cache,1);
//        $ret = merge_replace_config($defalut_config,$custom_config);
//        return $ret;
//    }
}

function merge_replace_config($defalut_config,$custom_config = array()){
    $arr_m = array();
    foreach ($custom_config as $m){
        $arr_m = array_merge($arr_m,$m);
    }
    foreach ($arr_m as $k=>$c){
        $defalut_config[$k] = $c;
    }
    return $defalut_config;
}

function create_platform_host_sign($params,$secret)
{
    if (isset($params['sign'])) {
        unset($params['sign']);
    }
    ksort($params);
    $query = '';
    foreach ($params as $k => $v) {
        $query .= $k . '=' . $v . '&';
    }
    $sign = md5(substr(md5($query . $secret), 0, -1) . 'P');
    return $sign;
}

function get_3rd_partner_id_by_device_id($device_id,$refer='alipay'){
    $config = get_platform_config_by_device_id($device_id);
    switch ($refer){
        case 'alipay':
            $partner_id = $config['mapi_partner'];
            break;
        case 'wechat':
            $partner_id = $config['wechat_mchid'];
            break;
        default :
            $partner_id = $config['mapi_partner'];
            break;
    }
    return $partner_id;

}

/**
 * @param $device_id
 * @return bool|int
 * 是否使用ISV模式
 */
function get_isv_platform($device_id){
    $ci =&get_instance();
    $ci->load->config('platform',true);
    $key_pd = $ci->config->item('platform_redis_new_key_ex', 'platform')."pd_".$device_id;//记录设备对应的platform_id
    $cache_pd = $ci->cache->redis->get($key_pd);
    if($cache_pd){
        $platform_id = $cache_pd;
    }else{
        $ci->load->model('equipment_model');
        $rs_eq = $ci->equipment_model->get_info_by_equipment_id($device_id);
        $platform_id = isset($rs_eq['platform_id']) ? $rs_eq['platform_id'] : 0;
        if($platform_id){
            $ci->cache->redis->save($key_pd,$platform_id,5*60);//5分钟
        }
    }
    return $platform_id == KOUBEI_PLATFORM_ID ? $platform_id : false;
}

/**
 * 检测设备是否能使用某种方式开门
 * @param $device_id
 * @param $refer
 * @return array|bool
 */
function check_device_open_refer($device_id,$refer){
    if($refer == 'fruitday'){
        $refer = 'fruitday-app';
    } 
    $config = get_platform_config_by_device_id($device_id);
    if($refer && is_array($config['refers']) && in_array($refer,$config['refers'])){
        return true;
    }else{
        return array('status'=>false,'redirect_url'=>$config['error_url'],'msg'=>$config['error_msg']);
    }
}

/**
 * 检测设备是否能使用余额
 * @param $device_id
 * @return array|bool
 */
function check_device_use_yue($device_id){
    $config = get_platform_config_by_device_id($device_id);
    if(intval($config['use_yue']) == 1){
        return true;
    }else{
        return false;
    }
}
function get_device_qr_common_pr($device_id){
    $config = get_platform_config_by_device_id($device_id);
    return $config['common_pr'];
}

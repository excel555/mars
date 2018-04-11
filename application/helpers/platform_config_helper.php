<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

function get_platform_config_by_device_id($device_id){
    //使用默认配置
    $ci =&get_instance();
    $ci->config->load('platform_config', TRUE);
    return $defalut_config = $ci->config->item('platform_config');
}

function get_platform_config($id){
    $ci =&get_instance();
    $ci->config->load('platform_config', TRUE);
    return $defalut_config = $ci->config->item('platform_config');
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
    return false;
}

/**
 * 检测设备是否能使用某种方式开门
 * @param $device_id
 * @param $refer
 * @return array|bool
 */
function check_device_open_refer($device_id,$refer){
    return true;
}

/**
 * 检测设备是否能使用余额
 * @param $device_id
 * @return array|bool
 */
function check_device_use_yue($device_id){
    return false;
}
function get_device_qr_common_pr($device_id){
    $config = get_platform_config_by_device_id($device_id);
    return $config['common_pr'];
}

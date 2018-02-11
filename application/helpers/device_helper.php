<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 * 设备帮助类
 */

/**
 * 激活设备在线
 * @param $device_id
 */
function active_device_helper($device_id)
{
    $ci = get_instance();
//    $ci->cache->redis->sadd(HEART_DEVICES_SET,$device_id); // 添加成功，返回1
    $key_last = str_replace('device',$device_id,DEVICES_LAST_STATUS_KEY);
    $key_heart = str_replace('device',$device_id,HEART_DEVICES_STATUS_KEY);
    $ci->cache->redis->save($key_last,date("Y-m-d H:i:s")); //记录最后一次心跳时间
    $ci->cache->redis->save($key_heart,'online',65); //65s后自动释放
}

/**
 * 设备失联
 * @param $device_id
 */
function dead_device_helper($device_id)
{
    $ci = get_instance();
    $ci->cache->redis->srem(HEART_DEVICES_SET,$device_id); // 返回 被删除的个数
}

function device_last_status_helper($device_id){
    $ci = get_instance();
    $key_last = str_replace('device',$device_id,DEVICES_LAST_STATUS_KEY);
    $key_heart = str_replace('device',$device_id,HEART_DEVICES_STATUS_KEY);
    $exist = $ci->cache->redis->get($key_heart);
    if($exist == 'online'){
        return 'online';
    }else{
        return $ci->cache->redis->get($key_last); //最后一次心跳时间
    }
}
function sismember($device_id){
    $ci = get_instance();
//    $ci->cache->redis->sismember();
}

<?php
/**
 * Created by PhpStorm.
 * User: Excel
 * Date: 17/09/2017
 * Time: 12:39
 */

function push_mq_for_redis($list_name,$value){
    $ci =&get_instance();
    return $ci->cache->redis->rPush($list_name, $value);
}

function pop_mq_for_redis($list_name){
    $ci =&get_instance();
    return $ci->cache->redis->lPop($list_name);
}
function length_mq_for_redis($list_name){
    $ci =&get_instance();
    return $ci->cache->redis->lLen($list_name);
}
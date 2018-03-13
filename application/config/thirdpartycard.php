<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');
/**
 * 第三方领券配置文件
 * Created by PhpStorm.
 * User: tangcw
 * Date: 2/5/18
 * Time: 21:57
 */

$config['thirdparty'] = array(
    'cityshop'=>array(
        'click_type'=>'mobile', //直接领取get,跳出手机号领取mobile
        'code'=>'bar',  //条码bar,二维码qr
        'aes_key'=>'SCrQ4uR4H1R81bKJqwl5oeGJA2OOdzYbohFK',
        'validate_url'=>'http://pre.haocaiji.shop/index.php?route=openapi/coupon/gettoken',
        'coupon_url'=>'http://pre.haocaiji.shop/index.php?route=openapi/coupon/sendCoupon',
        'used_url'=>'http://pre.haocaiji.shop/index.php?route=openapi/coupon/checkStatus',
        'coupon_id'=>'3542',
        'send_err_msg'=>array(
            '0' => '其他',
            '1' => 'success',
            '-1' => '非法访问',
            '-2' => '访问失效',
            '-3' => '非法手机号',
            '-4' => '非法优惠券规则ID',
            '-5' => '手机号为空',
            '-6' => '已经领取',
            '-7' => '券码不能为空',
        ),  
    ),
);
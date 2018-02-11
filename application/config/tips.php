<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require('env_config.php');

$config['open_door_succ_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，关门后系统将会自动扣款！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '开门成功，请放心购买',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 1
);
$config['open_door_succ_admin_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox，配送员操作上下货！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '开门成功，请放心操作',
    'keyword3' => '',
    'keyword4' => ''
);
$config['open_door_succ_admin_finish_msg'] = array(
    'url' => $env_config['mapi_box_host'] . '/public/deliver_log.html?deliver_no=',
    'action' => '查看上下货日志',
    'first' => '配送员操作上下货！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '上下货完成',
    'keyword3' => '',
    'keyword4' => ''
);
$config['box_trouble_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，盒子发生故障，请稍后再试，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '设备故障，请稍后重试',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 2
);
$config['box_init_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，盒子正在初始化，请稍后再试，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '盒子正在初始化，请稍后重试',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 2
);
$config['permission_error_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox，当前配送员无上下货操作权限，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '您没有权限开门上下货',
    'keyword3' => '',
    'keyword4' => ''
);
$config['box_busy_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，盒子忙碌中，请稍后再试，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '设备正在使用中，请稍后重试',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 2
);
$config['no_pay_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/orders.html?status=0',
    'action' => '点击支付',
    'first' => '感谢您使用魔盒CityBox购买商品，您有未支付的订单，请先支付，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '您存在未支付的订单，请点击支付',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 2
);
$config['user_black_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，您已被限制购买，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '您已进入黑名单，请联系管理员',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 1,
    'biz_type' => 2
);
$config['pay_succ_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击查看订单',
    'first' => '感谢您使用魔盒CityBox购买商品，系统自动扣款成功！订单号：',
    'remark' => '魔盒期待着与您再次邂逅',
    'keyword1' => "￥",
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 3,
    'biz_type' => 1
);
$config['pay_fail_msg'] = array(
    'url' => $env_config['mapi_box_host'] . '/public/sign.html',
    'action' => '点击开通免密支付',
    'first' => '感谢您使用魔盒CityBox，购买商品前需开通支付宝免密支付，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '您尚未开通免密支付，请安全开通此功能',
    'keyword3' => '',
    'keyword4' => ''
);
/**
 * 用户余额不足，正常都是切换支付方式
 */
$config['pay_blance_not_enough_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '已更改支付设置，点击支付',
    'first' => '感谢您使用魔盒CityBox购买商品，您的账号余额不足，请更改支付设置--扣款顺序，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => ''
);

/**
 * 产品额度超限
 */
$config['product_amount_limit_error_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击手动支付',
    'first' => '感谢您使用魔盒CityBox购买商品，您的购买的金额已超过免密支付的额度，请点击支付，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '单笔订单超过200元需手动支付',
    'keyword3' => '',
    'keyword4' => ''
);
//微信支付- 每天5次,金额，超过限制次数
$config['wechat_rule_limit'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击手动支付',
    'first' => '感谢您使用魔盒CityBox购买商品，交易金额或次数超出限制，请点击支付，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '交易金额或次数超出限制，请手动支付',
    'keyword3' => '',
    'keyword4' => ''
);

$config['pay_fail_unkonw_msg'] = array(
    'url' => $config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击手动支付',
    'first' => '感谢您使用魔盒CityBox，订单支付失败，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 3,
    'biz_type' => 2
);

$config['pay_inprocess'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击查看订单',
    'first' => '感谢您使用魔盒CityBox购买商品，您的订单下单成功支付处理中，回复短信确认支付，也点击查看订单手动支付，如有疑问请在首页联系我们，谢谢！',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => ''
);

$config['refund_succ_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击查看订单',
    'first' => '感谢您使用魔盒CityBox购买商品，您的申请退款已审核通过。退款金额将会原路返回，若为银行卡支付，受银行结算系统限制，通常需要3-5个工作日到账。',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => '用户申请退款',
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 4,
    'biz_type' => 1
);
/**
 * 退款申请驳回
 */
$config['refund_against_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '点击查看订单',
    'first' => '感谢您使用魔盒CityBox购买商品，您的申请退款已审核已被驳回。',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => '用户申请退款',
    'keyword2' => '',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 4,
    'biz_type' => 2
);
/**
 * 开关门超时
 */
$config['over_time_door_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，超时未打开门，售货机已自动关门。',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '超时未开门，已自动关门',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 2,
    'biz_type' => 2
);

$config['close_door_tip_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，售货机已关门成功',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '售货机已关门成功',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 2,
    'biz_type' => 1
);

/*
 * 关爱通退款消息
 * */

$config['gat_refund_succ_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，您已成功退款',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '退款成功',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 4,
    'biz_type' => 1
);

$config['gat_refund_error_msg'] = array(
    'url' => '',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，您申请的退款没有成功，如有疑问请在首页联系我们，谢谢！',
    'remark' => '如有疑问请在首页联系我们，谢谢！',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '退款失败',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 4,
    'biz_type' => 2
);

$config['gat_pay_succ_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox购买商品，扣款成功！订单号：',
    'remark' => '魔盒期待着与您再次邂逅',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '扣款成功',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 3,
    'biz_type' => 1
);
$config['gat_pay_fail_msg'] = array(
    'url' => $env_config['mapi_box_host'].'/public/order.html?orderId=',
    'action' => '',
    'first' => '感谢您使用魔盒CityBox，扣款失败！订单号：',
    'remark' => '感谢您的使用，祝您购物愉快',
    'keyword1' => date("Y-m-d H:i:s"),
    'keyword2' => '扣款失败',
    'keyword3' => '',
    'keyword4' => '',
    'msg_type' => 3,
    'biz_type' => 2
);

$open_door_tip_msg['black'] = "您已经被限制购买";
$open_door_tip_msg['no_pay'] = "您有未支付的订单";
$open_door_tip_msg['device_init'] = "设备正在初始化";
$open_door_tip_msg['device_dead'] = "设备正在维护中";
$open_door_tip_msg['device_trouble'] = "设备发生故障";
$open_door_tip_msg['device_busy'] = "设备正在使用中";
$open_door_tip_msg['no_permission'] = "你没有操作权限";
$open_door_tip_msg['open_fail'] = "开门失败";
$open_door_tip_msg['succ'] = "开门成功";
$open_door_tip_msg['defalut'] = "系统错误";
$config['open_door_tip_msg'] = $open_door_tip_msg;
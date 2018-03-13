<?php
require('env_config.php');
//应用ID
$config['app_key'] = $env_config['app_key'];
$config['secret'] = $env_config['secret'];
$config['host_device'] = $env_config['host_device'];
//请求开门
$config['open_door_url'] = $config['host_device']."/command/openDoor";
//请求盘点
$config['stock_url'] = $config['host_device']."/command/inventory";
//请求设备信息
$config['device_info_url'] = $config['host_device']."/command/getDeviceInfo";
//获取设备列表
$config['device_list_url'] = $config['host_device']."/box/list";


//-------------北京研发的设备配置-----------------//
//应用ID
$config['new_1_app_key'] = $env_config['new_app_key'];
$config['new_1_secret'] = $env_config['new_secret'];
$config['new_1_host_device'] = $env_config['new_host_device'];
//请求开门
$config['new_1_open_door_url'] = $config['new_1_host_device']."/command/openDoor";
//请求盘点
$config['new_1_stock_url'] = $config['new_1_host_device']."/command/inventory";
//请求设备信息
$config['new_1_device_info_url'] = $config['new_1_host_device']."/command/getDeviceInfo";


//扫码设备
//应用ID
$config['new_2_app_key'] = $env_config['new2_app_key'];
$config['new_2_secret'] = $env_config['new2_secret'];
$config['new2_host_device'] = $env_config['new2_host_device'];
//请求开门
$config['new_2_open_door_url'] = $config['new2_host_device']."/command/openDoor";
//请求盘点
$config['new_2_stock_url'] = $config['new2_host_device']."/command/inventory";
//请求设备信息
$config['new_2_device_info_url'] = $config['new2_host_device']."/command/getDeviceInfo";


//视觉设备
//应用ID
$config['new_3_app_key'] = $env_config['new3_app_key'];
$config['new_3_secret'] = $env_config['new3_secret'];
$config['new3_host_device'] = $env_config['new3_host_device'];
//请求开门
$config['new_3_open_door_url'] = $config['new3_host_device']."/command/openDoor";
//请求盘点
$config['new_3_stock_url'] = $config['new3_host_device']."/command/inventory";
//请求设备信息
$config['new_3_device_info_url'] = $config['new3_host_device']."/command/getDeviceInfo";


//视觉设备 数烨
//应用ID
$config['new_4_app_key'] = $env_config['new4_app_key'];
$config['new_4_secret'] = $env_config['new4_secret'];
$config['new4_host_device'] = $env_config['new4_host_device'];
//请求开门
$config['new_4_open_door_url'] = $config['new4_host_device']."/command/openDoor";
//请求盘点
$config['new_4_stock_url'] = $config['new4_host_device']."/command/inventory";
//请求设备信息
$config['new_4_device_info_url'] = $config['new4_host_device']."/command/getDeviceInfo";
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
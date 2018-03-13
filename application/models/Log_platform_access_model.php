<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 17/4/11
 * Time: 下午2:30
 * 记录开关门操作日志
 */
if (! defined ( 'BASEPATH' ))	exit ( 'No direct script access allowed' );

class Log_platform_access_model extends MY_Model
{
    function __construct()
    {
        parent::__construct();
    }

    public function table_name()
    {
        return 'log_platform_access';
    }

    function add_log($data){
        return $this->insert($data);
    }
}
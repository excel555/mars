<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

/**
 * 短信、邮件接口
 * 文档链接 http://notify.fruitday.com/
 */


require_once FCPATH . 'application/helpers/http_request_helper.php';
/**
 * 发送短信
 * @param $req_data
 * {
    "mobile": "18621681531",
    "message": "蔡昀辰在测试SMS单条推送"
    }
 * @return bool
 * @throws Exception
 */
function send_msg_execute($req_data)
{
    global $config;
    require APPPATH . 'config/sms.php';

    $accounts = $config['sms_notify_accounts'][$config['select_notify_pipe']];
    if(!isset($accounts) || !$accounts  || $config['select_market_pipe'] == 'fruitday'){
        $secret = $config['sms_secret'];
        $sms_url = $config['sms_url'].'&sign='.sms_sign($req_data,$secret);
        $result = HttpRequest::curl($sms_url, $req_data, 1);
        if(!empty($result)){
            $result = json_decode($result,true);
            write_log('发送短信结果'.$sms_url.",".var_export($req_data,1).",".var_export($result,1),'info');
            if(isset($result['code']) && $result['code'] == 200){
                return TRUE;
            }
        }else{
            write_log('发送短信失败'.$sms_url.",".$req_data.",".$result,'crit');
        }
    }else if(isset($accounts['url'])){
        $req_data = json_decode($req_data,true);
        $mobiles = explode(',',$req_data['mobile']);
        $content  = $req_data['message'];
        $data = array(
            'userId'=>$accounts['account'],
            'password'=>$accounts['pwd'],
            'pszMobis'=>$req_data['mobile'],
            'pszMsg'=>$content,
            'iMobiCount'=>count($mobiles),
            'pszSubPort'=>'*',
            'MsgId'=>time().rand(10000,99999999),
        );
        $sms_url = $accounts['url'];
        $result = HttpRequest::curl_get($sms_url, $data);
        if(!empty($result)){
            $result = xml2array($result);
            write_log('发送短信结果'.$sms_url.",".var_export($req_data,1).",".var_export($result,1),'info');
            if(isset($result[0]) && $result[0] <= 0 ){
                return TRUE;
            }
            return TRUE;
        }else{
            write_log('发送短信失败'.$sms_url.",".$req_data.",".$result,'crit');
        }
    }else{
        write_log('发送短信失败，未配置发送短信路径','crit');
    }
    return FALSE;
}

function send_market_msg_execute($req_data)
{
    global $config;
    require APPPATH . 'config/sms.php';
    $accounts = $config['sms_market_accounts'][$config['select_market_pipe']];

    $req_data = json_decode($req_data,true);
    $mobiles = explode(',',$req_data['mobile']);
    $content  = $req_data['message'];
    $data = array(
        'userId'=>$accounts['account'],
        'password'=>$accounts['account'],
        'pszMobis'=>$req_data['mobile'],
        'pszMsg'=>$content,
        'iMobiCount'=>count($mobiles),
        'pszSubPort'=>'*',
        'MsgId'=>time().rand(10000,99999999),
    );
    $sms_url = $accounts['url'];
    $result = HttpRequest::curl_get($sms_url, $data);

    if(!empty($result)){
        $result = xml2array($result);
        if(isset($result[0]) && $result[0] >= 0 ){
            return TRUE;
        }else{
            return TRUE;
        }
    }else{
        write_log('发送短信失败'.$sms_url.",".$req_data.",".$result,'crit');
    }
    return FALSE;
}


/**
 * 发送邮件
 * @param $req_data
 * {
    "email":        ["caiyunchen@fruitday.com", "caiyunchen@fruitday.com"],
    "title":        "email推送测试",
    "message":      "蔡昀辰在测试EMAIL批量推送"
    }
 * @return bool
 * @throws Exception
 */
function send_email_execute($req_data)
{
    global $config;
    require APPPATH . 'config/sms.php';
    $secret = $config['sms_secret'];
    $email_url = $config['email_url'].'&sign='.sms_sign($req_data,$secret);
    $result = HttpRequest::curl($email_url, $req_data, 1);
    write_log('发送邮件'.$email_url.",".$req_data.",".$result);
    if(!empty($result)){
        $result = json_decode($result,true);
        if(isset($result['code']) && $result['code'] == 200){
            return TRUE;
        }
    }
    return FALSE;
}

function sms_sign($params,$secret) {
    //字符串拼接密钥后md5加密,去处最后一位再拼接”s"，再md5加密
    return md5(substr(md5($params.$secret), 0,-1)."s");
}
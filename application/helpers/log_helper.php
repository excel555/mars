<?php
/**
 * 日志帮助类
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 5/15/17
 * Time: 18:41
 */
/**
 * 日记记录：
 * @param $content 日志内容
 * @param string $log_type crit 严重【业务层面/邮件短信通知】，error 错误，debug 调试，info 记录【业务层面，支付信息/必须记录】
 */
function write_log($content, $log_type = 'error') //todo 暂时改成error
{
    $text = log_characet($content);
    if ($log_type === 'error' OR $log_type === 'debug') {
        log_message($log_type, $text);
    } else if ($log_type === 'crit') {
        //记录发送告警邮件等...
        $ci = get_instance();
        $ci->load->config('log', TRUE);
        $path = $ci->config->item('custom_log_path', 'log');
//        $email_rec = $ci->config->item('crit_log_email_rec', 'log');
//        $title = $ci->config->item('crit_log_email_subject', 'log');
//        $message = $ci->config->item('crit_log_email_content', 'log');
        $text = date("Y-m-d H:i:s") . "  " . $text . "\r\n";
        error_log($text, 3, $path.date("Y-m-d")."-crit.log");

//        $email_data = array(
//            'email' => explode(',', $email_rec),
//            'title' => $title.'['.$_SERVER['HTTP_HOST'].']',
//            'message' => $message . $text,
//        );
//        $ci->load->helper('sms_send');
//        send_email_execute(json_encode($email_data));
    } else if ($log_type === 'info') {
        //必须记录
        $ci = get_instance();
        $ci->load->config('log', TRUE);
        $path = $ci->config->item('custom_log_path', 'log');
        $text = date("Y-m-d H:i:s") . "  " . $text . "\r\n";
        error_log($text, 3, $path.date("Y-m-d")."-info.log");
    } else if ($log_type === 'feedback') {
        //必须记录
        $ci = get_instance();
        $ci->load->config('log', TRUE);
        $path = $ci->config->item('custom_log_path', 'log');
        $text = date("Y-m-d H:i:s") . "  " . $text . "\r\n";
        error_log($text, 3, $path.date("Y-m-d")."-feedback.log");
    }
}

//转换编码
function log_characet($data)
{
    if (!empty ($data)) {
        $fileType = mb_detect_encoding($data, array(
            'UTF-8',
            'GBK',
            'GB2312',
            'LATIN1',
            'BIG5'
        ));
        if ($fileType != 'UTF-8') {
            $data = mb_convert_encoding($data, 'UTF-8', $fileType);
        }
    }
    return $data;
}


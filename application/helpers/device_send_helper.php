<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 15:04
 */

/**
 * 零售机执行接口请求
 */


require_once FCPATH . 'application/helpers/http_request_helper.php';
/**
 * 开门
 * @param $req_data
 * @return mixed
 */
function open_door_request_execute($req_data,$is_new=0)
{
    return send_device_request("open_door_url", $req_data,$is_new);

}

/**
 * 盘点
 * @param $request_data
 * @return mixed
 * @throws Exception
 *
 */
function stock_request_execute($request_data,$is_new=0)
{
    return send_device_request("stock_url", $request_data,$is_new);
}

/**
 * 设备信息
 * @param $request_data
 * @return mixed
 */
function device_info_request_execute($request_data,$is_new=0)
{
    return send_device_request("device_info_url", $request_data,$is_new);
}

function device_list_request_execute($request_data)
{
    $rs = send_device_request("device_list_url", $request_data);
    if($rs){
        return json_decode($rs, TRUE);
    }
}

/**
 * @param $type
 * @param $req_data
 * @param int $is_new 如果是北京的设备，则为is_new = 1
 * @return mixed|string
 */
function send_device_request($type, $req_data,$is_new=0)
{
    $ci = get_instance();
    $ci->load->config('device', TRUE);
    $pre = '';
    if($is_new > 0){
        $pre = 'new_'.$is_new."_";
    }
    $app_key = $ci->config->item($pre.'app_key', 'device');
    $secret = $ci->config->item($pre.'secret', 'device');
    $url = $ci->config->item($pre.$type, 'device');
    $request_id = uuid();//md5(date("YmdHis").rand(10000,99999));
    $req = array(
        'requestId'=> $request_id,
        'appkey'=>$app_key,
        'sign'=>md5($app_key.$request_id.$secret), //请求签名，具体算法：MD5(appkey+ requestId + secret )secret由蚂蚁分配
        'params'=>$req_data
    );
    if(!$req_data){
        unset($req['params']);
    }
    $ci->load->model("request_msg_log_model");
    $data = array(
        'box_no'=>$req_data['deviceId'],
        'req_time'=>date("Y-m-d H:i:s"),
        'req_body'=>json_encode($req),
        'req_type'=>$type,
        'response'=>'',
    );
    $insert_id = $ci->request_msg_log_model->insert_log($data);
    write_log("device url: " . $url);
    write_log("device req: " . var_export($req, true));
    $result = HttpRequest::curl($url, json_encode($req), 1);
    write_log("device response: " . var_export($result, true));
    $ci->request_msg_log_model->update_log($result,$insert_id);
    return $result;
}

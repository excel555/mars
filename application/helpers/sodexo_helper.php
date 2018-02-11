<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/1/3
 * Time: 下午4:53
 */
include(FCPATH.'application/libraries/zmxy/WebUtil.php');

/**
 * 获取索迪斯扫码签名
 * @param $device_id string 设备id
 * @param $code  string 用户支付码
 * @param $open_id string 用户唯一凭证 同时也是用户手机号和
 * @return string
 */
function get_sign($device_id, $code, $open_id){
    $ci = get_instance();
    $ci->load->config('sodexo', TRUE);
    $client_secret = $ci->config->item('sodexo_client_secret', 'sodexo');
    return md5('0000'.$device_id.$code.$client_secret.$open_id);
}

/**
 * 索迪斯支付  https://uat-epass.sdxpass.com/server/integration/payment
 * @param  $data array  参数
 * @return array
 */
function sodexo_pay($data){
    $config = get_sodexo_config($data['box_no']);
    $param['transactionId'] = strval($data['pay_code']);
    $param['amount']        = floatval(bcadd($data['amount'], 0, 2));
    $requestUrl = $config['sodexo_url'].'/server/integration/payment';
    $client = new SodexoClient();
    $rs = $client->execute($requestUrl, $param, $config['header']);
    write_log("sodexo request: ".var_export($data,true));
    write_log("sodexo response: ".var_export($rs,true));
    if($rs['responseText'] == 'success'){
        return array('code'=>200);
    }
    return array('code'=>500);
}


/**
 * 获取支付码
 * @param $data array
 * @return array
*/

function getTransactionId($data){
    $config = get_sodexo_config($data['box_no']);
    $param['terminalId'] = $data['terminalId'];
    $param['mobile']     = $data['mobile'];
    $requestUrl = $config['sodexo_url'].'/server/integration/getTransactionId';
    $client = new SodexoClient();
    $rs = $client->execute($requestUrl, $param, $config['header']);
    write_log("sodexo TID request: ".var_export($data,true));
    write_log("sodexo TID response: ".var_export($rs,true));
    return $rs;
}

/**
 * @desc 查询支付状态
 * @param $data array
 * @return array
 *
*/
function checkPay($data){
    $config = get_sodexo_config($data['box_no']);
    $param['transactionId'] = $data['transactionId'];
    $param['mobile']     = $data['mobile'];
    $param['amount']     = (float) number_format($data['amount'], 2);
    $requestUrl = $config['sodexo_url'].'/server/integration/paymentStatus';
    $client = new SodexoClient();
    $rs = $client->execute($requestUrl, $param, $config['header']);
    write_log("sodexo check request: ".var_export($param,true));
    write_log("sodexo check response: ".var_export($rs,true));
    return $rs;
}

function get_sodexo_config($box_no){
    $ci = get_instance();
    $ci->load->config('sodexo', TRUE);
    if($box_no == '68805328909'){//测试地址
        $sodexo_url              = $ci->config->item('sodexo_uat_url', 'sodexo');
        $header['client-id']     = $ci->config->item('sodexo_uat_client_id', 'sodexo');
        $header['client-secret'] = $ci->config->item('sodexo_uat_client_secret', 'sodexo');
    }else{
        $sodexo_url              = $ci->config->item('sodexo_url', 'sodexo');
        $header['client-id']     = $ci->config->item('sodexo_client_id', 'sodexo');
        $header['client-secret'] = $ci->config->item('sodexo_client_secret', 'sodexo');
    }
    return array('header'=>$header, 'sodexo_url' => $sodexo_url);
}

class SodexoClient
{

    protected $format ='json';
    /**
     * 执行请求
     * @param $requestUrl 请求url
     * @return bool|mixed
     * @throws Exception
     */
    public function execute($requestUrl, $params, $header)
    {
        $header_str = array();
        foreach($header as $k=>$v){
            $header_str[] = $k.':'.$v;
        }
        //发起HTTP请求
        try {
            $resp = WebUtil::curl_new($requestUrl, $params, $header_str);
        } catch (Exception $e) {
            $this->logCommunicationError($requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
            return false;
        }

        //解析返回结果
        $respWellFormed = false;
        if ("json" == $this->format) {
            $respObject = json_decode($resp,true);
            $respWellFormed = true;
        }

        //返回的HTTP文本不是标准JSON，记下错误日志
        if (false === $respWellFormed) {
            $this->logCommunicationError( $requestUrl, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);
            return false;
        }
        return $respObject;
    }

    /**
     * 记录错误日志
     * @param $requestUrl
     * @param $errorCode
     * @param $responseTxt
     */
    private function logCommunicationError( $requestUrl, $errorCode, $responseTxt)
    {
        $localIp = isset ($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : "CLI";

        $logData = array(
            date("Y-m-d H:i:s"),
            $localIp,
            PHP_OS,
            $requestUrl,
            $errorCode,
            str_replace("\n", "", $responseTxt)
        );
        write_log(var_export($logData, 1));
    }
}

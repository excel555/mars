<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/1/16
 * Time: 下午2:48
 */
include(FCPATH.'application/libraries/zmxy/WebUtil.php');

/**
 * 获取签名
 * @param $param array 接口传入的参数
 * @return string
 */
function get_open_sign($param, $source=null)
{
    $ci = get_instance();
    $ci->load->config('open', TRUE);
    $open_secret = $ci->config->item("open_secret", 'open');
    $String = "";
    ksort($param);
    foreach ($param as $k => $v)
    {
        $String .= $k . "=" . $v . "&";
    }
    $source = $source?$source:$param['source'];
    $String = $String."&secret=".$open_secret[$source];
    return md5($String);
}

/**
 * 沙丁鱼支付
 * @param $data array
 *
*/
function sdy_pay($data){
    $ci = get_instance();
    $ci->load->config('open', TRUE);
    $sdy_url              = $ci->config->item('sdy_api_url', 'open');
    $goodsinfo = '';
    foreach($data['detail'] as $k=>$v){
        $goodsinfo .= '|'.$v['product_name'].','.$v['price'].','.$v['qty'];
    }
    $goodsinfo = trim($goodsinfo, '|');
    $param['vmc']   = $data['box_no'];
    $param['money'] = $data['money'];
    $param['orderno'] = $data['order_name'];
    $param['open_id'] = $data['open_id'];
    $param['goodsinfo'] = $goodsinfo;
    $param['rand']  = rand(1000,9999);
    $param['sign']  = get_open_sign($param, 'sdy');
    write_log("sdy pay request: ".var_export($param,true));
    $client = new OpenClient();
    $rs = $client->execute($sdy_url.'/openapi/Citybox/pay', $param);
    write_log("sdy pay response: ".var_export($rs,true));
    return $rs;
}


class OpenClient
{

    protected $format ='json';
    /**
     * 执行请求
     * @param $requestUrl 请求url
     * @return bool|mixed
     * @throws Exception
     */
    public function execute($requestUrl, $params)
    {

        //发起HTTP请求
        try {
            $resp = WebUtil::curl_for_xform($requestUrl, $params, 'UTF-8');
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
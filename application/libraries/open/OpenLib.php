<?php
/**
 * Created by PhpStorm.
 * User: zhangtao
 * Date: 18/1/18
 * Time: 下午12:22
 */

class OpenLib
{

    //秘钥
    protected $secret;

    //沙丁鱼api url
    protected $sdy_api_url;

    protected $format ='json';
    public $CI;

    public function __construct()
    {
        $this->CI=& get_instance();
        $this->CI->load->config('open', TRUE);
        $this->secret      = $this->CI->config->item("open_secret", 'open');
        $this->sdy_api_url = $this->CI->config->item('sdy_api_url', 'open');
    }

    /**
     * 获取签名
     * @param $param array 接口传入的参数
     * @return string
     */
    function get_open_sign($param, $source=null)
    {
        $open_secret = $this->secret;
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
     * @return array
     */
    function sdy_pay($data){
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
        $param['sign']  = $this->get_open_sign($param, 'sdy');
        write_log("sdy pay request: ".var_export($param,true));
        $rs = $this->execute($this->sdy_api_url.'/openapi/Citybox/pay', $param);
        write_log("sdy pay response: ".var_export($rs,true));
        return $rs;
    }


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
            $resp = $this->curl_for_xform($requestUrl, $params, 'UTF-8');
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

    private function curl_for_xform($url, $postFields = null, $charset) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);
        $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $charset);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        curl_setopt($ch, CURLOPT_POST, true);
        $str = '';
        foreach ($postFields as $k=>$v){
            $str .= $k.'='.$v."&";
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $str);
        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            $response = curl_error($ch);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                write_log('curl code:'.$httpStatusCode);
            }
        }

        curl_close($ch);
        return $response;
    }
}
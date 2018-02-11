<?php

/**
 *
 * Class GuanaitongClient
 * 关爱通类库
 */
include(FCPATH.'application/libraries/zmxy/WebUtil.php');

class GuanaitongClient
{
    //应用ID
    public $appId;

    public $appsecret;

    //网关
    public $gatewayUrl;

    //返回数据格式
    public $format = "json";

    //api版本
    public $apiVersion = "1.0.0";

    public $charset = "UTF-8";

    //签名类型
    protected $signType = "sha1";

    //参数
    private $signParam = 'sign';

    private $token_key = "guanaitong_citybox_token";
    private $expires = 3300;//55分钟调用一次

    /**
     * 执行请求
     * @param $request 请求对象
     * @return bool|mixed
     * @throws Exception
     */
    public function execute($request)
    {

        $params = $this->getParams($request);
        $params[$this->signParam] = $this->get_sign($params);
        $requestUrl = $this->gatewayUrl . '/' . $request->getApiMethodName();
        //发起HTTP请求
        try {
            $resp = WebUtil::curl_for_xform($requestUrl, $params, $this->charset);
        } catch (Exception $e) {
            $this->logCommunicationError($request->getApiMethodName(), $requestUrl, "HTTP_ERROR_" . $e->getCode(), $e->getMessage());
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
            $this->logCommunicationError($request->getApiMethodName(), $requestUrl, "HTTP_RESPONSE_NOT_WELL_FORMED", $resp);
            return false;
        }
        return $respObject;
    }

    private function getParamStr($params)
    {
//        if(isset($params['trade_info']) || isset($params['message']) || isset($params['reason'])){//支付和消息 存在json数据， 不能对参数encode
//            $apiParamsQuery = WebUtil::buildQueryWithoutEncode($params);
//        }else{
//            $apiParamsQuery = WebUtil::buildQueryWithEncode($params);
//        }
        $apiParamsQuery = WebUtil::buildQueryWithoutEncode($params);//关爱通签名 不需要encode
        return $apiParamsQuery;
    }

    private function getSystemParams()
    {

        //组装系统参数
        $sysParams["appid"] = $this->appId;
//        $sysParams["access_token"] = $this->get_access_token();
        $t = time();
        $sysParams["timestamp"] = "{$t}";
        return $sysParams;

    }

    private function getParams($request)
    {

        $apiParams = $request->getApiParas();
        $sysParams = $this->getSystemParams();

        if ($request->getApiMethodName() == 'token/create') {
            unset($sysParams['access_token']);
        }
//        if($request->getApiMethodName() == 'pay/doPay'){
//            unset($sysParams['appid']);
//        }
        return array_merge($apiParams, $sysParams);

    }

    private function get_sign($params)
    {
        $params['appsecret'] = $this->appsecret;
        $apiParamsQuery = $this->getParamStr($params);
        return sha1($apiParamsQuery);
    }

    /**
     * 返回access_token
     */
    private function get_access_token()
    {
        $ci =& get_instance();
        $data = $ci->cache->get($this->token_key);
        if (!$data) {
            $ci->load->library('guanaitong/request/CommonRequest');
            $request = new CommonRequest();
            $request->setParameter('grant_type', 'client_credential');
            $request->setApi('token/create');
            $result = $this->execute($request);
            if ($result && $result['code'] == 0 && !empty($result['data']['access_token'])) {
                $data = $result['data']['access_token'];
            }
            $ci->cache->save($this->token_key, $data, $this->expires);
        }
        if (!$data) {
            write_log('关爱通获取token失败', 'crit');
        }
        return $data;
    }

    /**
     * 记录错误日志
     * @param $apiName
     * @param $requestUrl
     * @param $errorCode
     * @param $responseTxt
     */
    private function logCommunicationError($apiName, $requestUrl, $errorCode, $responseTxt)
    {
        $localIp = isset ($_SERVER["SERVER_ADDR"]) ? $_SERVER["SERVER_ADDR"] : "CLI";

        $logData = array(
            date("Y-m-d H:i:s"),
            $apiName,
            $this->appId,
            $localIp,
            PHP_OS,
            $requestUrl,
            $errorCode,
            str_replace("\n", "", $responseTxt)
        );
        write_log(var_export($logData, 1));
    }
}
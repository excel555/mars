<?php
require_once 'AopEncrypt.php';
require_once 'SignData.php';
class MapiClient
{
    public $partner;
    public $sign_type='';
    public $sign;
    public $_input_charset="UTF-8";
    public $service;
    public $gatewayUrl;
    public $format = "xml";
    // 表单提交字符集编码
    public $postCharset = "UTF-8";
    public $fileCharset = "UTF-8";
    //使用文件读取文件格式，请只传递该值
    public $alipayPublicKey = null;

    //使用读取字符串格式，请只传递该值
    public $alipayrsaPublicKey;

    //私钥文件路径
    public $rsaPrivateKeyFilePath;
    public $safe_key;

    //私钥值
    public $rsaPrivateKey;

    private $SIGN_NODE_NAME = "sign";
    private $RESPONSE_SUFFIX = "_response";
    private $ERROR_RESPONSE = "error_response";

    //加密XML节点名称
    private $ENCRYPT_XML_NODE_NAME = "response_encrypted";

    private $needEncrypt = false;


    //签名类型
    public $signType = "RSA";


    //加密密钥和类型

    public $encryptKey;

    public $encryptType = "AES";
    public $notify_url;
    public $return_url;

    protected $alipaySdkVersion = "alipay-sdk-php-20161101";

    public function agreement_page_sign($request){
        $this->setupCharsets($request);
        if (strcasecmp($this->fileCharset, $this->postCharset)) {
            throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
        }
        //组装系统参数
        $sysParams["service"] = $this->service;
        $sysParams["partner"] = $this->partner;
        $sysParams["return_url"] = $this->return_url;
        $sysParams["notify_url"] = $this->notify_url;
        $sysParams["_input_charset"] = $this->_input_charset;
        $sysParams["service"] = $request->getService();
        $apiParams = $request->getApiParas();
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->sign_type);
        $sysParams["sign_type"] = $this->sign_type;
        $sysParams = array_merge($apiParams, $sysParams);
        $requestUrl = $this->gatewayUrl . "?";
        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
        }
        return substr($requestUrl, 0, -1);
    }

    public function execute($request, $authToken = null, $appInfoAuthtoken = null)
    {

        $this->setupCharsets($request);

        //		//  如果两者编码不一致，会出现签名验签或者乱码
        if (strcasecmp($this->fileCharset, $this->postCharset)) {

            // write_log("本地文件字符集编码与表单提交编码不一致，请务必设置成一样，属性名分别为postCharset!");
            throw new Exception("文件编码：[" . $this->fileCharset . "] 与表单提交编码：[" . $this->postCharset . "]两者不一致!");
        }
        //组装系统参数
        $sysParams["service"] = $this->service;
        $sysParams["partner"] = $this->partner;
        if($this->notify_url)
        {
            $sysParams["notify_url"] = $this->notify_url;
        }
        if($this->return_url)
        {
            $sysParams["return_url"] = $this->return_url;
        }
        $sysParams["_input_charset"] = $this->_input_charset;
        $sysParams["service"] = $request->getService();
        //获取业务参数
        $apiParams = $request->getApiParas();

        //签名
        $sysParams["sign"] = $this->generateSign(array_merge($apiParams, $sysParams), $this->sign_type);
        $sysParams["sign_type"] = $this->sign_type;
//        $sysParams =  array_merge($apiParams, $sysParams);
        write_log("请求参数 " . var_export(array_merge($apiParams, $sysParams), 1));
        //系统参数放入GET请求串
        $requestUrl = $this->gatewayUrl . "?";
        foreach ($sysParams as $sysParamKey => $sysParamValue) {
            $requestUrl .= "$sysParamKey=" . urlencode($this->characet($sysParamValue, $this->postCharset)) . "&";
        }
        $requestUrl = substr($requestUrl, 0, -1);

        //发起HTTP请求
        try {
            $resp = $this->curl($requestUrl, $apiParams);
            write_log("response：".var_export($resp,1));
        } catch (Exception $e) {
            write_log($sysParams["service"].",".$requestUrl."HTTP_ERROR_" . $e->getCode(). $e->getMessage());
            return false;
        }
        //解析AOP返回结果
        $respWellFormed = false;
        // 将返回结果转换本地文件编码
        $r = iconv($this->postCharset, $this->fileCharset . "//IGNORE", $resp);
        write_log("return xml:".var_export($r,1));
        $signData = null;

        if ("json" == $this->format) {

            $respObject = json_decode($r);
            if (null !== $respObject) {
                $respWellFormed = true;
                $signData = $this->parserJSONSignData($request, $resp, $respObject);
            }
        } else if ("xml" == $this->format) {
            libxml_disable_entity_loader(true);
            $xml = simplexml_load_string($r, "SimpleXMLElement", LIBXML_NOCDATA);
            $json = json_encode($xml);
            $array = json_decode($json,TRUE);
        }
        return $array;
    }

    function getRspFromXML($xmlStr){
        if(empty($xmlStr)) {//判断POST来的数组是否为空
            return false;
        }
        $arrays = array();   //集合类型map
        $xml = simplexml_load_string($xmlStr);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->getName() == "response" && $node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
                    //$xmlStr = $v;
                }else if($node->getName() == "is_success"){
                    $arrays[$node->getName()] = (string)$node;
                }
            }
        }
        $xml = simplexml_load_string($v);
        if($xml && $xml->children()) {
            foreach ($xml->children() as $node){
                //有子节点
                if($node->children()) {
                    $k = $node->getName();
                    $nodeXml = $node->asXML();
                    $v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);

                } else {
                    $k = $node->getName();
                    $v = (string)$node;
                }
                $arrays[$k] = $v;

            }
        }
        return $arrays;
    }
    /**
     * 校验$value是否非空
     *  if not set ,return true;
     *    if is null , return true;
     **/
    protected function checkEmpty($value)
    {
        if (!isset($value))
            return true;
        if ($value === null)
            return true;
        if (trim($value) === "")
            return true;

        return false;
    }

    private function setupCharsets($request)
    {
        if ($this->checkEmpty($this->postCharset)) {
            $this->postCharset = 'UTF-8';
        }
        $str = preg_match('/[\x80-\xff]/', $this->partner) ? $this->partner : print_r($request, true);
        $this->fileCharset = mb_detect_encoding($str, "UTF-8, GBK") == 'UTF-8' ? 'UTF-8' : 'GBK';
    }

    public function generateSign($params, $signType = "RSA")
    {
        return $this->sign($this->getSignContent($params), $signType);
    }

    protected function sign($data, $signType = "RSA")
    {

        if ($this->checkEmpty($this->safe_key)) {
            throw new Exception(" check sign Fail! The reason : safe_key is Empty");
        }
//        echo $data.$this->safe_key;
        return md5($data.$this->safe_key);
    }

    public function getSignContent($params)
    {
        ksort($params);

        $stringToBeSigned = "";
        $i = 0;
        foreach ($params as $k => $v) {
            if (false === $this->checkEmpty($v) && "@" != substr($v, 0, 1)) {

                // 转换成目标字符集
                $v = $this->characet($v, $this->postCharset);

                if ($i == 0) {
                    $stringToBeSigned .= "$k" . "=" . "$v";
                } else {
                    $stringToBeSigned .= "&" . "$k" . "=" . "$v";
                }
                $i++;
            }
        }
        unset ($k, $v);
        return $stringToBeSigned;
    }

    /**
     * 验签
     * @param $request
     * @param $signData
     * @param $resp
     * @param $respObject
     * @throws Exception
     */
    public function checkResponseSign($request, $signData, $resp, $respObject)
    {

        if (!$this->checkEmpty($this->alipayPublicKey) || !$this->checkEmpty($this->alipayrsaPublicKey)) {


            if ($signData == null || $this->checkEmpty($signData->sign) || $this->checkEmpty($signData->signSourceData)) {

                throw new Exception(" check sign Fail! The reason : signData is Empty");
            }


            // 获取结果sub_code
            $responseSubCode = $this->parserResponseSubCode($request, $resp, $respObject, $this->format);


            if (!$this->checkEmpty($responseSubCode) || ($this->checkEmpty($responseSubCode) && !$this->checkEmpty($signData->sign))) {

                $checkResult = $this->verify($signData->signSourceData, $signData->sign, $this->alipayPublicKey, $this->sign_type);


                if (!$checkResult) {

                    if (strpos($signData->signSourceData, "\\/") > 0) {

                        $signData->signSourceData = str_replace("\\/", "/", $signData->signSourceData);

                        $checkResult = $this->verify($signData->signSourceData, $signData->sign, $this->alipayPublicKey, $this->signType);

                        if (!$checkResult) {
                            throw new Exception("check sign Fail! [sign=" . $signData->sign . ", signSourceData=" . $signData->signSourceData . "]");
                        }

                    } else {

                        throw new Exception("check sign Fail! [sign=" . $signData->sign . ", signSourceData=" . $signData->signSourceData . "]");
                    }

                }
            }


        }
    }

    function parserXMLSignData($request, $responseContent)
    {


        $signData = new SignData();
        $signData->sign = $this->parserXMLSign($responseContent);
        $signData->signSourceData = $this->parserXMLSignSource($request, $responseContent);
        return $signData;


    }

    function parserXMLSignSource($request, $responseContent)
    {


        $apiName = $request->getService();
        $rootNodeName = str_replace(".", "_", $apiName) . $this->RESPONSE_SUFFIX;


        $rootIndex = strpos($responseContent, $rootNodeName);
        $errorIndex = strpos($responseContent, $this->ERROR_RESPONSE);
//        		echo ("<br/>rootNodeName:" . $rootNodeName);
//        echo ("<br/> responseContent:<xmp>" . $responseContent . "</xmp>");


        if ($rootIndex > 0) {

            return $this->parserXMLSource($responseContent, $rootNodeName, $rootIndex);
        } else if ($errorIndex > 0) {

            return $this->parserXMLSource($responseContent, $this->ERROR_RESPONSE, $errorIndex);
        } else {

            return null;
        }


    }

    function parserXMLSource($responseContent, $nodeName, $nodeIndex)
    {
        $signDataStartIndex = $nodeIndex + strlen($nodeName) + 1;
        $signIndex = strpos($responseContent, "<" . $this->SIGN_NODE_NAME . ">");
        // 签名前-逗号
        $signDataEndIndex = $signIndex - 1;
        $indexLen = $signDataEndIndex - $signDataStartIndex + 1;

        if ($indexLen < 0) {
            return null;
        }


        return substr($responseContent, $signDataStartIndex, $indexLen);


    }

    function parserXMLSign($responseContent)
    {
        $signNodeName = "<" . $this->SIGN_NODE_NAME . ">";
        $signEndNodeName = "</" . $this->SIGN_NODE_NAME . ">";

        $indexOfSignNode = strpos($responseContent, $signNodeName);
        $indexOfSignEndNode = strpos($responseContent, $signEndNodeName);


        if ($indexOfSignNode < 0 || $indexOfSignEndNode < 0) {
            return null;
        }

        $nodeIndex = ($indexOfSignNode + strlen($signNodeName));

        $indexLen = $indexOfSignEndNode - $nodeIndex;

        if ($indexLen < 0) {
            return null;
        }

        // 签名
        return substr($responseContent, $nodeIndex, $indexLen);

    }

    function characet($data, $targetCharset) {

        if (!empty($data)) {
            $fileType = $this->fileCharset;
            if (strcasecmp($fileType, $targetCharset) != 0) {
                $data = mb_convert_encoding($data, $targetCharset, $fileType);
                //				$data = iconv($fileType, $targetCharset.'//IGNORE', $data);
            }
        }
        return $data;
    }

    protected function curl($url, $postFields = null) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_TIMEOUT,10);//todo 支付宝发起支付超时
        $postBodyString = "";
        $encodeArray = Array();
        $postMultipart = false;


        if (is_array($postFields) && 0 < count($postFields)) {

            foreach ($postFields as $k => $v) {
                if ("@" != substr($v, 0, 1)) //判断是不是文件上传
                {

                    $postBodyString .= "$k=" . urlencode($this->characet($v, $this->postCharset)) . "&";
                    $encodeArray[$k] = $this->characet($v, $this->postCharset);
                } else //文件上传用multipart/form-data，否则用www-form-urlencoded
                {
                    $postMultipart = true;
                    $encodeArray[$k] = new \CURLFile(substr($v, 1));
                }

            }
            unset ($k, $v);
            curl_setopt($ch, CURLOPT_POST, true);
            if ($postMultipart) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $encodeArray);
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, substr($postBodyString, 0, -1));
            }
            write_log( "url: ".$url."&".substr($postBodyString, 0, -1));

//            echo "<br/><br/><br/><a style='display:block;font-size:20px;' href='".$url."&".substr($postBodyString, 0, -1)."'>qianming</a>";
        }

        if ($postMultipart) {

            $headers = array('content-type: multipart/form-data;charset=' . $this->postCharset . ';boundary=' . $this->getMillisecond());
        } else {

            $headers = array('content-type: application/x-www-form-urlencoded;charset=' . $this->postCharset);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $reponse = curl_exec($ch);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            write_log('curl error:'.var_export($error,1),'crit');
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                write_log('curl code:'.$httpStatusCode,'crit');
            }
        }


        curl_close($ch);
        return $reponse;
    }
}

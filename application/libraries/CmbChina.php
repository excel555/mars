<?php


class CmbChina {
    
    //支付私钥
    // private $private_key = 'cmbtest1';
    private $private_key = 'b29f626ac6b6c49A';
    
    //支付公钥
    private $public_key = '';
    
    //登录私钥
    private $login_private_key = 'kt3cqra2';
    
    //登录公钥
    // private $login_public_key = 'mars-test.key';
    private $login_public_key = 'mars.key';
    
    //支付商户开户分行号
    private $branchId = '0021';
    
    //支付商户号
    // private $merId = '000332';
    // private $merId = '002002';
    private $merId = '630132';
    
    //登录商户号
    private $merLoginId = '003004';
    
    // private $operatorPwd = '000332';
    private $operatorPwd = '055929';
    
    // private $pay_gateway = 'http://121.15.180.66:801/netpayment/BaseHttp.dll?MB_APPQRPay';
    private $pay_gateway = 'https://netpay.cmbchina.com/netpayment/BaseHttp.dll?MB_APPQRPay';
    
    // private $bus_gateway = 'http://121.15.180.72/CmbBank_B2B/UI/NetPay/DoBusiness.ashx';
    private $bus_gateway = 'https://b2b.cmbchina.com/CmbBank_B2B/UI/NetPay/DoBusiness.ashx';
    
    // private $opt_gateway = 'http://121.15.180.66:801/NetPayment_dl/BaseHttp.dll';
    private $opt_gateway = 'https://payment.ebank.cmbchina.com/NetPayment/BaseHttp.dll';
    
    private $login_protocol = 'tplogins';
    
    
    public function __construct()
    {
        
    }
    
    
    /**
     * 设置支付商户号
     */
    public function set_pay()
    {
        // $this->merId = '000332';
        // $this->private_key = 'b29f626ac6b6c49A';
    }
    
    
    /**
     * 设置公钥
     */
    public function set_public_key($public_key)
    {
        $this->public_key = $public_key;
    }
    
    
    
    /**
     * 二维码支付
     */
    public function doQRPay( $reqParams )
    {
        $this->set_pay();
        
        $payParams = [];
        $payParams['version'] = '1.0';
        $payParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'date' => date("Ymd"),
            'orderNo' => $reqParams['order_name'],
            'amount' => $reqParams['amount'],
            'expireTimeSpan' => '30',
            'payNoticeUrl' => $reqParams['notify_url'],
            'payNoticePara' => '',
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId,
            'returnUrl' => $reqParams['return_url'],
            'clientIP' => $this->getClientIp()
        );
        
        $payParams['signType'] = 'SHA-256';
        $payParams['sign'] = $this->hash_encrypt($payParams['reqData']);
        
        $reqJSON = json_encode($payParams);
        
        $this->formSubmit($reqJSON);
    }
    
    
    /**
     * 订单支付查询
     */
    public function doQuery($params)
    {
        $this->set_pay();
        
        $reqParams = [];
        $reqParams['version'] = '1.0';
        $reqParams['charset'] = 'UTF-8';
        $reqParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId,
            'type' => 'B',
            'date' => $params['order_date'],
            'orderNo' => $params['order_name'],
            'operatorNo' => '9999'
        );
        
        $reqParams['signType'] = 'SHA-256';
        $reqParams['sign'] = $this->hash_encrypt($reqParams['reqData']);
        
        $reqPost = 'jsonRequestData=' . json_encode($reqParams);
        $respStr = $this->request($this->opt_gateway . '?QuerySingleOrder', $reqPost);
        $respData = json_decode($respStr, true);
        
        //echo $reqPost . "\r\n" . $respStr . "\r\n";
        
        $payStatus = 'unknown';
        if($respData['rspData']['rspCode'] == 'SUC0000'){
            if($respData['rspData']['orderStatus'] == '0'){
                $payStatus = 'success';
                return array(
                    'code' => 200, 
                    'payStatus' => 'success', 
                    'bankSerialNo' => $respData['rspData']['bankSerialNo'],
                    'orderAmount' => $respData['rspData']['orderAmount'],
                    'settleAmount' => $respData['rspData']['settleAmount'],
                    'discountAmount' => $respData['rspData']['discountAmount'],
                    'settleDate' => $respData['rspData']['settleDate'],
                    'fee' => $respData['rspData']['fee'],
                );
            }
        }
        
        return ['code' => 500, 'payStatus' => $payStatus];
    }
    
    
    /**
     * 退款申请
     */
    public function doRefund($params)
    {
        $this->set_pay();
        
        $operatorPwd = bin2hex($this->rc4_encrypt($this->private_key, $this->operatorPwd));
        
        $reqParams = [];
        $reqParams['version'] = '1.0';
        $reqParams['charset'] = 'UTF-8';
        $reqParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId,
            'date' => $params['order_date'],
            'orderNo' => $params['order_name'],
            'refundSerialNo' => $params['refund_id'],
            'amount' => $params['refund_fee'], 
            'operatorNo' => '9999',
            'encrypType' => 'RC4',
            'pwd' => $operatorPwd
        );
        
        $reqParams['signType'] = 'SHA-256';
        $reqParams['sign'] = $this->hash_encrypt($reqParams['reqData']);
        
        $reqPost = 'jsonRequestData=' . json_encode($reqParams);
        $respStr = $this->request($this->opt_gateway . '?DoRefund', $reqPost);
        $respData = json_decode($respStr, true);
        
        // echo $reqPost . "\r\n" . $respStr . "\r\n";
        
        if($respData['rspData']['rspCode'] == 'SUC0000'){
            return ['code' => 200, 'refundStatus' => 'success', 'refund_id' => $respData['rspData']['bankSerialNo']];
        }
        
        return ['code' => 500, 'refundStatus' => 'fail', 'rspMsg' => $respData['rspData']['rspMsg']];
    }
    
    
    /**
     * 退款查询
     */
    public function doRefundQuery($params)
    {
        $this->set_pay();
        
        $reqParams = [];
        $reqParams['version'] = '1.0';
        $reqParams['charset'] = 'UTF-8';
        $reqParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId,
            'type' => 'B',
            'date' => $params['refund_date'],
            'orderNo' => $params['order_name'],
            'merchantSerialNo' => $params['refund_id']
        );
        
        $reqParams['signType'] = 'SHA-256';
        $reqParams['sign'] = $this->hash_encrypt($reqParams['reqData']);
        
        $reqPost = 'jsonRequestData=' . json_encode($reqParams);
        $respStr = $this->request($this->opt_gateway . '?QuerySettledRefund', $reqPost);
        $respData = json_decode($respStr, true);
        
        //echo $reqPost . "\r\n" . $respStr . "\r\n";
        
        $refundStatus = 'unknown';
        if($respData['rspCode'] == 'SUC0000'){
            foreach(explode("\r\n", $respData['dataList']) as $key => $row){
                if($key > 0){
                    $data = explode(",`", $row);
                    if($data[5] == $params['refund_id']){
                        if($data[6] == '210'){
                            $refundStatus = 'success';    
                        }else{
                            $refundStatus = 'processing';
                        }
                        break;
                    }
                }
            }
            
            return ['code' => 200, 'refundStatus' => $refundStatus];
        }
        
        return ['code' => 500, 'refundStatus' => $refundStatus];
    }
    
    
    /**
     * 查询入账明细API
     */
    public function doAccountList($params)
    {
        $this->set_pay();
        
        $reqParams = [];
        $reqParams['version'] = '1.0';
        $reqParams['charset'] = 'UTF-8';
        $reqParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId,
            'date' => $params['query_date'],
            'operatorNo' => '9999'
        );
        
        $reqParams['signType'] = 'SHA-256';
        $reqParams['sign'] = $this->hash_encrypt($reqParams['reqData']);
        
        $reqPost = 'jsonRequestData=' . json_encode($reqParams);
        $respStr = $this->request($this->opt_gateway . '?QueryAccountList', $reqPost);
        $respData = json_decode($respStr, true);
        
        //echo $reqPost . "\r\n" . $respStr . "\r\n";
        
        $i = 0;
        $accountList = [];
        if($respData['rspCode'] == 'SUC0000'){
            foreach(explode("\r\n", $respData['dataList']) as $key => $row){
                if($key > 0){
                    $data = explode(",`", $row);
                    $accountList[$i]['orderName'] = $data[3];
                    $accountList[$i]['bankSerialNo'] = $data[4];
                    $accountList[$i]['orderAmount'] = $data[7];
                    $accountList[$i]['discountAmount'] = $data[9];
                    $accountList[$i]['fee'] = $data[10];
                    $accountList[$i++]['transDate'] = $data[11];
                }
            }
            
            return ['code' => 200, 'accountList' => $accountList];
        }
        
        return ['code' => 500, 'accountList' => []];
    }
    
    
    /**
     * 查询招行公钥
     */
    public function pullPublicKey()
    {
        $this->set_pay();
        
        $reqParams = [];
        $reqParams['version'] = '1.0';
        $reqParams['charset'] = 'UTF-8';
        $reqParams['reqData'] = array(
            'dateTime' => date("YmdHis"),
            'txCode' => 'FBPK',
            'branchNo' => $this->branchId,
            'merchantNo' => $this->merId
        );
        
        $reqParams['signType'] = 'SHA-256';
        $reqParams['sign'] = $this->hash_encrypt($reqParams['reqData']);
        
        $reqPost['jsonRequestData'] = json_encode($reqParams);
        
        $respStr = $this->request($this->bus_gateway, $reqPost);
        $respData = json_decode($respStr, true);
        
        if($respData['rspData']['rspCode'] == 'SUC0000'){
            return ['code' => 200, 'data' => $respData['rspData']['fbPubKey']];
        }
        
        return ['code' => 500, 'code' => $respData['rspData']['rspCode']];
    }
    
    
    
    /**
     * 登录地址
     */
    public function tplogin_url( $url, $signflag = false )
    {
        $url = str_replace(['http://', 'https://'], 'tplogins://', $url);
        
        if( strpos($url, '?') === false ){
            $url_prefix = $url . '?corpno=' . $this->merLoginId;
        }else{
            $url_prefix = $url . '&corpno=' . $this->merLoginId;
        }
        
        if( $signflag ){
            $url_prefix .= '&signflag=n';
        }
        
        $key = substr($this->login_private_key, 0, 8);
        return $url_prefix . '&auth=' . md5( strtolower($url_prefix) . $key );
    }
    
    
    
    /**
     * RSA验签
     *
     * @param Array $params
     * @param int $isSuccess        	
     */
    function rsa_verify($params, $signStr) 
    {
        $isSuccess = 0;
        
        $paramsStr = $this->coverParamsToString ( $params );
        
        $pem = chunk_split($this->public_key, 64, "\n");
        $pem = "-----BEGIN PUBLIC KEY-----\n" . $pem . "-----END PUBLIC KEY-----\n";
        $pkid = openssl_pkey_get_public($pem);
        
        $isSuccess = openssl_verify($paramsStr, base64_decode($signStr), $pkid, OPENSSL_ALGO_SHA1);
        
        return $isSuccess;
    }
    
    
    /**
     * 数组 排序后转化为字体串
     *
     * @param array $params        	
     * @return string
     */
    public function coverParamsToString($params) 
    {
        $sign_str = '';
        // 排序
        ksort ( $params );
        foreach ( $params as $key => $val ) {
            if ($key == 'signature') {
                continue;
            }
            $sign_str .= sprintf ( "%s=%s&", $key, $val );
            // $sign_str .= $key . '=' . $val . '&';
        }
        return substr ( $sign_str, 0, strlen ( $sign_str ) - 1 );
    }
    
    
    /**
     * 验签(登录接口使用)
     */
    public function verifySign( $response )
    {
		$this->initBridge();
        
        java_set_file_encoding("GB2312");
		$securityClient = new java( "cmb.netpayment.Security" , APPPATH . 'java' . DIRECTORY_SEPARATOR . 'cert' . DIRECTORY_SEPARATOR . 'cmb' . DIRECTORY_SEPARATOR . $this->login_public_key );        
        $checkSec = $securityClient->checkInfoFromBank( $response );
        
        if( java_values($checkSec) ){
            return true;
        }else{
            return false;
        }
    }
    
    
    /**
     * hash签名
     */
    public function hash_encrypt($data)
    {
        $strParams = '';
        ksort($data);
        
        foreach($data as $key => $val){
            $strParams .= $key . '=' . $val . '&';
        }
        
        $strParams .= $this->private_key;
        $strSign = hash('sha256', $strParams);
        
        return strtoupper($strSign);
    }
    
    
    /**
     * DES加密
     */
    public function des_encrypt($string)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        
        $key = substr($this->private_key, 0, 8);
        $ciphertext = mcrypt_encrypt(MCRYPT_DES, $key, $string, MCRYPT_MODE_ECB, $iv);
        $ciphertext = $iv . $ciphertext;
        $ciphertext_base64 = base64_encode($ciphertext);
        
        return $ciphertext_base64;
    }
    
    
    /**
     * DES解密(登录接口使用)
     */
    public function des_decrypt($string)
    {
        $iv_size = mcrypt_get_iv_size(MCRYPT_DES, MCRYPT_MODE_ECB);
        $iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
        $key = substr($this->login_private_key, 0, 8);
        $decrypted = mcrypt_decrypt(MCRYPT_DES, $key, base64_decode($string), MCRYPT_MODE_ECB, $iv);
        
        return rtrim($decrypted, "\x00..\x1F");
    }
    
    
    
    /**
     * RCR加密
     */
    public function rc4_encrypt($pwd, $data)
    {
        $cipher      = '';
        $key[]       = "";
        $box[]       = "";
        $pwd_length  = strlen($pwd);
        $data_length = strlen($data);
        for ($i = 0; $i < 256; $i++) {
            $key[$i] = ord($pwd[$i % $pwd_length]);
            $box[$i] = $i;
        }
        for ($j = $i = 0; $i < 256; $i++) {
            $j       = ($j + $box[$i] + $key[$i]) % 256;
            $tmp     = $box[$i];
            $box[$i] = $box[$j];
            $box[$j] = $tmp;
        }
        for ($a = $j = $i = 0; $i < $data_length; $i++) {
            $a       = ($a + 1) % 256;
            $j       = ($j + $box[$a]) % 256;
            $tmp     = $box[$a];
            $box[$a] = $box[$j];
            $box[$j] = $tmp;
            $k       = $box[(($box[$a] + $box[$j]) % 256)];
            $cipher .= chr(ord($data[$i]) ^ $k);
        }
        return $cipher;
    }
    
    
    
    /**
     * 初始initJavaBridge
     */
	public function initBridge()
    {
		require_once( APPPATH . 'java' . DIRECTORY_SEPARATOR . 'META-INF' . DIRECTORY_SEPARATOR . 'java' . DIRECTORY_SEPARATOR . 'Java.inc' );
        java_set_library_path( APPPATH . "java" . DIRECTORY_SEPARATOR );
	}
    
    
	/**
	 * 执行一个 HTTP GET请求
	 *
	 * @param string $url 执行请求的url
	 * @return array 返回网页内容
	 */
	function request($url, $post_data = '')
    {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		
		if( $post_data ){
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post_data);
		}
        
		$retval = curl_exec($curl);
		
		curl_close($curl);
        
		return $retval;
	}
    
    public function formSubmit( $reqJSON ) 
    {
        $action = $this->pay_gateway;
        
        $html = <<<HTML
<mars>
<head>
    <meta http-equiv="Content-Type" content="text/mars; charset=utf-8" />
</head>
<body onload="javascript:document.cityBoxForm.submit();">
    <form id="cityBoxForm" name="cityBoxForm" action="{$action}" method="post">
    <input type="hidden" name="jsonRequestData" value='{$reqJSON}' />
    <input type="hidden" name="charset" value="UTF-8" /> 
    <input type="submit" type="hidden">
    </form>
</body>
</mars>
HTML;

        exit($html);
    }
    
    
	/**
	 * 获取客户端IP
	 *
	 */
	function getClientIp()
    {
		$ip = '';
		if ($_SERVER["HTTP_X_FORWARDED_FOR"]){
			$ip = $_SERVER["HTTP_X_FORWARDED_FOR"];
		}elseif ($_SERVER["HTTP_CLIENT_IP"]){
			$ip = $_SERVER["HTTP_CLIENT_IP"];
		}elseif ($_SERVER["REMOTE_ADDR"]){
			$ip = $_SERVER["REMOTE_ADDR"];
		}elseif (getenv("HTTP_X_FORWARDED_FOR")){
			$ip = getenv("HTTP_X_FORWARDED_FOR");
		}elseif (getenv("HTTP_CLIENT_IP")){
			$ip = getenv("HTTP_CLIENT_IP");
		}elseif (getenv("REMOTE_ADDR")){
			$ip = getenv("REMOTE_ADDR");
		}else{
			$ip = "127.0.0.1";
		}

		return $ip;
	}
}
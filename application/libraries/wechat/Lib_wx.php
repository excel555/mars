<?php
/**
 * 微信支付帮助库
 * ====================================================
 * 接口分三种类型：
 * 【请求型接口】--Wxpay_client_
 * 		统一支付接口类--UnifiedOrder
 * 		订单查询接口--OrderQuery
 * 		退款申请接口--Refund
 * 		退款查询接口--RefundQuery
 * 		对账单接口--DownloadBill
 * 		短链接转换接口--ShortUrl
 * 【响应型接口】--Wxpay_server_
 * 		通用通知接口--Notify
 * 		Native支付——请求商家获取商品信息接口--NativeCall
 * 【其他】
 * 		静态链接二维码--NativeLink
 * 		JSAPI支付--JsApi
 * =====================================================
 * 【CommonUtil】常用工具：
 * 		trimString()，设置参数时需要用到的字符处理函数
 * 		createNoncestr()，产生随机字符串，不长于32位
 * 		formatBizQueryParaMap(),格式化参数，签名过程需要用到
 * 		getSign(),生成签名
 * 		arrayToXml(),array转xml
 * 		xmlToArray(),xml转 array
 * 		postXmlCurl(),以post方式提交xml到对应的接口url
 * 		postXmlSSLCurl(),使用证书，以post方式提交xml到对应的接口url
*/
	include_once("SDKRuntimeException.php");
/**
 * 所有接口的基类
 */
class Common_util_pub
{
	public $config;
	function __construct($config) {
		$this->config = $config;
	}

	function trimString($value)
	{
		$ret = null;
		if (null != $value) 
		{
			$ret = $value;
			if (strlen($ret) == 0) 
			{
				$ret = null;
			}
		}
		return $ret;
	}
	
	/**
	 * 	作用：产生随机字符串，不长于32位
	 */
	public function createNoncestr( $length = 32 ) 
	{
		$chars = "abcdefghijklmnopqrstuvwxyz0123456789";  
		$str ="";
		for ( $i = 0; $i < $length; $i++ )  {  
			$str.= substr($chars, mt_rand(0, strlen($chars)-1), 1);  
		}  
		return $str;
	}
	
	/**
	 * 	作用：格式化参数，签名过程需要使用
	 */
	function formatBizQueryParaMap($paraMap, $urlencode)
	{
		$buff = "";
		ksort($paraMap);
		foreach ($paraMap as $k => $v)
		{
		    if($urlencode)
		    {
			   $v = urlencode($v);
			}
			//$buff .= strtolower($k) . "=" . $v . "&";
			$buff .= $k . "=" . $v . "&";
		}
		$reqPar ="";
		if (strlen($buff) > 0) 
		{
			$reqPar = substr($buff, 0, strlen($buff)-1);
		}
		return $reqPar;
	}
	
	/**
	 * 	作用：生成签名
	 */
	public function getSign($Obj)
	{
		foreach ($Obj as $k => $v)
		{	
			if(empty($v)){
				continue;
			}
			$Parameters[$k] = $v;
		}
		//签名步骤一：按字典序排序参数
		ksort($Parameters);
		$String = $this->formatBizQueryParaMap($Parameters, false);
//		echo '<pre>【string1】'.$String.'</br>';
		//签名步骤二：在string后加入wechat_key
		$String = $String."&key=".$this->config['wechat_key'];
//		echo "【string2】".$String."</br>";
		//签名步骤三：MD5加密
		$String = md5($String);
//		echo "【string3】 ".$String."</br>";
		//签名步骤四：所有字符转为大写
		$result_ = strtoupper($String);
//		echo "【result】 ".$result_."</pre></br>";
		return $result_;
	}
	
	/**
	 * 	作用：array转xml
	 */
	function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
        	 if (is_numeric($val))
        	 {
        	 	$xml.="<".$key.">".$val."</".$key.">"; 

        	 }
        	 else
        	 	$xml.="<".$key."><![CDATA[".$val."]]></".$key.">";  
        }
        $xml.="</xml>";
        return $xml; 
    }

    function arrayToXml_new($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
			$xml.="<".$key.">".$val."</".$key.">";
        }
        $xml.="</xml>";
        return $xml;
    }
	
	/**
	 * 	作用：将xml转为array
	 */
	public function xmlToArray($xml)
	{		
        //将XML转为array        
        $array_data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);		
		return $array_data;
	}

	/**
	 * 	作用：以post方式提交xml到对应的接口url
	 */
	public function postXmlCurl($xml,$url,$second=30)
	{		
		//echo $url;
        //初始化curl        
       	$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOPT_TIMEOUT, $second);
        //这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//post提交方式
		curl_setopt($ch, CURLOPT_POST, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
		//运行curl
        $data = curl_exec($ch);
		//curl_close($ch);
		//返回结果
		if($data)
		{
			curl_close($ch);
			return $data;
		}
		else 
		{ 
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>"; 
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}

	/**
	 * 	作用：使用证书，以post方式提交xml到对应的接口url
	 */
	function postXmlSSLCurl($xml,$url,$second=30)
	{
		$ch = curl_init();
		//超时时间
		curl_setopt($ch,CURLOPT_TIMEOUT,$second);
		//这里设置代理，如果有的话
        //curl_setopt($ch,CURLOPT_PROXY, '8.8.8.8');
        //curl_setopt($ch,CURLOPT_PROXYPORT, 8080);
        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		//设置header
		curl_setopt($ch,CURLOPT_HEADER,FALSE);
		//要求结果为字符串且输出到屏幕上
		curl_setopt($ch,CURLOPT_RETURNTRANSFER,TRUE);
		//设置证书
		//使用证书：cert 与 key 分别属于两个.pem文件
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLCERTTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLCERT, $this->config['SSLCERT_PATH']);
		//默认格式为PEM，可以注释
		curl_setopt($ch,CURLOPT_SSLKEYTYPE,'PEM');
		curl_setopt($ch,CURLOPT_SSLKEY, $this->config['SSLKEY_PATH']);
		//post提交方式
		curl_setopt($ch,CURLOPT_POST, true);
		curl_setopt($ch,CURLOPT_POSTFIELDS,$xml);
		$data = curl_exec($ch);
		//返回结果
		if($data){
			curl_close($ch);
			return $data;
		}
		else { 
			$error = curl_errno($ch);
			echo "curl出错，错误码:$error"."<br>"; 
			echo "<a href='http://curl.haxx.se/libcurl/c/libcurl-errors.html'>错误原因查询</a></br>";
			curl_close($ch);
			return false;
		}
	}
	
	/**
	 * 	作用：打印数组
	 */
	function printErr($wording='',$err='')
	{
		print_r('<pre>');
		echo $wording."</br>";
		var_dump($err);
		print_r('</pre>');
	}

    public function postCurl($data,$url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOP_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //post提交方式
        curl_setopt($ch,CURLOPT_POST, true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);

        //运行curl，结果以jason形式返回
        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            $reponse = curl_error($ch);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                write_log('curl code:'.$httpStatusCode.',response:'.var_export($reponse,1));
            }
        }
        curl_close($ch);
        return $reponse;
    }

    public function getCurl($url,$second=30)
    {
        //初始化curl
        $ch = curl_init();
        //设置超时
        curl_setopt($ch, CURLOP_TIMEOUT, $second);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        //运行curl，结果以jason形式返回
        $reponse = curl_exec($ch);
        if (curl_errno($ch)) {
            $reponse = curl_error($ch);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                write_log('curl code:'.$httpStatusCode.',response:'.var_export($reponse,1));
            }
        }
        curl_close($ch);
        return $reponse;
    }
}


/**
 * 响应型接口基类
 */
class Wxpay_server_pub extends Common_util_pub 
{
	public $data;//接收到的数据，类型为关联数组
	var $returnParameters;//返回参数，类型为关联数组
	public $config;
	function __construct($config) {
		$this->config = $config;
	}
	/**
	 * 将微信的请求xml转换成关联数组，以方便数据处理
	 */
	function saveData($xml)
	{
		$this->data = $this->xmlToArray($xml);
	}
	
	function checkSign()
	{
		$tmpData = $this->data;
		unset($tmpData['sign']);
		$sign = $this->getSign($tmpData);//本地签名
		if ($this->data['sign'] == $sign) {
			return TRUE;
		}
		return FALSE;
	}
	
	/**
	 * 获取微信的请求数据
	 */
	function getData()
	{		
		return $this->data;
	}
	
	/**
	 * 设置返回微信的xml数据
	 */
	function setReturnParameter($parameter, $parameterValue)
	{
		$this->returnParameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}
	
	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		return $this->arrayToXml($this->returnParameters);
	}
	
	/**
	 * 将xml数据返回微信
	 */
	function returnXml()
	{
		$returnXml = $this->createXml();
		return $returnXml;
	}
}


/**
 * 通用通知接口
 */
class Notify_pub extends Wxpay_server_pub 
{
	public $config;
	function __construct($config) {
		$this->config = $config;
	}
}

/**
 * 请求型接口的基类
 */
class Wxpay_client_pub extends Common_util_pub
{
	var $parameters;//请求参数，类型为关联数组
	public $response;//微信返回的响应
	public $result;//返回参数，类型为关联数组
	var $url;//接口链接
	var $curl_timeout;//curl超时时间
	public $config;
	function __construct($config) {
		$this->config = $config;
	}
	/**
	 * 	作用：设置请求参数
	 */
	function setParameter($parameter, $parameterValue)
	{
		$this->parameters[$this->trimString($parameter)] = $this->trimString($parameterValue);
	}

	/**
	 * 	作用：设置标配的请求参数，生成签名，生成接口参数xml
	 */
	function createXml()
	{
		$this->parameters["appid"] = $this->config['wechat_appid'];//公众账号ID
		$this->parameters["mch_id"] = $this->config['wechat_mchid'];//商户号
		$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
		$this->parameters["sign"] = $this->getSign($this->parameters);//签名
		return  $this->arrayToXml($this->parameters);
	}

	/**
	 * 	作用：post请求xml
	 */
	function postXml()
	{
		$xml = $this->createXml();
		$this->response = $this->postXmlCurl($xml,$this->url,$this->curl_timeout);
		return $this->response;
	}

	/**
	 * 	作用：使用证书post请求xml
	 */
	function postXmlSSL()
	{
		$xml = $this->createXml();
		$this->response = $this->postXmlSSLCurl($xml,$this->url,$this->curl_timeout);
		return $this->response;
	}

	/**
	 * 	作用：获取结果，默认不使用证书
	 */
	function getResult()
	{
		$this->postXml();
		$this->result = $this->xmlToArray($this->response);
		return $this->result;
	}
}

/**
 * 退款申请接口
 */
class Refund_pub extends Wxpay_client_pub
{

	public $config;
	function __construct($config) {
		$this->config = $config;
		//设置接口链接
		$this->url = "https://api.mch.weixin.qq.com/secapi/pay/refund";
		//设置curl超时时间
		$this->curl_timeout = $this->config['CURL_TIMEOUT'];
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		try
		{
			//检测必填参数
			if($this->parameters["out_trade_no"] == null && $this->parameters["transaction_id"] == null) {
				throw new SDKRuntimeException("退款申请接口中，out_trade_no、transaction_id至少填一个！"."<br>");
			}elseif($this->parameters["out_refund_no"] == null){
				throw new SDKRuntimeException("退款申请接口中，缺少必填参数out_refund_no！"."<br>");
			}elseif($this->parameters["total_fee"] == null){
				throw new SDKRuntimeException("退款申请接口中，缺少必填参数total_fee！"."<br>");
			}elseif($this->parameters["refund_fee"] == null){
				throw new SDKRuntimeException("退款申请接口中，缺少必填参数refund_fee！"."<br>");
			}elseif($this->parameters["op_user_id"] == null){
				throw new SDKRuntimeException("退款申请接口中，缺少必填参数op_user_id！"."<br>");
			}
			$this->parameters["appid"] = $this->config['wechat_appid'];//公众账号ID
			$this->parameters["mch_id"] = $this->config['wechat_mchid'];//商户号
			$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
			$this->parameters["sign"] = $this->getSign($this->parameters);//签名
			return  $this->arrayToXml($this->parameters);
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}
	}
    function postXmlSSL()
    {
        $xml = $this->createXml();
        $this->response = $this->postXmlSSLCurl($xml,$this->url,$this->curl_timeout);
        return $this->response;
    }
	/**
	 * 	作用：获取结果，使用证书通信
	 */
	function getResult()
	{
		$this->postXmlSSL();
		$this->result = $this->xmlToArray($this->response);
		return $this->result;
	}

}


/**
 * 退款查询接口
 */
class RefundQuery_pub extends Wxpay_client_pub
{

	public $config;
	function __construct($config) {
		$this->config = $config;
		//设置接口链接
		$this->url = "https://api.mch.weixin.qq.com/pay/refundquery";
		//设置curl超时时间
		$this->curl_timeout = $this->config['CURL_TIMEOUT'];
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		try
		{
			if($this->parameters["out_refund_no"] == null &&
			$this->parameters["out_trade_no"] == null &&
			$this->parameters["transaction_id"] == null &&
			$this->parameters["refund_id "] == null)
			{
				throw new SDKRuntimeException("退款查询接口中，out_refund_no、out_trade_no、transaction_id、refund_id四个参数必填一个！"."<br>");
			}
			$this->parameters["appid"] = $this->config['wechat_appid'];//公众账号ID
			$this->parameters["mch_id"] = $this->config['wechat_mchid'];//商户号
			$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
			$this->parameters["sign"] = $this->getSign($this->parameters);//签名
			return  $this->arrayToXml($this->parameters);
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}
	}

	/**
	 * 	作用：获取结果，使用证书通信
	 */
	function getResult()
	{
		$this->postXmlSSL();
		$this->result = $this->xmlToArray($this->response);
		return $this->result;
	}

}

/**
 * 统一支付接口类
 */
class UnifiedOrder_pub extends Wxpay_client_pub
{
	public $config;
	function __construct($config)
	{
		$this->config = $config;
		//设置接口链接
		$this->url = "https://api.mch.weixin.qq.com/pay/unifiedorder";
		//设置curl超时时间
		$this->curl_timeout = $this->config['CURL_TIMEOUT'];
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		try
		{
			//检测必填参数
			if($this->parameters["out_trade_no"] == null)
			{
				throw new SDKRuntimeException("缺少统一支付接口必填参数out_trade_no！"."<br>");
			}elseif($this->parameters["body"] == null){
				throw new SDKRuntimeException("缺少统一支付接口必填参数body！"."<br>");
			}elseif ($this->parameters["total_fee"] == null ) {
				throw new SDKRuntimeException("缺少统一支付接口必填参数total_fee！"."<br>");
			}elseif ($this->parameters["notify_url"] == null) {
				throw new SDKRuntimeException("缺少统一支付接口必填参数notify_url！"."<br>");
			}elseif ($this->parameters["trade_type"] == null) {
				throw new SDKRuntimeException("缺少统一支付接口必填参数trade_type！"."<br>");
			}elseif ($this->parameters["trade_type"] == "JSAPI" &&
					$this->parameters["openid"] == NULL){
				throw new SDKRuntimeException("统一支付接口中，缺少必填参数openid！trade_type为JSAPI时，openid为必填参数！"."<br>");
			}
			$this->parameters["appid"] = $this->config['wechat_appid'];//公众账号ID
			$this->parameters["mch_id"] = $this->config['wechat_mchid'];//商户号
			$this->parameters["spbill_create_ip"] = $_SERVER['REMOTE_ADDR'];//终端ip
			$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
			$this->parameters["sign"] = $this->getSign($this->parameters);//签名
			return  $this->arrayToXml($this->parameters);
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}
	}

	/**
	 * 获取prepay_id
	 */
	function getPrepayId()
	{
		$this->postXml();
		$this->result = $this->xmlToArray($this->response);
		if(isset($this->result["prepay_id"]))
			$prepay_id = $this->result["prepay_id"];
		else
			$prepay_id = 0;
		return $prepay_id;
	}

}

class OrderQuery_pub extends Wxpay_client_pub
{
	public $config;
	function __construct($config) {
		$this->config = $config;
		//设置接口链接
		$this->url = "https://api.mch.weixin.qq.com/pay/orderquery";
		//设置curl超时时间
		$this->curl_timeout = $this->config['CURL_TIMEOUT'];
	}

	/**
	 * 生成接口参数xml
	 */
	function createXml()
	{
		try
		{
			//检测必填参数
			if($this->parameters["out_trade_no"] == null &&
			$this->parameters["transaction_id"] == null)
			{
				throw new SDKRuntimeException("订单查询接口中，out_trade_no、transaction_id至少填一个！"."<br>");
			}
			$this->parameters["appid"] = $this->config['wechat_appid'];//公众账号ID
			$this->parameters["mch_id"] = $this->config['wechat_mchid'];//商户号
			$this->parameters["nonce_str"] = $this->createNoncestr();//随机字符串
			$this->parameters["sign"] = $this->getSign($this->parameters);//签名
			return  $this->arrayToXml($this->parameters);
		}catch (SDKRuntimeException $e)
		{
			die($e->errorMessage());
		}
	}

}
/**
 * JSAPI支付——H5网页端调起支付接口
 */
class JsApi_pub extends Common_util_pub
{
	var $code;//code码，用以获取openid
	var $openid;//用户的openid
	var $parameters;//jsapi参数，格式为json
	var $prepay_id;//使用统一支付接口得到的预支付id
	var $curl_timeout;//curl超时时间

	public $config;
	function __construct($config) {
		$this->config = $config;
		//设置curl超时时间
		$this->curl_timeout = $this->config['CURL_TIMEOUT'];
	}

	/**
	 * 	作用：生成可以获得code的url
	 */
	function createOauthUrlForCode($redirectUrl)
	{
		$urlObj["appid"] =$this->config['wechat_appid'];
		$urlObj["redirect_uri"] = "$redirectUrl";
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}

	/**
	 * 	作用：生成可以获得openid的url
	 */
	function createOauthUrlForOpenid()
	{
		$urlObj["appid"] = $this->config['wechat_appid'];
		$urlObj["secret"] = $this->config['wechat_secret'];
		$urlObj["code"] = $this->code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}


	/**
	 * 	作用：通过curl向微信提交code，以获取openid
	 */
	function getOpenid()
	{
		$url = $this->createOauthUrlForOpenid();
		//初始化curl
		$ch = curl_init();
		//设置超时
		curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//运行curl，结果以jason形式返回
		$res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res,true);
		$this->openid = $data['openid'];
		return $this->openid;
	}

	/**
	 * 	作用：设置prepay_id
	 */
	function setPrepayId($prepayId)
	{
		$this->prepay_id = $prepayId;
	}

	/**
	 * 	作用：设置code
	 */
	function setCode($code_)
	{
		$this->code = $code_;
	}

	/**
	 * 	作用：设置jsapi的参数
	 */
	public function getParameters()
	{
		$jsApiObj["appId"] = $this->config['wechat_appid'];
		$timeStamp = time();
		$jsApiObj["timeStamp"] = "$timeStamp";
		$jsApiObj["nonceStr"] = $this->createNoncestr();
		$jsApiObj["package"] = "prepay_id=$this->prepay_id";
		$jsApiObj["signType"] = "MD5";
		$jsApiObj["paySign"] = $this->getSign($jsApiObj);
		$this->parameters = $jsApiObj;

		return $this->parameters;
	}
}

class JsApi_helper extends Common_util_pub{
	var $access_token;//公众号的全局唯一票据
	var $token_file;//缓存token的文件
	var $ticket_file;//缓存ticket的文件
	var $curl_timeout;

	public $config;
	function __construct($config) {
		$this->config = $config;
		$this->token_file = '';//APP_PATH.'/data/token';
		$this->ticket_file = '';//APP_PATH.'/data/ticket';
	}

	/**
	 * 	作用：生成可以获得access token的url
	 */
	function createTokenUrl()
	{
		$urlObj["appid"] = $this->config['wechat_appid'];
		$urlObj["secret"] = $this->config['wechat_secret'];
		$urlObj["grant_type"] = "client_credential";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/cgi-bin/token?".$bizString;
	}

	/**
	 * 	作用：生成可以获得jsapi ticket的url
	 */
	function createJsapiTicketUrl()
	{
		$urlObj["access_token"] = $this->access_token;
		$urlObj["type"] = "jsapi";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/cgi-bin/ticket/getticket?".$bizString;
	}

	/**
	 * 作用：生成获取code的url，用于获取用户的openid
	 */
	public function createOauthUrlForCode($redirect_url)
	{
		$urlObj["appid"] = $this->config['wechat_appid'];
		$urlObj["redirect_uri"] = $redirect_url;
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_base";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}

	/**
	 * 作用：生成获取code的url，用于获取用户更多基本信息
	 */
	public function createAuthCodeUrl($redirect_url)
	{
		$urlObj["appid"] = $this->config['wechat_appid'];
		$urlObj["redirect_uri"] = $redirect_url;
		$urlObj["response_type"] = "code";
		$urlObj["scope"] = "snsapi_userinfo";
		$urlObj["state"] = "STATE"."#wechat_redirect";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://open.weixin.qq.com/connect/oauth2/authorize?".$bizString;
	}

	/**
	 * 	作用：生成可以获得access token的url
	 */
	function createAccessTokenUrl($code)
	{
		$urlObj["appid"] = $this->config['wechat_appid'];
		$urlObj["secret"] = $this->config['wechat_secret'];
		$urlObj["code"] = $code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/sns/oauth2/access_token?".$bizString;
	}

	/**
	 * 	作用：生成可以获得用户基本信息的url
	 */
	function createUserinfoUrl($token, $openid){
		$urlObj["access_token"] = $token;
		$urlObj["openid"] = $openid;
		$urlObj["lang"] = "zh_CN";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/sns/userinfo?".$bizString;
	}

	/**
	 * 获取access token
	 * @return
	 */
	function getToken($token_wechat='token_wechat'){
		if($this->getCachedData($access_token,$token_wechat) !== false) { //从缓存中获取token
			$this->access_token = $access_token;
		} else {
			$url = $this->createTokenUrl();
			$data = $this->sendWithCurl($url);
			if(isset($data['errcode']) && $data['errcode'] != 0){
                write_log('getToken'.var_export($data,1).var_export($this->config,1),'crit');
				return $data;
			}
				
			$this->cachedData($data['access_token'],$token_wechat,$data['expires_in']);
			$this->access_token = $data['access_token'];
		}
	}

	/**
	 * 获得jsapi_ticket
	 * @return
	 */
	public function getJsapiTicket(){
		if($this->getCachedData($jsapi_ticket,"ticket_wechat") !== false) {
			$this->jsapi_ticket = $jsapi_ticket;
		} else {
			$url = $this->createJsapiTicketUrl();
			$data = $this->sendWithCurl($url);
			if($data['errcode'] != 0){
                write_log('getJsapiTicket'.var_export($data,1).var_export($this->config,1),'crit');
				return $data;
			}
			$this->jsapi_ticket = $data['ticket'];
			$this->cachedData($data['ticket'],"ticket_wechat",$data['expires_in']);
		}
	}

	/**
	 * 通过code换取网页授权access_token
	 * @param string $code 用户同一授权后回传的code
	 * @return json
	 * {
	 *     "access_token":"ACCESS_TOKEN",
	 *     "expires_in":7200,
	 *     "refresh_token":"REFRESH_TOKEN",
	 *     "openid":"OPENID",
	 *     "scope":"SCOPE"
	 * }
	 */
	public function getTokenByCode($code){
		$url = $this->createAccessTokenUrl($code);
		$data = $this->sendWithCurl($url);

		return $data;
	}

	/**
	 * 拉取用户信息(需scope为 snsapi_userinfo)
	 * @param string $token 网页授权的token
	 * @param string $openid 用户的openid
	 * @return json
	 * {
	 * 	 "openid":" OPENID",
	 * 	 "nickname": NICKNAME,
	 *    "sex":"1",
	 *    "province":"PROVINCE"
	 *    "city":"CITY",
	 *    "country":"COUNTRY",
	 *    "headimgurl":"http://wx.qlogo.cn/mmopen/g3MonUZtNHkdmzicIlibx6iaFqAc56vxLSUfpb6n5WKSYVY0ChQKkiaJSgQ1dZuTOgvLLrhJbERQQ4eMsv84eavHiaiceqxibJxCfHe/46",
	 *    "privilege":["PRIVILEGE1" "PRIVILEGE2"],
	 *    "unionid": "o6_bmasdasdsad6_2sgVt7hMZOPfL"
	 * }
	 */
	public function getUserInfoByTokenAndOpenid($token, $openid){
		$url = $this->createUserinfoUrl($token, $openid);
		$data = $this->sendWithCurl($url);
		return $data;
	}

	/**
	 * JS-SDK使用权限签名算法
	 * @param string $jsapi_ticket 公众号用于调用微信JS接口的临时票据
	 * @param string $url 调用JS接口页面的完整URL（不包含#后面）
	 * @return array 签名结果
	 */
	public function signature($noncestr, $timestamp, $url){
		$data = $this->getToken();
		if($data){
			return $data;
		}
		$data = $this->getJsapiTicket();
		if($data){
			return $data;
		}

		$param = array(
				'noncestr' => $noncestr,
				'jsapi_ticket' => $this->jsapi_ticket,
				'timestamp' => $timestamp,
				'url' => $url
		);

		ksort($param);
		$str = array();
		foreach($param as $k=>$v){
			$str[] = $k . '=' . $v;
		}
		$str = implode('&', $str);

		$result = array();
		$result['signature'] = sha1($str);
		$result['original'] = $str;
		return $result;
	}

	/**
	 * 缓存token
	 * @param array $data
	 * @return boolean
	 */
	function cachedData($data, $type, $expires) {

        $ci =&get_instance();
        $data = $ci->cache->save('wx_citybox_'.$type,$data,$expires);
		if (!$data) {
			die ( "保存token失败" );
		}
		
// 		$file = $this->getFileNameByType($type);
// 		if(!file_exists(dirname($file))) {
// 			@mkdir(dirname($file), 0777, true);
// 		}
// 		//计算过期时间
// 		$result['expires_end'] = time() + $expires;
// 		$result['data'] = $data;
// 		$str = "";
// 		foreach($result as $key=>$value) {
// 			$str .= "{$key} = $value\n";
// 		}
// 		file_put_contents($file, $str);
	}

	/**
	 * 获取缓存的数据
	 * @param array $data
	 * @return boolean
	 */
	function getCachedData(&$data, $type) {

        $ci =&get_instance();
        $data = $ci->cache->get('wx_citybox_'.$type);
		
		return $data;
// 		$file = $this->getFileNameByType($type);
// 		if(file_exists($file)) {
// 			$result = parse_ini_file($file);
// 			if($result['expires_end'] > time()) {
// 				$data = $result['data'];
// 				return true;
// 			}
// 		}
// 		return false;
	}

	/**
	 * 根据不同类型获取文件路径
	 * @param array $type
	 * @return string
	 */
	function getFileNameByType($type){
		if($type == "ticket"){
			return $this->ticket_file;
		}else{
			return $this->token_file;
		}

	}

	/**
	 * 通过curl调用
	 * @param array $url
	 * @return result
	 */
	function sendWithCurl($url){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
		curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		$res = curl_exec($ch);
		curl_close($ch);
		return json_decode($res,true);
	}
}

/**
 * Class Papay
 * 微信免密支付-签署免密协议
 */
Class Papay_entrustweb extends Common_util_pub{

    var $contract_code;//商户侧的签约协议号，由商户生成
    var $request_serial;//商户请求签约时的序列号，商户侧须唯一。序列号主要用于排序，不作为查询条件
    var $parameters;//jsapi参数，格式为json
    var $contract_display_account;//签约用户的名称，用于页面展示，
    var $curl_timeout;//curl超时时间
    var $notify_url;//回调通知

    public $config;

    function __construct($config) {
        $this->config = $config;
        //设置curl超时时间
        $this->curl_timeout = $this->config['CURL_TIMEOUT'];
    }

    function setContract_code($contract_code)
    {
        $this->contract_code = $contract_code;
    }

    function setRequest_serial($request_serial)
    {
        $this->request_serial = $request_serial;
    }

    function setContract_display_account($contract_display_account)
    {
        $this->contract_display_account = $contract_display_account;
    }


    /**
     * 	作用：设置jsapi的参数
     */
    public function getParameters()
    {
        $jsApiObj["mch_id"] = $this->config['wechat_mchid'];
        $jsApiObj["appid"] = $this->config['wechat_appid'];
        $jsApiObj["plan_id"] = $this->config['wechat_planid'];
        $timeStamp = time();
        $jsApiObj["timestamp"] = "$timeStamp";
        $jsApiObj["contract_code"] = $this->contract_code;
        $jsApiObj["request_serial"] = $this->request_serial;
        $jsApiObj["contract_display_account"] = $this->contract_display_account;
        $jsApiObj["notify_url"] = $this->config['entrustweb_notify_url'];
        $jsApiObj["return_web"] = 1;//1表示返回签约页面的referrer url, 0 或不填或获取不到referrer则不返回; 跳转referrer url时会自动带上参数from_wxpay=1
        $jsApiObj["version"] = "1.0";
        $jsApiObj["sign"] = $this->getSign($jsApiObj);
        //HTTP或者HTTPS开头的回调通知url,传输时需要对url进行encode，签名时要使用原值，特别注意：由于sdk的原因，ios需要encode两次
        $jsApiObj["notify_url"] = urlencode($this->config['entrustweb_notify_url']);
        $this->parameters = $jsApiObj;
        return $this->parameters;
    }

    public function entrustweb(){
        $urlObj = $this->getParameters();
        $bizString = $this->formatBizQueryParaMap($urlObj, false);
        return "https://api.mch.weixin.qq.com/papay/entrustweb?".$bizString;
	}

    public function entrustweb_program(){
        $jsApiObj["mch_id"] = $this->config['wechat_mchid'];
        $jsApiObj["appid"] = $this->config['wechat_appid'];
        $jsApiObj["plan_id"] = $this->config['wechat_planid'];
        $timeStamp = time();
        $jsApiObj["timestamp"] = "$timeStamp";
        $jsApiObj["contract_code"] = $this->contract_code;
        $jsApiObj["request_serial"] = $this->request_serial;
        $jsApiObj["contract_display_account"] = $this->contract_display_account;
        $jsApiObj["notify_url"] = $this->config['p_entrustweb_notify_url'];
        $jsApiObj["sign"] = $this->getSign($jsApiObj);
        //HTTP或者HTTPS开头的回调通知url,传输时需要对url进行encode，签名时要使用原值，特别注意：由于sdk的原因，ios需要encode两次
        $jsApiObj["notify_url"] = urlencode($this->config['p_entrustweb_notify_url']);
        $this->parameters = $jsApiObj;
        return $this->parameters;

    }
}

/**
 * Class Papay_querycontract
 * 微信免密支付-查询签约关系
 */
Class Papay_querycontract extends Common_util_pub{

    var $contract_id;//委托代扣签约成功后由微信返回的委托代扣协议id，选择contract_id查询，则此参数必填
    var $parameters;//jsapi参数，格式为json
    var $curl_timeout;//curl超时时间
    var $open_id;

    public $config;

    function __construct($config) {
        $this->config = $config;
        //设置curl超时时间
        $this->curl_timeout = $this->config['CURL_TIMEOUT'];
    }

    function setContract_id($contract_id)
    {
        $this->contract_id = $contract_id;
    }

    function setOpen_id($open_id)
    {
        $this->open_id = $open_id;
    }



    /**
     * 	作用：设置jsapi的参数
     */
    public function getParameters()
    {
        $jsApiObj["mch_id"] = $this->config['wechat_mchid'];
        $jsApiObj["appid"] = $this->config['wechat_appid'];
        $jsApiObj["contract_id"] = $this->contract_id;
        $jsApiObj["plan_id"] = $this->config['wechat_planid'];
        $jsApiObj["openid"] = $this->open_id;
        $jsApiObj["version"] = "1.0";
        $jsApiObj["sign"] = $this->getSign($jsApiObj);
        $this->parameters = $jsApiObj;
        return $this->parameters;
    }

    public function querycontract(){
        $xml = $this->arrayToXml_new($this->getParameters());
		$this->response = $this->postXmlCurl($xml,'https://api.mch.weixin.qq.com/papay/querycontract',$this->curl_timeout);
		$rs = $this->xmlToArray($this->response);
		write_log('querycontract '.var_export($xml,1).',result='.var_export($rs,1));
		return $rs;
    }
}

/**
 * Class Papay_apply
 * 微信免密支付-申请扣款
 */
Class Papay_apply extends Common_util_pub{

    var $contract_id;//委托代扣签约成功后由微信返回的委托代扣协议id，选择contract_id查询，则此参数必填
    var $body;//商品或支付单简要描述
    var $attach;//附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
    var $out_trade_no;//商户系统内部的订单号,32个字符内、可包含字母, 其他说明见商户订单号
    var $total_fee;//订单总金额，单位为分，只能为整数，详见支付金额
    var $notify_url;//
    var $spbill_create_ip;//调用微信支付API的机器IP
    var $parameters;//jsapi参数，格式为json
    var $curl_timeout;//curl超时时间

    public $config;

    function __construct($config) {
        $this->config = $config;
        //设置curl超时时间
        $this->curl_timeout = $this->config['CURL_TIMEOUT'];
    }

    function setContract_id($contract_id)
    {
        $this->contract_id = $contract_id;
    }
    function setBody($body)
    {
        $this->body = $body;
    }
    function setAttach($attach)
    {
        $this->attach = $attach;
    }
    function setOut_trade_no($out_trade_no)
    {
        $this->out_trade_no = $out_trade_no;
    }
    function setTotal_fee($total_fee)
    {
        $this->total_fee = $total_fee;
    }

    /**
     * 	作用：设置jsapi的参数
     */
    public function getParameters()
    {
        $jsApiObj["mch_id"] = $this->config['wechat_mchid'];
        $jsApiObj["appid"] = $this->config['wechat_appid'];
        $jsApiObj["nonce_str"] = $this->createNoncestr();
        $jsApiObj["body"] = $this->body;
        $jsApiObj["out_trade_no"] = $this->out_trade_no;
        if(!empty($this->attach)){
            $jsApiObj["attach"] =  $this->attach;
		}
        $jsApiObj["total_fee"] = $this->total_fee;
        $jsApiObj["contract_id"] = $this->contract_id;
        $jsApiObj["spbill_create_ip"] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : '8.8.8.8';
        $jsApiObj["notify_url"] = $this->config['pay_notify_url'];
        $jsApiObj["trade_type"] = "PAP";
        $jsApiObj["sign"] = $this->getSign($jsApiObj);
        $this->parameters = $jsApiObj;
        return $this->parameters;
    }

    public function pay_apply(){
        $xml = $this->arrayToXml_new($this->getParameters());
        write_log('wechat-pay-paraxml:'.var_export($xml,1),'info');
        $this->response = $this->postXmlCurl($xml,'https://api.mch.weixin.qq.com/pay/pappayapply',$this->curl_timeout);
        write_log('wechat-pay-result:'.var_export($this->response,1),'info');
        return $this->xmlToArray($this->response);
    }
}

/**
 * https://mp.weixin.qq.com/debug/wxadoc/dev/api/api-login.html#wxloginobject
 * Class Program_jscode2session
 * code 换取 session_key
 */
Class Program_jscode2session extends Common_util_pub{

    var $appid;//小程序唯一标识
    var $secret;//小程序的 app secret
    var $js_code;
    var $grant_type;//填写为 authorization_code


    public $config;

    function __construct($config) {
        $this->config = $config;
        //设置curl超时时间
        $this->curl_timeout = $this->config['CURL_TIMEOUT'];
    }

    public function jscode2session($code){
        $jsApiObj["appid"] = $this->config['wechat_program_appid'];
        $jsApiObj["secret"] = $this->config['wechat_program_secret'];
        $jsApiObj["js_code"] = $code;
        $jsApiObj["grant_type"] = "authorization_code";
        write_log(var_export($jsApiObj,1));
        $bizString = $this->formatBizQueryParaMap($jsApiObj, false);
        $url = "https://api.weixin.qq.com/sns/jscode2session?".$bizString;
        return $this->getCurl($url,$this->curl_timeout);
    }


}
?>

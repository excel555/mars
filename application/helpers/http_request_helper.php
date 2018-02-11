<?php
class HttpRequest {
	public static function sendPostRequst($url, $data) {
		$postdata = http_build_query ( $data );
// 		print_r($postdata);
		$opts = array (
			'http' => array (
				'method' => 'POST',
				'header' => 'Content-type: application/x-www-form-urlencoded',
				'content' => $postdata
			)
		);


		$context = stream_context_create ( $opts );

		$result = file_get_contents ( $url, false, $context );
		return $result;
	}

	public static function getRequest($key) {
		$request = null;
		if (isset ( $_GET [$key] ) && ! empty ( $_GET [$key] )) {
			$request = $_GET [$key];
		} elseif (isset ( $_POST [$key] ) && ! empty ( $_POST [$key] )) {
			$request = $_POST [$key];
		}
		return $request;
	}

	public static function curl($url, $postFields = null,$is_raw = 0) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_FAILONERROR, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch,CURLOPT_TIMEOUT,5);//超时时间为5s
		$postBodyString = "";
		$encodeArray = Array();
		$postMultipart = false;




		if($is_raw){
			curl_setopt($ch, CURLOPT_POSTFIELDS,$postFields );
			$headers = array('Content-Type: application/json; charset=utf-8');
		}else{
			if (is_array($postFields) && 0 < count($postFields)) {
				foreach ($postFields as $k => $v) {
					if ("@" != substr($v, 0, 1)) //判断是不是文件上传
					{
						$postBodyString .= "$k=" . urlencode(self::characet($v, 'UTF-8')) . "&";
						$encodeArray[$k] = self::characet($v, 'UTF-8');
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
				write_log( "curl url: ".$url."&".substr($postBodyString, 0, -1));
			}
			$headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
		}


		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

		$reponse = curl_exec($ch);


		if (curl_errno($ch)) {

			$reponse = curl_error($ch);
		} else {
			$httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			if (200 !== $httpStatusCode) {
                $reponse = $reponse;
                write_log('curl code:'.$httpStatusCode);
			}
		}

		curl_close($ch);
		return $reponse;
	}


    public static function curl_get($url,$params){
	    if($params){
            $url .= '?';
	        foreach ($params  as $k=>$v){
                $url .= $k.'='.$v.'&';
            }
            $url = rtrim($url,'&');
        }
        $ch = curl_init();
        $headers = array('content-type: application/x-www-form-urlencoded;charset=UTF-8');
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        if (curl_errno($ch)) {

            $result = curl_error($ch);
        } else {
            $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (200 !== $httpStatusCode) {
                write_log('curl code:'.$httpStatusCode.",response:".$result);
            }
        }
        curl_close($ch);
        return $result;
    }

	public static  function characet($data, $targetCharset) {

		if (!empty($data)) {
			$fileType = "UTF-8";
			if (strcasecmp($fileType, $targetCharset) != 0) {
				$data = mb_convert_encoding($data, $targetCharset, $fileType);
			}
		}
		return $data;
	}
}
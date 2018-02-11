<?php
/**
 * ZHIMA API: zhima.merchant.single.data.upload request
 *
 * @author auto create
 * @since 1.0, 2017-04-28 14:30:40
 */
class ZhimaMerchantSingleDataUploadRequest
{
	/** 
	 * 业务扩展参数
	 **/
	private $bizExtParams;
	
	/** 
	 * 传入的json数据
	 **/
	private $data;
	
	/** 
	 * 主键列使用传入字段进行组合，也可以使用传入的某个单字段（确保主键稳定，而且可以很好的区分不同的数据）。例如order_no,pay_month或者order_no,bill_month组合，对于一个order_no只会有一条数据的情况，直接使用order_no作为主键列
	 **/
	private $primaryKeys;
	
	/** 
	 * 场景码
	 **/
	private $sceneCode;

	private $apiParas = array();
	private $fileParas = array();
	private $apiVersion="1.0";
	private $scene;
	private $channel;
	private $platform;
	private $extParams;

	
	public function setBizExtParams($bizExtParams)
	{
		$this->bizExtParams = $bizExtParams;
		$this->apiParas["biz_ext_params"] = $bizExtParams;
	}

	public function getBizExtParams()
	{
		return $this->bizExtParams;
	}

	public function setData($data)
	{
		$this->data = $data;
		$this->apiParas["data"] = $data;
	}

	public function getData()
	{
		return $this->data;
	}

	public function setPrimaryKeys($primaryKeys)
	{
		$this->primaryKeys = $primaryKeys;
		$this->apiParas["primary_keys"] = $primaryKeys;
	}

	public function getPrimaryKeys()
	{
		return $this->primaryKeys;
	}

	public function setSceneCode($sceneCode)
	{
		$this->sceneCode = $sceneCode;
		$this->apiParas["scene_code"] = $sceneCode;
	}

	public function getSceneCode()
	{
		return $this->sceneCode;
	}

	public function getApiMethodName()
	{
		return "zhima.merchant.single.data.upload";
	}

	public function setScene($scene)
	{
		$this->scene=$scene;
	}

	public function getScene()
	{
		return $this->scene;
	}
	
	public function setChannel($channel)
	{
		$this->channel=$channel;
	}

	public function getChannel()
	{
		return $this->channel;
	}
	
	public function setPlatform($platform)
	{
		$this->platform=$platform;
	}

	public function getPlatform()
	{
		return $this->platform;
	}

	public function setExtParams($extParams)
	{
		$this->extParams=$extParams;
	}

	public function getExtParams()
	{
		return $this->extParams;
	}	

	public function getApiParas()
	{
		return $this->apiParas;
	}
	
	public function getFileParas()
	{
		return $this->fileParas;
	}

	public function setApiVersion($apiVersion)
	{
		$this->apiVersion=$apiVersion;
	}

	public function getApiVersion()
	{
		return $this->apiVersion;
	}

}

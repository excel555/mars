<?php
/**
 * ALIPAY API: alipay.dut.customer.agreement.query
 *
 * 支付宝个人协议查询接口
 * @author auto create
 * @since 1.0, 2016-05-23 14:55:42
 */
class AlipayDutCustomerAgreementQueryRequest
{

	private $service;
	private $product_code;
	private $alipay_user_id;
	private $alipay_logon_id;
    private $third_party_type;
	private $scene;
	private $external_sign_no;
	private $app_id;
	private $apiParas = array();

	/**
	 * @return array
	 */
	public function getApiParas()
	{
		return $this->apiParas;
	}


	/**
	 * @return mixed
	 */
	public function getService()
	{
		return "alipay.dut.customer.agreement.query";
	}

	/**
	 * @return mixed
	 */
	public function getProductCode()
	{
		return $this->product_code;

	}

	/**
	 * @return mixed
	 */
	public function getAlipayUserId()
	{
		return $this->alipay_user_id;
	}

	/**
	 * @return mixed
	 */
	public function getAlipayLogonId()
	{
		return $this->alipay_logon_id;
	}

	/**
	 * @return mixed
	 */
	public function getThirdPartyType()
	{
		return $this->third_party_type;
	}

	/**
	 * @return mixed
	 */
	public function getScene()
	{
		return $this->scene;
	}

	/**
	 * @return mixed
	 */
	public function getExternalSignNo()
	{

		return $this->external_sign_no;
	}

	/**
	 * @param mixed $service
	 */
	public function setService($service)
	{
		$this->service = $service;
	}

	/**
	 * @param mixed $product_code
	 */
	public function setProductCode($product_code)
	{
		$this->product_code = $product_code;
		$this->apiParas["product_code"] = $product_code;
	}

	/**
	 * @param mixed $alipay_user_id
	 */
	public function setAlipayUserId($alipay_user_id)
	{
		$this->alipay_user_id = $alipay_user_id;
		$this->apiParas["alipay_user_id"] = $alipay_user_id;
	}

	public function setAppId($app_id)
	{
		$this->app_id = $app_id;
		$this->apiParas["app_id"] = $app_id;
	}
	/**
	 * @param mixed $alipay_logon_id
	 */
	public function setAlipayLogonId($alipay_logon_id)
	{
		$this->alipay_logon_id = $alipay_logon_id;
		$this->apiParas["alipay_logon_id"] = $alipay_logon_id;
	}

	/**
	 * @param mixed $third_party_type
	 */
	public function setThirdPartyType($third_party_type)
	{
		$this->third_party_type = $third_party_type;
		$this->apiParas["third_party_type"] = $third_party_type;
	}

	/**
	 * @param mixed $scene
	 */
	public function setScene($scene)
	{
		$this->scene = $scene;
		$this->apiParas["scene"] = $scene;
	}

	/**
	 * @param mixed $external_sign_no
	 */
	public function setExternalSignNo($external_sign_no)
	{
		$this->external_sign_no = $external_sign_no;
		$this->apiParas["external_sign_no"] = $external_sign_no;
	}

}

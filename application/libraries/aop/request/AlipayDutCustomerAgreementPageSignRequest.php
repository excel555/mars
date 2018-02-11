<?php
/**
 * ALIPAY API: alipay.dut.customer.agreement.query
 *
 * 支付宝个人协议
 * @author auto create
 * @since 1.0, 2016-05-23 14:55:42
 */
class AlipayDutCustomerAgreementPageSignRequest
{

	private $service;
	private $product_code;
	private $return_url;
	private $notify_url;
    private $access_info;
	private $scene;
	private $sign_validity_period;
	private $external_sign_no;
	private $agreement_detail;
	private $prod_properties;
	private $external_user_id;
	private $zm_auth_info;
	private $sales_product_code;
	private $third_party_type;
	private $apiParas = array();

	/**
	 * @return mixed
	 */
	public function getReturnUrl()
	{
		return $this->return_url;
	}

	/**
	 * @param mixed $return_url
	 */
	public function setReturnUrl($return_url)
	{
		$this->return_url = $return_url;
		$this->apiParas["return_url"] = $return_url;
	}

	/**
	 * @return mixed
	 */
	public function getNotifyUrl()
	{
		return $this->notify_url;
	}

	/**
	 * @param mixed $notify_url
	 */
	public function setNotifyUrl($notify_url)
	{
		$this->notify_url = $notify_url;
		$this->apiParas["notify_url"] = $notify_url;
	}

	/**
	 * @return mixed
	 */
	public function getAccessInfo()
	{
		return $this->access_info;
	}

	/**
	 * @param mixed $access_info
	 */
	public function setAccessInfo($access_info)
	{
		$this->access_info = $access_info;
		$this->apiParas["access_info"] = $access_info;
	}

	/**
	 * @return mixed
	 */
	public function getSignValidityPeriod()
	{
		return $this->sign_validity_period;
	}

	/**
	 * @param mixed $sign_validity_period
	 */
	public function setSignValidityPeriod($sign_validity_period)
	{
		$this->sign_validity_period = $sign_validity_period;
		$this->apiParas["sign_validity_period"] = $sign_validity_period;
	}

	/**
	 * @return mixed
	 */
	public function getAgreementDetail()
	{
		return $this->agreement_detail;
	}

	/**
	 * @param mixed $agreement_detail
	 */
	public function setAgreementDetail($agreement_detail)
	{
		$this->agreement_detail = $agreement_detail;
		$this->apiParas["agreement_detail"] = $agreement_detail;
	}

	/**
	 * @return mixed
	 */
	public function getProdProperties()
	{
		return $this->prod_properties;
	}

	/**
	 * @param mixed $prod_properties
	 */
	public function setProdProperties($prod_properties)
	{
		$this->prod_properties = $prod_properties;
		$this->apiParas["prod_properties"] = $prod_properties;
	}

	/**
	 * @return mixed
	 */
	public function getExternalUserId()
	{
		return $this->external_user_id;
	}

	/**
	 * @param mixed $external_user_id
	 */
	public function setExternalUserId($external_user_id)
	{
		$this->external_user_id = $external_user_id;
		$this->apiParas["external_user_id"] = $external_user_id;
	}

	/**
	 * @return mixed
	 */
	public function getZmAuthInfo()
	{
		return $this->zm_auth_info;
	}

	/**
	 * @param mixed $zm_auth_info
	 */
	public function setZmAuthInfo($zm_auth_info)
	{
		$this->zm_auth_info = $zm_auth_info;
		$this->apiParas["zm_auth_info"] = $zm_auth_info;
	}

	/**
	 * @return mixed
	 */
	public function getSalesProductCode()
	{
		return $this->sales_product_code;
	}

	/**
	 * @param mixed $sales_product_code
	 */
	public function setSalesProductCode($sales_product_code)
	{
		$this->sales_product_code = $sales_product_code;
		$this->apiParas["sales_product_code"] = $sales_product_code;
	}


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
		return "alipay.dut.customer.agreement.page.sign";
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

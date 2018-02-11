<?php
/**
 *
 * @author auto create
 * @since 1.0, 2016-06-24 14:37:49
 */
class CommonRequest
{
	private $apiParas = array();
	private $api;

	
	public function setParameter($parameter,$parameterValue)
	{
		$this->apiParas[$parameter] = $parameterValue;
	}
    public function setApi($api)
    {
        $this->api = $api;
    }

	public function getApiMethodName()
	{
		return $this->api;
	}

	public function getApiParas()
	{
		return $this->apiParas;
	}

}

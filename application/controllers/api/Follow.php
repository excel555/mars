<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Follow extends REST_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper("http_request");
        $this->load->library("aop/request/AlipayMobilePublicFollowListRequest");
    }


    public function list_get()
    {
        $biz_content = "{\"nextUserId\":\"\"}";

        $request = new AlipayMobilePublicFollowListRequest ();
        $request->setBizContent ( $biz_content );

        $result = aopclient_request_execute($request);
        // var_dump($result);

        if ($result != null && $result->alipay_mobile_public_follow_list_response->code == 200) {
            $list = $result->alipay_mobile_public_follow_list_response->data->user_id_list->string;
            print_r ( $list );
        }
    }

}

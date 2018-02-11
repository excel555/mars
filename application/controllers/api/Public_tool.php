<?php
/**
 * 支付宝推广工具
 */
defined('BASEPATH') OR exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Public_tool extends REST_Controller
{

    function __construct()
    {
        parent::__construct();
        $this->load->helper("aop_send");
        $this->load->helper("http_request");
        $this->load->library("aop/request/AlipayMobilePublicQrcodeCreateRequest");
        $this->load->library("aop/request/AlipayOpenPublicShortlinkCreateRequest");
    }

    /**
     * 推广的二维码
     */
    public function create_qr_code_get()
    {
        $box_id = $this->get("box_id");
        $refer = $this->get("refer");
        if(empty($refer))
            $refer = 'alipay';
        $this->check_null_and_send_error($box_id, "零售机的id不能为空");
        if($refer == "alipay"){
            $this->config->load("tips", TRUE);
            $qr_code_go_url = $this->config->item("qr_code_go_url", "tips");
            $qr_biz = array (
                'codeInfo' => array (
                    'scene' => array (
                        'sceneId' => $box_id
                    ),
                    'gotoUrl'=>$qr_code_go_url.'?deviceId='.$box_id
                ),
                'codeType' => 'PERM',//PERM永久 TEMP 临时
                'expireSecond' => '', //临时码过期时间，以秒为单位，最大不超过1800秒； 永久码置空
                'showLogo' => 'Y'
            );

            $qr_biz = json_encode ( $qr_biz );
            $request = new AlipayMobilePublicQrcodeCreateRequest ();
            $request->setBizContent ( $qr_biz );

            $config = get_platform_config_by_device_id($box_id);

            $result = aopclient_request_execute($request,null,$config);
            if ($result->alipay_mobile_public_qrcode_create_response->code == 200) {
                $qr_img_url = $result->alipay_mobile_public_qrcode_create_response->code_img;
                write_log ( "返回的二维码地址：" . $qr_img_url );
                $this->send_ok_response(array("qr_img"=>$qr_img_url));
            }else{
                $this->send_error_response($result->alipay_mobile_public_qrcode_create_response->sub_msg);
            }
        } else {
            //生成二维码统一用这个
            $qr_url = get_device_qr_common_pr($box_id);
            $qr_url = str_replace('DEVICEID',$box_id,$qr_url);//替换关键字
            $img = general_qr_code($qr_url);
            $base_url = $this->config->item("base_url");
            $this->send_ok_response(array("qr_img"=>$base_url.'/uploads/'.$img,'url'=>$qr_url));
        }
//        else{
//            $base_url = $this->config->item("base_url");
//            $qr_str = $base_url.'/public/platform_auth.html?deviceId='.$box_id.'&scan_platform='.$refer;
//            $img = general_qr_code($qr_str);
//            $this->send_ok_response(array("qr_img"=>$base_url.'/uploads/'.$img));
//            //$short_url = general_short_url($qr_str);
////            if(substr($short_url,0,13) == 'http://980.so'){
////                $img = general_qr_code($short_url);
////                $this->send_ok_response(array("qr_img"=>$base_url.'/uploads/'.$img));
////            }else{
////                $this->send_error_response('生成短链接出错');
////            }
//
//        }

    }

    /**
     * 推广短连接
     */
    public function create_short_url_get()
    {
        $box_id = $this->get("box_id");
        $remark = $this->get("remark");
        $this->check_null_and_send_error($box_id, "零售机的id不能为空");
        $qr_biz = array (
            'scene_id' => $box_id,
            'remark' => $remark
        );
        $qr_biz = json_encode ( $qr_biz );
        $request = new AlipayOpenPublicShortlinkCreateRequest ();
        $request->setBizContent ( $qr_biz );

        $config = get_platform_config_by_device_id($box_id);

        $result = aopclient_request_execute($request,null,$config);
        if ($result->alipay_open_public_shortlink_create_response->code == 10000) {
            $shortlink = $result->alipay_open_public_shortlink_create_response->shortlink;
            write_log ( "返回的短连接地址：" . $shortlink );
            $this->send_ok_response(array("shortlink"=>$shortlink));
        }else{
            $this->send_error_response($result->alipay_open_public_shortlink_create_response->sub_msg);
        }
    }

    public function general_qr_code_get()
    {
        $qr_str = $this->get('qr_str');
        $img = general_qr_code($qr_str);
        $base_url = $this->config->item("base_url");
        $this->send_ok_response($base_url.'/uploads/'.$img);
    }
    public function wx_token_get(){
        $this->load->helper("wechat_send");
        $rs = get_wechat_token_execute();
        $this->send_ok_response($rs);
    }

    public function del_wx_token_get(){
        $type = 'token_wechat';
        $this->cache->delete('wx_citybox_'.$type);
        $this->send_ok_response($this->cache->get('wx_citybox_'.$type));

    }

    public function test_wx_msg_get(){
        $this->load->helper("message");
        $data = array(
            'buyer_id'=>'oCUeIwo4R1esx5bJZxu1R2y9vbK0',
            'url'=>'https://cityboxapi.fruitday.com/public/clear.html',
            'first'=>'测试是否能发送微信msg',
            'keyword1'=>'12',
            'keyword2'=>'12',
            'keyword3'=>'12',
            'keyword4'=>'121',
            'remark'=>'12',
        );
        Message::send_warn_exception_msg($data,'wechat','1231');
    }

    public function test_program_msg_get(){
        $this->load->helper("message");
        $data = array(
            'buyer_id'=>'oxl4R0YN_YTLZsGlYrAle43yUa8A',
            'page'=>'pages/order/list/index',
            'first'=>'测试是否能发送微信msg',
            'keyword1'=>'12',
            'keyword2'=>'12',
            'keyword3'=>'12',
            'keyword4'=>'121',
            'remark'=>'12',
            'form_id'=>"wx201710131738592e866ae4e10509929616",
            'emphasis_keyword'=>'keyword1.DATA'
        );
        Message::send_pay_succ_msg($data,'program','1231');
    }
}

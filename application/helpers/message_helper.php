<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/29/17
 * Time: 14:44
 */
require_once FCPATH . 'application/helpers/http_request_helper.php';
require_once FCPATH . 'application/libraries/aop/request/AlipayOpenPublicMessageSingleSendRequest.php';


class Message
{
    public $UserInfo;
    public $FromUserId;
    public $CreateTime;
    public $AppId;
    public $MsgType;
    public $EventType;
    public $AgreementId;
    public $ActionParam;
    public $AccountNo;
    public $Text;
    public $MediaId;
    public $Format;

    public function plain_msg($biz_content)
    {
        header("Content-Type: text/xml;charset=GBK");
        write_log($biz_content);
        $this->UserInfo = $this->getNode($biz_content, "UserInfo");
        $this->FromUserId = $this->getNode($biz_content, "FromAlipayUserId");
        $this->AppId = $this->getNode($biz_content, "AppId");
        $this->CreateTime = $this->getNode($biz_content, "CreateTime");
        $this->MsgType = $this->getNode($biz_content, "MsgType");
        $this->EventType = $this->getNode($biz_content, "EventType");
        $this->AgreementId = $this->getNode($biz_content, "AgreementId");
        $this->ActionParam = $this->getNode($biz_content, "ActionParam");
        $this->AccountNo = $this->getNode($biz_content, "AccountNo");
        $this->Text = $this->getNode($biz_content, "Text");
        $this->MediaId = $this->getNode($biz_content, "MediaId");
        $this->Format = $this->getNode($biz_content, "Format");
    }

    /**
     * 直接获取xml中某个结点的内容
     *
     * @param unknown $xml
     * @param unknown $node
     */
    public function getNode($xml, $node)
    {
        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>" . $xml;
        $dom = new DOMDocument ("1.0", "UTF-8");
        $dom->loadXML($xml);
        $event_type = $dom->getElementsByTagName($node);
        return $event_type->item(0)->nodeValue;
    }

    /**
     * 单发消息
     * @param $bizContent
     * @return bool|mixed|SimpleXMLElement
     */
    public static function sendAlipayOpenPublicMessageSingle($type, $data,$device_id)
    {

        $request = new AlipayOpenPublicMessageSingleSendRequest ();
        $ci =& get_instance();
        $ci->load->helper("aop_send");

        $config = get_platform_config_by_device_id($device_id);

        $tpl_id = $config[$type];

        if (iconv_strlen($data['first']) > 256) {
            $data['first'] = iconv_substr($data['first'], 0, 253) . "...";
        }

        $msg_content = array(
            'to_user_id' => $data['buyer_id'],
            'template' => array(
                'template_id' => $tpl_id,
                'context' => array(
                    'head_color' => '#000000',
                    'url' => $data['url'],
                    'action_name' => $data['action'],
                    'first' => array(
                        'color' => '#000000',
                        'value' => $data['first']
                    ),
                    'keyword1' => array(
                        'color' => '#0089cd',
                        'value' => $data['keyword1']
                    ),
                    'keyword2' => array(
                        'color' => '#0089cd',
                        'value' => $data['keyword2']
                    ),
                    'keyword3' => array(
                        'color' => '#0089cd',
                        'value' => $data['keyword3']
                    ),
                    'keyword4' => array(
                        'color' => '#0089cd',
                        'value' => $data['keyword4']
                    ),
                    'remark' => array(
                        'color' => '#000000',
                        'value' => $data['remark']
                    ),
                )
            )
        );
        if (!$data['url']) {
            unset($msg_content['template']['context']['url']);
        }
        if (!$data['action']) {
            unset($msg_content['template']['context']['action_name']);
        }
        if (!$data['keyword2']) {
            unset($msg_content['template']['context']['keyword2']);
        }
        if (!$data['keyword3']) {
            unset($msg_content['template']['context']['keyword3']);
        }
        if (!$data['keyword4']) {
            unset($msg_content['template']['context']['keyword4']);
        }
        if (!$data['remark']) {
            unset($msg_content['template']['context']['remark']);
        }
//        write_log("msg_content" . var_export($msg_content, 1));
        $request->setBizContent(json_encode($msg_content));


        return aopclient_request_execute($request,null,$config);
    }


    public static function sendProgramMessageSingle($type, $data,$device_id=''){
        $ci =& get_instance();
        $ci->load->helper("wechat_send");
        $platform_config = get_platform_config_by_device_id($device_id);
        $tpl_id = $platform_config['wechat_'.$type];

        if (iconv_strlen($data['first']) > 256) {
            $data['first'] = iconv_substr($data['first'], 0, 253) . "...";
        }
        if ($data['keyword4'] && iconv_strlen($data['keyword4']) > 256) {
            $data['keyword4'] = iconv_substr($data['keyword4'], 0, 253) . "...";
        }

        $msg_content = array(
            'touser' => $data['buyer_id'],
            'template_id' => $tpl_id,
            'page' => $data['page'],
            'form_id'=>$data['form_id'],
            "emphasis_keyword"=> $data['emphasis_keyword'],
            'data' => array(
                'first' => array(
                    'color' => '#0089cd',
                    'value' => $data['first']
                ),
                'keyword1' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword1']
                ),
                'keyword2' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword2']
                ),
                'keyword3' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword3']
                ),
                'keyword4' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword4']
                )
            )
        );
        if (!$data['page']) {
            unset($msg_content['data']['page']);
        }
        if (!$data['keyword1']) {
            unset($msg_content['data']['keyword1']);
        }
        if (!$data['keyword2']) {
            unset($msg_content['data']['keyword2']);
        }
        if (!$data['keyword3']) {
            unset($msg_content['data']['keyword3']);
        }
        if (!$data['keyword4']) {
            unset($msg_content['data']['keyword4']);
        }
        if (!$data['emphasis_keyword']) {
            unset($msg_content['emphasis_keyword']);
        }
//        write_log("msg_content" . var_export($msg_content, 1));
        return send_program_tpl_msg_execute(json_encode($msg_content),$platform_config);
    }
    public static function sendWechatMessageSingle($type, $data,$device_id='')
    {

        $ci =& get_instance();
        $ci->load->helper("wechat_send");
        $platform_config = get_platform_config_by_device_id($device_id);
        $tpl_id = $platform_config['wechat_'.$type];

        if (iconv_strlen($data['first']) > 256) {
            $data['first'] = iconv_substr($data['first'], 0, 253) . "...";
        }
        if ($data['keyword4'] && iconv_strlen($data['keyword4']) > 256) {
            $data['keyword4'] = iconv_substr($data['keyword4'], 0, 253) . "...";
        }

        $msg_content = array(
            'touser' => $data['buyer_id'],
            'template_id' => $tpl_id,
            'url' => $data['url'],
            'data' => array(
                'first' => array(
                    'color' => '#0089cd',
                    'value' => $data['first']
                ),
                'keyword1' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword1']
                ),
                'keyword2' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword2']
                ),
                'keyword3' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword3']
                ),
                'keyword4' => array(
                    'color' => '#0089cd',
                    'value' => $data['keyword4']
                ),
                'remark' => array(
                    'color' => '#000000',
                    'value' => $data['remark']
                ),
            )
        );
        if (!$data['url']) {
            unset($msg_content['data']['url']);
        }
        if (!$data['keyword1']) {
            unset($msg_content['data']['keyword1']);
        }
        if (!$data['keyword2']) {
            unset($msg_content['data']['keyword2']);
        }
        if (!$data['keyword3']) {
            unset($msg_content['data']['keyword3']);
        }
        if (!$data['keyword4']) {
            unset($msg_content['data']['keyword4']);
        }
        if (!$data['remark']) {
            unset($msg_content['data']['remark']);
        }
//        write_log("msg_content" . var_export($msg_content, 1));
        return send_wechat_tpl_msg_execute(json_encode($msg_content),$platform_config);
    }

    /**
     * 发送支付成功消息
     */
    public static function send_pay_succ_msg($data, $type = 'alipay',$device_id, $order_info=array(), $pay_info=array())
    {
        if ($type == 'alipay') {
            self::sendAlipayOpenPublicMessageSingle('pay_succ_tpl_id', $data,$device_id);
        } else if ($type == 'fruitday') {

        } else if ($type == 'wechat') {
            self::sendWechatMessageSingle('pay_succ_tpl_id',$data,$device_id);
        } else if($type == 'gat'){
            self::send_gat_pay_succ_msg($data, $order_info, $pay_info);
        } else if($type == 'program'){
            self::sendProgramMessageSingle('pay_succ_tpl_id',$data,$device_id);
        }
    }

    /**
     * @param $buyer_id
     * @param $remark
     * @param $url
     * @param $action
     * @param $first
     * @param $keyword1
     * @param string $keyword2
     * @param string $keyword3
     * @param string $keyword4
     * 发送支付失败消息
     */
    public static function send_pay_fail_msg($data, $type = 'alipay',$device_id)
    {
        if ($type == 'alipay') {
            self::sendAlipayOpenPublicMessageSingle('pay_fail_tpl_id', $data,$device_id);
        } else if ($type == 'fruitday') {

        } else if ($type == 'wechat') {
            self::sendWechatMessageSingle('pay_fail_tpl_id', $data,$device_id);
        }

    }

    /**
     * @param $buyer_id
     * @param $remark
     * @param $url
     * @param $action
     * @param $first
     * @param $keyword1
     * @param string $keyword2
     * @param string $keyword3
     * @param string $keyword4
     * 发送退款消息
     */
    public static function send_refund_msg($data, $type = 'alipay',$device_id)
    {
        if ($type == 'alipay') {
            self::sendAlipayOpenPublicMessageSingle('refund_tpl_id', $data,$device_id);
        } else if ($type == 'fruitday') {

        } else if ($type == 'wechat') {
            self::sendWechatMessageSingle('refund_tpl_id', $data,$device_id);
        }
    }

    /**
     * @param $buyer_id
     * @param $remark
     * @param $url
     * @param $action
     * @param $first
     * @param $keyword1
     * @param string $keyword2
     * @param string $keyword3
     * @param string $keyword4
     * 发送通用消息
     */
    public static function send_notify_msg($data, $type = 'alipay',$device_id)
    {
        write_log(' send_notify_msg : '.var_export($data,1),'debug');
        if ($type == 'alipay') {
            self::sendAlipayOpenPublicMessageSingle('notify_tpl_id', $data,$device_id);
        } else if ($type == 'fruitday') {

        } else if ($type == 'wechat') {
            self::sendWechatMessageSingle('notify_tpl_id', $data,$device_id);
        } else if ($type == 'gat') {
            //todo 发送关爱通通知,具体参考guanaitong_send_helper.php send_gat_notify_request
            $ci =& get_instance();
            $ci->load->helper("guanaitong_send");
            send_gat_notify_request($data);
        }

    }

    public static function send_warn_exception_msg($data, $type = 'alipay',$device_id)
    {
        if ($type == 'alipay') {
            self::sendAlipayOpenPublicMessageSingle('exception_tpl_id', $data,$device_id);
        } else if ($type == 'wechat') {
            return self::sendWechatMessageSingle('exception_tpl_id', $data,$device_id);
        }
    }

    //发送关爱通支付消息
    public static function send_gat_pay_succ_msg($data, $order_info, $pay_info){
        $tmp = '';
        foreach($pay_info['goods'] as $k=>$v){
            $product_name = str_replace('+', '加', $v['goodsName']);
            $product_name = str_replace('%', '', $product_name);
            $tmp = $product_name .'*'.$v['quantity'].' ,';
        }
        $tmp = trim($tmp ,',');
        $data['attach']['price']      = $order_info['money'];
        $data['attach']['o_price']    = $order_info['good_money'];
        $data['attach']['order_no']   = $order_info['order_name'];
        $data['attach']['order_url']  = $data['url'];
        $data['attach']['product_info'] = $tmp;
        $data['keyword1'] = date('Y-m-d H:i:s');
        $data['keyword2'] = '订单支付成功';
        $ci =& get_instance();
        $ci->load->helper("guanaitong_send");
        send_gat_notify_request($data);
    }

    public static function send_gat_fail_msg($data, $order_info){
        $tmp = '';
        foreach($order_info['goods'] as $k=>$v){
            $product_name = str_replace('+', '加', $v['product_name']);
            $product_name = str_replace('%', '', $product_name);
            $tmp = $product_name .'*'.$v['qty'].' ,';
        }
        $tmp = trim($tmp ,',');
        $data['attach']['price']      = $order_info['money'];
        $data['attach']['o_price']    = $order_info['good_money'];
        $data['attach']['order_no']   = $order_info['order_name'];
        $data['attach']['order_url']  = $data['url'];
        $data['attach']['product_info'] = $tmp;
        $data['keyword1'] = date('Y-m-d H:i:s');
        $data['keyword2'] = '订单支付失败';
        $ci =& get_instance();
        $ci->load->helper("guanaitong_send");
        send_gat_notify_request($data);

    }

}




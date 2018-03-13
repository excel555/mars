<?php
/**
 * Created by PhpStorm.
 * User: xingyulin
 * Date: 3/21/17
 * Time: 12:56
 */

function null_para($para)
{
    if (empty($para) || $para === "") {
        return TRUE;
    }
    return FALSE;
}

function is_mobile($mobile)
{
    if (preg_match("/^1[34578]{1}\d{9}$/", $mobile)) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * @param $str 原字符串
 * @param $needle 查找字符串
 * @return bool
 */
function check_str_exist($str, $needle)
{
    if (empty($str) || strlen($str) <= 0 || empty($needle) || strlen($needle) <= 0) {
        return FALSE;
    }
    $tmp_array = explode($needle, $str);
    if (count($tmp_array) > 1) {
        return TRUE;
    } else {
        return FALSE;
    }
}

/**
 * 用户信息保存在cache，
 * @return Array
 */
function get_cache_user($uid){
    $ci = &get_instance();
    $session_id = session_id_2_user($uid);
    $user_cache_key = 'user_'.$session_id;
    $user = $ci->cache->get($user_cache_key);
    return $user;
}
/**
 * session id 跟用户关联
 */
function session_id_2_user($uid){
    $ci = &get_instance();
    $key_user = 'user_id_'.$uid;
    $cache_session_id = $ci->cache->get($key_user);
    if($cache_session_id){
        $session_id = $cache_session_id;
    }else{
        $session_id = create_uuid();
    }
    $ci->cache->save($key_user,$session_id,604800);//记录用户session_id 保存7天
    return $session_id;
}
function update_user_cache($uid,$data){
    $ci = &get_instance();
    $session_id = session_id_2_user($uid);
    $user_cache_key = 'user_'.$session_id;
    $user = $ci->cache->get($user_cache_key);
    foreach ($data as $k=>$v){
        if(is_array($v)){
            foreach ($v as $vk=>$vv){
                $user[$k][$vk] = $vv;
            }
        }else{
            $user[$k] = $v;
        }

    }
    $ci->cache->save($user_cache_key,$user,604800);//记录用户保存7天
}
function random_code($uid){
    $code_key = array(
        '1'=>array('A','Q'),
        '2'=>array('S','W'),
        '3'=>array('D','E'),
        '4'=>array('F','R'),
        '5'=>array('G','T'),
        '6'=>array('H','Y'),
        '7'=>array('J','U'),
        '8'=>array('K','I'),
        '9'=>array('L','X'),
        '0'=>array('Z','C'),
    );
    $uid = ''.$uid;
    if(strlen($uid)<4){
        for ($i = 0;$i<=4-strlen($uid);$i++){
            $uid ='0'.$uid;
        }
    }
    $code = "";
    for($i = 0;$i<strlen($uid);$i++){
        $code .= $code_key[$uid[$i]][rand(0,1)];
    }
    return $code;
}

function general_qr_code($qr_str)
{
    $ci = get_instance();
    $ci->load->library('Qrcode');
    $qrcode = new Qrcode();
    $qrcode->set_data($qr_str);
    $img = $qrcode->build();

    $logo = 'logo.png';

    $QR = FCPATH . 'uploads/' . $img;
    $QR = imagecreatefromstring(file_get_contents($QR));
    $logo = imagecreatefromstring(file_get_contents($logo));
    $QR_width = imagesx($QR);
    $QR_height = imagesy($QR);
    $logo_width = imagesx($logo);
    $logo_height = imagesy($logo);
    $logo_qr_width = $QR_width / 4;
    $scale = $logo_width / $logo_qr_width;
    $logo_qr_height = $logo_height / $scale;
    $from_width = ($QR_width - $logo_qr_width) / 2;
    imagecopyresampled($QR, $logo, $from_width, $from_width, 0, 0, $logo_qr_width, $logo_qr_height, $logo_width, $logo_height);

    imagepng($QR, FCPATH . 'uploads/' . $img);

    return $img;
}

function general_short_url($long_url)
{
    $ci =& get_instance();
    $ci->load->helper('http_request');
    return HttpRequest::curl('http://980.so/api.php?url=' . urlencode($long_url));
}

function xml2array($xml){
    return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

}

function uuid($prefix = '')
{
    $chars = md5(uniqid(mt_rand(), true));
    $uuid  = substr($chars,0,8) . '-';
    $uuid .= substr($chars,8,4) . '-';
    $uuid .= substr($chars,12,4) . '-';
    $uuid .= substr($chars,16,4) . '-';
    $uuid .= substr($chars,20,12);
    return $prefix . $uuid;
}
function uuid_32()
{
    $time = explode ( " ", microtime () );
    $time = $time [1] . ($time [0] * 100000000);
    return $time;
}

function create_uuid($prefix = ""){
    $str = md5(uniqid(mt_rand(), true));
    $uuid  = substr($str,0,8) . '-';
    $uuid .= substr($str,8,4) . '-';
    $uuid .= substr($str,12,4) . '-';
    $uuid .= substr($str,16,4) . '-';
    $uuid .= substr($str,20,12);
    return $prefix . $uuid;
}

function sina_short_url($url){
    $ci =& get_instance();
    $ci->load->helper('http_request');
    $url = 'https://api.weibo.com/2/short_url/shorten.json?source=' . SINA_APPKEY . '&url_long=' . urlencode($url);
    $rs = HttpRequest::curl_get($url);
    return json_decode($rs,1);
}

/**
 * 通用日志函数
 * $content string 日志内容
 * $module string 日志业务模块
 * $slice string 日志分割类型（按天分：day，按月分：month）
 * $type string 日志类型(运行日志：run、错误日志：error、输出日志：output)
 */
function mutil_write_log( $content, $module, $slice = 'day', $type = 'run' )
{
    //NOLOG指令
    if( defined('NOLOG') && NOLOG === TRUE ){
        return;
    }
    
    if( $type == 'output' ){
        $boot = "/mnt/logs/";
    }else{
        $boot = "/mnt/logs/";
    }

    // boot目录不存在，非生产环境
    if( !file_exists($boot) ){
        return false;
    }

    if( $slice == 'month' ){
        $dir = $boot . $module . "/";
    }else{
        $dir = $boot . $module . "/" . date("Y_m") . "/";
    }

    if( !file_exists($dir) ){
        mkdir( $dir, 0700, true );
    }


    if( $slice == 'month' ){
        $file = $dir . $type . "_" . date("Ym") . ".log";
    }else{
        $file = $dir . $type . "_" . date("Ymd") . ".log";
    }

    error_log( $content, 3, $file );
}


class BarCode128 {
    const STARTA = 103;
    const STARTB = 104;
    const STARTC = 105;
    const STOP = 106;
    private $unit_width = 1; //单位宽度 缺省1个象素
    private $is_set_height = false;
    private $width = -1;
    private $heith = 35;
    private $quiet_zone = 6;
    private $font_height = 15;
    private $font_type = 4;
    private $color =0x000000;
    private $bgcolor =0xFFFFFF;
    private $image = null;
    private $codes = array("212222","222122","222221","121223","121322","131222","122213","122312","132212","221213","221312","231212","112232","122132","122231","113222","123122","123221","223211","221132","221231","213212","223112","312131","311222","321122","321221","312212","322112","322211","212123","212321","232121","111323","131123","131321","112313","132113","132311","211313","231113","231311","112133","112331","132131","113123","113321","133121","313121","211331","231131","213113","213311","213131","311123","311321","331121","312113","312311","332111","314111","221411","431111","111224","111422","121124","121421","141122","141221","112214","112412","122114","122411","142112","142211","241211","221114","413111","241112","134111","111242","121142","121241","114212","124112","124211","411212","421112","421211","212141","214121","412121","111143","111341","131141","114113","114311","411113","411311","113141","114131","311141","411131","211412","211214","211412","2331112");
    private $valid_code = -1;
    private $type ='B';
    private $start_codes =array('A'=>self::STARTA,'B'=>self::STARTB,'C'=>self::STARTC);
    private $code ='';
    private $bin_code ='';
    private $text ='';
    public function __construct($code='',$text='',$type='B')
    {
        if (in_array($type,array('A','B','C')))
            $this->setType($type);
        else
            $this->setType('B');
        if ($code !=='')
            $this->setCode($code);
        if ($text !=='')
            $this->setText($text);
    }
    public function setUnitWidth($unit_width)
    {
        $this->unit_width = $unit_width;
        $this->quiet_zone = $this->unit_width*6;
        $this->font_height = $this->unit_width*15;
        if (!$this->is_set_height)
        {
            $this->heith = $this->unit_width*35;
        }
    }
    public function setFontType($font_type)
    {
        $this->font_type = $font_type;
    }
    public function setBgcolor($bgcoloe)
    {
        $this->bgcolor = $bgcoloe;
    }
    public function setColor($color)
    {
        $this->color = $color;
    }
    public function setCode($code)
    {
        if ($code !='')
        {
            $this->code= $code;
            if ($this->text ==='')
                $this->text = $code;
        }
    }
    public function setText($text)
    {
        $this->text = $text;
    }
    public function setType($type)
    {
        $this->type = $type;
    }
    public function setHeight($height)
    {
        $this->height = $height;
        $this->is_set_height = true;
    }
    private function getValueFromChar($ch)
    {
        $val = ord($ch);
        try
        {
            if ($this->type =='A')
            {
                if ($val > 95)
                    throw new Exception(' illegal barcode character '.$ch.' for code128A in '.__FILE__.' on line '.__LINE__);
                if ($val < 32)
                    $val += 64;
                else
                    $val -=32;
            }
            elseif ($this->type =='B')
            {
                if ($val < 32 || $val > 127)
                    throw new Exception(' illegal barcode character '.$ch.' for code128B in '.__FILE__.' on line '.__LINE__);
                else
                    $val -=32;
            }
            else
            {
                if (!is_numeric($ch) || (int)$ch < 0 || (int)($ch) > 99)
                    throw new Exception(' illegal barcode character '.$ch.' for code128C in '.__FILE__.' on line '.__LINE__);
                else
                {
                    if (strlen($ch) ==1)
                        $ch .='0';
                    $val = (int)($ch);
                }
            }
        }
        catch(Exception $ex)
        {
            errorlog('die',$ex->getMessage());
        }
        return $val;
    }
    private function parseCode()
    {
        $this->type=='C'?$step=2:$step=1;
        $val_sum = $this->start_codes[$this->type];
        $this->width = 35;
        $this->bin_code = $this->codes[$val_sum];
        for($i =0;$i<strlen($this->code);$i+=$step)
        {
            $this->width +=11;
            $ch = substr($this->code,$i,$step);
            $val = $this->getValueFromChar($ch);
            $val_sum += $val;
            $this->bin_code .= $this->codes[$val];
        }
        $this->width *=$this->unit_width;
        $val_sum = $val_sum%103;
        $this->valid_code = $val_sum;
        $this->bin_code .= $this->codes[$this->valid_code];
        $this->bin_code .= $this->codes[self::STOP];
    }
    public function getValidCode()
    {
        if ($this->valid_code == -1)
            $this->parseCode();
        return $this->valid_code;
    }
    public function getWidth()
    {
        if ($this->width ==-1)
            $this->parseCode();
        return $this->width;
    }
    public function getHeight()
    {
        if ($this->width ==-1)
            $this->parseCode();
        return $this->height;
    }
    public function createBarCode($image_type ='png',$file_name=null)
    {
        $this->parseCode();
        $this->image = ImageCreate($this->width+2*$this->quiet_zone,$this->heith + $this->font_height);
        $this->bgcolor = imagecolorallocate($this->image,$this->bgcolor >> 16,($this->bgcolor >> 8)&0x00FF,$this->bgcolor & 0xFF);
        $this->color = imagecolorallocate($this->image,$this->color >> 16,($this->color >> 8)&0x00FF,$this->color & 0xFF);
        ImageFilledRectangle($this->image, 0, 0, $this->width + 2*$this->quiet_zone,$this->heith + $this->font_height, $this->bgcolor);
        $sx = $this->quiet_zone;
        $sy = $this->font_height -1;
        $fw = 10; //編號為2或3的字體的寬度為10，為4或5的字體寬度為11
        if ($this->font_type >3)
        {
            $sy++;
            $fw=11;
        }
        $ex = 0;
        $ey = $this->heith + $this->font_height - 2;
        for($i=0;$i<strlen($this->bin_code);$i++)
        {
            $ex = $sx + $this->unit_width*(int) $this->bin_code{$i} -1;
            if ($i%2==0)
                ImageFilledRectangle($this->image, $sx, $sy, $ex,$ey, $this->color);
            $sx =$ex + 1;
        }
        $t_num = strlen($this->text);
        $t_x = $this->width/$t_num;
        $t_sx = ($t_x -$fw)/2;        //目的为了使文字居中平均分布
        for($i=0;$i<$t_num;$i++)
        {
            imagechar($this->image,$this->font_type,6*$this->unit_width +$t_sx +$i*$t_x,0,$this->text{$i},$this->color);
        }
        if (!$file_name)
        {
            header("Content-Type: image/".$image_type);
        }
        switch ($image_type)
        {
            case 'jpg':
            case 'jpeg':
                Imagejpeg($this->image,$file_name);
                break;
            case 'png':
                Imagepng($this->image,$file_name);
                break;
            case 'gif':
                break;
                Imagegif($this->image,$file_name);
            default:
                Imagepng($this->image,$file_name);
                break;
        }
    }
}
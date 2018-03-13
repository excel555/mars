<?php
//set config
$config['IMAGE_URL'] = 'http://fdaycdn.fruitday.com/';
$config['IMAGE_URL_TMP'] = 'http://apicdn.fruitday.com/img/';
$config['WEB_BASE_PATH'] = '';//dirname(dirname($_SERVER['DOCUMENT_ROOT']))."/www.fruitday.com/public_html/";
$config['TMPL_ACTION_SUCCESS'] = 'include/dispatch_jump.php';
$config['TMPL_ACTION_ERROR'] = 'include/dispatch_jump.php';

$config['APP_BANNER_FARM'] = 'images/app/';

if(!defined('CITY_BOX_API')){
    define('CITY_BOX_API', 'http://stagingcityboxapi.fruitday.com');
}
//set define
if(!defined('SMS_SECRET')){
    define('SMS_SECRET','3410w312ecf4a3j814y50b6abff6f6b97e16');
}
if(!defined('SMS_CHANNEL')){
    define('SMS_CHANNEL','local');
}
if(!defined('SMS_API_URL')){
    define('SMS_API_URL','http://3tong.net/http/sms/Submit');
}
if(!defined('SMS_ACCOUNT')){
    define('SMS_ACCOUNT','dh1689');
}
if(!defined('SMS_PASSWD')){
    define('SMS_PASSWD','i_love#fruitday!123');
}
if (!defined('TTGY_API')) {
    define('TTGY_API','http://nirvana.fruitday.com/api');
}
if(!defined('API_SECRET')){
  define('API_SECRET',"7600w212ec04a3j814d50b6a5ff6f6b67e16");
}

if(!defined('CITY_API_SECRET')){
    define('CITY_API_SECRET',"48eU7IeTJ6zKKDd1");
}

if(!defined('POOL_PRODUCT_URL')){
  define('POOL_PRODUCT_URL','http://fruitday-soa.internal.fruitday.com/official/productsync');
}

if(!defined('OMSTESTPOOL_PRODUCT_URL')){
  define('OMSTESTPOOL_PRODUCT_URL','122.144.167.61:38080/official/productsync');
}


if(!defined('AES_KEY')){
  define('AES_KEY', 'rQYonmVRH/i8WA6A4eklbwSt7MWX/GbPaE2Amxr6o00=');
}

if(!defined('HASH256_KEY')){
  define('HASH256_KEY', '6E1C42-FFB4-4C34-8B7C-ED6F6629');
}

if(!defined('OMSTESTAES_KEY')){
  define('OMSTESTAES_KEY', 'Oms8SJ5NPVJGi9F46W+T9aW5SUg46I0JAtQM8Dhu63k=');
}

if(!defined('OMSTESTHASH256_KEY')){
  define('OMSTESTHASH256_KEY', 'FE460BA8ED88910111213fruit345jsdklfJKSD');
}

if(!defined('OPEN_S3')){
  define('OPEN_S3',false);
}

if(!defined('SECRET_V2')){
    define('SECRET_V2', 'd50b6a5ff6ff4a3j814y6f6b97ec62ab');
}


$config['order_operation'] = array(
    0=>'待审核',
    1=>'已审核',
    2=>'已发货',
    3=>'已完成',
//    4=>'捡货中',
//    5=>'已取消',
//    6=>'等待完成',
//    7=>'退货中',
//    8=>'换货中',
//    9=>'已收货'
);
$config['news_type'] = array(
           1=>'果园公告',
           2=>'购物指南',
           3=>'支付配送',
           4=>'售后服务',
           5=>'关于我们',
           6=>'企业服务',
           7=>'券卡使用',
           8=>'鲜果会',
);


$config['mobile_card_type'] = array(
      1=>"手机领取",
      2=>"晒单有礼",
      3=>"微信每日领取",
      4=>"1020抽奖活动",
      5=>"微信帐号领取",
      6=>"二维码抽奖",
      7=>"周年庆抽奖",
      8=>"app首次登陆活动",
      9=>"手机摇一摇",
      10=>"会员等级领取",
      11 => "支付优惠领取",
	12 => "新客礼包",
);

 $config['pay_array']  =  array(
     1=>array('name'=>'支付宝付款','son'=>array()),
   // 2=>array('name'=>'联华OK会员卡在线支付','son'=>array()),
     3=>array('name'=>'网上银行支付','son'=>array(
           "00102"=>"浦发银行信用卡",
           "00021"=>"招商银行",
           "00004"=>"中国工商银行",
           "00003"=>"中国建设银行",
           "00105"=>"广发信用卡(银联)",
           "00017"=>"中国农业银行",
           "00083"=>"中国银行",
           "00005"=>"交通银行",
           "00084"=>"上海银行",
           "00052"=>"广东发展银行",
           "00051"=>"邮政储蓄",
           "00023"=>"深圳发展银行",
           "00054"=>"中信银行",
           "00087"=>"平安银行",
           "00096"=>"东亚银行",
           "00057"=>"光大银行",
           "00041"=>"华夏银行",
           "00013"=>"民生银行",
           "00055"=>"南京银行",
           "00016"=>"兴业银行",
           "00081"=>"杭州银行",
           "00086"=>"浙商银行",
           "00032"=>"浦东发展银行",
           "00101"=>"交通银行",
           "00106"=>"花旗银行信用卡(银联)",
      // "00100"=>"民生银行(家园卡)",
           //"00030"=>"上海农村商业银行(如意借记卡（上海地区）)"
     )),
     4=>array('name'=>'线下支付','son'=>array(1=>'货到付现金',2=>'货到刷银行卡',7=>'红色储值卡支付',8=>'金色储值卡支付',9=>'果实卡支付',11=>'通用券/代金券支付')),
     //key=10的提货券支付在购买流程会作为判断条件不赠送满赠赠品，不要修改key=10，10=>'提货券支付'
     5=>array('name'=>'账户余额支付','son'=>array()),
     6=>array('name'=>'券卡支付','son'=>array(1=>'在线提货券支付')),
     7=>array('name'=>'微信开放平台支付','son'=>array()),
     // 8=>array('name'=>'银联支付','son'=>array()),
     9=>array('name'=>'微信公众号支付','son'=>array()),
     10=>array('name'=>'东方支付','son'=>array()),
   );

$config['order_region'] = array(
    1=>'上海',//上海订单
    2=>'江苏',//江浙皖订单
    3=>'浙江',
    4=>'安徽',
    5=>'北京',
    6=>'天津',
    7=>'河北',
 //   8=>'河南',
 //   9=>'山西',
  //  10=>'山东',
    11=>'陕西',
  //  12=>'吉林',
  //  13=>'黑龙江',
  //  14=>'辽宁',
    15=>'广东',
   // 16=>'海南',
  //  17=>'广西',
  //  18=>'福建',
  //  19=>'湖南',
    20=>'四川',
    21=>'重庆',
  //  22=>'云南',
  //  23=>'贵州',
    24=>'甘肃',
    25=>'湖北',
 //   26=>'江西',
    27=>'上海郊区',
  //  28=>'青海'
);

$config['area_refelect'] = array(
    1=>array(106092),//上海
    2=>array(1),//江苏
    3=>array(54351),//浙江
    4=>array(106340),//安徽
    5=>array(143949),//北京
    6=>array(144005),//天津
    7=>array(143983),//河北
    8=>array(143967),//河南
    9=>array(143996),//山西
    10=>array(144035),//山东
    11=>array(144039),//陕西
    12=>array(144045),//吉林
    13=>array(144051),//黑龙江
    14=>array(144224),//辽宁
    15=>array(144252),//广东
    16=>array(144370),//海南
    17=>array(144379),//广西
    18=>array(144387),//福建
    19=>array(144412),//湖南
    20=>array(144443),//四川
    21=>array(144522),//重庆
    22=>array(144551),//云南
    23=>array(144595),//贵州
    24=>array(144627),//甘肃
    25=>array(144643),//湖北
    26=>array(144795),//江西
    27=>array(145855),//上海郊区
    28=>array(145843),//青海
);


$config['product_option'] = array(
    'place'=>array(
        'name'=>'产地',
        'val'=>array('1'=>'进口','2'=>'国产'),
    ),
    'brand'=>array(
        'name'=>'品牌',
        'val'=>array('1'=>'经典佳沛','2'=>'新奇士','3'=>'都乐'),
    ),
    'occasion'=>array(
        'name'=>'场合',
        'val'=>array('1'=>'生日快乐','2'=>'早日康复','3'=>'走亲访友','4'=>'宴席聚会'),
    ),
    'size'=>array(
        'name'=>'规格',
        'val'=>array('1'=>'单品','2'=>'套餐','3'=>'礼盒','4'=>'礼篮','5'=>'券卡'),
    ),
    'detail_place' => array(
        'name' => '详细产地',
        'val' => array(
//            '1' => '新疆', '2' => '海南', '3' => '台湾', '4' => '陕西', '5' => '浙江',
//            '6' => '云南', '7' => '桂林', '8' => '重庆', '9' => '河北', '10' => '四川',
//            '11' => '江西', '12' => '甘肃', '13' => '雅安', '14' => '崇明', '15' => '贵州',
//            '16' => '广西',
            '50' => '中国',
            '51' => '美国', '52' => '智利', '53' => '泰国', '54' => '西班牙', '55' => '新西兰',
            '56' => '意大利', '57' => '埃及', '58' => '墨西哥', '59' => '越南', '60' => '菲律宾',
            '61' => '马来西亚', '62' => '秘鲁', '63' => '澳大利亚', '64' => '挪威', '65' => '日本',
            '66' => '荷兰', '67' => '南非', '68' => '阿根廷', '69' => '朝鲜', '70' => '法国',
            '71' => '韩国', '72' => '加拿大', '73' => '土耳其', '74' => '乌拉圭', '75' => '希腊',
            '76' => '以色列', '77' => '英国', '78' => '厄瓜多尔', '79' => '巴西',
            '999' => '其它',
        )
    ),
    'price'=>array(
        'name'=>'价格',
        'val'=>array('0T100'=>'100以下','100T300'=>'100~300','300T'=>'300以上'),
    ),
    'store' => array(
        'name' => '储藏方法',
        'val' => array('1' => '0°冷藏', '2' => '常温', '3' => '-18°~0°冷冻'),
    ),
);

$config['channel_array'] = array(
    'portal'=>'官网 channel',
    'xiaomi'=>'小米 channel',
    'baidu'=>'百度 channel',
    'm91'=>'91 channel',
    'hiapk'=>'安卓 channel',
    //'msjy' => '民生家园联合登陆',
    'm360'=>'360 channel',
    'qq' => '应用宝 channel',
    'huawei' => '华为 channel',
    'wandoujia' => '豌豆荚 channel',
    'anzhi' => '安智 channel',
    'lenovo' => '联想 channel',
    'AppStore' => 'IOS channel',
    'pp' => 'PP助手 channel',
    'andriod' => '非ios channel',
    'union'=>'张江工会 channel'
);

// 会员等级
$config['user_rank'] = array(
'cycle' => 12,
'level' => array(
  6 => array(
    'name' => '鲜果达人V5',
    'level_id' => 6,
    'ordernum' => 36,
    'ordermoney' => 6000,
    'icon' => 'small_userrankV5.jpg',
    'bigicon' => 'big_userrankV5.jpg',
    'condition_desc' => '1年中，已完成订单数达到36并且订单总额满足6000元' ,
    'pmt_desc' => '1、送3倍积分',
    'pmt' => array(
      'score' => '3x',
     ),
    'juice'=>array(
      'day_money'=>5,
      'day_num'=>2,
      'week_money'=>0,
      'week_num'=>1
    ),
    'shake_num'=>5
  ),
  5 => array(
    'name' => '鲜果达人V4',
    'level_id' => 5,
    'ordernum' => 26,
    'ordermoney' => 3000,
    'icon' => 'small_userrankV4.jpg',
    'bigicon' => 'big_userrankV4.jpg',
    'condition_desc' => '1年中，已完成订单数达到26并且订单总额满足3000元' ,
    'pmt_desc' => '1、送2.5倍积分',
    'pmt' => array(
      'score' => '2.5x',
     ),
    'juice'=>array(
      'day_money'=>5,
      'day_num'=>2,
      'week_money'=>0,
      'week_num'=>1
    ),
    'shake_num'=>5
  ),
  4 => array(
    'name' => '鲜果达人V3',
    'level_id' => 4,
    'ordernum' => 16,
    'ordermoney' => 1500,
    'icon' => 'small_userrankV3.jpg',
    'bigicon' => 'big_userrankV3.jpg',
    'condition_desc' => '1年中，已完成订单数达到16并且订单总额满足1500元' ,
    'pmt_desc' => '1、送2倍积分',
    'pmt' => array(
      'score' => '2x',
    ),
    'juice'=>array(
      'day_money'=>5,
      'day_num'=>2,
      'week_money'=>0,
      'week_num'=>0
    ),
    'shake_num'=>4
  ),
  3 => array(
    'name' => '鲜果达人V2',
    'level_id' => 3,
    'ordernum' => 5,
    'ordermoney' => 500,
    'icon' => 'small_userrankV2.jpg',
    'bigicon' => 'big_userrankV2.jpg',
    'condition_desc' => '1年中，已完成订单数达到5并且订单总额满足500元' ,
    'pmt_desc' => '1、送1.5倍积分',
    'pmt' => array(
      'score' => '1.5x',
    ),
    'juice'=>array(
      'day_money'=>5,
      'day_num'=>1,
      'week_money'=>0,
      'week_num'=>0
    ),
    'shake_num'=>4
  ),
  2 => array(
    'name' => '鲜果达人V1',
    'level_id' => 2,
    'ordernum' => 2,
    'ordermoney' => 200,
    'icon' => 'small_userrankV1.jpg',
    'bigicon' => 'big_userrankV1.jpg',
    'condition_desc' => '1年中，已完成订单数达到2并且订单总额满足200元' ,
    'pmt_desc' => '1、送1倍积分',
    'pmt' => array(
        'score' => '1x',
      ),
    'juice'=>array(
      'day_money'=>10,
      'day_num'=>1,
      'week_money'=>0,
      'week_num'=>0
    ),
    'shake_num'=>3
  ),
  1 => array(
    'name' => '普通会员',
    'level_id' => 1,
    'ordernum' => 0,
    'ordermoney' => 0,
    'icon' => 'small_userrankV0.jpg',
    'bigicon' => 'big_userrankV0.jpg',
    'condition_desc' => '注册' ,
    'pmt_desc' => '1、送1倍积分',
    'pmt' => array(
        'score' => '1x',
      ),
    'juice'=>array( //果汁
      'day_money'=>0,//每天购买的果汁金额
      'day_num'=>0,//每天购买的果汁数量
      'week_money'=>0,//每星期购买的果汁金额
      'week_num'=>0//每星期噶偶买的果汁数量
    ),
    'shake_num'=>3//每天摇一摇次数
  ),
),

);

$config['pay_array'] = array(
    1 => array('name' => '支付宝', 'son' => array()),
//    2 => array('name' => '联华OK会员卡在线支付', 'son' => array()),
    3 => array('name' => '网上银行支付', 'son' => array(
//            "00021" => "招商银行(银行卡支付（全国范围）)",
//            "00004" => "中国工商银行(网上签约注册用户（全国范围）)",
            "00102" => "浦发银行信用卡",
            "00103" => "交通银行信用卡",
            "00105" => "广发信用卡(银联)",
            "00003" => "中国建设银行",
            "00106"=>"花旗银行信用卡(银联)",
//            "00017" => "中国农业银行(网上银行签约客户（全国范围）)",
//            "00083" => "中国银行(银行卡支付（全国范围）)",
//            "00005" => "交通银行(太平洋卡（全国范围）)",
//            "00032" => "浦东发展银行(东方卡（全国范围）)",
//            "00084" => "上海银行(银行卡支付（全国范围）)",
//            "00052" => "广东发展银行(银行卡支付（全国范围）)",
//            "00051" => "邮政储蓄(银联网上支付签约客户（全国范围）)",
//            "00023" => "深圳发展银行(发展卡支付（全国范围）)",
//            "00054" => "中信银行(银行卡支付（全国范围）)",
//            "00087" => "平安银行(平安借记卡（全国范围）)",
//            "00096" => "东亚银行(银行卡支付（全国范围）)",
//            "00057" => "光大银行(银行卡支付（全国范围）)",
//            "00041" => "华夏银行(华夏借记卡（全国范围）)",
//            "00013" => "民生银行(民生卡（全国范围）)",
//            "00055" => "南京银行(银行卡支付（全国范围）)",
//            "00016" => "兴业银行(在线兴业（全国范围）)",
//            "00081" => "杭州银行(银行卡支付（全国范围）)",
//            "00086" => "浙商银行(银行卡支付（全国范围）)",
//            "00030" => "上海农村商业银行(如意借记卡（上海地区）)",
//            "00100" => "民生银行家园卡",
        )),
    7 => array('name' => '微信支付', 'son' => array()),
    8 => array('name' => '银联在线支付', 'son' => array()),
    9 => array('name' => '微信支付', 'son' => array()),
    5 => array('name' => '账户余额支付', 'son' => array()),
    4 => array('name' => '线下支付', 'son' => array(
            1 => '货到付现金',
            2 => '货到刷银行卡',
//            3 => '货到刷联华OK卡',
            7 => '红色储值卡支付',
            8 => '金色储值卡支付',
            9 => '果实卡支付',
//            10 => '提货券支付', //key=10的提货券支付在购买流程会作为判断条件不赠送满赠赠品，不要修改key=10
            11 => '通用券/代金券支付'
        )),
    6 => array('name' => '券卡支付', 'son' => array(1 => '在线提货券支付')),
);

$config['banner_type'] = [
    'image' => '图片',
    'text' => '文字',
    'product' => '商品'
];

// （app专用）为了区分 app 和 wap
$config['banner_target_type'] = [
    '1' => '专题（app专用）', // 后台-广告详情，B2C促销商品列表
    '2' => '详情', // 链接-商品详情

    '4' => '抢购（app专用）', // 后台-广告详情
    '7' => '抢购列表（app专用）', // 后台-广告详情

    '5' => '促销活动说明',
    '6' => '链接', // 链接-当前链接，内部促销H5
    '8' => '内部外网链接',

    '10' => '官方动态（百科文章）',
    '23' => '发现首页',
    '11' => '发现话题',
    '15' => '用户动态',
    '16' => '社区-官方文章（app专用）', // 果食-社区官方文章

    '12' => '跨境通（app专用）', // 链接-跨境通

    '9' => '线下入口', // app-o2o模块
    '13' => 'O2O商品详情',
    '26' => 'O2O H5链接',

    '18' => 'app-试吃（app专用）', // app-试吃
    '21' => '充值有礼（app专用）',
    '22' => '提货券（app专用）',
    '24' => '全部分类',
    '27' => '一级分类',
    '25' => '天天有礼（app专用）',

    '30' => '发现 - 官方话题',
    '31' => '发现 - 官方动态',
    '32' => '发现 - 用户动态',
    '33' => '发现 - H5页面',

    '999' => '广告单页', // 不能点击
];

$config['business_list'] = array(
    '2'=>'天天果园',
    '3'=>'城市超市',
);
?>

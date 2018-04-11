<?php
//set config
$config['IMAGE_URL'] = '';
$config['IMAGE_URL_TMP'] = '';
$config['WEB_BASE_PATH'] = '';//dirname(dirname($_SERVER['DOCUMENT_ROOT']))."/www.fruitday.com/public_html/";
$config['TMPL_ACTION_SUCCESS'] = 'include/dispatch_jump.php';
$config['TMPL_ACTION_ERROR'] = 'include/dispatch_jump.php';

$config['APP_BANNER_FARM'] = 'images/app/';





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

?>

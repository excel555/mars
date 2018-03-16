
/**
 * 配置信息
 * 把配置信息分为基础配置、提示信息
 */
(function() {
    var config = {
        HOST_API: location.protocol + '//' + location.host + '/index.php/api',
        alipayAuthUrl: "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=APPID&scope=auth_user&redirect_uri=REDIRECT_URI", //授权跳转地址
        wechatAuthUrl: "https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&connect_redirect=1#wechat_redirect", //微信授权跳转地址
        alipayAppId: "2018031602387886", //2017052207307682 2017032806445211
        wechatAppId: "wx3450e6a290582989", //
        platformKey: "ce6u8wD8Wf7fWufRp",
        page:1,
        pageSize:10,
        ratioOfMainPic: 3 / 2, //商品图片的宽高比
        ratioOfAdPic: 2 / 1, //广告图片的宽高比
        smsDuration: 60 //发短信计时间隔，秒
    };
    config.DEF_IMG_URL = location.protocol + '//' + location.host + "/public/img/shop.jpg"
    //接口配置
    config.API_GLOBAL_AD = config.HOST_API + '/global/get';

    //auto login
    config.API_ALIPAY_ACCOUNT_AUTOLOGIN = config.HOST_API + '/account/auth_alipay_login';
    config.API_WECHAT_ACCOUNT_AUTOLOGIN = config.HOST_API + '/dalang/auth_wechat_login';
    config.API_GET_CONFIG = config.HOST_API + '/dalang/get_config_by_device_id';


    //bind
    config.API_SIGN = config.HOST_API + '/dalang/goto_sign';

    //orders
    config.API_ORDERS = config.HOST_API + '/dalang/list_order';
    config.API_ORDER_DETAIL = config.HOST_API + '/dalang/get_detail';
    config.API_ORDER_REFUND = config.HOST_API + '/dalang/refund';
    config.API_ORDER_PAY = config.HOST_API + '/dalang/pay_by_manual';

    config.API_OPEN_DOOR = config.HOST_API + '/device/open_door';
    config.API_FD_BOX_STATUS = config.HOST_API + '/dalang/box_status';
    config.API_DEVICE_ORDER_AMOUNT = config.HOST_API + '/dalang/order_ad';

    config.ORDER_STATUS = { //订单状态
        '-1': '已取消',
        '0': '未支付',
        '2': '支付中',
        '1': '已支付',
        '3': '退款申请中',
        '4': '退款成功',
        '5': '退款申请驳回'
    };

    //不需要开通免密的登录
    config.NO_MIANMI = new Array(
        "coupon.html",
        "clear.html",
        "warn_msg"
    )


    window.config = config;
})();;

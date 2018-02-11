
/**
 * 配置信息
 * 把配置信息分为基础配置、提示信息
 */
(function() {
    var config = {
        HOST_API: location.protocol + '//' + location.host + '/index.php/api',
        alipayAuthUrl: "https://openauth.alipay.com/oauth2/publicAppAuthorize.htm?app_id=APPID&scope=auth_user&redirect_uri=REDIRECT_URI", //授权跳转地址
        wechatAuthUrl: "https://open.weixin.qq.com/connect/oauth2/authorize?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&connect_redirect=1#wechat_redirect", //微信授权跳转地址
        alipayAppId: "2017052207307682", //2017052207307682 2017032806445211
        wechatAppId: "wx3450e6a290582989", //
        platformKey: "ce6u8wX8Wf7fWufRp",
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
    config.API_WECHAT_ACCOUNT_AUTOLOGIN = config.HOST_API + '/account/auth_wechat_login';
    config.API_GET_CONFIG = config.HOST_API + '/account/get_config_by_device_id';

    //member info
    config.API_MEMBER_INFO = config.HOST_API + '/account/get_info';
    config.API_MEMBER_ACCOUNT = config.HOST_API + '/account/amount_info';
    config.API_RECHARGE_MONEY = config.HOST_API + '/account/recharge_money';
    config.API_RECHARGE_CARD = config.HOST_API + '/account/recharge_card';
    config.API_RECHARGE_LIST = config.HOST_API + '/account/amount_detail';
    config.API_WARN_MSG = config.HOST_API + '/account/warn_user_info';
    config.API_CARD_LIST = config.HOST_API + '/account/coupon_list';

    //record admin login
    config.API_REC_ADMIN = config.HOST_API + '/account/rec_admin';

    //vcode
    config.API_SEND_VECODE = config.HOST_API + '/account/get_vcode';

    //register
    config.API_REGISTER = config.HOST_API + '/account/register';
    //bind
    config.API_BIND = config.HOST_API + '/account/bind';
    config.API_SIGN = config.HOST_API + '/account/goto_sign';

    //orders
    config.API_ORDERS = config.HOST_API + '/order/list_order';
    config.API_ORDER_DETAIL = config.HOST_API + '/order/get_detail';
    config.API_ORDER_REFUND = config.HOST_API + '/order/refund';
    config.API_ORDER_PAY = config.HOST_API + '/order/pay_by_manual';

    //deliver
    config.API_DELIVER = config.HOST_API + '/order/list_deliver';

    config.API_OPEN_DOOR = config.HOST_API + '/device/open_door';

    config.API_INDEX_AD = config.HOST_API + '/device/index_ad';//首页广告
    config.API_NEW_INDEX_AD = config.HOST_API + '/device/new_index';//首页接口
    config.API_CARD_FU_LI = config.HOST_API + '/device/index_card';//11点福利

    config.API_ZMXY_AUTH = config.HOST_API + '/account/zmxy_auth';
    config.API_FRUITDAY_AUTH_OPEN = config.HOST_API + '/fruitday/auth_fruitday_login';
    config.API_AUTH_OPEN = config.HOST_API + '/account/auth_platform_login';
    config.API_PLATFORM_ACCESS = config.HOST_API + '/account/log_platform_access';
    config.API_PLATFORM_LOGIN = config.HOST_API + '/account/platform_login';

    config.API_FD_AMOUNT = config.HOST_API + '/fruitday/get_info';
    config.API_FD_RECHARGE = config.HOST_API + '/fruitday/recharge';
    config.API_FD_OPEN_DOOR = config.HOST_API + '/fruitday/open_door';
    config.API_FD_BOX_STATUS = config.HOST_API + '/fruitday/box_status';
    config.API_FD_ORDER_AMOUNT = config.HOST_API + '/fruitday/get_order_and_amount';
    config.API_DEVICE_ORDER_AMOUNT = config.HOST_API + '/device/order_and_ad';
    
    config.API_BUY_ADDRESS = config.HOST_API + '/device/buy_box_address';
    config.API_DEVICE_INFO = config.HOST_API + '/device/box_info';


    //gat
    config.API_GAT_OPEN = config.HOST_API + '/gat/open_door';
    config.API_GAT_LOGIN = config.HOST_API + '/account/gat_only_login';

    //cmb
    config.API_CMB_OPEN = config.HOST_API + '/cmb/cmb_open';
    config.API_CMB_BALANCE = config.HOST_API + '/cmb/cmb_balance';

    //cart
    config.API_ADD_CART = config.HOST_API + '/cart/add_product_cart';
    config.API_DEL_CART = config.HOST_API + '/cart/del_car_goods';
    config.API_INIT_CART = config.HOST_API + '/cart/car_goods';
    config.API_PAY_CART = config.HOST_API + '/cart/car_goods_pay';
    config.API_WX_PAY_CART = config.HOST_API + '/cart/car_goods_pay_wx';
    config.API_SCAN_STATUS = config.HOST_API + '/cart/scan_status';

    //deliver
    config.API_INIT_DELIVER = config.HOST_API + '/deliver/init_goods';
    config.API_CHANGE_DELIVER_PRO = config.HOST_API + '/deliver/fix_product';
    config.API_DELIVER_SCAN = config.HOST_API + '/deliver/deliver_open';//配送员扫码开门
    config.API_CONFIRE_DELIVER = config.HOST_API + '/deliver/confirm_deliver';//确认配送
    config.API_OPEN_SCAN = config.HOST_API + '/deliver/open_scan';//

    config.API_STOCK_EQ = config.HOST_API + '/deliver/init_eq_goods';//
    config.API_CONFIRE_STOCK = config.HOST_API + '/deliver/confirm_stock';//确认库存

    //coupon
    config.API_COUPON = config.HOST_API + '/device/qr_card';//

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

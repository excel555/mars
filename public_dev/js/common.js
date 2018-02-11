/**
 * TODO 根据具体业务逻辑修改 test watch
 */
(function () {

    var common = {};

    //获取登录的id
    common.getId = function () {
        var auth = Cookie.get(Storage.AUTH);
        return auth;
    };

    //获取微信号
    common.getOpenId = function () {
        return Cookie.get(Storage.OPENID);
    };

    //自动登录
    common.login = function () {
        if(Cookie.get("alipayAppId")){
            config.alipayAppId = Cookie.get("alipayAppId");
        }
        if(Cookie.get("wechatAppId")){
            config.wechatAppId = Cookie.get("wechatAppId");
        }
        var scan_platform = Cookie.get("scan_platform");
        if(Tools.isGatApp()){//如果是关爱通，重新根据code发起登录
            var deviceId = Cookie.get("deviceId");
            var auth_code= Tools._GET().auth_code || 0;
            Tools.alert(auth_code);
            Cookie.set("auth_code", auth_code, null);//保存关爱通登录凭证
            Ajax.custom({
                url: config.API_GAT_LOGIN,
                data:{
                    platform:'gat',
                    auth_code:auth_code
                },
                async:false,
            }, function(response) {
                Cookie.set("token", response.token, null);
                if(response['user']){
                    Cookie.set("UserSN",response['user']['id'], null);
                    location.reload();
                }
                Tools.alert(response);

            },function (e) {
                Tools.showAlert(e);
            });
            return '';
        }

        if(scan_platform == "fruitday" && !Tools.isAlipayBrowser() && !Tools.isWeChatBrowser()){
            var deviceId = Cookie.get("deviceId");
            if(Tools.isFruitdayAppBrowser()){
                Tools.showAlert("请重新扫码");
                location.href = "help.html";//
            }else{
                location.href = "http://m.fruitday.com/boxLogin?deviceId="+deviceId+"&scan_platform=fruitday";
            }
            return;
        }

        if(!Tools.isAlipayBrowser() && !Tools.isWeChatBrowser() && !Tools.isGatApp() && !Tools.isCmbApp()){
            location.href = "../public/err_scan.html";
            return;
        }
        var appId = config.alipayAppId,
            code = Tools.getQueryValue('auth_code');
        if (common.isLogining) return;//过滤多次的登录请求
        var url = config.API_ALIPAY_ACCOUNT_AUTOLOGIN;
        var platform = "alipay";
        if (Tools.isWeChatBrowser()) {
            //微信浏览器
            appId = config.wechatAppId;
            code = Tools.getQueryValue('code');
            if(Tools.getQueryValue('from_wxpay') == 1 || Tools.getQueryValue('from_wxpay') == "1"){
                code = "";
            }
            url = config.API_WECHAT_ACCOUNT_AUTOLOGIN;
            platform = "wechat";
        }
        Cookie.set("platform", platform, null);
        common.isLogining = true;

        Tools.alert("code: " + code);
        if (void 0 === code || "" == code) {
            //尤其注意：由于授权操作安全等级较高，所以在发起授权请求时，微信会对授权链接做正则强匹配校验，如果链接的参数顺序不对，授权页面将无法正常访问
            //?appid=APPID&redirect_uri=REDIRECT_URI&response_type=code&scope=SCOPE&state=STATE&connect_redirect=1#wechat_redirect
            var n = location.origin + Tools.removeParamFromUrl(["from", "code", "share_id", "isappinstalled", "state", "m", "c", "a","from_wxpay"]);

            var t = config.alipayAuthUrl;
            if (Tools.isWeChatBrowser()) {
                t = config.wechatAuthUrl;
            }
            if(Tools.isWeChatBrowser()){
                t = t.replace('APPID', appId).replace('REDIRECT_URI', encodeURIComponent(n)).replace('SCOPE', 'snsapi_userinfo');
            }else if(Tools.isAlipayBrowser()){
                t = t.replace('APPID', appId).replace('REDIRECT_URI', encodeURIComponent(n));
            }

            //document.write(t);
            Tools.alert("url: " + t);
            location.href = t;
        } else {
            var f_url = Tools.removeParamFromUrl(["auth_code"]);
            Tools.alert("url : " + f_url);
            var no_mianmi = 0;
            for(var c = 0;c <config.NO_MIANMI.length;c++){
                Tools.alert("no  : " + config.NO_MIANMI[c]);
                if(f_url.indexOf(config.NO_MIANMI[c]) > 0){
                    no_mianmi = 1;
                    break;
                }
            }
            Tools.alert("MIANMI : " + no_mianmi);
            Cookie.set('hasLoad', '', -1);//若有登录，需清空弹出窗口的记录标志
            Cookie.remove("BoxToken", -1);
            Ajax.custom({
                url: url,
                type: 'POST',
                showLoading: true,
                data: {
                    auth_code: code,
                    device_id: Cookie.get("deviceId"),
                    mianmi:no_mianmi
                }
            }, function (response) {
                console.log(response);
                common.isLogining = false;
                var o = response.user;
                Cookie.remove("token");
                Cookie.remove("a_s_token");
                Cookie.set("token", response.token, null);
                //Cookie.set("deviceBind", response.token, null);
                if (o.mobile && parseInt(o.mobile) > 0) {
                    Cookie.set("mobile", o.mobile, null);
                } else {
                    Cookie.set("mobile", '', null);
                }
                Cookie.set("UserSN", o.id, null);
                Tools.alert("UserSN: " + Cookie.get("UserSN"));
                Tools.alert("deviceBind: " + response.token + " " + Cookie.get("deviceBind"));
                location.href = Tools.removeParamFromUrl(["auth_code"]);
                location.href = Tools.removeParamFromUrl(["code"]);
            }, function (e) {
                if (e.message.substring(0, 4) == "http") {
                    location.href = e.message;
                    return;
                }
                location.href = Tools.removeParamFromUrl(["auth_code"]);
                location.href = Tools.removeParamFromUrl(["code"]);
                Tools.showToast(e.message || '服务器异常');
                common.isLogining = false;
            })
        }
    }

    //检查当前登录状态
    common.checkLoginStatus = function (fn) {

        common.init = fn;
        var auth_code= Tools._GET().auth_code || 0;//判断关爱通的登录凭证
        Tools.alert(auth_code);
        if(Tools.isGatApp() && auth_code && auth_code != Cookie.get("auth_code")){//如果关爱通登录凭证失效
            Tools.alert(Cookie.get("auth_code"));
            Cookie.remove("UserSN");//清除登录信息
        }

        var userSn = Cookie.get("UserSN");
        if (userSn) {
            Tools.alert("good token & app id");
            //确保登录后在加载数据
            fn && fn();
        } else {
            //获取APPID
            Tools.alert('check login '+Cookie.get("deviceId"));
            Ajax.custom({
                url: config.API_GET_CONFIG,
                data: {
                    device_id: Cookie.get("deviceId")
                },
                showLoading: true
            }, function (response) {
                Cookie.set("alipayAppId", response.app_id, null);
                Cookie.set("wechatAppId", response.wechat_id, null);
                common.login();
            },function (e) {
                common.login();
            });
        }
    }

    /**
     * 开放给app调用的登陆入口
     * @param  {[type]} key APP登陆后的用户标示,eg：openId
     * @return {[type]}     [description]
     */
    common.loginForApp = function (key) {
        Cookie.set('UserSN', key);
        typeof common.init == 'function' && common.init();
    }

    //微信支付
    common.weixinPayOrder = function (orderId,refer,susFn,errorFn) {
        Ajax.custom({
            url: config.API_ORDER_PAY,
            data: {
                order_name: orderId,
                user:1
            },
            showLoading: true
        }, function (response) {
            var r = response;
            if(refer == 'alipay' || refer == 'gat'){//关爱通的未支付订单 也通过支付宝支付
                susFn && susFn(r);
            }else if(refer == 'wechat'){
                if (typeof WeixinJSBridge == 'undefined') {
                    Tools.alert('WeixinJSBridge is undefined');
                    return;
                }
                WeixinJSBridge.invoke("getBrandWCPayRequest", {
                    appId: r.appId,
                    timeStamp: r.timeStamp,
                    nonceStr: r.nonceStr,
                    "package": r.package,
                    signType: r.signType,
                    paySign: r.paySign
                }, function (o) {
                    Tools.alert(JSON.stringify(o));
                    if ("get_brand_wcpay_request:ok" == o.err_msg) {
                        // location.href = 'order.html?orderId=' + orderId;
                        susFn && susFn(r);
                    } else if ("get_brand_wcpay_request:cancel" == o.err_msg) {
                        errorFn && errorFn();
                    } else {
                        errorFn && errorFn();
                    }
                })

            } else{
                Tools.showAlert('支付成功');
                location.href = 'order.html?orderId=' + orderId;
            }
        }, function (e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }


    var onlyFirst = false; // 倒计时标志，确保只初始化一次

    /**
     * 自定义倒计时
     * @return {[type]} [description]
     */
    common.initCountDown = function (serverTime, sel) {
        if (onlyFirst) {
            return;
        }
        onlyFirst = true;

        var tick = 0,
            serverTime = parseInt(serverTime);
        setInterval(function () {
            $(sel).each(function (i, d) {
                var endTime = $(this).attr('data-end');
                $(this).text(Tools.getRunTime(serverTime + tick, endTime));
            })
            tick++;
        }, 1000)
    }

    /**
     * 自定义延迟加载图片
     * @param  {[type]} sel 图片选择器
     * @return {[type]}
     */
    common.lazyload = function (sel) {
        var dh = $(document).height(), //内容的高度
            wh = $(window).height(), //窗口的高度
            st = 0; //滚动的高度

        $(window).scroll(function () {
            st = $(window).scrollTop();
            init();
        })

        setTimeout(init, 200);

        function init() {
            $(sel).each(function (i, d) {
                if ($(this).hasClass('loaded')) return;

                var d = $(d).offset();
                if (d.top > st && d.top < (st + wh)) {
                    $(this).attr('src', $(this).attr('data-src')).addClass('loaded');
                }
            })
        }
    }

    //点击加载下一页
    $(document).on('click', '.nextpage', function (response) {
        if ($(this).hasClass('disabled')) return;
        config.page++;
        common.getList && common.getList();
    })

    //滚动到底自动加载下一页
    $(window).scroll(function () {
        if ($('.nextpage').length == 0 || $('.nextpage').hasClass('disabled')) return;

        var st = $(window).scrollTop(),
            wh = $(window).height(), //窗口的高度
            d = $('.nextpage').offset();

        if (d.top < (st + wh)) {
            config.page++;
            common.getList && common.getList();
        }
    })

    function general_sign(data,token){
        console.log(data);
        var query = '';

        var arr = new Array();
        for(var i in data){
            arr.push(i);
        }
        var data_sort = arr.sort();
        for(i=0;i<data_sort.length;i++){
            query += data_sort[i]+'='+data[data_sort[i]]+'&';
        }
        var platform = config.platformKey;
        var tmp = md5(query+platform);
        return md5(tmp.substring(0,tmp.length-1)+"q");
    }

    function getWeChatJsSdkSignature(e, t, r) {

        var n = $.ajax({
                url: config.HOST_API + "/account/getjsapisign",
                data: {
                    noncestr: e,
                    timestamp: t,
                    url: (r) //签名需要是未编码的地址，如果接口没有解析直接传值
                },
                beforeSend: function(request) {
                    request.setRequestHeader("platform", 'wap');
                    request.setRequestHeader("sign", general_sign({
                        noncestr: e,
                        timestamp: t,
                        url: (r) //签名需要是未编码的地址，如果接口没有解析直接传值
                    }));
                },
                timeout: 3e3,
                async: !1
            }).responseText,
            a = JSON.parse(n);
        return a.signature
    }

    function getShareIdUrl(e) {
        var t = Cookie.get("UserSN"),
            r = e;
        return r = r.indexOf("?") <= 0 ? r + "?share_id=" + t : r.indexOf("share_id=") <= 0 ? r + "&share_id=" + t : changeURLArg(r, "share_id", t)
    }

    function changeURLArg(url, arg, arg_val) {
        var pattern = arg + "=([^&]*)",
            replaceText = arg + "=" + arg_val;
        if (url.match(pattern)) {
            var tmp = "/(" + arg + "=)([^&]*)/gi";
            return tmp = url.replace(eval(tmp), replaceText)
        }
        return url.match("[?]") ? url + "&" + replaceText : url + "?" + replaceText
    }

    function wechat_share_config() {
        var e = config.wechatAppId,
            t = "734618974",
            r = Math.floor(Date.now() / 1000), //签名需要10位的时间戳
            n = getWeChatJsSdkSignature(t, r, document.URL, e);
        wx.config({
            debug: !1,
            appId: e,
            timestamp: r,
            nonceStr: t,
            signature: n,
            jsApiList: ["onMenuShareTimeline", "onMenuShareAppMessage","scanQRCode"]
        }), wx.error(function () {
        })
    }

    function wechat_share_override(e, t, r) {
        if (typeof wx == 'undefined') return;

        wechat_share_config();
        var n = getShareIdUrl(r);
        wx.ready(function () {
            wx.onMenuShareTimeline({
                title: e.title,
                link: n,
                imgUrl: e.imgUrl,
                success: function () {
                },
                cancel: function () {
                }
            }), wx.onMenuShareAppMessage({
                title: t.title,
                desc: t.desc,
                link: n,
                imgUrl: t.imgUrl,
                type: "link",
                success: function () {
                },
                cancel: function () {
                }
            })
        })

        common.share.timeline = e;
        common.share.app = t;
        common.share.url = r;
    }

    common.wx_scanQRCode = function (succFn) {
        if (typeof wx == 'undefined') return;
        var qr = '';
        wechat_share_config();
        wx.ready(function () {
            wx.scanQRCode({
                needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
                scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
                success: function (res) {
                    Tools.alert("bar:"+res.resultStr);
                    succFn(res.resultStr);
                }
            });
        });
    }
    common.aliScan = function (succFn,failFn) {
        if (typeof Ali == 'undefined') return;
        if(Tools.isAlipayBrowser()){
            if((Ali.alipayVersion).slice(0,3)>=8.1){
                Ali.scan({
                    type: 'qr'
                }, function(result) {
                    Tools.alert(result);
                    if(result.errorCode){
                        //没有扫码的情况
                        //errorCode=10，用户取消
                        //errorCode=11，操作失败
                        if(failFn){
                            failFn(result);
                        }
                    }else{
                        //成功扫码的情况
                        if(result.barCode !== undefined){
                            Tools.alert('条码是：'+result.barCode);
                            succFn(result.barCode);
                        }else if(result.qrCode !== undefined){
                            Tools.alert('二维码是：'+result.qrCode);
                            succFn(result.qrCode);
                        }else if(result.cardNumber !== undefined){
                            Tools.alert('银行卡号是：'+result.cardNumber);
                        }else{
                            Tools.alert(result);
                        }
                    }
                });
            }else{
                alert('请在钱包8.1以上版本运行');
            }
        }else{
            Tools.showAlert('请使用支付宝钱包扫一扫');
        }
    }


    common.share = {
        override: function (e, t, r) {
            var n = void 0 == r ? document.URL : r;
            wechat_share_override(e, t, n)
        },
        default_send: function () {
            var e = {
                    title: Config.C("share_text").default.title,
                    imgUrl: "http://assets.yqphh.com/assets/images/logo.jpg"
                },
                t = {
                    title: MasterConfig.C("shop_name") + "商城",
                    desc: Config.C("share_text").default.desc,
                    imgUrl: "http://assets.yqphh.com/assets/images/logo.jpg"
                };
            wechat_share_override(e, t, MasterConfig.C("baseMobileUrl"))
        }
    };

    //获取您正在参与的活动
    common.getJoiningActivity = function () {
        if ($('#rby-join').length == 0 || Cookie.get('hasLoad') == '1') return;
        Cookie.set('hasLoad', 1, 0);

        Ajax.custom({
            url: config.HOST_API + '/index/getPopList'
        }, function (response) {
            var data = response.body;
            if (data && data.length > 0) {
                Ajax.render('#rby-join-list', 'rby-join-list-tmpl', data);
                $('#rby-join').css({
                    'height': 310,
                    'margin-top': -310 / 2
                }).show();
                $('#rby-cover-bg').show();
            }
        })
    }

    //关闭参与活动界面
    function closeResult() {
        $('#rby-cover-bg').hide();
        $('#rby-join').hide();
    }

    $('.join-close').click(function (e) {
        closeResult();
    })

    $('#rby-cover-bg').click(function (e) {
        closeResult();
    })

    if (document.getElementById('rby-cover-bg')) {
        //取消遮罩层的默认滑动
        document.getElementById('rby-cover-bg').addEventListener('touchmove', function (e) {
            e.preventDefault();
        }, true);
    }

    //测试用，团购加入购物车
    common.abcLogin = function (id) {
        Ajax.custom({
            url: config.HOST_API + "/account/test_login",
            data: {
                id: id
            }
        }, function (response) {

            console.log(response);
            common.isLogining = false;
            var o = response.user;
            //Cookie.set("deviceBind", response.token, null);
            //Cookie.set("OpenId", o.open_id, null);
            Cookie.set("token", response.token, null);
            Cookie.set("UserSN", o.id, 0);
            Cookie.set("mobile", o.mobile, null);
            Tools.alert("UserSN: " + Cookie.get("UserSN"));
            location.href = 'person.html'
        }, function (e) {
            Tools.showAlert(e.message || '服务器异常');
            common.isLogining = false;
        })
    }

    /**
     * 口碑网页支付
     * @param tradeNO
     * @param sucFn
     * @param failFn
     */
    common.alipayPayForIsv = function (tradeNO,sucFn,failFn) {
        // 通过传入交易号唤起快捷调用方式(注意tradeNO大小写严格)
        AlipayJSBridge.call("tradePay", {
            tradeNO: tradeNO
        }, function (data) {
            if ("9000" == data.resultCode) {
                if(sucFn){
                    sucFn(data);
                } else if(failFn){
                    failFn(data);
                }
            }
        });
    }

    window.common = common;

    if ('FastClick' in window)
        FastClick.attach(document.body);

    if (Tools.isFruitdayAppBrowser()) {
        // 设置顶部导航
        $('.back .icon-morehome').hide().click(function (e) {
            e.preventDefault();
        });
        $('.back .icon-searchhomedel').hide().click(function (e) {
            e.preventDefault();
        });
    }
    if (Tools.isFruitdayiOSBrowser()) {
        $('body').addClass('has-app');
    }

})();

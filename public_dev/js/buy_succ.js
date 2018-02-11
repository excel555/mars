(function() {
    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

    // body.find('.layout').height(layout_Height);

    function ready(callback) {
        // 如果jsbridge已经注入则直接调用
        if (window.AlipayJSBridge) {
            callback && callback();
        } else {
            // 如果没有注入则监听注入的事件
            document.addEventListener('AlipayJSBridgeReady', callback, false);
        }
    }

    document.addEventListener('resume', function (event) {
        AlipayJSBridge.call('closeWebview');
    });

    document.addEventListener('pause', function (e) {
        AlipayJSBridge.call('closeWebview');
    }, false);

    pushHistory();
    window.addEventListener("popstate", function(e) {
        pushHistory();
        var ua = navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i)=="micromessenger") {
            WeixinJSBridge.call('closeWindow');
        } else if(ua.indexOf("alipay")!=-1){
            AlipayJSBridge.call('closeWebview');
        } else{
            window.close();
        }
    }, false);
    function pushHistory() {
        var state = {
            title: "",
            url: "#"
        };
        window.history.pushState(state, "", "#");
    }


    var container = $('#buy-container');
    var order_name = Tools._GET().order_name || 0;
    var device_id = Tools._GET().deviceId || 0;
    var type = Tools.returnUserAgent();
    if(type == 'gat'){
        $('#alipay-tab li').eq(0).find('a').attr('href', 'gatgive://page.gat/native?name=code_scanner');
    }
    if(type == 'cmb'){
        $('#open_btn').hide();
        $('#order_btn').show();
    }
    var refund_money = $('#money'),
        reason = $('#reason'),
        reason_detail = $('#reason_detail');

    if(order_name == 0 || order_name == '0'){
        $('#refund_btn').hide();
    }
    history.replaceState(null,null,'index.html');//修改history.back

    body.on('click', '.pull>img', function(){
        if($(this).hasClass('down')){
            $(this).removeClass().addClass('up');
            $('#order-list>li').slideDown(200)
        }else if($(this).hasClass('up')){
            $(this).removeClass().addClass('down');
            $('#order-list>li').each(function(i){
                if(i>2){
                    $(this).slideUp(200);
                }
            })
        }
    });

    body.on('click', '.wenhao', function(){
        $('.tips-modou').show();
    });
    body.on('click', '.tips-modou', function(){
        $('.tips-modou').hide();
    });

    body.on('click', '#alipay-tab>li', function(){
        $('#alipay-tab>li>a').removeClass('cur');
        $(this).find('a').addClass('cur');
        hide = 1;
        $('.sub-menu').slideUp(200);
    });



    var hide = 1;
    body.on('click', '.menu', function(){
        if(hide == 1){
            $('.sub-menu').slideDown(200);
            hide = 0;
        }else{
            hide = 1;
            $('.sub-menu').slideUp(200);
        }
    });

    var order_status = 0;
    //退款
    body.on('click', '#refund_btn', function(e) {
        e.preventDefault();

        Ajax.custom({
            url: config.API_ORDER_DETAIL,
            data: {
                order_name: order_name
            },
            showLoading: true
        }, function(response) {
            if(response.order_status != "1"){
                Tools.showToast('订单 '+ config.ORDER_STATUS[response.order_status]);
                location.href = 'order.html?orderId='+order_name;
            }
        }, function(data) {

        });

        var mobile = Cookie.get("mobile");
        if(!mobile){
            Tools.showAlert('为了更好的提供售后服务，请先绑定手机号',0,function () {
                location.href='register.html?redirect_url='+encodeURIComponent(location.href);
            });
            return;
        }

        if (!order_name) Tools.showAlert('数据错误，订单不存在');
        $('#login-form').show();
        $('#rby-cover-bg').show();
    });

    // 关闭登陆注册框操作
    $('.account span.iconfont').click(function() {
        $('#login-form').hide();
        $('#rby-cover-bg').hide();
    })

    function refund(){
        Ajax.custom({
            url: config.API_ORDER_REFUND,
            data: {
                order_name: order_name,
                reason: reason.val(),
                reason_detail: reason_detail.val(),
                refund_money: refund_money.val(),
                photo: '--'
            },
            type: 'POST'
        }, function(response) {
            location.href = 'order.html?orderId='+order_name;
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        })
    }

    $(".account").on('click', '#refund_btn_send', function(e) {
        e.preventDefault();
        if (!order_name) Tools.showAlert('数据错误，订单不存在');
        if (reason.val().isEmpty()) {
            Tools.showToast('申请理由不能为空');
            return;
        }
        if (refund_money.val().isEmpty()) {
            Tools.showToast('退款金额不能为空');
            return;
        }
        if (parseFloat(refund_money.val()) > parseFloat(money) ) {
            Tools.showToast('退款金额不能大于支付金额');
            return;
        }
        if (parseFloat(refund_money.val()) <= parseFloat(0) || isNaN(parseFloat(refund_money.val()))) {
            Tools.showToast('退款金额不能小于0');
            return;
        }
        refund();

    });
    var money = 0;
    var title = '世界那么大,我们吃光它!';
    var desc = '物美价廉不排队,一起吃的更划算!';
    var pic = location.protocol + '//' + location.host +'/public/img/redpacket.png';
    var url = location.protocol + '//' + location.host +'/public/share-coupon.html?id=ACTIVEID&order_name='+order_name;
    function get_user_order_amount(){
        Ajax.custom({
            url: config.API_DEVICE_ORDER_AMOUNT,
            data:{
                order_name: order_name,
                device_id:device_id
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            if(response.order_goods.length > 0 && response.order)
            {
                order_status = parseInt(response.order.order_status);
                money = response.order.money;
                if(response.order.money <= 0){
                    $('#refund_btn').hide();
                    $('.suc>dl').css('border-bottom','none');
                }
            }else{
                response.order.money = "0.00";
                response.order.good_money = "0.00";
            }
            response.order.total_money = parseFloat(response.order.money) + parseFloat(response.order.yue);
            response.order.discounted = parseFloat(response.order.discounted_money) + parseFloat(response.order.card_money) +  parseFloat(response.order.modou) + parseFloat(response.order.level_money);

            Ajax.render("#buy-container", "buy-content-tmpl", response);
            if(response.ordercard.active_id > 0){
                $(".hongbao-geshu").html(''+response.ordercard.card_count+'<small>个</small>')
                pic = response.ordercard.share_img;
                title = response.ordercard.share_title;
                desc = response.ordercard.share_content;
                url = url.replace('ACTIVEID', response.ordercard.active_id);
                $("#fuli-panel").show();
                $(".honghaojingling").hide();
            }
            get_cityshop_coupon(device_id);
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    //click banner /icon
    $('body').on('click', '.click_icon_img', function(e){
        e.preventDefault();
        console.log(e)
        var click_type = $(this).attr('click_type');
        var click_url = $(this).attr('click_url');
        if(click_type == 1 && click_url){
            location.href = click_url;
        }else  if(click_type == 2 && click_url){
            $(".banner_img").attr('src',click_url);
            $("#popup-window").removeClass('hide');
        }
    });

    //弹窗关闭
    $('.popWin-hide, .popWin-close').click(function(e){
        e.preventDefault();
        console.log(e)
        // $(".popup-window").removeClass('bounceInDown').addClass('fadeOutDownBig');
        $(".popup-window").addClass('hide');
    });

    $('.fuli-close').click(function(e){
        $("#fuli-panel").hide();
        $(".honghaojingling").show();
    });

    $('body').on('click', '.honghaojingling', function(e){
        $("#fuli-panel").show();
        $(".honghaojingling").hide();
    });

    $('.share-bg').click(function () {
        $('.share-bg').hide();
        $(".honghaojingling").show();
    });
    $('.share-btn').click(function () {
        $("#fuli-panel").hide();
        if(Tools.isAlipayBrowser()){
            alipay_share();
            $(".honghaojingling").show();
        }else{
            $('.share-bg').show();
        }
    });
    common.checkLoginStatus(function() {
        get_user_order_amount();
    });



    function alipay_share() {
        Tools.alert(pic);
        if ((Ali.alipayVersion).slice(0, 3) >= 8.1) {
            Ali.share({
                //渠道名称。支持以下几种：Weibo/LaiwangContacts/LaiwangTimeline/Weixin/WeixinTimeLine/SMS/CopyLink
                'channels': [
                    {
                        name: 'ALPContact',   //支付宝联系人,9.0版本
                        param: {   //请注意，支付宝联系人仅支持一下参数
                            contentType: 'url',    //必选参数,目前支持支持"text","image","url"格式
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic,   //必选参数,缩略图url，发送前预览使用,
                            imageUrl: pic, //图片url
                            url: url,   //必选参数，卡片跳转连接
                            title: title,    //必选参数,分享标题
                            memo: ""   //透传参数,分享成功后，在联系人界面的通知提示。
                        }
                    }, {
                        name: 'ALPTimeLine', //支付宝生活圈
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }, {
                        name: 'Weixin', //微信
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }, {
                        name: 'WeixinTimeLine', //朋友圈
                        param: {
                            contentType: 'url',    //必选参数,目前只支持"url"格式
                            title: title,   //标题
                            url: url,  //url
                            content: desc,    //必选参数,分享描述
                            iconUrl: pic //icon
                        }
                    }
                ]
            }, function (result) {
                Tools.alert('分享成功');
                Tools.alert(result);
            });
        } else {
            alert('请在钱包8.1以上版本运行');
        }
    }


    /*城市优惠券 start*/
    $('.close-img').click(function () {
        $('.cityshop-coupon').hide();
    });
    $('body').on('click', '.cityshop-coupon-img', function(e){
        $('.cityshop-coupon').hide();
        Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", {"input":1});
        $("#cityshop-panel").show();
    });

    function get_cityshop_coupon(deviceId) {
        Ajax.custom({
            url: config.HOST_API + '/device/exist_thirdparty',
            data:{
                device_id:deviceId
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            if(response.thirdparty_cityshop && response.thirdparty_cityshop.active_id && response.thirdparty_cityshop.is_get != 1){
                $('.cityshop-coupon-img').attr('active_id',response.thirdparty_cityshop.active_id);
                $('.cityshop_img').show();

            }
        },function (e) {
            Tools.showAlert(e.message || '服务器异常，稍后重试')
        });
    }
    $('body').on('click', '.mobile-modify', function(e){
        var mobile = $(this).attr('data-mobile');
        Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", {"input":1,"mobile":mobile});
        $("#cityshop-panel").show();
    });
    $('body').on('click', '.cityshop-send-btn', function(e){
        fuli_info();
    });
    function fuli_info() {
        var phone = $('#mobile');
        if (phone.val().isEmpty()) {
            Tools.showToast('手机号不能为空');
            return;
        }
        if(!phone.val().isPhone()){
            Tools.showToast('手机号不正确');
            return;
        }

        var active_id = $('.cityshop-coupon-img').attr('active_id');
        Ajax.custom({
            url: config.HOST_API + '/device/thirdparty_card',
            data:{
                thirdpartycard_id:active_id,
                mobile:phone.val()
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            response.mobile = phone.val();
            Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", response);
            $("#cityshop-panel").show();
        },function (response) {
            console.log(response);
            Tools.showToast(response.message || '服务器异常');
        });
    }

    $('body').on('click', '.cityshop-close', function(e){
        $("#cityshop-panel").hide();
    });
    /*城市优惠券 start*/
})()



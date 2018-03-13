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
    history.replaceState(null,null,'index.html');//修改history.back

    var money = 0;
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
                money = response.order.money;

            }else{
                response.order.money = "0.00";
                response.order.good_money = "0.00";
            }
            response.order.total_money = parseFloat(response.order.money) + parseFloat(response.order.yue);
            response.order.discounted = parseFloat(response.order.discounted_money) + parseFloat(response.order.card_money) +  parseFloat(response.order.modou) + parseFloat(response.order.level_money);
            Ajax.render("#buy-container", "buy-content-tmpl", response);
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        });
    }

    common.checkLoginStatus(function() {
        get_user_order_amount();
    });
})()



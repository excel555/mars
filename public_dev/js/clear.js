(function() {

    $(".order").click(function(){
        Cookie.set("token", "", -1);
        Cookie.set("BoxToken", "", -1);
        Cookie.set("OpenId", "", -1);
        Cookie.set("UserSN", "", -1);
        Cookie.set('hasLoad', '', -1);
        Cookie.remove("mobile");
        Cookie.remove("deviceBind");

        alert("userSn="+Cookie.get("UserSN"));
    });

    $(".alipay").click(function(){
        alert(Tools.returnUserAgent());
        alert(navigator.userAgent.toLowerCase());
        alert(Tools.isAlipayBrowser());
    });
    $(".group").click(function(){
        common.checkLoginStatus(function() {//入口
            alert('checkLoginStatus');
        });
        //打开
        Cookie.set("DevDebug",1);
        alert(Cookie.get("DevDebug"));
    });
    $(".address").click(function(){
        //关闭
        Cookie.set("DevDebug",0);
        alert(Cookie.get("DevDebug"));
    });
    document.addEventListener('AlipayJSBridgeReady', function () {
        console.log(typeof AlipayJSBridge);
        AlipayJSBridge.call("hideToolbar");
        AlipayJSBridge.call("hideOptionMenu");
    }, false);

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
        alert('页面回退时带过来的内容： ' + JSON.stringify(event.data));
    });

    document.addEventListener('pause', function (e) {
        alert('paused');
    }, false);

})()

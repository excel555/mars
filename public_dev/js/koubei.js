(function() {
    var container = $('#index-content');
    var app_auth_code = Tools._GET().app_auth_code || 0;
    var app_id = Tools._GET().app_id || 0;
    var platform_id = Tools._GET().platform_id || 0;

    if(Tools.isAlipayBrowser()){
        auth();
    }else{
        Tools.showAlert("请在支付宝中打开");
    }

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


    function auth(){
        Ajax.custom({
            url: config.HOST_API + '/koubei/auth_koubei_login',
            data:{
                auth_code: app_auth_code,
                app_id:app_id,
                platform_id:platform_id
            },
            type: 'POST',
            showLoading: false
        }, function(response) {
            container.text(JSON.stringify(response));
        }, function(e) {
            console.log(e)
        })
    }
})()



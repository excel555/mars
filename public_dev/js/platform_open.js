(function() {

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

    var container = $('#index-content');
    var deviceId = Tools._GET().d || 0;
    var connect_id = Tools._GET().connect_id || 0;
    var auth_code = Tools._GET().auth_code || 0;
    var source = Tools._GET().source || 0;
    var redirect_uri = Tools._GET().redirect_uri || '';
    var code = Tools._GET().code || 0;//索迪斯使用到
    var TID  = Tools._GET().TID || 0;//索迪斯使用到
    var sign  = Tools._GET().sign || 0;//索迪斯使用到


    if(auth_code){
        var platform = 'gat';
    }else if(source && source == 'cmbchina'){
        var platform = 'cmb';
    }else if(source && source == 'sodexo'){
        var platform = 'sodexo';
    }else{
        var platform = Tools.returnUserAgent();
    }
    
    Cookie.set("deviceId", deviceId, null);
    history.replaceState(null,null,'wfz_error.html');
    function log_access() {
        Ajax.custom({
            url: config.API_PLATFORM_ACCESS,
            data:{
                device_id: deviceId,
                platform:platform,
                connect_id:connect_id,
                auth_code:auth_code,
                redirect_uri:redirect_uri,
                code:code,
                TID:TID,
                sign:sign
            },
        }, function(response) {
            if(platform == 'gat'){
                Cookie.set("UserSN",response['user']['id'], null);
                Cookie.set("token", response.token, null);
                Cookie.set("auth_code", auth_code, null);//保存关爱通登录凭证
                location.href = decodeURIComponent(response['user']['redirect_url']);
            }else{
                //Tools.alert(decodeURIComponent(response));
                location.href = decodeURIComponent(response);
            }
            Tools.alert(decodeURIComponent(response));
        },function (e) {
            if(e.message.msg){
                //限制开门方式
                var opt = {
                    showTips:true,
                    tipsText:"我知道了",
                    showTitle:true,
                    message: e.message.msg || '网络不太好，稍后重试',
                    titleText:"开门失败",
                    cancelText:false,
                    tipsCallback:function () {
                        if(e.message.redirect_url){
                            location.href = e.message.redirect_url;
                        }else{
                            location.href = 'index.html?deviceId='+deviceId;
                        }
                    },
                }
                Tools.showAlert(opt);
                return false;
            }else if(e.message.msg){
                Tools.showAlert(e.message.msg);
            }else{
                Tools.showAlert("系统异常，请返回重新扫码");
            }
        });
    }
    
    container.on('click', '.empty-page-tip', function() {
        location.reload();
    });

    log_access();
    // common.checkLoginStatus(function() {
    // })
})()



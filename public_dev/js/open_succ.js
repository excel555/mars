var deviceId = Tools._GET().deviceId || 0;
var timer;
var flag = false;
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

    history.replaceState(null,null,'index.html?device_id='+deviceId);//修改history.back
    function check() {
        timer = setInterval("check_status()",3000);
    }

    common.checkLoginStatus(function() {
        clearInterval(timer);
        check();
    })
})()

function check_status() {
    console.log("flag = "+flag);
    Ajax.custom({
        url: config.API_FD_BOX_STATUS,
        data:{
            device_id: deviceId,
        },
        type: 'GET',
        showLoading: false
    }, function(response) {
        console.log(response);
        if(response.status == "stock"){
            $('#rby-loading1').show();
            flag = false;
        }else if(response.status == "pay_succ"){
            if(flag == false){
                flag = true;
                clearInterval(timer);
                location.href='buy_succ.html?order_name='+response.order_name+'&deviceId='+deviceId;//只显示关闭按钮
            }else{
                flag = true;
                clearInterval(timer);
                alert('不跳转了');
            }
        }else if(response.status == "free"){
            location.href='index.html';
        }
    }, function(e) {
        // Tools.showAlert(e.message || '服务器异常');
    });
}
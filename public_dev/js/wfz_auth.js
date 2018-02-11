(function() {

    var container = $('#index-content');
    $('body').height($(window).height());
    var deviceId = Tools._GET().deviceId || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
        Tools.alert('auth'+Cookie.get("deviceId"));
    }
    var type = "";
    if(Tools.isAlipayBrowser()){
        type = "alipay";
    }else {
        container.hide();
        Tools.showToast("请使用支付宝APP首页扫一扫")
        return;
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


    function open(){
        history.replaceState(null,null,'wfz_error.html');
        Ajax.custom({
            url: config.API_OPEN_DOOR,
            data:{
                device_id: deviceId,
                type:type
            },
            type: 'POST',
            showLoading: false
        }, function(response) {
            if(response['status'] == 'error' && response['url']){
                location.href = response['url'];
                return false;
            }
            if(deviceId == "00D051AD03C4"){
                location.href='wfz_gate.html';
            }else{
                location.href='wfz_open_succ.html?from=auth&deviceId='+deviceId;
            }
        }, function(e) {
            if (e.message.substring(0, 4) == "您有未支") {
                showNoPay();
            }else{
                showDoorError(e.message);
            }
            return;
        })
    }
    function showNoPay() {
        $(".error_img").attr('src','./img/wfz-warning.png');
        $(".error_img").attr('width','100');
        $(".main-tips").text('您有待支付订单');
        $(".tips").show();
        $(".error_process").hide();
        $(".cancel-btn").show();
        $(".btn-know").show();
        $(".btn-know").text('去支付');
        $(".btn-know").attr('data-type','pay');
    }
    function showDoorError(text) {
        $(".error_img").attr('src','./img/wfz-unhappy.png');
        $(".error_img").attr('width','100');

        $(".main-tips").text(text);
        $(".tips").hide();
        $(".error_process").show();
        $(".cancel-btn").hide();
        $(".btn-know").show();
        $(".btn-know").attr('data-type','error');
    }



    container.on('click', '.cancel-btn', function() {
        console.log('close');
        AlipayJSBridge.call('closeWebview');
    });
    container.on('click', '.btn-know', function() {
        console.log('btn-know');
        if($(this).attr('data-type') == 'pay'){
            location.href='orders.html?status=0';
        }else{
            AlipayJSBridge.call('closeWebview');
        }
    });

    common.checkLoginStatus(function() {
        open();
    })
})()



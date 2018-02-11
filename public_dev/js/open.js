(function() {
    var admin = Tools._GET().admin || -1;
    var is_new = Tools._GET().is_new || -1;
    var ref = Tools._GET().ref || 'deliver';
    var deviceId = Tools._GET().deviceId || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    history.replaceState(null,null,'index.html');//修改history.back
    function rec_scan_succ(qrCode){
        container.html('<div class="opening_gif">开门中，请不要关闭页面</div>');
        Ajax.custom({
            url: config.API_REC_ADMIN,
            data:{
                admin: admin,
                qrcode:qrCode,
                is_new:is_new
            },
            type: 'POST',
            showLoading: false
        }, function(response) {
            deviceId =  response.device_id;
            Cookie.set("deviceId", deviceId, null);
            if(admin != -1){
                if(response.status == 'redirect') {
                    if(ref == 'deliver'){
                        location.href = 'deliver.html?admin=1&deviceId=' + deviceId;
                    }else {
                        location.href = 'stock.html?deviceId=' + deviceId;
                    }
                }else{
                    AlipayJSBridge.call('closeWebview');
                }
            }else{
                if(response['status'] == 'error' && response['url']){
                    location.href = response['url'];
                    return false;
                }else if(response['status'] == 'limit_open_refer'){
                    //限制开门方式
                    if(response.message){
                        var opt = {
                            showTips:true,
                            tipsText:"我知道了",
                            showTitle:true,
                            message: response.message || '网络不太好，稍后重试',
                            titleText:"开门失败",
                            cancelText:false,
                            panelImg:'img/Openning_close@2x.png',
                            tipsCallback:function () {
                                if(response['redirect_url']){
                                    location.href = response['redirect_url']
                                }else{
                                    location.href = 'index.html?deviceId='+deviceId;
                                }
                            },
                        }
                        Tools.showAlert(opt);
                    }
                    return false;
                }else if(response.status == 'redirect_p') {
                    location.href=response.redirect_url;
                }else{
                    location.href='open_succ.html?from=open&deviceId='+deviceId;
                }
            }
        }, function(e) {
            if (e.message.substring(0, 4) == "您有未支") {
                var opt = {
                    showTitle:true,
                    message: '有未支付订单，需先完成支付，才能继续开门购物',
                    titleText:"您有未支付订单",
                    cancelText:"取消",
                    okText:"去支付",
                    panelImg:'img/Openning_list@2x.png',
                    yesCallback:function () {
                        location.href='orders.html?status=0';
                    },
                    noCallback:function () {
                        location.href = 'index.html?deviceId='+deviceId;
                    }
                }
                Tools.showAlert(opt);
                return;
            }else{
                var opt = {
                    showTips:true,
                    tipsText:"我知道了",
                    showTitle:true,
                    message: e.message || '网络不太好，稍后重试',
                    titleText:"开门失败",
                    cancelText:false,
                    panelImg:'img/Openning_close@2x.png',
                    tipsCallback:function () {
                        location.href = 'index.html?deviceId='+deviceId;
                    },
                }
                Tools.showAlert(opt);
            }
        });

    }
    function failFn(res) {
        Tools.alert(res);
        location.href='index.html'
    }
    function qr(){
        if (Tools.isWeChatBrowser()) {
            common.wx_scanQRCode(rec_scan_succ);
        }
        else if (Tools.isAlipayBrowser()) {
            common.aliScan(rec_scan_succ,failFn);
        }else{
            location.href='err_scan.html'
        }
    }
    var container = $('#index-content');

    container.on('click', '.empty-page-tip', function() {
        qr();//扫码
    });
    common.checkLoginStatus(function() {
        qr();//扫码
        container.html('<div class="empty-page-tip">点击重新扫码开门</div>');
    })
})()



(function() {
    Tools.showLoading();
    var deviceId = Tools._GET().deviceId || 0;
    if(!deviceId)
    {
        alert('参数丢失，请重新扫码');
        return;
    }
    alert('请扫描出库单上条形码');

    function deliver_scan(qrCode){
        Ajax.custom({
            url: config.API_DELIVER_SCAN,
            data:{
                device_id:deviceId,
                deliver_no:qrCode
            },
            type: 'POST',
            showLoading: true
        }, function(response) {
            deviceId =  response.device_id;
            Tools.alert(response);
            Cookie.set("deviceId", deviceId, null);
            Tools.showToast("补货单校验中....");
            if(response.status == 'redirect')
            {
                location.href='deliver_confirm.html?deviceId='+deviceId+'&deliver_no='+response.deliver_id;
            }  else if(Tools.isAlipayBrowser()){
                AlipayJSBridge.call('closeWebview');
            }
        }, function(e) {
            container.html('<div class="empty-page-tip" d_code="'+qrCode+'">'+e.message+'，点击重新开门</div>');
            $('.footer-goods').show();
            $('.btn-add-cart').attr('d_code',qrCode);
            Tools.showAlert(e.message || '服务器异常');
            Tools.hideLoading();
        });

    }
    $('.btn-add-cart').click(function (e) {
        var qrCode = $('.btn-add-cart').attr('d_code');
        deliver_scan(qrCode);

    });

    function qr(){
        $('.footer-goods').hide();
        common.aliScan(deliver_scan);
        Tools.hideLoading();
    }
    var container = $('#index-content');

    container.on('click', '.empty-page-tip', function() {
        var qrCode =$('.empty-page-tip').attr('d_code');
        if(qrCode){
            deliver_scan(qrCode);
        }else{
            qr();//扫码
        }
    });
    common.checkLoginStatus(function() {
        qr();//扫码
        container.html('<div class="empty-page-tip">点击扫描条形码</div>');
    })
})()



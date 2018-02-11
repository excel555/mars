(function() {
    Tools.showLoading();
    var container = $('#index-content');
    var deviceId = Tools._GET().d || Cookie.get('deviceId');
    var pay_code  = Tools._GET().pay_code || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
        Tools.alert('auth'+Cookie.get("deviceId"));
    }
    var type = "";


    function open(){
        Ajax.custom({
            url: config.API_GAT_OPEN,
            data:{pay_code:pay_code,device_id:deviceId},
            type: 'POST',
            showLoading: true
        }, function(response) {
            //history.replaceState(null,null,'index.html?deviceId='+deviceId);//修改history.back
            Tools.showToast("开门中....");
            location.href='open_succ.html?from=gat&deviceId='+deviceId;
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '网络不太好，稍后重试');
        })
    }

    //
    container.on('click', '.empty-page-tip', function() {
        open();//再次开门
    });
    container.html('<div class="empty-page-tip">请求开门中，请稍后...</div>');
    open();
})();



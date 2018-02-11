(function() {
    Tools.showLoading();
    var container = $('#index-content');
    var action  = Tools._GET().action || '';
    var order_name  = Tools._GET().order_name || '';
    var deviceId = Tools._GET().d || Cookie.get('deviceId');
    var token = Tools._GET().token || Cookie.get('token');
    var user_id = Tools._GET().user_id || Cookie.get('user_id');
    var open_passwd  = Tools._GET().open_passwd || 0;
    
    if(token)
    {
        Cookie.set("token", token, null);
    }
    if(user_id)
    {
        Cookie.set("UserSN", user_id, null);
    }
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    if(action == 'doPay'){
        location.href = config.API_CMB_BALANCE+'?order_name='+order_name+'&deviceId='+deviceId;//招商银行手机支付
    }

    function open(){
        Ajax.custom({
            url: config.API_CMB_OPEN,
            data:{open_passwd:open_passwd,device_id:deviceId},
            type: 'POST',
            showLoading: true
        }, function(response) {
            Tools.showToast("开门中....");
            location.href='open_succ.html?from=cmb&deviceId='+deviceId;

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



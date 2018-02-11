(function() {
    Tools.showLoading();
    var container = $('#index-content');
    var deviceId = Tools._GET().deviceId || 0,
        platform =Tools._GET().p || 'fruitday-web',
        code =Tools._GET().code || 0;

    if(platform == 'fruitday-app'){
        code =Tools._GET().connect_id || 0;
    }
    Tools.alert(location.href);

    Cookie.set("scan_platform", platform, null);
    Cookie.set("deviceId", deviceId, null);

    function auth(){
        if(code == 0 && connect_id == 0){
            Tools.showAlert('缺少参数，请重新扫码');
            return;
        }

        Ajax.custom({
            url: config.API_PLATFORM_LOGIN,
            data:{
                device_id: deviceId,
                platform:platform,
                code:decodeURIComponent(code),
            },
            type: 'POST',
            showLoading: true
        }, function(response) {
            Tools.alert(response);
            var o = response.user;
            if(o.mobile && parseInt(o.mobile)>0){
                Cookie.set("mobile", o.mobile, null);
            }else{
                Cookie.set("mobile", '', null);
            }
            Cookie.set("token", response.token, null);
            Cookie.set("UserSN", o.id, null);
            Cookie.set("fruitday_money", o.fruitday_money, null);
            Cookie.set("deviceId", deviceId, null);
            Cookie.set("connect_id", code, null);
            Tools.alert("UserSN: " + Cookie.get("UserSN"));
            location.href =  o.redirect_url;
        }, function(e) {
            Tools.showToast(e);
            location.href = "p.html?d="+deviceId;
        })
    }

    container.on('click', '.empty-page-tip', function() {
        location.reload();
    });
    auth();
})()



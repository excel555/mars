(function() {
    Tools.showLoading();
    var container = $('#index-content');
    var deviceId = Tools._GET().deviceId || 0,
        platform =Tools._GET().scan_platform || 'fruitday',
        connect_id =Tools._GET().connect_id || 0;
    var refer = Tools._GET().refer || '';

    Tools.alert(location.href);

    Cookie.set("scan_platform", platform, null);
    Cookie.set("deviceId", deviceId, null);

    if(platform == "fruitday"){
        if(Tools.isFruitdayAppBrowser()){
            platform = "fruitday-app"
        }else{
            platform = "fruitday-web";
        }
    }
    log_access();

    if(platform == "fruitday-web"){
        //如果是果园是二维码，并且是用浏览器打开，则直接跳转下载APP页面
        location.href = "http://a.app.qq.com/o/simple.jsp?pkgname=com.ttxg.fruitday";
    }
    // if(platform == "fruitday-web" && connect_id == 0){
    //     location.href = "http://m.fruitday.com/boxLogin?deviceId="+deviceId+"&scan_platform=fruitday";
    //     return;
    // }

    function auth(){
        if(connect_id == 0){
            Tools.showAlert('缺少参数，请重新扫码');
            return;
        }
        Ajax.custom({
            url: config.API_AUTH_OPEN,
            data:{
                device_id: deviceId,
                platform:platform,
                connect_id:connect_id,
            },
            type: 'POST',
            showLoading: true
        }, function(response) {
            // Tools.showToast("授权登录中....");
            Tools.showLoading();
            Tools.alert(response);
            console.log(response);
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
            Cookie.set("connect_id", connect_id, null);
            Tools.alert("UserSN: " + Cookie.get("UserSN"));
            Tools.alert("deviceBind: " +response.token);
            if(platform == "fruitday-web" || platform == "fruitday-app" ){
                if(refer == "index"){
                    location.href = "fruitday_index.html?goBackEnable=0";
                }else{
                    location.href = "fruitday_amount.html?goBackEnable=0";
                }
            }else{

            }
        }, function(e) {
            // container.html('<div class="empty-page-tip">'+e.message+'</div>');
            if(platform == "fruitday-web"){
                location.href = "http://m.fruitday.com/boxLogin?deviceId="+deviceId+"&scan_platform=fruitday";
            }else{
                Tools.showAlert(e.message || '服务器异常');
            }
        })
    }

    function log_access() {
        Ajax.custom({
            url: config.API_PLATFORM_ACCESS,
            data:{
                device_id: deviceId,
                platform:platform,
            },
        }, function(response) {
        });
    }
    
    container.on('click', '.empty-page-tip', function() {
        location.reload();
    });
    auth();
})()



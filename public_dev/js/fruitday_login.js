var platform = Tools.returnUserAgent();
var deviceId = Tools._GET().deviceId || 0;
var connect_id =Tools._GET().connect_id || 0;
if(platform == "fruitday-app" || platform == "fruitday-web"){
    if(Tools.isFruitdayAppBrowser()){
        platform = "fruitday-app"
    }else{
        platform = "fruitday-web";
    }
    Tools.alert(location.href);
}


function authFruitday(succFn,failFn){
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
        if(succFn){
            succFn();
        }
    }, function(e) {
        Tools.showAlert(e.message || '服务器异常');
        if(failFn){
            failFn();
        }
    })
}
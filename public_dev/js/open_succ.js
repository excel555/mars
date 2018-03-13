var deviceId = Cookie.get("deviceId");
history.replaceState(null,null,'index.html');//修改history.back
(function() {
    common.checkLoginStatus(function() {
    })
})()
function get_order_status() {
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
        }else if(response.status == "pay_succ"){
            location.href='buy_succ.html?order_name='+response.order_name+'&deviceId='+deviceId;//只显示关闭按钮
        }else if(response.status == "free"){
            location.href='index.html';
        }
    }, function(e) {
        // Tools.showAlert(e.message || '服务器异常');
    });
}
if(deviceId && deviceId !=0) {
    self.setInterval("get_order_status()",2000)
}


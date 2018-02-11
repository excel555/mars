var deviceId = Cookie.get("deviceId");
var type = 'cmb';
(function() {
    
    var token = Tools._GET().token || Cookie.get('token');
    var user_id = Tools._GET().user_id || Cookie.get('user_id');
    
    if(token)
    {
        Cookie.set("token", token, null);
    }
    if(user_id)
    {
        Cookie.set("UserSN", user_id, null);
    }
    
    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

    body.find('.layout').height(layout_Height);

    common.checkLoginStatus(function() {
        get_order_status();
    })
})()

function get_order_status() {

    Ajax.custom({
        url: config.API_CMB_BALANCE,
        data:{
            device_id: deviceId,
            type:type
        },
        type: 'GET',
        showLoading: false
    }, function(response) {
        console.log(response);
        if(response.status == "stock"){
            $('#rby-loading1').show();
        }else if(response.status == "pay_succ"){
            location.href='fruitday_buy_succ.html?goBackEnable=0&order_name='+response.order_name;//只显示关闭按钮
        }else if(response.status == "free"){
            location.href='fruitday://Back';//跳转余额页
        }
    }, function(e) {
        // Tools.showAlert(e.message || '服务器异常');
    });
}

var int=self.setInterval("get_order_status()",2000)

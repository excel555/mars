
(function() {

    var body=$('body');

    var container = $('#amount-content');
    var deviceId = Cookie.get("deviceId");
    var type = Tools.returnUserAgent();


    function open_door() {
        var btn = $('.open_door');
        btn.addClass('dis_btn');
        btn.text('正在请求开门');
        Ajax.custom({
            url: config.API_FD_OPEN_DOOR,
            data:{
                device_id:deviceId
            },
            type: 'POST',
            showLoading: true
        }, function(response) {
            console.log(response);
            location.href='fruitday_open.html';
        }, function(e) {
            btn.text('立即开门');
            btn.removeClass('dis_btn');
            Tools.showToast(e.message || '服务器异常');

            if(e.message == "余额不足10元"){
                location.reload();
            }
        })
    }

    function get_fruitday_amount(){
        Ajax.custom({
            url: config.API_FD_AMOUNT,
            data:{
                device_id: deviceId,
                type:type
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            console.log(response);
            Ajax.render("#amount-content","amount-content-tmpl",response);
            var body=$('body'),
                layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

            body.find('.layout').height(layout_Height);
            if(parseInt(response.fruitday_money)<10){
                $("#title_amount").text("余额不足");
            }else if(type == "fruitday-app" && parseInt(response.fruitday_money) >= 10){
                // Tools.showToast('金额大于10元，开门中...');
                open_door();
            }
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    window.Get_fruitday_amount=get_fruitday_amount;//暴露
    //开门
    body.on('click','.open_door',function(){
        open_door();
    })


    common.checkLoginStatus(function() {
        get_fruitday_amount();
    })
})();
var i= 0
function get_fruitday_amount_out() {
    if(i<2)
    {
        var get_fruitday_amount=new Get_fruitday_amount();
        i ++
    }else{
        clearInterval(refresh);
    }
}
var refresh = setInterval("get_fruitday_amount_out()",3000);


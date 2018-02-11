var refer = Tools._GET().refer || '';
(function () {
    var body = $('body'),
        layout_Height = body.height() - (body.find('nav').height() + 1) - (body.find('.container>dl').height());

    body.find('.layout').height(layout_Height);

    var container = $('#amount-content');
    var deviceId = Cookie.get("deviceId");
    var type = Tools.returnUserAgent();

    function get_fruitday_amount() {

        Ajax.custom({
            url: config.API_FD_AMOUNT,
            data: {
                device_id: deviceId,
                type: type
            },
            type: 'GET',
            showLoading: true
        }, function (response) {
            console.log(response);
            Ajax.render("#recharge-content", "recharge-content-tmpl", response);
            if (parseInt(response.fruitday_money) < 10) {
                $("#title_amount").text("余额不足");
            }
            if(!Tools.isWeChatBrowser()){
                $("#alipay_btn").show();
            }
            if(Tools.isFruitdayAppBrowser()){
                $('.pay-way').hide();
            }
            var body = $('body'),
                layout_Height = body.height() - (body.find('nav').height() + 1) - (body.find('.container>dl').height());

            body.find('.layout').height(layout_Height);
        }, function (e) {
            container.html('<div class="empty-page-tip">' + e.message + '</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    body.on('click', '.recharge-item>li>span>input', function () {
        $('.amount').focus();
        $('.amount').val(' ');
    })
    body.on('click', '.recharge-item>li', function () {
        $(this).addClass('cur').siblings().removeClass('cur');
        $('.amount').val('')
        if($(this).attr('money')== '0'){
            $('.amount').attr('placeholder','');
        }else{
            $('.amount').attr('placeholder','其他金额');
        }
        $(".recharge-btn").attr("data-money", $(this).attr('money'));
    })

    body.on('click', '.pay-way>li', function () {
        $(this).addClass('cur').siblings().removeClass('cur');
        $(".recharge-btn").attr("data-pay", $(this).attr('pay_type'));
    })
    //充值
    body.on('click', '.recharge-btn', function () {
        var money = $(this).attr("data-money");
        if (money == "0") {
            money = $('.amount').val();
        }
        var pay_type = $(this).attr("data-pay");
        if(Tools.isWeChatBrowser()){
            pay_type = 9;
        }
        Ajax.custom({
            url: config.API_FD_RECHARGE,
            data: {
                money: money,
                type: pay_type,
                agent:type
            },
            type: 'POST',
            showLoading: true
        }, function (response) {
            console.log(response);
            if (type == "fruitday-app") {
                location.href = 'fruitday://CallCashierDesk?orderName=' + response.order_name;
            } else if (type == "fruitday-web") {
                Tools.alert(response.return_url);
                location.href = response.return_url;
            }else{
                Tools.alert(response.return_url);
                location.href = response.return_url;
            }
        }, function (e) {
            // container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    })

    common.checkLoginStatus(function () {
        get_fruitday_amount();
    })
})();

function checkoutCallback(orderName){
    if(refer == 'index'){
        location.href = 'fruitday_index.html?goBackEnable=0&refresh=1';
    }else{
        location.href = 'fruitday_amount.html?goBackEnable=0';
    }
}




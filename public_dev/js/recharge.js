(function(){
	var body=$('body');


	body.on('click', '.tab-menu>li', function(){ 
		var i = $(this).index();
        $(this).addClass('cur').siblings().removeClass('cur');
        $('.tab-box>div').eq(i).removeClass('hide').siblings().addClass('hide');
    });

    body.on('click', '.recharge-cash>li', function(){
        $('.recharge-btn').attr('data-money',$(this).attr('data-money'));
    	$(this).addClass('cur').siblings().removeClass('cur');
    });
    body.on('click', '.remove', function(){
        $("#card_num").val('');
        $('.remove').hide();
    });
    body.on('keyup', '#card_num', function(){
        if($("#card_num").val()){
            $('.remove').show();
        }else{
            $('.remove').hide();
        }
    });
    $('.remove').hide();


    $(".recharge-btn").on('click', function(e) {
        var money = $(this).attr('data-money');
        console.log(money);
        Ajax.custom({
            url: config.API_RECHARGE_MONEY,
            data:{
                refer:Tools.returnUserAgent(),
                money:money
            },
            type:"POST",
            showLoading: true
        }, function(response) {
            if(response.status == "success"){
                common.weixinPayOrder(response.param.order_name,Tools.returnUserAgent(), payCallback, errorCallback);
            }else{
                Tools.showToast(response.msg || '服务器异常');
            }
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        })
    });

    $(".recharge-btn-card").on('click', function(e) {
        var card_num = $("#card_num").val();
        if(!card_num){
            Tools.showToast("请输入充值码")
            return;
        }
        console.log(money);
        Ajax.custom({
            url: config.API_RECHARGE_CARD,
            data:{
                refer:Tools.returnUserAgent(),
                card_num:card_num
            },
            type:"POST",
            showLoading: true
        }, function(response) {
            if(response.status == "success"){
                // location.reload();
                Tools.showToast(response.msg);
                $('#money').text(Tools.rbyFormatCurrency(response.yue));
            }else{
                Tools.showToast(response.msg || '服务器异常');
            }
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        })
    });
    //支付成功回调
    function payCallback(data) {
        if(Tools.returnUserAgent() == 'alipay' || Tools.returnUserAgent() == 'gat'){
            $(".recharge").html(data.message);
        }else{
            location.reload();
        }
    }

    //取消支付回调
    function errorCallback() {

    }

    function getDetail() {
        Ajax.custom({
            url: config.API_MEMBER_ACCOUNT,
            showLoading: true
        }, function(response) {
            $('#money').text(Tools.rbyFormatCurrency(response.money));
            $('.recharge-btn').attr('data-money',response.config[0].money);
            Ajax.render("#recharge-cash-content", "recharge-cash-content-tmpl", response);
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        })
    }

    common.checkLoginStatus(function() { //入口
        getDetail();
    });
})();
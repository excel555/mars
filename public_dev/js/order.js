(function() {
    Tools.showLoading();
    var orderId = Tools._GET().orderId || '', //订单编号
        order_no = Tools._GET().order_no || '', //订单编号
        container = $('#rby-detail'),
        targetBtn = undefined,
        refund_money = $('#money'),
        reason = $('#reason'),
        reason_detail = $('#reason_detail'),
        tempData = undefined;

    if(orderId == '' && order_no != ''){
        orderId = order_no;
    }
    //获取订单
    function getDetail() {
        Ajax.detail({
            url: config.API_ORDER_DETAIL,
            data: {
                order_name: orderId
            },
            showLoading: true
        }, function(response) {
            tempData = response;
            container.show();
            // $("#money").val(tempData.money);
        }, function(data) {
            container.html('<div class="nodata">' + ((data && data.message) || '服务器异常。' ) + '</div>')
        });
    }

    common.checkLoginStatus(function() { //入口
        getDetail();
    });


    function goto_bind() {
        alert();
    }

    //退款
    container.on('click', '.btn-refund', function(e) {
        e.preventDefault();
        //判断是否绑定手机号
        var mobile = Cookie.get("mobile");
        if(!mobile){
            Tools.showAlert('为了更好的提供售后服务，请先绑定手机号',0,function () {
                location.href='register.html?redirect_url='+encodeURIComponent(location.href);
            });
            return;
        }
        if (!tempData) Tools.showAlert('数据错误，订单不存在');
        $('#login-form').show();
        $('#rby-cover-bg').show();
    });
    //发起支付
    container.on('click', '.btn-pay', function(e) {
        e.preventDefault();
        if (!tempData) Tools.showAlert('数据错误，订单不存在');
        if (Tools.isCmbApp()){
            location.href = config.API_CMB_BALANCE+'?order_name='+orderId;
        }
        common.weixinPayOrder(orderId,tempData.refer, payCallback, errorCallback);
    });

    //支付成功回调
    function payCallback(data) {
        if(tempData.refer == 'alipay' || tempData.refer == 'gat'){
            if(data.is_isv && data.is_isv == 1){
                common.alipayPayForIsv(data.message.trade_no,koubeiPaySuc,koubeiPayFail)
            }else{
                $("#rby-detail").html(data.message);
            }
        }else{
            location.reload();
        }
    }

    //取消支付回调
    function errorCallback() {

    }

    function koubeiPaySuc(data) {
        // alert("koubeiPaySuc"+JSON.stringify(data));
        location.reload();
    }
    function koubeiPayFail() {
        // alert("koubeiPayFail"+JSON.stringify(data));
    }

    // 关闭登陆注册框操作
    $('.account span.iconfont').click(function() {
        $('#login-form').hide();
        $('#rby-cover-bg').hide();
    })


    function refund(){
        Ajax.custom({
            url: config.API_ORDER_REFUND,
            data: {
                order_name: tempData.order_name,
                reason: reason.val(),
                reason_detail: reason_detail.val(),
                refund_money: refund_money.val(),
                photo: '--'
            },
            type: 'POST'
        }, function(response) {
            location.href = 'order.html?orderId='+tempData.order_name;
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        })
    }

    $(".account").on('click', '#refund_btn_send', function(e) {
        e.preventDefault();
        if (!tempData) Tools.showAlert('数据错误，订单不存在');
        if (reason.val().isEmpty()) {
            Tools.showToast('申请理由不能为空');
            return;
        }
        if (refund_money.val().isEmpty()) {
            Tools.showToast('退款金额不能为空');
            return;
        }
        if (parseFloat(refund_money.val()) > (parseFloat(tempData.money)+parseFloat(tempData.yue)) ) {
            Tools.showToast('退款金额不能大于支付金额');
            return;
        }
        if (parseFloat(refund_money.val()) <= parseFloat(0) || isNaN(parseFloat(refund_money.val()))) {
            Tools.showToast('退款金额不能小于0');
            return;
        }
        refund();

    });
})()

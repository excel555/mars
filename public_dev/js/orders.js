(function() {
    Tools.showLoading();
    var status = Tools._GET().status || 0, //订单状态，0未支付、1已支付，默认0
        container = $('.order'),
        rbyListDom = $('#rby-list'),
        tempData = undefined;
    var deviceId = Tools._GET().deviceId || 0;
    var auth_code= Tools._GET().auth_code || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    //获取订单列表
    function getList() {
        Ajax.paging({
            url: config.API_ORDERS,
            data: {
                status: status || 0,
                page: config.page,
                page_size: config.pageSize,
                auth_code: auth_code
            },
            showLoading: true
        }, function(response) {
            container.show();
            tempData = response;
            initTabWithStatus();

        }, function(e,data) {
            rbyListDom.html('<div class="nodata">' + ((data && data.message) || '服务器异常。' + e.message) + '</div>')
        });
    }

    //发起支付
    container.on('click', '.btn-pay', function(e) {
        e.preventDefault();
        var orderId = $(this).attr('order_id');
        var refer = $(this).attr('refer');
        if (!orderId) Tools.showAlert('数据错误，订单不存在');
        tmp_refer = $(this);
        if (Tools.isCmbApp()){
            location.href = config.API_CMB_BALANCE+'?order_name='+orderId;
        }
        common.weixinPayOrder(orderId,refer, payCallback, errorCallback);
    });


    //发起支付
    container.on('click', '.btn-pay', function(e) {
        e.preventDefault();
        if (!tempData) Tools.showAlert('数据错误，订单不存在');

    });

    //支付成功回调
    function payCallback(data) {
        if(tmp_refer.attr('refer') == 'alipay' || tmp_refer.attr('refer') == 'gat'){
            if(data.is_isv && data.is_isv == 1){
                common.alipayPayForIsv(data.message.trade_no,koubeiPaySuc,koubeiPayFail)
            }else{
                $("#rby-list").html(data.message);
            }
        }else{
            location.reload();
        }
    }

    function koubeiPaySuc(data) {
        // alert("koubeiPaySuc"+JSON.stringify(data));
        location.reload();
    }
    function koubeiPayFail() {
        // alert("koubeiPayFail"+JSON.stringify(data));
    }
    //取消支付回调
    function errorCallback() {

    }
    common.getList = getList;
    common.checkLoginStatus(function() {//入口
        getList();
    });

    //切换tab
    $('.order-tabs-item').click(function(e) {
        e.preventDefault();
        status = $(this).attr('data-v');
        $('.order-tabs-item').removeClass('active');
        $(this).addClass('active');
        config.page = 1;
        getList();

        if ('pushState' in history) {
            history.replaceState({
                status: status
            }, null, "?status=" + status);
        }
    });


    //依据序号获取接口返回数据的值
    function getData(idx) {
        if (!tempData) return tempData;

        for (var i in tempData) {
            console.log(idx);
            if (i == idx) return tempData[i];
        }

        return undefined;
    }

    //判断订单是否是临时订单
    function isTempOrder(order) {
        return order && order.status == '1';
    }

    //根据订单状态初始化tab状态
    function initTabWithStatus() {
        $('.order-tabs-item').each(function() {
            if ($(this).attr('data-v') == status) {
                $(this).addClass('active');
            } else {
                $(this).removeClass('active');
            }
        })
    }

})()

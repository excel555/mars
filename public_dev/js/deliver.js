(function() {
    var status = Tools._GET().status || 0, //订单状态，0未支付、1已支付，默认0
        deliver_no = Tools._GET().deliver_no || 0,
        container = $('.order'),
        rbyListDom = $('#rby-list'),
        tempData = undefined;

    //获取订单列表
    function getList() {
        Ajax.paging({
            url: config.API_DELIVER,
            data: {
                status: status || 0,
                deliver_no: deliver_no || 0,
                page: config.page,
                page_size: config.pageSize
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

    common.checkLoginStatus(function() {//入口
        getList();
    });


    window.addEventListener("popstate", function() {
        var currentState = history.state;
        if (currentState && currentState.status) {
            status = currentState.status;
            config.page = 1;
            getList();
        }
    });

    //取消订单
    container.on('click', '.btn-cancel', function(e) {
        e.preventDefault();

        var that = $(this),
            par = $(this).parent(),
            idx = $(this).attr('data-idx'),
            order = getData(idx),
            d = undefined;

        if (!order) Tools.showAlert('数据错误，订单不存在');

        if (isTempOrder(order)) {
            d = {
                tmpOrderId: order.orderId
            };
        } else {
            d = {
                orderId: order.orderId
            };
        }

        Tools.showConfirm('你确定要取消订单吗？', function() {
            Ajax.custom({
                url: config.HOST_API + '/member/cancelOrder',
                data: d,
                type: 'POST'
            }, function(response) {
                // getList();
                that.parent().parent().parent().remove();
                if (rbyListDom.children().length == 0) {
                    getList();
                }
            }, function(textStatus, data) {
                Tools.showToast(data.message || '服务器异常');
            })
        })
    })

    //去支付，临时订单跳转到结算页，未支付订单打开支付
    container.on('click', '.btn-pay', function(e) {
        e.preventDefault();

        var par = $(this).parent(),
            idx = $(this).attr('data-idx'),
            order = getData(idx);

        if (!order) Tools.showAlert('数据错误，订单不存在');

        if (isTempOrder(order)) {
            location.href = 'checkout.html?targetId=' + order.groupOrderId;
            return;
        }
        $('#login-form').show();
        //common.weixinPayOrder(order.id);
    })

    // 关闭登陆注册框操作
    $('.account span.iconfont').click(function() {
        $('#register-form').hide();
        $('#login-form').hide();
        $('#rby-cover-bg').hide();
    })

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

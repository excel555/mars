(function() {
    Tools.showLoading();
    var status = Tools._GET().status || 0, //订单状态，0未支付、1已支付，默认0
        container = $('.order'),
        rbyListDom = $('#rby-list'),
        tempData = undefined;
    var deviceId = Tools._GET().deviceId || 0;
    var history_status= Tools._GET().history_status || 0;
    var auth_code= Tools._GET().auth_code || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    //获取订单列表
    function getList() {
        initTabWithStatus();
        $("#history_coupon").attr('href',"coupons.html?history_status=1&status="+status+"&deviceId="+deviceId);
        var url = config.API_CARD_LIST;
        if(status == 99 ){
            url = config.HOST_API + '/account/thirdpartycoupon_list';
        }
        console.log(url);
        console.log(status);
        console.log(history_status);
        Ajax.paging({
            url: url,
            data: {
                status: history_status == 1 ? -1 : 1,
                page: config.page,
                page_size: config.pageSize,
                auth_code: auth_code,
                deviceId:deviceId
            },
            showLoading: true
        }, function(response) {
            container.show();
            tempData = response;
            if(tempData.length == 0){
                $('.nodata').html('<span class="empty-coupon">您还没有优惠券哦</span>')
            }
            if(history_status == 1){
                $('.history_coupon').hide();
            }else{
                $('.history_coupon').show();
            }
        }, function(e,data) {
            rbyListDom.html('<div class="nodata">' + ((data && data.message) || '服务器异常。' + e.message) + '</div>')
        });
    }

    container.on('click', '.user_btn', function(e) {
        e.preventDefault();
        var active = $(this).attr('active_id');
        if(active){
            location.href='cityshop-coupon-detail.html?id='+ $(this).attr('id');
        }else{
            location.href='index.html'
        }
    });

    function car_detail(active) {
        Ajax.custom({
            url: config.HOST_API+'/device/thirdparty_detail',
            data:{
                id: active
            },
            type: 'GET',
            showLoading: false
        }, function(response) {
            console.log(response);
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        });
    }

    container.on('click', '.pull', function(e) {
        if($(this).find('small').hasClass('pull_down')){
            //展开
            console.log('zhankai')
            $(this).find('small').removeClass('pull_down');
            $(this).find('small').addClass('pull_up');
            var dl = $(this).parent().find('dl').find('dl').css('display','flex');
            console.log(dl);
        }else{
            console.log('shou')
            $(this).find('small').removeClass('pull_up');
            $(this).find('small').addClass('pull_down');
            $(this).parent().find('dl').find('dl').css('display','none');
            $(this).parent().find('dl').find('dl').first().css('display','flex');;
        }
    });

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
        history_status = 0;
        $('.nextpage').remove();
        getList();

        if ('pushState' in history) {
            history.replaceState({
                status: status
            }, null, "?status=" + status+"&deviceId="+deviceId);
        }
    });

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

(function() {
    var id = Tools._GET().id || 0;

    /** 城市超市优惠券 开始 */
    $('.close-img').click(function () {
        $('.cityshop-coupon').hide();
    });
    $(".cityshop-coupon-img").click(function () {
        location.href = 'cityshop-coupon.html?id='+$(this).attr('active_id');
    });

    function get_cityshop_coupon(phone) {
        Ajax.custom({
            url: config.HOST_API + '/device/thirdparty_card',
            data:{
                thirdpartycard_id:id,
                mobile:phone
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            $('.coupon-send').hide();
            $('#moblie-text').text(phone);
            $('.coupon-name').text(response.coupon_name + '('+response.remark+')');
            $('.coupon-date').text('有效期:'+response.coupon_begin_date + '-' +response.coupon_end_date);
            $('.fuli-coupon-price').html(Tools.rbyFormatCurrency(response.coupon_money)+'<small class="coupon-small-yuan">元</small>');
            $('.coupon-succ').show();
        },function (e) {
            Tools.showToast(e.message || '服务器异常，稍后重试')
        });
    }
    /** 城市超市优惠券 结束 */
    function init() {
        console.log('cityshop coupon')
    }
    $(".mobile-modify").click(function () {
        $('.coupon-succ').hide();
        $('.coupon-send').show();
    });

    $(".coupon-btn").click(function () {
        var phone = $('#mobile');
        if (phone.val().isEmpty()) {
            Tools.showToast('手机号不能为空');
            return;
        }
        if(!phone.val().isPhone()){
            Tools.showToast('手机号不正确');
            return;
        }
        get_cityshop_coupon(phone.val());
    });
    common.checkLoginStatus(function() {
        init();
    });
})();







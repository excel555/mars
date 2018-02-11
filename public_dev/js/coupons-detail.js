(function() {
    Tools.showLoading();
    var id = Tools._GET().id;

    function car_detail(active) {
        Ajax.custom({
            url: config.HOST_API+'/device/thirdparty_detail',
            data:{
                id: active
            },
            type: 'GET',
            showLoading: false
        }, function(response) {
            // response.qr_img = config.HOST_API+'/device/barcode?card_number='+response.card_number;
            console.log(response);
            Ajax.render("#rby-coupon-detail", "rby-coupon-detail-tmpl", response);
        }, function(e) {
            Tools.showToast(e.message || '服务器异常');
        });
    }

    common.checkLoginStatus(function() {//入口
        car_detail(id);
    });
})()

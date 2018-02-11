(function() {
    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

    body.find('.layout').height(layout_Height);
    var container = $('#amount-content');
    var order_name = Tools._GET().order_name || 0;
    var device_id = Tools._GET().device_id || 0;
    var type = Tools.returnUserAgent();

    function get_fruitday_order_amount(){
        Ajax.custom({
            url: config.API_FD_ORDER_AMOUNT,
            data:{
                order_name: order_name,
                type:type,
                device_id:device_id
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            console.log(response);
            $('#pay_money').text(response['money']);
            $('#amount_money').text(response['fruitday_money']);
            if(parseInt(response['fruitday_money']) < 0){
                $('.text-center').show();
                $('.order-btn-view').hide();
            }else{
                $('.order-btn-view').show();
            }

            if(order_name == 0 || order_name == ''){
                $(".order-href").hide();
            }else{
                $(".order-href").attr('href','order.html?orderId='+order_name);
            }

            Ajax.render("#buy-container", "buy-content-tmpl", response);

            $('.click_icon_img').attr('src',response.foot_img.img_url);
            $('.click_icon_img').attr('click_type',response.foot_img.click_type);
            $('.click_icon_img').attr('click_url',response.foot_img.click_url);

        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    // history.replaceState(null,null,'fruitday_index.html');//修改history.back
    Tools.alert(location.href);
    common.checkLoginStatus(function() {
        get_fruitday_order_amount();
    })
})()



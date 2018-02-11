var deviceId = Cookie.get("deviceId");
var type = Tools.returnUserAgent();
// history.replaceState(null,null,'fruitday_index.html');//修改history.back
(function() {
    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

    // body.find('.layout').height(layout_Height);


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
            // $("#open_yue").text(response.fruitday_money);
            get_ad(response);
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    function get_ad(info) {
        Ajax.custom({
            url: config.API_NEW_INDEX_AD,
            data:{
                device_id: deviceId,
                is_index:0
            },
            showLoading: false
        }, function(response) {
            // response.active_icon = response.banner;
            response['fruitday'] = info;
            response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            if(response['equipment_type'] == 'rfid-1' || response['equipment_type'] == 'rfid-2' || response['equipment_type'] == 'vision-1' || response['equipment_type'] == 'vision-2'){
                response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            }else  if(response['equipment_type'] == 'scan-1' || response['equipment_type'] == 'scan-2'){
                response['tip'] = '请将商品条形码对准屏幕下方扫描头';
            }
            console.log(response);
            Ajax.render("#index-content", "index-content-tmpl", response);

            if( $('.swiper-banner').length ){
                var swiper = new Swiper('.swiper-banner', {
                    pagination: '.swiper-pagination',
                    paginationClickable: true,
                    loop: true,
                    autoplay: 5000,
                    autoplayDisableOnInteraction: false
                });
            }
        }, function(e) {
            e.message = e.message ? e.message : '服务器异常';
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    common.checkLoginStatus(function() {
        // get_order_status();
        get_fruitday_amount();
    })
})()

function get_order_status() {

    Ajax.custom({
        url: config.API_FD_BOX_STATUS,
        data:{
            device_id: deviceId,
            type:type
        },
        type: 'GET',
        showLoading: false
    }, function(response) {
        console.log(response);
        if(response.status == "stock"){
            $('#rby-loading1').show();
        }else if(response.status == "pay_succ"){
            location.href='fruitday_buy_succ.html?goBackEnable=0&order_name='+response.order_name;//只显示关闭按钮
        }else if(response.status == "free"){
            location.href='fruitday://Back';//跳转余额页
        }
    }, function(e) {
        // Tools.showAlert(e.message || '服务器异常');
    });
}

var int=self.setInterval("get_order_status()",2000)

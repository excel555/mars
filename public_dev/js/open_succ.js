var deviceId = Tools._GET().deviceId || 0;
var from = Tools._GET().from || '';
var view_pop = 0;

(function() {
    var container = $('#index-content');
    function pushHistory() {
        var state = {
            title: "",
            url: "#"
        };
        window.history.pushState(state, "", "#");
    }
    pushHistory();
    window.addEventListener("popstate", function(e) {
        pushHistory();
        var ua = navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i)=="micromessenger") {
            WeixinJSBridge.call('closeWindow');
        } else if(ua.indexOf("alipay")!=-1){
            AlipayJSBridge.call('closeWebview');
        } else{
            window.close();
        }
    }, false);

    history.replaceState(null,null,'index.html');//修改history.back
    /**
     * 获取广告信息
     */
    function get_ad() {
        Ajax.custom({
            url: config.API_NEW_INDEX_AD,
            data:{
                device_id: deviceId,
                is_index:0
            },
            showLoading: false
        }, function(response) {
            if(response.user_rank ==1){
                response.zong = 8;
            }else if(response.user_rank ==2){
                response.zong = 12;
            }else if(response.user_rank ==3){
                response.zong = 20;
            }else{
                response.zong = (response.moli)/100;
            }
            response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            if(response['equipment_type'] == 'rfid-1' || response['equipment_type'] == 'rfid-2' || response['equipment_type'] == 'vision-1' || response['equipment_type'] == 'vision-2'){
                response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            }else  if(response['equipment_type'] == 'scan-1' || response['equipment_type'] == 'scan-2'){
                response['tip'] = '请将商品条形码对准屏幕下方扫描头';
            }
            console.log(response);
            Ajax.render("#index-content", "index-content-tmpl", response);
            if(from == ''){
                $('.info').hide();
            }
            if( $('.swiper-banner').length ){
                var swiper = new Swiper('.swiper-banner', {
                    pagination: '.swiper-pagination',
                    paginationClickable: true,
                    loop: true,
                    autoplay: 5000,
                    autoplayDisableOnInteraction: false
                });
            }
            var  p_img = Cookie.get("pop_img");
            if(response.pop_img != undefined && response.pop_img != '' && p_img != response.pop_img){
                $(".banner_img").attr('src',response.pop_img);
                Cookie.set("pop_img", response.pop_img, 30);//保留30天
                $("#popup-window").removeClass('hide');
                $('.popWin-close').hide();
                view_pop = 1;
                setTimeout('view_pop_img()',2000);
            }

            // Tools.showToast('开门成功',2000);
            customToast();
            $("#level_num").text("M"+response.user_rank);

        }, function(e) {
            e.message = e.message ? e.message : '服务器异常';
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    function customToast() {
        $('#rby-toast2').show();
        setTimeout(function() {
            $('#rby-toast2').hide();
        }, 2000);

    }
    //弹窗关闭
    $('.popWin-hide, .popWin-close').click(function(e){
        e.preventDefault();
        console.log(e)
        // $(".popup-window").removeClass('bounceInDown').addClass('fadeOutDownBig');
        $(".popup-window").addClass('hide');
    });

    //优惠券展开
    $('body').on('click', '.pull', function(e) {
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

    //click banner /icon
    $('body').on('click', '.click_icon_img', function(){
        var click_type = $(this).attr('click_type');
        var click_url = $(this).attr('click_url');
        if(click_type == 1 && click_url){
            // location.href = click_url;
        }else  if(click_type == 2 && click_url){
            $(".banner_img").attr('src',click_url);
            $("#popup-window").removeClass('hide');
        }
    });
    $('body').on('click', '#coupon_btn', function(e){
        e.preventDefault();
        console.log('1212');
        var offset = $("#coupon").offset();
        console.log(offset)
        $(window).scrollTop(offset.top);
    });
    common.checkLoginStatus(function() {
        get_ad();
        get_cityshop_coupon(deviceId);
    })

    /*城市优惠券 start*/
    $('.close-img').click(function () {
        $('.cityshop-coupon').hide();
    });
    $(".cityshop-coupon-img").click(function () {
        $('.cityshop-coupon').hide();
        Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", {"input":1});
        $("#cityshop-panel").show();
    });


    function get_cityshop_coupon(deviceId) {
        Ajax.custom({
            url: config.HOST_API + '/device/exist_thirdparty',
            data:{
                device_id:deviceId
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            if(response.thirdparty_cityshop && response.thirdparty_cityshop.active_id && response.thirdparty_cityshop.is_get != 1){
                $('.cityshop-coupon-img').attr('active_id',response.thirdparty_cityshop.active_id);
                $('.cityshop-coupon').show();
                $('.cityshop_img').show();
            }
        },function (e) {
            Tools.showAlert(e.message || '服务器异常，稍后重试')
        });
    }
    $('body').on('click', '.mobile-modify', function(e){
        var mobile = $(this).attr('data-mobile');
        Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", {"input":1,"mobile":mobile});
        $("#cityshop-panel").show();
    });
    $('body').on('click', '.cityshop-send-btn', function(e){
        fuli_info();
    });

    function fuli_info() {
        var phone = $('#mobile');
        if (phone.val().isEmpty()) {
            Tools.showToast('手机号不能为空');
            return;
        }
        if(!phone.val().isPhone()){
            Tools.showToast('手机号不正确');
            return;
        }

        var active_id = $('.cityshop-coupon-img').attr('active_id');
        Ajax.custom({
            url: config.HOST_API + '/device/thirdparty_card',
            data:{
                thirdpartycard_id:active_id,
                mobile:phone.val()
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            response.mobile = phone.val();
            Ajax.render("#cityshop-coupon-content", "cityshop-coupon-content-tmpl", response);
            $("#cityshop-panel").show();
        },function (response) {
            console.log(response);
            Tools.showToast(response.message || '服务器异常');
        });
    }

    $('body').on('click', '.cityshop-close', function(e){
        $("#cityshop-panel").hide();
    });
    /*城市优惠券 start*/
})();
function showPaying() {
    $('#rby-loading1').show();
}
function view_pop_img() {
    console.log(view_pop);
    if(view_pop == 1){
        $('.popWin-close').show();
    }
}

var view_count = 0;
var i=1,str='.';
function get_order_status() {
    Ajax.custom({
        url: config.API_FD_BOX_STATUS,
        data:{
            device_id: deviceId
        },
        type: 'GET',
        showLoading: false
    }, function(response) {
        console.log(response);
        if(response.status == "stock"){
            view_count++;
            i++;
            console.log(view_count);
            if(view_count == 1){
                showPaying();
            }
        }else if(response.status == "pay_succ"){
            location.href='buy_succ.html?order_name='+response.order_name+'&deviceId='+deviceId;//只显示关闭按钮
        }else if(response.status == "wait_pay"){
            location.href = config.API_CMB_BALANCE+'?order_name='+response.order_name+'&deviceId='+deviceId;//招商银行手机支付
        }else if(response.status == "free"){
            location.href='index.html?deviceId='+deviceId;
        }
    }, function(e) {
        // Tools.showAlert(e.message || '服务器异常');
        // location.href='index.html?deviceId='+deviceId;
    });
}

//定时获取用户购买的开关门状态
if(from != '' && deviceId != 0){
    var int=self.setInterval("get_order_status()",2000);
}




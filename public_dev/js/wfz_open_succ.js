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

    history.replaceState(null,null,'wfz_error.html');
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

            response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            if(response['equipment_type'] == 'rfid-1' || response['equipment_type'] == 'rfid-2' || response['equipment_type'] == 'vision-1' || response['equipment_type'] == 'vision-2'){
                response['tip'] = '请取走您需要购买的商品，关门后将自动扣款';
            }else  if(response['equipment_type'] == 'scan-1' || response['equipment_type'] == 'scan-2'){
                response['tip'] = '请将商品条形码对准屏幕下方扫描头';
            }
            console.log(response);
            // response.active_icon = response.banner;
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
            $("#level_num").text("M"+response.user_rank);

        }, function(e) {
            e.message = e.message ? e.message : '服务器异常';
            Tools.showAlert(e.message || '服务器异常');
        })
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

    $('body').on('click', '.fuli-close', function(e){
        $("#fuli-panel").hide();
    });

    /**
     * 获取口碑优惠券
     */
    function getCouponList() {
        Ajax.custom({
            url: config.HOST_API + '/koubei/koubei_shop_discount_query',
            data:{
                device_id:deviceId
            },
            showLoading: true
        }, function(response) {
            if(response.camp_num > 0){
                Ajax.render("#card-content", "card-content-tmpl", response);
                $("#fuli-panel").show();
            }
        },function (e) {
            Tools.showToast(e.message || '服务器异常');
        });
    }
    $('body').on('click', '.coupon-btn', function(e){
        var item_id = $(this).attr('data-biz_id');
        var discount_type = $(this).attr('data-biz_type');
        benefitCoupon(item_id,discount_type,$(this));
    });
    /**
     * 领取优惠券
     */
    function benefitCoupon(item_id,discount_type,that) {
        Ajax.custom({
            url: config.HOST_API + '/koubei/koubei_benefit_send',
            data:{
                item_id:item_id,
                discount_type:discount_type,
                device_id:deviceId
            },
            type:"POST",
            showLoading: true
        }, function(response) {
            if(response.code == 10000){
                that.addClass('coupon-btn-gray');
                Tools.showToast('领取成功');
            }else{
                Tools.showToast(response.sub_msg);
            }
        },function (e) {
            Tools.showToast(e.message || '服务器异常');
        });
    }
    common.checkLoginStatus(function() {
        getCouponList();
        get_ad();
    })

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
            location.href='wfz_buy_succ.html?order_name='+response.order_name+'&deviceId='+deviceId;//只显示关闭按钮
        }else if(response.status == "free"){
            AlipayJSBridge.call('closeWebview');
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




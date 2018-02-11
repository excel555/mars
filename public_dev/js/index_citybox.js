(function() {
    var deviceId = Tools._GET().deviceId || 0;
    if(deviceId)
    {
        Cookie.set("deviceId", deviceId, null);
    }
    var first_name = "";
	//获取当前盒子以及其他盒子地址
    function get_box_address(){
        Ajax.custom({
            url: config.API_BUY_ADDRESS,
            data:{
                type:Tools.returnUserAgent()
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            history.replaceState(null,null,'index.html');//修改history.back
            if (response.is_open == 1) {
                // $('#first_name').text(response.first['name']);
                first_name = response.first['name'];
                Ajax.render("#address-content", "address-content-tmpl", response);
                getDetail(response.first['equipment_id']);
            }else {
                $('.container').addClass('hide');
                $('.container-empty').removeClass('hide');
            }
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    $('body').on('click', '.scan-con,.fuli-btn', function() {
        console.log('open...');
    	location.href = 'open.html'
    });

    $('body').on('click', '.fuli', function() {
        console.log('fuli...');
       var card_id = $(this).attr('data-disabled');
       if(card_id != 0){
           fuli_info(card_id);
       }else{
           Tools.showToast("活动未开始");
       }
    });
    function fuli_info(card_id) {
        Ajax.custom({
            url: config.API_CARD_FU_LI,
            data:{
                card_id:card_id
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            Ajax.render("#card-content", "card-content-tmpl", response);
            $("#fuli-panel").show();
        },function (response) {
            console.log(response);
            Ajax.render("#card-content", "card-content-tmpl", response);
            $("#fuli-panel").show();
        });

    }


    //切换魔盒
    $('body').on('click', '#first_name', function(){
        $('.cover-bg').slideDown(500);
        $('.address-prop').slideDown(500);
    }).on('click', '.address-prop .cancel, .cover-bg', function(){
        $('.cover-bg').slideUp(500);
        $('.address-prop').slideUp(500);
    });


    $('body').on('click', '.scroll ul li', function(){
        console.log(this);
		$(this).find('.sel').addClass('active');
        $(this).siblings().find('.sel').removeClass('active');
        var eq_id = $(this).attr('id');
        var name = $(this).attr('name');
        first_name = name;
        getDetail(eq_id);
        $('.cover-bg').slideUp(500);
        $('.address-prop').slideUp(500);
    })

    function getDetail(id){
        Ajax.custom({
            url: config.API_NEW_INDEX_AD,
            data:{
                type:Tools.returnUserAgent(),
                device_id:id,
                is_index:1
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            console.log(response);
            response['frist_name'] = first_name;
            // console.log(response)
            response.deviceId = id;
            // response.active_icon = response.banner;
            Ajax.render("#index-content", "index-content-tmpl", response);
            if(response.unpay_order == 1){
                var opt = {
                    showTitle:true,
                    message: '有未支付订单，需先完成支付，才能继续开门购物',
                    titleText:"您有未支付订单",
                    cancelText:"取消",
                    okText:"去支付",
                    panelImg:'img/Openning_list@2x.png',
                    yesCallback:function () {
                        location.href='orders.html?status=0';
                    },
                    noCallback:function () {
                        // location.href = 'index.html?deviceId='+deviceId;
                    }
                }
                Tools.showAlert(opt);
            }
            if( $('.swiper-banner').length ){
                new Swiper('.swiper-banner', {
                    pagination: '.swiper-pagination',
                    paginationClickable: true,
                    loop: true,
                    autoplay: 5000,
                    autoplayDisableOnInteraction: false,
                    observer:true,//修改swiper自己或子元素时，自动初始化swiper
                    observeParents:false,//修改swiper的g父元素时，自动初始化swiper
                    onSlideChangeEnd: function(swiper){
                        swiper.update();
                    }

                });
            }

        },function (e) {

        });
        get_cityshop_coupon(id);//检查是否有第三方优惠券
    }

    /** 城市超市优惠券 开始 */
    $('.close-img').click(function () {
        $('.cityshop-coupon').hide();
    });
    $(".cityshop-coupon-img").click(function () {
        location.href = 'cityshop-coupon.html?id='+$(this).attr('active_id');
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
            }
        },function (e) {
            Tools.showAlert(e.message || '服务器异常，稍后重试')
        });
    }
    /** 城市超市优惠券 结束 */

    //click banner /icon
    $('body').on('click', '.click_icon_img', function(e){
        e.preventDefault();
        console.log(e)
        var click_type = $(this).attr('click_type');
        var click_url = $(this).attr('click_url');
        if(click_type == 1 && click_url){
            location.href = click_url;
        }else  if(click_type == 2 && click_url){
            $(".banner_img").attr('src',click_url);
            $("#popup-window").removeClass('hide');
        }
    });
    // var scoller,last_socller = 0;
    // $(window).scroll(function (e) {
    //     e.preventDefault();
    //     $('.scan-con').hide();
    //     scoller = $(window).scrollTop();
    //     console.log('scroll'+scoller);
    // });

    // setInterval(function () {
    //     if(scoller != last_socller)
    //     {
    //         scoller = last_socller
    //     }else{
    //         $('.scan-con').show();
    //     }
    // },300);



    //弹窗关闭
    $('.popWin-hide, .popWin-close').click(function(e){
        e.preventDefault();
        console.log(e)
        // $(".popup-window").removeClass('bounceInDown').addClass('fadeOutDownBig');
        $(".popup-window").addClass('hide');
    });

    $('body').on('click', '.fuli-close', function(e){
        $("#fuli-panel").hide();
    });

    common.checkLoginStatus(function() {
        get_box_address();
    });
})();







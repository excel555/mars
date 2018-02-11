(function() {
    Tools.showLoading();
    var container = $('#index-content');
    var goBackEnable = Tools._GET().goBackEnable || 1;
    Tools.alert(goBackEnable);
    if(goBackEnable == 1){
        location.href = 'fruitday_index.html?goBackEnable=0';
        return;
    }
    Tools.alert(location.href);

    function get_fruitday_amount(){
        Ajax.custom({
            url: config.API_FD_AMOUNT,
            data:{
                device_id: 0,
                type:'fruitday-app'
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            // Ajax.render("#index-content","index-content-tmpl",response);
            get_box_address(response);
        }, function(e) {
            // container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    window.Get_fruitday_amount_index = get_fruitday_amount;//暴露
    container.on('click', '.empty-page-tip', function() {
        location.reload();
    });
    get_fruitday_amount();
    var first_name = "";
    //获取当前盒子以及其他盒子地址
    function get_box_address(info){
        Ajax.custom({
            url: config.API_BUY_ADDRESS,
            data:{
                type:Tools.returnUserAgent()
            },
            type: 'GET',
            showLoading: true
        }, function(response) {
            if (response.is_open == 1) {
                // $('#first_name').text(response.first['name']);
                first_name = response.first['name'];
                Ajax.render("#address-content", "address-content-tmpl", response);
                getDetail(response.first['equipment_id'],info);
            }else {
                $('.container').addClass('hide');
                $('.container-empty1').removeClass('hide');
                $('.fruitday_yue').text(info.fruitday_money);
                if(parseInt(info.fruitday_money) >= 10){
                    $('.saoma').show();
                }else{
                    $('.chongzhi').show();
                }
            }
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    function getDetail(id,info){
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
            if(info){
                response.fruitday = info;
            }else{
                response.fruitday = {
                    'fruitday_money':0
                };
            }
            console.log(response);

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

})();

var i= 0;
function get_fruitday_amount_out1() {
    if(i<2)
    {
        var get_fruitday_amount_index=new Get_fruitday_amount_index();
        i ++
    }else{
        clearInterval(refresh);
    }
}
var refresh_enable = Tools._GET().refresh || 0;
var refresh;
if(refresh_enable){
    refresh = setInterval("get_fruitday_amount_out1()",3000);
}
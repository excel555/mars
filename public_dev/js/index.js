var deviceId = Tools._GET().deviceId || 0;
var from = Tools._GET().from || '';
var view_pop = 0;
(function() {
    var container = $('#index-content');

    var body=$('body'),
        layout_Height=body.height()-(body.find('nav').height()+1)-(body.find('.container>dl').height());

    body.find('.layout').height(layout_Height);

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
    function pushHistory() {
        var state = {
            title: "",
            url: "#"
        };
        window.history.pushState(state, "", "#");
    }



    /**
     * 获取广告信息
     */
    function get_ad() {
        Ajax.custom({
            url: config.API_INDEX_AD,
            data:{
                device_id: deviceId,
                type:Tools.returnUserAgent()
            },
            showLoading: true
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

            $("#level_num").text("M"+response.user_rank);
        }, function(e) {
            e.message = e.message ? e.message : '服务器异常';
            //container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    container.on('click', '.empty-page-tip', function() {
        get_ad();
    });



    common.checkLoginStatus(function() {
        // container.html('<div class="empty-page-tip">正在加载中...</div>');
        get_ad();
    })

    $(document).ready(function(){
        //弹窗关闭
        $('.popWin-hide, .popWin-close').click(function(){
            $(".popup-window").removeClass('bounceInDown').addClass('fadeOutDownBig').slideUp('300');
        });

    });
})()
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
        history.replaceState(null,null,'index.html?deviceId='+deviceId);//修改history.back
        if(response.status == "stock"){
            view_count++;
            i++;
            console.log(view_count);
            if(view_count <= 1){
                Tools.showToast('正在结算，请稍后...');
                // $('#rby-loading1').show();
                $("#open_door_text").text('您已关门成功!');
                $("#open_door_p").html('系统正在结算.');

            }else{
                str = str + '.';
                $("#open_door_p").html('系统正在结算'+str);
                if(i==4){
                    str = '.';
                    i = 1;
                }
            }
        }else if(response.status == "pay_succ"){
            location.href='buy_succ.html?order_name='+response.order_name+'&deviceId='+deviceId;//只显示关闭按钮
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




(function() {
    Tools.showLoading();
    var deviceId = Tools._GET().deviceId || 0;
    if(!deviceId){
        alert('请退出重新扫码进入');
        return;
    }

    var container = $('#rby-detail');

    function getDetail() {
        Ajax.custom({
            url: config.API_MEMBER_INFO,
            showLoading: true
        }, function(response) {
            if (response.avatar) {
                $('#me-avatar').attr('src', response.avatar);
            }
            if(Tools.isAlipayBrowser() && response.s_admin_id != "0"){
                $('#box_admin').show();
                $('.address').attr('href','deliver.html?admin=1&deviceId='+deviceId);
                $('.kf').attr('href','stock.html?admin=1&deviceId='+deviceId);
            }
            var mobile = "";
            if(response.mobile){
                $('#bind').hide();
                mobile = response.mobile;
            }
            var name = "";
            if(response.user_name){
                name = response.user_name
                if(mobile) {
                    name = name + " - " + mobile;
                }
            }else if(mobile){
                name = mobile;
            }
            $('#me-name').text('管理员：'+name || '--');
            afterLoad();
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }


    //获取数据后，初始化界面，导航菜单
    function afterLoad() {
        var w = Math.min(Tools.getDocument().width, 640),
            h = (w - 15) / 2, //15为菜单的左右分隔间距
            padding = (h - 79) / 2, //计算导航的上下间距，79为菜单中内容的高度
            ratio = 750 / 500; //头部背景图的长宽比
        padding2 = (w / ratio - 115) / 2; //计算头部背景图的上下间距,115为内容的高
        $('.me-header').css('padding', (padding2 + 10) + 'px 0px ' + (padding2 - 10) + 'px');
        $('.me-nav-item').css('padding', padding + 'px 0px');
        $('.bottom-down').show();
        container.show();
    }
    common.checkLoginStatus(function() { //入口
        getDetail();
    });
})()

(function() {
    Tools.showLoading();
    var container = $('#rby-detail');
    var auth_code= Tools._GET().auth_code || 0;

    function getDetail() {
        Ajax.custom({
            url: config.API_MEMBER_INFO,
            data: {
                auth_code: auth_code
            },
            showLoading: true
        }, function(response) {
            if (response.avatar) {
                $('#me-avatar').attr('src', response.avatar);
            }

            var mobile = "";
            if(response.mobile){
                if(Tools.isAlipayBrowser() && response.s_admin_id != "0"){
                    $('#box_admin1').show();
                    if(response.view_old_deliver == 1){
                        $('#box_admin').show();
                    }else{
                        $('#box_admin').show();
                        $('.open_door').hide();
                    }
                }

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
            $('#me-name').text(name || '--');
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
        $('.me-header').css('padding', (padding2 + 0) + 'px 0px ' + (padding2 - 0) + 'px');
        $('.me-nav-item').css('padding', padding + 'px 0px');
        $('.bottom-down').show();
        container.show();
    }
    common.checkLoginStatus(function() { //入口
        getDetail();
    });
})()

(function() {

    function getSignUrl() {
        Ajax.custom({
            url: config.API_SIGN,
            data: {
                refer: Tools.returnUserAgent(),
                device_id: Cookie.get("deviceId")
            },
            showLoading: true
        }, function(response) {
            location.href=response;
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    }
    getSignUrl();
    var container = $('#rby-detail');
    container.html('<div class="empty-page-tip">服务器太累了，点击屏幕刷新试试</div>');
})()

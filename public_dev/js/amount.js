(function() {
    var container = $('#index-content');
    var deviceId = Tools._GET().deviceId || 0;

    common.checkLoginStatus(function() {
        container.html('<div class="empty-page-tip">请求开门中，请稍后...</div>');
    })
})()



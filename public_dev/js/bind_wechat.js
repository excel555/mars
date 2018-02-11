(function() {

    if (!Tools.isWeChatBrowser()) {
        Tools.showAlert('请在微信里面打开');
        return;
    }
    common.checkLoginStatus(function() {
        Ajax.custom({
            url: config.API_MEMBER_INFO,
            showLoading: true
        }, function(response) {
            $('#rby-detail').show();
            $('#open_id').text(response.open_id)
        }, function(e) {
            Tools.showAlert(e.message || '服务器异常');
        })
    })
})()



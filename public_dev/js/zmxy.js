(function() {
    var container = $('#index-content');

    function open(){
        Ajax.custom({
            url: config.API_ZMXY_AUTH,
            type: 'GET',
            showLoading: true
        }, function(response) {
            location.href = response;
        }, function(e) {
            container.html('<div class="empty-page-tip">'+e.message+'</div>');
            Tools.showAlert(e.message || '服务器异常');
        })
    }

    open();
})()



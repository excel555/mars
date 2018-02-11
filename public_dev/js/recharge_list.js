(function(){
    var container = $('.main'),
        rbyListDom = $('#rby-list'),
        tempData = undefined;
    //获取订单列表
    function getList() {
        Ajax.paging({
            url: config.API_RECHARGE_LIST,
            data:{
                page: config.page,
                page_size: config.pageSize,
            },
            showLoading: true
        }, function(response) {
            container.show();
            tempData = response;
        }, function(e,data) {
            rbyListDom.html('<div class="nodata">' + ((data && data.message) || '服务器异常。' + e.message) + '</div>')
        });
    }
    common.getList = getList;
    common.checkLoginStatus(function() {//入口
        getList();
    });
})();
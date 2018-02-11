(function() {
    var modou = Tools._GET().modou || 0;
    function init() {
        $(".modou").html(modou+'<span class="small-dou">豆</span>');
    }
    common.checkLoginStatus(function() { //入口
        init();
    });
})()

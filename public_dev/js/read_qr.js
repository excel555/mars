(function() {
    function rec_scan_succ(qrCode){
        var e = qrCode.split("d=");
        if(e.length == 2)
        {
            Tools.showAlert(e[1]);
        } else {
            Tools.showAlert('无法二维码内容');
        }
    }
    if (!Tools.isAlipayBrowser()) {
        Tools.showAlert('请在支付宝里面打开');
        $("#read_qr").hide();
    }
    function qr(){
       if (Tools.isAlipayBrowser()) {
            common.aliScan(rec_scan_succ);
        }else {
           Tools.showAlert('请在支付宝里面打开');
       }
    }
    var container = $('#index-content');

    container.on('click', '#read_qr', function() {
        qr();//扫码
    });
})()



(function() {
    var error_code  = Tools._GET().error_code || '';
    var error_tpls = new Array("操作错误，请重新扫码", "参数错误，请重新扫码", "交易不存在，请重新扫码", "交易状态异常，请联系客服");
    
    if(error_code)
    {
        $("#error_msg").html(error_tpls[error_code]);
    }
    
})();


